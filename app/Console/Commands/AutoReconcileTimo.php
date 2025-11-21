<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\TicketNotificationService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoReconcileTimo extends Command
{
    protected $signature = 'payments:auto-reconcile-timo {--from=} {--to=}';
    protected $description = 'Tự động kiểm tra giao dịch Timo và khớp thanh toán vé';

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly TicketNotificationService $ticketNotificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $username = config('payments.timo.username');
        $password = config('payments.timo.password');
        $account = config('payments.timo.account');
        $apiUrl = rtrim(config('payments.timo.api_url'), '/');

        if (!$username || !$password || !$account || !$apiUrl) {
            $this->warn('Thiếu cấu hình Timo (username/password/account/api_url). Bỏ qua.');
            return self::FAILURE;
        }

        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : Carbon::now()->subDays(3);
        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))
            : Carbon::now();

        $payload = [
            'USERNAME' => $username,
            'PASSWORD' => $password,
            'DAY_BEGIN' => $from->format('d/m/Y'),
            'DAY_END' => $to->format('d/m/Y'),
            'NUMBER_Timo' => $account,
        ];

        $client = new Client(['timeout' => 20]);
        $this->info(sprintf('Đang lấy giao dịch Timo %s → %s...', $payload['DAY_BEGIN'], $payload['DAY_END']));

        try {
            $res = $client->post("{$apiUrl}/api/transactions", ['json' => $payload]);
            $data = json_decode($res->getBody()->getContents(), true);
        } catch (\Throwable $e) {
            $this->error('Không gọi được API Timo: ' . $e->getMessage());
            Log::error('Timo reconcile error', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        $transactions = $data['data']['transactionHistoryList'] ?? [];
        $processed = 0;
        $matched = 0;

        foreach ($transactions as $item) {
            $amount = (int) round((float) ($item['creditAmount'] ?? 0));
            if ($amount <= 0) {
                continue;
            }

            $refNo = $item['refNo'] ?? null;
            $description = $item['description'] ?? '';
            $code = $this->extractOrderCode($description);
            $processed++;

            if (!$code) {
                continue;
            }

            $payment = Payment::query()
                ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_MISMATCH])
                ->where('amount_vnd', $amount)
                ->whereHas('ticket.donHang', fn ($q) => $q->where('ma_don', $code))
                ->latest()
                ->first();

            if (!$payment) {
                continue;
            }

            try {
                $this->markPaymentSuccess($payment, $refNo);
                $matched++;
                $this->info("Đã khớp thanh toán {$code} ({$amount}đ) - ref {$refNo}");
            } catch (\Throwable $e) {
                Log::error('Reconcile update failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
                $this->warn("Không cập nhật được payment #{$payment->id}: {$e->getMessage()}");
            }
        }

        $this->info("Đã duyệt {$processed} giao dịch, khớp thành công {$matched}.");
        return self::SUCCESS;
    }

    private function extractOrderCode(?string $description): ?string
    {
        if (!$description) return null;

        if (preg_match('/ORD([A-Z0-9\-]+)/i', $description, $matches)) {
            return 'ORD' . strtoupper($matches[1]);
        }

        return null;
    }

    private function markPaymentSuccess(Payment $payment, ?string $refNo = null): void
    {
        $ticketForMail = null;

        DB::transaction(function () use ($payment, $refNo, &$ticketForMail) {
            $payment->update([
                'status' => Payment::STATUS_SUCCEEDED,
                'paid_at' => now(),
                'provider_ref' => $refNo ?: $payment->provider_ref,
            ]);

            $ticket = $payment->ticket()->lockForUpdate()->first();
            if ($ticket) {
                $this->paymentService->setAmountOnTicket($ticket, $payment->amount_vnd, $payment->id);
                $ticketForMail = $ticket->fresh();
            }
        });

        if ($ticketForMail) {
            $this->ticketNotificationService->sendTicketBookedMail($ticketForMail, $payment->fresh());
        }
    }
}
