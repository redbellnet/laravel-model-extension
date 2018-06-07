<?php

namespace RedBellNet\ModelExtension;

use RedBellNet\ModelExtension\Event\HandleModelEvent;
use RedBellNet\ModelExtension\Listeners\AddWhereToModelListener;

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

        //监听事件

        $this->app['events']->listen(HandleModelEvent::class, function (HandleModelEvent $event){
            (new AddWhereToModelListener())->handle($event);
        });
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
