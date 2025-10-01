<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('file_type',  255);
            $table->string('file_extension')->nullable();
            $table->integer('user_id');
            $table->string('file_name', 255);
            $table->string('file_reference', 255)->nullable();
            $table->string('file_status', 255)->default('closed');
            $table->string('file_size', 255)->default('0');
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
