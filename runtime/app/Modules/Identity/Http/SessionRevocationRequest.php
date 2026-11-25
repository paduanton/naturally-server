<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Illuminate\Foundation\Http\FormRequest;

final class SessionRevocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function validationData(): array
    {
        $content = $this->getContent();
        try { $data = json_decode($content === '' ? '{}' : $content, true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { abort(400); }
        return ['revocation' => $data];
    }
    public function rules(): array { return ['revocation' => 'present|array|size:0']; }
}
