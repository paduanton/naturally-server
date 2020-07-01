<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersFavoriteRecipesTable extends Migration
{

    public function up()
    {
        Schema::create('users_favorite_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id');
            $table->foreignId('recipes_id');
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recipes_id')->references('id')->on('recipes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users_favorite_recipes');
    }
}
