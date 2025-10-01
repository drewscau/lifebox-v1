<?php
namespace App\Services\AppStore;

use App\Models\Subscription;
use Imdhemy\Purchases\Events\AppStore\DidChangeRenewalStatus;
use Symfony\Component\Console\Output\ConsoleOutput;

class ChangedRenewalStatus
{
    /**
     * @param Refund $event
     */
    public function handle(DidChangeRenewalStatus $event)
    {
        $out = new ConsoleOutput();
        $out->writeln("DidChangeRenewalStatus______");
        var_dump($event);
        // HandleHelper::handle($event, Subscription::IN_APP_STATUS_RENEW);
    }
}
