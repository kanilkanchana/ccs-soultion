<?php

namespace App\Http\Controllers;

use App\Services\CcsAdministration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CcsWhatsAppSignupController extends Controller
{
    public function start(Request $request): Response
    {
        $this->authorizeOwner($request);
        $settings = $this->settings();

        $state = Str::random(64);
        $request->session()->put('ccs_whatsapp_signup', [
            'state' => $state,
            'expires_at' => now()->addMinutes(20)->timestamp,
        ]);

        $query = http_build_query([
            'app_id' => $settings['app_id'],
            'config_id' => $settings['config_id'],
            'redirect_uri' => $settings['redirect_uri'],
            'state' => $state,
            'extras' => json_encode([
                'version' => 'v4',
                'featureType' => 'whatsapp_business_app_onboarding',
                'sessionInfoVersion' => '3',
            ], JSON_THROW_ON_ERROR),
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away('https://business.facebook.com/messaging/whatsapp/onboard/?'.$query)
            ->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function callback(Request $request): Response
    {
        $this->authorizeOwner($request);
        $settings = $this->settings();
        $pending = $request->session()->get('ccs_whatsapp_signup');
        $state = $request->query('state');

        if (!is_array($pending) || !is_string($state) ||
            $pending['expires_at'] < now()->timestamp || !hash_equals($pending['state'], $state)) {
            return $this->result('This signup session could not be verified. Start again from CCS Admin.', 403);
        }

        $request->session()->forget('ccs_whatsapp_signup');
        $code = $request->query('code');

        if ($request->query('error') !== null || !is_string($code) || $code === '' || strlen($code) > 4096) {
            return $this->result('Meta did not complete WhatsApp signup. Start again from CCS Admin.', 400);
        }

        $graph = 'https://graph.facebook.com/'.$settings['graph_version'];

        try {
            $response = Http::acceptJson()->withoutRedirecting()->timeout(10)->get($graph.'/oauth/access_token', [
                'client_id' => $settings['app_id'],
                'client_secret' => $settings['app_secret'],
                'redirect_uri' => $settings['redirect_uri'],
                'code' => $code,
            ]);
            $token = $response->json('access_token');

            if (!$response->successful() || !is_string($token) || $token === '') {
                return $this->result('Meta could not exchange the signup code. Start again from CCS Admin.', 502);
            }

            $response = Http::acceptJson()->withoutRedirecting()->withToken($token)->timeout(10)
                ->get($graph.'/'.$settings['phone_number_id'], ['fields' => 'id,platform_type']);
        } catch (ConnectionException) {
            return $this->result('The request to Meta timed out or could not connect. Start again from CCS Admin.', 502);
        }

        if (!$response->successful() || $response->json('id') !== $settings['phone_number_id']) {
            return $this->result('Meta did not grant access to the configured CCS WhatsApp number.', 422);
        }

        if ($response->json('platform_type') !== 'CLOUD_API') {
            return $this->result('Meta returned the CCS phone number, but Cloud API status is pending or unavailable.', 422);
        }

        return $this->result('Meta confirmed Cloud API access for the configured CCS number. You can close this page.', 200);
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless(app(CcsAdministration::class)->owner($request->user('web')), 403);
    }

    private function settings(): array
    {
        $settings = config('services.ccs_whatsapp_signup');

        foreach ($settings as $value) {
            abort_if(!is_string($value) || trim($value) === '', 503, 'WhatsApp signup has not been configured.');
        }

        return $settings;
    }

    private function result(string $message, int $status): Response
    {
        return response($message, $status, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
