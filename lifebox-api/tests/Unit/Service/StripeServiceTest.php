<?php

namespace Tests\Unit\Service;

use App\DataTransferObjects\CreditCard;
use App\Models\User;
use App\Services\StripeService;
use ErrorException;
use Illuminate\Support\Facades\App;
use League\Flysystem\FileNotFoundException;
use Stripe\Coupon;
use Stripe\PaymentMethod;
use Stripe\Service\CouponService;
use Stripe\Service\PaymentMethodService;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeServiceTest extends TestCase
{
    private $stripeClient;
    private $stripeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripeClient = $this->createMock(StripeClient::class);
        $this->stripeClient->coupons = $this->createMock(CouponService::class);
        $this->stripeClient->paymentMethods = $this->createMock(PaymentMethodService::class);
        $this->stripeService = new StripeService($this->stripeClient);
    }

    public function testGetCouponPlanIdMatches()
    {
        $user = new User();
        $coupon = new Coupon('test');
        $coupon->metadata = ['plan_id' => 'plan_id_test'];
        $user->stripe_id = 'test_id';
        $this->stripeClient->coupons->method('retrieve')
            ->willReturn($coupon);

        $this->assertEquals(
            'plan_id_test',
            $this->stripeService->getCouponPlanId('test')
        );
    }

    public function testCreatePaymentMethodMatches()
    {
        $paymentMethod = new PaymentMethod('payment-method-id');

        $this->stripeClient->paymentMethods
            ->method('create')
            ->willReturn($paymentMethod);

        $this->assertEquals(
            $paymentMethod->id,
            $this->stripeService->createPaymentMethod(
                new User(),
                new CreditCard([
                    'card_number' => '111',
                    'expiry_month' => 2,
                    'expiry_year' => 2022,
                    'cvc' => 111
                ])
            )->id
        );
    }

    public function testCreatePaymentMethodMissingCardDetailsException()
    {
        $this->expectException(ErrorException::class);
        $paymentMethod = new PaymentMethod('payment-method-id');

        $this->stripeClient->paymentMethods
            ->method('create')
            ->willReturn($paymentMethod);

        $this->stripeService->createPaymentMethod(
            new User(),
            new CreditCard(['missing-card-number'])
        );
    }
}
