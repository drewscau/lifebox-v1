<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends Controller
{
    /**
     * Reset password
     *
     * @unauthenticated
     * @group Auth
     * @bodyParam password string required new password
     * @urlParam token string required password_resets token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, $token)
    {
        $data = $request->validate([
            'password' => 'required|min:3'
        ]);
        $tokenData = DB::table('password_resets')
            ->where('token', $token)
            ->first();
        abort_if(is_null($tokenData), Response::HTTP_NOT_FOUND);

        $user = User::where('email', $tokenData->email)->firstOrFail();
        $user->password = bcrypt($data['password']);
        $user->update();

        // User shouldn't reuse the token , delete the token
        DB::table('password_resets')
            ->where('email', $user->email)
            ->delete();

        return response()->json([
            'message' => 'Password successfully changed. Please login with new password'
        ]);
    }
}
