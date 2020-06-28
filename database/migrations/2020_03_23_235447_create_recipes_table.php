<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Services\RecipeService;

class CreateRecipesTable extends Migration
{
    protected $recipeService;

    public function __construct()
    {
        $this->recipeService = new RecipeService();
    }

    public function up()
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id');
            $table->string('title');
            $table->string('description');
            $table->timeTz('cooking_time', 0);
            $table->enum('category', $this->recipeService->getRecipeCategories());
            $table->enum('meal_type', $this->recipeService->getRecipeMealTypes());
            $table->string('youtube_video_url')->nullable();
            $table->double('yields');
            $table->tinyInteger('cost');
            $table->tinyInteger('complexity');
            $table->string('notes')->nullable();
            $table->timestampsTz(0);
            $table->softDeletesTz('deleted_at', 0);
            $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recipes');
    }
}
