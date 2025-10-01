<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateVoucherCodeTableReferenceOnRetailer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('voucher_codes', function (Blueprint $table) {
            $table->dropForeign(['retailer_id']);
            $table->foreign('retailer_id')
                ->references('id')
                ->on('retailers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('voucher_codes', function (Blueprint $table) {
            $table->dropForeign(['retailer_id']);
            $table->foreign('retailer_id')
                ->references('id')
                ->on('retailer_users');
        });
    }
}
