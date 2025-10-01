<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVoucherCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voucher_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('retailer_id');
            $table->string('code', 255);
            $table->integer('max_redeem')->nullable();
            $table->date('last_redeem_date')->nullable();
            $table->timestamps();
            $table->foreign('coupon_id')
                ->references('id')
                ->on('coupons');
            $table->foreign('retailer_id')
                ->references('id')
                ->on('retailer_users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voucher_codes');
    }
}
