<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Retailer;
use App\Models\User;
use App\Models\VoucherCode;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    // this a coupon that exists in stripe test account, update if it gets deleted :-)
    const STRIPE_COUPON_ID = '8axFtmoT';

    public function test_register_no_payment_success()
    {
        Event::fake();

        $this->postJson('/api/register',
            [
                'email' => 'test@example.com',
                'first_name' => 'Tester',
                'last_name' => 'Foo',
                'password' => 'secret',
                'username' => 'tester',
            ])
            ->assertOk()
        ;


        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'email_verified_at' => null,
            'user_status' => User::STATUS_UNSUBSCRIBED,
        ]);

        Event::assertDispatched(Registered::class);
    }

    /**
     * @param array $input
     * @dataProvider dataInvalidInput
     */
    public function test_register_input_validation(array $input)
    {
        Event::fake();

        $this->postJson('/api/register', $input)->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseCount('users', 0);

        Event::assertNotDispatched(Registered::class);
    }

    public function dataInvalidInput()
    {
        return [
            [[]], // blank
            [
                [ // missing names
                    'email' => 'test@example.com',
                    'password' => 'secret',
                    'username' => 'tester',
                ]
            ],
            [
                [ // invalid email
                    'email' => 'test',
                    'first_name' => 'Tester',
                    'last_name' => 'Foo',
                    'password' => 'secret',
                    'username' => 'tester',
                ]
            ],
            [
                [ // short password
                    'email' => 'test',
                    'first_name' => 'Tester',
                    'last_name' => 'Foo',
                    'password' => 'pass',
                    'username' => 'tester',
                ]
            ],
            [
                [ // not existing coupon code
                    'email' => 'test@example.com',
                    'first_name' => 'Tester',
                    'last_name' => 'Foo',
                    'password' => 'pass',
                    'username' => 'tester',
                    'coupon_code' => 'NOT_EXISTING_COUPON_CODE',
                ]
            ],
            [
                [ // coupon code expired
                    'email' => 'test@example.com',
                    'first_name' => 'Tester',
                    'last_name' => 'Foo',
                    'password' => 'pass',
                    'username' => 'tester',
                    'coupon_code' => 'DOLGdMcM', // this an actual expired stripe coupon in test account, update if deleted
                ]
            ],
        ];
    }

    public function test_register_with_coupon_success()
    {
        Event::fake();

        $data = [
            'email' => 'test@example.com',
            'first_name' => 'Tester',
            'last_name' => 'Foo',
            'password' => 'secret',
            'username' => 'tester',
            'coupon_code' => self::STRIPE_COUPON_ID,
        ];
        Event::fake();

        $this->postJson('/api/register', $data)
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'email' => $data['email'],
            'email_verified_at' => null,
            'user_status' => User::STATUS_SUBSCRIBED,
        ]);

        Event::assertDispatched(Registered::class);
    }

    public function test_register_with_voucher_code_success()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $coupon = Coupon::factory()->create(['stripe_id' => self::STRIPE_COUPON_ID]);
        $retailer = Retailer::factory()->create();
        VoucherCode::factory()
            ->for($coupon, 'coupon')
            ->for($retailer, 'retailer')
            ->create(['code' => 'TEST_FREE']);

        Event::fake();

        $data = [
            'email' => 'test@example.com',
            'first_name' => 'Tester',
            'last_name' => 'Foo',
            'password' => 'secret',
            'username' => 'tester',
            'coupon_code' => 'TEST_FREE'
        ];
        Event::fake();

        $this->postJson('/api/register', $data)
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'email' => $data['email'],
            'email_verified_at' => null,
            'user_status' => User::STATUS_SUBSCRIBED,
        ]);

        Event::assertDispatched(Registered::class);
    }
}
