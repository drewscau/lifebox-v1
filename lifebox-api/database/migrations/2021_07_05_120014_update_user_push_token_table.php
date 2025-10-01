<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserPushTokenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_push_token', function (Blueprint $table) {
            $table->string('device_platform')->nullable();
            $table->string('device_os')->nullable();
            $table->string('device_os_version')->nullable();
            $table->string('device_name')->nullable();
            $table->string('device_model')->nullable();
            $table->string('device_manufacturer')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_push_token', function (Blueprint $table) {
            $table->dropColumn('device_platform');
            $table->dropColumn('device_os');
            $table->dropColumn('device_os_version');
            $table->dropColumn('device_name');
            $table->dropColumn('device_model');
            $table->dropColumn('device_manufacturer');
        });
    }
}
