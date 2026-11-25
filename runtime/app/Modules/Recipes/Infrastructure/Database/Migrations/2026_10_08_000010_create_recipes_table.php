<?php

declare(strict_types=1);

use App\Shared\Infrastructure\MysqlChecks;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipes', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            // A duration, not a wall-clock time. No timezone or 24-hour truncation.
            $table->unsignedInteger('cooking_time_seconds');
            $table->enum('category', ['diabetic', 'low-carb', 'heart-healthy', 'low-calorie', 'low-cholesterol', 'low-fat', 'weight-loss', 'high-fiber', 'keto', 'gluten-free', 'healthy', 'vegan', 'vegetarian', 'fitness', 'dairy-free', 'healthy-fried', 'vegetable', 'quick-and-easy', 'other']);
            $table->enum('meal_type', ['breakfast', 'brunch', 'dessert', 'dinner', 'lunch', 'other']);
            $table->string('youtube_video_url', 2048)->nullable();
            $table->decimal('yields', 8, 2);
            $table->unsignedTinyInteger('cost');
            $table->unsignedTinyInteger('complexity');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['deleted_at', 'created_at', 'id'], 'recipe_recent_index');
            $table->index(['category', 'meal_type', 'deleted_at', 'id'], 'recipe_filter_index');
        });
        MysqlChecks::add(Schema::getConnection(), 'recipes', 'recipe_bounds', 'yields > 0 AND cost BETWEEN 1 AND 5 AND complexity BETWEEN 1 AND 5 AND CHAR_LENGTH(TRIM(title)) > 0 AND CHAR_LENGTH(TRIM(description)) > 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
