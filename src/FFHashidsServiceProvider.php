<?php

namespace FF\LaravelHashids;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use FF\LaravelHashids\Contracts\Repository as RepositoryContract;

class FFHashidsServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config' => $this->app->basePath('config'),
            ], 'laravel-hashid-config');
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/hashids.php',
            'hashids'
        );

        $this->app->singleton('app.hashid', function () {
            return new Repository();
        });
        $this->app->alias('app.hashid', Repository::class);
        $this->app->alias('app.hashid', RepositoryContract::class);
    }
}
