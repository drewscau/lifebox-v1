<?php

namespace App\Services\AppStore;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Symfony\Component\Console\Output\ConsoleOutput;

class HandleHelper
{
    public static function handle($event, $status) {
        $isExpired = false;
        
        // The following data can be retrieved from the event
        $notification = $event->getServerNotification();
        $subscription = $notification->getSubscription();
        $latestReceipt = $notification->getLatestReceipt();

        $receiptDetails = $subscription->getProviderRepresentation();
        $productId = $subscription->getItemId();
        $originalTransactionId = $subscription->getUniqueIdentifier();

        $out = new ConsoleOutput();
        // $out->writeln("iOSAutoRenewSubscription Handler: " . json_encode($receiptDetails));
        // $out->writeln("Latest Receipt: " . $latestReceipt);

        $expirationTime = $subscription->getExpiryTime();
        if (isset($expiresDate)) {
            $isExpired = $expirationTime->isPast();
        }

        SubscriptionService::iOSRTDNInAppSubscription($productId, $originalTransactionId, $isExpired, $receiptDetails, $status, $latestReceipt);
    }
}