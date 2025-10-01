<?php

namespace Database\Factories;

use App\Models\FileTagProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileTagPropertyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FileTagProperty::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'file_tag_id' => 1,
            'tag_property_id' => 1,
            'value' => $this->faker->word,
        ];
    }
}
