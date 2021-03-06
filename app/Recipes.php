<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use eloquentFilter\QueryFilter\ModelFilters\Filterable;

class Recipes extends Model
{
    use SoftDeletes, Filterable, SoftCascadeTrait;

    protected $table = 'recipes';

    protected $fillable = [
        'users_id', 'title', 'description', 'cooking_time', 'category',
        'meal_type', 'youtube_video_url', 'yields', 'cost', 'complexity', 'notes'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    private static $whiteListFilter = [
        'cooking_time', 'meal_type', 'category', 'yields', 'cost', 'complexity'
    ];

    protected $softCascade = [
        'images', 'ingredients', 'comments', 'instructions',
        'likes', 'favorites', 'ratings', 'recipes_tags'
    ];

    /*
    * Model relationships
    **/

    public function users()
    {
        return $this->belongsTo(Users::class);
    }

    public function images()
    {
        return $this->hasMany(RecipesImages::class);
    }

    public function ingredients()
    {
        return $this->hasMany(Ingredients::class);
    }

    public function instructions()
    {
        return $this->hasMany(Instructions::class);
    }

    public function comments()
    {
        return $this->hasMany(Comments::class);
    }

    public function likes()
    {
        return $this->hasMany(Likes::class);
    }

    public function favorites()
    {
        return $this->hasMany(UsersFavoriteRecipes::class);
    }

    public function ratings()
    {
        return $this->hasMany(Ratings::class);
    }

    public function recipes_ratings_images()
    {
        return $this->hasManyThrough(RatingsImages::class, Ratings::class);
    }

    public function recipes_tags()
    {
        return $this->hasMany(RecipesTags::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tags::class)->withPivot('created_at', 'updated_at')->wherePivot('deleted_at', null)->as('relationship');
    }

    public function pdf_downloads()
    {
        return $this->hasMany(PDFDownloads::class);
    }

    /*
    *   Model queries
    **/

    public function getRecipeInstructionsOrderByOrder($orderBy = "asc")
    {
        Recipes::findOrFail($this->getKey());
        $recipeInstructions = Instructions::where('recipes_id', $this->getKey())->orderBy('order', $orderBy)->get();

        return $recipeInstructions;
    }
}
