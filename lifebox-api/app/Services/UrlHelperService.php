<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class UrlHelperService
{
    protected const CONNECTION_ROUTES = [
        'mysql_tradebox' => 'tradebox',
    ];

    public function prefixRouteByDbConnection(string $routeNameToPrefix)
    {
        $connection = DB::getDefaultConnection();

        if (array_key_exists($connection, self::CONNECTION_ROUTES)) {
            return self::CONNECTION_ROUTES[$connection] . '.' . $routeNameToPrefix;
        }

        return $routeNameToPrefix;
    }
}
