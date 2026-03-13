<?php

declare(strict_types=1);

namespace MrRijal\CCAvenue\Tests\Feature;

use MrRijal\CCAvenue\CCAvenue;
use MrRijal\CCAvenue\Facades\CCAvenue as CCAvenueFacade;
use MrRijal\CCAvenue\Gateways\CCAvenueGateway;
use MrRijal\CCAvenue\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function ccavenue_is_bound_in_container(): void
    {
        $ccavenue = $this->app->make(CCAvenue::class);

        self::assertInstanceOf(CCAvenue::class, $ccavenue);
    }

    #[Test]
    public function ccavenue_alias_resolves_to_ccavenue_instance(): void
    {
        $ccavenue = $this->app->make('CCAvenue');

        self::assertInstanceOf(CCAvenue::class, $ccavenue);
    }

    #[Test]
    public function facade_returns_ccavenue_instance(): void
    {
        CCAvenueFacade::clearResolvedInstance('CCAvenue');

        $ccavenue = CCAvenueFacade::getFacadeRoot();

        self::assertInstanceOf(CCAvenue::class, $ccavenue);
    }

    #[Test]
    public function config_is_merged(): void
    {
        self::assertTrue($this->app['config']->get('ccavenue.testMode'));
        self::assertSame('test_merchant', $this->app['config']->get('ccavenue.merchantId'));
        self::assertSame('INR', $this->app['config']->get('ccavenue.currency'));
    }

    #[Test]
    public function gateway_can_be_resolved(): void
    {
        $gateway = $this->app->make(CCAvenueGateway::class);

        self::assertInstanceOf(CCAvenueGateway::class, $gateway);
    }
}
