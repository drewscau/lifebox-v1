<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Retailer;
use App\Models\User;
use App\Models\VoucherCode;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class VoucherCodeTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['user_type' => User::USER_TYPE_ADMIN]);
        $this->user = User::factory()->create(['user_type' => User::USER_TYPE_USER]);
    }

    public function test_list_voucher_codes_by_non_admin()
    {
        Passport::actingAs($this->user, ['lifebox']);

        $response = $this->get('/api/voucher-code');
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_list_voucher_codes_success()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $coupon = Coupon::factory()->create();
        $retailer = Retailer::factory()->create();
        $voucherCode = VoucherCode::factory()
            ->for($coupon, 'coupon')
            ->for($retailer, 'retailer')
            ->create();

        Passport::actingAs($this->admin, ['lifebox']);

        $response = $this->get('/api/voucher-code');
        $response->assertStatus(Response::HTTP_OK);

        $responseCoupon = json_decode($response->getContent(), true)['data'][0];
        $this->assertEquals(
            $coupon->id,
            $responseCoupon['coupon_id']
        );
        $this->assertEquals(
            $voucherCode->id,
            $responseCoupon['id']
        );
        $this->assertEquals(
            $voucherCode->code,
            $responseCoupon['code']
        );
    }

    public function test_create_voucher_by_non_admin()
    {
        Passport::actingAs($this->user, ['lifebox']);

        $response = $this->postJson('/api/voucher-code');
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_create_voucher_success()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $coupon = Coupon::factory()->create();
        $retailer = Retailer::factory()->create(['company' => Retailer::DEFAULT_RETAILER_COMPANY]);

        Passport::actingAs($this->admin, ['lifebox']);

        $response = $this->post('/api/voucher-code', [
                'code' => 'FREE-TEST-123',
                'coupon_id' => $coupon->id,
            ]
        );

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('voucher_codes', [
            'code' => 'FREE-TEST-123',
            'coupon_id' => $coupon->id,
            'retailer_id' => $retailer->id,
        ]);
    }
}

