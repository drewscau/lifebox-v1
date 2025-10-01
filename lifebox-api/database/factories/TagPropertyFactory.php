<?php

namespace Database\Factories;

use App\Models\TagProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagPropertyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TagProperty::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'tag_id' => 1,
            'name' => $this->faker->word,
            'type' => TagProperty::TYPE_OTHERS,
            'system_created' => 0,
        ];
    }
}
