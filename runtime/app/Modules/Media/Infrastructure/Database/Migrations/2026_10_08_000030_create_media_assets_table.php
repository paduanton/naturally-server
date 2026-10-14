<?php

declare(strict_types=1);

use App\Shared\Infrastructure\MysqlChecks;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_assets', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('disk', ['local', 'azure']);
            $table->string('storage_key', 255)->collation('utf8mb4_bin');
            $table->enum('mime', ['image/jpeg', 'image/png', 'image/webp']);
            $table->unsignedInteger('bytes');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->char('sha256', 64)->collation('utf8mb4_bin');
            $table->timestamps();
            $table->unique(['disk', 'storage_key'], 'media_storage_unique');
            $table->unique(['id', 'user_id'], 'media_owner_unique');
        });
        MysqlChecks::add(Schema::getConnection(), 'media_assets', 'media_metadata', "bytes > 0 AND width > 0 AND height > 0 AND REGEXP_LIKE(storage_key, '^images/[a-f0-9]{32}[.](jpg|png|webp)$', 'c') AND REGEXP_LIKE(sha256, '^[a-f0-9]{64}$', 'c') AND ((mime = 'image/jpeg' AND storage_key LIKE '%.jpg') OR (mime = 'image/png' AND storage_key LIKE '%.png') OR (mime = 'image/webp' AND storage_key LIKE '%.webp'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
