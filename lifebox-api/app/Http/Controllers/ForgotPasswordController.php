<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Send password reset email
     *
     * @unauthenticated
     * @group Auth
     * @bodyParam email string required User sign-up email (not necessarily same as lifebox email)
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function __invoke(Request $request)
    {
        $isFromWebRequest = $request->query('web');
        $data = $request->validate(
            [
                'email' => 'email|required',
            ]
        );
        $user = User::where('email', $data['email'])->first();
        $token = Str::random(15);

        if ($user) {
            DB::table('password_resets')->insert(
                [
                    'email' => $data['email'],
                    'token' => $token,
                ]
            );

            if ($isFromWebRequest == 1) {
                // $mobileDeepLink = env('APP_WEB_LINK', 'https://web.lifebox.net.au');
                $mobileDeepLink = config('app.web_url');
                $tokenUrl = "$mobileDeepLink/auth/password-reset?token=$token";
            } else {
                $env = env('APP_ENV', 'dev');
                $mobileDeepLink = env('APP_DEEP_LINK');
                $tokenUrl = $env === 'production'
                    ? "$mobileDeepLink/password-reset?token=$token"
                    : "http://lifebox-mobile-$env.web.app/password-reset?token=$token";
            }

            Mail::to($request->email)->send(new ForgotPasswordMail($tokenUrl, $user));
        }

        return response()->json(
            [
                'message' => "We've sent a password reset email to your email address.",
                'token' => $token
            ]
        );
    }
}
