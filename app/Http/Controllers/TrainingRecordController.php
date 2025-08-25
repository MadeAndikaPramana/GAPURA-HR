<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Models\Department;
use App\Services\TrainingStatusService;
use App\Imports\TrainingRecordsImport;
use App\Exports\TrainingRecordsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TrainingRecordController extends Controller
{
    protected $statusService;

    public function __construct(TrainingStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Display employee-centric training records view (ENHANCED)
     */
    public function index(Request $request)
    {
        // Query semua employee dengan training records mereka
        $query = Employee::with([
            'department',
            'trainingRecords' => function($query) {
                $query->with('trainingType');
            }
        ])->where('status', 'active');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'no_training') {
                $query->doesntHave('trainingRecords');
            } else {
                // Map status ke compliance_status
                $complianceStatusMap = [
                    'active' => 'compliant',
                    'expiring_soon' => 'expiring_soon',
                    'expired' => 'expired'
                ];

                if (isset($complianceStatusMap[$status])) {
                    $query->whereHas('trainingRecords', function($q) use ($complianceStatusMap, $status) {
                        $q->where('compliance_status', $complianceStatusMap[$status]);
                    });
                }
            }
        }

        $employees = $query->paginate(20)->withQueryString();

        // Transform data untuk menambah training summary per employee
        $employees->getCollection()->transform(function($employee) {
            // Group training by type untuk mendapat latest record per type
            $trainingsByType = $employee->trainingRecords->groupBy('training_type_id');

            $trainingSummary = [];
            foreach($trainingsByType as $typeId => $records) {
                $latestRecord = $records->sortByDesc('issue_date')->first();
                $trainingSummary[] = [
                    'training_type' => $latestRecord->trainingType,
                    'status' => $latestRecord->compliance_status, // Use compliance_status
                    'expiry_date' => $latestRecord->expiry_date,
                    'issue_date' => $latestRecord->issue_date,
                    'total_records' => $records->count()
                ];
            }

            $employee->training_summary = $trainingSummary;
            $employee->training_stats = [
                'total' => count($trainingSummary),
                'active' => collect($trainingSummary)->where('status', 'compliant')->count(),
                'expiring' => collect($trainingSummary)->where('status', 'expiring_soon')->count(),
                'expired' => collect($trainingSummary)->where('status', 'expired')->count()
            ];

            return $employee;
        });

        // Get overall stats - update untuk menggunakan compliance_status
        $stats = [
            'total_employees' => Employee::where('status', 'active')->count(),
            'employees_with_training' => Employee::has('trainingRecords')->count(),
            'total_certificates' => TrainingRecord::count(),
            'active_certificates' => TrainingRecord::where('compliance_status', 'compliant')->count(),
            'expiring_certificates' => TrainingRecord::where('compliance_status', 'expiring_soon')->count(),
            'expired_certificates' => TrainingRecord::where('compliance_status', 'expired')->count(),
        ];

        return Inertia::render('TrainingRecords/Index', [
            'employees' => $employees,
            'departments' => Department::all(['id', 'name']),
            'filters' => $request->only(['search', 'department', 'status']),
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
     * Store a newly created training record (COMPLETELY FIXED)
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'issuer' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'auto_generate_certificate' => 'boolean',
            'certificate_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'score' => 'nullable|numeric|min:0|max:100',
            'training_hours' => 'nullable|numeric|min:0'
        ]);

        // ========= CRITICAL: Certificate Number Generation =========
        $certificateNumber = null;

        if ($request->boolean('auto_generate_certificate') || empty($request->certificate_number)) {
            // Use TrainingStatusService to generate certificate number
            try {
                $certificateNumber = $this->statusService->generateCertificateNumber(
                    $request->training_type_id,
                    $request->issuer
                );
            } catch (\Exception $e) {
                // Fallback generation if service fails
                $certificateNumber = $this->generateCertificateNumberSafe(
                    $request->training_type_id,
                    $request->issuer
                );
            }
        } else {
            $certificateNumber = $request->certificate_number;
        }

        // ========= CRITICAL: Ensure certificate_number is not null =========
        if (empty($certificateNumber)) {
            return back()->withErrors([
                'certificate_number' => 'Certificate number generation failed. Please provide manually.'
            ])->withInput();
        }

        // Check for uniqueness
        $existingRecord = TrainingRecord::where('certificate_number', $certificateNumber)->first();
        if ($existingRecord) {
            // Auto-increment if duplicate
            $counter = 1;
            $originalCertNumber = $certificateNumber;
            while (TrainingRecord::where('certificate_number', $certificateNumber)->exists()) {
                $certificateNumber = $originalCertNumber . '-' . $counter;
                $counter++;
            }
        }

        // ========= Calculate Expiry Date =========
        try {
            $expiryDate = $this->statusService->calculateExpiryDate(
                $request->issue_date,
                $request->training_type_id
            );
        } catch (\Exception $e) {
            // Fallback calculation
            $trainingType = TrainingType::find($request->training_type_id);
            $issueDate = Carbon::parse($request->issue_date);
            $expiryDate = $issueDate->copy()->addMonths($trainingType->validity_months ?? 12);
        }

        // ========= Calculate Compliance Status =========
        $complianceStatus = $this->calculateComplianceStatusSafe($request->issue_date, $expiryDate);

        // ========= FIXED: Create with ALL required fields =========
        try {
            $trainingRecord = TrainingRecord::create([
                // REQUIRED FIELDS
                'employee_id' => $request->employee_id,
                'training_type_id' => $request->training_type_id,
                'certificate_number' => $certificateNumber, // ← CRITICAL: Must be included
                'issuer' => $request->issuer,
                'issue_date' => $request->issue_date,

                // DATABASE SCHEMA FIELDS (set with defaults)
                'training_date' => $request->issue_date, // Same as issue_date
                'completion_date' => $request->issue_date, // Same as issue_date
                'expiry_date' => $expiryDate,
                'status' => 'completed', // Fixed enum value
                'compliance_status' => $complianceStatus, // Fixed enum value

                // OPTIONAL FIELDS
                'score' => $request->score,
                'training_hours' => $request->training_hours,
                'notes' => $request->notes,

                // AUDIT FIELDS (if exist)
                'created_by_id' => auth()->id(),
                'updated_by_id' => auth()->id()
            ]);

            return redirect()->route('training-records.index')
                ->with('success', 'Training record berhasil ditambahkan dengan certificate: ' . $certificateNumber);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('Training Record Creation Failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'certificate_number' => $certificateNumber
            ]);

            return back()->withErrors([
                'general' => 'Failed to create training record: ' . $e->getMessage()
            ])->withInput();
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
        return Inertia::render('TrainingRecords/Edit', [
            'trainingRecord' => $trainingRecord,
            'employees' => Employee::where('status', 'active')->get(['id', 'name', 'employee_id']),
            'trainingTypes' => TrainingType::where('is_active', true)->get(['id', 'name', 'validity_months']),
        ]);
    }

    /**
     * Update the specified training record (FIXED)
     */
    public function update(Request $request, TrainingRecord $trainingRecord)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'certificate_number' => 'required|string|unique:training_records,certificate_number,' .
                $trainingRecord->id,
            'issuer' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'notes' => 'nullable|string',
            'score' => 'nullable|numeric|min:0|max:100',
            'training_hours' => 'nullable|numeric|min:0'
        ]);

        // Calculate compliance status berdasarkan expiry date
        $complianceStatus = $this->calculateComplianceStatusSafe($request->issue_date, $request->expiry_date);

        $trainingRecord->update([
            'employee_id' => $request->employee_id,
            'training_type_id' => $request->training_type_id,
            'certificate_number' => $request->certificate_number,
            'issuer' => $request->issuer,
            'issue_date' => $request->issue_date,
            'training_date' => $request->issue_date,
            'completion_date' => $request->issue_date,
            'expiry_date' => $request->expiry_date,
            'compliance_status' => $complianceStatus,
            'score' => $request->score,
            'training_hours' => $request->training_hours,
            'notes' => $request->notes,
            'updated_by_id' => auth()->id()
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
     * NEW: Edit employee training records
     */
    public function editEmployee(Request $request, Employee $employee)
    {
        $employee->load([
            'department',
            'trainingRecords' => function($query) {
                $query->with('trainingType')->orderBy('issue_date', 'desc');
            }
        ]);

        $trainingTypes = TrainingType::where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'validity_months']);

        return Inertia::render('TrainingRecords/EditEmployee', [
            'employee' => $employee,
            'trainingTypes' => $trainingTypes,
            'can_edit' => $this->userCanEditTraining()
        ]);
    }

    /**
     * NEW: Update employee training records
     */
    public function updateEmployeeTraining(Request $request, Employee $employee)
    {
        if (!$this->userCanEditTraining()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'action' => 'required|in:create,update,delete',
            'training_type_id' => 'required_if:action,create,update|exists:training_types,id',
            'certificate_number' => 'nullable|string|max:100',
            'issuer' => 'required_if:action,create,update|string|max:255',
            'issue_date' => 'required_if:action,create,update|date',
            'expiry_date' => 'required_if:action,create,update|date|after:issue_date',
            'score' => 'nullable|numeric|min:0|max:100',
            'training_hours' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'record_id' => 'nullable|exists:training_records,id'
        ]);

        try {
            DB::transaction(function() use ($request, $employee) {
                $action = $request->action;

                if ($action === 'create') {
                    $this->createTrainingRecordForEmployee($employee, $request->all());
                } elseif ($action === 'update' && $request->record_id) {
                    $this->updateTrainingRecordForEmployee($request->record_id, $request->all());
                } elseif ($action === 'delete' && $request->record_id) {
                    $this->deleteTrainingRecordForEmployee($request->record_id);
                }
            });

            return back()->with('success', 'Training record berhasil diupdate.');

        } catch (\Exception $e) {
            Log::error('Employee Training Update Failed', [
                'error' => $e->getMessage(),
                'employee_id' => $employee->id,
                'action' => $request->action,
                'data' => $request->all()
            ]);

            return back()->withErrors([
                'general' => 'Failed to update training record: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * FIXED: Create training record for employee
     */
    private function createTrainingRecordForEmployee(Employee $employee, array $data)
    {
        // Generate certificate number
        $certificateNumber = !empty($data['certificate_number'])
            ? $data['certificate_number']
            : $this->generateCertificateNumberSafe($data['training_type_id'], $data['issuer']);

        // Ensure certificate number is unique
        $counter = 1;
        $originalCertNumber = $certificateNumber;
        while (TrainingRecord::where('certificate_number', $certificateNumber)->exists()) {
            $certificateNumber = $originalCertNumber . '-' . $counter;
            $counter++;
        }

        // Calculate compliance status
        $complianceStatus = $this->calculateComplianceStatusSafe($data['issue_date'], $data['expiry_date']);

        // ===== CRITICAL: Include certificate_number in create =====
        TrainingRecord::create([
            'employee_id' => $employee->id,
            'training_type_id' => $data['training_type_id'],
            'certificate_number' => $certificateNumber, // ← MUST BE INCLUDED
            'issuer' => $data['issuer'],
            'issue_date' => $data['issue_date'],
            'training_date' => $data['issue_date'],
            'completion_date' => $data['issue_date'],
            'expiry_date' => $data['expiry_date'],
            'status' => 'completed',
            'compliance_status' => $complianceStatus,
            'score' => !empty($data['score']) ? $data['score'] : null,
            'training_hours' => !empty($data['training_hours']) ? $data['training_hours'] : null,
            'notes' => !empty($data['notes']) ? $data['notes'] : null,
            'created_by_id' => auth()->id(),
            'updated_by_id' => auth()->id()
        ]);
    }

    /**
     * FIXED: Update training record for employee
     */
    private function updateTrainingRecordForEmployee($recordId, array $data)
    {
        $record = TrainingRecord::findOrFail($recordId);
        $complianceStatus = $this->calculateComplianceStatusSafe($data['issue_date'], $data['expiry_date']);

        $record->update([
            'training_type_id' => $data['training_type_id'],
            'certificate_number' => $data['certificate_number'] ?: $record->certificate_number,
            'issuer' => $data['issuer'],
            'issue_date' => $data['issue_date'],
            'training_date' => $data['issue_date'],
            'completion_date' => $data['issue_date'],
            'expiry_date' => $data['expiry_date'],
            'compliance_status' => $complianceStatus,
            'score' => !empty($data['score']) ? $data['score'] : null,
            'training_hours' => !empty($data['training_hours']) ? $data['training_hours'] : null,
            'notes' => !empty($data['notes']) ? $data['notes'] : null,
            'updated_by_id' => auth()->id()
        ]);
    }

    /**
     * Delete training record for employee
     */
    private function deleteTrainingRecordForEmployee($recordId)
    {
        TrainingRecord::findOrFail($recordId)->delete();
    }

    /**
     * Safe certificate number generation with fallback
     */
    private function generateCertificateNumberSafe($trainingTypeId, $issuer)
    {
        try {
            // Try using service first
            return $this->statusService->generateCertificateNumber($trainingTypeId, $issuer);
        } catch (\Exception $e) {
            // Fallback generation
            $trainingType = TrainingType::find($trainingTypeId);
            $prefix = strtoupper(substr($trainingType->code ?? 'TRN', 0, 3));
            $year = date('Y');
            $month = date('m');

            // Get next sequence number
            $lastRecord = TrainingRecord::where('certificate_number', 'like', "{$prefix}-{$year}{$month}-%")
                ->orderBy('certificate_number', 'desc')
                ->first();

            $sequence = 1;
            if ($lastRecord) {
                $parts = explode('-', $lastRecord->certificate_number);
                if (count($parts) >= 3) {
                    $lastSequence = (int) end($parts);
                    $sequence = $lastSequence + 1;
                }
            }

            return sprintf('%s-%s%s-%03d', $prefix, $year, $month, $sequence);
        }
    }

    /**
     * Safe compliance status calculation with fallback
     */
    private function calculateComplianceStatusSafe($issueDate, $expiryDate)
    {
        if (!$expiryDate) {
            return 'not_required';
        }

        try {
            $now = now();
            $expiry = Carbon::parse($expiryDate);

            if ($expiry->isPast()) {
                return 'expired';
            } elseif ($expiry->diffInDays($now) <= 30) {
                return 'expiring_soon';
            }

            return 'compliant';
        } catch (\Exception $e) {
            return 'compliant'; // Default fallback
        }
    }

    /**
     * Check if user can edit training records
     */
    private function userCanEditTraining(): bool
    {
        $user = auth()->user();

        // Check if user has hasRole method
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['admin', 'hr_staff']);
        }

        // Fallback: check role field directly
        if (isset($user->role)) {
            return in_array($user->role, ['admin', 'hr_staff']);
        }

        // Default: allow all authenticated users
        return true;
    }

    /**
     * Bulk import training records (EXISTING)
     */
    public function handleBulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new TrainingRecordsImport, $request->file('file'));

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
     * Bulk export training records (EXISTING)
     */
    public function bulkExport(Request $request)
    {
        $fileName = 'training_records_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new TrainingRecordsExport($request->all()), $fileName);
    }

    /**
     * Bulk operations for training records (EXISTING)
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
