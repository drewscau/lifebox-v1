<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\Services\PushNotificationService;
use Illuminate\Auth\Events\Registered;
use Stripe\StripeClient;

class UserAdminController extends Controller
{
    /**
     * List users
     *
     * @authenticated
     * @group Admin
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $limit =  $request->query('limit', 10);
        $searchText = $request->query("search", null);
        $sortBy =  $request->query("sort_by", 'id');
        $sortDirection = $request->query("sort_dir", 'asc');

        $users = UserService::search(
            $searchText,
            $sortBy,
            $sortDirection,
            $limit
        );

        foreach ($users as $user) {
            $user->subscription_status = $user->subscribed ? 'subscribed' : 'unsubscribed';
        }

        return response()->json($users);
    }

    /**
     * Get a user account?
     *
     * Get user->id, user->username with inbox_id?
     * Dunno why we have this endpoint? 🤷
     * @authenticated
     * @group User
     * @bodyParam username string users->username
     * @param Request $request
     * @return JsonResponse
     */
    public function getByAccount(Request $request)
    {
        $data = $request->validate([
            'username' => 'exists:users,username'
        ]);

        $user = User::select('id', 'username')
            ->where($data)->firstOrFail();

        if (isset($user)) {
            $user->subscription_status = $user->subscribed ? 'subscribed' : 'unsubscribed';
        } else {
            $user->subscription_status = 'unsubscribed';
        }

        $inboxFolder = FileService::getUserInboxFolder($user->id);
        $rs = $user->toArray() + ['inbox_id' => $inboxFolder->id];
        return response()->json($rs);
    }

    /**
     * Create a user
     *
     * Create user from admin? 🤷
     *
     * @authenticated
     * @group Admin
     * @bodyParam first_name string required
     * @bodyParam last_name string required
     * @bodyParam mobile number required
     * @bodyParam username string required
     * @bodyParam email string required
     * @bodyParam password string required
     * @bodyParam user_type string required one of: user, admin, general
     * @bodyParam user_status string required one of: active, inactive, subscribed, unsubscribed
     * @bodyParam profile_picture file image
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(Request $request)
    {
        $lifeboxSubdomain = config('app.subdomain');

        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mobile' => 'required|numeric|unique:users,mobile',
            'username' => 'required|max:255|unique:users,username,',
            'email' => 'required|email|unique:users,email,',
            'password' => 'required|min:8',
            'user_type' => ['required', Rule::in([User::USER_TYPE_USER, User::USER_TYPE_ADMIN, User::USER_TYPE_GENERAL])],
            'user_status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_SUBSCRIBED, User::STATUS_UNSUBSCRIBED])],
            'profile_picture' => 'nullable|image',
        ]);

        $data['account_number'] = 99999999999 - time();
        $data['lifebox_email'] = $data['username'] . "@$lifeboxSubdomain";
        $data['password'] =  bcrypt($data['password']);

        if ($request->file('profile_picture', null)) {
            $data['profile_picture'] = UserService::saveProfilePicture($request);
        }

        $user = UserService::create($data);
        if (!$user) {
            throw new \Exception('User Not Created');
        }

        if ($data['user_status'] == User::STATUS_ACTIVE || $data['user_status'] == User::STATUS_SUBSCRIBED) {
            $user->markEmailAsVerified();
        }

        if (!$user->isAdmin()) {
            try {
                $appName = config('app.name');
                $stripe = new StripeClient(config('services.stripe.secret'));
                $options = [
                    'description' => "This is the official Payment Customer account of $request->first_name $request->last_name, a $appName user.",
                    'email' => $request->email,
                    'name' => "$request->first_name $request->last_name",
                    'phone' => $request->mobile
                ];

                $user->createAsStripeCustomer($options);
                event(new Registered($user));
            } catch (\Exception $e) {
                return response()->json([
                    'msg' => $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return response()->json($user, Response::HTTP_CREATED);
    }

    /**
     * Show a user
     *
     * @authenticated
     * @group Admin
     * @urlParam user_id int required
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        $user->subscription_status = $user->subscribed('default') ? 'subscribed' : 'unsubscribed';

        return response()->json($user);
    }

    /**
     * Update a user
     *
     * @authenticated
     * @group Admin
     * @urlParam id int required user_id
     * @bodyParam first_name string required
     * @bodyParam last_name string required
     * @bodyParam email string required
     * @bodyParam lifebox_email string required
     * @bodyParam mobile number
     * @bodyParam account_number string number
     * @bodyParam username string
     * @bodyParam password string
     * @bodyParam user_type string required one of: user, admin, general
     * @bodyParam user_status string required one of: active, inactive, subscribed, unsubscribed
     * @bodyParam profile_picture file image
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mobile' => 'sometimes|numeric|unique:users,mobile,' . $id,
            'account_number' => 'nullable|numeric|unique:users,account_number,' . $id,
            'username' => 'max:255|unique:users,username,' . $id,
            'email' => 'required|email|unique:users,email,' . $id,
            'lifebox_email' => 'required|email|unique:users,lifebox_email,' . $id,
            'password' => 'min:8|confirmed',
            'user_type' => ['required', Rule::in([User::USER_TYPE_USER, User::USER_TYPE_ADMIN, User::USER_TYPE_GENERAL])],
            'user_status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_SUBSCRIBED, User::STATUS_UNSUBSCRIBED])],
            'profile_picture' => 'nullable|image',
        ]);

        if ($request->has('password')) {
            $data['password'] =  bcrypt($data['password']);
        }

        if ($request->file('profile_picture', null)) {
            $data['profile_picture'] = UserService::saveProfilePicture($request);
        }

        $user = tap($user)->update($data);

        if ($data['user_status'] == User::STATUS_ACTIVE || $data['user_status'] == User::STATUS_SUBSCRIBED) {
            $user->markEmailAsVerified();
        } else {
            $user->email_verified_at = null;
            $user->save();
        }

        if (!$user->isAdmin()) {
            event(new Registered($user));
        }

        return response()->json($user);
    }

    /**
     * Set user as inactive
     *
     * @authenticated
     * @group Admin
     * @urlParam user_id int required
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'user_status' => User::STATUS_INACTIVE
        ]);
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Send push notification to users device
     *
     * @authenticated
     * @group Admin
     * @urlParam user_id int required
     * @bodyParam title string required
     * @bodyParam body string required
     * @bodyParam subtitle string
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function sendPushNotificationToUsersDevice(Request $request, int $userId)
    {
        $FcmToken = [];
        $user = User::findOrFail($userId);
        $data = $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
        ]);
        if ($request->has('subtitle')) {
            $data['notification']['subtitle'] = $request->subtitle;
        }

        if (isset($user)) {
            $FcmTokens = $user->pushtokens()->latest()->get();
            if (!isset($FcmTokens)) {
                return response()->json([
                    'code' => 'PUSH_TOKEN_NOT_FOUND',
                    'message' => 'Push Tokens not set',
                ], Response::HTTP_BAD_REQUEST);
            }

            foreach ($FcmTokens as $token) {
                array_push($FcmToken, $token->push_token);
            }
            $message = PushNotificationService::sendPushNotification($data, $FcmToken);
            return response()->json(json_decode($message));
        }

        return response()->json([
            'code' => 'USER_NOT_FOUND',
            'message' => 'User not found',
        ], Response::HTTP_BAD_REQUEST);
    }
}
