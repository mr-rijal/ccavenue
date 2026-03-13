<?php

declare(strict_types=1);

namespace MrRijal\CCAvenue\Tests\Unit;

use MrRijal\CCAvenue\CCAvenue;
use MrRijal\CCAvenue\Gateways\CCAvenueGateway;
use MrRijal\CCAvenue\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\Test;

class CCAvenueTest extends TestCase
{
    /** @var CCAvenueGateway&MockObject */
    private CCAvenueGateway $gateway;

    private CCAvenue $ccavenue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = $this->createMock(CCAvenueGateway::class);
        $this->ccavenue = new CCAvenue($this->gateway);
    }

    #[Test]
    public function purchase_delegates_to_gateway_request_and_send(): void
    {
        $params = ['order_id' => 'ORD-1', 'amount' => 100.00];
        $view = $this->createMock(\Illuminate\Contracts\View\View::class);

        $this->gateway->expects(self::once())
            ->method('request')
            ->with($params)
            ->willReturnSelf();
        $this->gateway->expects(self::once())
            ->method('send')
            ->willReturn($view);

        $result = $this->ccavenue->purchase($params);

        self::assertSame($view, $result);
    }

    #[Test]
    public function response_delegates_to_gateway(): void
    {
        $request = (object) ['encResp' => 'encrypted'];
        $decoded = ['order_id' => 'ORD-1', 'order_status' => 'Success'];

        $this->gateway->expects(self::once())
            ->method('response')
            ->with(self::identicalTo($request))
            ->willReturn($decoded);

        self::assertSame($decoded, $this->ccavenue->response($request));
    }

    #[Test]
    public function prepare_returns_gateway_instance(): void
    {
        $params = ['order_id' => 'ORD-1', 'amount' => 50.00];

        $this->gateway->expects(self::once())
            ->method('request')
            ->with($params)
            ->willReturn($this->gateway);

        $result = $this->ccavenue->prepare($params);

        self::assertSame($this->gateway, $result);
    }

    #[Test]
    public function process_calls_send_on_gateway(): void
    {
        $view = $this->createMock(\Illuminate\Contracts\View\View::class);

        $this->gateway->expects(self::once())
            ->method('send')
            ->willReturn($view);

        self::assertSame($view, $this->ccavenue->process($this->gateway));
    }

    #[Test]
    public function get_order_details_delegates_to_gateway(): void
    {
        $orderData = ['order_no' => 'ORD-1', 'order_status' => 'Shipped'];

        $this->gateway->expects(self::once())
            ->method('getOrderDetails')
            ->with('ORD-1', 0)
            ->willReturn($orderData);

        self::assertSame($orderData, $this->ccavenue->getOrderDetails('ORD-1'));
    }

    #[Test]
    public function get_order_details_with_transaction_id_passes_through(): void
    {
        $this->gateway->expects(self::once())
            ->method('getOrderDetails')
            ->with('ORD-1', 12345)
            ->willReturn([]);

        $this->ccavenue->getOrderDetails('ORD-1', 12345);
    }
}
