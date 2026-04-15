<?php

namespace AndreiGhioc\BtiPay\Tests\Feature;

use AndreiGhioc\BtiPay\Facades\BtiPay;
use AndreiGhioc\BtiPay\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class PaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('btipay.username', 'test_user');
        config()->set('btipay.password', 'test_pass');
        config()->set('btipay.return_url', 'http://localhost/finish');
        config()->set('btipay.environment', 'sandbox');
    }

    public function test_it_can_register_a_one_phase_payment()
    {
        Http::fake([
            '*/rest/register.do' => Http::response([
                'orderId' => 'mock-uuid-1234-5678',
                'formUrl' => 'https://ecclients-sandbox.btrl.ro/payment/merchants/test/payment_ro.html?mdOrder=mock-uuid',
                'errorCode' => '0',
                'errorMessage' => 'Success',
            ], 200)
        ]);

        $response = BtiPay::register([
            'orderNumber' => 'ORD-12345',
            'amount'      => 1500, // 15 RON
            'currency'    => 946,
            'description' => 'Test Order',
            'email'       => 'test@example.com',
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('mock-uuid-1234-5678', $response->getOrderId());
        $this->assertStringContainsString('https://ecclients-sandbox.btrl.ro/payment/merchants/', $response->getFormUrl());

        Http::assertSent(function ($request) {
            parse_str($request->body(), $parsedBody);

            return str_contains($request->url(), '/payment/rest/register.do') &&
                   $request->hasHeader('Authorization', 'Basic ' . base64_encode('test_user:test_pass')) &&
                   $parsedBody['orderNumber'] === 'ORD-12345' &&
                   $parsedBody['amount'] === '1500';
        });
    }

    public function test_it_handles_registration_failure()
    {
        Http::fake([
            '*/rest/register.do' => Http::response([
                'errorCode' => '5',
                'errorMessage' => 'Access denied',
            ], 403)
        ]);

        $this->expectException(\AndreiGhioc\BtiPay\Exceptions\BtiPayAuthenticationException::class);
        $this->expectExceptionMessage('Authentication failed: access denied (401/403)');

        BtiPay::register([
            'orderNumber' => 'ORD-12345',
            'amount'      => 1500,
        ]);
    }
}
