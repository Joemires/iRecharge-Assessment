<?php
return [
    'gateway' => 'flutterwave',
    'flutterwave' => [
        'secret_key' => env('FW_SECKEY'),
        'public_key' => env('FW_PUBKEY'),
        'encryption_key' => env('FW_ENCKEY')
    ]
];
