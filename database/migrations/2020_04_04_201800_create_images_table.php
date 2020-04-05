<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipesPictures extends Migration
{
   
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id');
            $table->foreignId('recipes_id')->nullable();
            $table->enum('type', ['users_picture', 'recipes_picture']);
            $table->string('picture_url')->unique();
            $table->enum('mime', ['png', 'jpg', 'jpeg', 'gif']);
            $table->string('filename');
            $table->string('original_filename');
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('users_id')->references('id')->on('users');
            $table->foreign('recipes_id')->references('id')->on('recipes');
        });
    }

    public function down()
    {
        Schema::dropIfExists('images');
    }
}
