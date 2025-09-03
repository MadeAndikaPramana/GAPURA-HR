<?php
// app/Http/Requests/StoreEmployeeRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Assuming all authenticated users can create employees
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'string',
                'max:20',
                Rule::unique('employees', 'employee_id'),
            ],
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:100',
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'NIP wajib diisi',
            'employee_id.unique' => 'NIP sudah terdaftar dalam sistem',
            'employee_id.max' => 'NIP maksimal 20 karakter',
            'name.required' => 'Nama karyawan wajib diisi',
            'name.max' => 'Nama maksimal 255 karakter',
            'position.required' => 'Jabatan wajib diisi',
            'position.max' => 'Jabatan maksimal 100 karakter',
            'department_id.exists' => 'Departemen tidak valid',
            'status.required' => 'Status karyawan wajib dipilih',
            'status.in' => 'Status harus Aktif atau Tidak Aktif',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'NIP',
            'name' => 'Nama',
            'position' => 'Jabatan',
            'department_id' => 'Departemen',
            'status' => 'Status',
        ];
    }
}
