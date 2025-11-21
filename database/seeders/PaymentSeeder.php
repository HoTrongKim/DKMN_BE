<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('payments')) {
            return;
        }

        DB::table('payments')->truncate();

        $tickets = DB::table('tickets')
            ->select('id', 'don_hang_id', 'total_amount_vnd', 'status', 'created_at')
            ->orderBy('id')
            ->get();

        if ($tickets->isEmpty()) {
            return;
        }

        $rows = [];
        $ticketUpdates = [];
        $qrProviders = ['MOMO', 'VNPAY', 'ZALOPAY'];
        $cashProviders = ['VCB', 'ACB'];

        foreach ($tickets as $index => $ticket) {
            $method = ($index % 2 === 0) ? 'QR' : 'CASH_ONBOARD';
            $status = match ($ticket->status) {
                'PAID' => 'SUCCEEDED',
                'CANCELLED' => 'FAILED',
                default => 'PENDING',
            };

            $providerList = $method === 'QR' ? $qrProviders : $cashProviders;
            $provider = $providerList[$index % count($providerList)];

            $createdAt = Carbon::parse($ticket->created_at, 'Asia/Ho_Chi_Minh');
            $paidAt = $status === 'SUCCEEDED' ? $createdAt->copy()->addMinutes(30) : null;

            $rows[] = [
                'id' => count($rows) + 1,
                'ticket_id' => $ticket->id,
                'method' => $method,
                'provider' => $provider,
                'provider_ref' => sprintf('%s-TKT%03d', strtoupper($provider), $ticket->id),
                'qr_image_url' => $method === 'QR' ? 'https://cdn.dkmn.local/mock-qr.png' : null,
                'amount_vnd' => (int) $ticket->total_amount_vnd,
                'status' => $status,
                'checksum' => strtoupper(Str::random(16)),
                'idempotency_key' => Str::uuid()->toString(),
                'webhook_idempotency_key' => $status === 'SUCCEEDED' ? Str::uuid()->toString() : null,
                'paid_at' => $paidAt,
                'expires_at' => $createdAt->copy()->addDay(),
                'created_at' => $createdAt,
                'updated_at' => $paidAt ?? $createdAt,
            ];

            if ($status === 'SUCCEEDED') {
                $ticketUpdates[] = [
                    'ticket_id' => $ticket->id,
                    'payment_id' => count($rows),
                    'amount' => (int) $ticket->total_amount_vnd,
                ];
            }
        }

        DB::table('payments')->insert($rows);

        foreach ($ticketUpdates as $update) {
            DB::table('tickets')
                ->where('id', $update['ticket_id'])
                ->update([
                    'payment_id' => $update['payment_id'],
                    'paid_amount_vnd' => $update['amount'],
                ]);
        }
    }
}
