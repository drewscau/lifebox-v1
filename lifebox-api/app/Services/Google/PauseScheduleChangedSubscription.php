<?php

namespace App\Services\Google;

use App\Models\Subscription;
use App\Models\SubscriptionTransaction;
use App\Services\SubscriptionService;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionPauseScheduleChanged;
use Symfony\Component\Console\Output\ConsoleOutput;

class PauseScheduleChangedSubscription
{
    /**
     * @param SubscriptionPauseScheduleChanged $event
     */
    public function handle(SubscriptionPauseScheduleChanged $event)
    {
        $out = new ConsoleOutput();
        $out->writeln("AndroidPauseScheduleChangedSubscription: ");
        // The following data can be retrieved from the event
        $isExpired = false;
        $notification = $event->getServerNotification();
        $subscription = $notification->getSubscription();
        $receiptDetails = $subscription->getProviderRepresentation();
        $productId = $subscription->getItemId();
        $purchaseToken = $subscription->getUniqueIdentifier();

        $expirationTime = $subscription->getExpiryTime();
        if (isset($expiresDate)) {
            $isExpired = $expirationTime->isPast();
        }

        SubscriptionService::androidRTDNInAppSubscription($productId, $purchaseToken, $isExpired, $receiptDetails, Subscription::IN_APP_STATUS_PAUSE_SCHEDULE_CHANGED);
    }
}
