<?php
namespace App\Services\AppStore;

use App\Models\Subscription;
use Imdhemy\Purchases\Events\AppStore\DidFailToRenew;
use Symfony\Component\Console\Output\ConsoleOutput;

class FailToRenewSubscription
{
    /**
     * @param DidFailToRenew $event
     */
    public function handle(DidFailToRenew $event)
    {
        HandleHelper::handle($event, Subscription::IN_APP_STATUS_FAILED_TO_RENEW);
    }
}
