<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\TrainingRecord;
use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CertificatesExport;
use Carbon\Carbon;

class CertificateService
{
    /**
     * Store certificate file with proper naming and organization
     */
    public function storeCertificateFile(UploadedFile $file, string $certificateNumber): string
    {
        $year = date('Y');
        $month = date('m');

        // Create organized directory structure
        $directory = "certificates/{$year}/{$month}";

        // Generate secure filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug($certificateNumber) . '_' . time() . '.' . $extension;

        // Store file
        $path = $file->storeAs($directory, $filename, 'private');

        return $path;
    }

    /**
     * Generate QR code for certificate verification
     */
    public function generateQrCode(Certificate $certificate): string
    {
        $qrData = [
            'type' => 'certificate_verification',
            'certificate_id' => $certificate->id,
            'verification_code' => $certificate->verification_code,
            'verification_url' => $certificate->verification_url,
            'issued_date' => $certificate->issue_date->format('Y-m-d'),
            'expiry_date' => $certificate->expiry_date?->format('Y-m-d'),
            'employee_name' => $certificate->trainingRecord->employee->name,
            'training_name' => $certificate->trainingRecord->trainingType->name
        ];

        // Generate QR code
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate(json_encode($qrData));

        // Store QR code file
        $year = date('Y');
        $month = date('m');
        $directory = "qr_codes/{$year}/{$month}";
        $filename = Str::slug($certificate->certificate_number) . '_qr.png';
        $path = "{$directory}/{$filename}";

        Storage::put($path, $qrCode);

        // Update certificate with QR code path
        $certificate->update(['qr_code_path' => $path]);

        return $path;
    }

    /**
     * Create certificate from training record
     */
    public function createCertificateFromTrainingRecord(TrainingRecord $trainingRecord, array $additionalData = []): Certificate
    {
        $trainingType = $trainingRecord->trainingType;

        // Calculate expiry date based on training type validity
        $expiryDate = null;
        if ($trainingType->validity_months) {
            $expiryDate = Carbon::parse($trainingRecord->completion_date ?? now())
                ->addMonths($trainingType->validity_months);
        }

        $certificateData = array_merge([
            'training_record_id' => $trainingRecord->id,
            'certificate_number' => Certificate::generateCertificateNumber($trainingType->code),
            'issued_by' => $trainingType->certification_authority ?? 'Gapura Training System',
            'issue_date' => $trainingRecord->completion_date ?? now(),
            'expiry_date' => $expiryDate,
            'is_verified' => true,
            'verification_date' => now(),
            'verified_by_id' => auth()->id()
        ], $additionalData);

        $certificate = Certificate::create($certificateData);

        // Generate QR code
        $this->generateQrCode($certificate);

        return $certificate;
    }

    /**
     * Batch create certificates for multiple training records
     */
    public function batchCreateCertificates(array $trainingRecordIds, array $commonData = []): array
    {
        $results = [];

        foreach ($trainingRecordIds as $recordId) {
            try {
                $trainingRecord = TrainingRecord::findOrFail($recordId);

                // Skip if certificate already exists
                if ($trainingRecord->certificates()->exists()) {
                    $results[] = [
                        'training_record_id' => $recordId,
                        'status' => 'skipped',
                        'message' => 'Certificate already exists'
                    ];
                    continue;
                }

                $certificate = $this->createCertificateFromTrainingRecord($trainingRecord, $commonData);

                $results[] = [
                    'training_record_id' => $recordId,
                    'certificate_id' => $certificate->id,
                    'status' => 'created',
                    'message' => 'Certificate created successfully'
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'training_record_id' => $recordId,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Renew certificate
     */
    public function renewCertificate(Certificate $certificate, array $renewalData = []): Certificate
    {
        $trainingType = $certificate->trainingRecord->trainingType;

        // Create new certificate with extended validity
        $newExpiryDate = null;
        if ($trainingType->validity_months) {
            $newExpiryDate = now()->addMonths($trainingType->validity_months);
        }

        $renewalCertificateData = array_merge([
            'training_record_id' => $certificate->training_record_id,
            'certificate_number' => Certificate::generateCertificateNumber($trainingType->code),
            'issued_by' => $certificate->issued_by,
            'issue_date' => now(),
            'expiry_date' => $newExpiryDate,
            'is_verified' => true,
            'verification_date' => now(),
            'verified_by_id' => auth()->id(),
            'notes' => 'Renewal of certificate: ' . $certificate->certificate_number
        ], $renewalData);

        $newCertificate = Certificate::create($renewalCertificateData);

        // Generate QR code for new certificate
        $this->generateQrCode($newCertificate);

        // Mark old certificate as superseded (optional)
        $certificate->update([
            'notes' => ($certificate->notes ? $certificate->notes . "\n" : '') .
                      'Superseded by certificate: ' . $newCertificate->certificate_number
        ]);

        return $newCertificate;
    }

    /**
     * Validate certificate authenticity
     */
    public function validateCertificate(Certificate $certificate): array
    {
        $validations = [];

        // Check if certificate exists and is valid
        $validations['exists'] = true;

        // Check verification status
        $validations['is_verified'] = $certificate->is_verified;

        // Check expiry status
        $validations['is_expired'] = !$certificate->isValid();
        $validations['days_until_expiry'] = $certificate->days_until_expiry;

        // Check digital signature (if implemented)
        $validations['signature_valid'] = !empty($certificate->digital_signature);

        // Check associated training record
        $validations['training_record_exists'] = $certificate->trainingRecord !== null;
        $validations['employee_exists'] = $certificate->trainingRecord?->employee !== null;

        // Check file existence
        $validations['file_exists'] = $certificate->certificate_file_path &&
                                    Storage::exists($certificate->certificate_file_path);

        // Check QR code existence
        $validations['qr_code_exists'] = $certificate->qr_code_path &&
                                       Storage::exists($certificate->qr_code_path);

        // Overall validity
        $validations['overall_valid'] = $validations['exists'] &&
                                      $validations['is_verified'] &&
                                      !$validations['is_expired'] &&
                                      $validations['training_record_exists'];

        return $validations;
    }

    /**
     * Export certificates to Excel
     */
    public function exportToExcel($certificates, string $filename = null): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filename = $filename ?: 'certificates_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new CertificatesExport($certificates), $filename);
    }

    /**
     * Get certificate statistics for an employee
     */
    public function getEmployeeCertificateStatistics(Employee $employee): array
    {
        $certificates = $employee->certificates();

        return [
            'total_certificates' => $certificates->count(),
            'active_certificates' => $certificates->active()->count(),
            'expired_certificates' => $certificates->expired()->count(),
            'expiring_soon_certificates' => $certificates->expiringSoon(30)->count(),
            'verified_certificates' => $certificates->verified()->count(),
            'latest_certificate' => $certificates->latest()->first(),
            'next_expiry' => $certificates->active()
                ->whereNotNull('expiry_date')
                ->orderBy('expiry_date')
                ->first()
        ];
    }

    /**
     * Get certificate statistics for a department
     */
    public function getDepartmentCertificateStatistics($departmentId): array
    {
        $certificates = Certificate::whereHas('trainingRecord.employee', function ($query) use ($departmentId) {
            $query->where('department_id', $departmentId);
        });

        $employeeCount = Employee::where('department_id', $departmentId)->count();

        return [
            'total_certificates' => $certificates->count(),
            'active_certificates' => $certificates->active()->count(),
            'expired_certificates' => $certificates->expired()->count(),
            'expiring_soon_certificates' => $certificates->expiringSoon(30)->count(),
            'verified_certificates' => $certificates->verified()->count(),
            'employee_count' => $employeeCount,
            'certificates_per_employee' => $employeeCount > 0 ?
                round($certificates->count() / $employeeCount, 2) : 0,
            'compliance_rate' => $this->calculateDepartmentComplianceRate($departmentId)
        ];
    }

    /**
     * Calculate compliance rate for a department
     */
    private function calculateDepartmentComplianceRate($departmentId): float
    {
        $employees = Employee::where('department_id', $departmentId)->where('status', 'active');
        $totalEmployees = $employees->count();

        if ($totalEmployees === 0) return 100.0;

        // Count employees with all required certificates
        $compliantEmployees = $employees->whereHas('trainingRecords', function ($query) {
            $query->whereHas('trainingType', function ($typeQuery) {
                $typeQuery->where('is_mandatory', true);
            })->whereHas('certificates', function ($certQuery) {
                $certQuery->active();
            });
        })->count();

        return round(($compliantEmployees / $totalEmployees) * 100, 2);
    }

    /**
     * Get certificates expiring in specific periods
     */
    public function getCertificatesExpiringInPeriods(): array
    {
        return [
            'expiring_in_7_days' => Certificate::expiringIn(7)->count(),
            'expiring_in_30_days' => Certificate::expiringSoon(30)->count(),
            'expiring_in_60_days' => Certificate::expiringSoon(60)->count(),
            'expiring_in_90_days' => Certificate::expiringSoon(90)->count(),
            'expired' => Certificate::expired()->count()
        ];
    }

    /**
     * Generate certificate compliance report
     */
    public function generateComplianceReport(array $filters = []): array
    {
        $query = Certificate::with([
            'trainingRecord.employee.department',
            'trainingRecord.trainingType.category'
        ]);

        // Apply filters
        if (isset($filters['department_ids'])) {
            $query->whereHas('trainingRecord.employee', function ($q) use ($filters) {
                $q->whereIn('department_id', $filters['department_ids']);
            });
        }

        if (isset($filters['training_type_ids'])) {
            $query->whereHas('trainingRecord', function ($q) use ($filters) {
                $q->whereIn('training_type_id', $filters['training_type_ids']);
            });
        }

        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $query->active();
                    break;
                case 'expired':
                    $query->expired();
                    break;
                case 'expiring_soon':
                    $query->expiringSoon(30);
                    break;
            }
        }

        $certificates = $query->get();

        // Group by various dimensions
        $byDepartment = $certificates->groupBy('trainingRecord.employee.department.name');
        $byTrainingType = $certificates->groupBy('trainingRecord.trainingType.name');
        $byStatus = $certificates->groupBy('status');

        return [
            'total_certificates' => $certificates->count(),
            'by_department' => $byDepartment->map->count(),
            'by_training_type' => $byTrainingType->map->count(),
            'by_status' => $byStatus->map->count(),
            'summary' => [
                'active' => $certificates->where('status', 'active')->count(),
                'expired' => $certificates->where('status', 'expired')->count(),
                'expiring_soon' => $certificates->where('status', 'expiring_soon')->count(),
                'verified' => $certificates->where('is_verified', true)->count()
            ]
        ];
    }

    /**
     * Auto-create certificates for completed training records without certificates
     */
    public function autoCreateMissingCertificates(): array
    {
        $trainingRecords = TrainingRecord::where('status', 'completed')
            ->whereDoesntHave('certificates')
            ->whereHas('trainingType', function ($query) {
                $query->where('requires_certification', true);
            })
            ->with(['employee', 'trainingType'])
            ->get();

        $results = [];

        foreach ($trainingRecords as $record) {
            try {
                $certificate = $this->createCertificateFromTrainingRecord($record);

                $results[] = [
                    'training_record_id' => $record->id,
                    'certificate_id' => $certificate->id,
                    'employee_name' => $record->employee->name,
                    'training_name' => $record->trainingType->name,
                    'status' => 'created'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'training_record_id' => $record->id,
                    'employee_name' => $record->employee->name,
                    'training_name' => $record->trainingType->name,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
