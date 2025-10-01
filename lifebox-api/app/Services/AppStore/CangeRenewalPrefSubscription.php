<?php
namespace App\Services\AppStore;

use App\Models\Subscription;
use Imdhemy\Purchases\Events\AppStore\DidChangeRenewalPref;
use Symfony\Component\Console\Output\ConsoleOutput;

class CangeRenewalPrefSubscription
{
    /**
     * @param Cancel $event
     */
    public function handle(DidChangeRenewalPref $event)
    {
        $out = new ConsoleOutput();
        $out->writeln("iOSAutoRenewSubscription: Changed Renewal Pref!");
        HandleHelper::handle($event, Subscription::IN_APP_STATUS_RENEW);
    }
}


