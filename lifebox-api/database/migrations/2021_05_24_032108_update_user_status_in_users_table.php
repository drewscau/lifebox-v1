<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class UpdateUserStatusInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = DB::table('users')->get();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('user_status', ['active', 'inactive', 'subscribed', 'unsubscribed'])->default('inactive');
        });

        foreach ($users as $user) {
            $updateUserQuery = DB::table('users')->where('id', $user->id);

            if ($user->user_status == 'un_subscribed') {
                $updateUserQuery->update(['user_status' => User::STATUS_UNSUBSCRIBED]);
            } else {
                $updateUserQuery->update(['user_status' => $user->user_status]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_status', 255)->default('inactive')->change();
        });
    }
}
