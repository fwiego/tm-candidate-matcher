<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTechnologyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:technologies,name'],
            'group' => ['nullable', 'string', 'max:255'],
            'synonyms' => ['nullable', 'array'],
            'synonyms.*' => ['string', 'max:255', 'distinct'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'synonyms' => $this->normalizeSynonyms($this->input('synonyms')),
        ]);
    }

    /**
     * Normalize synonyms input: drop empty entries and trim whitespace.
     */
    protected function normalizeSynonyms(mixed $synonyms): array
    {
        if (! is_array($synonyms)) {
            return [];
        }

        return collect($synonyms)
            ->map(fn ($synonym) => is_string($synonym) ? trim($synonym) : $synonym)
            ->filter(fn ($synonym) => filled($synonym))
            ->values()
            ->all();
    }
}