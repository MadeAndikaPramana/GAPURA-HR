<?php

namespace App\Imports;

use App\Models\Department;
use App\Services\EmployeeContainerService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

abstract class BaseExcelImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable;

    protected array $importResults = [
        'batch_id' => null,
        'import_type' => null,
        'start_time' => null,
        'end_time' => null,
        'total_rows' => 0,
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'warnings' => 0,
        'processing_time' => 0,
        'memory_usage' => 0,
        'details' => [
            'success' => [],
            'errors' => [],
            'warnings' => [],
            'skipped' => [],
            'created_items' => [],
            'updated_items' => []
        ]
    ];

    protected bool $dryRun;
    protected bool $updateExisting;
    protected bool $createMissing;
    protected bool $autoCreateContainers;
    protected array $options;
    protected EmployeeContainerService $containerService;

    public function __construct(array $options = [])
    {
        $this->dryRun = $options['dry_run'] ?? false;
        $this->updateExisting = $options['update_existing'] ?? false;
        $this->createMissing = $options['create_missing'] ?? true;
        $this->autoCreateContainers = $options['auto_create_containers'] ?? true;
        $this->options = $options;

        $this->containerService = app(EmployeeContainerService::class);

        $this->importResults['batch_id'] = $this->generateBatchId();
        $this->importResults['import_type'] = static::class;
        $this->importResults['start_time'] = now();
    }

    /**
     * Process the Excel collection - main entry point
     */
    public function collection(Collection $collection): void
    {
        $this->importResults['total_rows'] = $collection->count();

        $this->logImportStart();

        if ($this->dryRun) {
            $this->processDryRun($collection);
        } else {
            $this->processRealImport($collection);
        }

        $this->finalizeImport();
    }

    /**
     * Process dry run - validate but don't save
     */
    protected function processDryRun(Collection $collection): void
    {
        foreach ($collection as $rowIndex => $row) {
            $this->validateRow($row->toArray(), $rowIndex + 2);
        }
    }

    /**
     * Process real import with transaction
     */
    protected function processRealImport(Collection $collection): void
    {
        DB::beginTransaction();

        try {
            foreach ($collection as $rowIndex => $row) {
                $this->processRow($row->toArray(), $rowIndex + 2);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            $this->logImportError($e);
            throw $e;
        }
    }

    /**
     * Finalize import and calculate metrics
     */
    protected function finalizeImport(): void
    {
        $this->importResults['end_time'] = now();
        $this->importResults['processing_time'] = $this->importResults['start_time']->diffInSeconds($this->importResults['end_time']);
        $this->importResults['memory_usage'] = memory_get_peak_usage(true);

        $this->logImportCompletion();
    }

    /**
     * Abstract method for processing individual rows
     */
    abstract protected function processRow(array $rowData, int $rowNumber): void;

    /**
     * Abstract method for defining column mappings
     */
    abstract protected function getColumnMapping(): array;

    /**
     * Abstract method for validation rules
     */
    abstract public function rules(): array;

    /**
     * Validate row without processing
     */
    protected function validateRow(array $rowData, int $rowNumber): array
    {
        try {
            $normalizedData = $this->normalizeRowData($rowData);

            // Skip empty rows
            if ($this->isEmptyRow($normalizedData)) {
                $this->importResults['skipped']++;
                return ['status' => 'skipped', 'reason' => 'empty_row'];
            }

            // Validate required fields
            $validation = $this->validateRequiredFields($normalizedData, $rowNumber);
            if (!$validation['valid']) {
                $this->importResults['errors']++;
                return ['status' => 'error', 'errors' => $validation['errors']];
            }

            return ['status' => 'valid', 'data' => $normalizedData];

        } catch (\Exception $e) {
            $this->importResults['errors']++;
            $this->logRowError($rowNumber, $e->getMessage(), $rowData);
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Normalize row data using column mapping
     */
    protected function normalizeRowData(array $rowData): array
    {
        $normalized = [];
        $columnMapping = $this->getColumnMapping();

        foreach ($rowData as $key => $value) {
            $normalizedKey = $this->normalizeColumnName($key);

            if (isset($columnMapping[$normalizedKey])) {
                $mappedKey = $columnMapping[$normalizedKey];
                $normalized[$mappedKey] = $this->cleanValue($value);
            }
        }

        return $normalized;
    }

    /**
     * Normalize column names for consistent mapping
     */
    protected function normalizeColumnName(string $key): string
    {
        $normalized = strtolower(trim($key));
        $normalized = str_replace([' ', '_', '-', '.'], '_', $normalized);
        $normalized = preg_replace('/[^a-z0-9_]/', '', $normalized);
        return $normalized;
    }

    /**
     * Clean and normalize cell values
     */
    protected function cleanValue($value)
    {
        if (is_string($value)) {
            return trim($value);
        }
        return $value;
    }

    /**
     * Check if row is empty
     */
    protected function isEmptyRow(array $data): bool
    {
        $requiredFields = $this->getRequiredFields();

        foreach ($requiredFields as $field) {
            if (!empty($data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get required fields for empty row detection
     */
    protected function getRequiredFields(): array
    {
        return ['name', 'employee_id', 'nip'];
    }

    /**
     * Validate required fields
     */
    protected function validateRequiredFields(array $data, int $rowNumber): array
    {
        $errors = [];
        $requiredFields = $this->getRequiredFieldsValidation();

        foreach ($requiredFields as $field => $rules) {
            if (empty($data[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get required fields with validation rules
     */
    protected function getRequiredFieldsValidation(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Find or create department
     */
    protected function findOrCreateDepartment(string $departmentName): ?Department
    {
        if (empty(trim($departmentName))) {
            return null;
        }

        return Department::firstOrCreate(
            ['name' => trim($departmentName)],
            [
                'code' => $this->generateDepartmentCode($departmentName),
                'is_active' => true,
                'description' => 'Auto-created during import'
            ]
        );
    }

    /**
     * Generate department code from name
     */
    protected function generateDepartmentCode(string $name): string
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6));
        return $code ?: 'DEPT' . rand(100, 999);
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate($dateValue): ?Carbon
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            // Handle Excel serial dates
            if (is_numeric($dateValue)) {
                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                return Carbon::createFromFormat('Y-m-d', $excelDate->format('Y-m-d'));
            }

            // Handle string dates
            if (is_string($dateValue)) {
                // Try common date formats
                $formats = [
                    'Y-m-d',
                    'd/m/Y',
                    'd-m-Y',
                    'm/d/Y',
                    'Y/m/d',
                    'd.m.Y',
                    'Y.m.d'
                ];

                foreach ($formats as $format) {
                    try {
                        return Carbon::createFromFormat($format, $dateValue);
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                // Fallback to Carbon's parser
                return Carbon::parse($dateValue);
            }

            return null;
        } catch (\Exception $e) {
            $this->logWarning("Could not parse date: {$dateValue}");
            return null;
        }
    }

    /**
     * Parse numeric values
     */
    protected function parseNumeric($value): ?float
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove non-numeric characters except decimal point and comma
        $cleaned = preg_replace('/[^\d.,]/', '', $value);

        // Handle comma as decimal separator
        if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') === false) {
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
            // Both comma and dot present, assume comma is thousands separator
            $cleaned = str_replace(',', '', $cleaned);
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Parse integer values
     */
    protected function parseInteger($value): ?int
    {
        $numeric = $this->parseNumeric($value);
        return $numeric !== null ? (int) $numeric : null;
    }

    /**
     * Create employee container if auto-creation is enabled
     */
    protected function createEmployeeContainer(int $employeeId): void
    {
        if (!$this->autoCreateContainers) {
            return;
        }

        try {
            $this->containerService->createContainerForEmployee($employeeId);
            $this->logInfo("Created container for employee {$employeeId}");
        } catch (\Exception $e) {
            $this->logWarning("Failed to create container for employee {$employeeId}: {$e->getMessage()}");
        }
    }

    /**
     * Generate unique batch ID
     */
    protected function generateBatchId(): string
    {
        return date('YmdHis') . '_' . substr(md5(uniqid()), 0, 8);
    }

    /**
     * Logging methods
     */
    protected function logImportStart(): void
    {
        Log::info("Starting {$this->importResults['import_type']} import", [
            'batch_id' => $this->importResults['batch_id'],
            'total_rows' => $this->importResults['total_rows'],
            'options' => $this->options
        ]);
    }

    protected function logImportCompletion(): void
    {
        Log::info("Completed {$this->importResults['import_type']} import", $this->importResults);
    }

    protected function logImportError(\Exception $e): void
    {
        Log::error("Import failed for {$this->importResults['import_type']}", [
            'batch_id' => $this->importResults['batch_id'],
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    protected function logRowError(int $rowNumber, string $error, array $data = []): void
    {
        $this->importResults['details']['errors'][] = [
            'row' => $rowNumber,
            'error' => $error,
            'data' => $data,
            'timestamp' => now()
        ];

        Log::warning("Row {$rowNumber} error in {$this->importResults['import_type']}", [
            'batch_id' => $this->importResults['batch_id'],
            'error' => $error,
            'data' => $data
        ]);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        $this->importResults['warnings']++;
        $this->importResults['details']['warnings'][] = [
            'message' => $message,
            'context' => $context,
            'timestamp' => now()
        ];

        Log::warning($message, array_merge($context, [
            'batch_id' => $this->importResults['batch_id'],
            'import_type' => $this->importResults['import_type']
        ]));
    }

    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, array_merge($context, [
            'batch_id' => $this->importResults['batch_id'],
            'import_type' => $this->importResults['import_type']
        ]));
    }

    protected function logSuccess(int $rowNumber, string $action, array $details = []): void
    {
        $this->importResults['details']['success'][] = [
            'row' => $rowNumber,
            'action' => $action,
            'details' => $details,
            'timestamp' => now()
        ];
    }

    /**
     * Error handling for Excel validation
     */
    public function onError(Throwable $error): void
    {
        $this->importResults['errors']++;
        $this->logImportError(new \Exception($error->getMessage()));
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->importResults['errors']++;
            $this->logRowError(
                $failure->row(),
                implode(', ', $failure->errors()),
                $failure->values()
            );
        }
    }

    /**
     * Get import results
     */
    public function getImportResults(): array
    {
        return $this->importResults;
    }

    /**
     * Generate comprehensive import report
     */
    public function generateReport(): string
    {
        $results = $this->importResults;
        $importType = class_basename($this->importResults['import_type']);

        $report = "{$importType} Import Report\n";
        $report .= str_repeat("=", strlen($importType) + 15) . "\n\n";

        $report .= "Batch Information:\n";
        $report .= "- Batch ID: {$results['batch_id']}\n";
        $report .= "- Import Type: {$importType}\n";
        $report .= "- Start Time: {$results['start_time']}\n";
        $report .= "- End Time: {$results['end_time']}\n";
        $report .= "- Processing Time: {$results['processing_time']} seconds\n";
        $report .= "- Memory Usage: " . number_format($results['memory_usage'] / 1024 / 1024, 2) . " MB\n";
        $report .= "- Mode: " . ($this->dryRun ? 'DRY RUN' : 'LIVE IMPORT') . "\n\n";

        $report .= "Summary:\n";
        $report .= "- Total rows: {$results['total_rows']}\n";
        $report .= "- Successfully processed: {$results['processed']}\n";
        $report .= "- Records created: {$results['created']}\n";
        $report .= "- Records updated: {$results['updated']}\n";
        $report .= "- Rows skipped: {$results['skipped']}\n";
        $report .= "- Errors: {$results['errors']}\n";
        $report .= "- Warnings: {$results['warnings']}\n\n";

        if (!empty($results['details']['errors'])) {
            $report .= "Errors:\n";
            foreach (array_slice($results['details']['errors'], 0, 20) as $error) {
                $report .= "- Row {$error['row']}: {$error['error']}\n";
            }
            if (count($results['details']['errors']) > 20) {
                $report .= "... and " . (count($results['details']['errors']) - 20) . " more errors\n";
            }
            $report .= "\n";
        }

        if (!empty($results['details']['warnings'])) {
            $report .= "Warnings:\n";
            foreach (array_slice($results['details']['warnings'], 0, 10) as $warning) {
                $report .= "- {$warning['message']}\n";
            }
            if (count($results['details']['warnings']) > 10) {
                $report .= "... and " . (count($results['details']['warnings']) - 10) . " more warnings\n";
            }
            $report .= "\n";
        }

        return $report;
    }

    /**
     * Export detailed results to array for API responses
     */
    public function getDetailedResults(): array
    {
        return [
            'summary' => [
                'batch_id' => $this->importResults['batch_id'],
                'import_type' => class_basename($this->importResults['import_type']),
                'total_rows' => $this->importResults['total_rows'],
                'processed' => $this->importResults['processed'],
                'created' => $this->importResults['created'],
                'updated' => $this->importResults['updated'],
                'skipped' => $this->importResults['skipped'],
                'errors' => $this->importResults['errors'],
                'warnings' => $this->importResults['warnings'],
                'processing_time' => $this->importResults['processing_time'],
                'memory_usage_mb' => round($this->importResults['memory_usage'] / 1024 / 1024, 2),
                'dry_run' => $this->dryRun
            ],
            'details' => $this->importResults['details'],
            'options' => $this->options
        ];
    }
}