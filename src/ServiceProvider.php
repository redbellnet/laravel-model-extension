<?php

namespace RedBellNet\ModelExtension;

class ServiceProvider  extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../config/modelExtension.php';
        $this->publishes([$configPath => config_path('modelExtension.php')], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
