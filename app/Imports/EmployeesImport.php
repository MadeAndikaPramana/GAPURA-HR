<?php
// app/Imports/EmployeesImport.php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Illuminate\Support\Facades\Log;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use Importable, SkipsErrors;

    protected $updateExisting;
    protected $departmentMapping;
    protected $results = [
        'created' => 0,
        'updated' => 0,
        'errors' => 0,
        'skipped' => 0
    ];

    public function __construct($updateExisting = false, $departmentMapping = [])
    {
        $this->updateExisting = $updateExisting;
        $this->departmentMapping = $departmentMapping;
    }

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Clean and normalize the row data
            $cleanedRow = $this->cleanRowData($row);

            // Skip empty rows
            if (empty($cleanedRow['employee_id']) && empty($cleanedRow['name'])) {
                $this->results['skipped']++;
                return null;
            }

            // Resolve department
            $department = $this->resolveDepartment($cleanedRow['department'] ?? null);

            // Check if employee exists
            $employee = Employee::where('employee_id', $cleanedRow['employee_id'])->first();

            if ($employee && $this->updateExisting) {
                // Update existing employee
                $employee->update([
                    'name' => $cleanedRow['name'],
                    'department_id' => $department ? $department->id : null,
                    'position' => $cleanedRow['position'] ?? null,
                    'status' => $cleanedRow['status'] ?? 'active',
                    'hire_date' => $this->parseDate($cleanedRow['hire_date'] ?? null),
                    'background_check_date' => $this->parseDate($cleanedRow['background_check_date'] ?? null),
                    'background_check_status' => $cleanedRow['background_check_status'] ?? null,
                    'background_check_notes' => $cleanedRow['background_check_notes'] ?? null,
                ]);

                $this->results['updated']++;
                return $employee;

            } elseif (!$employee) {
                // Create new employee
                $newEmployee = Employee::create([
                    'employee_id' => $cleanedRow['employee_id'],
                    'name' => $cleanedRow['name'],
                    'department_id' => $department ? $department->id : null,
                    'position' => $cleanedRow['position'] ?? null,
                    'status' => $cleanedRow['status'] ?? 'active',
                    'hire_date' => $this->parseDate($cleanedRow['hire_date'] ?? null),
                    'background_check_date' => $this->parseDate($cleanedRow['background_check_date'] ?? null),
                    'background_check_status' => $cleanedRow['background_check_status'] ?? null,
                    'background_check_notes' => $cleanedRow['background_check_notes'] ?? null,
                ]);

                $this->results['created']++;
                return $newEmployee;

            } else {
                // Employee exists but update not allowed
                $this->results['skipped']++;
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Employee import error', [
                'row' => $row,
                'error' => $e->getMessage()
            ]);

            $this->results['errors']++;
            return null;
        }
    }

    /**
     * Clean and normalize row data
     */
    protected function cleanRowData(array $row): array
    {
        $cleaned = [];

        foreach ($row as $key => $value) {
            // Normalize keys
            $normalizedKey = $this->normalizeKey($key);
            $cleanedValue = is_string($value) ? trim($value) : $value;
            $cleanedValue = empty($cleanedValue) ? null : $cleanedValue;
            $cleaned[$normalizedKey] = $cleanedValue;
        }

        return $cleaned;
    }

    /**
     * Normalize column keys
     */
    protected function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = str_replace([' ', '-', '.'], '_', $key);

        $keyMappings = [
            'emp_id' => 'employee_id',
            'staff_id' => 'employee_id',
            'id_karyawan' => 'employee_id',
            'nama' => 'name',
            'nama_karyawan' => 'name',
            'dept' => 'department',
            'departemen' => 'department',
            'bagian' => 'department',
            'jabatan' => 'position',
            'posisi' => 'position',
            'tanggal_masuk' => 'hire_date',
            'join_date' => 'hire_date',
            'status_karyawan' => 'status',
            'background_check' => 'background_check_date',
            'bg_check_date' => 'background_check_date',
            'bg_check_status' => 'background_check_status',
            'bg_check_notes' => 'background_check_notes',
            'catatan' => 'background_check_notes',
            'keterangan' => 'background_check_notes'
        ];

        return $keyMappings[$key] ?? $key;
    }

    /**
     * Resolve department from name or code
     */
    protected function resolveDepartment(?string $departmentIdentifier): ?Department
    {
        if (empty($departmentIdentifier)) {
            return null;
        }

        // Check if there's a custom mapping
        if (isset($this->departmentMapping[$departmentIdentifier])) {
            return Department::find($this->departmentMapping[$departmentIdentifier]);
        }

        // Try to find by name first
        $department = Department::where('name', 'like', '%' . trim($departmentIdentifier) . '%')->first();

        if (!$department) {
            // Try to find by code
            $department = Department::where('code', strtoupper(trim($departmentIdentifier)))->first();
        }

        return $department;
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate(?string $dateString): ?\Carbon\Carbon
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    /**
     * Get import results
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
