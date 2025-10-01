<?php

namespace App\Rules;

use App\Services\StripeService;
use App\Services\VoucherCodeService;
use Illuminate\Contracts\Validation\Rule;

class ValidateCouponRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        try {
            /** @var VoucherCodeService $voucherCodeService */
            $voucherCodeService = app()->get(VoucherCodeService::class);
            $couponCode = $voucherCodeService->getStripeCouponId($value);

            /** @var StripeService $stripeService */
            $stripeService = app()->get(StripeService::class);
            $coupon = $stripeService->getCoupon($couponCode);

            return $coupon->valid;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The coupon code is invalid.';
    }
}
