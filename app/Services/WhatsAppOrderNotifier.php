<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Sales\Contracts\Order as OrderContract;

class WhatsAppOrderNotifier
{
    /**
     * Notify the admin about a new order.
     * Order of attempts: WhatsApp Cloud API (Meta), then Green API, then Twilio.
     * Never throws, so checkout is not affected.
     */
    public function notify(OrderContract $order): void
    {
        $recipient = $this->phone(config('whatsapp.admin_phone'));

        if (! $recipient) {
            return;
        }

        $orderNumber = (string) ($order->increment_id ?: $order->id);
        $customerName = trim($order->customer_first_name.' '.$order->customer_last_name) ?: 'Cliente';
        $deliveryDate = now()->addDays((int) config('whatsapp.delivery_days', 3))->format('M j, Y');

        if ($this->sendViaMeta($recipient, $orderNumber, $customerName, $deliveryDate)) {
            return;
        }

        if ($this->sendViaGreenApi($recipient, $orderNumber, $customerName, $deliveryDate)) {
            return;
        }

        $this->sendViaTwilio($recipient, $orderNumber, $customerName, $deliveryDate);
    }

    private function message(string $orderNumber, string $customerName, string $deliveryDate): string
    {
        return "⚠️⚠️⚠️⚠️¡Nueva orden en ConeDepot!⚠️⚠️⚠️⚠️\n\n"
            ."CONIagradecemos a {$customerName} por su compra!,\n\n"
            ."Se completo la orden #{$orderNumber}.\n\n"
            ."Otro cliente CONOquistado.\n\n"
            ."Por favor, CONIaplausos para el equipo!\n\n"
            ."Preparemos todo con mucho CONOcimiento.\n\n"
            ."Entrega estimada: {$deliveryDate}.\n\n"
            ."Otra venta más, ya somos CONOcidos en todo el pais!!";
    }

    private function sendViaMeta(string $recipient, string $orderNumber, string $customerName, string $deliveryDate): bool
    {
        $cfg = config('whatsapp.meta');

        if (! $cfg['enabled'] || ! $cfg['phone_number_id'] || ! $cfg['access_token']) {
            return false;
        }

        // With a template: Meta fixes the text. Without one: free text (only inside the 24h window).
        if ($cfg['template']) {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'template',
                'template' => [
                    'name' => $cfg['template'],
                    'language' => ['code' => $cfg['template_language']],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $customerName],
                            ['type' => 'text', 'text' => $orderNumber],
                            ['type' => 'text', 'text' => $deliveryDate],
                        ],
                    ]],
                ],
            ];
        } else {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'text',
                'text' => ['body' => $this->message($orderNumber, $customerName, $deliveryDate)],
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withToken($cfg['access_token'])
                ->post('https://graph.facebook.com/'.$cfg['api_version'].'/'.$cfg['phone_number_id'].'/messages', $payload);

            if ($response->successful() && data_get($response->json(), 'messages.0.id')) {
                Log::info('WhatsApp Cloud API order notification accepted', ['recipient' => $recipient, 'message_id' => data_get($response->json(), 'messages.0.id')]);

                return true;
            }

            Log::warning('WhatsApp Cloud API order notification failed, trying Green API', ['status' => $response->status(), 'response' => $response->json()]);
        } catch (Throwable $e) {
            Log::warning('WhatsApp Cloud API order notification error, trying Green API', ['error' => $e->getMessage()]);
        }

        return false;
    }

    private function sendViaMeta(string $recipient, string $orderNumber, string $customerName, string $deliveryDate): bool
    {
        $cfg = config('whatsapp.meta');

        if (! $cfg['enabled'] || ! $cfg['phone_number_id'] || ! $cfg['access_token']) {
            return false;
        }

        // With a template: Meta fixes the text. Without one: free text (only inside the 24h window).
        if ($cfg['template']) {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'template',
                'template' => [
                    'name' => $cfg['template'],
                    'language' => ['code' => $cfg['template_language']],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $customerName],
                            ['type' => 'text', 'text' => $orderNumber],
                            ['type' => 'text', 'text' => $deliveryDate],
                        ],
                    ]],
                ],
            ];
        } else {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'text',
                'text' => ['body' => $this->message($orderNumber, $customerName, $deliveryDate)],
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withToken($cfg['access_token'])
                ->post('https://graph.facebook.com/'.$cfg['api_version'].'/'.$cfg['phone_number_id'].'/messages', $payload);

            if ($response->successful() && data_get($response->json(), 'messages.0.id')) {
                Log::info('WhatsApp Cloud API order notification accepted', ['recipient' => $recipient, 'message_id' => data_get($response->json(), 'messages.0.id')]);

                return true;
            }

            Log::warning('WhatsApp Cloud API order notification failed, trying Green API', ['status' => $response->status(), 'response' => $response->json()]);
        } catch (Throwable $e) {
            Log::warning('WhatsApp Cloud API order notification error, trying Green API', ['error' => $e->getMessage()]);
        }

        return false;
    }

    private function sendViaGreenApi(string $recipient, string $orderNumber, string $customerName, string $deliveryDate): bool
    {
        $cfg = config('whatsapp.greenapi');

        if (! $cfg['enabled'] || ! $cfg['url'] || ! $cfg['id'] || ! $cfg['token']) {
            return false;
        }

        try {
            $response = Http::timeout(15)->post(
                rtrim($cfg['url'], '/').'/waInstance'.$cfg['id'].'/sendMessage/'.$cfg['token'],
                [
                    'chatId' => $cfg['chat_id'] ?: $recipient.'@c.us',
                    'message' => $this->message($orderNumber, $customerName, $deliveryDate),
                ]
            );

            if ($response->successful() && data_get($response->json(), 'idMessage')) {
                Log::info('Green API order notification accepted', ['recipient' => $recipient, 'message_id' => data_get($response->json(), 'idMessage')]);

                return true;
            }

            Log::warning('Green API order notification failed, trying Twilio', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (Throwable $e) {
            Log::warning('Green API order notification error, trying Twilio', ['error' => $e->getMessage()]);
        }

        return false;
    }

    private function sendViaTwilio(string $recipient, string $orderNumber, string $customerName, string $deliveryDate): bool
    {
        if (! config('twilio.enabled') || ! config('twilio.account_sid') || ! config('twilio.auth_token') || ! config('twilio.from')) {
            return false;
        }

        $payload = [
            'From' => 'whatsapp:'.config('twilio.from'),
            'To' => 'whatsapp:+'.$recipient,
        ];

        if (config('twilio.content_sid')) {
            $payload['ContentSid'] = config('twilio.content_sid');

            // Only for your own approved template: {{1}} name, {{2}} order number, {{3}} delivery date
            if (config('twilio.send_variables')) {
                $payload['ContentVariables'] = json_encode([
                    '1' => $customerName,
                    '2' => $orderNumber,
                    '3' => $deliveryDate,
                ]);
            }
        } else {
            $payload['Body'] = $this->message($orderNumber, $customerName, $deliveryDate);
        }

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->withBasicAuth(config('twilio.account_sid'), config('twilio.auth_token'))
                ->post('https://api.twilio.com/2010-04-01/Accounts/'.config('twilio.account_sid').'/Messages.json', $payload);

            if ($response->failed()) {
                Log::warning('Twilio WhatsApp order notification failed', ['recipient' => $recipient, 'status' => $response->status(), 'response' => $response->json()]);

                return false;
            }

            Log::info('Twilio WhatsApp order notification accepted', ['recipient' => $recipient, 'message_id' => data_get($response->json(), 'sid')]);

            return true;
        } catch (Throwable $e) {
            Log::warning('Twilio WhatsApp order notification error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function phone(?string $phone): ?string
    {
        return $phone ? preg_replace('/\D+/', '', $phone) : null;
    }
}
