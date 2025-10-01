<?php

namespace App\Services\Google;

use App\Models\Subscription;
use App\Models\SubscriptionTransaction;
use App\Services\SubscriptionService;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionCanceled;
use Symfony\Component\Console\Output\ConsoleOutput;

class CancelledSubscription
{
    /**
     * @param SubscriptionCanceled $event
     */
    public function handle(SubscriptionCanceled $event)
    {
        $out = new ConsoleOutput();
        $out->writeln("AndroidCancelledSubscription: ");
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

        SubscriptionService::androidRTDNInAppSubscription($productId, $purchaseToken, $isExpired, $receiptDetails, Subscription::IN_APP_STATUS_CANCELED);
    }
}
