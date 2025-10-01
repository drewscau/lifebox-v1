<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use App\Services\VoucherCodeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use App\Models\SubscriptionTransaction;
use App\Services\UserService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Stripe\StripeClient;
use Symfony\Component\Console\Output\ConsoleOutput;

class PaymentController extends Controller
{
    /**
     * Apply coupon
     *
     * @authenticated
     * @group Coupon
     * @bodyParam coupon_code string required
     * @bodyParam payment_method string
     * @param Request $request
     * @param StripeService $stripeService
     * @param VoucherCodeService $voucherCodeService
     * @return JsonResponse
     */
    public function applyCoupon(
        Request $request,
        StripeService $stripeService,
        VoucherCodeService $voucherCodeService
    ) {
        $user = UserService::getCurrentUser();

        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required',
            'payment_method' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->errors()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $couponCode = $voucherCodeService->getStripeCouponId($request->input('coupon_code'));
            $paymentMethod = $request->input('payment_method', null);

            $planId = $stripeService->getCouponPlanId($couponCode);

            $user->newSubscription('default', $planId)
                ->withCoupon($couponCode)
                ->create($paymentMethod, [
                    'email' => $user->email
                ]);

            $user->user_status = User::STATUS_SUBSCRIBED;
            $user->update();

            return response()->json([
                'subscriptions' => $user->subscriptions()->get(),
                'subscribed' => $user->subscribed()
            ]);
        } catch (Exception $e) {
            return response()->json(
                ['errors' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Show subscription
     *
     * @authenticated
     * @group Subscription
     * @return JsonResponse
     */
    public function showSubscription()
    {
        $plans = $this->retrievePlans();
        $user = UserService::getCurrentUser();

        return response()->json([
            'user' => $user,
            'intent' => $user->createSetupIntent(),
            'plans' => $plans,
            'subscribed' => $user->subscribed(),
            'subscriptions' => $user->subscriptions()->get()
        ]);
    }

    /**
     * Process subscription
     *
     * Subscribe a user
     *
     * @authenticated
     * @group Subscription
     * @bodyParam payment_method string stripe_payment_method
     * @bodyParam plan string plan_id
     * @bodyParam promotion_code string
     * @bodyParam coupon_code string stripe_coupon_id
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function processSubscription(Request $request)
    {
        $user = UserService::getCurrentUser();
        $paymentMethodId = $request->input('payment_method', null);
        $planID = $request->input('plan', null);
        $promotionCode = $request->input('promotion_code', null);
        $couponCode = $request->input('coupon_code', null);

        if ($user->subscribed()) {
            // Checks if subscription is still under grace period (cancelled but not yet expired)
            // Then resumes the old subscription afterwards, and the resubscription is complete

            if ($user->subscription('default')->onGracePeriod()) {
                $user->subscription('default')->resume();
                $user->user_status = User::STATUS_SUBSCRIBED;
                $user->save();

                return response()->json([
                    'message' => 'Subscription has been reactivated. Thank you for trusting us once more!',
                ]);
            }

            return response()->json([
                'message' => "No need to pay or resubscribe because you're already a subscriber!"
            ]);
        }

        // Else, create a new subscription in replacement of the old one
        // This means that the old subscription has now expired within its grace period / officially cancelled

        if (!$planID) {
            return response()->json([
                'code' => 'INVALID_SUBSCRIPTION_DETAILS',
                'message' => "Subscription can't be processed. Please review your details first."
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user->createOrGetStripeCustomer();
        } catch (Exception $e) {
            $user->stripe_id = null;
            $user->save();
            $user->createOrGetStripeCustomer();
        }

        if (!$user->hasPaymentMethod() && $paymentMethodId) {
            $user->addPaymentMethod($paymentMethodId);
        }

        try {
            $subscriptions = $user->newSubscription('default', $planID);

            if ($couponCode) {
                $subscriptions->withCoupon($couponCode);
            }

            if ($promotionCode) {
                $subscriptions->withPromotionCode($promotionCode);
            }

            $subscriptions->create($paymentMethodId, [
                'email' => $user->email
            ]);

            $user->user_status = User::STATUS_SUBSCRIBED;
            $user->update();

            return response()->json([
                'message' => 'Successful payment transaction.',
                'user' => $user
            ]);
        } catch (Exception $e) {
            return back()->withErrors([
                'message' => 'Error creating subscription. ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update a subscription
     *
     * @authenticated
     * @group Subscription
     * @bodyParam plan string plan_id
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function updateSubscription(Request $request)
    {
        $user = UserService::getCurrentUser();
        $planID = $request->input('plan', null);

        if (!$user->subscribed()) {
            return response()->json([
                'code' => 'ACCESS_DENIED',
                'message' => "You can't update your subscription if you don't have a subscription in the first place!"
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$planID) {
            return response()->json([
                'code' => 'INVALID_SUBSCRIPTION_DETAILS',
                'message' => "Subscription can't be processed. Please review your details first."
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $latestSubscriptionPlan = $user->subscriptions()->latest()->first();

        if ($planID == $latestSubscriptionPlan->stripe_plan) {
            return response()->json([
                'code' => 'DUPLICATE_SUBSCRIPTION_PLAN',
                'message' => "You're currently subscribed to this plan. Please select other plans available and try again."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $user->subscription('default')->swap($planID);

            return response()->json([
                'message' => 'Successful change of subscription plan.',
                'user' => $user
            ]);
        } catch (Exception $e) {
            return back()->withErrors([
                'message' => 'Error updating subscription. ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Validate in-app purchase
     *
     * @authenticated
     * @group Subscription
     * @bodyParam transaction string[] transaction_body
     * @bodyParam id string product_id
     * @bodyParam additionalData string[]
     * @param Request $request
     * @return JsonResponse
     */
    public function inAppValidation(Request $request)
    {
        $out = new ConsoleOutput();
        $out->writeln("inAppValidation: ");
        
        $user = UserService::getCurrentUser();
        if ($request->has('transaction')) {
            $productID = $request->get('id');
            $transactionBody = $request->get('transaction');
            $additionalData = $request->get('additionalData');

            $collection = [];
            $latestReceipt = false;
            $subscriptionReceipt = null;
            $transactionResponse = null;
            $applicationUsername = null;
            $expiresDate = null;
            $expiryDate = null;

            if ($request->has('additionalData')) {
                if (isset($additionalData['applicationUsername'])) {
                    $applicationUsername = $additionalData['applicationUsername'];
                }
            }

            // VALIDATE PURCHASE RECEIPT WHETHER APPROVED OR NOT ON APPLE or ANDROID IAP SERVER
            if (isset($transactionBody['type'])) {
                // Check if receipt is expired or not
                if ($transactionBody['type'] === SubscriptionTransaction::TRANSACTION_ANDROID) {
                    $receiptToken = $transactionBody['purchaseToken'];
                    $originalTransactionId = $transactionBody['id'];
                } else {
                    $receiptToken = $transactionBody['appStoreReceipt'];
                    $originalTransactionId = $transactionBody['original_transaction_id'];
                }

                $expiryDate = SubscriptionService::validateIfReceiptIsExpired($transactionBody['type'], $productID, $receiptToken);
                if (isset($expiryDate)) {
                    return response()->json([
                        "ok" => false,
                        "status" => 419,
                        "code" => SubscriptionTransaction::VALIDATION_PURCHASE_EXPIRED,
                        "message" => "Transaction has expired {$expiryDate}",
                        "data" => [
                            "code" => SubscriptionTransaction::VALIDATION_PURCHASE_EXPIRED,
                            "error" => [
                                "message" => "Transaction has expired {$expiryDate}",
                            ],
                        ],
                    ], Response::HTTP_OK);
                }

                // Check if receipt already existed on the subscription, invalidates transaction on store if true
                if ($applicationUsername && $originalTransactionId) {
                    try {
                        $hasExistingSubscriptions = SubscriptionService::checkExistingSubscriptions($productID, $originalTransactionId, $applicationUsername, $user->id);
                        if ($hasExistingSubscriptions) {
                            return response()->json([
                                "ok" => false,
                                "code" => SubscriptionTransaction::VALIDATION_PURCHASE_EXPIRED,
                                "status" => 200,
                                "data" => [
                                    "error" => [
                                        "code" => SubscriptionTransaction::VALIDATION_PURCHASE_EXPIRED,
                                        "message" => "In-App Subscription already taken by another account. Please use another In App account for payment"
                                    ]
                                ],
                            ], Response::HTTP_OK);
                        }
                    } catch (\Throwable $th) {
                        SubscriptionService::revokeInAppReceipt(SubscriptionTransaction::TRANSACTION_ANDROID, $productID, $receiptToken);
                    }
                }


                // Format Validation response so it would be formatted back from CORDOVA PURCHASE PLUGIN
                if ($transactionBody['type'] === SubscriptionTransaction::TRANSACTION_ANDROID) {
                    try {
                        $subscriptionReceipt = SubscriptionService::verifyAndroidSubscription($productID, $receiptToken);
                        $receipt = isset($transactionBody['receipt']) ? json_decode($transactionBody['receipt'], true) : [];

                        if (isset($subscriptionReceipt)) {
                            $expiresDate = SubscriptionService::getPropertyFor($subscriptionReceipt, 'ExpiryTime');
                            $linkedPurchaseToken =  SubscriptionService::getPropertyFor($subscriptionReceipt, 'LinkedPurchaseToken');
                        }

                        $transactionResponse = [
                            "orderId" => $receipt['orderId'],
                            "packageName" => $receipt['packageName'],
                            "productId" => $receipt['productId'],
                            "purchaseTime" => $receipt['purchaseTime'],
                            "purchaseState" => $receipt['purchaseState'],
                            "purchaseToken" => $receipt['purchaseToken'],
                            "obfuscatedAccountId" => $receipt['obfuscatedAccountId'],
                            "autoRenewing" => $receipt['autoRenewing'],
                            "acknowledged" => $receipt['acknowledged'],

                            "expiryTimeMillis"              => isset($expiresDate) ? $expiresDate->getCarbon()->getPreciseTimestamp(3) : null,
                            "type"                          => SubscriptionTransaction::TRANSACTION_ANDROID,
                            "startTimeMillis"               => $subscriptionReceipt->getStartTime()->getCarbon()->getPreciseTimestamp(3),
                            "priceCurrencyCode"             => SubscriptionService::getPropertyFor($subscriptionReceipt, 'PriceCurrencyCode'),
                            "priceAmountMicros"             => SubscriptionService::getPropertyFor($subscriptionReceipt, 'PriceAmountMicros'),
                            "countryCode"                   => SubscriptionService::getPropertyFor($subscriptionReceipt, 'CountryCode'),
                            "developerPayload"              => SubscriptionService::getPropertyFor($subscriptionReceipt, 'DeveloperPayload'),
                            "paymentState"                  => SubscriptionService::getPropertyFor($subscriptionReceipt, 'PaymentState'),
                            "acknowledgementState"          => SubscriptionService::getPropertyFor($subscriptionReceipt, 'AcknowledgementState'),
                            "kind"                          => SubscriptionService::getPropertyFor($subscriptionReceipt, 'Kind'),
                            "obfuscatedExternalAccountId"   => SubscriptionService::getPropertyFor($subscriptionReceipt, 'ObfuscatedExternalAccountId'),
                            "id"                            => SubscriptionService::getPropertyFor($subscriptionReceipt, 'OrderId'),
                            "link_purchase_token"           => isset($linkedPurchaseToken) ? $linkedPurchaseToken : null,
                        ];

                        return response()->json([
                            "ok" => true,
                            "data" => [
                                "id" => $productID,
                                "transaction" => $transactionResponse,
                                "collection" => $collection,
                            ],
                        ], Response::HTTP_OK);
                    } catch (\Throwable $th) {
                        SubscriptionService::revokeInAppReceipt(SubscriptionTransaction::TRANSACTION_ANDROID, $productID, $receiptToken);
                    }
                } else {
                    $receiptData = $transactionBody['appStoreReceipt'];
                    $subscriptionReceipt = SubscriptionService::verifyIOSSubscription($receiptData);
                    $receipt = $subscriptionReceipt->getReceipt();
                    $receiptCreationDate = $receipt->getReceiptCreationDate();
                    $originalPurchaseDate = $receipt->getOriginalPurchaseDate();

                    if (!empty($subscriptionReceipt->getLatestReceiptInfo())) {
                        $latestReceipt = true;
                        $latestReceiptInfo = $subscriptionReceipt->getLatestReceiptInfo()[0];

                        if ($latestReceiptInfo) {
                            $expiresDate = $latestReceiptInfo->getExpiresDate();
                            $collection[] = [
                                "expiryDate"            => $expiresDate->getCarbon()->getPreciseTimestamp(3),
                                "id"                    => $latestReceiptInfo->getProductId(),
                                "isExpired"             => $expiresDate->isPast(),
                                "expired"               => $expiresDate->isPast(),
                                "purchaseDate"          => $latestReceiptInfo->getPurchaseDate(),
                                "cancellationReason"    => $latestReceiptInfo->getCancellationReason(),
                                "cancellationDate"      => $latestReceiptInfo->getCancellationDate(),
                            ];
                        }
                    }

                    $transactionResponse = [
                        "receipt_type"                      => $receipt->getReceiptType(),
                        "adam_id"                           => $receipt->getAdamId(),
                        "bundle_id"                         => $receipt->getBundleId(),
                        "application_version"               => $receipt->getApplicationVersion(),
                        "download_id"                       => $receipt->getDownloadId(),
                        "version_external_identifier"       => $receipt->getVersionExternalIdentifier(),
                        "original_application_version"      => $receipt->getOriginalApplicationVersion(),
                        "type"                              => $transactionBody['type'],
                        "in_app_ownership_type"             => "PURCHASED",

                        "receipt_creation_date"             => $receiptCreationDate->getCarbon(),
                        "receipt_creation_date_ms"          => $receiptCreationDate->getCarbon()->getPreciseTimestamp(3),
                        "receipt_creation_date_pst"         => Carbon::createFromFormat('Y-m-d H:i:s', $receiptCreationDate->getCarbon())->shiftTimezone("America/Los_Angeles"),

                        "original_purchase_date"            => $originalPurchaseDate->getCarbon(),
                        "original_purchase_date_ms"         => $originalPurchaseDate->getCarbon()->getPreciseTimestamp(3),
                        "original_purchase_date_pst"        => Carbon::createFromFormat('Y-m-d H:i:s', $originalPurchaseDate->getCarbon())->shiftTimezone("America/Los_Angeles"),
                    ];

                    if ($latestReceiptInfo) {
                        $expiresDate = $latestReceiptInfo->getExpiresDate();
                        $origPurchaseDate = $latestReceiptInfo->getOriginalPurchaseDate();
                        $purchaseDate = $latestReceiptInfo->getPurchaseDate();

                        $transactionResponse = [
                            "expires_date"                  => $expiresDate->getCarbon()->toISOString(),
                            "expiration_date_ms"            => $expiresDate->getCarbon()->getPreciseTimestamp(3),
                            "expiration_date_pst"           => Carbon::createFromFormat('Y-m-d H:i:s', $expiresDate->getCarbon())->shiftTimezone("America/Los_Angeles"),
                            "isExpired"                     => $expiresDate->isPast(),
                            "expired"                       => $expiresDate->isPast(),

                            "is_in_intro_offer_period"      => $latestReceiptInfo->isInIntroOfferPeriod(),
                            "original_transaction_id"       => $latestReceiptInfo->getOriginalTransactionId(),
                            "is_trial_period"               => $latestReceiptInfo->isTrialPeriod(),
                            "quantity"                      => $latestReceiptInfo->getQuantity(),
                            "subscription_group_identifier" => $latestReceiptInfo->getSubscriptionGroupIdentifier(),
                            "transaction_id"                => $latestReceiptInfo->getTransactionId(),
                            "web_order_line_item_id"        => $latestReceiptInfo->getWebOrderLineItemId(),

                            "original_purchase_date"        => $origPurchaseDate->getCarbon(),
                            "original_purchase_date_ms"     => $origPurchaseDate->getCarbon()->getPreciseTimestamp(3),
                            "original_purchase_date_pst"    => Carbon::createFromFormat('Y-m-d H:i:s', $originalPurchaseDate->getCarbon())->shiftTimezone("America/Los_Angeles"),

                            "purchase_date"                 => $purchaseDate->getCarbon()->toISOString(),
                            "purchase_date_ms"              => $purchaseDate->getCarbon()->getPreciseTimestamp(3),
                            "purchase_date_pst"             => Carbon::createFromFormat('Y-m-d H:i:s', $purchaseDate->getCarbon())->shiftTimezone("America/Los_Angeles"),
                        ];
                    }

                    return response()->json([
                        "ok" => true,
                        "data" => [
                            "latest_receipt" => $latestReceipt,
                            "id" => $productID,
                            "environment" => $subscriptionReceipt->getEnvironment(),
                            "transaction" => $transactionResponse,
                            "collection" => $collection,
                        ],
                    ], Response::HTTP_OK);
                }
            }
        }

        return response()->json([
            "ok" => false,
            "code" => SubscriptionTransaction::VALIDATION_PURCHASE_EXPIRED,
            "message" => "Verification failed as it lacks data for validation",
            "data" => [
                "code" => SubscriptionTransaction::VALIDATION_PURCHASE_EXPIRED,
                "error" => [
                    "message" => "Verification failed as it lacks data for validation"
                ],
            ],
        ], Response::HTTP_OK);
    }

    /**
     * In-app store
     *
     * @authenticated
     * @group Subscription
     * @group Payment
     * @bodyParam additionalData object
     * @bodyParam additionalData.applicationUsername string
     * @bodyParam id string product_id
     * @bodyParam isExpired bool
     * @bodyParam expired bool
     * @bodyParam transaction object transaction body
     * @bodyParam state string
     * @bodyParam alias string
     * @bodyParam description string
     * @bodyParam title string
     * @bodyParam type string
     * @bodyParam valid string
     * @bodyParam isBillingRetryPeriod bool
     * @bodyParam isTrialPeriod bool
     * @bodyParam isIntroPeriod bool
     * @bodyParam lastRenewalDate string date
     * @bodyParam renewalIntent string
     * @param Request $request
     * @return JsonResponse
     */
    public function inAppStore(Request $request)
    {
        $user = UserService::getCurrentUser();
        $additionalData = $request->get('additionalData');
        $productID = $request->get('id');
        $transactionBody = null;
        $originalTransactionId = null;
        $transactionId = null;

        $inAppApplicationUserName = $request->has('additionalData') && isset($additionalData['applicationUsername'])
            ? $additionalData['applicationUsername']
            : null;
        $isExpired = $request->has('isExpired') ? $request->get('isExpired', 0) : $request->get('expired', 0);


        $productData = [
            "user_id"                               => $user->id,
            "type"                                  => Subscription::SUBSCRIPTION_IN_APP,

            "in_app_status"                         => strtoupper($request->get('state', null)),
            "in_app_alias"                          => $request->get('alias', null),
            "in_app_description"                    => $request->get('description', null),
            "in_app_id"                             => $productID,
            "in_app_title"                          => $request->get('title', null),
            "in_app_type"                           => $request->get('type', null),
            "in_app_valid"                          => $request->get('valid', null),

            "in_app_billing_retry_period"           => $request->has('isBillingRetryPeriod') ? $request->get('isBillingRetryPeriod', 0) : 0,
            "in_app_trial_period"                   => $request->has('isTrialPeriod') ? $request->get('isTrialPeriod', 0) : 0,
            "in_app_intro_period"                   => $request->has('isIntroPeriod') ? $request->get('isIntroPeriod', 0) : 0,
            "in_app_expired"                        => $isExpired,
            "in_app_lastRenewalDate"                => $request->get('lastRenewalDate', null),
            "in_app_renewalIntent"                  => $request->get('renewalIntent', null),
            'in_app_applicationUsername'            => $inAppApplicationUserName,
        ];


        if ($request->has('transaction')) {
            $transactionBody = $request->get('transaction');
            if (isset($transactionBody['type'])) {
                if ($transactionBody['type'] == SubscriptionTransaction::TRANSACTION_ANDROID) {
                    $receipt = isset($transactionBody['receipt']) ? json_decode($transactionBody['receipt'], true) : [];

                    $expiryTimeMillis = $request->has('expiryTimeMillis') ? $request->get('transaction')['expiryTimeMillis'] : null;
                    $purchaseTime = $request->has('expiryTimeMillis') ? $request->get('transaction')['purchaseTime'] : $receipt['purchaseTime'];
                    $receiptToken = $transactionBody['purchaseToken'];

                    $inAppExpiryDate = isset($expiryTimeMillis)
                        ? Carbon::createFromTimestampMs($expiryTimeMillis)->toISOString()
                        : $request->get('expiryDate', null);

                    $inAppPurchaseDate = isset($purchaseTime)
                        ? Carbon::createFromTimestampMs($purchaseTime)->toISOString() : $request->get('purchaseDate', null);

                    $originalTransactionId = isset($transactionBody['orderId']) ? $transactionBody['orderId'] : $receipt["orderId"];
                } else {
                    $receiptToken = $transactionBody['appStoreReceipt'];
                    $inAppExpiryDate = isset($request->get('transaction')['expires_date'])
                        ? $request->get('transaction')['expires_date']
                        : $request->get('expiryDate', null);

                    $inAppPurchaseDate = isset($request->get('transaction')['purchase_date'])
                        ? $request->get('transaction')['purchase_date'] : $request->get('purchaseDate', null);

                    $originalTransactionId = $transactionBody['original_transaction_id'];
                }


                // Check if receipt is expired or not
                $expiryDate = SubscriptionService::validateIfReceiptIsExpired($transactionBody['type'], $productID, $receiptToken);
                if (isset($expiryDate)) {
                    return response()->json([
                        "ok" => false,
                        "status" => 419,
                        "code" => SubscriptionTransaction::VALIDATION_PURCHASE_EXPIRED,
                        "message" => "Transaction has expired {$expiryDate}",
                        "data" => [
                            "code" => SubscriptionTransaction::VALIDATION_PURCHASE_EXPIRED,
                            "error" => [
                                "message" => "Transaction has expired {$expiryDate}",
                            ],
                        ],
                    ], Response::HTTP_OK);
                }
            }
            $productData['in_app_expiryDate'] = $inAppExpiryDate;
            $productData['in_app_purchaseDate'] = $inAppPurchaseDate;


            if (isset($transactionBody['id'])) {
                $transactionId = $transactionBody['id'];
            }
            if (isset($transactionBody['transaction_id'])) {
                $transactionId = $transactionBody['transaction_id'];
            }

            $hasExistingSubscriptions = SubscriptionService::checkExistingSubscriptionsAndTransaction($productID, $transactionId, $originalTransactionId, $user->id);
            if (isset($hasExistingSubscriptions)) {
                return response()->json([
                    "ok" => false,
                    "code" => SubscriptionTransaction::VALIDATION_INTERNAL_ERROR,
                    "status" => 200,
                    "data" => [
                        "error" => [
                            "code" => SubscriptionTransaction::VALIDATION_INTERNAL_ERROR,
                            "message" => "In-App Subscription already existed under the same user"
                        ]
                    ],
                ], Response::HTTP_OK);
            }
        }

        $subscription = SubscriptionService::saveInApp($productData);
        if (!$subscription) {
            SubscriptionService::revokeInAppReceipt($transactionBody['type'], $productID, $receiptToken);
            return response()->json([
                "error" => "SAVE_SUBSCRIPTION_FAILED",
                "message" => "New In-App Purchase Subscription Not Saved",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($request->has('transaction')) {
            $transactionData = [
                "subscription_id"                   => $subscription->id,
                "type"                              => $transactionBody['type'],

                "in_app_ownership_type"             => isset($transactionBody['in_app_ownership_type']) ? $transactionBody['in_app_ownership_type'] : null,
                "is_in_intro_offer_period"          => isset($transactionBody['is_in_intro_offer_period']) ? $transactionBody['is_in_intro_offer_period'] : 0,
                "is_trial_period"                   => isset($transactionBody['is_trial_period']) ? $transactionBody['is_trial_period'] : 0,
                "subscription_group_identifier"     => isset($transactionBody['subscription_group_identifier']) ? $transactionBody['subscription_group_identifier'] : null,
                "expires_date"                      => isset($transactionBody['expires_date']) ? $transactionBody['expires_date'] : $inAppExpiryDate,
                "original_purchase_date"            => isset($transactionBody['original_purchase_date']) ? $transactionBody['original_purchase_date'] : $inAppPurchaseDate,
                "purchase_date"                     => isset($transactionBody['purchase_date']) ? $transactionBody['purchase_date'] : $inAppPurchaseDate,
            ];

            $receipt = isset($transactionBody['receipt']) ? json_decode($transactionBody['receipt'], true) : [];
            if (isset($transactionBody['id'])) {
                $transactionData['transaction_id'] = $transactionBody['id'];
            }
            if (isset($transactionBody['transaction_id'])) {
                $transactionData['transaction_id'] = $transactionBody['transaction_id'];
            }

            if ($transactionData['type'] == SubscriptionTransaction::TRANSACTION_ANDROID) {
                $transactionData['purchase_token'] = $transactionBody['purchaseToken'];
                $transactionData['signature'] = $transactionBody['signature'];
                $transactionData['developerPayload'] = isset($transactionBody['developerPayload']) ? $transactionBody['developerPayload'] : null;
                $transactionData['receipt'] = $transactionBody['receipt'];
                $transactionData['link_purchase_token'] = isset($transactionBody['link_purchase_token']) ? $transactionBody['link_purchase_token'] : null;
            } else {
                $transactionData['receipt'] = $transactionBody['appStoreReceipt'];
                $transactionData['purchase_token'] = $transactionBody['web_order_line_item_id'];
            }
            $transactionData['original_transaction_id'] = $originalTransactionId;

            $inAppTransaction = SubscriptionService::saveTransaction($transactionData);
            if (!$inAppTransaction) {
                SubscriptionService::revokeInAppReceipt($transactionBody['type'], $productID, $receiptToken);
                return response()->json([
                    "error" => "SAVE_TRANSACTION_FAILED",
                    "message" => "Transaction For The New In-App Purchase Subscription Not Saved"
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $user->user_status = User::STATUS_SUBSCRIBED;
        $user->update();

        return response()->json([
            "message" => "Successfully Saved In-App Purchase",
            "subscription" => $subscription,
            "transaction" => $inAppTransaction ?? null
        ], Response::HTTP_CREATED);
    }

    /**
     * Update in app store
     *
     * @authenticated
     * @group Subscription
     * @bodyParam id string in_app_product_id
     * @bodyParam additionalData object
     * @bodyParam additionalData.applicationUsername string
     * @bodyParam isExpired bool
     * @bodyParam expiryDate string date
     * @bodyParam purchaseDate string date
     * @bodyParam lastRenewalDate string date
     * @bodyParam renewalIntent string
     * @bodyParam isBillingRetryPeriod bool
     * @bodyParam isTrialPeriod bool
     * @bodyParam isIntroPeriod bool
     * @param Request $request
     * @return JsonResponse
     */
    public function updateInAppStore(Request $request): JsonResponse
    {
        $inAppProductID = $request->get('id');
        $additionalData = $request->get('additionalData');
        $user = UserService::getCurrentUser();

        $productData = [
            "in_app_expired" => $request->get('isExpired'),
            "in_app_expiryDate" => $request->get('expiryDate'),
            "in_app_purchaseDate" => $request->get('purchaseDate'),
            "in_app_lastRenewalDate" => $request->get('lastRenewalDate'),
            "in_app_renewalIntent" => $request->get('renewalIntent'),
        ];

        if ($request->has('isBillingRetryPeriod')) {
            $productData['in_app_billing_retry_period'] = $request->get('isBillingRetryPeriod');
        }
        if ($request->has('isTrialPeriod')) {
            $productData['in_app_trial_period'] = $request->get('isTrialPeriod');
        }
        if ($request->has('isIntroPeriod')) {
            $productData['in_app_intro_period'] = $request->get('isIntroPeriod');
        }

        if (isset($additionalData)) {
            if (isset($additionalData['applicationUsername'])) {
                $productData['in_app_applicationUsername'] = $additionalData['applicationUsername'];
            }
        }

        $existingInAppPurchase = Subscription::where('in_app_id', $inAppProductID)->where('in_app_applicationUsername', $productData['in_app_applicationUsername'])->first();
        $existingInAppPurchase->update($productData);

        if (!$existingInAppPurchase) {
            return response()->json([
                "error" => "UPDATE_SUBSCRIPTION_FAILED",
                "message" => "In-App Purchase Subscription Not Updated",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user->user_status = $request->get('isExpired') ? User::STATUS_UNSUBSCRIBED : User::STATUS_SUBSCRIBED;
        $user->update();

        return response()->json([
            "message" => "Successfully Updated In-App Purchase",
            "subscription" => $productData,
            "transaction" => $inAppTransaction ?? null
        ], Response::HTTP_CREATED);
    }

    /**
     * Unsubscribe user
     *
     * Unsubscribe users latest subscription
     *
     * @authenticated
     * @group Subscription
     *
     * @return JsonResponse
     */
    public function unsubscribe()
    {
        $user = UserService::getCurrentUser();
        $latestSubscription = $user->subscriptions()->latest()->first();

        if ($latestSubscription->fromStripe()) {
            $user->subscription('default')->cancel();
        } else {
            $latestSubscription->update([
                'in_app_status' => Subscription::IN_APP_STATUS_INVALID
            ]);

            // SubscriptionService::cancelInAppSubscription($latestSubscription);
        }

        $user->user_status = User::STATUS_UNSUBSCRIBED;
        $user->save();

        return response()->json([
            'message' => 'Subscription has been cancelled, hence you are now unsubscribed.',
        ]);
    }

    /**
     * Delete card
     *
     * Delete a payment method (card) from user
     *
     * @authenticated
     * @group Payment
     * @bodyParam payment_method string stripe_payment_method
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteCard(Request $request)
    {
        $user = UserService::getCurrentUser();
        $paymentMethodId = $request->input('payment_method');
        $paymentMethod = $user->findPaymentMethod($paymentMethodId);
        $paymentMethod->delete();
        return response()->json([
            'message' => 'Card deleted',
        ]);
    }

    private function retrievePlans()
    {
        $key = config('services.stripe.secret');
        $stripe = new StripeClient($key);
        $plansraw = $stripe->plans->all();
        $plans = $plansraw->data;

        foreach ($plans as $plan) {
            $prod = $stripe->products->retrieve(
                $plan->product,
                []
            );
            $plan->product = $prod;
        }

        return $plans;
    }

    /**
     * Revoke subscription
     *
     * @authenticated
     * @group Subscription
     * @param Request $request
     * @return JsonResponse
     */
    public function revokeInAppSubscription(Request $request)
    {
        try {
            $user = UserService::getCurrentUser();
            $latestSubscription = $user->subscriptions()->latest()->first();
            SubscriptionService::revokeOrderedInAppSubscription($latestSubscription);

            $user->user_status = User::STATUS_UNSUBSCRIBED;
            $user->update();

            return response()->json([
                'success' => true,
                'message' => "Successfully refunded and revoked user's subscription. Access to the subscription is now terminated and will stop recurring",
            ]);
        } catch (BadResponseException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $exception = $response->getBody()->getContents();
                return response()->json(json_decode($exception));
            }
        }
    }

    /**
     * Refund subscription
     *
     * @authenticated
     * @group Subscription
     * @param Request $request
     * @return JsonResponse
     */
    public function refundInAppSubscription(Request $request)
    {
        try {
            $user = UserService::getCurrentUser();
            $latestSubscription = $user->subscriptions()->latest()->first();
            SubscriptionService::refundOrderedInAppSubscription($latestSubscription);
            return response()->json([
                'success' => true,
                'message' => "Successfully refunded user's subscription. but the subscription remains valid until its expiration time and it will continue to recur.",
            ]);
        } catch (BadResponseException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $exception = $response->getBody()->getContents();
                return response()->json(json_decode($exception));
            }
        }
    }

    /**
     * Cancel in-app subscription
     *
     * @authenticated
     * @group Subscription
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelInAppSubscription(Request $request)
    {
        try {
            $user = UserService::getCurrentUser();
            $latestSubscription = $user->subscriptions()->latest()->first();
            SubscriptionService::cancelInAppSubscription($latestSubscription, true);
            return response()->json([
                'success' => true,
                'message' => "Successfully cancelled user's subscription. The subscription remains valid until its expiration time.",
            ]);
        } catch (BadResponseException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $exception = $response->getBody()->getContents();
                return response()->json(json_decode($exception));
            }
        }
    }
}
