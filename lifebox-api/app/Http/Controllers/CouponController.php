<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use App\DataTransferObjects\CouponDetail;
use App\Exceptions\Payment\InvalidStripeCouponException;
use App\Exceptions\Payment\MissingPriceForStripeProductException;
use App\Exceptions\Payment\MissingStripeProductException;
use App\Models\Coupon;
use App\Rules\StripeCouponExistsRule;
use App\Services\VoucherCodeService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Coupon as StripeCoupon;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response;

class CouponController
{
    /**
     * Get a coupon
     *
     * @group Coupon
     * @unauthenticated
     * @urlParam couponId string could be a voucher_code->code or stripe_coupon_id
     * @param string $couponId
     * @param StripeService $stripeService
     * @param VoucherCodeService $voucherCodeService
     * @return JsonResponse|StripeCoupon
     */
    public function getCoupon(string $couponId, StripeService $stripeService, VoucherCodeService $voucherCodeService)
    {
        try {
            return $stripeService->getCoupon($voucherCodeService->getStripeCouponId($couponId));
        } catch (ApiErrorException $apiErrorException) {
            return response()->json(
                ['errors' => $apiErrorException->getMessage()],
                Response::HTTP_NOT_FOUND
            );
        } catch (Exception $exception) {
            return response()->json(
                ['errors' => $exception->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Create a coupon
     *
     * Create a coupon from an existing stripe_coupon or
     * create new stripe_coupon with new amount/percent off
     *
     * @authenticated
     * @group Admin
     * @bodyParam amount_off int required Discount amount in cents (only one of amount_off or percent_off is needed)
     * @bodyParam percent_off int required Discount percentage (only one of amount_off or percent_off is needed)
     * @bodyParam is_annual bool defaults to monthly
     * @bodyParam stripe_coupon_id string If given we will not create a new stripe coupon
     * @param Request $request
     * @param StripeService $stripeService
     * @return mixed
     * @throws ApiErrorException
     * @throws InvalidStripeCouponException
     * @throws MissingPriceForStripeProductException
     * @throws MissingStripeProductException
     */
    public function store(Request $request, StripeService $stripeService)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'stripe_coupon_id' => ['sometimes', new StripeCouponExistsRule($stripeService)],
                'amount_off' => ['required_without_all:stripe_coupon_id,percent_off', 'integer'],
                'percent_off' => ['required_without_all:stripe_coupon_id,amount_off', 'numeric'],
                'is_annual' => ['sometimes', 'boolean'],
                'max_redeem' => ['sometimes', 'integer'],
                'last_redeem_date' => ['sometimes', 'date'],
            ]
        );
        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->errors()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        $productType = $request->input('is_annual', false)
            ? Coupon::PRODUCT_TYPE_ANNUALLY
            : Coupon::PRODUCT_TYPE_MONTHLY;
        if (!$request->has('stripe_coupon_id')) {
            $productId = $stripeService->getStripeProductWithName($productType);
            if ($productId === null) {
                throw new MissingStripeProductException('Unable to find product with name: ' . $productType);
            }
            $priceId = $stripeService->getStripePriceWithProduct($productId);
            if ($priceId === null) {
                throw new MissingPriceForStripeProductException(
                    'No stripe price found for product with name: ' . $productType
                );
            }
            $stripeCoupon = $stripeService->createCoupon(
                new CouponDetail(
                    array_merge(
                        $request->toArray(),
                        ['price_id' => $priceId, 'currency' => config('app.currency')]
                    )
                )
            );
        } else {
            $stripeCoupon = $stripeService->getCoupon($request->stripe_coupon_id);
        }

        if (!$stripeCoupon->valid) {
            throw new InvalidStripeCouponException('Coupon with id: ' . $stripeCoupon->id . ' is not valid');
        }

        return Coupon::create(
            [
                'stripe_id' => $stripeCoupon->id,
                'amount_off' => $stripeCoupon->amount_off,
                'percent_off' => $stripeCoupon->percent_off,
                'last_redeem_date' => $stripeCoupon->redeem_by
                    // this will be expiry on stripe so 1day before is effectively last_redeem_date
                    ? Carbon::createFromTimestamp($stripeCoupon->redeem_by)->subDay()
                    : null,
                'product_type' => $productType,
                'max_redeem' => $stripeCoupon->max_redemptions
            ]
        );
    }

    /**
     * List all coupons
     *
     * @authenticated
     * @group Admin
     * @queryParam length int defaults to 50
     */
    public function list(Request $request)
    {
        return Coupon::paginate($request->query('length', 50));
    }
}
