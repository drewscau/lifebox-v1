<?php

namespace App\Http\Middleware;

use App\Exceptions\User\UserIsNotSubscribedOrNotAdminException;
use App\Services\UserService;
use Closure;
use Illuminate\Http\Request;

class SubscribedOrAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = UserService::getCurrentUser();

        if (UserService::isUserSubscribed($user) || $user->isAdmin()) {
            return $next($request);
        }

        throw new UserIsNotSubscribedOrNotAdminException('User is not subscribed or is not an admin.');
    }
}
