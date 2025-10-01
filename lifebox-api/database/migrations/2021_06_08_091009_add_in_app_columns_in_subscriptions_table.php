<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInAppColumnsInSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('in_app_id')->nullable();
            $table->string('in_app_description')->nullable();
            $table->string('in_app_alias')->nullable();
            $table->string('in_app_title')->nullable();
            $table->string('in_app_type')->nullable();      
            $table->boolean('in_app_valid')->default(false);
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
            $table->dropColumn('in_app_id');
            $table->dropColumn('in_app_description');
            $table->dropColumn('in_app_alias');
            $table->dropColumn('in_app_title');
            $table->dropColumn('in_app_type');
            $table->dropColumn('in_app_valid');
        });
    }
}
