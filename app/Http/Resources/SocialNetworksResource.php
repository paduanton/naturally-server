<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SocialNetworksResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'users_id' => $this->users_id,
            'provider_name' => $this->provider_name,
            'provider_id' => $this->provider_id,
            'nickname' => $this->nickname,
            'profile_url' => $this->profile_url,
            'picture_url' => $this->picture_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
