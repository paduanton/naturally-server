<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIngredientsTable extends Migration
{

    public function up()
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipes_id');
            $table->string('measure');
            $table->string('description');
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('recipes_id')->references('id')->on('recipes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ingredients');
    }
}
