<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id')->nullable();
            $table->string('title');
            $table->string('description');
            $table->enum('category', ['bug', 'suggestion', 'idea', 'partnership', 'something_else', 'application_improvement']);
            $table->string('who_reported')->nullable();
            $table->string('email')->nullable();
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
}
