<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('price')) {
            $cleaned = preg_replace('/[^0-9]/', '', (string) $this->input('price'));
            $this->merge([
                'price' => $cleaned !== '' ? (int) $cleaned : 0,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'synopsis' => ['nullable', 'string'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'rating' => ['required', 'numeric', 'min:0', 'max:10'],
            'price' => ['nullable', 'integer', 'min:0'],
            'age_limit' => ['required', 'integer', 'in:0,13,17,21'],
            'poster' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif,avif,svg'], // tanpa batas ukuran aplikasi; batas server = upload_max_filesize/post_max_size (php.ini)
            'genres' => ['nullable', 'array'],
            'genres.*' => ['integer', 'exists:genres,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
