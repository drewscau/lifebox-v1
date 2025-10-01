<?php

namespace App\Http\Controllers;

use App\Models\UserPushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Services\PushNotificationService;
use App\Services\UserService;

class PushNotificationController extends Controller
{
    /**
     * Store push token
     *
     * @authenticated
     * @group Push Notification
     * @bodyParam push_token string required
     * @bodyParam device_id string required
     * @bodyParam device_platform string required
     * @bodyParam device_os string required
     * @bodyParam device_os_version string required
     * @bodyParam device_name string required
     * @bodyParam device_model string required
     * @bodyParam device_manufacturer string required
     * @param Request $request
     * @return JsonResponse
     */
    public function storeToken(Request $request)
    {
        $user = UserService::getCurrentUser();
        $data = $request->validate([
            'push_token' => 'required|string',
            'device_id' => 'required|string',
            'device_platform' => 'required|string',
            'device_os' => 'required|string',
            'device_os_version' => 'required|string',
            'device_name' => 'required|string',
            'device_model' => 'required|string',
            'device_manufacturer' => 'required|string',
        ]);

        $hasTokens = UserService::hasExistingPushToken($request->push_token, $request->device_id);
        if ($hasTokens) {
            return response()->json([
                'code' => 'PUSH_TOKEN_EXISTED',
                'message' => 'Push Token already set',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data['user_id'] = $user->id;
            $pushToken = UserPushToken::create($data);
            return response()->json($pushToken);
        } catch (\Exception $e) {
            return response()->json([
                "code" => $e->getCode(),
                "message" => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Send push notification
     *
     * @authenticated
     * @group Push Notification
     * @bodyParam title string required
     * @bodyParam body string required
     * @param Request $request
     * @return JsonResponse
     */
    public function sendWebPushNotification(Request $request)
    {
        $user = UserService::getCurrentUser();
        $FcmTokens = $user->pushtokens()->latest()->get();
        $FcmToken = [];

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        if (!isset($FcmTokens)) {
            return response()->json([
                'code' => 'PUSH_TOKEN_NOT_FOUND',
                'message' => 'Push Tokens not set',
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = [
            'title' => $request->title,
            'body' => $request->body,
        ];

        if ($request->has('subtitle')) {
            $data['notification']['subtitle'] = $request->subtitle;
        }

        foreach ($FcmTokens as $token) {
            array_push($FcmToken, $token->push_token);
        }

        try {
            $message = PushNotificationService::sendPushNotification($data, $FcmToken);
            return response()->json(json_decode($message));
        } catch (\Exception $e) {
            return response()->json([
                "code" => $e->getCode(),
                "message" => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
