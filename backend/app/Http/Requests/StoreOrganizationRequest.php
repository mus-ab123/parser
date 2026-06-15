<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'url',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/\/org\/([^\/]+\/)?(\d+)/', $value)) {
                        $fail('Ссылка должна вести на карточку организации Яндекс.Карт (/org/.../id)');
                    }
                }
            ],
        ];
    }
}
