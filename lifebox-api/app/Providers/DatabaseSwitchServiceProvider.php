<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class DatabaseSwitchServiceProvider extends ServiceProvider
{
    protected const DB_SWITCH_CONNECTIONS = [
        '/api/tradebox' => 'mysql_tradebox',
    ];

    public function boot()
    {
        foreach (self::DB_SWITCH_CONNECTIONS as $path => $connection) {
            if ($connection !== DB::getDefaultConnection()
                && $this->app->request
                && Str::startsWith($this->app->request->getRequestUri(), $path)
            ) {
                DB::setDefaultConnection($connection);
                Config::set('database.db_switch_connection', $connection);
            }
        }

    }
}
