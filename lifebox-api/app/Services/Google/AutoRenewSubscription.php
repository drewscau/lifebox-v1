<?php

namespace App\Services\Google;

use App\Models\Subscription;
use App\Models\SubscriptionTransaction;
use App\Services\SubscriptionService;

use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRenewed;
use Symfony\Component\Console\Output\ConsoleOutput;

class AutoRenewSubscription
{
    /**
     * @param SubscriptionRenewed $event
     */
    public function handle(SubscriptionRenewed $event)
    {
        $out = new ConsoleOutput();
        $out->writeln("AndroidAutoRenewSubscription: ");
        $isExpired = false;
        // The following data can be retrieved from the event
        $notification = $event->getServerNotification();
        $subscription = $notification->getSubscription();
        $receiptDetails = $subscription->getProviderRepresentation();
        $productId = $subscription->getItemId();
        $purchaseToken = $subscription->getUniqueIdentifier();

        $expirationTime = $subscription->getExpiryTime();
        if (isset($expiresDate)) {
            $isExpired = $expirationTime->isPast();
        }

        // $out->writeln("receiptDetails: " . print_r($receiptDetails, true));
        SubscriptionService::androidRTDNInAppSubscription($productId, $purchaseToken, $isExpired, $receiptDetails, Subscription::IN_APP_STATUS_RENEW);
    }
}
