<?php

declare(strict_types=1);

use App\Shared\Infrastructure\MysqlChecks;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('recipes', static function (Blueprint $table): void {
            $table->unique(['id', 'user_id'], 'recipe_owner_unique');
        });
        $connection = Schema::getConnection();
        $prefix = $connection->getTablePrefix();
        foreach (['recipe_images' => ['recipe_id', 'recipes', 'recipe_media'], 'rating_images' => ['rating_id', 'ratings', 'rating_media']] as $name => [$parentId, $parentTable, $shortName]) {
            Schema::create($name, static function (Blueprint $table) use ($parentId, $parentTable, $shortName, $prefix): void {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger($parentId);
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('asset_id');
                $table->unsignedSmallInteger('position');
                $table->boolean('thumbnail')->default(false);
                $table->string('title')->nullable();
                $table->string('alt')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedTinyInteger('primary_slot')->nullable()->virtualAs('CASE WHEN thumbnail = 1 AND deleted_at IS NULL THEN 1 ELSE NULL END');
                $table->unsignedSmallInteger('active_position')->nullable()->virtualAs('CASE WHEN deleted_at IS NULL THEN position ELSE NULL END');
                $table->unique([$parentId, 'primary_slot'], 'image_primary_unique');
                $table->unique([$parentId, 'active_position'], 'image_position_unique');
                $table->unique([$parentId, 'asset_id'], 'image_asset_unique');
                $table->foreign([$parentId, 'user_id'], $prefix.$shortName.'_parent_fk')->references(['id', 'user_id'])->on($parentTable)->cascadeOnDelete();
                $table->foreign(['asset_id', 'user_id'], $prefix.$shortName.'_asset_fk')->references(['id', 'user_id'])->on('media_assets')->cascadeOnDelete();
            });
            MysqlChecks::add($connection, $name, $shortName.'_bounds', 'position > 0 AND thumbnail IN (0, 1)');
        }
        Schema::create('profile_images', static function (Blueprint $table) use ($prefix): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->boolean('thumbnail')->default(true);
            $table->string('title')->nullable();
            $table->string('alt')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedTinyInteger('primary_slot')->nullable()->virtualAs('CASE WHEN thumbnail = 1 AND deleted_at IS NULL THEN 1 ELSE NULL END');
            $table->unique(['user_id', 'primary_slot'], 'profile_primary_unique');
            $table->unique(['user_id', 'asset_id'], 'profile_asset_unique');
            $table->foreign(['asset_id', 'user_id'], $prefix.'profile_asset_fk')->references(['id', 'user_id'])->on('media_assets')->cascadeOnDelete();
        });
        MysqlChecks::add($connection, 'profile_images', 'profile_primary_bounds', 'thumbnail IN (0, 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_images');
        Schema::dropIfExists('rating_images');
        Schema::dropIfExists('recipe_images');
        Schema::table('recipes', static function (Blueprint $table): void {
            $table->dropUnique('recipe_owner_unique');
        });
    }
};
