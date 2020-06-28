<?php

namespace App\Services\Interfaces;

interface RecipeInterface
{
    public function convertGenericObjectToRecipeModel($recipeArray);
    public function getRecipeCategories(): array;
    public function getRecipeMealTypes(): array;
}
