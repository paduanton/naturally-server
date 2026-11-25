<?php

declare(strict_types=1);

use App\Shared\Infrastructure\MysqlChecks;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instructions', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('description');
            $table->timestamps();
            $table->unique(['recipe_id', 'position'], 'instruction_order_unique');
        });
        MysqlChecks::add(Schema::getConnection(), 'instructions', 'instruction_content', 'position > 0 AND CHAR_LENGTH(TRIM(description)) > 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('instructions');
    }
};
