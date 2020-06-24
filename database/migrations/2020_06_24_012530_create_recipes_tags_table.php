<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecipesTagsTable extends Migration
{
    
    public function up()
    {
        Schema::create('recipes_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tags_id');
            $table->foreignId('recipes_id');
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('tags_id')->references('id')->on('tags')->onDelete('cascade');
            $table->foreign('recipes_id')->references('id')->on('recipes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipes_tags');
    }
}
