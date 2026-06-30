<?php

namespace App\Http\Requests;

use App\Models\JobRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateJobRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $request = $this->route('job_request');

        if (! ($this->user()->isAdmin() || $this->user()->isManager())) {
            return false;
        }

        // Closed requests are locked from editing entirely.
        if ($request->status === 'closed') {
            return false;
        }

        // Only the creator (or an admin) may edit.
        return $this->user()->isAdmin() || $request->created_by === $this->user()->id;
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
            'status' => ['required', Rule::in(JobRequest::STATUSES)],

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
            $current = $this->route('job_request');
            $newStatus = $this->input('status');

            // Enforce strict forward-only status progression: draft -> open -> closed.
            $order = array_flip(JobRequest::STATUSES);

            if (isset($order[$current->status], $order[$newStatus]) && $order[$newStatus] < $order[$current->status]) {
                $validator->errors()->add('status', 'Нельзя вернуть запрос на предыдущий статус.');
            }

            if ($newStatus === 'open') {
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