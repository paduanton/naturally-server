<?php

declare(strict_types=1);

namespace App\Modules\Recipes\Domain;

enum MealType: string
{
    case Breakfast = 'breakfast';
    case Brunch = 'brunch';
    case Dessert = 'dessert';
    case Dinner = 'dinner';
    case Lunch = 'lunch';
    case Other = 'other';
}
