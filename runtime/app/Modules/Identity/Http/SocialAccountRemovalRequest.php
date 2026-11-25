<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Illuminate\Foundation\Http\FormRequest;

final class SocialAccountRemovalRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function validationData(): array
    {
        $content = $this->getContent();
        try {
            $data = json_decode($content === '' ? '{}' : $content, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            abort(400);
        }

        return ['account' => $data];
    }

    public function rules(): array
    {
        return ['account' => 'present|array|size:0'];
    }
}
