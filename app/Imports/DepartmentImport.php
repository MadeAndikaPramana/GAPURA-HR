<?php

namespace App\Imports;

use App\Models\Department;
use Illuminate\Support\Facades\DB;

class DepartmentImport extends BaseExcelImport
{
    private array $departmentCache = [];

    protected function getColumnMapping(): array
    {
        return [
            'no' => 'no',
            'nomor' => 'no',
            'code' => 'code',
            'kode' => 'code',
            'department_code' => 'code',
            'kode_departemen' => 'code',
            'name' => 'name',
            'nama' => 'name',
            'department_name' => 'name',
            'nama_departemen' => 'name',
            'description' => 'description',
            'deskripsi' => 'description',
            'keterangan' => 'description',
            'parent' => 'parent_name',
            'parent_department' => 'parent_name',
            'induk' => 'parent_name',
            'parent_code' => 'parent_code',
            'kode_induk' => 'parent_code',
            'manager' => 'manager',
            'kepala' => 'manager',
            'head' => 'manager',
            'manager_name' => 'manager',
            'head_of_department' => 'manager',
            'location' => 'location',
            'lokasi' => 'location',
            'alamat' => 'location',
            'phone' => 'phone',
            'telepon' => 'phone',
            'no_telp' => 'phone',
            'email' => 'email',
            'cost_center' => 'cost_center',
            'pusat_biaya' => 'cost_center',
            'budget_code' => 'cost_center',
            'active' => 'is_active',
            'is_active' => 'is_active',
            'status' => 'is_active',
            'aktif' => 'is_active',
            'established_date' => 'established_date',
            'tanggal_berdiri' => 'established_date',
            'created_date' => 'established_date'
        ];
    }

    protected function getRequiredFields(): array
    {
        return ['name'];
    }

    protected function getRequiredFieldsValidation(): array
    {
        return [
            'name' => 'required|string|max:255',
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

            // Check for duplicate in current import
            if ($this->isDuplicateInImport($normalizedData)) {
                $this->importResults['errors']++;
                $this->logRowError($rowNumber, "Duplicate department in import: {$normalizedData['name']}", $normalizedData);
                return;
            }

            // Find existing department
            $existingDepartment = $this->findExistingDepartment($normalizedData);

            if ($existingDepartment) {
                if ($this->updateExisting) {
                    $this->updateDepartment($existingDepartment, $normalizedData, $rowNumber);
                } else {
                    $this->importResults['skipped']++;
                    $this->importResults['details']['skipped'][] = [
                        'row' => $rowNumber,
                        'reason' => 'Department already exists',
                        'name' => $normalizedData['name'],
                        'code' => $normalizedData['code'] ?? 'N/A'
                    ];
                }
            } else {
                $this->createDepartment($normalizedData, $rowNumber);
            }

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, $e->getMessage(), $rowData);
        }
    }

    protected function findExistingDepartment(array $data): ?Department
    {
        $query = Department::query();

        // Try to find by code first (if provided)
        if (!empty($data['code'])) {
            $department = $query->where('code', $data['code'])->first();
            if ($department) {
                return $department;
            }
        }

        // Then try by name
        return Department::where('name', $data['name'])->first();
    }

    protected function isDuplicateInImport(array $data): bool
    {
        $key = $data['code'] ?? $data['name'];

        if (in_array($key, $this->departmentCache)) {
            return true;
        }

        $this->departmentCache[] = $key;
        return false;
    }

    protected function createDepartment(array $data, int $rowNumber): void
    {
        try {
            // Find parent department if specified
            $parentDepartment = $this->findParentDepartment($data);

            $departmentData = [
                'code' => $data['code'] ?? $this->generateDepartmentCode($data['name']),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'parent_id' => $parentDepartment?->id,
                'manager' => $data['manager'] ?? null,
                'location' => $data['location'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $this->validateEmail($data['email'] ?? null),
                'cost_center' => $data['cost_center'] ?? null,
                'is_active' => $this->parseBoolean($data['is_active'] ?? true),
                'established_date' => $this->parseDate($data['established_date'] ?? null),
                'created_at' => now(),
                'updated_at' => now()
            ];

            $department = Department::create($departmentData);

            $this->importResults['processed']++;
            $this->importResults['created']++;

            $this->importResults['details']['created_items'][] = [
                'row' => $rowNumber,
                'department_id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'parent' => $parentDepartment?->name
            ];

            $this->logSuccess($rowNumber, 'created', [
                'department_id' => $department->id,
                'code' => $department->code,
                'name' => $department->name
            ]);

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, "Failed to create department: {$e->getMessage()}", $data);
        }
    }

    protected function updateDepartment(Department $department, array $data, int $rowNumber): void
    {
        try {
            $updatedFields = [];
            $parentDepartment = $this->findParentDepartment($data);

            // Update fields if they have values and are different
            $fieldsToUpdate = [
                'code' => $data['code'] ?? $department->code,
                'name' => $data['name'],
                'description' => $data['description'] ?? $department->description,
                'parent_id' => $parentDepartment?->id ?? $department->parent_id,
                'manager' => $data['manager'] ?? $department->manager,
                'location' => $data['location'] ?? $department->location,
                'phone' => $data['phone'] ?? $department->phone,
                'email' => $this->validateEmail($data['email'] ?? null) ?? $department->email,
                'cost_center' => $data['cost_center'] ?? $department->cost_center,
                'is_active' => $this->parseBoolean($data['is_active'] ?? $department->is_active),
                'established_date' => $this->parseDate($data['established_date'] ?? null) ?? $department->established_date
            ];

            foreach ($fieldsToUpdate as $field => $value) {
                if ($value !== null && $department->$field != $value) {
                    $updatedFields[$field] = [
                        'old' => $department->$field,
                        'new' => $value
                    ];
                    $department->$field = $value;
                }
            }

            if (!empty($updatedFields)) {
                $department->updated_at = now();
                $department->save();

                $this->importResults['processed']++;
                $this->importResults['updated']++;

                $this->importResults['details']['updated_items'][] = [
                    'row' => $rowNumber,
                    'department_id' => $department->id,
                    'code' => $department->code,
                    'name' => $department->name,
                    'updated_fields' => $updatedFields
                ];

                $this->logSuccess($rowNumber, 'updated', [
                    'department_id' => $department->id,
                    'code' => $department->code,
                    'name' => $department->name,
                    'changes' => array_keys($updatedFields)
                ]);
            } else {
                $this->importResults['skipped']++;
                $this->importResults['details']['skipped'][] = [
                    'row' => $rowNumber,
                    'reason' => 'No changes detected',
                    'name' => $department->name,
                    'code' => $department->code
                ];
            }

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, "Failed to update department: {$e->getMessage()}", $data);
        }
    }

    protected function findParentDepartment(array $data): ?Department
    {
        if (empty($data['parent_name']) && empty($data['parent_code'])) {
            return null;
        }

        // Try to find by parent code first
        if (!empty($data['parent_code'])) {
            $parent = Department::where('code', $data['parent_code'])->first();
            if ($parent) {
                return $parent;
            }
        }

        // Try to find by parent name
        if (!empty($data['parent_name'])) {
            $parent = Department::where('name', $data['parent_name'])->first();
            if ($parent) {
                return $parent;
            }
        }

        // Log warning if parent specified but not found
        $this->logWarning("Parent department not found", [
            'parent_name' => $data['parent_name'] ?? null,
            'parent_code' => $data['parent_code'] ?? null
        ]);

        return null;
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

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            $trueValues = ['true', 'yes', 'ya', '1', 'active', 'aktif'];
            $falseValues = ['false', 'no', 'tidak', '0', 'inactive', 'nonaktif'];

            if (in_array($value, $trueValues)) {
                return true;
            }

            if (in_array($value, $falseValues)) {
                return false;
            }
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return true; // Default to active
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.nama' => 'required|string|max:255',
            '*.code' => 'sometimes|string|max:20',
            '*.kode' => 'sometimes|string|max:20',
            '*.description' => 'sometimes|string|max:1000',
            '*.email' => 'sometimes|email|max:255',
            '*.phone' => 'sometimes|string|max:20',
            '*.manager' => 'sometimes|string|max:255',
            '*.location' => 'sometimes|string|max:255',
            '*.cost_center' => 'sometimes|string|max:50',
            '*.is_active' => 'sometimes|boolean',
            '*.established_date' => 'sometimes|date'
        ];
    }

    /**
     * Generate detailed report specific to department import
     */
    public function generateReport(): string
    {
        $baseReport = parent::generateReport();
        $results = $this->importResults;

        $report = $baseReport;

        // Add department-specific statistics
        if (!empty($results['details']['created_items'])) {
            $report .= "Created Departments:\n";
            foreach (array_slice($results['details']['created_items'], 0, 15) as $item) {
                $report .= "- Row {$item['row']}: {$item['name']} ({$item['code']})";
                if (isset($item['parent'])) {
                    $report .= " - Parent: {$item['parent']}";
                }
                $report .= "\n";
            }
            if (count($results['details']['created_items']) > 15) {
                $report .= "... and " . (count($results['details']['created_items']) - 15) . " more\n";
            }
            $report .= "\n";
        }

        if (!empty($results['details']['updated_items'])) {
            $report .= "Updated Departments:\n";
            foreach (array_slice($results['details']['updated_items'], 0, 15) as $item) {
                $report .= "- Row {$item['row']}: {$item['name']} ({$item['code']})";
                if (isset($item['updated_fields'])) {
                    $report .= " - Fields: " . implode(', ', array_keys($item['updated_fields']));
                }
                $report .= "\n";
            }
            if (count($results['details']['updated_items']) > 15) {
                $report .= "... and " . (count($results['details']['updated_items']) - 15) . " more\n";
            }
            $report .= "\n";
        }

        // Department hierarchy statistics
        $parentStats = [];
        foreach (array_merge($results['details']['created_items'], $results['details']['updated_items']) as $item) {
            if (isset($item['parent'])) {
                $parentStats[$item['parent']] = ($parentStats[$item['parent']] ?? 0) + 1;
            } else {
                $parentStats['Root Level'] = ($parentStats['Root Level'] ?? 0) + 1;
            }
        }

        if (!empty($parentStats)) {
            $report .= "Department Hierarchy Distribution:\n";
            foreach ($parentStats as $parent => $count) {
                $report .= "- {$parent}: {$count} departments\n";
            }
        }

        return $report;
    }
}