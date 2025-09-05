<?php

namespace App\Http\Controllers;

use App\Models\CertificateType;
use App\Models\Employee;
use App\Models\EmployeeCertificate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class TrainingTypeController extends Controller
{
    /**
     * Display training types list
     */
    public function index(Request $request)
    {
        $query = CertificateType::withCount([
            'employeeCertificates as total_certificates',
            'activeCertificates as active_certificates'
        ]);

        // Search functionality
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
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        $certificateTypes = $query->orderBy('name')->paginate(15);

        // Transform data for enhanced display
        $certificateTypes->getCollection()->transform(function ($type) {
            // Get unique employees count
            $uniqueEmployees = EmployeeCertificate::where('certificate_type_id', $type->id)
                ->distinct('employee_id')
                ->count();

            $type->unique_employees_count = $uniqueEmployees;
            $type->recency_score = $this->calculateRecencyScore($type);

            return $type;
        });

        // Get categories for filter
        $categories = CertificateType::where('is_active', true)
            ->whereNotNull('category')
            ->distinct('category')
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('TrainingTypes/Index', [
            'certificateTypes' => $certificateTypes,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status']),
            'stats' => $this->getStats(),
        ]);
    }

    /**
     * Show form for creating new training type
     */
    public function create()
    {
        // Get existing categories for dropdown
        $existingCategories = CertificateType::whereNotNull('category')
            ->distinct('category')
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('TrainingTypes/Create', [
            'existingCategories' => $existingCategories,
        ]);
    }

    /**
     * Store new training type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:certificate_types,name',
            'code' => 'nullable|string|max:50|unique:certificate_types,code',
            'category' => 'nullable|string|max:50',
            'validity_months' => 'nullable|integer|min:1|max:120',
            'warning_days' => 'nullable|integer|min:1|max:365',
            'is_mandatory' => 'boolean',
            'is_recurrent' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'requirements' => 'nullable|string|max:1000',
            'learning_objectives' => 'nullable|string|max:1000',
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_duration_hours' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
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
        // Get existing categories for dropdown
        $existingCategories = CertificateType::whereNotNull('category')
            ->where('id', '!=', $certificateType->id)
            ->distinct('category')
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('TrainingTypes/Edit', [
            'certificateType' => $certificateType,
            'existingCategories' => $existingCategories,
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
            'category' => 'nullable|string|max:50',
            'validity_months' => 'nullable|integer|min:1|max:120',
            'warning_days' => 'nullable|integer|min:1|max:365',
            'is_mandatory' => 'boolean',
            'is_recurrent' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'requirements' => 'nullable|string|max:1000',
            'learning_objectives' => 'nullable|string|max:1000',
            'estimated_cost' => 'nullable|numeric|min:0',
            'estimated_duration_hours' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
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
     */
    public function showContainer(CertificateType $certificateType)
    {
        // Get container data (reverse lookup)
        $containerData = $certificateType->getContainerData();

        return Inertia::render('TrainingTypes/Container', [
            'certificateType' => $certificateType,
            'containerData' => $containerData,
            'breadcrumb' => [
                ['name' => 'Training Types', 'url' => route('training-types.index')],
                ['name' => $certificateType->name, 'url' => null]
            ]
        ]);
    }

    /**
     * Get employees list for this training type
     */
    public function getEmployeesList(Request $request, CertificateType $certificateType)
    {
        $query = $certificateType->getEmployeesWithCertificate();

        // Status filter
        if ($request->filled('status')) {
            $status = $request->status;
            $query = $query->filter(function($employee) use ($status) {
                $latestCert = $employee->employeeCertificates->first();
                return $latestCert && $latestCert->status === $status;
            });
        }

        // Department filter
        if ($request->filled('department')) {
            $departmentId = $request->department;
            $query = $query->filter(function($employee) use ($departmentId) {
                return $employee->department_id == $departmentId;
            });
        }

        $employees = $query->values(); // Reset collection keys

        // Transform for API response
        $transformedEmployees = $employees->map(function($employee) {
            $latestCert = $employee->employeeCertificates->first();
            $allCerts = $employee->employeeCertificates;

            return [
                'employee' => [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'nip' => $employee->nip,
                    'name' => $employee->name,
                    'department' => $employee->department?->name,
                    'position' => $employee->position,
                    'status' => $employee->status
                ],
                'latest_certificate' => $latestCert ? [
                    'id' => $latestCert->id,
                    'certificate_number' => $latestCert->certificate_number,
                    'issue_date' => $latestCert->issue_date,
                    'expiry_date' => $latestCert->expiry_date,
                    'status' => $latestCert->status,
                    'issuer' => $latestCert->issuer,
                ] : null,
                'certificates_count' => $allCerts->count(),
                'certificates_history' => $allCerts->map(function($cert) {
                    return [
                        'id' => $cert->id,
                        'certificate_number' => $cert->certificate_number,
                        'issue_date' => $cert->issue_date,
                        'expiry_date' => $cert->expiry_date,
                        'status' => $cert->status,
                    ];
                })->toArray()
            ];
        });

        return response()->json($transformedEmployees);
    }

    /**
     * Get statistics for training type
     */
    public function getStatistics(CertificateType $certificateType)
    {
        $stats = $certificateType->getCertificateStats();

        // Add additional statistics
        $recentCertificates = $certificateType->employeeCertificates()
            ->with(['employee'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        $expiringCertificates = $certificateType->employeeCertificates()
            ->with(['employee'])
            ->where('status', 'expiring_soon')
            ->orderBy('expiry_date')
            ->get();

        return response()->json([
            'statistics' => $stats,
            'recent_certificates' => $recentCertificates,
            'expiring_certificates' => $expiringCertificates,
            'compliance_rate' => $this->calculateComplianceRate($certificateType),
        ]);
    }

    // ===== SEARCH & API METHODS =====

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
     * Calculate recency score for training type
     */
    private function calculateRecencyScore(CertificateType $certificateType)
    {
        $recentCertificates = $certificateType->employeeCertificates()
            ->where('created_at', '>=', now()->subMonths(6))
            ->count();

        $totalCertificates = $certificateType->employeeCertificates()->count();

        if ($totalCertificates === 0) {
            return 0;
        }

        return round(($recentCertificates / $totalCertificates) * 100, 1);
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
            'mandatory_types' => CertificateType::where('is_mandatory', true)->count(),
            'categories_count' => CertificateType::whereNotNull('category')
                ->distinct('category')
                ->count(),
            'types_with_certificates' => CertificateType::has('employeeCertificates')->count(),
        ];
    }
}
