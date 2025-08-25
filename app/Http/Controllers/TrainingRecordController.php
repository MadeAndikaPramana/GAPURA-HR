<?php
// app/Http/Controllers/TrainingRecordController.php

namespace App\Http\Controllers;

use App\Models\TrainingRecord;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TrainingRecordController extends Controller
{
/**
 * Display a listing of training records with clean UI - 1 Employee = 1 Row
 */
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
            $q->with('trainingType')
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

    // Employee filter (specific employee)
    if ($request->filled('employee')) {
        $query->where('id', $request->employee);
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
    ];

    // Debug: Log statistics
    Log::info('Training Records Statistics:', $stats);

    // PERBAIKAN: Data structure yang benar untuk frontend
    return Inertia::render('TrainingRecords/Index', [
        'employees' => $employees, // ✅ Paginated employees with certificate counts
        'employeeList' => Employee::where('status', 'active')->get(['id', 'name', 'employee_id']), // ✅ For filter dropdown
        'trainingTypes' => TrainingType::where('is_active', true)->get(['id', 'name']),
        'departments' => Department::all(['id', 'name']),
        'filters' => $request->only(['search', 'status', 'training_type', 'employee', 'department']),
        'stats' => $stats
    ]);
}

/**
 * NEW METHOD: Get detailed certificates for specific employee (AJAX)
 */
public function getEmployeeCertificates(Request $request, Employee $employee)
{
    Log::info('Getting certificates for employee:', ['employee_id' => $employee->id]);

    $certificates = TrainingRecord::where('employee_id', $employee->id)
        ->with(['trainingType', 'employee.department'])
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
                'issuer' => $cert->issuer,
                'issue_date' => $cert->issue_date ? $cert->issue_date->format('d/m/Y') : null,
                'expiry_date' => $cert->expiry_date ? $cert->expiry_date->format('d/m/Y') : null,
                'status' => $cert->compliance_status,
                'status_color' => $this->getStatusColor($cert->compliance_status),
                'days_until_expiry' => $cert->days_until_expiry,
            ];
        })
    ]);
}

/**
 * Helper method for status colors
 */
private function getStatusColor($status)
{
    return match($status) {
        'compliant' => 'green',
        'expiring_soon' => 'yellow',
        'expired' => 'red',
        default => 'gray'
    };
}
    /**
     * Show the form for creating a new training record
     */
    public function create()
    {
        return Inertia::render('TrainingRecords/Create', [
            'employees' => Employee::where('status', 'active')->get(['id', 'name', 'employee_id']),
            'trainingTypes' => TrainingType::where('is_active', true)->get(['id', 'name', 'validity_months']),
        ]);
    }

    /**
     * PERBAIKAN: Store method dengan field dan status yang benar
     */
    public function store(Request $request)
    {
        // Debug: Log incoming request
        Log::info('TrainingRecord Store Request:', $request->all());

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'issuer' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'certificate_number' => 'nullable|string|max:100|unique:training_records',
            'notes' => 'nullable|string',
        ]);

        // Auto-generate certificate number jika tidak diisi
        $certificateNumber = $request->certificate_number;
        if (!$certificateNumber) {
            $certificateNumber = TrainingRecord::generateCertificateNumber(
                $request->training_type_id,
                $request->issuer
            );
        }

        // Auto-calculate expiry date berdasarkan training type
        $trainingType = TrainingType::find($request->training_type_id);
        $issueDate = Carbon::parse($request->issue_date);
        $expiryDate = $issueDate->copy()->addMonths($trainingType->validity_months ?? 12);

        // PERBAIKAN: Gunakan status enum yang benar dari database
        $status = 'completed'; // Default status sesuai enum database

        // Set completion_date sama dengan issue_date untuk record baru
        $completionDate = $issueDate;

        try {
            $trainingRecord = TrainingRecord::create([
                'employee_id' => $request->employee_id,
                'training_type_id' => $request->training_type_id,
                'certificate_number' => $certificateNumber,
                'issuer' => $request->issuer,
                'issue_date' => $request->issue_date,
                'completion_date' => $completionDate,
                'expiry_date' => $expiryDate,
                'status' => $status,
                'notes' => $request->notes,
                // compliance_status akan di-set otomatis di model boot method
            ]);

            Log::info('TrainingRecord Created Successfully:', $trainingRecord->toArray());

            return redirect()->route('training-records.index')
                ->with('success', 'Training record berhasil ditambahkan.');

        } catch (\Exception $e) {
            Log::error('TrainingRecord Creation Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Error creating training record: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified training record
     */
    public function show(TrainingRecord $trainingRecord)
    {
        $trainingRecord->load(['employee.department', 'trainingType']);

        return Inertia::render('TrainingRecords/Show', [
            'trainingRecord' => $trainingRecord,
            'relatedRecords' => TrainingRecord::where('employee_id', $trainingRecord->employee_id)
                ->where('id', '!=', $trainingRecord->id)
                ->with('trainingType')
                ->get()
        ]);
    }

    /**
     * Show the form for editing the specified training record
     */
    public function edit(TrainingRecord $trainingRecord)
    {
    // PERBAIKAN: Load relationship yang dibutuhkan frontend
        $trainingRecord->load(['employee.department', 'trainingType']);

        return Inertia::render('TrainingRecords/Edit', [
            'trainingRecord' => $trainingRecord, // ✅ Sekarang punya employee & department relationship
            'employees' => Employee::where('status', 'active')
            ->with('department')  // ✅ Tambah department relationship untuk employees
            ->get(['id', 'name', 'employee_id', 'department_id']),
            'trainingTypes' => TrainingType::where('is_active', true)
            ->get(['id', 'name', 'validity_months', 'code']),
            'departments' => Department::all(['id', 'name']) // ✅ Tambah departments data jika dibutuhkan
     ]);
    }

    /**
     * PERBAIKAN: Update method dengan status yang benar
     */
    public function update(Request $request, TrainingRecord $trainingRecord)
{
    // Debug: Log incoming request
    Log::info('TrainingRecord Update Request:', $request->all());

    $request->validate([
        'employee_id' => 'required|exists:employees,id',
        'training_type_id' => 'required|exists:training_types,id',
        'certificate_number' => 'required|string|max:100|unique:training_records,certificate_number,' . $trainingRecord->id,
        'issuer' => 'required|string|max:255',
        'issue_date' => 'required|date',
        'expiry_date' => 'required|date|after:issue_date',
        'notes' => 'nullable|string',
    ]);

    try {
        $trainingRecord->update([
            'employee_id' => $request->employee_id,
            'training_type_id' => $request->training_type_id,
            'certificate_number' => $request->certificate_number,
            'issuer' => $request->issuer,
            'issue_date' => $request->issue_date,
            'expiry_date' => $request->expiry_date,
            'completion_date' => $request->issue_date, // Update completion_date juga
            'notes' => $request->notes,
            // compliance_status akan di-update otomatis di model boot method
        ]);

        Log::info('TrainingRecord Updated Successfully:', $trainingRecord->toArray());

        return redirect()->route('training-records.index')
            ->with('success', 'Training record berhasil diupdate.');

    } catch (\Exception $e) {
        Log::error('TrainingRecord Update Error:', [
            'id' => $trainingRecord->id,
            'error' => $e->getMessage(),
            'request_data' => $request->all()
        ]);

        return redirect()->back()
            ->withErrors(['error' => 'Error updating training record: ' . $e->getMessage()])
            ->withInput();
    }
}

    /**
     * Remove the specified training record
     */
    public function destroy(TrainingRecord $trainingRecord)
    {
        try {
            $trainingRecord->delete();

            return redirect()->route('training-records.index')
                ->with('success', 'Training record berhasil dihapus.');

        } catch (\Exception $e) {
            Log::error('TrainingRecord Delete Error:', [
                'id' => $trainingRecord->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Error deleting training record: ' . $e->getMessage()]);
        }
    }

    /**
     * TAMBAHAN: Method untuk debugging
     */
    public function debug()
    {
        $latestRecord = TrainingRecord::latest()->first();
        $modelFillable = (new TrainingRecord())->getFillable();

        return response()->json([
            'latest_record' => $latestRecord,
            'model_fillable_fields' => $modelFillable,
            'database_fields' => Schema::getColumnListing('training_records'),
        ]);
    }
}
