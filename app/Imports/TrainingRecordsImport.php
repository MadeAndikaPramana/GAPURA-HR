<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\CertificateType;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainingRecordsImport extends BaseExcelImport
{
    private array $employeeCache = [];
    private array $certificateTypeCache = [];
    private array $departmentCache = [];
    private bool $createCertificateTypes;
    private bool $createEmployees;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->createCertificateTypes = $options['create_certificate_types'] ?? true;
        $this->createEmployees = $options['create_employees'] ?? false;
    }

    protected function getColumnMapping(): array
    {
        return [
            'no' => 'no',
            'nomor' => 'no',
            'nip' => 'nip',
            'employee_id' => 'employee_id',
            'id_pegawai' => 'employee_id',
            'nama' => 'employee_name',
            'name' => 'employee_name',
            'nama_pegawai' => 'employee_name',
            'employee_name' => 'employee_name',
            'department' => 'department',
            'departemen' => 'department',
            'bagian' => 'department',
            'divisi' => 'department',
            'position' => 'position',
            'jabatan' => 'position',
            'posisi' => 'position',
            'training_code' => 'training_code',
            'kode_training' => 'training_code',
            'code' => 'training_code',
            'training_name' => 'training_name',
            'nama_training' => 'training_name',
            'certificate_name' => 'training_name',
            'nama_sertifikat' => 'training_name',
            'training_type' => 'training_type',
            'jenis_training' => 'training_type',
            'certificate_type' => 'training_type',
            'category' => 'training_type',
            'kategori' => 'training_type',
            'certificate_number' => 'certificate_number',
            'nomor_sertifikat' => 'certificate_number',
            'cert_number' => 'certificate_number',
            'issuer' => 'issuer',
            'penerbit' => 'issuer',
            'issued_by' => 'issuer',
            'training_provider' => 'training_provider',
            'penyelenggara' => 'training_provider',
            'provider' => 'training_provider',
            'organizer' => 'training_provider',
            'training_date' => 'training_date',
            'tanggal_training' => 'training_date',
            'start_date' => 'training_date',
            'completion_date' => 'completion_date',
            'tanggal_selesai' => 'completion_date',
            'end_date' => 'completion_date',
            'issue_date' => 'issue_date',
            'tanggal_terbit' => 'issue_date',
            'issued_date' => 'issue_date',
            'expiry_date' => 'expiry_date',
            'tanggal_expired' => 'expiry_date',
            'expired_date' => 'expiry_date',
            'validity_months' => 'validity_months',
            'masa_berlaku' => 'validity_months',
            'valid_for' => 'validity_months',
            'location' => 'location',
            'lokasi' => 'location',
            'tempat' => 'location',
            'venue' => 'location',
            'instructor' => 'instructor_name',
            'instruktur' => 'instructor_name',
            'trainer' => 'instructor_name',
            'training_hours' => 'training_hours',
            'jam_training' => 'training_hours',
            'hours' => 'training_hours',
            'duration' => 'training_hours',
            'score' => 'score',
            'nilai' => 'score',
            'grade' => 'grade',
            'cost' => 'cost',
            'biaya' => 'cost',
            'fee' => 'cost',
            'notes' => 'notes',
            'catatan' => 'notes',
            'keterangan' => 'notes',
            'remarks' => 'notes',
            'status' => 'status',
            'file_path' => 'file_path',
            'file_url' => 'file_path',
            'document' => 'file_path'
        ];
    }

    protected function getRequiredFields(): array
    {
        return ['nip', 'employee_name', 'training_name'];
    }

    protected function getRequiredFieldsValidation(): array
    {
        return [
            'nip' => 'required|string|max:50',
            'employee_name' => 'required|string|max:255',
            'training_name' => 'required|string|max:255',
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

            // Find or create employee
            $employee = $this->findOrCreateEmployee($normalizedData, $rowNumber);
            if (!$employee) {
                return;
            }

            // Find or create certificate type
            $certificateType = $this->findOrCreateCertificateType($normalizedData, $rowNumber);
            if (!$certificateType) {
                return;
            }

            // Create or update certificate
            $certificate = $this->createOrUpdateCertificate($employee, $certificateType, $normalizedData, $rowNumber);

            if ($certificate) {
                $this->importResults['processed']++;
                if ($certificate->wasRecentlyCreated) {
                    $this->importResults['created']++;
                } else {
                    $this->importResults['updated']++;
                }

                $this->logCertificateAction($certificate, $rowNumber, $normalizedData);
            }

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, $e->getMessage(), $rowData);
        }
    }

    protected function findOrCreateEmployee(array $data, int $rowNumber): ?Employee
    {
        $nip = $data['nip'];

        // Use cache to avoid repeated database queries
        if (isset($this->employeeCache[$nip])) {
            return $this->employeeCache[$nip];
        }

        // Try to find employee by NIP or employee_id
        $employee = Employee::where('employee_id', $nip)
                           ->orWhere('nip', $nip)
                           ->first();

        if ($employee) {
            $this->employeeCache[$nip] = $employee;

            // Update employee data if needed
            $this->updateEmployeeData($employee, $data);

            return $employee;
        }

        // Create employee if creation is enabled
        if ($this->createEmployees) {
            $employee = $this->createNewEmployee($data, $rowNumber);
            if ($employee) {
                $this->employeeCache[$nip] = $employee;
                return $employee;
            }
        }

        $this->importResults['errors']++;
        $this->logRowError($rowNumber, "Employee not found: {$nip} ({$data['employee_name']})", $data);
        return null;
    }

    protected function createNewEmployee(array $data, int $rowNumber): ?Employee
    {
        try {
            $department = null;
            if (!empty($data['department'])) {
                $department = $this->findOrCreateDepartment($data['department']);
            }

            $employee = Employee::create([
                'employee_id' => $data['nip'],
                'nip' => $data['nip'],
                'name' => $data['employee_name'],
                'department_id' => $department?->id,
                'position' => $data['position'] ?? null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->logInfo("Created new employee during training import: {$employee->name} ({$employee->employee_id})");

            // Auto-create employee container if enabled
            $this->createEmployeeContainer($employee->id);

            return $employee;

        } catch (\Exception $e) {
            $this->logRowError($rowNumber, "Failed to create employee: {$e->getMessage()}", $data);
            return null;
        }
    }

    protected function updateEmployeeData(Employee $employee, array $data): void
    {
        $updated = false;

        // Update name if provided and different
        if (!empty($data['employee_name']) && $employee->name !== $data['employee_name']) {
            $employee->name = $data['employee_name'];
            $updated = true;
        }

        // Update position if provided and different
        if (!empty($data['position']) && $employee->position !== $data['position']) {
            $employee->position = $data['position'];
            $updated = true;
        }

        // Update department if provided
        if (!empty($data['department'])) {
            $department = $this->findOrCreateDepartment($data['department']);
            if ($department && $employee->department_id !== $department->id) {
                $employee->department_id = $department->id;
                $updated = true;
            }
        }

        if ($updated) {
            $employee->save();
        }
    }

    protected function findOrCreateCertificateType(array $data, int $rowNumber): ?CertificateType
    {
        $trainingName = $data['training_name'];
        $trainingCode = $data['training_code'] ?? null;

        // Create cache key
        $cacheKey = $trainingCode ?: $trainingName;

        if (isset($this->certificateTypeCache[$cacheKey])) {
            return $this->certificateTypeCache[$cacheKey];
        }

        $certificateType = null;

        // Try to find by code first
        if ($trainingCode) {
            $certificateType = CertificateType::where('code', $trainingCode)->first();
        }

        // Try to find by name if not found by code
        if (!$certificateType) {
            $certificateType = CertificateType::where('name', $trainingName)
                                            ->orWhere('name', 'like', '%' . $trainingName . '%')
                                            ->first();
        }

        // Create new certificate type if not found and creation is enabled
        if (!$certificateType && $this->createCertificateTypes) {
            $certificateType = $this->createNewCertificateType($data, $rowNumber);
        }

        if ($certificateType) {
            $this->certificateTypeCache[$cacheKey] = $certificateType;
        } else {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, "Certificate type not found: {$trainingName}", $data);
        }

        return $certificateType;
    }

    protected function createNewCertificateType(array $data, int $rowNumber): ?CertificateType
    {
        try {
            $trainingName = $data['training_name'];
            $trainingCode = $data['training_code'] ?? $this->generateCertificateCode($trainingName);

            $certificateType = CertificateType::create([
                'name' => $trainingName,
                'code' => $trainingCode,
                'category' => $data['training_type'] ?? 'General Training',
                'validity_months' => $this->parseValidityMonths($data),
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'is_active' => true,
                'description' => 'Auto-created from training records import',
                'estimated_duration_hours' => $this->parseNumeric($data['training_hours'])
            ]);

            $this->importResults['details']['created_items'][] = [
                'type' => 'certificate_type',
                'row' => $rowNumber,
                'name' => $trainingName,
                'code' => $trainingCode
            ];

            $this->logInfo("Created new certificate type: {$trainingName} ({$trainingCode})");

            return $certificateType;

        } catch (\Exception $e) {
            $this->logRowError($rowNumber, "Failed to create certificate type: {$e->getMessage()}", $data);
            return null;
        }
    }

    protected function generateCertificateCode(string $name): string
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 8));
        return $code ?: 'CERT' . rand(1000, 9999);
    }

    protected function parseValidityMonths(array $data): ?int
    {
        if (!empty($data['validity_months'])) {
            $months = $this->parseInteger($data['validity_months']);
            return $months > 0 ? $months : null;
        }

        // Try to calculate from issue and expiry dates
        if (!empty($data['issue_date']) && !empty($data['expiry_date'])) {
            $issueDate = $this->parseDate($data['issue_date']);
            $expiryDate = $this->parseDate($data['expiry_date']);

            if ($issueDate && $expiryDate) {
                $months = $issueDate->diffInMonths($expiryDate);
                return $months > 0 ? $months : null;
            }
        }

        // Default validity based on training type
        $trainingType = strtolower($data['training_type'] ?? '');

        if (strpos($trainingType, 'safety') !== false || strpos($trainingType, 'k3') !== false) {
            return 12; // Safety trainings typically 1 year
        }

        if (strpos($trainingType, 'technical') !== false || strpos($trainingType, 'teknis') !== false) {
            return 24; // Technical trainings typically 2 years
        }

        return 36; // Default 3 years
    }

    protected function createOrUpdateCertificate(Employee $employee, CertificateType $certificateType, array $data, int $rowNumber): ?EmployeeCertificate
    {
        try {
            // Check for existing certificate
            $existingCertificate = EmployeeCertificate::where('employee_id', $employee->id)
                                                    ->where('certificate_type_id', $certificateType->id)
                                                    ->orderBy('issue_date', 'desc')
                                                    ->first();

            // Parse dates
            $trainingDate = $this->parseDate($data['training_date'] ?? null);
            $completionDate = $this->parseDate($data['completion_date'] ?? null);
            $issueDate = $this->parseDate($data['issue_date'] ?? null)
                        ?? $completionDate
                        ?? $trainingDate;
            $expiryDate = $this->parseDate($data['expiry_date'] ?? null);

            // Calculate expiry date if not provided
            if (!$expiryDate && $issueDate && $certificateType->validity_months) {
                $expiryDate = $issueDate->copy()->addMonths($certificateType->validity_months);
            }

            $certificateData = [
                'employee_id' => $employee->id,
                'certificate_type_id' => $certificateType->id,
                'certificate_number' => $data['certificate_number'] ?? null,
                'issuer' => $data['issuer'] ?? 'Unknown',
                'training_provider' => $data['training_provider'] ?? $data['issuer'] ?? 'Unknown',
                'training_date' => $trainingDate,
                'completion_date' => $completionDate,
                'issue_date' => $issueDate,
                'expiry_date' => $expiryDate,
                'location' => $data['location'] ?? null,
                'instructor_name' => $data['instructor_name'] ?? null,
                'training_hours' => $this->parseNumeric($data['training_hours']),
                'cost' => $this->parseNumeric($data['cost']),
                'score' => $this->parseNumeric($data['score']),
                'grade' => $data['grade'] ?? null,
                'notes' => $data['notes'] ?? null,
                'file_path' => $data['file_path'] ?? null,
                'status' => 'pending' // Will be updated by status calculation
            ];

            $certificate = null;

            if ($existingCertificate && $this->updateExisting) {
                // Update existing certificate
                $existingCertificate->update($certificateData);
                $certificate = $existingCertificate;
            } elseif (!$existingCertificate || $this->isNewerCertificate($existingCertificate, $certificateData)) {
                // Create new certificate
                $certificate = EmployeeCertificate::create($certificateData);
            } else {
                // Skip if existing certificate is newer
                $this->importResults['skipped']++;
                $this->importResults['details']['skipped'][] = [
                    'row' => $rowNumber,
                    'reason' => 'Existing certificate is newer',
                    'employee' => $employee->name,
                    'certificate_type' => $certificateType->name
                ];
                return null;
            }

            if ($certificate) {
                // Update status based on dates
                $certificate->updateStatusBasedOnDates();
            }

            return $certificate;

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, "Failed to create/update certificate: {$e->getMessage()}", $data);
            return null;
        }
    }

    protected function isNewerCertificate(EmployeeCertificate $existing, array $newData): bool
    {
        $existingDate = $existing->issue_date ?? $existing->training_date;
        $newDate = $newData['issue_date'] ?? $newData['training_date'];

        if (!$existingDate || !$newDate) {
            return true; // Allow if we can't compare dates
        }

        return $newDate->greaterThan($existingDate);
    }

    protected function logCertificateAction(EmployeeCertificate $certificate, int $rowNumber, array $data): void
    {
        $action = $certificate->wasRecentlyCreated ? 'created' : 'updated';

        $this->importResults['details']['success'][] = [
            'row' => $rowNumber,
            'action' => $action,
            'employee' => $certificate->employee->name,
            'employee_nip' => $certificate->employee->employee_id,
            'certificate_type' => $certificate->certificateType->name,
            'certificate_number' => $certificate->certificate_number,
            'issue_date' => $certificate->issue_date?->format('Y-m-d'),
            'expiry_date' => $certificate->expiry_date?->format('Y-m-d'),
            'status' => $certificate->status
        ];

        $this->logSuccess($rowNumber, $action, [
            'certificate_id' => $certificate->id,
            'employee' => $certificate->employee->name,
            'certificate_type' => $certificate->certificateType->name
        ]);
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

    public function rules(): array
    {
        return [
            '*.nip' => 'required|string|max:50',
            '*.nama' => 'required|string|max:255',
            '*.employee_name' => 'required|string|max:255',
            '*.training_name' => 'required|string|max:255',
            '*.training_code' => 'sometimes|string|max:50',
            '*.certificate_number' => 'sometimes|string|max:100',
            '*.issuer' => 'sometimes|string|max:255',
            '*.training_date' => 'sometimes|date',
            '*.completion_date' => 'sometimes|date',
            '*.issue_date' => 'sometimes|date',
            '*.expiry_date' => 'sometimes|date',
            '*.training_hours' => 'sometimes|numeric|min:0',
            '*.cost' => 'sometimes|numeric|min:0',
            '*.score' => 'sometimes|numeric|min:0|max:100',
            '*.validity_months' => 'sometimes|integer|min:1|max:120'
        ];
    }

    /**
     * Generate detailed report specific to training records import
     */
    public function generateReport(): string
    {
        $baseReport = parent::generateReport();
        $results = $this->importResults;

        $report = $baseReport;

        // Certificate statistics
        $certificateStats = [
            'total_certificates' => $results['created'] + $results['updated'],
            'new_certificates' => $results['created'],
            'updated_certificates' => $results['updated']
        ];

        $report .= "Certificate Statistics:\n";
        $report .= "- Total certificates processed: {$certificateStats['total_certificates']}\n";
        $report .= "- New certificates created: {$certificateStats['new_certificates']}\n";
        $report .= "- Certificates updated: {$certificateStats['updated_certificates']}\n\n";

        // Training type statistics
        $newCertTypes = array_filter($results['details']['created_items'], function($item) {
            return $item['type'] === 'certificate_type';
        });

        if (!empty($newCertTypes)) {
            $report .= "New Certificate Types Created:\n";
            foreach ($newCertTypes as $item) {
                $report .= "- {$item['name']} ({$item['code']}) - Row {$item['row']}\n";
            }
            $report .= "\n";
        }

        // Recent certificates
        if (!empty($results['details']['success'])) {
            $report .= "Recent Certificates Processed:\n";
            foreach (array_slice($results['details']['success'], 0, 10) as $item) {
                $report .= "- Row {$item['row']}: {$item['employee']} - {$item['certificate_type']} ({$item['action']})";
                if ($item['issue_date']) {
                    $report .= " - Issued: {$item['issue_date']}";
                }
                if ($item['expiry_date']) {
                    $report .= " - Expires: {$item['expiry_date']}";
                }
                $report .= "\n";
            }
            if (count($results['details']['success']) > 10) {
                $report .= "... and " . (count($results['details']['success']) - 10) . " more\n";
            }
        }

        return $report;
    }
}