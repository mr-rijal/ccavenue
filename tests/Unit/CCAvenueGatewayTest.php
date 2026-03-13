<?php

declare(strict_types=1);

namespace MrRijal\CCAvenue\Tests\Unit;

use MrRijal\CCAvenue\Exceptions\PaymentGatewayException;
use MrRijal\CCAvenue\Gateways\CCAvenueGateway;
use MrRijal\CCAvenue\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CCAvenueGatewayTest extends TestCase
{
    private CCAvenueGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new CCAvenueGateway();
    }

    #[Test]
    public function encrypt_and_decrypt_roundtrip(): void
    {
        $plain = 'merchant_id=123&order_id=ORD-1&amount=100';
        $key = 'test_working_key_32_chars_long!!';

        $encrypted = $this->gateway->encrypt($plain, $key);
        self::assertNotSame($plain, $encrypted);
        self::assertMatchesRegularExpression('/^[a-f0-9]+$/i', $encrypted);

        $decrypted = $this->gateway->decrypt($encrypted, $key);
        self::assertSame($plain, $decrypted);
    }

    #[Test]
    public function hextobin_converts_hex_to_binary(): void
    {
        $hex = '48656c6c6f'; // "Hello"
        $binary = $this->gateway->hextobin($hex);
        self::assertSame('Hello', $binary);
    }

    #[Test]
    public function pkcs5_pad_adds_padding(): void
    {
        $padded = $this->gateway->pkcs5_pad('test', 8);
        self::assertSame(8, strlen($padded));
        self::assertStringStartsWith('test', $padded);
    }

    #[Test]
    public function get_end_point_returns_test_url_when_test_mode(): void
    {
        $url = $this->gateway->getEndPoint();
        self::assertStringContainsString('test.ccavenue.com', $url);
    }

    #[Test]
    public function get_api_end_point_returns_test_api_when_test_mode(): void
    {
        $url = $this->gateway->getAPIEndPoint();
        self::assertStringContainsString('apitest.ccavenue.com', $url);
    }

    #[Test]
    public function request_builds_and_encrypts_parameters(): void
    {
        $params = [
            'order_id' => 'ORD-123',
            'amount' => 99.99,
        ];

        $result = $this->gateway->request($params);

        self::assertSame($this->gateway, $result);
    }

    #[Test]
    public function request_throws_on_missing_required_parameters(): void
    {
        $this->expectException(PaymentGatewayException::class);

        $this->gateway->request([
            'order_id' => 'ORD-1',
            // missing: amount, etc. (merchant_id, currency, etc. come from config)
        ]);
    }

    #[Test]
    public function check_parameters_throws_on_invalid_amount(): void
    {
        $this->expectException(PaymentGatewayException::class);

        $this->gateway->checkParameters([
            'merchant_id' => 'M1',
            'currency' => 'INR',
            'redirect_url' => 'http://example.com/success',
            'cancel_url' => 'http://example.com/cancel',
            'language' => 'EN',
            'order_id' => 'ORD-1',
            'amount' => 'not-numeric',
        ]);
    }

    #[Test]
    public function response_decrypts_enc_response(): void
    {
        $plain = 'order_id=ORD-1&order_status=Success';
        $encResp = $this->gateway->encrypt($plain, $this->getTestWorkingKey());

        $request = (object) ['encResp' => $encResp];

        $decoded = $this->gateway->response($request);

        self::assertIsArray($decoded);
        self::assertArrayHasKey('order_id', $decoded);
        self::assertArrayHasKey('order_status', $decoded);
        self::assertSame('ORD-1', $decoded['order_id']);
        self::assertSame('Success', $decoded['order_status']);
    }

    #[Test]
    public function send_returns_view_with_enc_request_and_end_point(): void
    {
        $this->gateway->request([
            'order_id' => 'ORD-1',
            'amount' => 100,
        ]);

        $view = $this->gateway->send();

        self::assertInstanceOf(\Illuminate\Contracts\View\View::class, $view);
        $data = $view->getData();
        self::assertArrayHasKey('encRequest', $data);
        self::assertArrayHasKey('accessCode', $data);
        self::assertArrayHasKey('endPoint', $data);
        self::assertStringContainsString('initiateTransaction', (string) $data['endPoint']);
    }

    #[Test]
    public function initialize_api_request_overrides_endpoints(): void
    {
        $this->gateway->initializeApiRequest(
            'https://test.example.com/',
            'https://live.example.com/',
            '2.0'
        );

        self::assertStringContainsString('test.example.com', $this->gateway->getAPIEndPoint());
    }

    #[Test]
    public function get_order_details_returns_false_for_empty_order_number(): void
    {
        $result = $this->gateway->getOrderDetails('', 0);

        self::assertFalse($result);
    }

    private function getTestWorkingKey(): string
    {
        return (string) $this->app['config']->get('ccavenue.workingKey', 'test_working_key_32_chars_long!!');
    }
}
