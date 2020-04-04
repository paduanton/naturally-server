<?php

namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersResource extends JsonResource
{
    
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'birthday' => $this->birthday,
            'picture_url' => $this->picture_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
