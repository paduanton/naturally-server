<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('password_reset_tokens', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->string('email')->primary();
            // Laravel's token repository stores a hash here, not the recovery token.
            $table->string('token');
            $table->timestamp('created_at')->nullable()->index('recovery_expiry_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
