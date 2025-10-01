<?php

namespace App\Http\Controllers;

use App\Exceptions\Payment\MissingPaymentMethodException;
use App\Exceptions\User\UserNotCreatedException;
use App\Models\User;
use App\Rules\ValidateCouponRule;
use App\Rules\LifeboxUsernameRule;
use App\Services\StripeService;
use App\Services\UserService;
use App\Services\VoucherCodeService;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RegisterController extends Controller
{
    /**
     * Register a user
     *
     * @unauthenticated
     * @group User
     * @bodyParam first_name string required
     * @bodyParam last_name string required
     * @bodyParam username string required
     * @bodyParam email string required
     * @bodyParam password string required
     * @bodyParam coupon_code string stripe_coupon_id or voucher_code
     * @bodyParam profile_picture file
     * @bodyParam plan string
     * @bodyParam payment_method string stripe_payment_method id
     * @param Request $request
     * @param StripeService $stripeService
     * @return JsonResponse
     */
    public function __invoke(Request $request, StripeService $stripeService, VoucherCodeService $voucherCodeService)
    {
        $lifeboxSubdomain = config('app.subdomain');

        $data = $request->validate(
            [
                'first_name' => 'required|max:255|string',
                'last_name' => 'required|max:255',
                'username' => ['required', 'max:255', 'unique:users', new LifeboxUsernameRule],
                'email' => 'required|email|unique:users',
                'password' => 'required|min:5',
                'coupon_code' => ['nullable', 'string', new ValidateCouponRule],
                'profile_picture' => 'nullable|image',
                'plan' => ['sometimes'],
                'payment_method' => ['sometimes'],
            ]
        );
        $paymentMethod = $request->input('payment_method', null);
        $couponCode = $request->input('coupon_code', null);
        if ($couponCode) {
            $couponCode = $voucherCodeService->getStripeCouponId($couponCode);
        }

        $accountNumber = 99999999999 - time();
        $data['password'] =  bcrypt($data['password']);
        $data['user_status'] = User::STATUS_UNSUBSCRIBED;
        $data['lifebox_email'] = $data['username'] . "@$lifeboxSubdomain";
        $data['account_number'] = $accountNumber;

        if ($request->file('profile_picture', null)) {
            $data['profile_picture'] = UserService::saveProfilePicture($request);
        }

        $coupon = $couponCode ? $stripeService->getCoupon($couponCode) : null;
        if ($couponCode && $coupon->valid && $coupon->percent_off < 100 && $paymentMethod === null) {
            throw new MissingPaymentMethodException('Partial discount requires a payment method');
        }

        try {
            $user = UserService::create($data);

            if (!$user) {
                throw new UserNotCreatedException('User Not Created');
            }

            $options = [
                'description' => sprintf(
                    'This is the official Stripe Customer account of %s %s, a %s user.',
                    $request->first_name,
                    $request->last_name,
                    config('app.name')
                ),
                'email' => $request->email,
                'name' => "$request->first_name $request->last_name",
                'phone' => $request->mobile
            ];

            $user->createAsStripeCustomer($options);

            // Checks coupon code then apply it to subscribe
            if ($couponCode) {
                $planId = $stripeService->getCouponPlanId($couponCode);
                $user->newSubscription('default', $planId)
                    ->withCoupon($couponCode)
                    ->create(
                        $paymentMethod,
                        ['email' => $user->email]
                    );

                $user->update(['user_status' => User::STATUS_SUBSCRIBED]);
            }

            event(new Registered($user));

            return response()->json(
                ['user' => $user]
            );
        } catch (Exception $e) {
            return response()->json(
                ['msg' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
