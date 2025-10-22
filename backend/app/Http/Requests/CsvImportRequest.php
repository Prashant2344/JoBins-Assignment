<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CsvImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'CSV file is required',
            'csv_file.file' => 'The uploaded file must be a valid file',
            'csv_file.mimes' => 'The file must be a CSV file',
            'csv_file.max' => 'The file size must not exceed 10MB',
        ];
    }
}
