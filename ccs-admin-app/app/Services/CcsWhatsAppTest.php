<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

/** Sandbox-only transport. Intentionally cannot accept a customer or arbitrary message. */
class CcsWhatsAppTest
{
    // The Meta-provided sandbox number claimed for CCS Messaging Dev.
    // This is a public sender number, NOT a credential or a customer's phone.
    public const SANDBOX_NUMBER = '15551450250';

    private function settings(): array
    {
        app(CcsCareFeatures::class)->api();
        if (config('ccs_whatsapp_test.enabled') !== true) {
            throw new RuntimeException('WhatsApp testing is disabled. Set CCS_WHATSAPP_TEST_ENABLED=true privately in .env.');
        }

        $token = trim((string) config('ccs_whatsapp_test.access_token'));
        $id = trim((string) config('ccs_whatsapp_test.phone_number_id'));
        $recipient = preg_replace('/[\s()+-]/', '', (string) config('ccs_whatsapp_test.recipient'));
        $version = (string) config('ccs_whatsapp_test.graph_version');

        if ($token === '' || preg_match('/\s/', $token)) {
            throw new RuntimeException('A valid test access token is required in .env. Never paste it into chat or command arguments.');
        }
        if (!preg_match('/^[0-9]{5,30}$/D', $id)) {
            throw new RuntimeException('Set the Meta test Phone Number ID (digits only); this is not the displayed phone number or WABA ID.');
        }
        if (!preg_match('/^[1-9][0-9]{7,14}$/D', $recipient)) {
            throw new RuntimeException('Configure exactly one international test recipient, e.g. +94 followed by the number without its leading zero.');
        }
        if ($recipient === self::SANDBOX_NUMBER) {
            throw new RuntimeException('The recipient must be your own verified test recipient, not the Meta sender.');
        }
        if (!preg_match('/^v[0-9]{1,3}\.0$/D', $version)) {
            throw new RuntimeException('Invalid Graph API version. Use the version shown in the Meta dashboard.');
        }

        return compact('token', 'id', 'recipient', 'version');
    }

    public function preview(): array
    {
        $settings = $this->settings();

        return [
            'sender' => '+'.self::SANDBOX_NUMBER,
            'recipient' => '***'.substr($settings['recipient'], -4),
            'graph_version' => $settings['version'],
            'template' => 'hello_world',
            'language' => 'en_US',
            'token' => 'configured (hidden)',
        ];
    }

    /** API acceptance is NOT proof of delivery. No retries: an ambiguous timeout must be checked first. */
    public function send(): string
    {
        $settings = $this->settings();
        $key = 'ccs-whatsapp-test:'.hash('sha256', $settings['id'].'|'.$settings['recipient']);
        if (!RateLimiter::attempt($key, 1, static fn () => true, 60)) {
            throw new RuntimeException('A test was attempted recently. Wait 60 seconds and check WhatsApp before sending again.');
        }

        $url = 'https://graph.facebook.com/'.$settings['version'].'/'.$settings['id'];

        try {
            // Verify the actual sender from Meta, not merely an ID supplied in .env.
            $sender = Http::withToken($settings['token'])->acceptJson()
                ->connectTimeout(5)->timeout(20)->withoutRedirecting()
                ->get($url, ['fields' => 'display_phone_number']);
            $this->check($sender);
            $display = preg_replace('/\D/', '', (string) $sender->json('display_phone_number'));
            if ($display !== self::SANDBOX_NUMBER) {
                throw new RuntimeException('Blocked: this Phone Number ID is not the CCS Meta sandbox sender. No message was sent.');
            }

            app(CcsCareFeatures::class)->api();
            $response = Http::withToken($settings['token'])->acceptJson()->asJson()
                ->connectTimeout(5)->timeout(20)->withoutRedirecting()
                ->post($url.'/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $settings['recipient'],
                    'type' => 'template',
                    'template' => ['name' => 'hello_world', 'language' => ['code' => 'en_US']],
                ]);
            $this->check($response);
            $messageId = $response->json('messages.0.id');
            if (!is_string($messageId) || !preg_match('/^wamid\.[A-Za-z0-9_+=\/.-]{1,500}$/D', $messageId)) {
                throw new RuntimeException('Meta did not return a usable message ID. Check WhatsApp before retrying; delivery is unknown.');
            }

            return $messageId;
        } catch (ConnectionException) {
            // Do not leak request headers, recipient data, or token-bearing exceptions into logs.
            throw new RuntimeException('Meta connection failed or timed out. Check WhatsApp/Meta before retrying; delivery may be unknown.');
        }
    }

    private function check(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $code = $response->json('error.code');
        $subcode = $response->json('error.error_subcode');
        $details = 'HTTP '.$response->status();
        if (is_numeric($code)) {
            $details .= ', code '.(int) $code;
        }
        if (is_numeric($subcode)) {
            $details .= ', subcode '.(int) $subcode;
        }
        // No raw API response: it can include personal information or secrets.
        throw new RuntimeException('Meta rejected the request ('.$details.'). Check token expiry, permissions, recipient verification and template availability in Meta.');
    }
}
