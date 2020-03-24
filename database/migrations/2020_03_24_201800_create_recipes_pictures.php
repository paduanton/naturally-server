<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipesPictures extends Migration
{
   
    public function up()
    {
        Schema::create('recipes_pictures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipes_id');
            $table->string('picture_url');
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('recipes_id')->references('id')->on('recipes');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipes_pictures');
    }
}
