<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FilesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('files')->insert([
            'file_name' => 'Folder1',
            'file_reference' => 'userstorage/1',
            'user_id' => 1,
            'file_type' => 'folder',
        ]);

        DB::table('files')->insert([
            'file_name' => 'Folder2',
            'file_reference' => 'userstorage/1',
            'user_id' => 1,
            'file_type' => 'folder',
        ]);
    }
}
