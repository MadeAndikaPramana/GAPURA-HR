<?php
// app/Imports/EnhancedEmployeeImport.php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class EnhancedEmployeeImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
    WithBatchInserts,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable;

    private $importResults = [
        'total_rows' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'error_details' => [],
        'processing_time' => 0
    ];

    private $departments;
    private $updateExisting;
    private $startTime;

    public function __construct(bool $updateExisting = false)
    {
        $this->updateExisting = $updateExisting;
        $this->departments = Department::all()->keyBy('code');
        $this->startTime = microtime(true);
    }

    /**
     * Enhanced model creation with business logic
     */
    public function model(array $row)
    {
        $this->importResults['total_rows']++;

        try {
            // Clean and normalize data
            $cleanRow = $this->cleanRowData($row);

            // Validate required fields
            if (empty($cleanRow['employee_id']) || empty($cleanRow['name'])) {
                $this->importResults['skipped']++;
                return null;
            }

            // Resolve department
            $department = $this->resolveDepartment($cleanRow['department']);

            // Check if employee exists
            $existingEmployee = Employee::where('employee_id', $cleanRow['employee_id'])->first();

            if ($existingEmployee) {
                if ($this->updateExisting) {
                    $this->updateEmployee($existingEmployee, $cleanRow, $department);
                    $this->importResults['updated']++;
                    return null;
                } else {
                    $this->importResults['skipped']++;
                    return null;
                }
            }

            // Create new employee
            $employee = $this->createEmployee($cleanRow, $department);
            $this->importResults['created']++;

            return $employee;

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->importResults['error_details'][] = [
                'row' => $this->importResults['total_rows'],
                'employee_id' => $row['employee_id'] ?? 'N/A',
                'error' => $e->getMessage()
            ];

            Log::error('Employee import error', [
                'row' => $this->importResults['total_rows'],
                'data' => $row,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Enhanced validation rules
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'string',
                'max:20',
                // Skip unique validation if updating existing
                $this->updateExisting ? '' : 'unique:employees,employee_id'
            ],
            'name' => 'required|string|max:255',
            'department' => 'nullable|string|max:10',
            'position' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
            'hire_date' => 'nullable|date',
            'background_check_date' => 'nullable|date',
            'background_check_notes' => 'nullable|string|max:500'
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'employee_id.required' => 'Employee ID is required',
            'employee_id.unique' => 'Employee ID already exists',
            'name.required' => 'Employee name is required',
            'department.exists' => 'Department code not found',
            'status.in' => 'Status must be active or inactive'
        ];
    }

    /**
     * Handle validation failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->importResults['errors']++;
            $this->importResults['error_details'][] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values()
            ];
        }
    }

    /**
     * Handle processing errors
     */
    public function onError(\Throwable $e)
    {
        $this->importResults['errors']++;
        Log::error('Import processing error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Chunk size for large files
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Batch insert size
     */
    public function batchSize(): int
    {
        return 50;
    }

    /**
     * Get import results
     */
    public function getResults(): array
    {
        $this->importResults['processing_time'] = round(microtime(true) - $this->startTime, 2);
        return $this->importResults;
    }

    /**
     * Clean and normalize row data
     */
    private function cleanRowData(array $row): array
    {
        return [
            'employee_id' => trim(strtoupper($row['employee_id'] ?? '')),
            'name' => trim($row['name'] ?? ''),
            'department' => trim(strtoupper($row['department'] ?? $row['department_code'] ?? '')),
            'position' => trim($row['position'] ?? $row['job_title'] ?? ''),
            'status' => strtolower(trim($row['status'] ?? 'active')),
            'hire_date' => $this->parseDate($row['hire_date'] ?? $row['join_date'] ?? null),
            'background_check_date' => $this->parseDate($row['background_check_date'] ?? null),
            'background_check_notes' => trim($row['background_check_notes'] ?? '')
        ];
    }

    /**
     * Resolve department by code or name
     */
    private function resolveDepartment(?string $departmentIdentifier): ?Department
    {
        if (empty($departmentIdentifier)) {
            return null;
        }

        // Try by code first
        if (isset($this->departments[$departmentIdentifier])) {
            return $this->departments[$departmentIdentifier];
        }

        // Try by name
        return $this->departments->first(function($dept) use ($departmentIdentifier) {
            return strcasecmp($dept->name, $departmentIdentifier) === 0;
        });
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateValue): ?Carbon
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            // Handle Excel date formats
            if (is_numeric($dateValue)) {
                return Carbon::createFromFormat('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue)->format('Y-m-d'));
            }

            // Handle string dates
            return Carbon::parse($dateValue);
        } catch (\Exception $e) {
            Log::warning('Date parsing failed', ['value' => $dateValue, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create new employee
     */
    private function createEmployee(array $cleanRow, ?Department $department): Employee
    {
        return Employee::create([
            'employee_id' => $cleanRow['employee_id'],
            'name' => $cleanRow['name'],
            'department_id' => $department?->id,
            'position' => $cleanRow['position'] ?: null,
            'status' => $cleanRow['status'] ?: 'active',
            'hire_date' => $cleanRow['hire_date'],
            'background_check_date' => $cleanRow['background_check_date'],
            'background_check_notes' => $cleanRow['background_check_notes'] ?: null,
        ]);
    }

    /**
     * Update existing employee
     */
    private function updateEmployee(Employee $employee, array $cleanRow, ?Department $department): void
    {
        $updateData = [
            'name' => $cleanRow['name'],
            'department_id' => $department?->id,
        ];

        // Only update non-empty values
        if (!empty($cleanRow['position'])) {
            $updateData['position'] = $cleanRow['position'];
        }

        if (!empty($cleanRow['status'])) {
            $updateData['status'] = $cleanRow['status'];
        }

        if ($cleanRow['hire_date']) {
            $updateData['hire_date'] = $cleanRow['hire_date'];
        }

        if ($cleanRow['background_check_date']) {
            $updateData['background_check_date'] = $cleanRow['background_check_date'];
        }

        if (!empty($cleanRow['background_check_notes'])) {
            $updateData['background_check_notes'] = $cleanRow['background_check_notes'];
        }

        $employee->update($updateData);
    }
}
