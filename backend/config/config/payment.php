<?php

return [

    /*
    |--------------------------------------------------------------------------
    */

'default' => env('PAYMENT_METHOD', 'credit_card'),

/*
|--------------------------------------------------------------------------*/

'methods' => [
    'credit_card' => [
        'provider' => 'stripe', // Example provider
        'api_key' => env('STRIPE_API_KEY'),
    ],
    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
    ],
],

/*
|--------------------------------------------------------------------------*/

'currency' => env('PAYMENT_CURRENCY', 'EUR'),
];