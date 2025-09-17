<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SDMEmployeeImport extends BaseExcelImport
{
    private array $departmentCache = [];
    private array $employeeIdCache = [];

    protected function getColumnMapping(): array
    {
        return [
            'no' => 'no',
            'nomor' => 'no',
            'nip' => 'nip',
            'employee_id' => 'employee_id',
            'id_pegawai' => 'employee_id',
            'nama' => 'name',
            'name' => 'name',
            'nama_lengkap' => 'name',
            'full_name' => 'name',
            'department' => 'department',
            'departemen' => 'department',
            'bagian' => 'department',
            'divisi' => 'department',
            'position' => 'position',
            'jabatan' => 'position',
            'posisi' => 'position',
            'title' => 'position',
            'email' => 'email',
            'phone' => 'phone',
            'telephone' => 'phone',
            'no_hp' => 'phone',
            'no_telepon' => 'phone',
            'hire_date' => 'hire_date',
            'tanggal_masuk' => 'hire_date',
            'start_date' => 'hire_date',
            'join_date' => 'hire_date',
            'birth_date' => 'birth_date',
            'tanggal_lahir' => 'birth_date',
            'date_of_birth' => 'birth_date',
            'birth_place' => 'birth_place',
            'tempat_lahir' => 'birth_place',
            'place_of_birth' => 'birth_place',
            'gender' => 'gender',
            'jenis_kelamin' => 'gender',
            'sex' => 'gender',
            'address' => 'address',
            'alamat' => 'address',
            'full_address' => 'address',
            'employee_status' => 'employee_status',
            'status_pegawai' => 'employee_status',
            'employment_status' => 'employee_status',
            'active' => 'is_active',
            'is_active' => 'is_active',
            'status_aktif' => 'is_active',
            'aktif' => 'is_active',
            'education' => 'education',
            'pendidikan' => 'education',
            'education_level' => 'education',
            'marital_status' => 'marital_status',
            'status_pernikahan' => 'marital_status',
            'religion' => 'religion',
            'agama' => 'religion',
            'nationality' => 'nationality',
            'kewarganegaraan' => 'nationality',
            'id_number' => 'id_number',
            'nik' => 'id_number',
            'ktp' => 'id_number',
            'salary' => 'salary',
            'gaji' => 'salary',
            'basic_salary' => 'salary',
            'notes' => 'notes',
            'catatan' => 'notes',
            'keterangan' => 'notes',
            'remarks' => 'notes'
        ];
    }

    protected function getRequiredFields(): array
    {
        return ['name', 'nip'];
    }

    protected function getRequiredFieldsValidation(): array
    {
        return [
            'name' => 'required|string|max:255',
            'nip' => 'required|string|max:50',
        ];
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

            // Check for duplicate NIP in current import
            if ($this->isDuplicateInImport($normalizedData['nip'])) {
                $this->importResults['errors']++;
                $this->logRowError($rowNumber, "Duplicate NIP in import: {$normalizedData['nip']}", $normalizedData);
                return;
            }

            // Find existing employee
            $existingEmployee = $this->findExistingEmployee($normalizedData);

            if ($existingEmployee) {
                if ($this->updateExisting) {
                    $this->updateEmployee($existingEmployee, $normalizedData, $rowNumber);
                } else {
                    $this->importResults['skipped']++;
                    $this->importResults['details']['skipped'][] = [
                        'row' => $rowNumber,
                        'reason' => 'Employee already exists',
                        'nip' => $normalizedData['nip'],
                        'name' => $normalizedData['name']
                    ];
                }
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

    protected function isDuplicateInImport(string $nip): bool
    {
        if (in_array($nip, $this->employeeIdCache)) {
            return true;
        }

        $this->employeeIdCache[] = $nip;
        return false;
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
                'birth_date' => $this->parseDate($data['birth_date'] ?? null),
                'birth_place' => $data['birth_place'] ?? null,
                'gender' => $this->normalizeGender($data['gender'] ?? null),
                'address' => $data['address'] ?? null,
                'employee_status' => $data['employee_status'] ?? 'permanent',
                'is_active' => $this->parseBoolean($data['is_active'] ?? true),
                'education' => $data['education'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'religion' => $data['religion'] ?? null,
                'nationality' => $data['nationality'] ?? 'Indonesian',
                'id_number' => $data['id_number'] ?? null,
                'salary' => $this->parseNumeric($data['salary'] ?? null),
                'notes' => $data['notes'] ?? null,
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
                'department' => $department?->name
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
            $originalData = $employee->toArray();
            $updatedFields = [];

            $department = null;
            if (!empty($data['department'])) {
                $department = $this->findOrCreateDepartment($data['department']);
            }

            // Update fields if they have values and are different
            $fieldsToUpdate = [
                'name' => $data['name'],
                'department_id' => $department?->id,
                'position' => $data['position'] ?? $employee->position,
                'email' => $this->validateEmail($data['email'] ?? null) ?? $employee->email,
                'phone' => $data['phone'] ?? $employee->phone,
                'hire_date' => $this->parseDate($data['hire_date'] ?? null) ?? $employee->hire_date,
                'birth_date' => $this->parseDate($data['birth_date'] ?? null) ?? $employee->birth_date,
                'birth_place' => $data['birth_place'] ?? $employee->birth_place,
                'gender' => $this->normalizeGender($data['gender'] ?? null) ?? $employee->gender,
                'address' => $data['address'] ?? $employee->address,
                'employee_status' => $data['employee_status'] ?? $employee->employee_status,
                'is_active' => $this->parseBoolean($data['is_active'] ?? $employee->is_active),
                'education' => $data['education'] ?? $employee->education,
                'marital_status' => $data['marital_status'] ?? $employee->marital_status,
                'religion' => $data['religion'] ?? $employee->religion,
                'nationality' => $data['nationality'] ?? $employee->nationality,
                'id_number' => $data['id_number'] ?? $employee->id_number,
                'salary' => $this->parseNumeric($data['salary'] ?? null) ?? $employee->salary,
                'notes' => $data['notes'] ?? $employee->notes
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
                    'updated_fields' => $updatedFields
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
                    'name' => $employee->name
                ];
            }

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, "Failed to update employee: {$e->getMessage()}", $data);
        }
    }

    protected function findOrCreateDepartment(string $departmentName): ?Department
    {
        $normalizedName = trim($departmentName);

        if (empty($normalizedName)) {
            return null;
        }

        // Use cache to avoid repeated database queries
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

        $this->logWarning("Unknown gender format: {$gender}");
        return null;
    }

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            $trueValues = ['true', 'yes', 'ya', '1', 'active', 'aktif'];
            return in_array($value, $trueValues);
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return true; // Default to active
    }

    public function rules(): array
    {
        return [
            '*.nip' => 'required|string|max:50',
            '*.nama' => 'required|string|max:255',
            '*.name' => 'required|string|max:255',
            '*.employee_id' => 'sometimes|string|max:50',
            '*.email' => 'sometimes|email|max:255',
            '*.phone' => 'sometimes|string|max:20',
            '*.department' => 'sometimes|string|max:255',
            '*.position' => 'sometimes|string|max:255',
            '*.hire_date' => 'sometimes|date',
            '*.birth_date' => 'sometimes|date',
            '*.gender' => 'sometimes|string|in:male,female,laki-laki,perempuan,l,p,m,f',
            '*.employee_status' => 'sometimes|string|in:permanent,contract,intern,temporary',
            '*.is_active' => 'sometimes|boolean',
            '*.salary' => 'sometimes|numeric|min:0'
        ];
    }

    /**
     * Generate detailed report specific to employee import
     */
    public function generateReport(): string
    {
        $baseReport = parent::generateReport();
        $results = $this->importResults;

        $report = $baseReport;

        // Add employee-specific statistics
        if (!empty($results['details']['created_items'])) {
            $report .= "Created Employees:\n";
            foreach (array_slice($results['details']['created_items'], 0, 10) as $item) {
                $report .= "- Row {$item['row']}: {$item['name']} ({$item['nip']})";
                if (isset($item['department'])) {
                    $report .= " - {$item['department']}";
                }
                $report .= "\n";
            }
            if (count($results['details']['created_items']) > 10) {
                $report .= "... and " . (count($results['details']['created_items']) - 10) . " more\n";
            }
            $report .= "\n";
        }

        if (!empty($results['details']['updated_items'])) {
            $report .= "Updated Employees:\n";
            foreach (array_slice($results['details']['updated_items'], 0, 10) as $item) {
                $report .= "- Row {$item['row']}: {$item['name']} ({$item['nip']})";
                if (isset($item['updated_fields'])) {
                    $report .= " - Fields: " . implode(', ', array_keys($item['updated_fields']));
                }
                $report .= "\n";
            }
            if (count($results['details']['updated_items']) > 10) {
                $report .= "... and " . (count($results['details']['updated_items']) - 10) . " more\n";
            }
            $report .= "\n";
        }

        // Department statistics
        $departmentStats = [];
        foreach (array_merge($results['details']['created_items'], $results['details']['updated_items']) as $item) {
            if (isset($item['department'])) {
                $departmentStats[$item['department']] = ($departmentStats[$item['department']] ?? 0) + 1;
            }
        }

        if (!empty($departmentStats)) {
            $report .= "Department Distribution:\n";
            foreach ($departmentStats as $dept => $count) {
                $report .= "- {$dept}: {$count} employees\n";
            }
        }

        return $report;
    }
}