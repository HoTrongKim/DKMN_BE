<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketData;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTicketData;

    public function test_qr_webhook_success_reconciles_ticket()
    {
        $ticket = $this->createTicketWithSeats([120000]);
        $init = $this->withHeaders(['Idempotency-Key' => 'init-qrcode'])
            ->postJson('/dkmn/payments/qr/init', [
                'ticketId' => $ticket->id,
                'channel' => 'vietqr',
                'testMode' => false,
            ]);

        $init->assertStatus(201);
        $data = $init->json('data');
        $paymentId = $data['paymentId'];
        $providerRef = $data['providerRef'];
        $amount = $data['amount'];

        $payload = [
            'providerRef' => $providerRef,
            'amount_vnd' => $amount,
            'status' => 'SUCCESS',
        ];
        $signature = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), config('payments.webhook_secret'));

        $webhook = $this->withHeaders([
            'X-Signature' => $signature,
            'X-Idempotency-Key' => 'wh-success',
        ])->postJson('/dkmn/payments/qr/webhook', $payload);

        $webhook->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 'SUCCEEDED',
            'amount_vnd' => $amount,
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'PAID',
            'paid_amount_vnd' => $amount,
        ]);

        // Replaying the same webhook should be idempotent.
        $replay = $this->withHeaders([
            'X-Signature' => $signature,
            'X-Idempotency-Key' => 'wh-success',
        ])->postJson('/dkmn/payments/qr/webhook', $payload);
        $replay->assertStatus(200);
    }

    public function test_webhook_mismatch_amount_marks_mismatch()
    {
        $ticket = $this->createTicketWithSeats([140000]);
        $init = $this->withHeaders(['Idempotency-Key' => 'init-mismatch'])
            ->postJson('/dkmn/payments/qr/init', [
                'ticketId' => $ticket->id,
                'channel' => 'vietqr',
                'testMode' => false,
            ]);

        $init->assertStatus(201);
        $data = $init->json('data');
        $paymentId = $data['paymentId'];

        $payload = [
            'providerRef' => $data['providerRef'],
            'amount_vnd' => 5000,
            'status' => 'SUCCESS',
        ];
        $signature = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), config('payments.webhook_secret'));

        $webhook = $this->withHeaders([
            'X-Signature' => $signature,
            'X-Idempotency-Key' => 'wh-mismatch',
        ])->postJson('/dkmn/payments/qr/webhook', $payload);

        $webhook->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 'MISMATCH',
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_webhook_allows_delta_within_allowed_limit()
    {
        config(['payments.allowed_amount_delta' => 1200]);

        $ticket = $this->createTicketWithSeats([160000]);
        $init = $this->withHeaders(['Idempotency-Key' => 'init-delta'])
            ->postJson('/dkmn/payments/qr/init', [
                'ticketId' => $ticket->id,
                'channel' => 'vietqr',
                'testMode' => false,
            ]);

        $init->assertStatus(201);
        $data = $init->json('data');
        $paymentId = $data['paymentId'];
        $providerRef = $data['providerRef'];
        $amount = $data['amount'];

        $payload = [
            'providerRef' => $providerRef,
            'amount_vnd' => $amount + 800,
            'status' => 'SUCCESS',
        ];
        $signature = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), config('payments.webhook_secret'));

        $webhook = $this->withHeaders([
            'X-Signature' => $signature,
            'X-Idempotency-Key' => 'wh-delta',
        ])->postJson('/dkmn/payments/qr/webhook', $payload);

        $webhook->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 'SUCCEEDED',
            'amount_vnd' => $amount,
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'PAID',
            'paid_amount_vnd' => $amount,
        ]);
    }

    public function test_onboard_confirm_marks_ticket_paid()
    {
        $ticket = $this->createTicketWithSeats([200000]);

        $response = $this->postJson('/dkmn/payments/onboard/confirm', [
            'ticketId' => $ticket->id,
            'operatorId' => 'OP-01',
            'note' => 'Collect cash on board',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'ticket_id' => $ticket->id,
            'method' => 'CASH_ONBOARD',
            'status' => 'SUCCEEDED',
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'PAID',
        ]);
    }
}
