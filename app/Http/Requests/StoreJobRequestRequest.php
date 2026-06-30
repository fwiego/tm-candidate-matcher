<?php

namespace App\Http\Requests;

use App\Models\JobRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreJobRequestRequest extends FormRequest
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
            'position' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'grade' => ['required', Rule::in(JobRequest::GRADES)],
            'location' => ['nullable', 'string', 'max:255'],
            'citizenship' => ['nullable', 'string', 'max:255'],
            'needed_by' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'open'])],

            'requirements' => ['array'],
            'requirements.*.technology_id' => ['required', 'integer', 'exists:technologies,id', 'distinct'],
            'requirements.*.type' => ['required', Rule::in(['must', 'nice'])],
            'requirements.*.weight' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('status') === 'open') {
                $requirements = collect($this->input('requirements', []));
                $hasMust = $requirements->contains(fn ($r) => ($r['type'] ?? null) === 'must');

                if (! $hasMust) {
                    $validator->errors()->add(
                        'requirements',
                        'Для публикации запроса (статус "Открыт") нужно как минимум одно обязательное (must) требование.'
                    );
                }
            }
        });
    }
}