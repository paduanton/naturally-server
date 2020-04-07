<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersImagesTable extends Migration
{
    
    public function up()
    {
        Schema::create('users_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id');
            $table->boolean('thumbnail');
            $table->string('picture_url')->unique();
            $table->string('filename')->unique()->nullable();;
            $table->string('path')->unique()->nullable();;
            $table->enum('mime', ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'])->nullable();
            $table->string('original_filename')->nullable();
            $table->enum('original_extension', ['png', 'jpg', 'jpeg', 'gif'])->nullable();
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('users_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users_images');
    }
}
