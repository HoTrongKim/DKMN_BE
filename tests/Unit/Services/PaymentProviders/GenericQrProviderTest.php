<?php

namespace Tests\Unit\Services\PaymentProviders;

use App\Models\DonHang;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\PaymentProviders\GenericQrProvider;
use Tests\TestCase;

class GenericQrProviderTest extends TestCase
{
    public function test_note_contains_order_code_when_available(): void
    {
        $payment = new Payment([
            'id' => 1024,
        ]);

        $order = new DonHang([
            'ma_don' => 'ORD20231111-XYZ',
        ]);

        $ticket = new Ticket();
        $ticket->setRelation('donHang', $order);
        $payment->setRelation('ticket', $ticket);

        $provider = new GenericQrProvider('vietqr', 'VietQR', 'MSB-123456789');
        $data = $provider->generateQr(200000, $payment);

        $this->assertStringContainsString('ORD20231111-XYZ', $data['metadata']['note']);
        $this->assertStringContainsString('DKMN', $data['metadata']['note']);
    }

    public function test_note_falls_back_to_provider_ref_when_order_code_missing(): void
    {
        $payment = new Payment([
            'id' => 2048,
            'provider_ref' => 'VIETQR-2048',
        ]);

        $payment->setRelation('ticket', new Ticket());

        $provider = new GenericQrProvider('vietqr', 'VietQR', 'MSB-123456789');
        $data = $provider->generateQr(150000, $payment);

        $this->assertStringContainsString('VIETQR-2048', $data['metadata']['note']);
        $this->assertStringContainsString('DKMN', $data['metadata']['note']);
    }
}

