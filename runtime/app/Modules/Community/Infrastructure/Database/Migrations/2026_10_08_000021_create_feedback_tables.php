<?php

declare(strict_types=1);

use App\Shared\Infrastructure\MysqlChecks;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ratings', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->unsignedTinyInteger('value');
            $table->boolean('made_it')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'recipe_id'], 'rating_user_recipe_unique');
            $table->unique(['id', 'user_id'], 'rating_owner_unique');
        });
        MysqlChecks::add(Schema::getConnection(), 'ratings', 'rating_bounds', 'value BETWEEN 1 AND 5 AND (made_it IS NULL OR made_it IN (0, 1))');
        $parentConstraint = Schema::getConnection()->getTablePrefix().'comment_parent_fk';
        Schema::create('comments', static function (Blueprint $table) use ($parentConstraint): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('description');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['id', 'recipe_id'], 'comment_recipe_identity');
            // The composite foreign key prevents a reply from changing recipes.
            $table->foreign(['parent_id', 'recipe_id'], $parentConstraint)->references(['id', 'recipe_id'])->on('comments')->cascadeOnDelete();
            $table->index(['recipe_id', 'deleted_at', 'created_at', 'id'], 'comment_listing_index');
        });
        MysqlChecks::add(Schema::getConnection(), 'comments', 'comment_content', 'CHAR_LENGTH(TRIM(description)) > 0');
        Schema::create('reports', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('category', ['bug', 'suggestion', 'idea', 'partnership', 'application_improvement', 'other']);
            $table->string('reporter_name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        MysqlChecks::add(Schema::getConnection(), 'reports', 'report_content', 'CHAR_LENGTH(TRIM(title)) > 0 AND CHAR_LENGTH(TRIM(description)) > 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('ratings');
    }
};
