<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialNetworksTable extends Migration
{
    
    public function up()
    {
        Schema::create('social_networks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id');
            $table->enum('provider_name', ['facebook', 'twitter', 'google']); 
            $table->decimal('provider_id', 65, 0);
            $table->string('username')->nullable();
            $table->string('profile_url')->nullable();
            $table->string('picture_url');
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('users_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('social_networks');
    }
}
