<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSubscriptionTransactionsForAndroidIap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_transactions', function (Blueprint $table) {
            $table->longtext('purchase_token')->nullable()->change();
            $table->longtext('signature')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_transactions', function (Blueprint $table) {
            $table->string('purchase_token')->change();
            $table->string('signature')->change();
        });
    }
}
