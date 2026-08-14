<?php

return [
    'driver' => env('SMS_DRIVER', 'log'),

    'philsms' => [
        'api_token' => env('PHILSMS_API_TOKEN'),
        'sender_id' => env('SMS_SENDER_NAME', 'PhilSMS'),
        'endpoint' => env('SMS_API_ENDPOINT', 'https://dashboard.philsms.com/api/v3/sms/send'),
    ],
];
