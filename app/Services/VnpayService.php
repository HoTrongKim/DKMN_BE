<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class VnpayService
{
    private readonly string $tmnCode;
    private readonly string $hashSecret;
    private readonly string $paymentUrl;
    private readonly string $returnUrl;
    private readonly string $ipnUrl;
    private readonly string $version;
    private readonly string $command;
    private readonly string $orderType;
    private readonly string $defaultLocale;
    private readonly int $expireMinutes;

    public function __construct()
    {
        $config = config('payments.vnpay', []);
        $this->tmnCode = (string) ($config['tmn_code'] ?? '');
        $this->hashSecret = (string) ($config['hash_secret'] ?? '');
        $this->paymentUrl = (string) ($config['payment_url'] ?? '');
        $this->returnUrl = (string) ($config['return_url'] ?? '');
        $this->ipnUrl = (string) ($config['ipn_url'] ?? '');
        $this->version = (string) ($config['version'] ?? '2.1.0');
        $this->command = (string) ($config['command'] ?? 'pay');
        $this->orderType = (string) ($config['order_type'] ?? 'other');
        $this->defaultLocale = (string) ($config['default_locale'] ?? 'vn');
        $this->expireMinutes = max(1, (int) ($config['expire_minutes'] ?? 15));
    }

    /**
     * Build a VNPAY payment URL for the given payment.
     *
     * @param  array<string, mixed>  $options
     * @return array{pay_url: string, query: array<string, string>, txn_ref: string}
     */
    public function buildPayUrl(Payment $payment, array $options = []): array
    {
        $this->assertConfigured();

        $txnRef = $this->normalizeTxnRef((string) $payment->id);
        $amount = max(0, (int) $payment->amount_vnd) * 100;

        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $expire = $now->copy()->addMinutes($this->expireMinutes);

        $data = array_filter([
            'vnp_Version' => $this->version,
            'vnp_Command' => $this->command,
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Amount' => $amount,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $txnRef,
            'vnp_OrderInfo' => $this->buildOrderInfo($payment, $options['order_info'] ?? null),
            'vnp_OrderType' => $this->orderType,
            'vnp_Locale' => $this->resolveLocale($options['locale'] ?? null),
            'vnp_ReturnUrl' => $options['return_url'] ?? $this->returnUrl,
            'vnp_IpAddr' => $options['ip_addr'] ?? '127.0.0.1',
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_ExpireDate' => $expire->format('YmdHis'),
            'vnp_BankCode' => $options['bank_code'] ?? null,
            'vnp_IpnUrl' => $this->ipnUrl,
        ], static fn ($value) => $value !== null && $value !== '');

        ksort($data);
        $query = http_build_query($data, '', '&', PHP_QUERY_RFC3986);
        $secureHash = $this->generateSignature($query);
        $payUrl = rtrim($this->paymentUrl, '?') . '?' . $query . '&vnp_SecureHash=' . $secureHash;

        return [
            'pay_url' => $payUrl,
            'query' => $data,
            'txn_ref' => $txnRef,
        ];
    }

    public function verifySignature(array $input): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $secureHash = $input['vnp_SecureHash'] ?? '';
        unset($input['vnp_SecureHash'], $input['vnp_SecureHashType']);
        ksort($input);

        $hashData = http_build_query($input, '', '&', PHP_QUERY_RFC3986);
        $calculated = $this->generateSignature($hashData);

        return $secureHash !== '' && hash_equals($calculated, $secureHash);
    }

    private function generateSignature(string $data): string
    {
        return hash_hmac('sha512', $data, $this->hashSecret);
    }

    private function normalizeTxnRef(string $value): string
    {
        $trimmed = preg_replace('/[^0-9A-Za-z]/', '', $value) ?: '0';
        return substr($trimmed, 0, 32);
    }

    private function resolveLocale(?string $value): string
    {
        if (!$value) {
            return $this->defaultLocale;
        }

        $normalized = strtolower($value);
        $allowed = ['vn', 'en'];

        return in_array($normalized, $allowed, true) ? $normalized : $this->defaultLocale;
    }

    private function buildOrderInfo(Payment $payment, ?string $custom = null): string
    {
        if ($custom && trim($custom) !== '') {
            return trim($custom);
        }

        $ticket = $payment->ticket()->with('donHang')->first();
        $orderCode = $ticket?->donHang?->ma_don;

        return $orderCode
            ? sprintf('Thanh toan don %s', $orderCode)
            : sprintf('Thanh toan ve %s', $payment->id);
    }

    private function assertConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new InvalidArgumentException('VNPAY credentials are not configured.');
        }
    }

    private function isConfigured(): bool
    {
        return $this->tmnCode !== '' && $this->hashSecret !== '' && $this->paymentUrl !== '';
    }
}
