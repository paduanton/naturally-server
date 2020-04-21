<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLikesTable extends Migration
{

  public function up()
  {
    Schema::create('likes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('users_id');
      $table->foreignId('recipes_id');
      $table->boolean('is_liked');
      $table->timestampsTz(0);
      $table->softDeletesTz('deleted_at', 0);
      $table->foreign('users_id')->references('id')->on('users');
      $table->foreign('recipes_id')->references('id')->on('recipes');
    });
  }

  public function down()
  {
    Schema::dropIfExists('likes');
  }
}
