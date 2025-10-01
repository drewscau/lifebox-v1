<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Services\FileService;
use Illuminate\Http\Response;

class AccountController extends Controller
{
    /**
     * Unsubscribe email notification
     *
     * Doesn't look like this works?
     * Requires user but is not authenticated?
     *
     * @unauthenticated
     * @group Subscription
     * @urlParam user_id int required user
     * @param Request $request
     * @param User $user
     * @return array
     */
    public function mailUnsubscribe(Request $request, User $user)
    {
        if (!$request->hasValidSignature()) {
            abort(401);
        }

        return [
            'action' => "Unsubscribes Emails Notifications",
            'user' => $user
        ];
    }

    /**
     * Download files in an archive (zip)
     *
     * @unauthenticated
     * @group Files
     * @urlParam user_id int required user
     * @param Request $request
     * @param User $user
     */
    public function downloadAll(Request $request, User $user)
    {
        if (!$request->hasValidSignature()) {
            abort(401);
        }

        $userFolder = FileService::getUserFolder($user->id);
        $zipName = sprintf("%s %s's Lifebox Files", $user->first_name, $user->last_name);
        return FileService::downloadFolder($userFolder->id, $zipName);
    }

    /**
     * Terminate user subscription
     *
     * Set subscription status to invalid, set user status to inactive
     * @unauthenticated
     * @group Subscription
     * @urlParam user_id int required user
     * @param Request $request
     * @param User $user
     * @return string[]
     */
    public function terminate(Request $request, User $user)
    {
        if (!$request->hasValidSignature()) {
            abort(401);
        }

        $latestSubscription = $user->subscriptions()->latest()->first();
        if ($user->subscription('default')->cancelled() === false && $latestSubscription->fromStripe()) {
            $user->subscription('default')->cancelNow();
        } else {
            $latestSubscription->update(['in_app_status' => Subscription::IN_APP_STATUS_INVALID]);
            // SubscriptionService::cancelInAppSubscription($latestSubscription);
        }

        $user->update(['user_status' => User::STATUS_INACTIVE]);

        return ['message' => "Successfully Terminated User Account"];
    }
}
