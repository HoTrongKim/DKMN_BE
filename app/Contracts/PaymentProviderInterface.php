<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentProviderInterface
{
    public function key(): string;

    public function label(): string;

    /**
     * @return array{qrImageUrl?: string, providerRef?: string, metadata?: array}
     */
    public function generateQr(int $amount, Payment $payment): array;
}
