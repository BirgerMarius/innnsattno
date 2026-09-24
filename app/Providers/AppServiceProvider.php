<?php

namespace App\Providers;

use App\Services\FootballApi\Contracts\FootballProviderInterface;
use App\Services\FootballApi\Providers\ApiFootballProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        View::composer('*', function ($view) {
            $returnTo = request()->input('return_to');

            if (! is_string($returnTo) || ! $this->isSafePrintReturnUrl($returnTo)) {
                return;
            }

            $view->with('printReturnUrl', $returnTo);
        });
    }

    private function isSafePrintReturnUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || isset($parts['scheme'], $parts['host'], $parts['user'], $parts['pass'])
            || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return false;
        }

        $path = $parts['path'] ?? '';

        return ! preg_match('#(?:^|/)(?:print|utskrift|fasit)$#', $path);
    }
}
