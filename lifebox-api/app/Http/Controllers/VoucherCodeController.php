<?php

namespace App\Http\Controllers;

use App\Models\VoucherCode;
use App\Services\RetailerService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VoucherCodeController
{
    /**
     * Create a voucher code
     *
     * @authenticated
     * @bodyParam code string required voucher code to create
     * @bodyParam coupon_id int required id of coupon that this voucher code belongs to
     * @bodyParam retailer_id int retailer that owns the voucher code will default to Lifebox if not specified
     * @bodyParam max_redeem int number of times this voucher code can be redeemed
     * @bodyParam last_redeem_date string date in Y-m-d format for when it can be last redeemed
     * @group Admin
     */
    public function store(Request $request, RetailerService $retailerService)
    {
        $request->validate(
            [
                'code' => ['required', 'string', 'max:255'],
                'coupon_id' => ['required', 'exists:coupons,id'],
                'retailer_id' => ['sometimes', 'exists:retailers,id'],
                'max_redeem' => ['sometimes', 'numeric'],
                'last_redeem_date' => ['sometimes', 'date'],
            ]
        );

        return VoucherCode::create(
            [
                'code' => $request->code,
                'coupon_id' => $request->coupon_id,
                'retailer_id' => $request->input('retailer_id', $retailerService->getDefaultRetailerId()),
                'max_redeem' => $request->input('max_redeem', 1),
                'last_redeem_date' => Carbon::createFromFormat(
                    'Y-m-d',
                    $request->input(
                        'last_redeem_date',
                        Carbon::now()->add('1 month')->format('Y-m-d')
                    )
                )
            ]
        );
    }

    /**
     * List all voucher codes
     *
     * @authenticated
     * @group Admin
     * @queryParam length int defaults to 50
     */
    public function list(Request $request)
    {
        return VoucherCode::with('coupon')->paginate($request->query('length', 50));
    }
}
