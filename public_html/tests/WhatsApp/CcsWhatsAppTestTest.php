<?php

namespace Tests\WhatsApp;

use App\Services\CcsWhatsAppTest;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** No application/database bootstrap, credentials or real network calls are used. */
class CcsWhatsAppTestTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container;
        Container::setInstance($this->container);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->container);
        $this->container->instance('config', new Repository([
            'ccs_whatsapp_test' => [
                'enabled' => true,
                'access_token' => 'FAKE_TEST_TOKEN_DO_NOT_USE',
                'phone_number_id' => '123456789',
                'recipient' => '+94 77 000 0000',
                'graph_version' => 'v25.0',
            ],
        ]));
        $this->container->instance(Factory::class, new Factory);
        $this->container->instance(RateLimiter::class, new RateLimiter(new CacheRepository(new ArrayStore)));
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        parent::tearDown();
    }

    private function fakeSender(string $number = '+1 (555) 145-0250'): void
    {
        Http::fake([
            'graph.facebook.com/v25.0/123456789?*' => Http::response(['display_phone_number' => $number]),
            'graph.facebook.com/v25.0/123456789/messages' => Http::response(['messages' => [['id' => 'wamid.TEST123']]]),
        ]);
    }

    public function test_preview_makes_no_request_and_hides_secrets(): void
    {
        Http::fake();
        $preview = (new CcsWhatsAppTest)->preview();
        self::assertSame('***0000', $preview['recipient']);
        self::assertStringNotContainsString('FAKE_TEST_TOKEN', json_encode($preview));
        Http::assertNothingSent();
    }

    public function test_disabled_feature_cannot_send(): void
    {
        Http::fake();
        config(['ccs_whatsapp_test.enabled' => false]);
        try {
            (new CcsWhatsAppTest)->send();
            self::fail('Disabled send must be blocked');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('disabled', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_invalid_settings_cannot_make_network_calls(): void
    {
        foreach ([
            ['access_token', ''],
            ['phone_number_id', 'https://evil.invalid'],
            ['recipient', '0770000000'],
            ['recipient', '94770000000,94770000001'],
            ['recipient', '15551450250'],
            ['graph_version', '../evil'],
        ] as [$key, $value]) {
            $original = config('ccs_whatsapp_test.'.$key);
            config(['ccs_whatsapp_test.'.$key => $value]);
            try {
                (new CcsWhatsAppTest)->send();
                self::fail('Invalid configuration was accepted: '.$key);
            } catch (RuntimeException) {
                // All failures must occur before HTTP, including the recipient allowlist.
            }
            config(['ccs_whatsapp_test.'.$key => $original]);
        }
        Http::assertNothingSent();
    }

    public function test_real_business_sender_is_blocked_before_message_post(): void
    {
        $this->fakeSender('+94 11 000 0000');
        try {
            (new CcsWhatsAppTest)->send();
            self::fail('Production sender was accepted');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('not the CCS Meta sandbox sender', $e->getMessage());
        }
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_sample_has_only_the_fixed_recipient_and_template(): void
    {
        $this->fakeSender();
        self::assertSame('wamid.TEST123', (new CcsWhatsAppTest)->send());
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://graph.facebook.com/v25.0/123456789/messages'
            && $request->hasHeader('Authorization', 'Bearer FAKE_TEST_TOKEN_DO_NOT_USE')
            && $request->data() === [
                'messaging_product' => 'whatsapp', 'to' => '94770000000', 'type' => 'template',
                'template' => ['name' => 'hello_world', 'language' => ['code' => 'en_US']],
            ]);
    }

    public function test_cooldown_blocks_duplicate_api_requests(): void
    {
        $this->fakeSender();
        $service = new CcsWhatsAppTest;
        $service->send();
        try {
            $service->send();
            self::fail('Second send was not rate limited');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Wait 60 seconds', $e->getMessage());
        }
        Http::assertSentCount(2);
    }

    public function test_expired_token_is_sanitized_and_does_not_post(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 190, 'message' => 'SECRET_RAW_RESPONSE']], 401)]);
        try {
            (new CcsWhatsAppTest)->send();
            self::fail('Expired token was accepted');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('HTTP 401, code 190', $e->getMessage());
            self::assertStringNotContainsString('SECRET_RAW_RESPONSE', $e->getMessage());
            self::assertStringNotContainsString('FAKE_TEST_TOKEN', $e->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_unknown_sender_response_is_blocked(): void
    {
        Http::fake(['*' => Http::response([])]);
        $this->expectExceptionMessage('not the CCS Meta sandbox sender');
        (new CcsWhatsAppTest)->send();
    }

    public function test_api_failure_does_not_trigger_an_automatic_retry(): void
    {
        Http::fakeSequence()
            ->push(['display_phone_number' => '+1 (555) 145-0250'])
            ->push(['error' => ['code' => 131030, 'message' => 'SECRET_RAW_RESPONSE']], 400);
        try {
            (new CcsWhatsAppTest)->send();
            self::fail('Rejected send was accepted');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('131030', $e->getMessage());
            self::assertStringNotContainsString('SECRET_RAW_RESPONSE', $e->getMessage());
        }
        Http::assertSentCount(2);
    }

    public function test_timeout_is_sanitized(): void
    {
        Http::fake(fn () => throw new ConnectionException('SECRET_HEADER_DATA'));
        try {
            (new CcsWhatsAppTest)->send();
            self::fail('Timeout was accepted');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('delivery may be unknown', $e->getMessage());
            self::assertStringNotContainsString('SECRET_HEADER_DATA', $e->getMessage());
        }
    }

    public function test_missing_message_id_does_not_claim_delivery(): void
    {
        Http::fakeSequence()->push(['display_phone_number' => CcsWhatsAppTest::SANDBOX_NUMBER])->push([]);
        $this->expectExceptionMessage('delivery is unknown');
        (new CcsWhatsAppTest)->send();
    }
}
