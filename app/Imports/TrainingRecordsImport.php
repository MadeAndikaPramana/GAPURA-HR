<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\TrainingProvider;
use App\Services\CertificateService;
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
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TrainingRecordsImport implements
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

    protected $autoCreateCertificates;
    protected $defaultProviderId;
    protected $certificateService;
    protected $results = [
        'created' => 0,
        'updated' => 0,
        'errors' => 0,
        'certificates_created' => 0,
        'error_details' => []
    ];

    public function __construct($autoCreateCertificates = false, $defaultProviderId = null)
    {
        $this->autoCreateCertificates = $autoCreateCertificates;
        $this->defaultProviderId = $defaultProviderId;
        $this->certificateService = app(CertificateService::class);
    }

    /**
     * Transform each row into a model
     */
    public function model(array $row)
    {
        try {
            // Clean and validate the row data
            $cleanRow = $this->cleanRowData($row);

            // Find employee
            $employee = $this->resolveEmployee($cleanRow['employee_id'] ?? null);
            if (!$employee) {
                throw new \Exception("Employee not found: " . ($cleanRow['employee_id'] ?? 'N/A'));
            }

            // Find training type
            $trainingType = $this->resolveTrainingType($cleanRow['training_type_code'] ?? null);
            if (!$trainingType) {
                throw new \Exception("Training type not found: " . ($cleanRow['training_type_code'] ?? 'N/A'));
            }

            // Resolve training provider
            $trainingProvider = $this->resolveTrainingProvider($cleanRow['training_provider'] ?? null);

            // Parse dates
            $trainingDate = $this->parseDate($cleanRow['training_date'] ?? null);
            $completionDate = $this->parseDate($cleanRow['completion_date'] ?? null);

            // Calculate expiry date if completion date is provided
            $expiryDate = null;
            if ($completionDate && $trainingType->validity_months) {
                $expiryDate = Carbon::parse($completionDate)->addMonths($trainingType->validity_months);
            }

            // Determine status
            $status = $this->determineStatus($cleanRow, $completionDate);

            // Prepare training record data
            $trainingData = [
                'employee_id' => $employee->id,
                'training_type_id' => $trainingType->id,
                'training_provider_id' => $trainingProvider?->id ?? $this->defaultProviderId,
                'batch_number' => $cleanRow['batch_number'] ?? null,
                'training_date' => $trainingDate,
                'completion_date' => $completionDate,
                'expiry_date' => $expiryDate,
                'status' => $status,
                'score' => $this->parseScore($cleanRow['score'] ?? null),
                'passing_score' => $trainingType->pass_criteria['minimum_score'] ?? 70,
                'training_hours' => $cleanRow['training_hours'] ?? $trainingType->duration_hours ?? 0,
                'cost' => $this->parseCost($cleanRow['cost'] ?? null) ?? $trainingType->cost_per_person ?? 0,
                'location' => $cleanRow['location'] ?? null,
                'instructor_name' => $cleanRow['instructor_name'] ?? null,
                'notes' => $cleanRow['notes'] ?? null,
                'created_by_id' => auth()->id()
            ];

            // Check for existing record
            $existingRecord = TrainingRecord::where('employee_id', $employee->id)
                ->where('training_type_id', $trainingType->id)
                ->where('completion_date', $completionDate)
                ->first();

            if ($existingRecord) {
                $existingRecord->update($trainingData);
                $this->results['updated']++;

                Log::info('Training record updated during import', [
                    'employee_id' => $employee->employee_id,
                    'training_type' => $trainingType->code,
                    'completion_date' => $completionDate
                ]);

                $trainingRecord = $existingRecord;
            } else {
                $trainingRecord = new TrainingRecord($trainingData);
                $this->results['created']++;

                Log::info('Training record created during import', [
                    'employee_id' => $employee->employee_id,
                    'training_type' => $trainingType->code,
                    'completion_date' => $completionDate
                ]);
            }

            // Auto-create certificate if requested and training is completed
            if ($this->autoCreateCertificates &&
                $status === 'completed' &&
                $trainingType->requires_certification) {

                $this->createCertificateForRecord($trainingRecord, $cleanRow);
            }

            return $existingRecord ? null : $trainingRecord;

        } catch (\Exception $e) {
            $this->results['errors']++;
            $this->results['error_details'][] = [
                'row' => $row,
                'error' => $e->getMessage()
            ];

            Log::error('Training record import row failed', [
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
            'employee_id' => 'required|string|exists:employees,employee_id',
            'training_type_code' => 'required|string|exists:training_types,code',
            'training_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'score' => 'nullable|numeric|min:0|max:100',
            'cost' => 'nullable|numeric|min:0',
            'training_hours' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'batch_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string'
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'employee_id.required' => 'Employee ID is required.',
            'employee_id.exists' => 'Employee not found.',
            'training_type_code.required' => 'Training type code is required.',
            'training_type_code.exists' => 'Training type not found.',
            'training_date.date' => 'Invalid training date format.',
            'completion_date.date' => 'Invalid completion date format.',
            'score.numeric' => 'Score must be a number.',
            'score.between' => 'Score must be between 0 and 100.',
            'cost.numeric' => 'Cost must be a number.',
            'training_hours.numeric' => 'Training hours must be a number.'
        ];
    }

    /**
     * Configure batch size for performance
     */
    public function batchSize(): int
    {
        return 50;
    }

    /**
     * Configure chunk size for memory management
     */
    public function chunkSize(): int
    {
        return 250;
    }

    /**
     * Clean and normalize row data
     */
    protected function cleanRowData(array $row): array
    {
        $cleaned = [];

        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeKey($key);
            $cleanedValue = is_string($value) ? trim($value) : $value;
            $cleanedValue = empty($cleanedValue) ? null : $cleanedValue;
            $cleaned[$normalizedKey] = $cleanedValue;
        }

        return $cleaned;
    }

    /**
     * Normalize column keys
     */
    protected function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = str_replace([' ', '-', '.'], '_', $key);

        $keyMappings = [
            'emp_id' => 'employee_id',
            'staff_id' => 'employee_id',
            'training_code' => 'training_type_code',
            'course_code' => 'training_type_code',
            'training_type' => 'training_type_code',
            'course_name' => 'training_type_code',
            'provider' => 'training_provider',
            'provider_name' => 'training_provider',
            'training_provider_name' => 'training_provider',
            'start_date' => 'training_date',
            'course_date' => 'training_date',
            'end_date' => 'completion_date',
            'completed_date' => 'completion_date',
            'finish_date' => 'completion_date',
            'final_score' => 'score',
            'grade' => 'score',
            'result' => 'score',
            'training_cost' => 'cost',
            'course_cost' => 'cost',
            'fee' => 'cost',
            'duration' => 'training_hours',
            'hours' => 'training_hours',
            'venue' => 'location',
            'place' => 'location',
            'trainer' => 'instructor_name',
            'facilitator' => 'instructor_name',
            'instructor' => 'instructor_name',
            'batch' => 'batch_number',
            'batch_no' => 'batch_number',
            'class' => 'batch_number',
            'remarks' => 'notes',
            'comment' => 'notes',
            'comments' => 'notes'
        ];

        return $keyMappings[$key] ?? $key;
    }

    /**
     * Resolve employee from employee ID
     */
    protected function resolveEmployee(?string $employeeId): ?Employee
    {
        if (empty($employeeId)) {
            return null;
        }

        return Employee::where('employee_id', $employeeId)->first();
    }

    /**
     * Resolve training type from code or name
     */
    protected function resolveTrainingType(?string $identifier): ?TrainingType
    {
        if (empty($identifier)) {
            return null;
        }

        // Try by code first
        $trainingType = TrainingType::where('code', strtoupper($identifier))->first();

        if (!$trainingType) {
            // Try by name (case insensitive)
            $trainingType = TrainingType::whereRaw('LOWER(name) = ?', [strtolower($identifier)])->first();
        }

        return $trainingType;
    }

    /**
     * Resolve training provider from name
     */
    protected function resolveTrainingProvider(?string $providerName): ?TrainingProvider
    {
        if (empty($providerName)) {
            return null;
        }

        // Try exact match first
        $provider = TrainingProvider::where('name', $providerName)->first();

        if (!$provider) {
            // Try case insensitive match
            $provider = TrainingProvider::whereRaw('LOWER(name) = ?', [strtolower($providerName)])->first();
        }

        if (!$provider) {
            // Try partial match
            $provider = TrainingProvider::where('name', 'like', "%{$providerName}%")->first();
        }

        return $provider;
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
                'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'Y/m/d',
                'd.m.Y', 'Y.m.d', 'F j, Y', 'j F Y', 'd M Y', 'M d, Y'
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
            Log::warning('Failed to parse date during training record import', [
                'date_string' => $dateString,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse score value
     */
    protected function parseScore(?string $scoreString): ?float
    {
        if (empty($scoreString)) {
            return null;
        }

        // Remove percentage sign if present
        $scoreString = str_replace('%', '', $scoreString);

        // Handle pass/fail text
        if (in_array(strtolower($scoreString), ['pass', 'passed', 'lulus'])) {
            return 75.0; // Default passing score
        }

        if (in_array(strtolower($scoreString), ['fail', 'failed', 'tidak lulus'])) {
            return 50.0; // Default failing score
        }

        return is_numeric($scoreString) ? (float) $scoreString : null;
    }

    /**
     * Parse cost value
     */
    protected function parseCost(?string $costString): ?float
    {
        if (empty($costString)) {
            return null;
        }

        // Remove currency symbols and formatting
        $costString = preg_replace('/[^\d.,]/', '', $costString);
        $costString = str_replace(',', '', $costString);

        return is_numeric($costString) ? (float) $costString : null;
    }

    /**
     * Determine training status
     */
    protected function determineStatus(array $row, ?string $completionDate): string
    {
        // Check if explicitly provided
        if (!empty($row['status'])) {
            $status = strtolower($row['status']);
            $validStatuses = ['registered', 'in_progress', 'completed', 'failed', 'cancelled'];

            if (in_array($status, $validStatuses)) {
                return $status;
            }
        }

        // Determine from other fields
        if (!empty($completionDate)) {
            $score = $this->parseScore($row['score'] ?? null);

            if ($score !== null) {
                return $score >= 70 ? 'completed' : 'failed';
            }

            return 'completed';
        }

        if (!empty($row['training_date'])) {
            $trainingDate = $this->parseDate($row['training_date']);
            if ($trainingDate && Carbon::parse($trainingDate)->isPast()) {
                return 'in_progress';
            }
        }

        return 'registered';
    }

    /**
     * Create certificate for completed training record
     */
    protected function createCertificateForRecord($trainingRecord, array $rowData)
    {
        try {
            if ($trainingRecord instanceof TrainingRecord) {
                // Save the record first if it's new
                if (!$trainingRecord->exists) {
                    $trainingRecord->save();
                }
            }

            $certificateData = [];

            // Add certificate-specific data from row if available
            if (!empty($rowData['certificate_number'])) {
                $certificateData['certificate_number'] = $rowData['certificate_number'];
            }

            if (!empty($rowData['issued_by'])) {
                $certificateData['issued_by'] = $rowData['issued_by'];
            }

            $certificate = $this->certificateService->createCertificateFromTrainingRecord($trainingRecord, $certificateData);

            $this->results['certificates_created']++;

            Log::info('Certificate auto-created during training record import', [
                'training_record_id' => $trainingRecord->id,
                'certificate_id' => $certificate->id,
                'employee_id' => $trainingRecord->employee->employee_id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create certificate during import', [
                'training_record_data' => $trainingRecord->toArray(),
                'error' => $e->getMessage()
            ]);
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

            Log::warning('Training record import validation failure', [
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

        Log::error('Training record import general error', [
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
            'Import completed: %d created, %d updated, %d certificates created, %d errors',
            $this->results['created'],
            $this->results['updated'],
            $this->results['certificates_created'],
            $this->results['errors']
        );
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

            $headers = array_shift($rows);
            $previewData = array_slice($rows, 0, $limit);

            return [
                'headers' => $headers,
                'data' => $previewData,
                'total_rows' => count($rows),
                'suggested_mappings' => static::getSuggestedMappings($headers)
            ];

        } catch (\Exception $e) {
            return [
                'headers' => [],
                'data' => [],
                'error' => 'Failed to read file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get suggested column mappings
     */
    public static function getSuggestedMappings(array $headers): array
    {
        $mappings = [];
        $import = new static();

        foreach ($headers as $index => $header) {
            $normalizedKey = $import->normalizeKey($header);
            $mappings[$index] = [
                'original' => $header,
                'suggested' => $normalizedKey,
                'confidence' => static::getMappingConfidence($header, $normalizedKey)
            ];
        }

        return $mappings;
    }

    /**
     * Calculate mapping confidence
     */
    protected static function getMappingConfidence(string $original, string $suggested): string
    {
        $original = strtolower($original);
        $suggested = strtolower($suggested);

        if ($original === $suggested) return 'high';
        if (str_contains($original, $suggested) || str_contains($suggested, $original)) return 'medium';
        return 'low';
    }
}
