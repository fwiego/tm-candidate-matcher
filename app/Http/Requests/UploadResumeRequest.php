<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadResumeRequest extends FormRequest
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
            'resume' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'], // 10MB
            'grade' => ['nullable', 'in:junior,middle,senior,lead'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}