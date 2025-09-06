<?php
// app/Http/Controllers/TrainingTypeController.php - Fixed method calls

namespace App\Http\Controllers;

use App\Models\CertificateType;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TrainingTypeController extends Controller
{
    // ===== BASIC CRUD OPERATIONS =====

    /**
     * Display training types index with statistics
     */
    public function index(Request $request)
    {
        $query = CertificateType::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Mandatory filter
        if ($request->filled('mandatory')) {
            $query->where('is_mandatory', $request->mandatory === 'mandatory');
        }

        $certificateTypes = $query->orderBy('name')->paginate(15);

        // ✅ FIXED: Use the correct method names that exist in the model
        $certificateTypes->getCollection()->transform(function ($type) {
            $type->container_stats = [
                'total_certificates' => $type->getTotalCertificatesCount(),
                'active_certificates' => $type->getActiveCertificatesCount(),
                'expired_certificates' => $type->getExpiredCertificatesCount(),
                'expiring_soon_certificates' => $type->getExpiringSoonCertificatesCount(),
                'unique_employees' => $type->employeeCertificates()->distinct('employee_id')->count(),
                'compliance_rate' => $this->calculateComplianceRate($type)
            ];
            return $type;
        });

        // Get available categories for filter
        $categories = CertificateType::whereNotNull('category')
                                   ->distinct('category')
                                   ->pluck('category')
                                   ->sort()
                                   ->values();

        return Inertia::render('TrainingTypes/Index', [
            'certificateTypes' => $certificateTypes,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status', 'mandatory']),
            'stats' => $this->getStats()
        ]);
    }

    /**
     * Show form for creating training type
     */
    public function create()
{
    return Inertia::render('TrainingTypes/Create');
}


    /**
     * Store new training type
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:100|unique:certificate_types,name',
        'code' => 'nullable|string|max:50|unique:certificate_types,code',
        'validity_months' => 'nullable|integer|min:1|max:120',
        'warning_days' => 'nullable|integer|min:1|max:365',
        'is_recurrent' => 'boolean',
        'description' => 'nullable|string|max:1000',
        'requirements' => 'nullable|string|max:1000',
        'learning_objectives' => 'nullable|string|max:1000',
        'estimated_cost' => 'nullable|numeric|min:0',
        'estimated_duration_hours' => 'nullable|numeric|min:0',
        'is_active' => 'boolean'
    ]);

    // Generate code if not provided
    if (empty($validated['code'])) {
        $validated['code'] = $this->generateCode($validated['name']);
    }

    $certificateType = CertificateType::create($validated);

    return redirect()->route('training-types.index')
        ->with('success', "Training type '{$certificateType->name}' created successfully.");
}

    /**
     * Show form for editing training type
     */
    public function edit(CertificateType $certificateType)
{
    return Inertia::render('TrainingTypes/Edit', [
        'certificateType' => $certificateType
    ]);
}

    /**
     * Update training type
     */
    public function update(Request $request, CertificateType $certificateType)
{
    $validated = $request->validate([
        'name' => 'required|string|max:100|unique:certificate_types,name,' . $certificateType->id,
        'code' => 'nullable|string|max:50|unique:certificate_types,code,' . $certificateType->id,
        'validity_months' => 'nullable|integer|min:1|max:120',
        'warning_days' => 'nullable|integer|min:1|max:365',
        'is_recurrent' => 'boolean',
        'description' => 'nullable|string|max:1000',
        'requirements' => 'nullable|string|max:1000',
        'learning_objectives' => 'nullable|string|max:1000',
        'estimated_cost' => 'nullable|numeric|min:0',
        'estimated_duration_hours' => 'nullable|numeric|min:0',
        'is_active' => 'boolean'
    ]);

    // Generate code if not provided
    if (empty($validated['code'])) {
        $validated['code'] = $this->generateCode($validated['name']);
    }

    $certificateType->update($validated);

    return redirect()->route('training-types.index')
        ->with('success', "Training type '{$certificateType->name}' updated successfully.");
}

    /**
     * Delete training type
     */
    public function destroy(CertificateType $certificateType)
    {
        // Check if training type is being used
        if ($certificateType->employeeCertificates()->count() > 0) {
            return back()->with('error', 'Cannot delete training type that has certificates associated with it.');
        }

        $name = $certificateType->name;
        $certificateType->delete();

        return redirect()->route('training-types.index')
            ->with('success', "Training type '{$name}' deleted successfully.");
    }

    // ===== TRAINING TYPE CONTAINER METHODS (Reverse Lookup) =====

    /**
     * Show training type container (who has this certificate?)
     * This is the main "certificate jenis ini dimiliki siapa saja" view
     */
    public function showContainer(CertificateType $certificateType)
    {
        // Get container data (reverse lookup)
        $containerData = $certificateType->getContainerData();

        // Get departments for filters
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return Inertia::render('TrainingTypes/Container', [
            'certificateType' => $certificateType,
            'containerData' => $containerData,
            'departments' => $departments,
            'breadcrumb' => [
                ['name' => 'Training Types', 'url' => route('training-types.index')],
                ['name' => $certificateType->name, 'url' => null]
            ]
        ]);
    }

    /**
     * Get employees list for this training type (AJAX endpoint)
     */
    public function getEmployeesList(Request $request, CertificateType $certificateType)
    {
        $employees = $certificateType->getEmployeesWithCertificate();

        // Status filter
        if ($request->filled('status')) {
            $status = $request->status;
            $employees = $employees->filter(function($employee) use ($status) {
                return $employee['latest_certificate']
                    && $employee['latest_certificate']['status'] === $status;
            });
        }

        // Department filter
        if ($request->filled('department_id')) {
            $departmentId = $request->department_id;
            $employees = $employees->filter(function($employee) use ($departmentId) {
                // Get the actual employee model to check department
                $emp = Employee::find($employee['employee']['id']);
                return $emp && $emp->department_id == $departmentId;
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $employees = $employees->filter(function($employee) use ($search) {
                return str_contains(strtolower($employee['employee']['name']), $search) ||
                       str_contains(strtolower($employee['employee']['employee_id'] ?? ''), $search);
            });
        }

        return response()->json([
            'employees' => $employees->values(),
            'total' => $employees->count()
        ]);
    }

    /**
     * Get container statistics for this training type (AJAX endpoint)
     */
    public function getContainerStats(CertificateType $certificateType)
    {
        $stats = $certificateType->getContainerStatistics();

        return response()->json($stats);
    }

    /**
     * Search training types (for autocomplete/AJAX)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $certificateTypes = CertificateType::where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('category', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'code', 'category']);

        return response()->json($certificateTypes);
    }

    // ===== HELPER METHODS =====

    /**
     * Generate unique code from name
     */
    private function generateCode($name)
    {
        // Create base code from name
        $baseCode = strtoupper(Str::slug($name, '_'));
        $baseCode = substr($baseCode, 0, 20); // Limit length

        // Ensure uniqueness
        $code = $baseCode;
        $counter = 1;

        while (CertificateType::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Calculate compliance rate for training type
     */
    private function calculateComplianceRate(CertificateType $certificateType)
    {
        if (!$certificateType->is_mandatory) {
            return null; // Not applicable for non-mandatory certificates
        }

        $totalEmployees = Employee::where('status', 'active')->count();
        $employeesWithValidCert = $certificateType->employeeCertificates()
                                                 ->where('status', 'active')
                                                 ->distinct('employee_id')
                                                 ->count();

        if ($totalEmployees === 0) {
            return 0;
        }

        return round(($employeesWithValidCert / $totalEmployees) * 100, 1);
    }

    /**
     * Get statistics for training types index
     */
    private function getStats()
{
    return [
        'total_types' => CertificateType::count(),
        'active_types' => CertificateType::where('is_active', true)->count(),
        'recurrent_types' => CertificateType::where('is_recurrent', true)->count(),
        'types_with_certificates' => CertificateType::has('employeeCertificates')->count(),
    ];
}
}
