<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Customer Model Configuration
    |--------------------------------------------------------------------------
    | Map the polymorphic user relationship profile to your authentic app model.
    */
    'customer_model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Logistics Configuration
    |--------------------------------------------------------------------------
    */
    'shipping_flat_rate' => 50.00, // Flat operational cost added to checkout

    /*
    |--------------------------------------------------------------------------
    | Regional Payment Omnichannel Drivers
    |--------------------------------------------------------------------------
    */
    'payment_driver' => env('CYBERCART_PAYMENT_DRIVER', 'paymob'),

    'payments' => [
        'paymob' => [
            'api_key'        => env('PAYMOB_API_KEY'),
            'integration_id' => env('PAYMOB_INTEGRATION_ID'),
            'iframe_id'      => env('PAYMOB_IFRAME_ID'),
            'hmac_secret'    => env('PAYMOB_HMAC_SECRET'),
        ],
        
        'tamara' => [
            'api_token' => env('TAMARA_API_TOKEN'),
            'sandbox'   => env('TAMARA_SANDBOX', true),
        ],
        'mada'   => [
        'secret_key' => env('MADA_SECRET_KEY'),
        'sandbox'    => env('MADA_SANDBOX', true),
        ],
    ],
];