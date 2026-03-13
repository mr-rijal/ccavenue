<?php

declare(strict_types=1);

namespace MrRijal\CCAvenue\Tests\Unit;

use MrRijal\CCAvenue\Exceptions\PaymentGatewayException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PaymentGatewayExceptionTest extends TestCase
{
    #[Test]
    public function can_create_with_message(): void
    {
        $e = new PaymentGatewayException('Invalid parameters');

        self::assertSame('Invalid parameters', $e->getMessage());
        self::assertSame(0, $e->getCode());
        self::assertNull($e->getPrevious());
    }

    #[Test]
    public function can_create_with_code_and_previous(): void
    {
        $previous = new \RuntimeException('Previous');
        $e = new PaymentGatewayException('Failed', 500, $previous);

        self::assertSame('Failed', $e->getMessage());
        self::assertSame(500, $e->getCode());
        self::assertSame($previous, $e->getPrevious());
    }

    #[Test]
    public function is_instance_of_exception(): void
    {
        $e = new PaymentGatewayException('Test');

        self::assertInstanceOf(\Exception::class, $e);
        self::assertInstanceOf(\Throwable::class, $e);
    }
}
