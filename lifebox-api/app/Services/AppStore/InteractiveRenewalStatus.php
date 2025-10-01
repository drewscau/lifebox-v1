<?php
namespace App\Services\AppStore;

use App\Models\Subscription;
use Imdhemy\Purchases\Events\AppStore\InteractiveRenewal;
use Symfony\Component\Console\Output\ConsoleOutput;

class InteractiveRenewalStatus
{
    /**
     * @param Refund $event
     */
    public function handle(InteractiveRenewal $event)
    {
        $out = new ConsoleOutput();
        $out->writeln("InteractiveRenewal______");
        // var_dump($event);
        HandleHelper::handle($event, Subscription::IN_APP_STATUS_RENEW);
    }
}
