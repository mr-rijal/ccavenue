<?php

declare(strict_types=1);

namespace MrRijal\CCAvenue\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use MrRijal\CCAvenue\CCAvenueServiceProvider;
use Orchestra\Testbench\TestCase as Testbench;

abstract class TestCase extends Testbench
{
    /**
     * Get package providers required for tests.
     *
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CCAvenueServiceProvider::class,
        ];
    }

    /**
     * Define default CCAvenue config for tests.
     *
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('ccavenue', [
            'testMode' => true,
            'merchantId' => 'test_merchant',
            'accessCode' => 'test_access',
            'workingKey' => 'test_working_key_32_chars_long!!',
            'redirectUrl' => 'payment/success',
            'cancelUrl' => 'payment/cancel',
            'currency' => 'INR',
            'language' => 'EN',
        ]);
    }
}
