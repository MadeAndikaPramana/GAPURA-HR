<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeSyncImport extends BaseExcelImport
{
    private array $processedEmployeeIds = [];
    private array $departmentCache = [];
    private bool $softDelete;
    private string $syncMode;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->softDelete = $options['soft_delete'] ?? true;
        $this->syncMode = $options['sync_mode'] ?? 'replace'; // 'replace', 'merge', 'update_only'
    }

    protected function getColumnMapping(): array
    {
        return [
            'nip' => 'nip',
            'employee_id' => 'nip',
            'nama_lengkap' => 'name',
            'nama' => 'name',
            'name' => 'name',
            'department' => 'department',
            'departemen' => 'department',
            'jabatan' => 'position',
            'position' => 'position',
            'posisi' => 'position',
            'email' => 'email',
            'no_hp' => 'phone',
            'phone' => 'phone',
            'telepon' => 'phone',
            'tanggal_masuk' => 'hire_date',
            'hire_date' => 'hire_date',
            'tanggal_lahir' => 'birth_date',
            'birth_date' => 'birth_date',
            'tempat_lahir' => 'birth_place',
            'birth_place' => 'birth_place',
            'jenis_kelamin' => 'gender',
            'gender' => 'gender',
            'alamat' => 'address',
            'address' => 'address',
            'status_pegawai' => 'employee_status',
            'employee_status' => 'employee_status',
            'employment_status' => 'employee_status',
            'status_aktif' => 'status',
            'is_active' => 'status',
            'active' => 'status',
            'aktif' => 'status',
            'pendidikan' => 'education',
            'education' => 'education',
            'status_pernikahan' => 'marital_status',
            'marital_status' => 'marital_status',
            'agama' => 'religion',
            'religion' => 'religion',
            'kewarganegaraan' => 'nationality',
            'nationality' => 'nationality',
            'nik' => 'id_number',
            'id_number' => 'id_number',
            'gaji' => 'salary',
            'salary' => 'salary',
            'catatan' => 'notes',
            'notes' => 'notes'
        ];
    }

    protected function getRequiredFields(): array
    {
        return ['nip', 'name'];
    }

    protected function getRequiredFieldsValidation(): array
    {
        return [
            'nip' => 'required|string|max:50',
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Process the synchronization import
     */
    protected function processRealImport(Collection $collection): void
    {
        DB::beginTransaction();

        try {
            // Phase 1: Process uploaded employees
            foreach ($collection as $rowIndex => $row) {
                $this->processRow($row->toArray(), $rowIndex + 2);
            }

            // Phase 2: Handle employees not in the upload (synchronization)
            $this->syncMissingEmployees();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            $this->logImportError($e);
            throw $e;
        }
    }

    protected function processRow(array $rowData, int $rowNumber): void
    {
        try {
            $validation = $this->validateRow($rowData, $rowNumber);

            if ($validation['status'] === 'skipped') {
                return;
            }

            if ($validation['status'] === 'error') {
                return;
            }

            $normalizedData = $validation['data'];

            // Track processed employee IDs
            $this->processedEmployeeIds[] = $normalizedData['nip'];

            // Find existing employee
            $existingEmployee = $this->findExistingEmployee($normalizedData);

            if ($existingEmployee) {
                $this->updateEmployee($existingEmployee, $normalizedData, $rowNumber);
            } else {
                $this->createEmployee($normalizedData, $rowNumber);
            }

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, $e->getMessage(), $rowData);
        }
    }

    protected function findExistingEmployee(array $data): ?Employee
    {
        return Employee::where('employee_id', $data['nip'])
                      ->orWhere('nip', $data['nip'])
                      ->first();
    }

    protected function createEmployee(array $data, int $rowNumber): void
    {
        try {
            $department = null;
            if (!empty($data['department'])) {
                $department = $this->findOrCreateDepartment($data['department']);
            }

            $employeeData = [
                'employee_id' => $data['nip'],
                'nip' => $data['nip'],
                'name' => $data['name'],
                'department_id' => $department?->id,
                'position' => $data['position'] ?? null,
                'email' => $this->validateEmail($data['email'] ?? null),
                'phone' => $data['phone'] ?? null,
                'hire_date' => $this->parseDate($data['hire_date'] ?? null),
                'status' => $this->parseActiveStatus($data['status'] ?? 'active'),
                'created_at' => now(),
                'updated_at' => now()
            ];

            $employee = Employee::create($employeeData);

            $this->importResults['processed']++;
            $this->importResults['created']++;

            $this->importResults['details']['created_items'][] = [
                'row' => $rowNumber,
                'employee_id' => $employee->id,
                'nip' => $employee->employee_id,
                'name' => $employee->name,
                'department' => $department?->name,
                'action' => 'created'
            ];

            $this->logSuccess($rowNumber, 'created', [
                'employee_id' => $employee->id,
                'nip' => $employee->employee_id,
                'name' => $employee->name
            ]);

            // Auto-create employee container if enabled
            $this->createEmployeeContainer($employee->id);

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, "Failed to create employee: {$e->getMessage()}", $data);
        }
    }

    protected function updateEmployee(Employee $employee, array $data, int $rowNumber): void
    {
        try {
            $updatedFields = [];
            $department = null;

            if (!empty($data['department'])) {
                $department = $this->findOrCreateDepartment($data['department']);
            }

            // Update fields if they have values and are different (only use existing columns)
            $fieldsToUpdate = [
                'name' => $data['name'],
                'department_id' => $department?->id ?? $employee->department_id,
                'position' => $data['position'] ?? $employee->position,
                'email' => $this->validateEmail($data['email'] ?? null) ?? $employee->email,
                'phone' => $data['phone'] ?? $employee->phone,
                'hire_date' => $this->parseDate($data['hire_date'] ?? null) ?? $employee->hire_date,
                'status' => $this->parseActiveStatus($data['status'] ?? $employee->status)
            ];

            foreach ($fieldsToUpdate as $field => $value) {
                if ($value !== null && $employee->$field != $value) {
                    $updatedFields[$field] = [
                        'old' => $employee->$field,
                        'new' => $value
                    ];
                    $employee->$field = $value;
                }
            }

            // Ensure employee is marked as active if in upload (reactivation)
            if ($employee->status !== 'active') {
                $updatedFields['status'] = [
                    'old' => $employee->status,
                    'new' => 'active'
                ];
                $employee->status = 'active';
            }

            if (!empty($updatedFields)) {
                $employee->updated_at = now();
                $employee->save();

                $this->importResults['processed']++;
                $this->importResults['updated']++;

                $this->importResults['details']['updated_items'][] = [
                    'row' => $rowNumber,
                    'employee_id' => $employee->id,
                    'nip' => $employee->employee_id,
                    'name' => $employee->name,
                    'updated_fields' => $updatedFields,
                    'action' => 'updated'
                ];

                $this->logSuccess($rowNumber, 'updated', [
                    'employee_id' => $employee->id,
                    'nip' => $employee->employee_id,
                    'name' => $employee->name,
                    'changes' => array_keys($updatedFields)
                ]);
            } else {
                $this->importResults['skipped']++;
                $this->importResults['details']['skipped'][] = [
                    'row' => $rowNumber,
                    'reason' => 'No changes detected',
                    'nip' => $employee->employee_id,
                    'name' => $employee->name,
                    'action' => 'no_change'
                ];
            }

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, "Failed to update employee: {$e->getMessage()}", $data);
        }
    }

    /**
     * Handle employees not present in the upload (synchronization logic)
     */
    protected function syncMissingEmployees(): void
    {
        if ($this->syncMode === 'update_only') {
            // Don't sync missing employees in update-only mode
            return;
        }

        try {
            // Find employees not in the uploaded data
            $missingEmployees = Employee::whereNotIn('employee_id', $this->processedEmployeeIds)
                                      ->whereNotIn('nip', $this->processedEmployeeIds)
                                      ->where('status', 'active')
                                      ->get();

            foreach ($missingEmployees as $employee) {
                if ($this->softDelete) {
                    // Soft delete: mark as inactive
                    $employee->update([
                        'status' => 'inactive',
                        'updated_at' => now()
                    ]);

                    $this->importResults['details']['updated_items'][] = [
                        'row' => 'sync',
                        'employee_id' => $employee->id,
                        'nip' => $employee->employee_id,
                        'name' => $employee->name,
                        'action' => 'deactivated',
                        'reason' => 'Not present in upload'
                    ];

                    $this->logInfo("Deactivated employee not in upload: {$employee->name} ({$employee->employee_id})");
                } else {
                    // Hard delete: remove from database
                    $employeeData = [
                        'employee_id' => $employee->id,
                        'nip' => $employee->employee_id,
                        'name' => $employee->name
                    ];

                    $employee->delete();

                    $this->importResults['details']['updated_items'][] = [
                        'row' => 'sync',
                        'employee_id' => $employeeData['employee_id'],
                        'nip' => $employeeData['nip'],
                        'name' => $employeeData['name'],
                        'action' => 'deleted',
                        'reason' => 'Not present in upload'
                    ];

                    $this->logInfo("Deleted employee not in upload: {$employeeData['name']} ({$employeeData['nip']})");
                }

                $this->importResults['processed']++;
                $this->importResults['updated']++;
            }

            $this->importResults['details']['sync_summary'] = [
                'missing_employees_count' => $missingEmployees->count(),
                'action_taken' => $this->softDelete ? 'deactivated' : 'deleted',
                'sync_mode' => $this->syncMode
            ];

        } catch (\Exception $e) {
            $this->logRowError('sync', "Failed to sync missing employees: {$e->getMessage()}");
        }
    }

    protected function findOrCreateDepartment(string $departmentName): ?Department
    {
        $normalizedName = trim($departmentName);

        if (empty($normalizedName)) {
            return null;
        }

        if (isset($this->departmentCache[$normalizedName])) {
            return $this->departmentCache[$normalizedName];
        }

        $department = parent::findOrCreateDepartment($normalizedName);
        $this->departmentCache[$normalizedName] = $department;

        return $department;
    }

    protected function validateEmail(?string $email): ?string
    {
        if (empty($email)) {
            return null;
        }

        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->logWarning("Invalid email format: {$email}");
            return null;
        }

        return $email;
    }

    protected function normalizeGender(?string $gender): ?string
    {
        if (empty($gender)) {
            return null;
        }

        $gender = strtolower(trim($gender));

        $maleVariants = ['male', 'laki-laki', 'l', 'm', 'pria', 'man'];
        $femaleVariants = ['female', 'perempuan', 'p', 'f', 'wanita', 'woman'];

        if (in_array($gender, $maleVariants)) {
            return 'male';
        }

        if (in_array($gender, $femaleVariants)) {
            return 'female';
        }

        return null;
    }

    protected function parseActiveStatus($value): string
    {
        if (is_string($value)) {
            $value = strtolower(trim($value));
            $activeValues = ['true', 'yes', 'ya', '1', 'active', 'aktif'];
            $inactiveValues = ['false', 'no', 'tidak', '0', 'inactive', 'nonaktif'];

            if (in_array($value, $activeValues)) {
                return 'active';
            }

            if (in_array($value, $inactiveValues)) {
                return 'inactive';
            }
        }

        if (is_bool($value)) {
            return $value ? 'active' : 'inactive';
        }

        if (is_numeric($value)) {
            return $value ? 'active' : 'inactive';
        }

        return 'active'; // Default to active
    }

    public function rules(): array
    {
        return [
            '*.nip' => 'required|string|max:50',
            '*.nama_lengkap' => 'required|string|max:255',
            '*.nama' => 'required|string|max:255',
            '*.name' => 'required|string|max:255',
            '*.email' => 'sometimes|email|max:255',
            '*.phone' => 'sometimes|string|max:20',
            '*.department' => 'sometimes|string|max:255',
            '*.position' => 'sometimes|string|max:255',
            '*.hire_date' => 'sometimes|date',
            '*.birth_date' => 'sometimes|date',
            '*.gender' => 'sometimes|string|in:male,female,laki-laki,perempuan,l,p,m,f',
            '*.employee_status' => 'sometimes|string|in:permanent,contract,intern,temporary',
            '*.status' => 'sometimes|string|in:active,inactive',
            '*.is_active' => 'sometimes|boolean',
            '*.salary' => 'sometimes|numeric|min:0'
        ];
    }

    /**
     * Generate detailed synchronization report
     */
    public function generateReport(): string
    {
        $baseReport = parent::generateReport();
        $results = $this->importResults;

        $report = $baseReport;

        // Synchronization-specific statistics
        $report .= "Synchronization Details:\n";
        $report .= "- Sync Mode: {$this->syncMode}\n";
        $report .= "- Soft Delete: " . ($this->softDelete ? 'Yes' : 'No') . "\n";
        $report .= "- Processed Employee IDs: " . count($this->processedEmployeeIds) . "\n";

        if (isset($results['details']['sync_summary'])) {
            $syncSummary = $results['details']['sync_summary'];
            $report .= "- Missing Employees: {$syncSummary['missing_employees_count']}\n";
            $report .= "- Action Taken: {$syncSummary['action_taken']}\n";
        }

        $report .= "\n";

        // Actions breakdown
        $actions = ['created' => 0, 'updated' => 0, 'deactivated' => 0, 'deleted' => 0, 'no_change' => 0];

        foreach (array_merge($results['details']['created_items'], $results['details']['updated_items']) as $item) {
            $action = $item['action'] ?? 'unknown';
            if (isset($actions[$action])) {
                $actions[$action]++;
            }
        }

        $report .= "Actions Summary:\n";
        foreach ($actions as $action => $count) {
            $report .= "- " . ucfirst(str_replace('_', ' ', $action)) . ": {$count}\n";
        }

        // Recent actions
        if (!empty($results['details']['updated_items'])) {
            $report .= "\nRecent Synchronization Actions:\n";
            foreach (array_slice($results['details']['updated_items'], 0, 10) as $item) {
                $action = ucfirst($item['action']);
                $report .= "- {$action}: {$item['name']} ({$item['nip']})";
                if (isset($item['reason'])) {
                    $report .= " - {$item['reason']}";
                }
                $report .= "\n";
            }
            if (count($results['details']['updated_items']) > 10) {
                $report .= "... and " . (count($results['details']['updated_items']) - 10) . " more\n";
            }
        }

        return $report;
    }

    /**
     * Get synchronization summary for API responses
     */
    public function getSyncSummary(): array
    {
        $results = $this->importResults;

        return [
            'sync_mode' => $this->syncMode,
            'soft_delete' => $this->softDelete,
            'processed_employees' => count($this->processedEmployeeIds),
            'actions' => [
                'created' => $results['created'],
                'updated' => $results['updated'],
                'deactivated' => 0, // Will be calculated from details
                'deleted' => 0,     // Will be calculated from details
            ],
            'sync_summary' => $results['details']['sync_summary'] ?? null
        ];
    }
}