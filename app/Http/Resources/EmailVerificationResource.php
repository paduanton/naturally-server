<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmailVerificationResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'token' => $this->token,
            'signature' => $this->signature,
            'done' => (boolean) $this->done,
            'expires_at' => $this->expires_at->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
