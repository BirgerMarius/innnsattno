<?php

namespace App\Providers;

use App\Services\FootballApi\Contracts\FootballProviderInterface;
use App\Services\FootballApi\Providers\ApiFootballProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('path.public', function() {
            return base_path().'/public';
          });

        $this->app->bind(FootballProviderInterface::class, ApiFootballProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
