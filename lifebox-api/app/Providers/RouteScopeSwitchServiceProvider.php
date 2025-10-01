<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class RouteScopeSwitchServiceProvider extends ServiceProvider
{
    protected const SCOPE_SWITCH_CONNECTIONS = [
        '/api/tradebox' => 'tradebox',
    ];

    public function boot()
    {
        foreach (self::SCOPE_SWITCH_CONNECTIONS as $path => $scope) {
            if ($this->app->request
                && Str::startsWith($this->app->request->getRequestUri(), $path)
            ) {
                Config::set('passport.route_scope', $scope);
            }
        }

    }
}
