<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipesImagesTable extends Migration
{
   
    public function up()
    {
        Schema::create('recipes_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipes_id');
            $table->boolean('thumbnail');
            $table->string('picture_url')->unique();
            $table->string('filename')->unique();
            $table->enum('mime', ['image/png', 'image/jpg', 'image/jpeg', 'image/gif']);
            $table->string('original_filename');
            $table->enum('original_extension', ['png', 'jpg', 'jpeg', 'gif']);
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('recipes_id')->references('id')->on('recipes');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipes_images');
    }
}
