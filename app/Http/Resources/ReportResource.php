<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'users_id' => $this->users_id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'who_reported' => $this->who_reported,
            'email' => $this->email,
            'created_at' =>  $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString()
        ];
    }
}
