<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnsOnTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn(['type', 'data', 'tags']);
            $table->integer('tag_type_id')->default(1);
            $table->boolean('is_outside_tag')->default(false);
            $table->index(['user_id', 'tag_type_id']); // composite key
            $table->index('tag_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->integer('type')->default(0);
            $table->text('data')->nullable();
            $table->text('tags')->nullable();
            $table->dropColumn(['tag_type_id', 'is_outside_tag']);
        });
    }
}
