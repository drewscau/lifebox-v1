<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\UserService;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use App\Rules\UserIsValid;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend', 'sendToVerify');
    }

    /**
     * Resend verification email
     *
     * @unauthenticated
     * @group User
     * @param Request $request
     * @return JsonResponse
     */
    public function resend(Request $request)
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', new UserIsValid],
            'user_id' => ['nullable', 'exists:users,id']
        ]);

        if ($request->filled('user_id')) {
            $user = User::findOrFail($data['user_id']);
        } else {
            $user = User::where('lifebox_email', $data['email'])->first();
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already Verified']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification Email Sent',
            'email' => $user->email
        ]);
    }

    /**
     * Verify user email
     *
     * Mark the authenticated user's email address as verified.
     *
     * @unauthenticated
     * @group User
     * @urlParam id int user_id
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function verify(Request $request, $id)
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        Auth::loginUsingId($id);

        $user = auth()->user();
        $userKey = $user->getKey();
        $userCode = sha1($request->signature);

        if ($id != $user->getKey()) {
            throw new AuthorizationException;
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->away(config('app.web_url') . "/auth/verify-fail/{$userKey}/{$userCode}");
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $user->update([
            'email_verified_at' => now()
        ]);

        return redirect()->away(config('app.web_url') . "/auth/verification/{$userKey}/{$userCode}");
    }
}
