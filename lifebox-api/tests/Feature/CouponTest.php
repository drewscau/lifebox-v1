<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    // this a coupon that exists in stripe test account, update if it gets deleted :-)
    const STRIPE_COUPON_ID = '8axFtmoT';
    const AMOUNT_OFF_TEN_DOLLARS = 1000;

    /**
     * @covers \App\Http\Controllers\CouponController::store
     */
    public function test_create_coupon_validation_empty_body()
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        Passport::actingAs(
            $admin,
            ['lifebox']
        );

        $response = $this->postJson('/api/coupon');


        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @covers \App\Http\Controllers\CouponController::store
     */
    public function test_create_coupon_from_scratch()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['user_type' => User::USER_TYPE_ADMIN]);
        Passport::actingAs(
            $admin,
            ['lifebox']
        );

        $response = $this->post(
                '/api/coupon',
                ['amount_off' => self::AMOUNT_OFF_TEN_DOLLARS]
            )
        ;
        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('coupons', [
            'amount_off' => self::AMOUNT_OFF_TEN_DOLLARS,
        ]);
    }

    /**
     * @covers \App\Http\Controllers\CouponController::store
     */
    public function test_create_coupon_from_existing_stripe_coupon()
    {
        $admin = User::factory()->create(['user_type' => User::USER_TYPE_ADMIN]);
        Passport::actingAs(
            $admin,
            ['lifebox']
        );

        $response = $this->post(
                '/api/coupon',
                ['stripe_coupon_id' => self::STRIPE_COUPON_ID]
            )
        ;
        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('coupons', [
            'stripe_id' => self::STRIPE_COUPON_ID,
        ]);
    }

    /**
     * @covers \App\Http\Controllers\CouponController::store
     */
    public function test_create_coupon_from_non_admin_user()
    {
        $user = User::factory()->create(['user_type' => User::USER_TYPE_USER]);
        Passport::actingAs(
            $user,
            ['lifebox']
        );

        $response = $this->post(
                '/api/coupon',
                ['stripe_coupon_id' => self::STRIPE_COUPON_ID]
            )
        ;
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $this->assertDatabaseMissing('coupons', [
            'stripe_id' => self::STRIPE_COUPON_ID,
        ]);
    }

    /**
     * @covers \App\Http\Controllers\CouponController::store
     */
    public function test_create_coupon_with_product_type()
    {
        $admin = User::factory()->create(['user_type' => User::USER_TYPE_ADMIN]);
        Passport::actingAs(
            $admin,
            ['lifebox']
        );

        $response = $this->post(
                '/api/coupon',
                ['percent_off' => 20]
            )
        ;
        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('coupons', [
            'percent_off' => 20,
            'product_type' => Coupon::PRODUCT_TYPE_MONTHLY,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->post(
                '/api/coupon',
                ['percent_off' => 30, 'is_annual' => true]
            )
        ;
        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('coupons', [
            'percent_off' => 30,
            'product_type' => Coupon::PRODUCT_TYPE_ANNUALLY,
        ]);
    }

    /**
     * @covers \App\Http\Controllers\CouponController::list()
     */
    public function test_list_coupon_from_non_admin_user()
    {
        $user = User::factory()->create(['user_type' => User::USER_TYPE_USER]);
        Passport::actingAs(
            $user,
            ['lifebox']
        );

        $response = $this->get('/api/coupon');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @covers \App\Http\Controllers\CouponController::list()
     */
    public function test_list_coupon_from_success()
    {
        $user = User::factory()->create(['user_type' => User::USER_TYPE_ADMIN]);
        Passport::actingAs(
            $user,
            ['lifebox']
        );
        $coupon = Coupon::factory()->create();

        $response = $this->get('/api/coupon')->assertOk();

        $responseCoupon = json_decode($response->getContent(), true)['data'][0];
        $this->assertEquals(
            $coupon->id,
            $responseCoupon['id']
        );
        $this->assertEquals(
            $coupon->stripe_id,
            $responseCoupon['stripe_id']
        );
    }

    /**
     * @covers \App\Http\Controllers\CouponController::store
     */
    public function test_create_coupon_with_last_redeem_date()
    {
        $admin = User::factory()->create(['user_type' => User::USER_TYPE_ADMIN]);
        Passport::actingAs(
            $admin,
            ['lifebox']
        );

        $lastRedeemDate = Carbon::now()->addMonth()->format('Y-m-d');
        $response = $this->post(
                '/api/coupon',
                ['amount_off' => self::AMOUNT_OFF_TEN_DOLLARS, 'last_redeem_date' => $lastRedeemDate]
            )
        ;
        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('coupons', [
            'amount_off' => self::AMOUNT_OFF_TEN_DOLLARS,
            'last_redeem_date' => $lastRedeemDate,
        ]);
    }
}
