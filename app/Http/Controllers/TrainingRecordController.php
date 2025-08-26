<?php
// app/Http/Controllers/TrainingRecordController.php

namespace App\Http\Controllers;

use App\Models\TrainingRecord;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\Department;
use App\Models\TrainingProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TrainingRecordController extends Controller
{
    /**
     * Display a listing of training records with clean UI - 1 Employee = 1 Row
     */
    public function index(Request $request)
    {
        // DEBUG: Log request untuk melihat filter yang dikirim
        Log::info('TrainingRecords Index Request:', $request->all());

        // PERBAIKAN: Query employees dengan aggregate certificate counts
        $query = Employee::with(['department'])
            ->where('status', 'active')
            ->withCount([
                'trainingRecords as total_certificates_count',
                'trainingRecords as active_certificates_count' => function($q) {
                    $q->where('compliance_status', 'compliant');
                },
                'trainingRecords as expiring_certificates_count' => function($q) {
                    $q->where('compliance_status', 'expiring_soon');
                },
                'trainingRecords as expired_certificates_count' => function($q) {
                    $q->where('compliance_status', 'expired');
                }
            ]);

        // Add latest training records per employee untuk preview
        $query->with([
            'trainingRecords' => function($q) {
                $q->with(['trainingType', 'trainingProvider'])
                  ->orderBy('created_at', 'desc')
                  ->limit(3); // Show only latest 3 for summary
            }
        ]);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('employee_id', 'like', '%' . $request->search . '%')
                  ->orWhereHas('trainingRecords', function($tr) use ($request) {
                      $tr->where('certificate_number', 'like', '%' . $request->search . '%')
                         ->orWhere('issuer', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Department filter
        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        // Training Type filter - employees who have this training type
        if ($request->filled('training_type')) {
            $query->whereHas('trainingRecords', function($q) use ($request) {
                $q->where('training_type_id', $request->training_type);
            });
        }

        // Provider filter - employees who have training from specific provider
        if ($request->filled('provider')) {
            $query->whereHas('trainingRecords', function($q) use ($request) {
                $q->where('training_provider_id', $request->provider);
            });
        }

        // Status filter - employees with certificates in specific status
        if ($request->filled('status')) {
            $statusMap = [
                'active' => 'compliant',
                'expiring_soon' => 'expiring_soon',
                'expired' => 'expired'
            ];

            $dbStatus = $statusMap[$request->status] ?? $request->status;

            $query->whereHas('trainingRecords', function($q) use ($dbStatus) {
                $q->where('compliance_status', $dbStatus);
            });
        }

        // Execute query dengan pagination
        $employees = $query->orderBy('name')->paginate(15)->withQueryString();

        // Debug: Log query results
        Log::info('Employees Query Result:', [
            'total' => $employees->total(),
            'count' => $employees->count(),
            'first_employee' => $employees->first() ? $employees->first()->toArray() : null
        ]);

        // Calculate overall statistics
        $stats = [
            'total_employees' => Employee::where('status', 'active')->count(),
            'total_certificates' => TrainingRecord::count(),
            'compliant_certificates' => TrainingRecord::where('compliance_status', 'compliant')->count(),
            'expiring_certificates' => TrainingRecord::where('compliance_status', 'expiring_soon')->count(),
            'expired_certificates' => TrainingRecord::where('compliance_status', 'expired')->count(),
            'total_providers' => TrainingProvider::where('is_active', true)->count(),
        ];

        // Debug: Log statistics
        Log::info('Training Records Statistics:', $stats);

        // PERBAIKAN: Data structure yang benar untuk frontend
        return Inertia::render('TrainingRecords/Index', [
            'employees' => $employees, // ✅ Paginated employees with certificate counts
            'trainingTypes' => TrainingType::where('is_active', true)->get(['id', 'name']),
            'departments' => Department::all(['id', 'name']),
            'trainingProviders' => TrainingProvider::where('is_active', true)->get(['id', 'name', 'code']), // ✅ Provider list for filter
            'filters' => $request->only(['search', 'status', 'training_type', 'provider', 'department']),
            'stats' => $stats
        ]);
    }

    /**
     * Show the form for creating a new training record.
     */
    public function create(Request $request)
    {
        // Pre-select employee if provided in query parameter
        $selectedEmployeeId = $request->get('employee_id');

        return Inertia::render('TrainingRecords/Create', [
            'employees' => Employee::where('status', 'active')
                ->with('department')
                ->orderBy('name')
                ->get(['id', 'name', 'employee_id', 'department_id']),
            'trainingTypes' => TrainingType::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'validity_months']),
            'trainingProviders' => TrainingProvider::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'departments' => Department::all(['id', 'name']),
            'selectedEmployeeId' => $selectedEmployeeId // ✅ Pass selected employee for pre-filling
        ]);
    }

    /**
     * Store a newly created training record in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'training_provider_id' => 'nullable|exists:training_providers,id',
            'certificate_number' => 'required|string|max:100|unique:training_records',
            'issuer' => 'required|string|max:100',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'completion_date' => 'nullable|date',
            'training_date' => 'nullable|date',
            'score' => 'nullable|numeric|min:0|max:100',
            'training_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        // Calculate compliance status
        $complianceStatus = 'compliant';
        if ($validated['expiry_date']) {
            $expiryDate = Carbon::parse($validated['expiry_date']);
            $now = Carbon::now();

            if ($expiryDate->isPast()) {
                $complianceStatus = 'expired';
            } elseif ($expiryDate->diffInDays($now) <= 30) {
                $complianceStatus = 'expiring_soon';
            }
        }

        $validated['compliance_status'] = $complianceStatus;
        $validated['status'] = 'completed';

        TrainingRecord::create($validated);

        return redirect()->route('training-records.index')
            ->with('success', 'Training record created successfully.');
    }

    /**
     * Display the specified training record.
     */
    public function show(TrainingRecord $trainingRecord)
    {
        $trainingRecord->load(['employee.department', 'trainingType', 'trainingProvider']);

        return Inertia::render('TrainingRecords/Show', [
            'trainingRecord' => $trainingRecord
        ]);
    }

    /**
     * Show the form for editing the specified training record.
     */
    public function edit(TrainingRecord $trainingRecord)
    {
        $trainingRecord->load(['employee', 'trainingType', 'trainingProvider']);

        return Inertia::render('TrainingRecords/Edit', [
            'trainingRecord' => $trainingRecord,
            'employees' => Employee::where('status', 'active')
                ->with('department')
                ->orderBy('name')
                ->get(['id', 'name', 'employee_id', 'department_id']),
            'trainingTypes' => TrainingType::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'validity_months']),
            'trainingProviders' => TrainingProvider::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'departments' => Department::all(['id', 'name'])
        ]);
    }

    /**
     * Update the specified training record in storage.
     */
    public function update(Request $request, TrainingRecord $trainingRecord)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'training_provider_id' => 'nullable|exists:training_providers,id',
            'certificate_number' => 'required|string|max:100|unique:training_records,certificate_number,' . $trainingRecord->id,
            'issuer' => 'required|string|max:100',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'completion_date' => 'nullable|date',
            'training_date' => 'nullable|date',
            'score' => 'nullable|numeric|min:0|max:100',
            'training_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        // Calculate compliance status
        $complianceStatus = 'compliant';
        if ($validated['expiry_date']) {
            $expiryDate = Carbon::parse($validated['expiry_date']);
            $now = Carbon::now();

            if ($expiryDate->isPast()) {
                $complianceStatus = 'expired';
            } elseif ($expiryDate->diffInDays($now) <= 30) {
                $complianceStatus = 'expiring_soon';
            }
        }

        $validated['compliance_status'] = $complianceStatus;

        $trainingRecord->update($validated);

        return redirect()->route('training-records.index')
            ->with('success', 'Training record updated successfully.');
    }

    /**
     * Remove the specified training record from storage.
     */
    public function destroy(TrainingRecord $trainingRecord)
    {
        try {
            // Log the deletion for audit purposes
            Log::info('Training record deletion requested', [
                'training_record_id' => $trainingRecord->id,
                'certificate_number' => $trainingRecord->certificate_number,
                'employee_name' => $trainingRecord->employee->name ?? 'Unknown',
                'user_id' => Auth::id(),
            ]);

            // Store info for success message
            $certificateNumber = $trainingRecord->certificate_number;
            $employeeName = $trainingRecord->employee->name ?? 'Unknown Employee';

            // Delete the training record
            $trainingRecord->delete();

            // Log successful deletion
            Log::info('Training record deleted successfully', [
                'certificate_number' => $certificateNumber,
                'employee_name' => $employeeName,
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('success',
                "Training record '{$certificateNumber}' for {$employeeName} has been deleted successfully."
            );

        } catch (\Exception $e) {
            Log::error('Failed to delete training record', [
                'training_record_id' => $trainingRecord->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('error',
                'Failed to delete training record. Please try again.'
            );
        }
    }

    /**
     * ✅ Export training records to Excel
     */
    public function export(Request $request)
    {
        try {
            $filters = $request->only(['search', 'status', 'training_type', 'provider', 'department']);

            // Create export with filtered data
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\TrainingRecordsExport($filters),
                'training_records_export_' . date('Y-m-d_H-i-s') . '.xlsx'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Training records export failed', [
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Export template for training records
     */
    public function exportTemplate()
    {
        try {
            // Create a template with sample data and proper headers
            $templateData = collect([
                [
                    'employee_id' => 'EMP001',
                    'employee_name' => 'John Doe',
                    'training_type' => 'MPGA Awareness',
                    'provider_name' => 'Gapura Learning Center',
                    'certificate_number' => 'CERT001',
                    'issuer' => 'GLC',
                    'issue_date' => '2024-01-01',
                    'expiry_date' => '2025-01-01',
                    'training_date' => '2024-01-01',
                    'score' => '85.5',
                    'training_hours' => '8',
                    'cost' => '500000',
                    'location' => 'Jakarta Training Center',
                    'instructor_name' => 'Instructor Name',
                    'notes' => 'Sample training record'
                ]
            ]);

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\TrainingRecordsExport([], $templateData),
                'training_records_template.xlsx'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Training records template export failed', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Template export failed: ' . $e->getMessage());
        }
    }

    /**
     * NEW METHOD: Get detailed certificates for specific employee (AJAX)
     */
    public function getEmployeeCertificates(Request $request, Employee $employee)
    {
        Log::info('Getting certificates for employee:', ['employee_id' => $employee->id]);

        $certificates = TrainingRecord::where('employee_id', $employee->id)
            ->with(['trainingType', 'trainingProvider', 'employee.department'])
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('Certificates found:', ['count' => $certificates->count()]);

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_id' => $employee->employee_id,
                'department' => $employee->department->name ?? 'No Department',
            ],
            'certificates' => $certificates->map(function($cert) {
                return [
                    'id' => $cert->id,
                    'certificate_number' => $cert->certificate_number,
                    'training_type' => $cert->trainingType->name ?? 'Unknown',
                    'provider' => $cert->trainingProvider->name ?? 'Unknown Provider',
                    'issuer' => $cert->issuer,
                    'issue_date' => $cert->issue_date ? Carbon::parse($cert->issue_date)->format('Y-m-d') : null,
                    'expiry_date' => $cert->expiry_date ? Carbon::parse($cert->expiry_date)->format('Y-m-d') : null,
                    'compliance_status' => $cert->compliance_status,
                    'status' => $cert->status,
                    'notes' => $cert->notes
                ];
            })
        ]);
    }
}
