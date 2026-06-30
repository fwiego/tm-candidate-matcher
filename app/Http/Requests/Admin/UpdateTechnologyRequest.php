<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateTechnologyRequest extends StoreTechnologyRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $technologyId = $this->route('technology')->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('technologies', 'name')->ignore($technologyId)],
            'group' => ['nullable', 'string', 'max:255'],
            'synonyms' => ['nullable', 'array'],
            'synonyms.*' => ['string', 'max:255', 'distinct'],
        ];
    }
}