<?php
namespace App\Services\AppStore;

use App\Models\Subscription;
use Imdhemy\Purchases\Events\AppStore\Revoke;
use Symfony\Component\Console\Output\ConsoleOutput;

class RevokeSubscription
{
    /**
     * @param Revoke $event
     */
    public function handle(Revoke $event)
    {
        HandleHelper::handle($event, Subscription::IN_APP_STATUS_REVOKED);
    }
}
