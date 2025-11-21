<?php

namespace App\Services\PaymentProviders;

use App\Contracts\PaymentProviderInterface;
use App\Models\Payment;
use Illuminate\Support\Str;

class GenericQrProvider implements PaymentProviderInterface
{
    public function __construct(
        protected string $key,
        protected string $label,
        protected string $account,
        protected string $baseUrl = 'https://img.vietqr.io/image'
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function generateQr(int $amount, Payment $payment): array
    {
        $note = $this->buildQrNote($payment);
        $payload = urlencode($note);
        $qrImageUrl = sprintf(
            '%s/%s-compact.png?amount=%d&addInfo=%s',
            rtrim($this->baseUrl, '/'),
            $this->account,
            $amount,
            $payload
        );

        return [
            'qrImageUrl' => $qrImageUrl,
            'providerRef' => strtoupper(sprintf('%s-%s', $this->key, $payment->id)),
            'metadata' => [
                'amount' => $amount,
                'ticket_id' => $payment->ticket_id,
                'note' => $note,
            ],
        ];
    }

    protected function buildQrNote(Payment $payment): string
    {
        $payment->loadMissing(['ticket.donHang']);

        $ticket = $payment->ticket;
        $order = $ticket?->donHang;

        $noteParts = [];

        if (!empty($order?->ma_don)) {
            $noteParts[] = $order->ma_don;
        } elseif (!empty($payment->provider_ref)) {
            $noteParts[] = $payment->provider_ref;
        } else {
            $noteParts[] = sprintf('PAY-%s', $payment->id ?? Str::random(6));
        }

        $noteParts[] = 'DKMN';

        return $this->normalizeNote(implode(' ', $noteParts));
    }

    protected function formatSeatList(?string $seatNumbers): ?string
    {
        if (!$seatNumbers) {
            return null;
        }

        $parts = array_filter(array_map(static function ($seat) {
            return trim($seat);
        }, explode(',', $seatNumbers)));

        if (empty($parts)) {
            return null;
        }

        return implode('-', $parts);
    }

    protected function normalizeNote(string $note): string
    {
        $normalized = Str::upper(Str::ascii($note));
        $normalized = preg_replace('/[^A-Z0-9\- ]/', ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', trim((string) $normalized));

        $maxLength = 60;
        if (strlen($normalized) > $maxLength) {
            $normalized = substr($normalized, 0, $maxLength);
        }

        return $normalized !== '' ? $normalized : 'DKMN';
    }
}

