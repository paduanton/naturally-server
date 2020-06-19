<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingsImagesTable extends Migration
{

    public function up()
    {
        Schema::create('ratings_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ratings_id');
            $table->string('title')->nullable();
            $table->string('alt')->nullable();
            $table->boolean('thumbnail');
            $table->string('picture_url')->unique();
            $table->string('filename')->unique();
            $table->string('path')->unique();
            $table->enum('mime', ['image/png', 'image/jpg', 'image/jpeg', 'image/gif']);
            $table->string('original_filename');
            $table->enum('original_extension', ['png', 'jpg', 'jpeg', 'gif']);
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('ratings_id')->references('id')->on('ratings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ratings_images');
    }
}
