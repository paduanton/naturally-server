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
            $table->unsignedBigInteger('provider_id')->unique();
            $table->string('username');
            $table->string('profile_url');
            $table->string('picture_url');
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('users_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('oauth_providers');
    }
}
