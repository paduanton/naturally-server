<?php

namespace App\Services\Interfaces;

use App\Recipes;

interface RecipeInterface
{
    public function getRecipeCategories(): array;
    public function getRecipeMealTypes(): array;
    public function parseRecipeData(Recipes $recipe): array;
    public function convertGenericObjectToRecipeModel($recipeArray);
}
