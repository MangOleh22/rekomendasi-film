<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class GenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** Slug otomatis dari nama dibuat sebelum validasi agar aturan `unique` ikut memeriksanya (422, bukan 500). */
    protected function prepareForValidation(): void
    {
        if (blank($this->slug) && is_string($this->name)) {
            $this->merge(['slug' => Str::slug($this->name)]);
        }
    }

    public function rules(): array
    {
        $genreId = $this->route('genre')?->id ?? $this->route('genre');

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:genres,slug,' . $genreId],
        ];
    }
}
