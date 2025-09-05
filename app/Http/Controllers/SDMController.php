<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SdmController extends Controller
{
    /**
     * Display employee list for SDM management
     */
    public function index(Request $request)
    {
        $query = Employee::with(['department'])
            ->withCount(['employeeCertificates', 'activeCertificates']);

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Department filter
        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employees = $query->orderBy('name')->paginate(20);

        // Add container status to each employee
        $employees->getCollection()->transform(function ($employee) {
            $employee->has_container = !is_null($employee->container_created_at);
            $employee->files_count = $employee->total_files_count;
            return $employee;
        });

        return Inertia::render('Sdm/Index', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'department', 'status']),
            'stats' => $this->getStats(),
        ]);
    }

    /**
     * Show form for creating new employee
     */
    public function create()
    {
        return Inertia::render('Sdm/Create', [
            'departments' => Department::orderBy('name')->get(['id', 'name', 'code']),
            'nextEmployeeId' => $this->generateNextEmployeeId(),
        ]);
    }

    /**
     * Store new employee (auto-creates container)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|unique:employees,employee_id|max:20',
            'nip' => 'nullable|string|unique:employees,nip|max:20',
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'email' => 'nullable|email|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'status' => 'required|in:active,inactive',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Create employee (container will be auto-created via model event)
        $employee = Employee::create(array_merge($validated, [
            'background_check_status' => 'not_started',
        ]));

        return redirect()->route('sdm.index')
            ->with('success', "Employee {$employee->name} created successfully with digital container.");
    }

    /**
     * Show form for editing employee
     */
    public function edit(Employee $employee)
    {
        return Inertia::render('Sdm/Edit', [
            'employee' => $employee->load('department'),
            'departments' => Department::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Update employee data
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|max:20|unique:employees,employee_id,' . $employee->id,
            'nip' => 'nullable|string|max:20|unique:employees,nip,' . $employee->id,
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'email' => 'nullable|email|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'status' => 'required|in:active,inactive',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Track NIP changes for history
        if ($employee->nip !== $validated['nip'] && !empty($validated['nip'])) {
            // Could implement NIP history tracking here if needed
        }

        $employee->update($validated);

        return redirect()->route('sdm.index')
            ->with('success', "Employee {$employee->name} updated successfully.");
    }

    /**
     * Delete employee (and container)
     */
    public function destroy(Employee $employee)
    {
        // Check if employee has certificates or files
        if ($employee->employeeCertificates()->count() > 0 || $employee->total_files_count > 0) {
            return back()->with('error', 'Cannot delete employee with existing certificates or files. Please clean container first.');
        }

        $name = $employee->name;
        $employee->delete();

        return redirect()->route('sdm.index')
            ->with('success', "Employee {$name} and digital container deleted successfully.");
    }

    // ===== EXCEL INTEGRATION METHODS =====

    /**
     * Download Excel template for employee data
     */
    public function downloadExcelTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = ['NIP', 'NAMA', 'DEPARTEMEN'];
        $sheet->fromArray($headers, null, 'A1');

        // Add sample data
        $sampleData = [
            ['12345', 'John Doe', 'IT'],
            ['67890', 'Jane Smith', 'HR'],
            ['11111', 'Bob Wilson', 'Finance']
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        // Style the header
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFCCCCCC');

        // Auto-size columns
        foreach (range('A', 'C') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add instructions
        $sheet->setCellValue('A6', 'INSTRUKSI:');
        $sheet->setCellValue('A7', '1. Isi data karyawan mulai dari baris 2');
        $sheet->setCellValue('A8', '2. NIP harus unik');
        $sheet->setCellValue('A9', '3. Departemen akan dibuat otomatis jika belum ada');
        $sheet->setCellValue('A10', '4. Simpan file ini dan upload ke sistem');

        $sheet->getStyle('A6')->getFont()->setBold(true);

        // Create file
        $fileName = 'template_employee_' . date('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        // Return as download
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Sync employee data from Excel file
     */
    public function syncExcelData(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('excel_file');
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();

            $rows = $worksheet->toArray();

            // Skip header row
            $dataRows = array_slice($rows, 1);

            $results = [
                'total_processed' => 0,
                'created' => 0,
                'updated' => 0,
                'errors' => [],
                'details' => []
            ];

            foreach ($dataRows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we skip header and array is 0-indexed

                // Skip empty rows
                if (empty($row[0]) && empty($row[1]) && empty($row[2])) {
                    continue;
                }

                try {
                    $nipData = trim($row[0] ?? '');
                    $namaData = trim($row[1] ?? '');
                    $departemenData = trim($row[2] ?? '');

                    // Validate required fields
                    if (empty($nipData) || empty($namaData) || empty($departemenData)) {
                        $results['errors'][] = "Row {$rowNumber}: Missing required data (NIP, Nama, or Departemen)";
                        continue;
                    }

                    // Process employee data
                    $result = Employee::createOrUpdateFromExcel($nipData, $namaData, $departemenData);

                    $results['total_processed']++;
                    $results[$result['action']]++;
                    $results['details'][] = [
                        'row' => $rowNumber,
                        'nip' => $nipData,
                        'nama' => $namaData,
                        'departemen' => $departemenData,
                        'action' => $result['action'],
                        'employee_id' => $result['employee']->id
                    ];

                } catch (\Exception $e) {
                    $results['errors'][] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Excel sync completed. Created: {$results['created']}, Updated: {$results['updated']}",
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Excel sync failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get sync status (for polling during sync)
     */
    public function getSyncStatus()
    {
        // This could be enhanced with job status tracking
        return response()->json([
            'status' => 'completed',
            'progress' => 100
        ]);
    }

    // ===== SEARCH & API METHODS =====

    /**
     * Search employees (web interface)
     */
    public function search(Request $request)
    {
        return $this->index($request);
    }

    /**
     * API search for AJAX calls
     */
    public function apiSearch(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $employees = Employee::search($query)
            ->with('department')
            ->limit($limit)
            ->get(['id', 'employee_id', 'nip', 'name', 'position', 'department_id', 'status']);

        return response()->json($employees);
    }

    /**
     * Quick update employee via API
     */
    public function quickUpdate(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'field' => 'required|in:status,position,department_id,nip',
            'value' => 'required'
        ]);

        // Additional validation based on field
        if ($validated['field'] === 'department_id') {
            $request->validate(['value' => 'exists:departments,id']);
        } elseif ($validated['field'] === 'nip') {
            $request->validate(['value' => 'unique:employees,nip,' . $employee->id]);
        }

        $employee->update([$validated['field'] => $validated['value']]);

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully',
            'employee' => $employee->fresh(['department'])
        ]);
    }

    // ===== HELPER METHODS =====

    /**
     * Generate next employee ID
     */
    private function generateNextEmployeeId()
    {
        $lastEmployee = Employee::orderBy('id', 'desc')->first();
        $nextId = $lastEmployee ? $lastEmployee->id + 1 : 1;
        return 'EMP' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get statistics for SDM dashboard
     */
    private function getStats()
    {
        return [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::where('status', 'active')->count(),
            'employees_with_containers' => Employee::whereNotNull('container_created_at')->count(),
            'departments_count' => Department::count(),
            'recent_additions' => Employee::where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }
}
