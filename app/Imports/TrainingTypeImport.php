<?php

namespace App\Imports;

use App\Models\CertificateType;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class TrainingTypeImport extends BaseExcelImport
{
    private array $certificateTypeCache = [];
    private array $departmentRequirements = [];

    protected function getColumnMapping(): array
    {
        return [
            'no' => 'no',
            'nomor' => 'no',
            'code' => 'code',
            'kode' => 'code',
            'training_code' => 'code',
            'certificate_code' => 'code',
            'kode_training' => 'code',
            'kode_sertifikat' => 'code',
            'name' => 'name',
            'nama' => 'name',
            'training_name' => 'name',
            'certificate_name' => 'name',
            'nama_training' => 'name',
            'nama_sertifikat' => 'name',
            'description' => 'description',
            'deskripsi' => 'description',
            'keterangan' => 'description',
            'category' => 'category',
            'kategori' => 'category',
            'training_category' => 'category',
            'jenis' => 'category',
            'type' => 'category',
            'validity_months' => 'validity_months',
            'masa_berlaku' => 'validity_months',
            'valid_for' => 'validity_months',
            'warning_days' => 'warning_days',
            'hari_peringatan' => 'warning_days',
            'remind_before' => 'warning_days',
            'mandatory' => 'is_mandatory',
            'is_mandatory' => 'is_mandatory',
            'wajib' => 'is_mandatory',
            'required' => 'is_mandatory',
            'recurrent' => 'is_recurrent',
            'is_recurrent' => 'is_recurrent',
            'berulang' => 'is_recurrent',
            'recurring' => 'is_recurrent',
            'active' => 'is_active',
            'is_active' => 'is_active',
            'status' => 'is_active',
            'aktif' => 'is_active',
            'duration_hours' => 'estimated_duration_hours',
            'durasi_jam' => 'estimated_duration_hours',
            'training_hours' => 'estimated_duration_hours',
            'jam_training' => 'estimated_duration_hours',
            'cost' => 'estimated_cost',
            'biaya' => 'estimated_cost',
            'estimated_cost' => 'estimated_cost',
            'perkiraan_biaya' => 'estimated_cost',
            'provider' => 'default_provider',
            'penyelenggara' => 'default_provider',
            'training_provider' => 'default_provider',
            'default_provider' => 'default_provider',
            'departments' => 'required_departments',
            'departemen' => 'required_departments',
            'required_departments' => 'required_departments',
            'department_list' => 'required_departments',
            'competency' => 'competency_area',
            'kompetensi' => 'competency_area',
            'competency_area' => 'competency_area',
            'area_kompetensi' => 'competency_area',
            'level' => 'level',
            'tingkat' => 'level',
            'training_level' => 'level',
            'difficulty' => 'level',
            'prerequisite' => 'prerequisites',
            'prasyarat' => 'prerequisites',
            'requirements' => 'prerequisites',
            'persyaratan' => 'prerequisites'
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
                $this->logRowError($rowNumber, "Duplicate training type in import: {$normalizedData['name']}", $normalizedData);
                return;
            }

            // Find existing certificate type
            $existingCertificateType = $this->findExistingCertificateType($normalizedData);

            if ($existingCertificateType) {
                if ($this->updateExisting) {
                    $this->updateCertificateType($existingCertificateType, $normalizedData, $rowNumber);
                } else {
                    $this->importResults['skipped']++;
                    $this->importResults['details']['skipped'][] = [
                        'row' => $rowNumber,
                        'reason' => 'Training type already exists',
                        'name' => $normalizedData['name'],
                        'code' => $normalizedData['code'] ?? 'N/A'
                    ];
                }
            } else {
                $this->createCertificateType($normalizedData, $rowNumber);
            }

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, $e->getMessage(), $rowData);
        }
    }

    protected function findExistingCertificateType(array $data): ?CertificateType
    {
        // Try to find by code first (if provided)
        if (!empty($data['code'])) {
            $certificateType = CertificateType::where('code', $data['code'])->first();
            if ($certificateType) {
                return $certificateType;
            }
        }

        // Then try by name
        return CertificateType::where('name', $data['name'])->first();
    }

    protected function isDuplicateInImport(array $data): bool
    {
        $key = $data['code'] ?? $data['name'];

        if (in_array($key, $this->certificateTypeCache)) {
            return true;
        }

        $this->certificateTypeCache[] = $key;
        return false;
    }

    protected function createCertificateType(array $data, int $rowNumber): void
    {
        try {
            $certificateTypeData = [
                'code' => $data['code'] ?? $this->generateCertificateTypeCode($data['name']),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? 'General',
                'validity_months' => $this->parseInteger($data['validity_months'] ?? null),
                'warning_days' => $this->parseInteger($data['warning_days'] ?? 90),
                'is_mandatory' => $this->parseBoolean($data['is_mandatory'] ?? false),
                'is_recurrent' => $this->parseBoolean($data['is_recurrent'] ?? true),
                'is_active' => $this->parseBoolean($data['is_active'] ?? true),
                'estimated_duration_hours' => $this->parseNumeric($data['estimated_duration_hours'] ?? null),
                'estimated_cost' => $this->parseNumeric($data['estimated_cost'] ?? null),
                'default_provider' => $data['default_provider'] ?? null,
                'competency_area' => $data['competency_area'] ?? null,
                'level' => $data['level'] ?? null,
                'prerequisites' => $data['prerequisites'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ];

            $certificateType = CertificateType::create($certificateTypeData);

            // Handle department requirements
            $this->processDepartmentRequirements($certificateType, $data, $rowNumber);

            $this->importResults['processed']++;
            $this->importResults['created']++;

            $this->importResults['details']['created_items'][] = [
                'row' => $rowNumber,
                'certificate_type_id' => $certificateType->id,
                'code' => $certificateType->code,
                'name' => $certificateType->name,
                'category' => $certificateType->category,
                'is_mandatory' => $certificateType->is_mandatory
            ];

            $this->logSuccess($rowNumber, 'created', [
                'certificate_type_id' => $certificateType->id,
                'code' => $certificateType->code,
                'name' => $certificateType->name
            ]);

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, "Failed to create training type: {$e->getMessage()}", $data);
        }
    }

    protected function updateCertificateType(CertificateType $certificateType, array $data, int $rowNumber): void
    {
        try {
            $updatedFields = [];

            // Update fields if they have values and are different
            $fieldsToUpdate = [
                'code' => $data['code'] ?? $certificateType->code,
                'name' => $data['name'],
                'description' => $data['description'] ?? $certificateType->description,
                'category' => $data['category'] ?? $certificateType->category,
                'validity_months' => $this->parseInteger($data['validity_months'] ?? null) ?? $certificateType->validity_months,
                'warning_days' => $this->parseInteger($data['warning_days'] ?? null) ?? $certificateType->warning_days,
                'is_mandatory' => $this->parseBoolean($data['is_mandatory'] ?? $certificateType->is_mandatory),
                'is_recurrent' => $this->parseBoolean($data['is_recurrent'] ?? $certificateType->is_recurrent),
                'is_active' => $this->parseBoolean($data['is_active'] ?? $certificateType->is_active),
                'estimated_duration_hours' => $this->parseNumeric($data['estimated_duration_hours'] ?? null) ?? $certificateType->estimated_duration_hours,
                'estimated_cost' => $this->parseNumeric($data['estimated_cost'] ?? null) ?? $certificateType->estimated_cost,
                'default_provider' => $data['default_provider'] ?? $certificateType->default_provider,
                'competency_area' => $data['competency_area'] ?? $certificateType->competency_area,
                'level' => $data['level'] ?? $certificateType->level,
                'prerequisites' => $data['prerequisites'] ?? $certificateType->prerequisites
            ];

            foreach ($fieldsToUpdate as $field => $value) {
                if ($value !== null && $certificateType->$field != $value) {
                    $updatedFields[$field] = [
                        'old' => $certificateType->$field,
                        'new' => $value
                    ];
                    $certificateType->$field = $value;
                }
            }

            if (!empty($updatedFields)) {
                $certificateType->updated_at = now();
                $certificateType->save();

                // Handle department requirements
                $this->processDepartmentRequirements($certificateType, $data, $rowNumber);

                $this->importResults['processed']++;
                $this->importResults['updated']++;

                $this->importResults['details']['updated_items'][] = [
                    'row' => $rowNumber,
                    'certificate_type_id' => $certificateType->id,
                    'code' => $certificateType->code,
                    'name' => $certificateType->name,
                    'updated_fields' => $updatedFields
                ];

                $this->logSuccess($rowNumber, 'updated', [
                    'certificate_type_id' => $certificateType->id,
                    'code' => $certificateType->code,
                    'name' => $certificateType->name,
                    'changes' => array_keys($updatedFields)
                ]);
            } else {
                $this->importResults['skipped']++;
                $this->importResults['details']['skipped'][] = [
                    'row' => $rowNumber,
                    'reason' => 'No changes detected',
                    'name' => $certificateType->name,
                    'code' => $certificateType->code
                ];
            }

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, "Failed to update training type: {$e->getMessage()}", $data);
        }
    }

    protected function processDepartmentRequirements(CertificateType $certificateType, array $data, int $rowNumber): void
    {
        if (empty($data['required_departments'])) {
            return;
        }

        try {
            // Parse department names (comma or semicolon separated)
            $departmentNames = preg_split('/[,;]/', $data['required_departments']);
            $departmentIds = [];

            foreach ($departmentNames as $departmentName) {
                $departmentName = trim($departmentName);
                if (empty($departmentName)) {
                    continue;
                }

                $department = Department::where('name', $departmentName)
                                      ->orWhere('code', $departmentName)
                                      ->first();

                if ($department) {
                    $departmentIds[] = $department->id;
                } else {
                    $this->logWarning("Department not found for training type requirement", [
                        'training_type' => $certificateType->name,
                        'department' => $departmentName,
                        'row' => $rowNumber
                    ]);
                }
            }

            // Store department requirements for later processing
            // This would require a pivot table or JSON field in CertificateType model
            if (!empty($departmentIds)) {
                $this->departmentRequirements[$certificateType->id] = $departmentIds;
                $this->logInfo("Processed department requirements for {$certificateType->name}", [
                    'departments' => count($departmentIds)
                ]);
            }

        } catch (\Exception $e) {
            $this->logWarning("Failed to process department requirements", [
                'training_type' => $certificateType->name,
                'error' => $e->getMessage(),
                'row' => $rowNumber
            ]);
        }
    }

    protected function generateCertificateTypeCode(string $name): string
    {
        // Create code from name
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 8));

        // Ensure uniqueness
        $baseCode = $code ?: 'CERT';
        $counter = 1;
        $finalCode = $baseCode;

        while (CertificateType::where('code', $finalCode)->exists()) {
            $finalCode = $baseCode . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $finalCode;
    }

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            $trueValues = ['true', 'yes', 'ya', '1', 'active', 'aktif', 'mandatory', 'wajib', 'required'];
            $falseValues = ['false', 'no', 'tidak', '0', 'inactive', 'nonaktif', 'optional', 'opsional'];

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

        return false; // Default to false for training types
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.nama' => 'required|string|max:255',
            '*.code' => 'sometimes|string|max:20',
            '*.kode' => 'sometimes|string|max:20',
            '*.description' => 'sometimes|string|max:1000',
            '*.category' => 'sometimes|string|max:100',
            '*.validity_months' => 'sometimes|integer|min:1|max:120',
            '*.warning_days' => 'sometimes|integer|min:1|max:365',
            '*.is_mandatory' => 'sometimes|boolean',
            '*.is_recurrent' => 'sometimes|boolean',
            '*.is_active' => 'sometimes|boolean',
            '*.estimated_duration_hours' => 'sometimes|numeric|min:0',
            '*.estimated_cost' => 'sometimes|numeric|min:0',
            '*.default_provider' => 'sometimes|string|max:255',
            '*.competency_area' => 'sometimes|string|max:255',
            '*.level' => 'sometimes|string|max:50',
            '*.prerequisites' => 'sometimes|string|max:1000'
        ];
    }

    /**
     * Generate detailed report specific to training type import
     */
    public function generateReport(): string
    {
        $baseReport = parent::generateReport();
        $results = $this->importResults;

        $report = $baseReport;

        // Add training type-specific statistics
        if (!empty($results['details']['created_items'])) {
            $report .= "Created Training Types:\n";
            foreach (array_slice($results['details']['created_items'], 0, 15) as $item) {
                $report .= "- Row {$item['row']}: {$item['name']} ({$item['code']}) - {$item['category']}";
                if ($item['is_mandatory']) {
                    $report .= " [MANDATORY]";
                }
                $report .= "\n";
            }
            if (count($results['details']['created_items']) > 15) {
                $report .= "... and " . (count($results['details']['created_items']) - 15) . " more\n";
            }
            $report .= "\n";
        }

        if (!empty($results['details']['updated_items'])) {
            $report .= "Updated Training Types:\n";
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

        // Category statistics
        $categoryStats = [];
        $mandatoryCount = 0;
        foreach (array_merge($results['details']['created_items'], $results['details']['updated_items']) as $item) {
            $category = $item['category'] ?? 'General';
            $categoryStats[$category] = ($categoryStats[$category] ?? 0) + 1;

            if ($item['is_mandatory'] ?? false) {
                $mandatoryCount++;
            }
        }

        if (!empty($categoryStats)) {
            $report .= "Category Distribution:\n";
            foreach ($categoryStats as $category => $count) {
                $report .= "- {$category}: {$count} training types\n";
            }
            $report .= "- Mandatory Training Types: {$mandatoryCount}\n";
        }

        // Department requirements summary
        if (!empty($this->departmentRequirements)) {
            $report .= "\nDepartment Requirements Processed: " . count($this->departmentRequirements) . " training types\n";
        }

        return $report;
    }
}