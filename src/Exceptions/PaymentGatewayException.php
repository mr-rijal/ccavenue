<?php

declare(strict_types=1);

namespace MrRijal\CCAvenue\Exceptions;

use Exception;
use Throwable;

/**
 * Thrown when payment gateway parameters are invalid or a gateway operation fails.
 *
 * Typical causes: missing/invalid merchant config, validation errors on request parameters.
 */
class PaymentGatewayException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
