<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** @var User */
        $admin = User::updateOrCreate(
            [
                'email' => 'admin@lifebox.net.au',
                'user_type' => 'administrator',
            ],
            [
                'first_name' => 'Admin',
                'last_name' => 'Admin',
                'mobile' => '1300711369',
                'username' => 'admin',
                'user_type' => 'administrator',
                'account_number' => '1111',
                'user_status' => 'active',
                'email' => 'admin@lifebox.net.au',
                'lifebox_email' => 'admin@lifebox.net.au',
                'password' => bcrypt('123456'),
                'email_verified_at' => now()
            ]
        );
        $admin->generateToken();
    }
}
