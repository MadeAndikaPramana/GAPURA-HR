<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Department;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Services\TrainingStatusService;
use App\Imports\TrainingRecordsImport;
use App\Exports\TrainingRecordsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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

        // Advanced filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee')) {
            $query->where('employee_id', $request->employee);
        }

        if ($request->filled('training_type')) {
            $query->where('training_type_id', $request->training_type);
        }

        if ($request->filled('department')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('department_id', $request->department);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        $trainingRecords = $query->orderBy('created_at', 'desc')->paginate(15);

        // Statistics
        $stats = [
            'total_certificates' => TrainingRecord::count(),
            'active_certificates' => TrainingRecord::where('status', 'active')->count(),
            'expiring_certificates' => TrainingRecord::where('status', 'expiring_soon')->count(),
            'expired_certificates' => TrainingRecord::where('status', 'expired')->count(),
        ];

        return Inertia::render('TrainingRecords/Index', [
            'trainingRecords' => $trainingRecords,
            'employees' => Employee::all(['id', 'name', 'employee_id']),
            'trainingTypes' => TrainingType::where('is_active', true)->get(['id', 'name']),
            'departments' => Department::all(['id', 'name']),
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
            'employees' => Employee::where('status', 'active')
                ->with('department')
                ->get(['id', 'name', 'employee_id']),
            'trainingTypes' => TrainingType::where('is_active', true)
                ->get(['id', 'name', 'code', 'validity_months']),
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
            'certificate_number' => 'required_if:auto_generate_certificate,false|string|max:100|unique:training_records',
            'completion_date' => 'nullable|date|after_or_equal:issue_date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'score' => 'nullable|numeric|min:0|max:100',
            'training_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $employee = Employee::findOrFail($request->employee_id);
            $trainingType = TrainingType::findOrFail($request->training_type_id);

            // Auto-generate certificate number if requested
            $certificateNumber = $request->auto_generate_certificate
                ? $this->generateCertificateNumber($employee, $trainingType)
                : $request->certificate_number;

            // Calculate expiry date if not provided
            $expiryDate = $request->expiry_date;
            if (!$expiryDate && $trainingType->validity_months) {
                $issueDate = Carbon::parse($request->issue_date);
                $expiryDate = $issueDate->addMonths($trainingType->validity_months)->format('Y-m-d');
            }

            // Determine initial status
            $status = $this->calculateStatus($expiryDate);

            $trainingRecord = TrainingRecord::create([
                'employee_id' => $request->employee_id,
                'training_type_id' => $request->training_type_id,
                'certificate_number' => $certificateNumber,
                'issuer' => $request->issuer,
                'issue_date' => $request->issue_date,
                'completion_date' => $request->completion_date,
                'expiry_date' => $expiryDate,
                'status' => $status,
                'compliance_status' => $status === 'expired' ? 'expired' : 'compliant',
                'score' => $request->score,
                'training_hours' => $request->training_hours,
                'cost' => $request->cost,
                'location' => $request->location,
                'instructor_name' => $request->instructor_name,
                'notes' => $request->notes,
                'created_by_id' => Auth::id(),
            ]);

            DB::commit();

            Log::info('Training record created', [
                'id' => $trainingRecord->id,
                'certificate_number' => $certificateNumber,
                'employee' => $employee->name,
                'training_type' => $trainingType->name,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('training-records.index')
                ->with('success', "Training record berhasil ditambahkan untuk {$employee->name}.");

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Training record creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Gagal menambahkan training record: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified training record
     */
    public function show(TrainingRecord $trainingRecord)
    {
        $trainingRecord->load(['employee.department', 'trainingType']);

        // Get related training records for same employee
        $relatedRecords = TrainingRecord::where('employee_id', $trainingRecord->employee_id)
            ->where('id', '!=', $trainingRecord->id)
            ->with('trainingType')
            ->orderBy('issue_date', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('TrainingRecords/Show', [
            'trainingRecord' => $trainingRecord,
            'relatedRecords' => $relatedRecords
        ]);
    }

    /**
     * Show the form for editing the specified training record
     */
    public function edit(TrainingRecord $trainingRecord)
    {
        $trainingRecord->load(['employee', 'trainingType']);

        return Inertia::render('TrainingRecords/Edit', [
            'trainingRecord' => $trainingRecord,
            'employees' => Employee::where('status', 'active')
                ->with('department')
                ->get(['id', 'name', 'employee_id']),
            'trainingTypes' => TrainingType::where('is_active', true)
                ->get(['id', 'name', 'code', 'validity_months']),
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
            'certificate_number' => 'required|string|max:100|unique:training_records,certificate_number,' . $trainingRecord->id,
            'issuer' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'completion_date' => 'nullable|date|after_or_equal:issue_date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'score' => 'nullable|numeric|min:0|max:100',
            'training_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'instructor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Recalculate status based on expiry date
            $status = $this->calculateStatus($request->expiry_date);

            $trainingRecord->update([
                'employee_id' => $request->employee_id,
                'training_type_id' => $request->training_type_id,
                'certificate_number' => $request->certificate_number,
                'issuer' => $request->issuer,
                'issue_date' => $request->issue_date,
                'completion_date' => $request->completion_date,
                'expiry_date' => $request->expiry_date,
                'status' => $status,
                'compliance_status' => $status === 'expired' ? 'expired' : 'compliant',
                'score' => $request->score,
                'training_hours' => $request->training_hours,
                'cost' => $request->cost,
                'location' => $request->location,
                'instructor_name' => $request->instructor_name,
                'notes' => $request->notes,
                'updated_by_id' => Auth::id(),
            ]);

            DB::commit();

            Log::info('Training record updated', [
                'id' => $trainingRecord->id,
                'certificate_number' => $request->certificate_number,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('training-records.index')
                ->with('success', 'Training record berhasil diupdate.');

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Training record update failed', [
                'id' => $trainingRecord->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Gagal mengupdate training record: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified training record
     */
    public function destroy(TrainingRecord $trainingRecord)
    {
        try {
            DB::beginTransaction();

            $certificateNumber = $trainingRecord->certificate_number;
            $employeeName = $trainingRecord->employee->name ?? 'Unknown';

            $trainingRecord->delete();

            DB::commit();

            Log::info('Training record deleted', [
                'certificate_number' => $certificateNumber,
                'employee' => $employeeName,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('training-records.index')
                ->with('success', "Training record {$certificateNumber} berhasil dihapus.");

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Training record deletion failed', [
                'id' => $trainingRecord->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->route('training-records.index')
                ->with('error', 'Gagal menghapus training record: ' . $e->getMessage());
        }
    }

    /**
     * Handle bulk actions on training records
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,export,mark_expired,mark_active',
            'training_record_ids' => 'required|array',
            'training_record_ids.*' => 'exists:training_records,id'
        ]);

        $trainingRecords = TrainingRecord::whereIn('id', $request->training_record_ids);

        try {
            DB::beginTransaction();

            switch ($request->action) {
                case 'delete':
                    $count = $trainingRecords->count();
                    $trainingRecords->delete();
                    $message = "Successfully deleted {$count} training records.";
                    break;

                case 'mark_expired':
                    $count = $trainingRecords->update([
                        'status' => 'expired',
                        'compliance_status' => 'expired'
                    ]);
                    $message = "Successfully marked {$count} records as expired.";
                    break;

                case 'mark_active':
                    $count = $trainingRecords->update([
                        'status' => 'active',
                        'compliance_status' => 'compliant'
                    ]);
                    $message = "Successfully marked {$count} records as active.";
                    break;

                case 'export':
                    $filters = ['training_record_ids' => $request->training_record_ids];
                    return Excel::download(new TrainingRecordsExport($filters), 'selected_training_records.xlsx');
            }

            DB::commit();

            Log::info('Bulk action completed', [
                'action' => $request->action,
                'record_count' => count($request->training_record_ids),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Bulk action failed', [
                'action' => $request->action,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->with('error', 'Bulk action failed: ' . $e->getMessage());
        }
    }

    /**
     * Mark training record as completed
     */
    public function markCompleted(TrainingRecord $trainingRecord)
    {
        try {
            $trainingRecord->update([
                'status' => 'completed',
                'completion_date' => now()->format('Y-m-d'),
                'updated_by_id' => Auth::id(),
            ]);

            // Update compliance status
            $status = $this->calculateStatus($trainingRecord->expiry_date);
            $trainingRecord->update(['status' => $status]);

            return redirect()->back()
                ->with('success', 'Training record marked as completed.');

        } catch (\Exception $e) {
            Log::error('Mark completed failed', [
                'id' => $trainingRecord->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to mark as completed: ' . $e->getMessage());
        }
    }

    /**
     * Create renewal for training record
     */
    public function createRenewal(TrainingRecord $trainingRecord)
    {
        try {
            DB::beginTransaction();

            $renewalData = [
                'employee_id' => $trainingRecord->employee_id,
                'training_type_id' => $trainingRecord->training_type_id,
                'certificate_number' => $this->generateCertificateNumber(
                    $trainingRecord->employee,
                    $trainingRecord->trainingType
                ),
                'issuer' => $trainingRecord->issuer,
                'issue_date' => now()->format('Y-m-d'),
                'status' => 'active',
                'compliance_status' => 'compliant',
                'created_by_id' => Auth::id(),
            ];

            // Calculate expiry date
            if ($trainingRecord->trainingType->validity_months) {
                $renewalData['expiry_date'] = now()
                    ->addMonths($trainingRecord->trainingType->validity_months)
                    ->format('Y-m-d');
            }

            $renewal = TrainingRecord::create($renewalData);

            DB::commit();

            Log::info('Renewal created', [
                'original_id' => $trainingRecord->id,
                'renewal_id' => $renewal->id,
                'certificate_number' => $renewal->certificate_number,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('training-records.show', $renewal)
                ->with('success', 'Renewal training record created successfully.');

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Renewal creation failed', [
                'original_id' => $trainingRecord->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create renewal: ' . $e->getMessage());
        }
    }

    /**
     * Calculate training record status based on expiry date
     */
    private function calculateStatus($expiryDate)
    {
        if (!$expiryDate) {
            return 'active';
        }

        $today = Carbon::today();
        $expiry = Carbon::parse($expiryDate);
        $daysUntilExpiry = $today->diffInDays($expiry, false);

        if ($daysUntilExpiry < 0) {
            return 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            return 'expiring_soon';
        } else {
            return 'active';
        }
    }

    /**
     * Generate certificate number
     */
    private function generateCertificateNumber($employee, $trainingType)
    {
        $departmentCode = $employee->department?->code ?? 'GEN';
        $trainingCode = $trainingType->code ?? 'TRN';
        $year = date('Y');
        $month = date('m');

        // Get next sequence number
        $lastRecord = TrainingRecord::where('certificate_number', 'like', "{$departmentCode}-{$trainingCode}-{$year}{$month}-%")
            ->orderBy('certificate_number', 'desc')
            ->first();

        $sequence = 1;
        if ($lastRecord) {
            $parts = explode('-', $lastRecord->certificate_number);
            if (count($parts) >= 4) {
                $lastSequence = (int) end($parts);
                $sequence = $lastSequence + 1;
            }
        }

        return sprintf('%s-%s-%s%s-%03d', $departmentCode, $trainingCode, $year, $month, $sequence);
    }
}
