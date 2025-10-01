<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSubscriptionsTableAddedMoreInAppColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('in_app_applicationUsername')->nullable();
            $table->string('in_app_expiryDate')->nullable();
            $table->string('in_app_purchaseDate')->nullable();
            $table->string('in_app_lastRenewalDate')->nullable();
            $table->string('in_app_renewalIntent')->nullable();

            $table->boolean('in_app_expired')->default(false);
            $table->boolean('in_app_trial_period')->default(false);
            $table->boolean('in_app_intro_period')->default(false);
            $table->boolean('in_app_billing_retry_period')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('in_app_applicationUsername');
            $table->dropColumn('in_app_expiryDate');
            $table->dropColumn('in_app_purchaseDate');
            $table->dropColumn('in_app_lastRenewalDate');
            $table->dropColumn('in_app_renewalIntent');

            $table->dropColumn('in_app_expired');
            $table->dropColumn('in_app_trial_period');
            $table->dropColumn('in_app_intro_period');
            $table->dropColumn('in_app_billing_retry_period');
        });
    }
}
