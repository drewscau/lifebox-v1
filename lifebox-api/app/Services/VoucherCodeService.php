<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VoucherCode;

class VoucherCodeService
{
    public function getStripeCouponId(string $couponCode)
    {
        if ($voucherCode = VoucherCode::where('code', $couponCode)->first()) {
            return $voucherCode->coupon->stripe_id;
        }

        return $couponCode;
    }
}
