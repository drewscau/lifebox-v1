<?php

use Imdhemy\Purchases\Events\AppStore\Cancel;
use Imdhemy\Purchases\Events\AppStore\DidChangeRenewalPref;
use Imdhemy\Purchases\Events\AppStore\DidChangeRenewalStatus;
use Imdhemy\Purchases\Events\AppStore\DidFailToRenew;
use Imdhemy\Purchases\Events\AppStore\DidRecover;
use Imdhemy\Purchases\Events\AppStore\DidRenew;
use Imdhemy\Purchases\Events\AppStore\InitialBuy;
use Imdhemy\Purchases\Events\AppStore\InteractiveRenewal;
use Imdhemy\Purchases\Events\AppStore\PriceIncreaseConsent;
use Imdhemy\Purchases\Events\AppStore\Refund;
use Imdhemy\Purchases\Events\AppStore\Revoke;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionCanceled;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionDeferred;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionExpired;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionInGracePeriod;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionOnHold;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionPaused;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionPauseScheduleChanged;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionPriceChangeConfirmed;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionPurchased;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRecovered;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRenewed;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRestarted;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRevoked;

use App\Services\Google\AutoRenewSubscription as AndroidAutoRenewSubscription;
use App\Services\Google\CancelledSubscription as AndroidCancelledSubscription;
use App\Services\Google\InGracePeriodSubscription as AndroidInGracePeriodSubscription;
use App\Services\Google\OnHoldSubscription as AndroidOnHoldSubscription;
use App\Services\Google\RecoveredSubscription as AndroidRecoveredSubscription;
use App\Services\Google\PausedSubscription as AndroidPausedSubscription;
use App\Services\Google\PauseScheduleChangedSubscription as AndroidPauseScheduleChangedSubscription;
use App\Services\Google\RestartedSubscription as AndroidRestartedSubscription;
// use App\Services\Google\ExpiredSubscription as AndroidExpiredSubscription;
// use App\Services\Google\RevokeSubscription as AndroidRevokeSubscription;
// use App\Services\Google\PurchaseSubscription as AndroidPurchaseSubscription;

use App\Services\AppStore\AutoRenewSubscription as iOSAutoRenewSubscription;
use App\Services\AppStore\CancelledSubscription as iOSCancelledSubscription;
use App\Services\AppStore\RevokeSubscription as iOSRevokeSubscription;
use App\Services\AppStore\RefundSubscription as iOSRefundSubscription;
use App\Services\AppStore\FailToRenewSubscription as iOSDidFailToRenewSubscription;
use App\Services\AppStore\InitialBuySubscription as iOSInitialBuySubscription;
use App\Services\AppStore\RecoverSubscription as iOSRecoverSubscription;
use App\Services\AppStore\ChangedRenewalStatus as iOSChangedRenewalSubscription;
use App\Services\AppStore\InteractiveRenewalStatus as iOSInteractiveRenewalSubscription;
use App\Services\AppStore\CangeRenewalPrefSubscription as iOSCangeRenewalPrefSubscription;


return [
    'routing' => [
        'prefix' => 'api',
    ],

    'google_play_package_name' => env('GOOGLE_PLAY_PACKAGE_NAME', 'com.simpleclick.lifebox'),

    'appstore_password' => env('APPSTORE_PASSWORD', '1251e324a1404208b3aa049ed1244629'),

    'appstore_sandbox' => env('APPSTORE_SANDBOX', true),

    'eventListeners' => [
        /**
         * --------------------------------------------------------
         * Google Play Events
         * --------------------------------------------------------
         */
        SubscriptionPurchased::class => [],
        SubscriptionRenewed::class => [AndroidAutoRenewSubscription::class],
        SubscriptionInGracePeriod::class => [AndroidInGracePeriodSubscription::class],
        SubscriptionExpired::class => [],
        SubscriptionCanceled::class => [AndroidCancelledSubscription::class],
        SubscriptionPaused::class => [AndroidPausedSubscription::class],
        SubscriptionRestarted::class => [AndroidRestartedSubscription::class],
        SubscriptionDeferred::class => [],
        SubscriptionRevoked::class => [],
        SubscriptionOnHold::class => [AndroidOnHoldSubscription::class],
        SubscriptionRecovered::class => [AndroidRecoveredSubscription::class],
        SubscriptionPauseScheduleChanged::class => [AndroidPauseScheduleChangedSubscription::class],
        SubscriptionPriceChangeConfirmed::class => [],

        /**
         * --------------------------------------------------------
         * Appstore Events
         * --------------------------------------------------------
         */
        Cancel::class => [iOSCancelledSubscription::class],
        DidChangeRenewalPref::class => [iOSCangeRenewalPrefSubscription::class],
        DidChangeRenewalStatus::class => [iOSChangedRenewalSubscription::class],
        DidFailToRenew::class => [iOSDidFailToRenewSubscription::class],
        DidRecover::class => [iOSRecoverSubscription::class],
        DidRenew::class => [iOSAutoRenewSubscription::class],
        InitialBuy::class => [iOSInitialBuySubscription::class],
        InteractiveRenewal::class => [iOSInteractiveRenewalSubscription::class],
        PriceIncreaseConsent::class => [],
        Refund::class => [iOSRefundSubscription::class],
        Revoke::class => [iOSRevokeSubscription::class],
    ],
];
