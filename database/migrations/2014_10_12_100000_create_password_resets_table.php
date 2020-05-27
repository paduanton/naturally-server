<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePasswordResetsTable extends Migration
{
    public function up()
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token')->unique();
            $table->boolean('done');
            $table->timestampTz('expires_at', 0);
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('password_resets');
    }
}
