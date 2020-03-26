<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{

    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->date('birthday')->nullable();
            $table->string('picture_url')->nullable();
            $table->rememberToken();
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
