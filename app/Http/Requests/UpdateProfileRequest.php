<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'required', 'date', 'before:today', 'after:1900-01-01'],
            'minimum_rating' => ['sometimes', 'required', 'numeric', 'min:0', 'max:10'],
        ];
    }
}
