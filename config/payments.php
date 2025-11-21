<?php

use Illuminate\Support\Str;

return [
    'test_amount_vnd' => env('TEST_AMOUNT_VND', 1200),
    'default_fare_vnd' => env('DEFAULT_FARE_VND', 1200),
    'allowed_amount_delta' => (int) env('ALLOWED_AMOUNT_DELTA', 0),
    'default_provider' => env('PAYMENT_QR_PROVIDER', 'vietqr'),
    'display_min_vnd' => (int) env('TRIP_PRICE_MIN_VND', 0),
    'display_max_vnd' => (int) env('TRIP_PRICE_MAX_VND', 0),
    'ticket_hold_minutes' => (int) env('TICKET_HOLD_MINUTES', 10),
    'providers' => [
        'vietqr' => [
            'label' => 'VietQR',
            // Timo (Ban Viet) - cập nhật tài khoản QR mặc định
            'account' => env('VIETQR_ACCOUNT', 'BVB-0793587033'),
        ],
        'momo' => [
            'label' => 'MoMo',
            // Đang QR VietQR với tài khoản Timo (Ban Viet)
            'account' => env('VIETQR_ACCOUNT', 'BVB-0793587033'),
        ],
        'zalopay' => [
            'label' => 'ZaloPay',
            'account' => env('ZALOPAY_ACCOUNT', 'ZP-1234567890'),
        ],
    ],
    'timo' => [
        'api_url' => env('TIMO_API_URL', 'https://api-timo.dzmid.io.vn'),
        'username' => env('TIMO_USERNAME'),
        'password' => env('TIMO_PASSWORD'),
        'account' => env('TIMO_ACCOUNT', '0793587033'),
    ],
    'bank' => [
        'api_url' => env('MSB_API_URL', 'https://api-msb.dzmid.io.vn'),
        'username' => env('MSB_USERNAME'),
        'password' => env('MSB_PASSWORD'),
        'account' => env('MSB_ACCOUNT', '7008032005'),
        'account_identifier' => env('BANK_ACCOUNT_IDENTIFIER', env('VIETQR_ACCOUNT', 'MSB-7008032005')),
        'account_payload_key' => env('BANK_ACCOUNT_FIELD', 'NUMBER_MSB'),
        'description_regex' => env('BANK_DESCRIPTION_REGEX', '/(ORD[0-9A-Z\\-]+)/i'),
    ],
    'intent_expiration_minutes' => env('PAYMENT_INTENT_EXP_MIN', 15),
    'webhook_secret' => env('WEBHOOK_SECRET', Str::random(32)),
    'vnpay' => [
        'payment_url' => env('VNPAY_PAYMENT_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'return_url' => env('VNPAY_RETURN_URL', rtrim(env('APP_URL', 'http://localhost'), '/') . '/api/dkmn/payments/vnpay/return'),
        'ipn_url' => env('VNPAY_IPN_URL', rtrim(env('APP_URL', 'http://localhost'), '/') . '/api/dkmn/payments/vnpay/ipn'),
        'version' => env('VNPAY_VERSION', '2.1.0'),
        'command' => env('VNPAY_COMMAND', 'pay'),
        'order_type' => env('VNPAY_ORDER_TYPE', 'other'),
        'default_locale' => env('VNPAY_LOCALE', 'vn'),
        'expire_minutes' => (int) env('VNPAY_EXPIRE_MINUTES', 15),
    ],
];
