<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Illuminate\Foundation\Http\FormRequest;

final class PhoneContactRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function validationData(): array
    {
        $content = $this->getContent();
        try { $data = json_decode($content === '' ? '{}' : $content, true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { abort(400); }
        return ['contact' => $data];
    }

    public function rules(): array
    {
        if ($this->isMethod('DELETE')) { return ['contact' => 'present|array|size:0']; }
        if ($this->isMethod('PATCH')) {
            return ['contact' => 'required|array:label,main', 'contact.label' => 'sometimes|required|string|min:4|max:15', 'contact.main' => 'sometimes|required|boolean:strict'];
        }
        return [
            'contact' => 'required|array:label,country_code,number,main',
            'contact.label' => 'required|string|min:4|max:15',
            'contact.country_code' => 'required|string|size:2',
            'contact.number' => 'required|string|min:3|max:16',
            'contact.main' => 'required|boolean:strict',
        ];
    }
}
