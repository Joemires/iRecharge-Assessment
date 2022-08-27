<?php

namespace App\Providers;

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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Support\Collection::macro('recursive', function () {
            return collect($this)->map(function ($value, $index) {
                if (is_array($value) || $value instanceof \stdClass) {
                    return collect($value)->recursive();
                }
                return $value;
            });
        });

        bind_payment_method();
    }
}
