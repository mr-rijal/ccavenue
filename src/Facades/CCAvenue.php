<?php

declare(strict_types=1);

namespace MrRijal\CCAvenue\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the CCAvenue payment gateway.
 *
 * @method static mixed purchase(array $parameters = [])
 * @method static array response(object $request)
 * @method static \MrRijal\CCAvenue\Gateways\CCAvenueGateway prepare(array $parameters = [])
 * @method static mixed process(\MrRijal\CCAvenue\Gateways\CCAvenueGateway $order)
 * @method static array|false getOrderDetails(string|int $orderNumber, int $transactionId = 0)
 *
 * @see \MrRijal\CCAvenue\CCAvenue
 */
class CCAvenue extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'CCAvenue';
    }
}
