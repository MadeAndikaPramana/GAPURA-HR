<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Importable;

class MasterDataImport implements WithMultipleSheets
{
    use Importable;

    private array $importResults = [
        'batch_id' => null,
        'start_time' => null,
        'end_time' => null,
        'sheets_processed' => 0,
        'total_results' => [
            'total_rows' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'warnings' => 0
        ],
        'sheet_results' => [],
        'processing_time' => 0
    ];

    private array $options;
    private array $sheetImporters = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->importResults['batch_id'] = $this->generateBatchId();
        $this->importResults['start_time'] = now();

        Log::info("Starting master data import", [
            'batch_id' => $this->importResults['batch_id'],
            'options' => $options
        ]);
    }

    /**
     * Map each sheet to its appropriate importer
     */
    public function sheets(): array
    {
        return [
            'employees' => new SDMEmployeeImport($this->options),
            'employee' => new SDMEmployeeImport($this->options),
            'pegawai' => new SDMEmployeeImport($this->options),
            'sdm' => new SDMEmployeeImport($this->options),
            'karyawan' => new SDMEmployeeImport($this->options),

            'departments' => new DepartmentImport($this->options),
            'department' => new DepartmentImport($this->options),
            'departemen' => new DepartmentImport($this->options),
            'bagian' => new DepartmentImport($this->options),
            'divisi' => new DepartmentImport($this->options),

            'training_types' => new TrainingTypeImport($this->options),
            'training_type' => new TrainingTypeImport($this->options),
            'certificate_types' => new TrainingTypeImport($this->options),
            'certificate_type' => new TrainingTypeImport($this->options),
            'jenis_training' => new TrainingTypeImport($this->options),
            'tipe_sertifikat' => new TrainingTypeImport($this->options),

            'training_records' => new TrainingRecordsImport($this->options),
            'training_record' => new TrainingRecordsImport($this->options),
            'certificates' => new TrainingRecordsImport($this->options),
            'certificate' => new TrainingRecordsImport($this->options),
            'sertifikat' => new TrainingRecordsImport($this->options),
            'training' => new TrainingRecordsImport($this->options),
            'pelatihan' => new TrainingRecordsImport($this->options),
        ];
    }

    /**
     * Process all sheets and collect results
     */
    public function afterImport(): void
    {
        $this->importResults['end_time'] = now();
        $this->importResults['processing_time'] = $this->importResults['start_time']->diffInSeconds($this->importResults['end_time']);

        // Collect results from all sheet importers
        foreach ($this->sheetImporters as $sheetName => $importer) {
            if (method_exists($importer, 'getImportResults')) {
                $sheetResults = $importer->getImportResults();
                $this->importResults['sheet_results'][$sheetName] = $sheetResults;
                $this->importResults['sheets_processed']++;

                // Aggregate totals
                $this->importResults['total_results']['total_rows'] += $sheetResults['total_rows'] ?? 0;
                $this->importResults['total_results']['processed'] += $sheetResults['processed'] ?? 0;
                $this->importResults['total_results']['created'] += $sheetResults['created'] ?? 0;
                $this->importResults['total_results']['updated'] += $sheetResults['updated'] ?? 0;
                $this->importResults['total_results']['skipped'] += $sheetResults['skipped'] ?? 0;
                $this->importResults['total_results']['errors'] += $sheetResults['errors'] ?? 0;
                $this->importResults['total_results']['warnings'] += $sheetResults['warnings'] ?? 0;
            }
        }

        Log::info("Completed master data import", $this->importResults);
    }

    /**
     * Register sheet importer for result collection
     */
    public function registerSheetImporter(string $sheetName, $importer): void
    {
        $this->sheetImporters[$sheetName] = $importer;
    }

    private function generateBatchId(): string
    {
        return 'master_' . date('YmdHis') . '_' . substr(md5(uniqid()), 0, 8);
    }

    /**
     * Get comprehensive import results
     */
    public function getImportResults(): array
    {
        return $this->importResults;
    }

    /**
     * Generate comprehensive master data import report
     */
    public function generateReport(): string
    {
        $results = $this->importResults;

        $report = "Master Data Import Report\n";
        $report .= "=" . str_repeat("=", 26) . "\n\n";

        $report .= "Batch Information:\n";
        $report .= "- Batch ID: {$results['batch_id']}\n";
        $report .= "- Start Time: {$results['start_time']}\n";
        $report .= "- End Time: {$results['end_time']}\n";
        $report .= "- Processing Time: {$results['processing_time']} seconds\n";
        $report .= "- Sheets Processed: {$results['sheets_processed']}\n\n";

        $report .= "Overall Summary:\n";
        $report .= "- Total rows: {$results['total_results']['total_rows']}\n";
        $report .= "- Successfully processed: {$results['total_results']['processed']}\n";
        $report .= "- Records created: {$results['total_results']['created']}\n";
        $report .= "- Records updated: {$results['total_results']['updated']}\n";
        $report .= "- Rows skipped: {$results['total_results']['skipped']}\n";
        $report .= "- Errors: {$results['total_results']['errors']}\n";
        $report .= "- Warnings: {$results['total_results']['warnings']}\n\n";

        // Sheet-by-sheet breakdown
        if (!empty($results['sheet_results'])) {
            $report .= "Sheet-by-Sheet Results:\n";
            $report .= str_repeat("-", 50) . "\n";

            foreach ($results['sheet_results'] as $sheetName => $sheetResult) {
                $report .= "\nSheet: {$sheetName}\n";
                $report .= "- Import Type: " . class_basename($sheetResult['import_type']) . "\n";
                $report .= "- Total rows: {$sheetResult['total_rows']}\n";
                $report .= "- Processed: {$sheetResult['processed']}\n";
                $report .= "- Created: {$sheetResult['created']}\n";
                $report .= "- Updated: {$sheetResult['updated']}\n";
                $report .= "- Skipped: {$sheetResult['skipped']}\n";
                $report .= "- Errors: {$sheetResult['errors']}\n";
                $report .= "- Warnings: {$sheetResult['warnings']}\n";

                // Show top errors for this sheet
                if (!empty($sheetResult['details']['errors'])) {
                    $report .= "- Top Errors:\n";
                    foreach (array_slice($sheetResult['details']['errors'], 0, 3) as $error) {
                        $report .= "  * Row {$error['row']}: {$error['error']}\n";
                    }
                    if (count($sheetResult['details']['errors']) > 3) {
                        $report .= "  * ... and " . (count($sheetResult['details']['errors']) - 3) . " more\n";
                    }
                }
            }
        }

        // Success rate calculation
        if ($results['total_results']['total_rows'] > 0) {
            $successRate = round(($results['total_results']['processed'] / $results['total_results']['total_rows']) * 100, 2);
            $report .= "\nSuccess Rate: {$successRate}%\n";
        }

        // Recommendations
        $report .= "\nRecommendations:\n";
        if ($results['total_results']['errors'] > 0) {
            $report .= "- Review and fix errors in data source before re-importing\n";
        }
        if ($results['total_results']['warnings'] > 0) {
            $report .= "- Check warnings for data quality issues\n";
        }
        if ($results['total_results']['skipped'] > 0) {
            $report .= "- Consider using 'update_existing' option to update skipped records\n";
        }

        return $report;
    }

    /**
     * Export detailed results for API responses
     */
    public function getDetailedResults(): array
    {
        return [
            'summary' => [
                'batch_id' => $this->importResults['batch_id'],
                'import_type' => 'MasterDataImport',
                'sheets_processed' => $this->importResults['sheets_processed'],
                'processing_time' => $this->importResults['processing_time'],
                'total_results' => $this->importResults['total_results']
            ],
            'sheet_results' => $this->importResults['sheet_results'],
            'options' => $this->options
        ];
    }

    /**
     * Generate summary statistics for dashboard
     */
    public function getSummaryStats(): array
    {
        $results = $this->importResults;

        return [
            'batch_id' => $results['batch_id'],
            'sheets_processed' => $results['sheets_processed'],
            'total_rows' => $results['total_results']['total_rows'],
            'success_count' => $results['total_results']['processed'],
            'error_count' => $results['total_results']['errors'],
            'success_rate' => $results['total_results']['total_rows'] > 0
                ? round(($results['total_results']['processed'] / $results['total_results']['total_rows']) * 100, 2)
                : 0,
            'processing_time' => $results['processing_time'],
            'created_records' => [
                'employees' => $this->getSheetStat('employees', 'created', 0),
                'departments' => $this->getSheetStat('departments', 'created', 0),
                'training_types' => $this->getSheetStat('training_types', 'created', 0),
                'certificates' => $this->getSheetStat('training_records', 'created', 0),
            ]
        ];
    }

    private function getSheetStat(string $sheetPattern, string $stat, $default = 0)
    {
        foreach ($this->importResults['sheet_results'] as $sheetName => $result) {
            if (strpos(strtolower($sheetName), $sheetPattern) !== false) {
                return $result[$stat] ?? $default;
            }
        }
        return $default;
    }
}

// Custom sheet importers that extend the base imports to work with multi-sheet
class MasterDataSheetImportWrapper
{
    private $baseImporter;
    private string $sheetName;
    private MasterDataImport $masterImport;

    public function __construct($baseImporter, string $sheetName, MasterDataImport $masterImport)
    {
        $this->baseImporter = $baseImporter;
        $this->sheetName = $sheetName;
        $this->masterImport = $masterImport;
    }

    public function collection(Collection $collection): void
    {
        // Register this importer with master for result collection
        $this->masterImport->registerSheetImporter($this->sheetName, $this->baseImporter);

        // Process the collection
        if (method_exists($this->baseImporter, 'collection')) {
            $this->baseImporter->collection($collection);
        }
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->baseImporter, $method], $arguments);
    }
}