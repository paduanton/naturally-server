<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailVerificationsTable extends Migration
{

    public function up()
    {
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token')->unique();
            $table->string('signature')->unique();
            $table->boolean('done');
            $table->dateTimeTz('expires_at', 0);
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_verifications');
    }
}
