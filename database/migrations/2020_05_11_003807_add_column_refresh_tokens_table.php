<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnRefreshTokensTable extends Migration
{

    public function up()
    {
        Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
            $table->string('token', 768)->after('access_token_id')->unique();
            $table->foreign('access_token_id')->constrained('oauth_access_tokens')->references('id')->on('oauth_access_tokens')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
            $table->dropColumn('token');
            $table->string('access_token_id', 100)->after('id');
        });
    }
}
