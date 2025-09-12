<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;

class EmployeesImport implements ToCollection, WithHeadingRow, WithValidation
{
    use Importable;

    private array $importResults = [
        'total_rows' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'containers_created' => 0,
        'container_errors' => 0
    ];

    private bool $updateExisting;
    private bool $createDepartments;

    public function __construct(bool $updateExisting = false, bool $createDepartments = false)
    {
        $this->updateExisting = $updateExisting;
        $this->createDepartments = $createDepartments;
    }

    public function collection(Collection $collection): void
    {
        $this->importResults['total_rows'] = $collection->count();

        DB::beginTransaction();

        try {
            foreach ($collection as $row) {
                $this->processEmployeeRow($row->toArray());
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function processEmployeeRow(array $row): void
    {
        try {
            // Skip empty rows
            if (empty($row['employee_id']) && empty($row['name'])) {
                $this->importResults['skipped']++;
                return;
            }

            // Find existing employee
            $employee = Employee::where('employee_id', $row['employee_id'])->first();

            // Handle department
            $departmentId = null;
            if (!empty($row['department'])) {
                $department = Department::where('name', $row['department'])->first();
                
                if (!$department && $this->createDepartments) {
                    $department = Department::create([
                        'name' => $row['department'],
                        'code' => strtoupper(substr($row['department'], 0, 3)),
                        'is_active' => true
                    ]);
                }
                
                $departmentId = $department?->id;
            }

            $employeeData = [
                'employee_id' => $row['employee_id'],
                'name' => $row['name'],
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'department_id' => $departmentId,
                'position' => $row['position'] ?? null,
                'hire_date' => !empty($row['hire_date']) ? \Carbon\Carbon::parse($row['hire_date']) : null,
                'status' => $row['status'] ?? 'active'
            ];

            if ($employee) {
                if ($this->updateExisting) {
                    $employee->update($employeeData);
                    $this->importResults['updated']++;
                } else {
                    $this->importResults['skipped']++;
                }
            } else {
                $newEmployee = Employee::create($employeeData);
                $this->importResults['created']++;
                
                // Container creation is handled automatically by EmployeeObserver
                // Just verify it was created successfully
                if ($newEmployee->container_status === 'active') {
                    $this->importResults['containers_created']++;
                } else {
                    $this->importResults['container_errors']++;
                    Log::warning('Container creation failed during import', [
                        'employee_id' => $newEmployee->employee_id,
                        'row' => $this->importResults['total_rows']
                    ]);
                }
            }

        } catch (\Exception $e) {
            $this->importResults['errors']++;
        }
    }

    public function rules(): array
    {
        return [
            '*.employee_id' => 'required|string|max:20',
            '*.name' => 'required|string|max:255',
            '*.email' => 'nullable|email',
            '*.status' => 'nullable|in:active,inactive'
        ];
    }

    public function getImportResults(): array
    {
        return $this->importResults;
    }
}