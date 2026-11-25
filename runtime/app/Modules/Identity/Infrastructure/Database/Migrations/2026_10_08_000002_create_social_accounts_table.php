<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_accounts', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('provider', ['google', 'facebook', 'x']);
            // Provider identifiers are opaque, case-sensitive strings, never numbers.
            $table->string('provider_user_id', 191)->collation('utf8mb4_bin');
            $table->string('username')->nullable();
            $table->string('profile_url', 2048)->nullable();
            $table->string('picture_url', 2048)->nullable();
            $table->unique(['provider', 'provider_user_id'], 'social_identity_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
