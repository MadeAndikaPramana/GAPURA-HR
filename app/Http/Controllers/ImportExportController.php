<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeesImport;
use App\Imports\TrainingRecordsImport;
use App\Imports\CertificatesImport;
use App\Exports\EmployeesExport;
use App\Exports\TrainingRecordsExport;
use App\Exports\CertificatesExport;
use App\Exports\ComplianceReportExport;
use App\Models\Employee;
use App\Models\Department;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportExportController extends Controller
{
    /**
     * Display the import/export dashboard
     */
    public function index()
    {
        $recentImports = $this->getRecentImports();
        $exportStats = $this->getExportStats();

        return Inertia::render('ImportExport/Index', [
            'recentImports' => $recentImports,
            'exportStats' => $exportStats
        ]);
    }

    // =================================================================
    // EMPLOYEE IMPORT/EXPORT
    // =================================================================

    /**
     * Show employee import form
     */
    public function showEmployeeImport()
    {
        return Inertia::render('ImportExport/EmployeeImport', [
            'departments' => Department::all(['id', 'name']),
            'sampleData' => $this->getEmployeeSampleData()
        ]);
    }

    /**
     * Process employee import
     */
    public function importEmployees(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv|max:10240', // 10MB max
            'update_existing' => 'boolean',
            'department_mapping' => 'array'
        ]);

        try {
            $file = $request->file('file');
            $updateExisting = $request->boolean('update_existing');
            $departmentMapping = $request->get('department_mapping', []);

            $import = new EmployeesImport($updateExisting, $departmentMapping);
            Excel::import($import, $file);

            $results = $import->getResults();

            Log::info('Employee import completed', [
                'user_id' => auth()->id(),
                'filename' => $file->getClientOriginalName(),
                'results' => $results
            ]);

            return redirect()->back()->with('success',
                "Import completed! Created: {$results['created']}, Updated: {$results['updated']}, Errors: {$results['errors']}"
            )->with('importResults', $results);

        } catch (\Exception $e) {
            Log::error('Employee import failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Export employees
     */
    public function exportEmployees(Request $request)
    {
        $request->validate([
            'department_ids' => 'array',
            'status' => 'in:active,inactive,all',
            'format' => 'in:xlsx,csv'
        ]);

        $departmentIds = $request->get('department_ids');
        $status = $request->get('status', 'active');
        $format = $request->get('format', 'xlsx');

        $filename = 'employees_export_' . now()->format('Y_m_d_H_i_s') . '.' . $format;

        try {
            return Excel::download(
                new EmployeesExport($departmentIds, $status),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Employee export failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    // =================================================================
    // TRAINING RECORDS IMPORT/EXPORT
    // =================================================================

    /**
     * Show training records import form
     */
    public function showTrainingRecordsImport()
    {
        return Inertia::render('ImportExport/TrainingRecordsImport', [
            'trainingTypes' => TrainingType::all(['id', 'name', 'code']),
            'sampleData' => $this->getTrainingRecordsSampleData()
        ]);
    }

    /**
     * Process training records import
     */
    public function importTrainingRecords(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv|max:10240',
            'auto_create_certificates' => 'boolean',
            'default_training_provider' => 'nullable|exists:training_providers,id'
        ]);

        try {
            $file = $request->file('file');
            $autoCreateCertificates = $request->boolean('auto_create_certificates');
            $defaultProviderId = $request->get('default_training_provider');

            $import = new TrainingRecordsImport($autoCreateCertificates, $defaultProviderId);
            Excel::import($import, $file);

            $results = $import->getResults();

            Log::info('Training records import completed', [
                'user_id' => auth()->id(),
                'filename' => $file->getClientOriginalName(),
                'results' => $results
            ]);

            return redirect()->back()->with('success',
                "Import completed! Created: {$results['created']}, Certificates: {$results['certificates_created']}, Errors: {$results['errors']}"
            )->with('importResults', $results);

        } catch (\Exception $e) {
            Log::error('Training records import failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Export training records
     */
    public function exportTrainingRecords(Request $request)
    {
        $request->validate([
            'department_ids' => 'array',
            'training_type_ids' => 'array',
            'status' => 'in:all,completed,active,expired',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'format' => 'in:xlsx,csv'
        ]);

        $filters = $request->only([
            'department_ids', 'training_type_ids', 'status',
            'date_from', 'date_to'
        ]);
        $format = $request->get('format', 'xlsx');

        $filename = 'training_records_export_' . now()->format('Y_m_d_H_i_s') . '.' . $format;

        try {
            return Excel::download(
                new TrainingRecordsExport($filters),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Training records export failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);

            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    // =================================================================
    // CERTIFICATE IMPORT/EXPORT
    // =================================================================

    /**
     * Show certificates import form
     */
    public function showCertificatesImport()
    {
        return Inertia::render('ImportExport/CertificatesImport', [
            'trainingTypes' => TrainingType::all(['id', 'name', 'code']),
            'sampleData' => $this->getCertificatesSampleData()
        ]);
    }

    /**
     * Process certificates import
     */
    public function importCertificates(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv|max:10240',
            'auto_verify' => 'boolean'
        ]);

        try {
            $file = $request->file('file');
            $autoVerify = $request->boolean('auto_verify');

            $import = new CertificatesImport($autoVerify);
            Excel::import($import, $file);

            $results = $import->getResults();

            Log::info('Certificates import completed', [
                'user_id' => auth()->id(),
                'filename' => $file->getClientOriginalName(),
                'results' => $results
            ]);

            return redirect()->back()->with('success',
                "Import completed! Created: {$results['created']}, Updated: {$results['updated']}, Errors: {$results['errors']}"
            )->with('importResults', $results);

        } catch (\Exception $e) {
            Log::error('Certificates import failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Export certificates
     */
    public function exportCertificates(Request $request)
    {
        $request->validate([
            'department_ids' => 'array',
            'training_type_ids' => 'array',
            'status' => 'in:all,active,expired,expiring_soon',
            'verified' => 'nullable|boolean',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'format' => 'in:xlsx,csv'
        ]);

        $filters = $request->only([
            'department_ids', 'training_type_ids', 'status',
            'verified', 'date_from', 'date_to'
        ]);
        $format = $request->get('format', 'xlsx');

        $filename = 'certificates_export_' . now()->format('Y_m_d_H_i_s') . '.' . $format;

        try {
            return Excel::download(
                new CertificatesExport(null, true, $filters['department_ids'] ?? null, $filters['training_type_ids'] ?? null),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Certificates export failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);

            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    // =================================================================
    // COMPLIANCE REPORTS
    // =================================================================

    /**
     * Export compliance report
     */
    public function exportComplianceReport(Request $request)
    {
        $request->validate([
            'department_ids' => 'array',
            'include_expired' => 'boolean',
            'include_employee_details' => 'boolean',
            'format' => 'in:xlsx,csv,pdf'
        ]);

        $departmentIds = $request->get('department_ids');
        $includeExpired = $request->boolean('include_expired', true);
        $includeEmployeeDetails = $request->boolean('include_employee_details', true);
        $format = $request->get('format', 'xlsx');

        $filename = 'compliance_report_' . now()->format('Y_m_d_H_i_s') . '.' . $format;

        try {
            return Excel::download(
                new ComplianceReportExport($departmentIds, $includeExpired, $includeEmployeeDetails),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Compliance report export failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    // =================================================================
    // TEMPLATE DOWNLOADS
    // =================================================================

    /**
     * Download employee import template
     */
    public function downloadEmployeeTemplate()
    {
        $templateData = [
            ['employee_id', 'name', 'email', 'phone', 'department_code', 'position', 'hire_date', 'status'],
            ['GAP001', 'John Doe', 'john.doe@gapura.com', '+62812345678', 'HR', 'HR Manager', '2024-01-15', 'active'],
            ['GAP002', 'Jane Smith', 'jane.smith@gapura.com', '+62812345679', 'IT', 'System Administrator', '2024-02-01', 'active']
        ];

        return $this->generateTemplate('employee_import_template.xlsx', $templateData, [
            'A1:H1' => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '10B981']]]
        ]);
    }

    /**
     * Download training records import template
     */
    public function downloadTrainingRecordsTemplate()
    {
        $templateData = [
            ['employee_id', 'training_type_code', 'training_date', 'completion_date', 'score', 'cost', 'location', 'instructor_name', 'notes'],
            ['GAP001', 'FIRE', '2024-03-01', '2024-03-01', '85', '250000', 'Training Room A', 'Ahmad Suryanto', 'Fire safety training completed'],
            ['GAP002', 'FIRST', '2024-03-05', '2024-03-05', '92', '500000', 'Red Cross Center', 'Dr. Sari Wulandari', 'First aid certification']
        ];

        return $this->generateTemplate('training_records_import_template.xlsx', $templateData, [
            'A1:I1' => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '10B981']]]
        ]);
    }

    /**
     * Download certificates import template
     */
    public function downloadCertificatesTemplate()
    {
        $templateData = [
            ['employee_id', 'training_type_code', 'certificate_number', 'issued_by', 'issue_date', 'expiry_date', 'verification_code', 'notes'],
            ['GAP001', 'FIRE', 'FIRE-202403-0001', 'Gapura Safety Department', '2024-03-01', '2025-03-01', 'CERT-ABC12345', 'Fire safety certificate'],
            ['GAP002', 'FIRST', 'FIRST-202403-0002', 'Red Cross Indonesia', '2024-03-05', '2026-03-05', 'CERT-DEF67890', 'First aid certificate']
        ];

        return $this->generateTemplate('certificates_import_template.xlsx', $templateData, [
            'A1:H1' => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '10B981']]]
        ]);
    }

    // =================================================================
    // HELPER METHODS
    // =================================================================

    /**
     * Generate Excel template
     */
    protected function generateTemplate($filename, $data, $styles = [])
    {
        return Excel::download(new class($data, $styles) implements
            \Maatwebsite\Excel\Concerns\FromArray,
            \Maatwebsite\Excel\Concerns\WithStyles,
            \Maatwebsite\Excel\Concerns\WithColumnWidths
        {
            protected $data;
            protected $styles;

            public function __construct($data, $styles)
            {
                $this->data = $data;
                $this->styles = $styles;
            }

            public function array(): array
            {
                return $this->data;
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                foreach ($this->styles as $range => $style) {
                    $sheet->getStyle($range)->applyFromArray($style);
                }

                return [];
            }

            public function columnWidths(): array
            {
                return [
                    'A' => 15, 'B' => 25, 'C' => 25, 'D' => 15,
                    'E' => 20, 'F' => 20, 'G' => 15, 'H' => 20, 'I' => 30
                ];
            }
        }, $filename);
    }

    /**
     * Get recent import statistics
     */
    protected function getRecentImports()
    {
        // This would typically query an import_logs table
        // For now, returning mock data
        return [
            [
                'type' => 'employees',
                'filename' => 'employees_march_2024.xlsx',
                'imported_at' => now()->subHours(2),
                'records_processed' => 150,
                'records_successful' => 148,
                'records_failed' => 2,
                'status' => 'completed'
            ],
            [
                'type' => 'training_records',
                'filename' => 'training_completions_q1.xlsx',
                'imported_at' => now()->subDays(1),
                'records_processed' => 320,
                'records_successful' => 315,
                'records_failed' => 5,
                'status' => 'completed'
            ]
        ];
    }

    /**
     * Get export statistics
     */
    protected function getExportStats()
    {
        return [
            'total_exports_this_month' => 25,
            'most_exported_type' => 'certificates',
            'total_records_exported' => 15420,
            'average_export_size' => 617
        ];
    }

    /**
     * Get employee sample data for preview
     */
    protected function getEmployeeSampleData()
    {
        return [
            ['GAP001', 'Ahmad Suryanto', 'ahmad.suryanto@gapura.com', '+62812345678', 'HR', 'HR Manager', '2024-01-15', 'active'],
            ['GAP002', 'Nina Sari Dewi', 'nina.dewi@gapura.com', '+62812345679', 'HR', 'Training Coordinator', '2024-02-01', 'active'],
            ['GAP003', 'Budi Santoso', 'budi.santoso@gapura.com', '+62812345680', 'SEC', 'Safety Officer', '2024-01-20', 'active']
        ];
    }

    /**
     * Get training records sample data for preview
     */
    protected function getTrainingRecordsSampleData()
    {
        return [
            ['GAP001', 'FIRE', '2024-03-01', '2024-03-01', '85', '250000', 'Training Room A', 'Ahmad Suryanto', 'Fire safety training completed'],
            ['GAP002', 'FIRST', '2024-03-05', '2024-03-05', '92', '500000', 'Red Cross Center', 'Dr. Sari Wulandari', 'First aid certification'],
            ['GAP003', 'OHS', '2024-03-10', '2024-03-10', '78', '350000', 'Safety Institute', 'Agus Setiawan', 'Occupational health and safety']
        ];
    }

    /**
     * Get certificates sample data for preview
     */
    protected function getCertificatesSampleData()
    {
        return [
            ['GAP001', 'FIRE', 'FIRE-202403-0001', 'Gapura Safety Department', '2024-03-01', '2025-03-01', 'CERT-ABC12345', 'Fire safety certificate'],
            ['GAP002', 'FIRST', 'FIRST-202403-0002', 'Red Cross Indonesia', '2024-03-05', '2026-03-05', 'CERT-DEF67890', 'First aid certificate'],
            ['GAP003', 'OHS', 'OHS-202403-0003', 'Safety Institute Indonesia', '2024-03-10', '2025-03-10', 'CERT-GHI01234', 'OHS certification']
        ];
    }

    /**
     * Validate uploaded file
     */
    protected function validateUploadedFile($file, $allowedMimes = ['xlsx', 'csv'], $maxSize = 10240)
    {
        $validator = validator(['file' => $file], [
            'file' => "required|file|mimes:" . implode(',', $allowedMimes) . "|max:{$maxSize}"
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        // Additional security checks
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        if (!in_array(strtolower($extension), $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid file type.');
        }

        // Check file size
        if ($file->getSize() > $maxSize * 1024) {
            throw new \InvalidArgumentException('File too large.');
        }

        return true;
    }

    /**
     * Store uploaded file securely
     */
    protected function storeUploadedFile($file, $directory = 'imports')
    {
        $filename = Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, 'private');

        return $path;
    }

    /**
     * Clean up temporary files
     */
    protected function cleanupTempFiles($olderThanHours = 24)
    {
        $directories = ['imports', 'exports', 'temp'];

        foreach ($directories as $directory) {
            $files = Storage::disk('private')->files($directory);

            foreach ($files as $file) {
                $lastModified = Storage::disk('private')->lastModified($file);

                if ($lastModified < now()->subHours($olderThanHours)->timestamp) {
                    Storage::disk('private')->delete($file);
                }
            }
        }
    }
}
