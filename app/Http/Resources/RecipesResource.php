<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecipesResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'users_id' => $this->users_id,
            'title' => $this->title,
            'description' => $this->description,
            'cooking_time' => $this->cooking_time,
            'category' => $this->category,
            'meal_type' => $this->meal_type,
            'video_url' => $this->video_url,
            'yields' => $this->yields,
            'cost' => $this->cost,
            'complexity' => $this->complexity,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
