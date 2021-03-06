<?php

namespace App\Http\Resources;

use App\Comments;
use App\PDFDownloads;
use Illuminate\Http\Resources\Json\JsonResource;

class FullRecipeResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'users_id' => (int) $this->users_id,
            'title' => $this->title,
            'description' => $this->description,
            'cooking_time' => $this->cooking_time,
            'category' => $this->category,
            'meal_type' => $this->meal_type,
            'youtube_video_url' => $this->youtube_video_url,
            'yields' => $this->yields,
            'cost' => $this->cost,
            'complexity' => $this->complexity,
            'notes' => $this->notes,
            'pdf_downloads' => PDFDownloadsResource::collection($this->pdf_downloads),
            'images' => RecipesImagesResource::collection($this->images),
            'tags' => TagResource::collection($this->tags),
            'ingredients' => IngredientsResource::collection($this->ingredients),
            'instructions' => InstructionsResource::collection($this->getRecipeInstructionsOrderByOrder()),
            'comments' => CommentsResource::collection($this->comments),
            'likes' => RecipesLikesResource::collection($this->likes),
            'favorites' => FavoritesRecipesResource::collection($this->favorites),
            'ratings' => RatingsResource::collection($this->ratings),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString()
        ];
    }
}
