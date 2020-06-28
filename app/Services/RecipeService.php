<?php

namespace App\Services;

use App\Recipes;
use Carbon\Carbon;
use App\Services\Interfaces\RecipeInterface;

class RecipeService implements RecipeInterface
{

    public function __construct()
    {
        //
    }

    public function convertGenericObjectToRecipeModel($recipeArray)
    {
        foreach ($recipeArray as $recipe) {
            $recipesArray[] = [
                'id' => $recipe->id,
                'users_id' => $recipe->users_id,
                'title' => $recipe->title,
                'description' => $recipe->description,
                'cooking_time' => $recipe->cooking_time,
                'category' => $recipe->category,
                'meal_type' => $recipe->meal_type,
                'youtube_video_url' => $recipe->youtube_video_url,
                'yields' => $recipe->yields,
                'cost' => $recipe->cost,
                'complexity' => $recipe->complexity,
                'notes' => $recipe->notes,
                'created_at' => Carbon::parse($recipe->created_at),
                'updated_at' => Carbon::parse($recipe->updated_at),
            ];
        }

        return Recipes::hydrate($recipesArray) ?? null;
    }
}
