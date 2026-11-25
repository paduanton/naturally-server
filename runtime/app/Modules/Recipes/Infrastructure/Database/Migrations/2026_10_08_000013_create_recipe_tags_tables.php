<?php

declare(strict_types=1);

use App\Shared\Infrastructure\MysqlChecks;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tags', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('hashtag', 100)->unique('tag_hashtag_unique');
            $table->timestamps();
        });
        MysqlChecks::add(Schema::getConnection(), 'tags', 'tag_content', 'CHAR_LENGTH(TRIM(hashtag)) > 0');
        Schema::create('recipe_tags', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['recipe_id', 'tag_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_tags');
        Schema::dropIfExists('tags');
    }
};
