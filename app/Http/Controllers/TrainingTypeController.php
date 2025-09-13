<?php
// app/Http/Controllers/TrainingTypeController.php - Complete & Fixed

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
    $performanceService = app(\App\Services\PerformanceOptimizationService::class);
    
    // Build cache key based on filters
    $cacheKey = 'training_types_index_' . md5(serialize($request->only(['search', 'status', 'category', 'page'])));
    
    $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($request, $performanceService) {
        $query = CertificateType::query();

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'mandatory':
                    $query->where('is_mandatory', true);
                    break;
            }
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Optimized query with eager loading and selective fields
        $certificateTypes = $query->select([
                'id', 'name', 'code', 'category', 'is_active', 'is_mandatory', 
                'validity_months', 'warning_days', 'description'
            ])
            ->withCount([
                'employeeCertificates as total_certificates',
                'employeeCertificates as active_certificates' => function ($query) {
                    $query->where('status', 'active');
                },
                'employeeCertificates as expired_certificates' => function ($query) {
                    $query->where('status', 'expired');
                },
                'employeeCertificates as expiring_soon_certificates' => function ($query) {
                    $query->where('status', 'expiring_soon');
                },
            ])
            ->withCount([
                'employeeCertificates as unique_employees' => function ($query) {
                    $query->distinct('employee_id');
                }
            ])
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        // Transform to add container_stats without additional queries
        $certificateTypes->getCollection()->transform(function ($certificateType) {
            $certificateType->container_stats = [
                'total_certificates' => $certificateType->total_certificates,
                'active_certificates' => $certificateType->active_certificates,
                'expired_certificates' => $certificateType->expired_certificates,
                'expiring_soon_certificates' => $certificateType->expiring_soon_certificates,
                'unique_employees' => $certificateType->unique_employees,
            ];

            // Remove count attributes to clean up response
            unset($certificateType->total_certificates, $certificateType->active_certificates, 
                  $certificateType->expired_certificates, $certificateType->expiring_soon_certificates,
                  $certificateType->unique_employees);

            return $certificateType;
        });

        return [
            'certificateTypes' => $certificateTypes,
            'stats' => $performanceService->getCertificateTypeStatistics()->count() > 0 
                ? collect($performanceService->getCertificateTypeStatistics())->take(10) 
                : $this->getStats(),
        ];
    });

    return Inertia::render('TrainingTypes/Index', [
        'certificateTypes' => $data['certificateTypes'],
        'filters' => $request->only(['search', 'status', 'category']),
        'stats' => $data['stats'],
    ]);
}

    /**
     * Show form for creating new training type
     */
    public function create()
    {
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

    /**
     * Show training type container (who has this certificate?) - FIXED
     */
   public function showContainer(CertificateType $certificateType)
{
    // Load employees with certificates for this type
    $employees = Employee::whereHas('employeeCertificates', function($query) use ($certificateType) {
        $query->where('certificate_type_id', $certificateType->id);
    })->with([
        'department',
        'employeeCertificates' => function($query) use ($certificateType) {
            $query->where('certificate_type_id', $certificateType->id)
                  ->orderBy('created_at', 'desc');
        }
    ])->get();

    // Transform employees data for the container view
    $employeesData = $employees->map(function($employee) {
        $certificates = $employee->employeeCertificates;
        $latestCert = $certificates->first();

        return [
            'employee' => [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id ?? $employee->nip,
                'name' => $employee->name,
                'position' => $employee->position,
                'department' => $employee->department->name ?? 'No Department',
                'department_id' => $employee->department_id,
                'status' => $employee->status,
            ],
            'latest_certificate' => $latestCert ? [
                'id' => $latestCert->id,
                'certificate_number' => $latestCert->certificate_number,
                'status' => $latestCert->status,
                'issue_date' => $latestCert->issue_date,
                'expiry_date' => $latestCert->expiry_date,
                'issuer' => $latestCert->issuer,
            ] : null,
            'certificates_history' => [
                'total_count' => $certificates->count(),
                'active_count' => $certificates->where('status', 'active')->count(),
                'expired_count' => $certificates->where('status', 'expired')->count(),
                'expiring_soon_count' => $certificates->where('status', 'expiring_soon')->count(),
            ],
        ];
    });

    // Container statistics
    $containerData = [
        'statistics' => [
            'total_certificates' => $certificateType->employeeCertificates->count(),
            'active_certificates' => $certificateType->employeeCertificates->where('status', 'active')->count(),
            'expired_certificates' => $certificateType->employeeCertificates->where('status', 'expired')->count(),
            'expiring_soon_certificates' => $certificateType->employeeCertificates->where('status', 'expiring_soon')->count(),
            'unique_employees' => $employees->count(),
            'compliance_rate' => $this->calculateComplianceRate($certificateType),
        ],
        'employees' => $employeesData,
    ];

    // Get departments for filter
    $departments = \App\Models\Department::all(['id', 'name']);

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
     * Calculate compliance rate for mandatory certificates
     */
   private function calculateComplianceRate(CertificateType $certificateType)
{
    if (!$certificateType->is_mandatory) {
        return null;
    }

    $totalActiveEmployees = Employee::where('status', 'active')->count();
    $employeesWithValidCert = $certificateType->employeeCertificates()
        ->where('status', 'active')
        ->distinct('employee_id')
        ->count();

    if ($totalActiveEmployees === 0) {
        return 0;
    }

    return round(($employeesWithValidCert / $totalActiveEmployees) * 100, 2);
}

    /**
     * Get employees list for this training type - MISSING METHOD
     */
    public function getEmployeesList(Request $request, CertificateType $certificateType)
    {
        $employees = Employee::whereHas('employeeCertificates', function($query) use ($certificateType) {
            $query->where('certificate_type_id', $certificateType->id);
        })->with([
            'department',
            'employeeCertificates' => function($query) use ($certificateType) {
                $query->where('certificate_type_id', $certificateType->id)
                      ->orderBy('created_at', 'desc');
            }
        ])->get();

        return response()->json($employees);
    }

    /**
     * Analytics for training type - MISSING METHOD
     */
    public function analytics(CertificateType $certificateType)
    {
        $analytics = [
            'total_certificates' => $certificateType->employeeCertificates->count(),
            'active_certificates' => $certificateType->employeeCertificates->where('status', 'active')->count(),
            'expired_certificates' => $certificateType->employeeCertificates->where('status', 'expired')->count(),
            'unique_employees' => $certificateType->employeeCertificates->unique('employee_id')->count(),
        ];

        return Inertia::render('TrainingTypes/Analytics', [
            'certificateType' => $certificateType,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Search training types (AJAX) - MISSING METHOD
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

    /**
     * Get categories (API) - MISSING METHOD
     */
    public function getCategories()
    {
        $categories = CertificateType::whereNotNull('category')
            ->distinct('category')
            ->pluck('category')
            ->sort()
            ->values();

        return response()->json($categories);
    }

    /**
     * Bulk actions - MISSING METHOD
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'selected' => 'required|array|min:1',
            'selected.*' => 'exists:certificate_types,id'
        ]);

        $certificateTypes = CertificateType::whereIn('id', $request->selected);
        $count = count($request->selected);

        switch ($request->action) {
            case 'activate':
                $certificateTypes->update(['is_active' => true]);
                return back()->with('success', "{$count} training types activated successfully.");

            case 'deactivate':
                $certificateTypes->update(['is_active' => false]);
                return back()->with('success', "{$count} training types deactivated successfully.");

            case 'delete':
                $certificateTypes->delete();
                return back()->with('success', "{$count} training types deleted successfully.");
        }

        return back();
    }

    /**
     * Bulk delete - MISSING METHOD
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'exists:certificate_types,id'
        ]);

        CertificateType::whereIn('id', $request->selected)->delete();
        $count = count($request->selected);

        return back()->with('success', "{$count} training types deleted successfully.");
    }

    // ===== HELPER METHODS =====

    /**
     * Generate unique code from name
     */
    private function generateCode($name)
    {
        $baseCode = strtoupper(Str::slug($name, '_'));
        $baseCode = substr($baseCode, 0, 20);

        $code = $baseCode;
        $counter = 1;

        while (CertificateType::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }

        return $code;
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
