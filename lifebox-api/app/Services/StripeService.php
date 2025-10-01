<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\CouponDetail;
use App\DataTransferObjects\CreditCard;
use App\Models\User;
use Stripe\Coupon;
use Illuminate\Support\Str;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentMethod;
use Stripe\Price;
use Stripe\Product;
use Stripe\StripeClient;

class StripeService
{
    private $stripeClient;

    public function __construct(StripeClient $stripeClient)
    {
        $this->stripeClient = $stripeClient;
    }

    /**
     * @param string $couponId
     * @return string
     * @throws ApiErrorException
     */
    public function getCouponPlanId(string $couponId): string
    {
        $coupon = $this->stripeClient->coupons->retrieve($couponId);

        return $coupon->metadata['plan_id'];
    }

    /**
     * @param string $couponId
     * @return Coupon
     * @throws ApiErrorException
     */
    public function getCoupon(string $couponId): Coupon
    {
        return $this->stripeClient->coupons->retrieve($couponId);
    }

    /**
     * @param User $user
     * @param CreditCard $card
     * @return PaymentMethod
     * @throws ApiErrorException
     */
    public function createPaymentMethod(User $user, CreditCard $card): PaymentMethod
    {
        $paymentMethod = $this->stripeClient->paymentMethods->create($card->getCardRequestArray());

        $this->stripeClient->paymentMethods->attach(
            $paymentMethod->id,
            ['customer' => $user->stripe_id]
        );

        return $paymentMethod;
    }

    public function createCoupon(CouponDetail $couponDetail)
    {
        return $this->stripeClient->coupons->create($couponDetail->getCouponCreateRequestArray());
    }

    /**
     * @param string $name
     * @return string|null
     * @throws ApiErrorException
     */
    public function getStripeProductWithName(string $name)
    {
        /** @var Product $product */
        foreach ($this->stripeClient->products->all() as $product) {
            if (str_contains($product->name, $name)) {
                return $product->id;
            }
        }

        return null;
    }

    public function getStripePriceWithProduct(string $productId)
    {
        /** @var Price $price */
        foreach ($this->stripeClient->prices->all(['limit' => 50]) as $price) {
            if ($productId === $price->product) {
                return $price->id;
            }
        }

        return null;
    }
}
