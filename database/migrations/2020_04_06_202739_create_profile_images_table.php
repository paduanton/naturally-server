<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfileImagesTable extends Migration
{

    public function up()
    {
        Schema::create('profile_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id');
            $table->string('title')->nullable();
            $table->string('alt')->nullable();
            $table->boolean('thumbnail');
            $table->string('picture_url')->unique();
            $table->string('filename')->unique();
            $table->string('path')->unique();
            $table->enum('mime', ['image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'application/octet-stream']);
            $table->string('original_filename');
            $table->enum('original_extension', ['png', 'jpg', 'jpeg', 'gif']);
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('users_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('profile_images');
    }
}
