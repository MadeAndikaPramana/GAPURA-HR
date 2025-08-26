<?php

namespace App\Http\Controllers;

use App\Models\TrainingProvider;
use App\Models\Department;
use App\Models\TrainingType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TrainingProviderController extends Controller
{
    /**
     * Display a listing of training providers.
     */
    public function index(Request $request)
    {
        $query = TrainingProvider::query();

        // Apply search filter
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('contact_person', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'accredited') {
                $query->accredited();
            } elseif ($request->status === 'contract_active') {
                $query->withActiveContract();
            } elseif ($request->status === 'highly_rated') {
                $query->highlyRated();
            }
        }

        // Apply rating filter
        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        $providers = $query->orderBy('name')->paginate(15)->withQueryString();

        // Add statistics for each provider
        $providers->getCollection()->transform(function ($provider) {
            $provider->statistics = $provider->statistics;
            $provider->performance_metrics = $provider->performance_metrics;
            $provider->status_badge = $provider->status_badge;
            return $provider;
        });

        // Calculate overall statistics
        $stats = [
            'total_providers' => TrainingProvider::count(),
            'active_providers' => TrainingProvider::where('is_active', true)->count(),
            'accredited_providers' => TrainingProvider::accredited()->count(),
            'highly_rated_providers' => TrainingProvider::highlyRated()->count(),
            'providers_with_active_contracts' => TrainingProvider::withActiveContract()->count(),
            'average_rating' => round(TrainingProvider::where('is_active', true)->avg('rating'), 2),
        ];

        return Inertia::render('TrainingProviders/Index', [
            'providers' => $providers,
            'filters' => $request->only(['search', 'status', 'min_rating']),
            'stats' => $stats
        ]);
    }

    /**
     * Show the form for creating a new training provider.
     */
    public function create()
    {
        return Inertia::render('TrainingProviders/Create', [
            'departments' => Department::all(['id', 'name']),
            'trainingTypes' => TrainingType::where('is_active', true)->get(['id', 'name', 'code'])
        ]);
    }

    /**
     * Store a newly created training provider.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:training_providers',
            'code' => 'nullable|string|max:20|unique:training_providers',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'accreditation_number' => 'nullable|string|max:100',
            'accreditation_expiry' => 'nullable|date|after:today',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'rating' => 'nullable|numeric|min:0|max:5',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        TrainingProvider::create($validated);

        return redirect()->route('training-providers.index')
            ->with('success', 'Training provider created successfully.');
    }

    /**
     * Display the specified training provider.
     */
    public function show(TrainingProvider $trainingProvider)
    {
        $trainingProvider->load(['trainingRecords.employee', 'trainingRecords.trainingType']);

        // Get detailed statistics
        $statistics = $trainingProvider->statistics;
        $performanceMetrics = $trainingProvider->performance_metrics;

        // Get recent training records
        $recentTrainings = $trainingProvider->trainingRecords()
            ->with(['employee', 'trainingType'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get training types offered
        $offeredTrainingTypes = $trainingProvider->trainingRecords()
            ->with('trainingType')
            ->select('training_type_id')
            ->distinct()
            ->get()
            ->pluck('trainingType')
            ->filter()
            ->unique('id')
            ->values();

        // Get departments served
        $departmentsServed = $trainingProvider->trainingRecords()
            ->join('employees', 'employees.id', '=', 'training_records.employee_id')
            ->join('departments', 'departments.id', '=', 'employees.department_id')
            ->select('departments.name')
            ->distinct()
            ->get()
            ->pluck('name');

        return Inertia::render('TrainingProviders/Show', [
            'provider' => $trainingProvider,
            'statistics' => $statistics,
            'performanceMetrics' => $performanceMetrics,
            'recentTrainings' => $recentTrainings,
            'offeredTrainingTypes' => $offeredTrainingTypes,
            'departmentsServed' => $departmentsServed
        ]);
    }

    /**
     * Show the form for editing the training provider.
     */
    public function edit(TrainingProvider $trainingProvider)
    {
        return Inertia::render('TrainingProviders/Edit', [
            'provider' => $trainingProvider,
            'departments' => Department::all(['id', 'name']),
            'trainingTypes' => TrainingType::where('is_active', true)->get(['id', 'name', 'code'])
        ]);
    }

    /**
     * Update the specified training provider.
     */
    public function update(Request $request, TrainingProvider $trainingProvider)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:training_providers,name,' . $trainingProvider->id,
            'code' => 'nullable|string|max:20|unique:training_providers,code,' . $trainingProvider->id,
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'accreditation_number' => 'nullable|string|max:100',
            'accreditation_expiry' => 'nullable|date|after:today',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'rating' => 'nullable|numeric|min:0|max:5',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $trainingProvider->update($validated);

        return redirect()->route('training-providers.index')
            ->with('success', 'Training provider updated successfully.');
    }

    /**
     * Remove the specified training provider.
     */
    public function destroy(TrainingProvider $trainingProvider)
    {
        // Check if provider has associated training records
        if ($trainingProvider->trainingRecords()->count() > 0) {
            return back()->withErrors([
                'error' => 'Cannot delete training provider with existing training records. Consider deactivating instead.'
            ]);
        }

        $trainingProvider->delete();

        return redirect()->route('training-providers.index')
            ->with('success', 'Training provider deleted successfully.');
    }

    /**
     * Get provider statistics for API
     */
    public function getStatistics(TrainingProvider $trainingProvider)
    {
        $statistics = $trainingProvider->statistics;
        $performanceMetrics = $trainingProvider->performance_metrics;

        return response()->json([
            'statistics' => $statistics,
            'performance_metrics' => $performanceMetrics,
            'status_badge' => $trainingProvider->status_badge,
        ]);
    }

    /**
     * Update provider rating
     */
    public function updateRating(Request $request, TrainingProvider $trainingProvider)
    {
        $validated = $request->validate([
            'rating' => 'required|numeric|min:0|max:5',
            'notes' => 'nullable|string'
        ]);

        $trainingProvider->update([
            'rating' => $validated['rating'],
            'notes' => $validated['notes'] ? $trainingProvider->notes . "\n" . $validated['notes'] : $trainingProvider->notes
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Provider rating updated successfully',
            'rating' => $trainingProvider->rating
        ]);
    }

    /**
     * Bulk actions for providers
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'provider_ids' => 'required|array',
            'provider_ids.*' => 'exists:training_providers,id'
        ]);

        $providers = TrainingProvider::whereIn('id', $validated['provider_ids']);

        switch ($validated['action']) {
            case 'activate':
                $providers->update(['is_active' => true]);
                $message = 'Training providers activated successfully.';
                break;

            case 'deactivate':
                $providers->update(['is_active' => false]);
                $message = 'Training providers deactivated successfully.';
                break;

            case 'delete':
                // Check if any provider has training records
                $providersWithRecords = $providers->whereHas('trainingRecords')->count();
                if ($providersWithRecords > 0) {
                    return back()->withErrors([
                        'error' => 'Cannot delete providers with existing training records. Consider deactivating instead.'
                    ]);
                }
                $providers->delete();
                $message = 'Training providers deleted successfully.';
                break;
        }

        return redirect()->route('training-providers.index')
            ->with('success', $message);
    }

    /**
     * Get providers filtered by criteria (for API/AJAX calls)
     */
    public function getFilteredProviders(Request $request)
    {
        $query = TrainingProvider::where('is_active', true);

        // Filter by training type
        if ($request->filled('training_type_id')) {
            $query->whereHas('trainingRecords', function($q) use ($request) {
                $q->where('training_type_id', $request->training_type_id);
            });
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->whereHas('trainingRecords.employee', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Filter by rating
        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        $providers = $query->orderBy('name')->get(['id', 'name', 'code', 'rating']);

        return response()->json($providers);
    }

    /**
     * Check provider accreditation status
     */
    public function checkAccreditation()
    {
        $expiringProviders = TrainingProvider::whereNotNull('accreditation_expiry')
            ->where('accreditation_expiry', '<=', Carbon::now()->addDays(30))
            ->where('is_active', true)
            ->get();

        return response()->json([
            'expiring_soon' => $expiringProviders->count(),
            'providers' => $expiringProviders
        ]);
    }

    /**
     * Provider performance report
     */
    public function performanceReport(Request $request)
    {
        $providers = TrainingProvider::where('is_active', true)
            ->withCount('trainingRecords')
            ->get();

        $report = $providers->map(function ($provider) {
            return [
                'id' => $provider->id,
                'name' => $provider->name,
                'code' => $provider->code,
                'statistics' => $provider->statistics,
                'performance_metrics' => $provider->performance_metrics,
                'status_badge' => $provider->status_badge,
            ];
        });

        return response()->json($report);
    }
}
