<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmployeesImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithBatchInserts,
    WithChunkReading,
    SkipsEmptyRows,
    SkipsErrors,
    SkipsFailures
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $updateExisting;
    protected $departmentMapping;
    protected $results = [
        'created' => 0,
        'updated' => 0,
        'errors' => 0,
        'error_details' => []
    ];

    public function __construct($updateExisting = false, $departmentMapping = [])
    {
        $this->updateExisting = $updateExisting;
        $this->departmentMapping = $departmentMapping;
    }

    /**
     * Transform each row into a model
     */
    public function model(array $row)
    {
        try {
            // Clean and validate the row data
            $cleanRow = $this->cleanRowData($row);

            // Find or resolve department
            $department = $this->resolveDepartment($cleanRow['department_code'] ?? null);

            // Prepare employee data
            $employeeData = [
                'employee_id' => $cleanRow['employee_id'],
                'name' => $cleanRow['name'],
                'email' => $cleanRow['email'] ?? null,
                'phone' => $cleanRow['phone'] ?? null,
                'department_id' => $department?->id,
                'position' => $cleanRow['position'] ?? null,
                'position_level' => $this->determinePositionLevel($cleanRow['position'] ?? ''),
                'employment_type' => $cleanRow['employment_type'] ?? 'permanent',
                'hire_date' => $this->parseDate($cleanRow['hire_date'] ?? null),
                'status' => $cleanRow['status'] ?? 'active',
                'background_check_status' => 'pending',
                'emergency_contact_name' => $cleanRow['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $cleanRow['emergency_contact_phone'] ?? null,
                'address' => $cleanRow['address'] ?? null
            ];

            // Check if employee exists
            $existingEmployee = Employee::where('employee_id', $cleanRow['employee_id'])->first();

            if ($existingEmployee) {
                if ($this->updateExisting) {
                    $existingEmployee->update($employeeData);
                    $this->results['updated']++;

                    Log::info('Employee updated during import', [
                        'employee_id' => $cleanRow['employee_id'],
                        'name' => $cleanRow['name']
                    ]);

                    return null; // Don't create a new model
                } else {
                    // Skip existing employee
                    Log::debug('Employee skipped (already exists)', [
                        'employee_id' => $cleanRow['employee_id']
                    ]);
                    return null;
                }
            }

            // Create new employee
            $this->results['created']++;

            Log::info('Employee created during import', [
                'employee_id' => $cleanRow['employee_id'],
                'name' => $cleanRow['name'],
                'department' => $department?->name
            ]);

            return new Employee($employeeData);

        } catch (\Exception $e) {
            $this->results['errors']++;
            $this->results['error_details'][] = [
                'row' => $row,
                'error' => $e->getMessage()
            ];

            Log::error('Employee import row failed', [
                'row' => $row,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Define validation rules
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!$this->updateExisting && Employee::where('employee_id', $value)->exists()) {
                        $fail('Employee ID already exists.');
                    }
                }
            ],
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'department_code' => 'nullable|string|max:10',
            'position' => 'nullable|string|max:100',
            'employment_type' => 'nullable|in:permanent,contract,internship,consultant',
            'hire_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive,terminated,on_leave'
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'employee_id.required' => 'Employee ID is required.',
            'employee_id.unique' => 'Employee ID already exists.',
            'name.required' => 'Employee name is required.',
            'email.email' => 'Invalid email format.',
            'department_code.exists' => 'Department code does not exist.',
            'employment_type.in' => 'Invalid employment type.',
            'status.in' => 'Invalid employee status.'
        ];
    }

    /**
     * Configure batch size for performance
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Configure chunk size for memory management
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Clean and normalize row data
     */
    protected function cleanRowData(array $row): array
    {
        $cleaned = [];

        foreach ($row as $key => $value) {
            // Normalize key names
            $normalizedKey = $this->normalizeKey($key);

            // Clean value
            $cleanedValue = is_string($value) ? trim($value) : $value;
            $cleanedValue = empty($cleanedValue) ? null : $cleanedValue;

            $cleaned[$normalizedKey] = $cleanedValue;
        }

        return $cleaned;
    }

    /**
     * Normalize column keys to match expected format
     */
    protected function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = str_replace([' ', '-', '.'], '_', $key);

        // Handle common variations
        $keyMappings = [
            'emp_id' => 'employee_id',
            'emp_no' => 'employee_id',
            'staff_id' => 'employee_id',
            'full_name' => 'name',
            'employee_name' => 'name',
            'dept' => 'department_code',
            'department' => 'department_code',
            'dept_code' => 'department_code',
            'job_title' => 'position',
            'role' => 'position',
            'designation' => 'position',
            'start_date' => 'hire_date',
            'join_date' => 'hire_date',
            'employment_date' => 'hire_date',
            'mobile' => 'phone',
            'phone_number' => 'phone',
            'contact' => 'phone',
            'emp_status' => 'status',
            'employee_status' => 'status',
            'emp_type' => 'employment_type',
            'type' => 'employment_type',
            'emergency_name' => 'emergency_contact_name',
            'emergency_phone' => 'emergency_contact_phone',
            'emergency_contact' => 'emergency_contact_phone'
        ];

        return $keyMappings[$key] ?? $key;
    }

    /**
     * Resolve department from code or name
     */
    protected function resolveDepartment(?string $departmentIdentifier): ?Department
    {
        if (empty($departmentIdentifier)) {
            return null;
        }

        // Check mapping first
        if (isset($this->departmentMapping[$departmentIdentifier])) {
            return Department::find($this->departmentMapping[$departmentIdentifier]);
        }

        // Try to find by code first
        $department = Department::where('code', strtoupper($departmentIdentifier))->first();

        if (!$department) {
            // Try to find by name (case insensitive)
            $department = Department::whereRaw('LOWER(name) = ?', [strtolower($departmentIdentifier)])->first();
        }

        if (!$department) {
            // Auto-create department if it doesn't exist
            $department = Department::create([
                'name' => ucwords(str_replace(['_', '-'], ' ', $departmentIdentifier)),
                'code' => strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($departmentIdentifier)), 0, 10)),
                'description' => 'Auto-created during employee import'
            ]);

            Log::info('Department auto-created during import', [
                'department_name' => $department->name,
                'department_code' => $department->code
            ]);
        }

        return $department;
    }

    /**
     * Determine position level from position title
     */
    protected function determinePositionLevel(string $position): string
    {
        $position = strtolower($position);

        if (str_contains($position, 'director') || str_contains($position, 'ceo') || str_contains($position, 'president')) {
            return 'executive';
        }

        if (str_contains($position, 'manager') || str_contains($position, 'head') || str_contains($position, 'lead')) {
            return 'manager';
        }

        if (str_contains($position, 'supervisor') || str_contains($position, 'coordinator') || str_contains($position, 'team lead')) {
            return 'supervisor';
        }

        return 'staff';
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            // Handle Excel date numbers
            if (is_numeric($dateString)) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateString);
                return $date->format('Y-m-d');
            }

            // Parse various date formats
            $formats = [
                'Y-m-d',
                'd/m/Y',
                'm/d/Y',
                'd-m-Y',
                'm-d-Y',
                'Y/m/d',
                'd.m.Y',
                'Y.m.d',
                'F j, Y',
                'j F Y'
            ];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $dateString);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }

            // Try Carbon parsing as fallback
            $date = Carbon::parse($dateString);
            return $date->format('Y-m-d');

        } catch (\Exception $e) {
            Log::warning('Failed to parse date during import', [
                'date_string' => $dateString,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Handle validation failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->results['errors']++;
            $this->results['error_details'][] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values()
            ];

            Log::warning('Employee import validation failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors()
            ]);
        }
    }

    /**
     * Handle import errors
     */
    public function onError(\Throwable $e)
    {
        $this->results['errors']++;
        $this->results['error_details'][] = [
            'error' => $e->getMessage(),
            'type' => 'general_error'
        ];

        Log::error('Employee import general error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Get import results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get summary of import results
     */
    public function getSummary(): string
    {
        return sprintf(
            'Import completed: %d created, %d updated, %d errors',
            $this->results['created'],
            $this->results['updated'],
            $this->results['errors']
        );
    }

    /**
     * Get validation errors in user-friendly format
     */
    public function getValidationErrors(): array
    {
        return collect($this->results['error_details'])
            ->filter(function ($error) {
                return isset($error['attribute']);
            })
            ->map(function ($error) {
                return [
                    'row' => $error['row'],
                    'field' => $error['attribute'],
                    'message' => implode(', ', $error['errors']),
                    'value' => $error['values'][$error['attribute']] ?? null
                ];
            })
            ->toArray();
    }

    /**
     * Prepare preview data for UI
     */
    public static function getPreviewData($file, $limit = 5): array
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                return ['headers' => [], 'data' => []];
            }

            $headers = array_shift($rows); // First row as headers
            $previewData = array_slice($rows, 0, $limit);

            return [
                'headers' => $headers,
                'data' => $previewData,
                'total_rows' => count($rows)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate preview for employee import', [
                'error' => $e->getMessage()
            ]);

            return [
                'headers' => [],
                'data' => [],
                'error' => 'Failed to read file: ' . $e->getMessage()
            ];
        }
    }
}
