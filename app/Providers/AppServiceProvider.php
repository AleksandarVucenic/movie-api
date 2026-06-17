<?php

namespace App\Providers;

use App\Repositories\MovieRepository;
use App\Repositories\MovieRepositoryInterface;
use App\Services\MovieApiProviders\MovieApiProvidersInterface;
use App\Services\MovieApiProviders\OmdbProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MovieApiProvidersInterface::class, fn () => new OmdbProvider(
            config('services.omdb.key'),
        ));

        $this->app->bind(MovieRepositoryInterface::class, MovieRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
