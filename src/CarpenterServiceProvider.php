<?php

namespace Evandersonluks\Carpenter;

use Evandersonluks\Carpenter\Console\BuildCommand;
use Evandersonluks\Carpenter\Console\InstallCommand;
use Illuminate\Support\ServiceProvider;

class CarpenterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                BuildCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/carpenter.php', 'carpenter'
        );

        $this->publishes([
            __DIR__.'/../config/carpenter.php' => $this->app->configPath('carpenter.php'),
        ], 'carpenter');
    }
}
