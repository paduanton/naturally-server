<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http;

use Illuminate\Foundation\Http\FormRequest;

final class ListSocialAccountsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function validationData(): array
    {
        return ['pagination' => $this->query->all()];
    }

    public function rules(): array
    {
        return [
            'pagination' => 'present|array:page,per_page',
            'pagination.page' => 'sometimes|required|integer|min:1|max:1000000',
            'pagination.per_page' => 'sometimes|required|integer|min:1|max:100',
        ];
    }
}
