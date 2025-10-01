<?php

namespace App\Http\Middleware;

use App\Services\UserService;
use Auth;
use Closure;
use Illuminate\Http\Response;

class EnsureUserIsActive
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

        if (!$user->activated) {
          return response()->json([
              'message' => 'This account is already terminated.',
          ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
