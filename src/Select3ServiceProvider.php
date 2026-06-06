<?php

namespace Ozankurt\Select3;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Ozankurt\Select3\View\Components\Select3 as Select3Component;

class Select3ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/select3.php', 'select3');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/select3.php' => $this->app->configPath('select3.php'),
            ], 'select3-config');
        }

        Blade::component('select3', Select3Component::class);
    }
}
