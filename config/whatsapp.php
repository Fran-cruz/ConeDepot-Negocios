<?php

return [
    'admin_phone' => env('WHATSAPP_ADMIN_PHONE', env('TWILIO_WHATSAPP_ADMIN_PHONE')),
    'delivery_days' => (int) env('WHATSAPP_DELIVERY_DAYS', 3),

    'greenapi' => [
        'enabled' => (bool) env('GREENAPI_ENABLED', false),
        'url' => env('GREENAPI_URL'),
        'id' => env('GREENAPI_ID'),
        'token' => env('GREENAPI_TOKEN'),
        'chat_id' => env('GREENAPI_CHAT_ID'),
    ],
];
