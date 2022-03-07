<?php

namespace Wahdam\LaravelSequence;

use Illuminate\Support\ServiceProvider;

class LaravelSequenceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'laravell-sequence');
    }
}
