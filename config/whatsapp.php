<?php

return [
    'enabled' => (bool) env('WHATSAPP_ENABLED', false),
    'api_version' => env('WHATSAPP_API_VERSION', 'v25.0'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'admin_phone' => env('WHATSAPP_ADMIN_PHONE'),
    'template' => env('WHATSAPP_TEMPLATE', 'jaspers_market_order_confirmation_v1'),
    'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en_US'),
];
