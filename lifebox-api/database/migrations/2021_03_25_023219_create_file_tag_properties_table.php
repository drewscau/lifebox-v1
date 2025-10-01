<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileTagPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_tag_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_tag_id');
            $table->unsignedBigInteger('tag_property_id');
            $table->string('value', 2048);
            $table->timestamps();

            $table->foreign('file_tag_id')
                ->on('file_tag')
                ->references('id');
            $table->foreign('tag_property_id')
                ->on('tag_properties')
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
        Schema::dropIfExists('file_tag_properties');
    }
}
