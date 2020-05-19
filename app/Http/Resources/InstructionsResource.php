<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InstructionsResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'recipes_id' => $this->recipes_id,
            'order' => $this->order,
            'description' => $this->description,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString()
        ];
    }
}
