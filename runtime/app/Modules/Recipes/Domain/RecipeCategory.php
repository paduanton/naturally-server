<?php

declare(strict_types=1);

namespace App\Modules\Recipes\Domain;

enum RecipeCategory: string
{
    case Diabetic = 'diabetic';
    case LowCarb = 'low-carb';
    case HeartHealthy = 'heart-healthy';
    case LowCalorie = 'low-calorie';
    case LowCholesterol = 'low-cholesterol';
    case LowFat = 'low-fat';
    case WeightLoss = 'weight-loss';
    case HighFiber = 'high-fiber';
    case Keto = 'keto';
    case GlutenFree = 'gluten-free';
    case Healthy = 'healthy';
    case Vegan = 'vegan';
    case Vegetarian = 'vegetarian';
    case Fitness = 'fitness';
    case DairyFree = 'dairy-free';
    case HealthyFried = 'healthy-fried';
    case Vegetable = 'vegetable';
    case QuickAndEasy = 'quick-and-easy';
    case Other = 'other';
}
