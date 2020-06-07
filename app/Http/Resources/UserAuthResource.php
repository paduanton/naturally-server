<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAuthResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'birthday' => $this->birthday,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'auth_resource' => [
                "token_type" => $this->auth_resource['token_type'],
                "expires_in" => $this->auth_resource['expires_in']->toDateTimeString(),
                "access_token" => $this->auth_resource['access_token'],
                "created_at" => $this->auth_resource['created_at']->toDateTimeString(),
                "refresh_token" => $this->auth_resource['refresh_token'],
                "remember_token" => $this->auth_resource['remember_token']
            ]
        ];
    }
}
