<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRetailerUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retailer_users', function (Blueprint $table) {
            $table->id();
            $table->string('retailer_account_number', 255)->unique();
            $table->string('retailer_password',255);
            $table->string('retailer_status', 255);
            $table->string('company', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('retailer_users');
    }
}
