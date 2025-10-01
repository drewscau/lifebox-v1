<?php
namespace App\Services\AppStore;

use App\Models\Subscription;
use Imdhemy\Purchases\Events\AppStore\DidRecover;
use Symfony\Component\Console\Output\ConsoleOutput;

class RecoverSubscription
{
    /**
     * @param DidRecover $event
     */
    public function handle(DidRecover $event)
    {
        HandleHelper::handle($event, Subscription::IN_APP_STATUS_RECOVERED);
    }
}
