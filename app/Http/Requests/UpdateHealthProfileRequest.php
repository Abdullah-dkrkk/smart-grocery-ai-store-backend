<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHealthProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'weight' => ['nullable', 'numeric', 'min:1'],
            'goals' => ['nullable', 'string', 'max:255'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string'],
            'dietary_type' => ['nullable', 'string', 'max:255'],
        ];
    }
}
