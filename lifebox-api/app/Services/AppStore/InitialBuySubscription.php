<?php
namespace App\Services\AppStore;

use Imdhemy\Purchases\Events\AppStore\InitialBuy;
use Symfony\Component\Console\Output\ConsoleOutput;

class InitialBuySubscription
{
    /**
     * @param InitialBuy $event
     */
    public function handle(InitialBuy $event)
    {
        // https://api-dev.lifebox.net.au/api/purchases/subscriptions/apple
        $out = new ConsoleOutput();
        $out->writeln("iOSInitialBuySubscription: ");
        HandleHelper::handle($event, Subscription::IN_APP_STATUS_APPROVED);
    }
}
