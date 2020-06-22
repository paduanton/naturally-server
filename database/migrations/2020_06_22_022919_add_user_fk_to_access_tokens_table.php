<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserFkToAccessTokensTable extends Migration
{

    public function up()
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->foreign('user_id')->constrained('users')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->constrained('oauth_clients')->references('id')->on('oauth_clients')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('user_id');
        });
    }
}
