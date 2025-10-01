<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Subscription;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Models\NotificationType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Imdhemy\Purchases\Facades\Subscription as VerifySubscription;
use Simpleclick\GooglePlay\Subscriptions\SubscriptionPurchase;
use App\Services\UserService;
use Symfony\Component\Console\Output\ConsoleOutput;


class SubscriptionService
{
    /**
     * Save in-app purchase instance as well as its attached transaction
     *
     * @return \App\Models\Subscription
     */
    public static function saveInApp(array $data)
    {
        DB::beginTransaction();

        try {
            $inAppPurchase = Subscription::create($data);
            DB::commit();
            return $inAppPurchase;
        } catch (\Exception $e) {
            DB::rollBack();
        }

        return null;
    }

    /**
     * Save attached transaction to an in-app purchase instance
     *
     * @return \App\Models\SubscriptionTransaction
     */
    public static function saveTransaction(array $data)
    {
        DB::beginTransaction();

        try {
            $inAppTransaction = SubscriptionTransaction::create($data);
            DB::commit();
            return $inAppTransaction;
        } catch (\Exception $e) {
            DB::rollBack();
        }

        return null;
    }


    /**
     * Check if given subscription exists in database as well as its attached transaction
     *
     * @param string $productId, $originalTransactionId, $applicationUsername, $userI
     * @return boolean
     */
    public static function checkExistingSubscriptions($productId, $originalTransactionId, $applicationUsername = null, $userID)
    {
        $subscription = Subscription::where([
            ["in_app_id", $productId],
            ["in_app_expired", false],
            ["user_id", "!=", $userID]
        ])
            ->whereHas('transactions', function ($query) use ($originalTransactionId) {
                $query->where('original_transaction_id', $originalTransactionId);
            })
            ->first();

        return $subscription;
    }

    /**
     * Check if given subscription exists in database as well as its attached transaction
     *
     * @param string $productId, $originalTransactionId, $applicationUsername, $userI
     * @return boolean
     */
    public static function checkExistingSubscriptionsAndTransaction($productId, $transactionId, $originalTransactionId, $userID)
    {
        $subscription = Subscription::where([
            ["in_app_id", $productId],
            ["in_app_expired", false],
            ["user_id", "!=", $userID]
        ])
            ->whereHas('transactions', function ($query) use ($originalTransactionId, $transactionId) {
                $query->where('original_transaction_id', $originalTransactionId);
                $query->where('transaction_id', $transactionId);
            })
            ->first();

        return $subscription;
    }

    /**
     * Cancel in-app purchased subscription
     *
     * @return response
     */
    public static function cancelInAppSubscription(Subscription $subscription)
    {
        $existingTransaction = $subscription->transactions()->latest()->first();
        if ($existingTransaction->fromAndroid()) {
            return VerifySubscription::googlePlay()->id($subscription->in_app_id)->token($existingTransaction->purchase_token)->cancel();
        } else {
            /**
             * Insert here for IOS side
             * Haven't found an API way of cancelling the in-app purchased subscription though...
             */
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Refund in-app purchased subscription
     *
     * @return response
     */
    public static function refundOrderedInAppSubscription(Subscription $subscription)
    {
        $existingTransaction = $subscription->transactions()->latest()->first();
        if ($existingTransaction->fromAndroid()) {
            return VerifySubscription::googlePlay()->id($subscription->in_app_id)->token($existingTransaction->purchase_token)->refund();
        } else {
            /**
             * Insert here for IOS side
             * Haven't found an API way of cancelling the in-app purchased subscription though...
             */
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Revoke in-app purchased subscription
     *
     * @return response
     */
    public static function revokeOrderedInAppSubscription(Subscription $subscription)
    {
        $existingTransaction = $subscription->transactions()->latest()->first();
        if ($existingTransaction->fromAndroid()) {
            return VerifySubscription::googlePlay()->id($subscription->in_app_id)->token($existingTransaction->purchase_token)->revoke();
        } else {
            /**
             * Insert here for IOS side
             * Haven't found an API way of cancelling the in-app purchased subscription though...
             */
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Revoke in-app purchased receipt
     *
     * @return response
     */
    public static function revokeInAppReceipt(string $type, string $productId, string $token)
    {
        if ($type === SubscriptionTransaction::TRANSACTION_ANDROID) {
            return VerifySubscription::googlePlay()->id($productId)->token($token)->revoke();
        } else {
            /**
             * Insert here for IOS side
             * Haven't found an API way of cancelling the in-app purchased subscription though...
             */
        }
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Refund in-app purchased receipt
     *
     * @return response
     */
    public static function refundInAppReceipt(string $type, string $productId, string $token)
    {
        if ($type === SubscriptionTransaction::TRANSACTION_ANDROID) {
            return VerifySubscription::googlePlay()->id($productId)->token($token)->refund();
        } else {
            /**
             * Insert here for IOS side
             * Haven't found an API way of cancelling the in-app purchased subscription though...
             */
        }
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Cancel in-app purchased receipt
     *
     * @return response
     */
    public static function cancelInAppReceipt(string $type, string $productId, string $token)
    {
        if ($type === SubscriptionTransaction::TRANSACTION_ANDROID) {
            return VerifySubscription::googlePlay()->id($productId)->token($token)->cancel();
        } else {
            /**
             * Insert here for IOS side
             * Haven't found an API way of cancelling the in-app purchased subscription though...
             */
        }
        return response()->json([], Response::HTTP_NO_CONTENT);
    }


    /**
     * Return iOS Subscription receipt Details
     *
     * @return response
     */
    public static function verifyIOSSubscription($receiptData)
    {
        return VerifySubscription::appStore()->receiptData($receiptData)->verifyRenewable();
    }

    /**
     * Return Android Subscription receipt Details
     *
     * @return response
     */
    public static function verifyAndroidSubscription($productId, $purchaseToken)
    {
        // VerifySubscription::googlePlay()->id($productId)->token($purchaseToken)->acknowledge();
        return VerifySubscription::googlePlay()->id($productId)->token($purchaseToken)->get();
    }

    /**
     * Assign default value for a subscription property when failed to fetch (exclusively for Android In-App Purchases)
     *
     * @param \Simpleclick\GooglePlay\Subscriptions\SubscriptionPurchase $subscriptionReceipt
     * @param string $property
     * @return mixed
     */
    public static function getPropertyFor(SubscriptionPurchase $subscriptionReceipt, string $property)
    {
        $methodName = sprintf('get%s', $property);

        try {
            $value = $subscriptionReceipt->$methodName();
        } catch (\Throwable $th) {
            $value = null;
        }

        return $value;
    }


    /**
     * Return boolean either the Receipt is expired or not
     *
     * @param string $type ---> Either "android-playstore" or "ios-appstore"
     * @param string $productId --> Product ID of the InApp Purchase
     * @return response
     */
    public static function validateIfReceiptIsExpired($type, $productId, $token)
    {
        $expiresDate = null;
        if ($type === SubscriptionTransaction::TRANSACTION_ANDROID) {
            $subscriptionReceipt = self::verifyAndroidSubscription($productId, $token);
            $expiresDate = $subscriptionReceipt->getExpiryTime();
        } else {
            $subscriptionReceipt = self::verifyIOSSubscription($token);
            if (!empty($subscriptionReceipt->getLatestReceiptInfo())) {
                $latestReceiptInfo = $subscriptionReceipt->getLatestReceiptInfo()[0];
                if ($latestReceiptInfo) {
                    $expiresDate = $latestReceiptInfo->getExpiresDate();
                }
            }
        }

        if (isset($expiresDate) && $expiresDate->isPast()) {
            return $expiresDate->getCarbon()->isoFormat('ddd MMM D YYYY, h:mm:ss zZZ');
        } else {
            return null;
        }
    }


    public static function UpdateUserStatusFromInApp($inAppStatus, $user, $latestSubscription, $isExpired)
    {
        switch ($inAppStatus) {
            case Subscription::IN_APP_STATUS_CANCELED:
                if ($isExpired) {
                    if (isset($latestSubscription)) {
                        $latestSubscription->in_app_expired = true;
                        $latestSubscription->update();
                    }
                    if (isset($user)) {
                        $user->user_status = User::STATUS_UNSUBSCRIBED;
                        $user->update();
                    }
                } else {
                    if (isset($user)) {
                        $user->user_status = User::STATUS_SUBSCRIBED;
                        $user->update();
                    }
                }

                break;

            case Subscription::IN_APP_STATUS_REVOKED:
            case Subscription::IN_APP_STATUS_ON_HOLD:
            case Subscription::IN_APP_STATUS_PAUSED:
                if (isset($user)) {
                    $user->user_status = User::STATUS_UNSUBSCRIBED;
                    $user->update();
                }
                break;
            case Subscription::IN_APP_STATUS_RECOVERED:
            case Subscription::IN_APP_STATUS_RENEW:
            case Subscription::IN_APP_STATUS_IN_GRACE_PERIOD:
            case Subscription::IN_APP_STATUS_PAUSE_SCHEDULE_CHANGED:
                if (isset($user)) {
                    $user->user_status = User::STATUS_SUBSCRIBED;
                    $user->update();
                }
                break;

            default:
                if ($isExpired) {
                    if (isset($latestSubscription)) {
                        $latestSubscription->in_app_expired = true;
                        $latestSubscription->update();
                    }
                    if (isset($user)) {
                        $user->user_status = User::STATUS_UNSUBSCRIBED;
                        $user->update();
                    }
                }
                break;
        }
    }

    public static function sendNotificationFromInApp($inAppStatus, $user, $app_platform = 'android')
    {
        $exceptions = [Subscription::IN_APP_STATUS_RECOVERED];

        if ($app_platform == 'ios' || in_array($inAppStatus, $exceptions)) {
            $out = new ConsoleOutput();
            $FcmToken = [];
            $notification = [];
            $data = [];
            if (isset($user)) {
                $FcmTokens = $user->pushtokens()->latest()->get();
                if (isset($FcmTokens)) {
                    foreach ($FcmTokens as $token) {
                        array_push($FcmToken, $token->push_token);
                    }
                }
            }


            /**
             * Comment out for now all those notification that causes double notification on the app
             */

            switch ($inAppStatus) {
                case Subscription::IN_APP_STATUS_CANCELED:

                    break;

                case Subscription::IN_APP_STATUS_REVOKED:

                    break;
                case Subscription::IN_APP_STATUS_ON_HOLD:
                    // $notification = [
                    //     'title' => NotificationType::SUBSCRIPTION_ON_HOLD["title"],
                    //     'body' => NotificationType::SUBSCRIPTION_ON_HOLD["text"],
                    // ];
                    // $data = [
                    //     "notificationType" => Subscription::IN_APP_STATUS_ON_HOLD
                    // ];
                    break;
                case Subscription::IN_APP_STATUS_PAUSED:
                    // $notification = [
                    //     'title' => NotificationType::SUBSCRIPTION_PAUSED["title"],
                    //     'body' => NotificationType::SUBSCRIPTION_PAUSED["text"],
                    // ];
                    // $data = [
                    //     "notificationType" => Subscription::IN_APP_STATUS_PAUSED
                    // ];
                    break;
                case Subscription::IN_APP_STATUS_RECOVERED:
                    $notification = [
                        'title' => NotificationType::SUBSCRIPTION_RECOVERED["title"],
                        'body' => NotificationType::SUBSCRIPTION_RECOVERED["text"],
                    ];
                    $data = [
                        "notificationType" => Subscription::IN_APP_STATUS_RECOVERED
                    ];
                    break;
                case Subscription::IN_APP_STATUS_RENEW:
                    $notification = [
                        'title' => 'Subscription Renewed',
                        'body' => 'An active subscription was renewed.',
                    ];
                    $data = [
                        "notificationType" => Subscription::IN_APP_STATUS_RENEW
                    ];
                    break;
                case Subscription::IN_APP_STATUS_APPROVED:
                    $notification = [
                        'title' => 'Subscription Approved',
                        'body' => 'Your subscription was approved.',
                    ];
                    $data = [
                        "notificationType" => Subscription::IN_APP_STATUS_APPROVED
                    ];
                    break;
                case Subscription::IN_APP_STATUS_IN_GRACE_PERIOD:
                    // $notification = [
                    //     'title' => NotificationType::SUBSCRIPTION_IN_GRACE_PERIOD["title"],
                    //     'body' => NotificationType::SUBSCRIPTION_IN_GRACE_PERIOD["text"],
                    // ];
                    // $data = [
                    //     "notificationType" => Subscription::IN_APP_STATUS_IN_GRACE_PERIOD
                    // ];
                    break;
                case Subscription::IN_APP_STATUS_PAUSE_SCHEDULE_CHANGED:
                    // $notification = [
                    //     'title' => NotificationType::SUBSCRIPTION_PAUSE_SCHEDULE_CHANGED["title"],
                    //     'body' => NotificationType::SUBSCRIPTION_PAUSE_SCHEDULE_CHANGED["text"],
                    // ];
                    // $data = [
                    //     "notificationType" => Subscription::IN_APP_STATUS_PAUSE_SCHEDULE_CHANGED
                    // ];
                    break;
            }

            if (!empty($notification) && !empty($FcmToken)) {
                $out->writeln("sendPushNotification sending: ");
                $message = PushNotificationService::sendPushNotification($notification, $FcmToken, $data);
                $out->writeln("sendPushNotification message: " . print_r($message, true));
            }
        }
    }


    public static function androidRTDNInAppSubscription($productID, $receiptToken, $isExpired, $receiptDetails, $inAppStatus)
    {
        $out = new ConsoleOutput();
        $user = null;
        $subscription = null;
        $latestSubscription = null;
        $existingTransaction = null;
        $inAppTransaction = null;
        $productData = [];
        $transactionData = [];

        $orderID = $receiptDetails->getOrderID();
        $splitOrderID = explode("..", $orderID);
        $originalTransactionId = $splitOrderID[0];

        $latestSubscription = Subscription::where([
            ["in_app_id", $productID]
        ])
            ->whereHas('transactions', function ($query) use ($originalTransactionId, $receiptToken) {
                $query->where('original_transaction_id', $originalTransactionId);
                $query->where('purchase_token', $receiptToken);
            })
            ->first();

        if (isset($latestSubscription)) {
            $user = User::findOrFail($latestSubscription->user_id);
        }

        $existingTransaction = SubscriptionTransaction::where([
            ["original_transaction_id", $originalTransactionId],
            ["purchase_token", $receiptToken],

        ])->first();

        if ($latestSubscription) {
            $productData = [
                "type"                                  => Subscription::SUBSCRIPTION_IN_APP,
                "in_app_status"                         => $inAppStatus,
                "in_app_alias"                          => isset($latestSubscription) ? $latestSubscription->in_app_alias : null,
                "in_app_description"                    => isset($latestSubscription) ? $latestSubscription->in_app_description : null,
                "in_app_id"                             => $productID,
                "in_app_title"                          => isset($latestSubscription) ? $latestSubscription->in_app_title : null,
                "in_app_type"                           => isset($latestSubscription) ? $latestSubscription->in_app_type : null,
                "in_app_valid"                          => isset($latestSubscription) ? $latestSubscription->in_app_valid : null,
                "in_app_billing_retry_period"           => isset($latestSubscription) ? $latestSubscription->in_app_billing_retry_period : 0,
                "in_app_trial_period"                   => isset($latestSubscription) ? $latestSubscription->in_app_trial_period : 0,
                "in_app_intro_period"                   => isset($latestSubscription) ? $latestSubscription->in_app_intro_period : 0,
                "in_app_expired"                        => $isExpired,
                'in_app_applicationUsername'            => isset($latestSubscription) ? $latestSubscription->in_app_applicationUsername : null,
                'in_app_renewalIntent'                  => $inAppStatus,
            ];

            $expiryTimeMillis = $receiptDetails->getExpiryTime();
            $inAppExpiryDate = $expiryTimeMillis->getCarbon()->toISOString();
            $purchaseTime = $receiptDetails->getStartTime();
            $inAppPurchaseDate = $purchaseTime->getCarbon()->toISOString();;

            $productData['in_app_expiryDate'] = $inAppExpiryDate;
            $productData['in_app_purchaseDate'] = $inAppPurchaseDate;

            if (isset($user)) {
                $productData['user_id'] = $user->id;

                $out->writeln("sendNotificationFromInApp");
                self::sendNotificationFromInApp($inAppStatus, $user);
            }


            $out->writeln("saveInApp: ");
            $subscription = self::saveInApp($productData);
            if (!$subscription) {
                $out->writeln("RENEW_SUBSCRIPTION_FAILED: ");
                return response()->json([
                    "error" => "RENEW_SUBSCRIPTION_FAILED",
                    "message" => "Renewing In-App Purchase Subscription Not Saved",
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        if ($existingTransaction) {
            $transactionData = [
                "subscription_id"                   => $subscription->id,
                "type"                              => SubscriptionTransaction::TRANSACTION_ANDROID,
                "in_app_ownership_type"             => isset($existingTransaction) ? $existingTransaction->in_app_ownership_type : 0,
                "is_in_intro_offer_period"          => isset($existingTransaction) ? $existingTransaction->is_in_intro_offer_period : 0,
                "is_trial_period"                   => isset($existingTransaction) ? $existingTransaction->is_trial_period : 0,
                "subscription_group_identifier"     => isset($existingTransaction) ?  $existingTransaction->subscription_group_identifier : null,
                "expires_date"                      => $inAppExpiryDate,
                "original_purchase_date"            => isset($existingTransaction) ? $existingTransaction->original_purchase_date : null,
                "purchase_date"                     => $inAppPurchaseDate,
                "transaction_id"                    => $orderID,
                "purchase_token"                    => $receiptToken,
                "signature"                         => isset($existingTransaction) ?  $existingTransaction->signature : null,
                "developerPayload"                  => isset($existingTransaction) ? $receiptDetails->getDeveloperPayload() : null,
                "receipt"                           => isset($existingTransaction) ? $existingTransaction->receipt : null,
                "original_transaction_id"           => $originalTransactionId,
            ];

            $inAppTransaction = self::saveTransaction($transactionData);
            if (!$inAppTransaction) {
                return response()->json([
                    "error" => "RENEW_TRANSACTION_FAILED",
                    "message" => "Renewing In-App Subscription Transaction Not Saved"
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        if (isset($user)) {
            self::UpdateUserStatusFromInApp($inAppStatus, $user, $latestSubscription, $isExpired);
        }

        $out->writeln("Successfully Renew In-App Subscription: ");
        return response()->json([
            "message" => "Successfully Renew In-App Subscription",
            "subscription" => $subscription,
            "transaction" => $inAppTransaction ?? null
        ], Response::HTTP_CREATED);
    }

    public static function iOSRTDNInAppSubscription($productID, $originalTransactionId, $isExpired, $receiptDetails, $inAppStatus, $latestReceipt)
    {
        $out = new ConsoleOutput();
        $user = null;
        $subscription = null;
        $latestSubscription = null;
        $existingTransaction = null;
        $inAppTransaction = null;
        $productData = [];
        $transactionData = [];

        $expiresDate = $receiptDetails->getExpiresDate()->getCarbon()->toISOString();
        $isInIntroOfferPeriod = $receiptDetails->isInIntroOfferPeriod();
        $isTrialPeriod = $receiptDetails->isTrialPeriod();
        $subscriptionGroupIdentifier = $receiptDetails->getSubscriptionGroupIdentifier();

        $originalPurchaseDate = $receiptDetails->getOriginalPurchaseDate()->getCarbon()->toISOString();
        $purchaseDate = $receiptDetails->getPurchaseDate()->getCarbon()->toISOString();
        $webOrderLineItemId = $receiptDetails->getWebOrderLineItemId();
        $transactionId = $receiptDetails->getTransactionId();

        $out->writeln("in_app_id: " . $productID);
        $out->writeln("original_transaction_id: " . $originalTransactionId);
        $out->writeln("purchase_token: " . $webOrderLineItemId);

        $latestSubscription = Subscription::where([
            ["in_app_id", $productID]
        ])
            ->whereHas('transactions', function ($query) use ($originalTransactionId, $webOrderLineItemId) {
                $query->where('original_transaction_id', $originalTransactionId);
                // $query->where('purchase_token', $webOrderLineItemId);
            })
            ->first();

        if (isset($latestSubscription)) {
            $out->writeln("Latest subscription!");
            print_r($latestSubscription);
            $user = User::findOrFail($latestSubscription->user_id);
        } else {
            $out->writeln("No latest subscription!");
        }

        // $existingTransaction = SubscriptionTransaction::where([
        //     ["original_transaction_id", $originalTransactionId],
        //     // ["purchase_token", $webOrderLineItemId],
        // ])->first();

        // map fields
        if ($latestSubscription) {
            $productData = [
                "type"                                  => Subscription::SUBSCRIPTION_IN_APP,
                "in_app_status"                         => $inAppStatus,
                "in_app_alias"                          => isset($latestSubscription) ? $latestSubscription->in_app_alias : null,
                "in_app_description"                    => isset($latestSubscription) ? $latestSubscription->in_app_description : null,
                "in_app_id"                             => $productID,
                "in_app_title"                          => isset($latestSubscription) ? $latestSubscription->in_app_title : null,
                "in_app_type"                           => isset($latestSubscription) ? $latestSubscription->in_app_type : null,
                "in_app_valid"                          => isset($latestSubscription) ? $latestSubscription->in_app_valid : null,
                "in_app_billing_retry_period"           => isset($latestSubscription) ? $latestSubscription->in_app_billing_retry_period : 0,
                "in_app_trial_period"                   => isset($latestSubscription) ? $latestSubscription->in_app_trial_period : 0,
                "in_app_intro_period"                   => isset($latestSubscription) ? $latestSubscription->in_app_intro_period : 0,
                "in_app_expired"                        => $isExpired,
                'in_app_applicationUsername'            => isset($latestSubscription) ? $latestSubscription->in_app_applicationUsername : null,
                'in_app_renewalIntent'                  => $inAppStatus,
            ];

            $productData['in_app_expiryDate'] = $expiresDate;
            $productData['in_app_purchaseDate'] = $purchaseDate;

            if (isset($user)) {
                $productData['user_id'] = $user->id;

                $out->writeln("sendNotificationFromInApp");
                self::sendNotificationFromInApp($inAppStatus, $user, 'ios');
            }

            $out->writeln("saveInApp: ");
            $subscription = self::saveInApp($productData);
            if (!$subscription) {
                $out->writeln("RENEW_SUBSCRIPTION_FAILED: ");
                return response()->json([
                    "error" => "RENEW_SUBSCRIPTION_FAILED",
                    "message" => "Renewing In-App Purchase Subscription Not Saved",
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        if ($subscription) {
            $transactionData = [
                "subscription_id"                   => $subscription->id,
                "type"                              => SubscriptionTransaction::TRANSACTION_IOS,
                "in_app_ownership_type"             => 0,
                "is_in_intro_offer_period"          => $isInIntroOfferPeriod,
                "is_trial_period"                   => $isTrialPeriod,
                "subscription_group_identifier"     => $subscriptionGroupIdentifier,
                "expires_date"                      => $expiresDate, // $inAppExpiryDate,
                "original_purchase_date"            => $originalPurchaseDate,
                "purchase_date"                     => $purchaseDate, // $inAppPurchaseDate,
                "transaction_id"                    => $transactionId, //$orderID,
                "purchase_token"                    => $webOrderLineItemId,
                "signature"                         => null,
                "developerPayload"                  => null,
                "receipt"                           => $latestReceipt ?? null,
                "original_transaction_id"           => $originalTransactionId,
            ];

            $inAppTransaction = self::saveTransaction($transactionData);
            if (!$inAppTransaction) {
                return response()->json([
                    "error" => "RENEW_TRANSACTION_FAILED",
                    "message" => "Renewing In-App Subscription Transaction Not Saved"
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // if ($existingTransaction) {
        //     $transactionData = [
        //         "subscription_id"                   => $subscription->id,
        //         "type"                              => SubscriptionTransaction::TRANSACTION_IOS,
        //         "in_app_ownership_type"             => isset($existingTransaction) ? $existingTransaction->in_app_ownership_type : 0,
        //         "is_in_intro_offer_period"          => isset($existingTransaction) ? $existingTransaction->is_in_intro_offer_period : 0,
        //         "is_trial_period"                   => isset($existingTransaction) ? $existingTransaction->is_trial_period : 0,
        //         "subscription_group_identifier"     => isset($existingTransaction) ?  $existingTransaction->subscription_group_identifier : null,
        //         "expires_date"                      => $expiresDate, // $inAppExpiryDate,
        //         "original_purchase_date"            => isset($existingTransaction) ? $existingTransaction->original_purchase_date : null,
        //         "purchase_date"                     => $purchaseDate, // $inAppPurchaseDate,
        //         "transaction_id"                    => $transactionId, //$orderID,
        //         "purchase_token"                    => $webOrderLineItemId,
        //         "signature"                         => isset($existingTransaction) ?  $existingTransaction->signature : null,
        //         "developerPayload"                  => isset($existingTransaction) ? $receiptDetails->getDeveloperPayload() : null,
        //         "receipt"                           => isset($existingTransaction) ? $existingTransaction->receipt : null,
        //         "original_transaction_id"           => $originalTransactionId,
        //     ];

        //     $inAppTransaction = self::saveTransaction($transactionData);
        //     if (!$inAppTransaction) {
        //         return response()->json([
        //             "error" => "RENEW_TRANSACTION_FAILED",
        //             "message" => "Renewing In-App Subscription Transaction Not Saved"
        //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
        //     }
        // }

        if (isset($user)) {
            self::UpdateUserStatusFromInApp($inAppStatus, $user, $latestSubscription, $isExpired);
        }

        $out->writeln("Successfully Renew In-App Subscription (IOS): ");
        return response()->json([
            "message" => "Successfully Renew In-App Subscription",
            "subscription" => $subscription,
            "transaction" => $inAppTransaction ?? null
        ], Response::HTTP_CREATED);


        // $out->writeln("receipt: " . print_r($receipt, true));
        // $out->writeln("latestReceipt: " . print_r($latestReceipt, true));
    }
}
