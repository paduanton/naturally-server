<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersRatingsResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'users_id' => $this->users_id,
            'made_it' => (boolean) $this->made_it,
            'value' => $this->value,
            'description' => $this->description,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'recipe' => $this->recipes
        ];
    }
}
