<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Contracts\Order as OrderContract;

class WhatsAppOrderNotifier
{
    public function notify(OrderContract $order): void
    {
        if (! config('whatsapp.enabled') || ! config('whatsapp.access_token') || ! config('whatsapp.phone_number_id')) return;
        $recipients = array_filter([
            $this->phone($order->billing_address?->phone ?? $order->customer?->phone),
            $this->phone(config('whatsapp.admin_phone')),
        ]);
        foreach (array_unique($recipients) as $recipient) {
            $this->send($recipient, (string) ($order->increment_id ?: $order->id), (string) $order->customer_first_name);
        }
    }

    private function send(string $recipient, string $orderNumber, string $customerName): void
    {
        $response = Http::withToken(config('whatsapp.access_token'))->post(
            'https://graph.facebook.com/' . config('whatsapp.api_version') . '/' . config('whatsapp.phone_number_id') . '/messages',
            [
                'messaging_product' => 'whatsapp', 'to' => $recipient, 'type' => 'template',
                'template' => [
                    'name' => config('whatsapp.template'), 'language' => ['code' => config('whatsapp.template_language')],
                    'components' => [['type' => 'body', 'parameters' => [
                        ['type' => 'text', 'text' => $customerName ?: 'Cliente'],
                        ['type' => 'text', 'text' => $orderNumber],
                    ]]],
                ],
            ]
        );
        if ($response->failed()) Log::warning('WhatsApp order notification failed', ['recipient' => $recipient, 'status' => $response->status(), 'response' => $response->json()]);
    }

    private function phone(?string $phone): ?string
    {
        return $phone ? preg_replace('/\D+/', '', $phone) : null;
    }
}
