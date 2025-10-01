<?php

namespace Database\Seeders;

use App\Models\RetailerUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RetailerUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RetailerUser::firstOrCreate([
           'retailer_status' => RetailerUser::STATUS_ACTIVE,
           'company' => 'Lifebox',

        ]);
        DB::table('retailer_users')->insert([
            'retailer_account_number' => '0111',
            'retailer_status' => 'inactive',
            'company' => 'Woolworths',
            'retailer_password' => bcrypt('123456'),
        ]);

        DB::table('retailer_users')->insert([
            'retailer_account_number' => '0222',
            'retailer_status' => 'inactive',
            'company' => 'Coles',
            'retailer_password' => bcrypt('123456'),
        ]);
    }
}
