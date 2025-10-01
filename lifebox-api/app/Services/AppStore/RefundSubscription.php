<?php
namespace App\Services\AppStore;

use App\Models\Subscription;
use Imdhemy\Purchases\Events\AppStore\Refund;
use Symfony\Component\Console\Output\ConsoleOutput;

class RefundSubscription
{
    /**
     * @param Refund $event
     */
    public function handle(Refund $event)
    {
        // tbd
        HandleHelper::handle($event, Subscription::IN_APP_STATUS_REFUNDED);
    }
}
