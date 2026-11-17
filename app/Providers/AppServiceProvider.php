<?php

namespace App\Providers;

use App\Services\FootballApi\Contracts\FootballProviderInterface;
use App\Services\FootballApi\Providers\ApiFootballProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\HeaderMessageService;
use App\Services\PrintReturnUrl;
use App\Services\PrisonContext;

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
        View::composer('partials.header', function ($view) {
            $messages = app(HeaderMessageService::class);
            $view->with('headerMessage', $messages->forDate());
            $view->with('headerMessageNextChangeAt', $messages->nextChangeAt());
        });

        View::composer('*', function ($view) {
            $prisons = app(PrisonContext::class);
            $view->with('prisonHomeUrl', $prisons->homeUrl());
            $view->with('prisonUrl', fn (string $url): string => $prisons->contextUrl($url));

            $returnTo = request()->input('return_to');

            if (! is_string($returnTo) || ! app(PrintReturnUrl::class)->isSafe($returnTo)) {
                return;
            }

            $view->with('printReturnUrl', $returnTo);
        });
    }

}
