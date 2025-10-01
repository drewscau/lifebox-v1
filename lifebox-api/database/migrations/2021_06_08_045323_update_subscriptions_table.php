<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('stripe_id')->nullable()->change();
            $table->string('stripe_status')->nullable()->change();
            $table->enum('type', ['stripe', 'in-app'])->default('stripe');
            $table->string('in_app_status')->nullable()->index();
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
            $table->string('name')->change();
            $table->string('stripe_id')->change();
            $table->string('stripe_status')->change();
            $table->dropColumn('type');
            $table->dropColumn('in_app_status');
        });
    }
}
