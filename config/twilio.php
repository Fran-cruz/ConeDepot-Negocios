<?php

return [
    'enabled' => (bool) env('TWILIO_ENABLED', false),
    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    'from' => env('TWILIO_WHATSAPP_FROM'),
    'admin_phone' => env('TWILIO_WHATSAPP_ADMIN_PHONE'),
    'content_sid' => env('TWILIO_CONTENT_SID'),
    'send_variables' => (bool) env('TWILIO_SEND_VARIABLES', false),
];
