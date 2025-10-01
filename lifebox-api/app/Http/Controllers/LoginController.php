<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Rules\UserIsValid;

class LoginController extends Controller
{
    /**
     * Login to generate access token
     *
     * @group Auth
     * @unauthenticated
     * @bodyParam email string required your lifebox email
     * @bodyParam password string required
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', new UserIsValid],
            'password' => ['required', 'min:5']
        ]);

        $toBeLoggedIn = User::where('lifebox_email', $data['email'])->first();

        if (Auth::attempt(['email' => $toBeLoggedIn->email, 'password' => $data['password']])) {
            $user = auth()->user();

            if (! $user->activated) {
                return response()->json([
                    'code' => 'ACCOUNT_TERMINATED',
                    'message' => 'This account has been terminated.',
                ], Response::HTTP_FORBIDDEN);
            }

            if (! $user->hasVerifiedEmail() && ! $user->isAdmin()) {
                return response()->json([
                    'code' => 'EMAIL_NOT_VERIFIED',
                    'message' => 'Login is restricted. Please check and verify your email address first.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $scopes = [];
            $scopes[] = config('passport.route_scope', 'lifebox');

            return response()->json([
                'user' => UserService::getUserWithStorageDetails(),
                'accessToken' => $user->generateToken($scopes),
            ]);
        }

        return response()->json([
            'code' => 'INVALID_CREDENTIALS',
            'message' => 'Invalid user credentials used. Please try again.'
        ], Response::HTTP_UNAUTHORIZED);
    }
}
