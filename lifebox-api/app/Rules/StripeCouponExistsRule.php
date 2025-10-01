<?php

namespace App\Rules;

use App\Services\StripeService;
use Exception;
use Illuminate\Contracts\Validation\Rule;
use Stripe\Coupon;
use Stripe\Stripe;

class StripeCouponExistsRule implements Rule
{
    private $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    public function passes($attribute, $value)
    {
        try {
            $this->stripeService->getCoupon($value);
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    public function message()
    {
        return 'The :attribute does not exist.';
    }
}
