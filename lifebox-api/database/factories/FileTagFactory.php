<?php

namespace Database\Factories;

use App\Models\FileTag;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileTagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FileTag::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'tag_id' => 1,
            'file_id' => 1
        ];
    }
}
