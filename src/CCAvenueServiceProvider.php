<?php

declare(strict_types=1);

namespace MrRijal\CCAvenue;

use Illuminate\Support\ServiceProvider;
use MrRijal\CCAvenue\Gateways\CCAvenueGateway;

/**
 * Laravel service provider for the CCAvenue payment package.
 *
 * Registers the CCAvenue gateway and publishes config and views.
 */
class CCAvenueServiceProvider extends ServiceProvider
{
    /**
     * Register package services (config, views, bindings).
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/config.php', 'ccavenue');

        $this->publishes([
            __DIR__.'/Config/config.php' => base_path('config/ccavenue.php'),
            __DIR__.'/Views/ccavenue.blade.php' => base_path('resources/views/vendor/ccavenue/ccavenue.blade.php'),
        ], 'ccavenue-config');

        // Load package views under the 'ccavenue' namespace (e.g. ccavenue::ccavenue)
        $this->loadViewsFrom(__DIR__.'/Views', 'ccavenue');
    }

    /**
     * Bootstrap: bind CCAvenue class and alias for facade.
     */
    public function boot(): void
    {
        $this->app->bind(CCAvenue::class, function ($app): CCAvenue {
            return new CCAvenue($app->make(CCAvenueGateway::class));
        });

        $this->app->alias(CCAvenue::class, 'CCAvenue');
    }
}
