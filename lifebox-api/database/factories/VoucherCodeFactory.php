<?php

namespace Database\Factories;

use App\Models\VoucherCode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherCodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VoucherCode::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => strtoupper($this->faker->word()),
            'max_redeem' => $this->faker->randomDigit(),
            'last_redeem_date' => Carbon::now()->add(1, 'month'),
        ];
    }
}
