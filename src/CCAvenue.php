<?php

declare(strict_types=1);

namespace MrRijal\CCAvenue;

use MrRijal\CCAvenue\Gateways\CCAvenueGateway;

/**
 * Main CCAvenue payment gateway facade for Laravel.
 *
 * Provides a simple API to initiate payments, handle responses,
 * and query order status via the CCAvenue gateway.
 */
class CCAvenue
{
    public function __construct(
        protected CCAvenueGateway $gateway
    ) {}

    /**
     * Initiate a payment: prepare request and redirect user to CCAvenue.
     *
     * @param  array<string, mixed>  $parameters  Payment parameters (order_id, amount, etc.)
     * @return mixed View or redirect response for the payment form
     */
    public function purchase(array $parameters = []): mixed
    {
        return $this->gateway->request($parameters)->send();
    }

    /**
     * Decode and return the response from CCAvenue after payment.
     *
     * @param  object  $request  Request containing encResp (e.g. Illuminate\Http\Request)
     * @return array<string, string> Decrypted response parameters
     */
    public function response(object $request): array
    {
        return $this->gateway->response($request);
    }

    /**
     * Prepare a payment request without sending (e.g. for custom flow).
     *
     * @param  array<string, mixed>  $parameters
     */
    public function prepare(array $parameters = []): CCAvenueGateway
    {
        return $this->gateway->request($parameters);
    }

    /**
     * Send a previously prepared gateway request (redirect to CCAvenue).
     *
     * @param  CCAvenueGateway  $order  Gateway instance returned by prepare()
     * @return mixed View or redirect response
     */
    public function process(CCAvenueGateway $order): mixed
    {
        return $order->send();
    }

    /**
     * Fetch order/transaction status from CCAvenue.
     *
     * @param  string|int  $orderNumber  Your order reference
     * @param  int  $transactionId  Optional CCAvenue transaction reference
     * @return array<string, mixed>|false Decoded order data or false on failure
     */
    public function getOrderDetails(string|int $orderNumber, int $transactionId = 0): array|false
    {
        return $this->gateway->getOrderDetails($orderNumber, $transactionId);
    }
}
