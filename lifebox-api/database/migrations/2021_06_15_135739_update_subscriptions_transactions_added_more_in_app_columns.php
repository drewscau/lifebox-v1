<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSubscriptionsTransactionsAddedMoreInAppColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_transactions', function (Blueprint $table) {
            $table->string('in_app_ownership_type')->nullable();
            $table->string('is_in_intro_offer_period')->nullable();
            $table->string('is_trial_period')->nullable();
            $table->string('subscription_group_identifier')->nullable();
            $table->string('expires_date')->nullable();
            $table->string('original_purchase_date')->nullable();
            $table->string('purchase_date')->nullable();
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
            $table->dropColumn('in_app_ownership_type');
            $table->dropColumn('is_in_intro_offer_period');
            $table->dropColumn('is_trial_period');
            $table->dropColumn('subscription_group_identifier');
            $table->dropColumn('expires_date');
            $table->dropColumn('original_purchase_date');
            $table->dropColumn('purchase_date');
        });
    }
}
