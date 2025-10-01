<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = File::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        return [
            'parent_id' => 1,
            'user_id' => 1,
            'file_name' => $this->faker->name,
            'file_status' => 'close',
            'file_type' => 'folder',
            'file_extension' => $this->faker->fileExtension,
            'file_size' => 0.20,
            'file_reference' => '/userstorage/1/filename',
        ];
    }
}
