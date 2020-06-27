<?php

namespace App\Http\Resources;

use App\RatingsImages;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingsResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'users_id' => $this->users_id,
            'recipes_id' => $this->recipes_id,
            'made_it' => (bool) $this->made_it,
            'value' => $this->value,
            'description' => $this->description,
            'images' => RatingsImages::collection($this->images),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
