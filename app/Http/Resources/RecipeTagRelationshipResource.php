<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecipeTagRelationshipResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'hashtag' => $this->hashtag,
            'relationship' => [
                "recipes_id" => $this->relationship['recipes_id'],
                "tags_id" => $this->relationship['tags_id'],
                "created_at" => $this->relationship['created_at']->toDateTimeString(),
                "updated_at" => $this->relationship['updated_at']->toDateTimeString(),
            ]
        ];
    }
}
