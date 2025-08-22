<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Services\TrainingStatusService;
use App\Imports\TrainingRecordsImport;
use App\Exports\TrainingRecordsExport;
use Maatwebsite\Excel\Facades\Excel;

class TrainingRecordController extends Controller
{
    protected $statusService;

    public function __construct(TrainingStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Display a listing of training records with advanced filtering
     */
    // Fix untuk TrainingRecordController.php - method index()

public function index(Request $request)
{
    $query = TrainingRecord::with(['employee.department', 'trainingType']);

    // Search functionality
    if ($request->has('search') && $request->search) {
        $query->where(function($q) use ($request) {
            $q->where('certificate_number', 'like', '%' . $request->search . '%')
              ->orWhere('issuer', 'like', '%' . $request->search . '%')
              ->orWhereHas('employee', function($eq) use ($request) {
                  $eq->where('name', 'like', '%' . $request->search . '%')
                     ->orWhere('employee_id', 'like', '%' . $request->search . '%');
              })
              ->orWhereHas('trainingType', function($tq) use ($request) {
                  $tq->where('name', 'like', '%' . $request->search . '%');
              });
        });
    }

    // Status filter
    if ($request->has('status') && $request->status) {
        $query->where('status', $request->status);
    }

    // Training type filter - FIX: gunakan training_type bukan training_type_id
    if ($request->has('training_type') && $request->training_type) {
        $query->where('training_type_id', $request->training_type);
    }

    // Employee filter - FIX: gunakan employee bukan employee_id
    if ($request->has('employee') && $request->employee) {
        $query->where('employee_id', $request->employee);
    }

    // Department filter - TAMBAHAN: filter by department
    if ($request->has('department') && $request->department) {
        $query->whereHas('employee', function($eq) use ($request) {
            $eq->where('department_id', $request->department);
        });
    }

    // Date range filter
    if ($request->has('date_from') && $request->date_from) {
        $query->where('expiry_date', '>=', $request->date_from);
    }
    if ($request->has('date_to') && $request->date_to) {
        $query->where('expiry_date', '<=', $request->date_to);
    }

    $trainingRecords = $query->orderBy('expiry_date', 'desc')
        ->paginate(15)
        ->withQueryString();

    // Add statistics
    $stats = [
        'total_employees' => Employee::count(),
        'total_certificates' => TrainingRecord::count(),
        'active_certificates' => TrainingRecord::where('status', 'active')->count(),
        'expiring_certificates' => TrainingRecord::where('status', 'expiring_soon')->count(),
        'expired_certificates' => TrainingRecord::where('status', 'expired')->count(),
    ];

    return Inertia::render('TrainingRecords/Index', [
        'trainingRecords' => $trainingRecords,
        'employees' => Employee::all(['id', 'name', 'employee_id']),
        'trainingTypes' => TrainingType::where('is_active', true)->get(['id', 'name']),
        'departments' => Department::all(['id', 'name']), // TAMBAHAN
        'filters' => $request->only(['search', 'status', 'training_type', 'employee', 'department', 'date_from', 'date_to']),
        'stats' => $stats
    ]);
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
     * Store a newly created training record
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'issuer' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'auto_generate_certificate' => 'boolean',
            'certificate_number' => 'required_if:auto_generate_certificate,false|string|unique:training_records',
            'notes' => 'nullable|string',
        ]);

        // Auto-generate certificate number if requested
        if ($request->auto_generate_certificate) {
            $certificateNumber = $this->statusService->generateCertificateNumber(
                $request->training_type_id,
                $request->issuer
            );
        } else {
            $certificateNumber = $request->certificate_number;
        }

        // Auto-calculate expiry date
        $expiryDate = $this->statusService->calculateExpiryDate(
            $request->issue_date,
            $request->training_type_id
        );

        // Determine initial status
        $status = 'active';
        $daysUntilExpiry = \Carbon\Carbon::parse($expiryDate)->diffInDays(\Carbon\Carbon::now(), false);

        if ($daysUntilExpiry <= 0) {
            $status = 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            $status = 'expiring_soon';
        }

        TrainingRecord::create([
            'employee_id' => $request->employee_id,
            'training_type_id' => $request->training_type_id,
            'certificate_number' => $certificateNumber,
            'issuer' => $request->issuer,
            'issue_date' => $request->issue_date,
            'expiry_date' => $expiryDate,
            'status' => $status,
            'notes' => $request->notes,
        ]);

        return redirect()->route('training-records.index')
            ->with('success', 'Training record berhasil ditambahkan.');
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
        return Inertia::render('TrainingRecords/Edit', [
            'trainingRecord' => $trainingRecord,
            'employees' => Employee::where('status', 'active')->get(['id', 'name', 'employee_id']),
            'trainingTypes' => TrainingType::where('is_active', true)->get(['id', 'name', 'validity_months']),
        ]);
    }

    /**
     * Update the specified training record
     */
    public function update(Request $request, TrainingRecord $trainingRecord)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'certificate_number' => 'required|string|unique:training_records,certificate_number,' . $trainingRecord->id,
            'issuer' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'notes' => 'nullable|string',
        ]);

        // Update status based on expiry date
        $status = 'active';
        $daysUntilExpiry = \Carbon\Carbon::parse($request->expiry_date)->diffInDays(\Carbon\Carbon::now(), false);

        if ($daysUntilExpiry <= 0) {
            $status = 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            $status = 'expiring_soon';
        }

        $trainingRecord->update([
            'employee_id' => $request->employee_id,
            'training_type_id' => $request->training_type_id,
            'certificate_number' => $request->certificate_number,
            'issuer' => $request->issuer,
            'issue_date' => $request->issue_date,
            'expiry_date' => $request->expiry_date,
            'status' => $status,
            'notes' => $request->notes,
        ]);

        return redirect()->route('training-records.index')
            ->with('success', 'Training record berhasil diupdate.');
    }

    /**
     * Remove the specified training record
     */
    public function destroy(TrainingRecord $trainingRecord)
    {
        $trainingRecord->delete();

        return redirect()->route('training-records.index')
            ->with('success', 'Training record berhasil dihapus.');
    }

    /**
     * Bulk import training records
     */
    public function handleBulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new TrainingRecordImport, $request->file('file'));

            // Update statuses after import
            $this->statusService->updateAllStatuses();

            return redirect()->route('training-records.index')
                ->with('success', 'Training records berhasil diimport.');
        } catch (\Exception $e) {
            return redirect()->route('training-records.index')
                ->with('error', 'Error importing data: ' . $e->getMessage());
        }
    }

    /**
     * Bulk export training records
     */
    public function bulkExport(Request $request)
    {
        $fileName = 'training_records_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new TrainingRecordsExport($request->all()), $fileName);
    }

    /**
     * Get expiring certificates
     */
    public function expiring(Request $request)
    {
        $days = $request->get('days', 30);
        $expiringRecords = $this->statusService->getExpiringSoon($days);

        return Inertia::render('TrainingRecords/Expiring', [
            'expiringRecords' => $expiringRecords,
            'days' => $days
        ]);
    }

    /**
     * Renew a training record
     */
    public function renew(Request $request, TrainingRecord $trainingRecord)
    {
        $request->validate([
            'issue_date' => 'required|date',
            'issuer' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        // Generate new certificate number
        $certificateNumber = $this->statusService->generateCertificateNumber(
            $trainingRecord->training_type_id,
            $request->issuer
        );

        // Calculate new expiry date
        $expiryDate = $this->statusService->calculateExpiryDate(
            $request->issue_date,
            $trainingRecord->training_type_id
        );

        // Create new training record (renewal)
        TrainingRecord::create([
            'employee_id' => $trainingRecord->employee_id,
            'training_type_id' => $trainingRecord->training_type_id,
            'certificate_number' => $certificateNumber,
            'issuer' => $request->issuer,
            'issue_date' => $request->issue_date,
            'expiry_date' => $expiryDate,
            'status' => 'active',
            'notes' => $request->notes,
        ]);

        // Mark old record as renewed (you could add a renewed_by field)
        $trainingRecord->update([
            'status' => 'expired',
            'notes' => ($trainingRecord->notes ? $trainingRecord->notes . ' | ' : '') . 'Renewed on ' . $request->issue_date
        ]);

        return redirect()->route('training-records.index')
            ->with('success', 'Training record berhasil diperpanjang.');
    }

    /**
     * Bulk operations for training records
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,export',
            'record_ids' => 'required|array',
            'record_ids.*' => 'exists:training_records,id'
        ]);

        switch ($request->action) {
            case 'delete':
                TrainingRecord::whereIn('id', $request->record_ids)->delete();
                $message = 'Training records berhasil dihapus.';
                return redirect()->route('training-records.index')
                    ->with('success', $message);
                break;

            case 'export':
                $fileName = 'selected_training_records_' . date('Y-m-d_H-i-s') . '.xlsx';
                return Excel::download(
                    new TrainingRecordsExport(['record_ids' => $request->record_ids]),
                    $fileName
                );
                break;
        }
    }
}
