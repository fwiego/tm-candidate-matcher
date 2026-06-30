<?php

namespace App\Http\Requests;

use App\Models\Candidate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isManager();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'grade' => ['nullable', 'in:'.implode(',', Candidate::GRADES)],
            'location' => ['nullable', 'string', 'max:255'],
            'skills' => ['array'],
            'skills.*' => ['integer', 'exists:technologies,id', 'distinct'],
        ];
    }
}