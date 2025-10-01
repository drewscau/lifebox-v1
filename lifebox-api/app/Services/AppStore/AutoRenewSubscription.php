<?php

namespace App\Services\AppStore;

use App\Models\Subscription;
// use App\Services\SubscriptionService;
use Imdhemy\Purchases\Events\AppStore\DidRenew;
use App\Services\AppStore\HandleHelper;
use Symfony\Component\Console\Output\ConsoleOutput;

use Carbon\Carbon;

class AutoRenewSubscription
{
    /**
     * @param DidRenew $event
     */
    public function handle(DidRenew $event)
    {
        HandleHelper::handle($event, Subscription::IN_APP_STATUS_RENEW);
        $out = new ConsoleOutput();
        $out->writeln("iOSAutoRenewSubscription: ");
        // // The following data can be retrieved from the event
        // $isExpired = false;
        // $notification = $event->getServerNotification();
        // $subscription = $notification->getSubscription();
        // $receiptDetails = $subscription->getProviderRepresentation();
        // $productId = $subscription->getItemId();
        // $originalTransactionId = $subscription->getUniqueIdentifier();

        // // $class_methods = get_class_methods($receiptDetails);

        // // foreach ($class_methods as $method_name) {
        // //     $out->writeln($method_name);
        // // }
        
        // // $out->writeln("getExpiresDate: " . $receiptDetails->getExpiresDate()->getCarbon()->toISOString());
        // // $out->writeln("originalTransactionId: " . $originalTransactionId);
        // // $out->writeln("receiptDetails getExpirationDate: " . $receiptDetails->getExpirationDate()->getCarbon()->toISOString());

        // $expirationTime = $subscription->getExpiryTime();
        // if (isset($expiresDate)) {
        //     $isExpired = $expirationTime->isPast();
        // }
        // // $out->writeln("originalTransactionId: " . print_r($originalTransactionId, true));
        // // $out->writeln("isExpired: " . print_r($isExpired, true));
        // // $out->writeln("productId: " . print_r($productId, true));
        // SubscriptionService::iOSRTDNInAppSubscription($productId, $originalTransactionId, $isExpired, $receiptDetails, Subscription::IN_APP_STATUS_RENEW);
    }
}
