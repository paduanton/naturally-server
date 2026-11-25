<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use App\Modules\Identity\Application\PhoneContactData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PhoneContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PhoneContactData $contact */
        $contact = $this->resource;
        return ['id' => $contact->id, 'label' => $contact->label, 'country_code' => $contact->countryCode, 'number' => $contact->number, 'main' => $contact->main];
    }
}
