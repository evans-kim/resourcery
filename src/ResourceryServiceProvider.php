<?php

namespace EvansKim\Resourcery;


use EvansKim\Resourcery\Command\CacheResourceCommand;
use EvansKim\Resourcery\Command\InstallResourceCommand;
use EvansKim\Resourcery\Command\MakeResourceCommand;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;

class ResourceryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../resourcery.php', 'resourcery');
        $this->loadMigrationsFrom(__DIR__."/../migrations");
        $this->loadRoutesFrom(__DIR__ . '/../route.php');
        $this->app->make('Illuminate\Database\Eloquent\Factory')->load(__DIR__ . '/../factories');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'resourcery');

        $this->publishes([
            __DIR__.'/../lang' => resource_path('lang/vendor/courier'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CacheResourceCommand::class,
                InstallResourceCommand::class,
                MakeResourceCommand::class,
            ]);
        }
    }
}
