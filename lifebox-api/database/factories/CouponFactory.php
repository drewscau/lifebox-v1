<?php

namespace Database\Factories;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'stripe_id' => $this->faker->word(),
            'amount_off' => 1000,
            'percent_off' => null,
            'max_redeem' => 1,
            'last_redeem_date' => Carbon::now()->add(1, 'year'),
        ];
    }
}
