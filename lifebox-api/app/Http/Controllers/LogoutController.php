<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    /**
     * Logout user
     *
     * @authenticated
     * @group Auth
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request)
    {
        $user = UserService::getCurrentUser();
        $user->OauthAcessToken()->delete();
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
