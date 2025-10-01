<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tag_types')->truncate();

        $data = [
            [
                'name' => 'SYSTEM_TAG',
                'description' => 'Systam Tag'
            ],
            [
                'name' => 'INFO_TAG',
                'description' => 'Info Tag'
            ],
            [
                'name' => 'REMINDER_TAG',
                'description' => 'Reminder Tag'
            ],
            [
                'name' => 'FORM_TAG',
                'description' => 'Form Tag'
            ],
        ];

        foreach ($data as $type) {
            DB::table('tag_types')->insert([
                'name' => $type['name'],
                'description' => $type['description'],
            ]);
        }
    }
}
