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

    public function parseRecipeData(Recipes $recipe): array
    {
        $recipeData = [
            'author' => [
                'name' => $recipe->users->name,
                'email' => $recipe->users->email,
                'username' => $recipe->users->username
            ],
            'title' => $recipe->title,
            'description' => $recipe->description,
            'category' => $recipe->category,
            'cookingTime' => $recipe->cooking_time,
            'mealType' => $recipe->meal_type,
            'youtubeVideoURL' => $recipe->youtube_video_url,
            'yields' => $recipe->yields,
            'cost' => $recipe->cost,
            'complexity' => $recipe->complexity,
            'notes' => $recipe->notes,
            'createdAt' => $recipe->created_at,
            'tags' => $recipe->tags,
            'ingredients' => $recipe->ingredients,
            'instructions' => $recipe->instructions,
            'PDFDownloads' => $recipe->pdf_downloads,
        ];

        return $recipeData;
    }

    public function getRecipeCategories(): array
    {
        return [
            'diabetic', 'low-carb', 'heart-healthy', 'low-calorie', 'low-cholesterol', 'low-fat', 'weight-loss', 'high-fiber', 'keto',
            'gluten-free', 'healthy', 'vegan', 'vegetarian', 'fitness', 'dairy-free', 'healthy-fried', 'vegetable', 'quick-&-Easy', 'other',
        ];
    }

    public function getRecipeMealTypes(): array
    {
        return ['breakfast', 'brunch', 'dessert', 'dinner', 'lunch', 'other'];
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
