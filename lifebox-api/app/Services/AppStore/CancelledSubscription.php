<?php
namespace App\Services\AppStore;

use App\Models\Subscription;
use Imdhemy\Purchases\Events\AppStore\Cancel;
use Symfony\Component\Console\Output\ConsoleOutput;

class CancelledSubscription
{
    /**
     * @param Cancel $event
     */
    public function handle(Cancel $event)
    {
        $out = new ConsoleOutput();
        $out->writeln("iOSAutoRenewSubscription: CANCELLED!");
        HandleHelper::handle($event, Subscription::IN_APP_STATUS_CANCELED);
    }
}
