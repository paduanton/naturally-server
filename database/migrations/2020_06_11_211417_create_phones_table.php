<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhonesTable extends Migration
{

    public function up()
    {
        Schema::create('phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id');
            $table->boolean('main');
            $table->string('label', 25);
            $table->string('country');
            $table->string('country_code');
            $table->string('number', 20)->unique();
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('users_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('phones');
    }
}
