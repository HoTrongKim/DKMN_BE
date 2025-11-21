<?php

namespace App\Http\Controllers;

use App\Mail\MasterMail;
use Illuminate\Support\Facades\Mail;

class TestMail extends Controller
{
    public function testmail()
    {
        $subject = 'DKMN - Xác nhận đặt vé thành công';
        $view = 'emails.dkmn_booking';

        $payload = [
            'customer' => [
                'name' => 'Nguyễn Văn A',
                'phone' => '0901 234 567',
                'email' => 'khachhang@example.com',
                'bookingCode' => 'DKMN-23001',
            ],
            'trip' => [
                'route' => 'Bến xe Giáp Bát → Bến xe Miền Đông',
                'operator' => 'Phương Trang (Futa)',
                'vehicle' => 'Xe giường nằm',
                'departure' => '19:00 · 30/11/2025',
                'arrival' => '09:00 · 01/12/2025',
                'pickup' => 'Bến xe Giáp Bát',
                'dropoff' => 'Bến xe Miền Đông',
            ],
            'seats' => [
                ['label' => 'B5', 'price' => 1200000],
                ['label' => 'B6', 'price' => 1200000],
            ],
            'payment' => [
                'method' => 'Ví MoMo',
                'status' => 'Đã thanh toán',
            ],
        ];

        $payload['totals'] = [
            'subtotal' => array_sum(array_column($payload['seats'], 'price')),
            'discount' => 0,
            'total' => array_sum(array_column($payload['seats'], 'price')),
        ];

        $recipient = config('mail.test_recipient') ?? config('mail.from.address');
        if (empty($recipient)) {
            $recipient = 'team@dkmn.local';
        }

        try {
            Mail::to($recipient)->send(new MasterMail($subject, $view, $payload));

            return response()->json([
                'status' => true,
                'message' => 'Đã gửi email kiểm tra tới ' . $recipient,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể gửi email kiểm tra: ' . $e->getMessage(),
            ], 500);
        }
    }
}
