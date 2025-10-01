<?php

namespace App\DataTransferObjects;

use Carbon\Carbon;

class CouponDetail
{
    private $amountOff;
    private $percentOff;
    private $maxRedeem;
    private $lastRedeemDate;
    private $priceId;
    private $currency;

    public function __construct(array $request)
    {
        $this->amountOff = $request['amount_off'] ?? 0;
        $this->percentOff = $request['percent_off'] ?? 0;
        $this->maxRedeem = $request['max_redeem'] ?? null;
        $this->lastRedeemDate = $request['last_redeem_date'] ?? null
            // stripe will set this as expiry so we have to set it to the next day
            ? Carbon::createFromFormat('Y-m-d', $request['last_redeem_date'])->addDay()
            : Carbon::now()->add('1 year');
        $this->priceId = $request['price_id'];
        $this->currency = $request['currency'];
    }

    public function getCouponCreateRequestArray()
    {
        $requestArray = [];

        if ($this->amountOff > 0) {
            $requestArray['amount_off'] = $this->amountOff;
            $requestArray['currency'] = $this->currency;
        }
        if ($this->percentOff > 0 && $this->amountOff === 0) {
            $requestArray['percent_off'] = $this->percentOff;
        }
        if ($this->maxRedeem !== null) {
            $requestArray['max_redemptions'] = $this->maxRedeem;
        }
        $requestArray['redeem_by'] = $this->lastRedeemDate->timestamp;
        $requestArray['metadata'] = ['plan_id' => $this->priceId];

        return $requestArray;
    }
}
