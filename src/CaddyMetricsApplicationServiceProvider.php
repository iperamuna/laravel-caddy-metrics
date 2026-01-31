<?php

namespace Iperamuna\CaddyMetrics;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CaddyMetricsApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->authorization();
    }

    /**
     * Configure the Caddy Metrics authorization services.
     *
     * @return void
     */
    protected function authorization()
    {
        $this->gate();

        CaddyMetrics::auth(function ($request) {
            return Gate::check('viewCaddyMetrics', [$request->user()]) || app()->environment('local');
        });
    }

    /**
     * Register the Caddy Metrics gate.
     *
     * This gate determines who can access Caddy Metrics in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewCaddyMetrics', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
