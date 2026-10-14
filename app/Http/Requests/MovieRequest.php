<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'synopsis' => ['nullable', 'string'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'rating' => ['required', 'numeric', 'min:0', 'max:10'],
            'age_limit' => ['required', 'integer', 'in:0,13,17,21'],
            'poster' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['integer', 'exists:genres,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
