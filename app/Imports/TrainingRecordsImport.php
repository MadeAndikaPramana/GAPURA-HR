<?php
// app/Imports/EnhancedTrainingRecordImport.php

namespace App\Imports;

use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Models\Certificate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TrainingRecordImport implements
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
        'certificates_created' => 0,
        'skipped' => 0,
        'errors' => 0,
        'error_details' => [],
        'processing_time' => 0
    ];

    private $employees;
    private $trainingTypes;
    private $autoCreateCertificates;
    private $defaultProviderId;
    private $startTime;

    public function __construct(bool $autoCreateCertificates = true, $defaultProviderId = null)
    {
        $this->autoCreateCertificates = $autoCreateCertificates;
        $this->defaultProviderId = $defaultProviderId;
        $this->startTime = microtime(true);

        // Pre-load data untuk performa
        $this->employees = Employee::all()->keyBy('employee_id');
        $this->trainingTypes = TrainingType::all()->keyBy('code');
    }

    public function model(array $row)
    {
        $this->importResults['total_rows']++;

        try {
            // Clean and normalize data
            $cleanRow = $this->cleanRowData($row);

            // Validate required fields
            if (empty($cleanRow['employee_id']) || empty($cleanRow['training_type_code'])) {
                $this->importResults['skipped']++;
                return null;
            }

            // Resolve employee
            $employee = $this->resolveEmployee($cleanRow['employee_id']);
            if (!$employee) {
                throw new \Exception("Employee not found: {$cleanRow['employee_id']}");
            }

            // Resolve training type
            $trainingType = $this->resolveTrainingType($cleanRow['training_type_code']);
            if (!$trainingType) {
                throw new \Exception("Training type not found: {$cleanRow['training_type_code']}");
            }

            // Parse dates
            $trainingDate = $this->parseDate($cleanRow['training_date']);
            $completionDate = $this->parseDate($cleanRow['completion_date']);
            $issueDate = $completionDate ?: $trainingDate ?: Carbon::now();

            // Calculate expiry date
            $expiryDate = null;
            if ($issueDate && $trainingType->validity_months) {
                $expiryDate = $issueDate->copy()->addMonths($trainingType->validity_months);
            }

            // Determine status
            $status = $this->determineStatus($completionDate, $expiryDate);

            // Generate certificate number if not provided
            $certificateNumber = $cleanRow['certificate_number']
                ?: $this->generateCertificateNumber($trainingType, $employee);

            // Check for existing record
            $existingRecord = TrainingRecord::where('employee_id', $employee->id)
                ->where('training_type_id', $trainingType->id)
                ->where('certificate_number', $certificateNumber)
                ->first();

            if ($existingRecord) {
                $this->updateTrainingRecord($existingRecord, $cleanRow, $status, $expiryDate);
                $this->importResults['updated']++;
                return null;
            }

            // Create new training record
            $trainingRecord = $this->createTrainingRecord(
                $employee,
                $trainingType,
                $cleanRow,
                $certificateNumber,
                $issueDate,
                $expiryDate,
                $status
            );

            $this->importResults['created']++;

            // Auto-create certificate if enabled
            if ($this->autoCreateCertificates && $status !== 'expired') {
                $this->createCertificate($trainingRecord, $cleanRow);
                $this->importResults['certificates_created']++;
            }

            return $trainingRecord;

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->importResults['error_details'][] = [
                'row' => $this->importResults['total_rows'],
                'employee_id' => $cleanRow['employee_id'] ?? 'N/A',
                'training_type' => $cleanRow['training_type_code'] ?? 'N/A',
                'error' => $e->getMessage()
            ];

            Log::error('Training record import error', [
                'row' => $this->importResults['total_rows'],
                'data' => $row,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|string',
            'training_type_code' => 'required|string',
            'training_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'certificate_number' => 'nullable|string',
            'issuer' => 'nullable|string|max:255',
            'score' => 'nullable|numeric|min:0|max:100',
            'training_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ];
    }

    public function customValidationMessages()
    {
        return [
            'employee_id.required' => 'Employee ID is required',
            'training_type_code.required' => 'Training type code is required',
            'score.numeric' => 'Score must be a number',
            'score.between' => 'Score must be between 0 and 100'
        ];
    }

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

    public function onError(\Throwable $e)
    {
        $this->importResults['errors']++;
        Log::error('Training record import processing error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getResults(): array
    {
        $this->importResults['processing_time'] = round(microtime(true) - $this->startTime, 2);
        return $this->importResults;
    }

    private function cleanRowData(array $row): array
    {
        return [
            'employee_id' => trim(strtoupper($row['employee_id'] ?? '')),
            'training_type_code' => trim(strtoupper($row['training_type_code'] ?? $row['training_type'] ?? $row['training_code'] ?? '')),
            'certificate_number' => trim($row['certificate_number'] ?? ''),
            'issuer' => trim($row['issuer'] ?? $row['issued_by'] ?? ''),
            'training_date' => $row['training_date'] ?? null,
            'completion_date' => $row['completion_date'] ?? $row['issue_date'] ?? null,
            'score' => $this->parseNumeric($row['score'] ?? null),
            'training_hours' => $this->parseNumeric($row['training_hours'] ?? $row['hours'] ?? null),
            'cost' => $this->parseNumeric($row['cost'] ?? $row['training_cost'] ?? null),
            'location' => trim($row['location'] ?? $row['training_location'] ?? ''),
            'instructor_name' => trim($row['instructor_name'] ?? $row['instructor'] ?? ''),
            'notes' => trim($row['notes'] ?? $row['remarks'] ?? $row['comments'] ?? '')
        ];
    }

    private function resolveEmployee(string $employeeId): ?Employee
    {
        return $this->employees->get($employeeId);
    }

    private function resolveTrainingType(string $trainingIdentifier): ?TrainingType
    {
        // Try by code first
        if (isset($this->trainingTypes[$trainingIdentifier])) {
            return $this->trainingTypes[$trainingIdentifier];
        }

        // Try by name (case insensitive)
        return $this->trainingTypes->first(function($type) use ($trainingIdentifier) {
            return strcasecmp($type->name, $trainingIdentifier) === 0;
        });
    }

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

    private function parseNumeric($value): ?float
    {
        if (empty($value) || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function determineStatus(?Carbon $completionDate, ?Carbon $expiryDate): string
    {
        if (!$completionDate) {
            return 'registered';
        }

        if (!$expiryDate) {
            return 'completed';
        }

        $daysUntilExpiry = $expiryDate->diffInDays(Carbon::now(), false);

        if ($daysUntilExpiry <= 0) {
            return 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            return 'expiring_soon';
        } else {
            return 'active';
        }
    }

    private function generateCertificateNumber(TrainingType $trainingType, Employee $employee): string
    {
        $year = Carbon::now()->year;
        $month = Carbon::now()->format('m');

        $prefix = sprintf(
            '%s/%s/%s%s',
            $trainingType->code,
            $employee->department->code ?? 'GEN',
            $year,
            $month
        );

        // Get next sequence number
        $lastRecord = TrainingRecord::where('certificate_number', 'LIKE', $prefix . '/%')
            ->orderBy('certificate_number', 'desc')
            ->first();

        $sequence = 1;
        if ($lastRecord) {
            $parts = explode('/', $lastRecord->certificate_number);
            $lastSequence = intval(end($parts));
            $sequence = $lastSequence + 1;
        }

        return sprintf('%s/%03d', $prefix, $sequence);
    }

    private function createTrainingRecord(
        Employee $employee,
        TrainingType $trainingType,
        array $cleanRow,
        string $certificateNumber,
        Carbon $issueDate,
        ?Carbon $expiryDate,
        string $status
    ): TrainingRecord {
        return TrainingRecord::create([
            'employee_id' => $employee->id,
            'training_type_id' => $trainingType->id,
            'training_provider_id' => $this->defaultProviderId,
            'certificate_number' => $certificateNumber,
            'issuer' => $cleanRow['issuer'] ?: 'PT. Gapura Angkasa',
            'training_date' => $this->parseDate($cleanRow['training_date']),
            'completion_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'status' => $status,
            'score' => $cleanRow['score'],
            'passing_score' => $trainingType->pass_criteria['minimum_score'] ?? 70,
            'training_hours' => $cleanRow['training_hours'] ?? $trainingType->duration_hours ?? 0,
            'cost' => $cleanRow['cost'] ?? $trainingType->cost_per_person ?? 0,
            'location' => $cleanRow['location'],
            'instructor_name' => $cleanRow['instructor_name'],
            'notes' => $cleanRow['notes']
        ]);
    }

    private function updateTrainingRecord(
        TrainingRecord $record,
        array $cleanRow,
        string $status,
        ?Carbon $expiryDate
    ): void {
        $updateData = [
            'status' => $status,
            'expiry_date' => $expiryDate
        ];

        // Update non-empty values
        if (!empty($cleanRow['issuer'])) {
            $updateData['issuer'] = $cleanRow['issuer'];
        }

        if ($cleanRow['score'] !== null) {
            $updateData['score'] = $cleanRow['score'];
        }

        if ($cleanRow['training_hours'] !== null) {
            $updateData['training_hours'] = $cleanRow['training_hours'];
        }

        if ($cleanRow['cost'] !== null) {
            $updateData['cost'] = $cleanRow['cost'];
        }

        if (!empty($cleanRow['location'])) {
            $updateData['location'] = $cleanRow['location'];
        }

        if (!empty($cleanRow['instructor_name'])) {
            $updateData['instructor_name'] = $cleanRow['instructor_name'];
        }

        if (!empty($cleanRow['notes'])) {
            $updateData['notes'] = $cleanRow['notes'];
        }

        $record->update($updateData);
    }

    private function createCertificate(TrainingRecord $trainingRecord, array $cleanRow): void
    {
        // Only create certificate if Certificate model exists and training is completed
        if (!class_exists(Certificate::class) || $trainingRecord->status === 'registered') {
            return;
        }

        Certificate::create([
            'training_record_id' => $trainingRecord->id,
            'certificate_number' => $trainingRecord->certificate_number,
            'issued_by' => $trainingRecord->issuer,
            'issue_date' => $trainingRecord->completion_date,
            'expiry_date' => $trainingRecord->expiry_date,
            'is_verified' => true,
            'verification_date' => Carbon::now(),
            'verified_by_id' => auth()->id()
        ]);
    }
}
