<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmployeesImport implements  // ✅ FIXED: Nama class yang benar
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
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

    private function cleanRowData(array $row): array
    {
        return [
            'employee_id' => trim(strtoupper($row['employee_id'] ?? $row['nip'] ?? '')),
            'name' => trim($row['name'] ?? $row['nama'] ?? ''),
            'email' => trim(strtolower($row['email'] ?? '')),
            'phone' => trim($row['phone'] ?? $row['telepon'] ?? ''),
            'department' => trim(strtoupper($row['department'] ?? $row['dept'] ?? $row['departemen'] ?? '')),
            'position' => trim($row['position'] ?? $row['jabatan'] ?? ''),
            'hire_date' => $row['hire_date'] ?? $row['tanggal_masuk'] ?? null,
            'status' => trim(strtolower($row['status'] ?? 'active'))
        ];
    }

    private function resolveDepartment($departmentCode)
    {
        if (empty($departmentCode)) {
            return null;
        }

        return $this->departments->get(strtoupper($departmentCode));
    }

    private function createEmployee(array $cleanRow, $department): Employee
    {
        return Employee::create([
            'employee_id' => $cleanRow['employee_id'],
            'name' => $cleanRow['name'],
            'email' => $cleanRow['email'] ?: null,
            'phone' => $cleanRow['phone'] ?: null,
            'department_id' => $department?->id,
            'position' => $cleanRow['position'] ?: 'Staff',
            'hire_date' => $cleanRow['hire_date'] ? Carbon::parse($cleanRow['hire_date']) : now(),
            'status' => in_array($cleanRow['status'], ['active', 'inactive']) ? $cleanRow['status'] : 'active',
            'background_check_date' => now(),
            'background_check_status' => 'completed',
            'background_check_notes' => 'Imported via Excel'
        ]);
    }

    private function updateEmployee($employee, array $cleanRow, $department): void
    {
        $employee->update([
            'name' => $cleanRow['name'],
            'email' => $cleanRow['email'] ?: $employee->email,
            'phone' => $cleanRow['phone'] ?: $employee->phone,
            'department_id' => $department?->id ?: $employee->department_id,
            'position' => $cleanRow['position'] ?: $employee->position,
            'status' => in_array($cleanRow['status'], ['active', 'inactive']) ? $cleanRow['status'] : $employee->status,
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:10',
            'position' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive'
        ];
    }

    public function getResults(): array
    {
        $this->importResults['processing_time'] = round(microtime(true) - $this->startTime, 2);
        return $this->importResults;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Handle errors during import (required by SkipsOnError).
     */
    public function onError(\Throwable $e)
    {
        $this->importResults['errors']++;
        $this->importResults['error_details'][] = [
            'row' => $this->importResults['total_rows'],
            'employee_id' => 'N/A',
            'error' => $e->getMessage()
        ];
        Log::error('Employee import error (onError)', [
            'row' => $this->importResults['total_rows'],
            'error' => $e->getMessage()
        ]);
    }

    /**
     * Handle validation failures (required by SkipsOnFailure).
     */
    public function onFailure(...$failures)
    {
        foreach ($failures as $failure) {
            $this->importResults['errors']++;
            $this->importResults['error_details'][] = [
                'row' => $failure->row() ?? 'N/A',
                'employee_id' => $failure->values()['employee_id'] ?? 'N/A',
                'error' => implode('; ', $failure->errors())
            ];
            Log::error('Employee import validation failure', [
                'row' => $failure->row(),
                'employee_id' => $failure->values()['employee_id'] ?? 'N/A',
                'error' => implode('; ', $failure->errors())
            ]);
        }
    }
}

