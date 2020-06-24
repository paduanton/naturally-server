<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecipesTagsResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tags_id' => $this->tags_id,
            'recipes_id' => $this->recipes_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString()
        ];
    }
}
