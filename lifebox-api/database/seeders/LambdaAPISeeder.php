<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class LambdaAPISeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'username' => 'lambda-api',
            'email' => 'lambda-api@lifebox.net.au',
            'first_name' => 'lambda',
            'last_name' => 'lambda',
            'user_type' => User::USER_TYPE_ADMIN,
            'email_verified_at' => now(),
            'mobile' => '123',
            'user_status' => User::STATUS_ACTIVE,
            'account_number' => '0000000000',
            'password' => bcrypt('1iF3B0x-L4Md4(0@') // do not dare to change this
        ];
        User::create($data);
    }
}
