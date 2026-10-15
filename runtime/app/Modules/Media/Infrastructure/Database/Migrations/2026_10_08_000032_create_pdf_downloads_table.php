<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pdf_downloads', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            // Count delivery events without retaining IPs, cookies or user agents.
            $table->timestamp('created_at')->useCurrent();
            $table->index(['recipe_id', 'created_at', 'id'], 'pdf_download_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_downloads');
    }
};
