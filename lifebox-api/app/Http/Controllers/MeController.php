<?php

namespace App\Http\Controllers;

use App\Services\FileService;
use App\Services\SubscriptionService;
use App\Services\UserService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\ConsoleOutput;

class MeController extends Controller
{
    /**
     * Check Subscriptions
     *
     * @authenticated
     * @group Subscription
     * @return JsonResponse
     */
    public function checkSubscriptions()
    {
        $user = UserService::getCurrentUser();
        $latestSubscription = $user->subscriptions()->latest()->first();
        $data = [];
        $out = new ConsoleOutput();

        if (!$latestSubscription) {
            $data = [
                'subscriptions' => null,
                'status' => 'unsubscribed'
            ];

            return response()->json($data);
        }

        if ($latestSubscription->fromStripe()) {
            $stripeCustomer = $user->asStripeCustomer();
            $subscription = $stripeCustomer["subscriptions"]->data;
            $renewal_date = null;
            $subscription_started = null;
            $subscription_ended = null;

            if ($subscription) {
                $start_stamp = $subscription[0]["current_period_start"];
                $end_stamp = $subscription[0]["current_period_end"];
                $cancel_at = $subscription[0]["cancel_at"];
                $cancel_at_period_end = $subscription[0]["cancel_at_period_end"];

                $subscription_started = self::getSubscriptionRenewDate($start_stamp);
                $subscription_ended = self::getSubscriptionRenewDate($cancel_at);

                if (!$cancel_at_period_end) {
                    $renewal_date = self::getSubscriptionRenewDate($end_stamp);
                }
            }

            $data = [
                'subscriptions' => $user->subscriptions()->get(),
                'renewal_date' => $renewal_date,
                'subscription_started' => $subscription_started,
                'subscription_ended' => $subscription_ended,
                'status' => $user->subscribed() ? 'subscribed' : 'unsubscribed'
            ];
        } else {
            $existingTransaction = $latestSubscription->transactions()->latest()->first();
            $subscriptionStartDate = null;
            $subscriptionReceipt = null;
            $cancellationDate = null;
            $cancellationReason = null;
            $environment = null;

            $expiresDate = null;
            $autoRenewStatus = false;
            $isExpired = false;
            $expirationIntent = null;

            // Validate directly to the Apple or Android InApp Server for receipt validation if expired or not.
            if (isset($existingTransaction)) {
                if ($existingTransaction->fromAndroid()) {
                    $receiptObj = json_decode($existingTransaction->receipt, true);
                    $subscriptionStartDate = $receiptObj["purchaseTime"];
                    $subscriptionReceipt = SubscriptionService::verifyAndroidSubscription($latestSubscription->in_app_id, $existingTransaction->purchase_token);
                    $expiresDate = SubscriptionService::getPropertyFor($subscriptionReceipt, 'ExpiryTime');
                } else {
                    $out->writeln("checkSubscriptions: IOS!!!");
                    $out->writeln(json_encode($existingTransaction));

                    $subscriptionReceipt = SubscriptionService::verifyIOSSubscription($existingTransaction->receipt);
                    $environment = $subscriptionReceipt->getEnvironment();

                    if (!empty($subscriptionReceipt->getLatestReceiptInfo())) {
                        $latestReceiptInfo = $subscriptionReceipt->getLatestReceiptInfo()[0];
                        if ($latestReceiptInfo) {
                            $subscriptionStartDate = $latestReceiptInfo->getPurchaseDate();
                            $expiresDate = $latestReceiptInfo->getExpiresDate();
                            $cancellationDate = $latestReceiptInfo->getCancellationDate();
                            $cancellationReason = $latestReceiptInfo->getCancellationReason();
                        }
                    }

                    if (!empty($subscriptionReceipt->getPendingRenewalInfo())) {
                        $pendingRenewalInfo = $subscriptionReceipt->getPendingRenewalInfo()[0];
                        if ($pendingRenewalInfo) {
                            $autoRenewStatus = $pendingRenewalInfo->isAutoRenewStatus();
                            $expirationIntent = $pendingRenewalInfo->getExpirationIntent();
                        }
                    }
                }
            }


            if (isset($expiresDate)) {
                $isExpired = $expiresDate->isPast();
            }

            $data = [
                'valid' => !$latestSubscription->in_app_expired && $latestSubscription->isValidInApp(),
                'isExpired' => $isExpired,
                'cancellation_date' => $cancellationDate,
                'cancellation_reason' => $cancellationReason,
                'status' => $user->subscribed ? 'subscribed' : 'unsubscribed',
                'auto_renew' => $autoRenewStatus,
                'expirate_intent' => $expirationIntent,
                'subscriptions' => $user->subscriptions()->get(),
            ];
            if (isset($subscriptionStartDate)) {
                $data['subscription_started_date'] = $existingTransaction->fromAndroid() ? Carbon::createFromTimestampMs($subscriptionStartDate)->toISOString() : $subscriptionStartDate->getCarbon()->toISOString();
                $data['subscription_started_date_ms'] = $existingTransaction->fromAndroid() ? $subscriptionStartDate : $subscriptionStartDate->getCarbon()->getPreciseTimestamp(3);
                $data['subscription_started_date_pst'] = $existingTransaction->fromAndroid() ? Carbon::createFromTimestampMs($subscriptionStartDate, "America/Los_Angeles") : Carbon::createFromFormat('Y-m-d H:i:s', $subscriptionStartDate->getCarbon())->shiftTimezone("America/Los_Angeles");
            } else {
                $data['subscription_started_date'] = null;
                $data['subscription_started_date_ms'] = null;
                $data['subscription_started_date_pst'] = null;
            }

            if (isset($expiresDate)) {
                $data['subscription_expiry'] = $expiresDate->getCarbon()->toISOString();
                $data['subscription_expiry_ms'] = $expiresDate->getCarbon()->getPreciseTimestamp(3);
                $data['subscription_expiry_pst'] = Carbon::createFromFormat('Y-m-d H:i:s', $expiresDate->getCarbon())->shiftTimezone("America/Los_Angeles");
            } else {
                $data['subscription_expiry'] = null;
                $data['subscription_expiry_ms'] = null;
                $data['subscription_expiry_pst'] = null;
            }

            if ($existingTransaction->fromAndroid()) {
                SubscriptionService::UpdateUserStatusFromInApp($latestSubscription->in_app_status, $user, $latestSubscription, $isExpired);
            } else {
                if ($environment === "Sandbox") {
                    if (!$autoRenewStatus && $isExpired) {
                        $latestSubscription->in_app_expired = true;
                        $latestSubscription->update();

                        $user->user_status = User::STATUS_UNSUBSCRIBED;
                        $user->update();
                    }
                } else {
                    if (!$autoRenewStatus && isset($cancellationDate)) {
                        if ($isExpired) {
                            $latestSubscription->in_app_expired = true;
                            $latestSubscription->update();

                            $user->user_status = User::STATUS_UNSUBSCRIBED;
                            $user->update();
                        }
                    }
                }
            }
        }
        return response()->json($data);
    }

    /**
     * Get user
     *
     * Get details of current user logged-in
     *
     * @authenticated
     * @group User
     * @return JsonResponse
     */
    public function show()
    {
        $user = UserService::getUserWithStorageDetails();
        return response()->json($user);
    }

    /**
     * Get User File Detail
     *
     * @authenticated
     * @group Files
     * @return JsonResponse
     */
    public function getUserFileDetail()
    {
        $user = UserService::getCurrentUser();
        $baseFolder = FileService::getUserFolder($user->id);
        $trashFolder = FileService::getTrashedFolder($user->id);

        return response()->json([
            'user_folder' => $baseFolder,
            'trash_folder' => $trashFolder
        ]);
    }

    /**
     * Update user profile
     *
     * @authenticated
     * @group User
     * @bodyParam first_name string user first_name
     * @bodyParam last_name string user last_name
     * @bodyParam mobile string user mobile number
     * @bodyParam email string user email
     * @bodyParam profile_picture file
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $user = UserService::getCurrentUser();

        $data = $request->validate([
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'mobile' => 'sometimes|numeric|unique:users,mobile,' . $user->id,
            'email' => 'sometimes|unique:users,email,' . $user->id,
            'profile_picture' => 'nullable|image|mimes:jpeg,x-png,png,gif,jpg',
        ]);

        $profile_picture = $request->file('profile_picture');
        if ($profile_picture) {
            if ($user->profile_picture) {
                UserService::removeProfilePicture($user->profile_picture);
            }
            $data['profile_picture'] = UserService::saveProfilePicture($profile_picture);
        }

        $updatedUser = tap($user)->update($data);
        return response()->json($updatedUser);
    }

    /**
     * Remove photo
     *
     * Remove users profile picture
     *
     * @authenticated
     * @group User
     * @return JsonResponse
     */
    public function removePhoto()
    {
        $user = UserService::getCurrentUser();
        $isRemoved = UserService::removeProfilePicture($user->profile_picture);
        if ($isRemoved) {
            $user->update([
                'profile_picture' => null,
            ]);
            return response()->json([
                'code' => 'AVATAR_REMOVED',
                'message' => 'Profile picture successfully removed',
                'user' => $user
            ]);
        } else {
            return response()->json([
                'code' => 'AVATAR_NOT_EXISTED',
                'message' => 'Profile picture not existed',
                'profile_picture' => $user->profile_picture
            ]);
        }
    }

    private function getSubscriptionRenewDate($current_period_end)
    {
        return \Carbon\Carbon::createFromTimeStamp($current_period_end);
    }
}
