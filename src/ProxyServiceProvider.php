<?php

namespace Balerka\LaravelProxy;

use Illuminate\Support\ServiceProvider;

class ProxyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/proxy.php', 'proxy');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/proxy.php' => config_path('proxy.php'),
            ], 'proxy-config');
        }
    }
}
