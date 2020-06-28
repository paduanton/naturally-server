<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentsResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'recipes_id' => $this->recipes_id,
            'parent_comments_id' => $this->parent_comments_id,
            'description' => $this->description,
            'replies' => $this->replies()->paginate(3),
            'user' => new UsersResource($this->users),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
