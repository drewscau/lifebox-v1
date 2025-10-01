<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRelationToTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('file_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('tag_id')->change();
            $table->unsignedBigInteger('file_id')->change();

            $table->foreign('tag_id')
                ->on('tags')
                ->references('id');
            $table->foreign('file_id')
                ->on('files')
                ->references('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('file_tag', function (Blueprint $table) {
            //
        });
    }
}
