<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_restoration_tokens', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable()->index('restoration_expiry_index');
            $table->foreign('email', Schema::getConnection()->getTablePrefix().'restore_email_fk')->references('email')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void { Schema::dropIfExists('account_restoration_tokens'); }
};
