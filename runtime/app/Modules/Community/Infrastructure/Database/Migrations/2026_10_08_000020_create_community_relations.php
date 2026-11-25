<?php

declare(strict_types=1);

use App\Shared\Infrastructure\MysqlChecks;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['recipe_likes', 'recipe_favorites'] as $name) {
            Schema::create($name, static function (Blueprint $table): void {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
                $table->unique(['user_id', 'recipe_id'], 'user_recipe_unique');
                $table->timestamps();
            });
        }
        Schema::create('follows', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('followed_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['follower_id', 'followed_id'], 'follow_pair_unique');
            $table->timestamps();
        });
        MysqlChecks::add(Schema::getConnection(), 'follows', 'follow_distinct_users', 'follower_id <> followed_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
        Schema::dropIfExists('recipe_favorites');
        Schema::dropIfExists('recipe_likes');
    }
};
