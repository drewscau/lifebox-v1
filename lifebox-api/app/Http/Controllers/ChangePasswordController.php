<?php

namespace App\Http\Controllers;

use App\Rules\MatchOldPasswordRule;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChangePasswordController extends Controller
{
    /**
     * Change password
     *
     * Change password of currently logged_in user
     *
     * @authenticated
     * @group Auth
     * @bodyParam old_password string required
     * @bodyParam new_password string required
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $user = UserService::getCurrentUser();
        $data = $request->validate(
            [
                'old_password' => ['required', new MatchOldPasswordRule],
                'new_password' => 'required|string|min:8'
            ]
        );

        $user->update(
            [
                'password' => bcrypt($data['new_password'])
            ]
        );
        return response()->json([], Response::HTTP_ACCEPTED);
    }
}
