<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecipesResource extends JsonResource
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
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString()
        ];
    }
}
