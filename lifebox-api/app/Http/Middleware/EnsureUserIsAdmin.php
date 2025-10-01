<?php

namespace App\Http\Middleware;

use App\Services\UserService;
use Auth;
use Closure;
use Illuminate\Http\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = UserService::getCurrentUser();
        abort_if(
            !$user || !$user->isAdmin(),
            Response::HTTP_UNAUTHORIZED,
            '401 UnAuthorized'
        );

        return $next($request);
    }
}
