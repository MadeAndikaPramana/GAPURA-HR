<?php
// app/Http/Controllers/TrainingTypeController.php

namespace App\Http\Controllers;

use App\Models\TrainingType;
use App\Models\TrainingProvider;
use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Services\TrainingTypeAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class TrainingTypeController extends Controller
{
    protected $analyticsService;

    public function __construct()
    {
        // Only create analytics service if it exists
        if (class_exists(\App\Services\TrainingTypeAnalyticsService::class)) {
            $this->analyticsService = app(TrainingTypeAnalyticsService::class);
        }
    }

    /**
     * Display training types with analytics dashboard
     */
    public function index(Request $request)
    {
        // Get basic analytics data
        $analytics = [];
        $complianceOverview = [];
        $monthlyTrends = [];
        $costAnalytics = [];

        // If analytics service is available, use it
        if ($this->analyticsService) {
            try {
                $analytics = $this->analyticsService->getTrainingTypeAnalytics();
                $complianceOverview = $this->analyticsService->getComplianceOverview();
                $monthlyTrends = $this->analyticsService->getMonthlyTrainingTrends();
                $costAnalytics = $this->analyticsService->getTrainingCostAnalytics();
            } catch (\Exception $e) {
                // Fallback to basic data if analytics fail
                logger('Training Type Analytics Error: ' . $e->getMessage());
            }
        }

        // Get basic training types data
        $trainingTypes = TrainingType::withCount([
            'trainingRecords',
            'trainingRecords as active_count' => function ($q) {
                $q->where('status', 'active');
            },
            'trainingRecords as expiring_count' => function ($q) {
                $q->where('status', 'expiring_soon');
            },
            'trainingRecords as expired_count' => function ($q) {
                $q->where('status', 'expired');
            }
        ])->paginate(15);

        return Inertia::render('TrainingTypes/Index', [
            'trainingTypes' => $trainingTypes,
            'analytics' => $analytics,
            'complianceOverview' => $complianceOverview,
            'monthlyTrends' => $monthlyTrends,
            'costAnalytics' => $costAnalytics,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $providers = TrainingProvider::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('TrainingTypes/Create', [
            'providers' => $providers,
            'categoryOptions' => $this->getCategoryOptions(),
        ]);
    }

    /**
     * Store new training type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:training_types',
            'code' => 'required|string|max:20|unique:training_types',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'validity_months' => 'nullable|integer|min:1|max:120',
            // Add Phase 3 fields if they exist in the table
            'is_mandatory' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'validity_period_months' => 'sometimes|integer|min:1|max:120',
            'warning_period_days' => 'sometimes|integer|min:1|max:365',
            'estimated_cost' => 'sometimes|nullable|numeric|min:0',
            'estimated_duration_hours' => 'sometimes|nullable|numeric|min:0.5',
            'requirements' => 'sometimes|nullable|string',
            'learning_objectives' => 'sometimes|nullable|string',
        ]);

        // Set defaults for Phase 3 fields if they don't exist
        $validated = array_merge([
            'is_active' => true,
            'validity_months' => $validated['validity_period_months'] ?? $validated['validity_months'] ?? 12,
        ], $validated);

        try {
            $trainingType = TrainingType::create($validated);

            return redirect()
                ->route('training-types.index')
                ->with('success', "Training type '{$trainingType->name}' berhasil dibuat!");

        } catch (\Exception $e) {
            return back()
                ->withErrors(['general' => 'Gagal membuat training type: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show training type details
     */
    public function show(TrainingType $trainingType)
    {
        $trainingType->load([
            'trainingRecords' => function ($query) {
                $query->with('employee:id,name,employee_id,department_id')
                      ->with('employee.department:id,name')
                      ->orderBy('completion_date', 'desc')
                      ->limit(10);
            }
        ]);

        $trainingType->loadCount([
            'trainingRecords',
            'trainingRecords as active_count' => function ($q) {
                $q->where('status', 'active');
            },
            'trainingRecords as expiring_count' => function ($q) {
                $q->where('status', 'expiring_soon');
            },
            'trainingRecords as expired_count' => function ($q) {
                $q->where('status', 'expired');
            }
        ]);

        // Calculate compliance rate
        $totalEmployees = Employee::count();
        $complianceRate = $totalEmployees > 0
            ? round(($trainingType->active_count / $totalEmployees) * 100, 1)
            : 0;

        return Inertia::render('TrainingTypes/Show', [
            'trainingType' => $trainingType,
            'complianceRate' => $complianceRate,
            'stats' => [
                'total_certificates' => $trainingType->training_records_count,
                'active_certificates' => $trainingType->active_count,
                'expiring_certificates' => $trainingType->expiring_count,
                'expired_certificates' => $trainingType->expired_count,
            ]
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(TrainingType $trainingType)
    {
        $providers = TrainingProvider::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('TrainingTypes/Edit', [
            'trainingType' => $trainingType,
            'providers' => $providers,
            'categoryOptions' => $this->getCategoryOptions(),
        ]);
    }

    /**
     * Update training type
     */
    public function update(Request $request, TrainingType $trainingType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:training_types,name,' . $trainingType->id,
            'code' => 'required|string|max:20|unique:training_types,code,' . $trainingType->id,
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'validity_months' => 'nullable|integer|min:1|max:120',
            // Add Phase 3 fields if they exist
            'is_mandatory' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'validity_period_months' => 'sometimes|integer|min:1|max:120',
            'warning_period_days' => 'sometimes|integer|min:1|max:365',
            'estimated_cost' => 'sometimes|nullable|numeric|min:0',
            'estimated_duration_hours' => 'sometimes|nullable|numeric|min:0.5',
            'requirements' => 'sometimes|nullable|string',
            'learning_objectives' => 'sometimes|nullable|string',
        ]);

        try {
            $trainingType->update($validated);

            return redirect()
                ->route('training-types.index')
                ->with('success', "Training type '{$trainingType->name}' berhasil diupdate!");

        } catch (\Exception $e) {
            return back()
                ->withErrors(['general' => 'Gagal mengupdate training type: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Delete training type
     */
    public function destroy(TrainingType $trainingType)
{
    try {
        // Check if training type has training records
        $recordsCount = $trainingType->trainingRecords()->count();

        if ($recordsCount > 0) {
            $errorMessage = "Tidak dapat menghapus training type \"{$trainingType->name}\" karena memiliki {$recordsCount} training record(s). Hapus training records terlebih dahulu atau non-aktifkan training type ini.";

            // Return JSON response for AJAX requests
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'records_count' => $recordsCount
                ], 422);
            }

            return redirect()->route('training-types.index')
                ->with('error', $errorMessage);
        }

        // Log the delete action
        Log::info('TrainingType Delete Attempt', [
            'training_type_id' => $trainingType->id,
            'training_type_name' => $trainingType->name,
            'user_id' => auth()->id(),
            'records_count' => $recordsCount
        ]);

        $trainingTypeName = $trainingType->name;
        $trainingType->delete();

        Log::info('TrainingType Deleted Successfully', [
            'training_type_name' => $trainingTypeName,
            'user_id' => auth()->id()
        ]);

        // Return JSON response for AJAX requests
        if (request()->expectsJson() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Training type \"{$trainingTypeName}\" berhasil dihapus.",
                'redirect' => route('training-types.index')
            ]);
        }

        return redirect()->route('training-types.index')
            ->with('success', "Training type \"{$trainingTypeName}\" berhasil dihapus.");

    } catch (\Exception $e) {
        $errorMessage = 'Gagal menghapus training type: ' . $e->getMessage();

        Log::error('TrainingType Delete Error', [
            'training_type_id' => $trainingType->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id()
        ]);

        // Return JSON response for AJAX requests
        if (request()->expectsJson() || request()->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 500);
        }

        return redirect()->route('training-types.index')
            ->with('error', $errorMessage);
    }
}

    /**
     * Get category options
     */
    private function getCategoryOptions(): array
    {
        return [
            'Safety' => 'Safety',
            'Security' => 'Security',
            'Aviation' => 'Aviation',
            'Technical' => 'Technical',
            'Compliance' => 'Compliance',
            'Quality' => 'Quality',
            'Service' => 'Service',
            'Operations' => 'Operations',
            'Management' => 'Management',
            'Finance' => 'Finance',
            'IT' => 'IT & Technology'
        ];
    }
}
