<?php

namespace App\Http\Controllers;

use Laravel\Passport\Passport;

class PingController extends Controller
{
    public function __invoke()
    {
        return response()->json(
            [
                'server_time' => now(),
                'name' => config('app.name')
            ]
        );
    }
}
