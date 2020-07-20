<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePdfDownloadsTable extends Migration
{
    
    public function up()
    {
        Schema::create('pdf_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id')->nullable();
            $table->foreignId('recipes_id');
            $table->ipAddress('ip');
            $table->string('user_agent');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recipes_id')->references('id')->on('recipes')->onDelete('cascade');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('pdf_downloads');
    }
}
