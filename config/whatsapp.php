<?php

return [
    'admin_phone' => env('WHATSAPP_ADMIN_PHONE', env('TWILIO_WHATSAPP_ADMIN_PHONE')),
    'delivery_days' => (int) env('WHATSAPP_DELIVERY_DAYS', 3),

    // WhatsApp Cloud API (Meta)
    'meta' => [
        'enabled' => (bool) env('WHATSAPP_ENABLED', false),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'template' => env('WHATSAPP_TEMPLATE'),
        'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'es'),
    ],

    'greenapi' => [
        'enabled' => (bool) env('GREENAPI_ENABLED', false),
        'url' => env('GREENAPI_URL'),
        'id' => env('GREENAPI_ID'),
        'token' => env('GREENAPI_TOKEN'),
        'chat_id' => env('GREENAPI_CHAT_ID'),
    ],
];
