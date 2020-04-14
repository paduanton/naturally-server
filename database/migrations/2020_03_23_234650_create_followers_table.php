<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFollowersTable extends Migration
{

    public function up()
    {
        Schema::create('followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id');
            $table->foreignId('following_users_id');
            $table->timestamp('followed_at')->useCurrent();
            $table->softDeletesTz('unfollowed_at', 0);
            $table->foreign('users_id')->references('id')->on('users');
            $table->foreign('following_users_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('followers');
    }
}
