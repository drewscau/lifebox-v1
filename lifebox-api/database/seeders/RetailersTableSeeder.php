<?php

namespace Database\Seeders;

use App\Models\Retailer;
use Illuminate\Database\Seeder;

class RetailersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Retailer::firstOrCreate(
            ['company' => Retailer::DEFAULT_RETAILER_COMPANY],
            ['status' => Retailer::STATUS_ACTIVE]
        );
    }
}
