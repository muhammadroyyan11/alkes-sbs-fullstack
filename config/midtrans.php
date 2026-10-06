<?php

return [
    'merchant_id' => env('MIDTRANS_MERCHANT_ID', ''),
    'server_key' => env('MIDTRANS_SERVER_KEY', ''),
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
    'production' => env('MIDTRANS_PRODUCTION', false),
    '3ds' => env('MIDTRANS_3DS', true),
    'sanitized' => env('MIDTRANS_SANITIZED', true),
];
