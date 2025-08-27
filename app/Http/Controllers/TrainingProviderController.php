<?php

namespace App\Http\Controllers;

use App\Models\TrainingProvider;
use App\Models\TrainingRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TrainingProviderController extends Controller
{
    /**
     * Display a listing of training providers
     */
    public function index(Request $request)
    {
        $query = TrainingProvider::query();

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->filled('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        // Rating filter
        if ($request->filled('rating') && $request->rating !== '') {
            $ratingMap = [
                'excellent' => [4.5, 5.0],
                'good' => [3.5, 4.49],
                'average' => [2.5, 3.49],
                'poor' => [0, 2.49]
            ];

            if (isset($ratingMap[$request->rating])) {
                [$min, $max] = $ratingMap[$request->rating];
                $query->whereBetween('rating', [$min, $max]);
            }
        }

        // Accreditation status filter
        if ($request->filled('accreditation')) {
            switch ($request->accreditation) {
                case 'valid':
                    $query->where('accreditation_expiry', '>', now());
                    break;
                case 'expiring':
                    $query->whereBetween('accreditation_expiry', [now(), now()->addDays(90)]);
                    break;
                case 'expired':
                    $query->where('accreditation_expiry', '<=', now());
                    break;
            }
        }

        $providers = $query->withCount([
                'trainingRecords',
                'trainingRecords as completed_trainings' => function($q) {
                    $q->where('status', 'completed');
                },
                'trainingRecords as recent_trainings' => function($q) {
                    $q->where('completion_date', '>=', now()->subMonths(6));
                }
            ])
            ->paginate(15)
            ->withQueryString();

        // Calculate stats
        $stats = [
            'total' => TrainingProvider::count(),
            'active' => TrainingProvider::where('is_active', true)->count(),
            'with_accreditation' => TrainingProvider::whereNotNull('accreditation_number')->count(),
            'accreditation_expiring' => TrainingProvider::whereBetween('accreditation_expiry', [now(), now()->addDays(30)])->count(),
        ];

        // Get filter options
        $filterOptions = [
            'rating_ranges' => ['excellent', 'good', 'average', 'poor'],
            'accreditation_statuses' => ['valid', 'expiring', 'expired']
        ];

        return Inertia::render('TrainingProviders/Index', [
            'providers' => $providers,
            'filters' => $request->only(['search', 'status', 'rating', 'accreditation']),
            'stats' => $stats,
            'filterOptions' => $filterOptions
        ]);
    }

    /**
     * Show the form for creating a new training provider
     */
    public function create()
    {
        return Inertia::render('TrainingProviders/Create');
    }

    /**
     * Store a newly created training provider
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:training_providers',
            'code' => 'nullable|string|max:20|unique:training_providers',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'website' => 'nullable|url',
            'accreditation_number' => 'nullable|string|max:100',
            'accreditation_expiry' => 'nullable|date|after:today',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'rating' => 'nullable|numeric|min:0|max:5',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $provider = TrainingProvider::create([
            'name' => $request->name,
            'code' => $request->code ? strtoupper($request->code) : null,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'website' => $request->website,
            'accreditation_number' => $request->accreditation_number,
            'accreditation_expiry' => $request->accreditation_expiry,
            'contract_start_date' => $request->contract_start_date,
            'contract_end_date' => $request->contract_end_date,
            'rating' => $request->rating,
            'notes' => $request->notes,
            'is_active' => $request->boolean('is_active', true)
        ]);

        Log::info('Training Provider Created', [
            'provider_id' => $provider->id,
            'provider_name' => $provider->name,
            'user_id' => auth()->id()
        ]);

        return redirect()->route('training-providers.index')
            ->with('success', "Training provider \"{$provider->name}\" berhasil ditambahkan.");
    }

    /**
     * Display the specified training provider with statistics
     */
    public function show(TrainingProvider $trainingProvider)
    {
        $trainingProvider->load(['trainingRecords.employee.department', 'trainingRecords.trainingType']);

        // Calculate comprehensive statistics
        $stats = [
            'total_trainings' => $trainingProvider->trainingRecords()->count(),
            'completed_trainings' => $trainingProvider->trainingRecords()->where('status', 'completed')->count(),
            'active_certificates' => $trainingProvider->trainingRecords()->where('compliance_status', 'compliant')->count(),
            'expiring_certificates' => $trainingProvider->trainingRecords()->where('compliance_status', 'expiring_soon')->count(),
            'expired_certificates' => $trainingProvider->trainingRecords()->where('compliance_status', 'expired')->count(),
            'unique_employees' => $trainingProvider->trainingRecords()->distinct('employee_id')->count(),
            'unique_training_types' => $trainingProvider->trainingRecords()->distinct('training_type_id')->count(),
        ];

        // Performance metrics
        $performance = $this->calculatePerformanceMetrics($trainingProvider);

        // Recent training records
        $recentTrainings = $trainingProvider->trainingRecords()
            ->with(['employee.department', 'trainingType'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Training by type breakdown
        $trainingsByType = $trainingProvider->trainingRecords()
            ->with('trainingType')
            ->get()
            ->groupBy('trainingType.name')
            ->map(function($records) {
                return [
                    'total' => $records->count(),
                    'completed' => $records->where('status', 'completed')->count(),
                    'active' => $records->where('compliance_status', 'compliant')->count(),
                ];
            });

        // Training trend (last 12 months)
        $trainingTrend = $this->getTrainingTrend($trainingProvider);

        return Inertia::render('TrainingProviders/Show', [
            'provider' => $trainingProvider,
            'stats' => $stats,
            'performance' => $performance,
            'recentTrainings' => $recentTrainings,
            'trainingsByType' => $trainingsByType,
            'trainingTrend' => $trainingTrend
        ]);
    }

    /**
     * Show the form for editing the specified training provider
     */
    public function edit(TrainingProvider $trainingProvider)
    {
        return Inertia::render('TrainingProviders/Edit', [
            'provider' => $trainingProvider
        ]);
    }

    /**
     * Update the specified training provider
     */
    public function update(Request $request, TrainingProvider $trainingProvider)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:training_providers,name,' . $trainingProvider->id,
            'code' => 'nullable|string|max:20|unique:training_providers,code,' . $trainingProvider->id,
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'website' => 'nullable|url',
            'accreditation_number' => 'nullable|string|max:100',
            'accreditation_expiry' => 'nullable|date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'rating' => 'nullable|numeric|min:0|max:5',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $trainingProvider->update([
            'name' => $request->name,
            'code' => $request->code ? strtoupper($request->code) : null,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'website' => $request->website,
            'accreditation_number' => $request->accreditation_number,
            'accreditation_expiry' => $request->accreditation_expiry,
            'contract_start_date' => $request->contract_start_date,
            'contract_end_date' => $request->contract_end_date,
            'rating' => $request->rating,
            'notes' => $request->notes,
            'is_active' => $request->boolean('is_active')
        ]);

        Log::info('Training Provider Updated', [
            'provider_id' => $trainingProvider->id,
            'provider_name' => $trainingProvider->name,
            'user_id' => auth()->id()
        ]);

        return redirect()->route('training-providers.index')
            ->with('success', "Training provider \"{$trainingProvider->name}\" berhasil diupdate.");
    }

    /**
     * Remove the specified training provider
     */
    public function destroy(TrainingProvider $trainingProvider)
    {
        try {
            // Check if provider has training records
            $recordsCount = $trainingProvider->trainingRecords()->count();

            if ($recordsCount > 0) {
                $errorMessage = "❌ TIDAK DAPAT MENGHAPUS Training Provider \"{$trainingProvider->name}\"\n\n" .
                               "Alasan: Masih terdapat {$recordsCount} training record(s) yang menggunakan provider ini.\n\n" .
                               "🔧 LANGKAH PENYELESAIAN:\n" .
                               "1. Masuk ke menu 'Training Records'\n" .
                               "2. Filter berdasarkan Training Provider = \"{$trainingProvider->name}\"\n" .
                               "3. Hapus SEMUA training records yang terkait\n" .
                               "4. Baru kemudian hapus training provider ini\n\n" .
                               "💡 ALTERNATIF: Non-aktifkan provider ini agar tidak muncul di dropdown baru.";

                Log::warning('Training Provider Delete Blocked', [
                    'provider_id' => $trainingProvider->id,
                    'provider_name' => $trainingProvider->name,
                    'related_records_count' => $recordsCount,
                    'user_id' => auth()->id()
                ]);

                if (request()->expectsJson() || request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'records_count' => $recordsCount
                    ], 422);
                }

                return redirect()->route('training-providers.index')
                    ->with('error', $errorMessage);
            }

            $providerName = $trainingProvider->name;
            $trainingProvider->delete();

            Log::info('Training Provider Deleted', [
                'provider_name' => $providerName,
                'user_id' => auth()->id()
            ]);

            $successMessage = "✅ Training Provider \"{$providerName}\" berhasil dihapus.";

            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage
                ]);
            }

            return redirect()->route('training-providers.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            $errorMessage = '❌ Gagal menghapus training provider: ' . $e->getMessage();

            Log::error('Training Provider Delete Error', [
                'provider_id' => $trainingProvider->id ?? 'unknown',
                'error_message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return redirect()->route('training-providers.index')
                ->with('error', $errorMessage);
        }
    }

    /**
     * Toggle provider status
     */
    public function toggleStatus(TrainingProvider $trainingProvider)
    {
        $trainingProvider->update([
            'is_active' => !$trainingProvider->is_active
        ]);

        $status = $trainingProvider->is_active ? 'activated' : 'deactivated';

        Log::info('Training Provider Status Toggled', [
            'provider_id' => $trainingProvider->id,
            'provider_name' => $trainingProvider->name,
            'new_status' => $trainingProvider->is_active ? 'active' : 'inactive',
            'user_id' => auth()->id()
        ]);

        return redirect()->back()
            ->with('success', "Training provider \"{$trainingProvider->name}\" berhasil {$status}.");
    }

    /**
     * Calculate performance metrics for a provider
     */
    private function calculatePerformanceMetrics(TrainingProvider $provider)
    {
        $recentRecords = $provider->trainingRecords()
            ->where('completion_date', '>=', now()->subMonths(6))
            ->whereNotNull('score')
            ->get();

        $totalRecords = $provider->trainingRecords()->count();
        $completedRecords = $provider->trainingRecords()->where('status', 'completed')->count();

        return [
            'completion_rate' => $totalRecords > 0 ? round(($completedRecords / $totalRecords) * 100, 2) : 0,
            'average_score' => $recentRecords->isNotEmpty() ? round($recentRecords->avg('score'), 2) : null,
            'total_trainings_delivered' => $completedRecords,
            'recent_performance_trend' => $this->getPerformanceTrend($provider),
            'accreditation_status' => $provider->accreditation_status
        ];
    }

    /**
     * Get training trend for the last 12 months
     */
    private function getTrainingTrend(TrainingProvider $provider)
    {
        $trend = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = $provider->trainingRecords()
                ->whereYear('completion_date', $date->year)
                ->whereMonth('completion_date', $date->month)
                ->count();

            $trend[] = [
                'month' => $date->format('M Y'),
                'count' => $count
            ];
        }

        return $trend;
    }

    /**
     * Get performance trend
     */
    private function getPerformanceTrend(TrainingProvider $provider)
    {
        // Simple trend calculation based on recent vs older performance
        $recentAvg = $provider->trainingRecords()
            ->where('completion_date', '>=', now()->subMonths(3))
            ->whereNotNull('score')
            ->avg('score');

        $olderAvg = $provider->trainingRecords()
            ->whereBetween('completion_date', [now()->subMonths(6), now()->subMonths(3)])
            ->whereNotNull('score')
            ->avg('score');

        if (!$recentAvg || !$olderAvg) return 'insufficient_data';

        $diff = $recentAvg - $olderAvg;

        if ($diff > 5) return 'improving';
        if ($diff < -5) return 'declining';
        return 'stable';
    }

    /**
     * Get statistics for a specific provider (API endpoint)
     */
    public function getStatistics(TrainingProvider $trainingProvider)
    {
        return response()->json([
            'provider' => $trainingProvider->only(['id', 'name', 'rating', 'is_active']),
            'statistics' => $this->calculatePerformanceMetrics($trainingProvider)
        ]);
    }

    /**
     * Update provider rating (API endpoint)
     */
    public function updateRating(Request $request, TrainingProvider $trainingProvider)
    {
        $request->validate([
            'rating' => 'required|numeric|min:0|max:5'
        ]);

        $trainingProvider->update(['rating' => $request->rating]);

        return response()->json([
            'success' => true,
            'message' => 'Rating updated successfully',
            'rating' => $trainingProvider->rating
        ]);
    }

    /**
     * Bulk actions on providers
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'provider_ids' => 'required|array|min:1',
            'provider_ids.*' => 'exists:training_providers,id'
        ]);

        $providers = TrainingProvider::whereIn('id', $request->provider_ids)->get();
        $successCount = 0;
        $errors = [];

        foreach ($providers as $provider) {
            try {
                switch ($request->action) {
                    case 'activate':
                        $provider->update(['is_active' => true]);
                        $successCount++;
                        break;
                    case 'deactivate':
                        $provider->update(['is_active' => false]);
                        $successCount++;
                        break;
                    case 'delete':
                        if ($provider->trainingRecords()->count() === 0) {
                            $provider->delete();
                            $successCount++;
                        } else {
                            $errors[] = "Cannot delete {$provider->name} - has training records";
                        }
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to {$request->action} {$provider->name}: " . $e->getMessage();
            }
        }

        $message = "{$successCount} providers successfully {$request->action}d.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }

        return redirect()->back()->with('success', $message);
    }
}
