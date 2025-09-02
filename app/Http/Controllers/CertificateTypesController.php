<?php
// app/Http/Controllers/CertificateTypesController.php

namespace App\Http\Controllers;

use App\Models\CertificateType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CertificateTypesController extends Controller
{
    /**
     * Display a listing of certificate types
     */
    public function index(Request $request)
    {
        $query = CertificateType::query();

        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $certificateTypes = $query->withCount(['employeeCertificates', 'activeCertificates', 'expiredCertificates'])
                                 ->orderBy('name')
                                 ->paginate(15);

        // Get unique categories for filter dropdown
        $categories = CertificateType::select('category')
                                   ->distinct()
                                   ->pluck('category')
                                   ->sort();

        return Inertia::render('CertificateTypes/Index', [
            'certificateTypes' => $certificateTypes,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status'])
        ]);
    }

    /**
     * Show the form for creating a new certificate type
     */
    public function create()
    {
        $categories = CertificateType::select('category')
                                   ->distinct()
                                   ->pluck('category')
                                   ->sort();

        return Inertia::render('CertificateTypes/Create', [
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created certificate type
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:certificate_types',
            'code' => 'required|string|max:10|unique:certificate_types',
            'category' => 'required|string|max:50',
            'validity_months' => 'required|integer|min:0|max:120',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        try {
            $certificateType = CertificateType::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'category' => strtoupper($request->category),
                'validity_months' => $request->validity_months,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', true)
            ]);

            Log::info('Certificate type created', [
                'certificate_type_id' => $certificateType->id,
                'name' => $certificateType->name,
                'code' => $certificateType->code
            ]);

            return redirect()->route('certificate-types.index')
                           ->with('success', 'Certificate type created successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to create certificate type', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create certificate type: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified certificate type
     */
    public function show(CertificateType $certificateType)
    {
        $certificateType->load(['employeeCertificates.employee']);

        // Get statistics
        $statistics = $certificateType->getStatistics();

        // Get recent certificates of this type
        $recentCertificates = $certificateType->employeeCertificates()
                                            ->with(['employee'])
                                            ->orderBy('created_at', 'desc')
                                            ->limit(10)
                                            ->get();

        return Inertia::render('CertificateTypes/Show', [
            'certificateType' => $certificateType,
            'statistics' => $statistics,
            'recentCertificates' => $recentCertificates
        ]);
    }

    /**
     * Show the form for editing the specified certificate type
     */
    public function edit(CertificateType $certificateType)
    {
        $categories = CertificateType::select('category')
                                   ->distinct()
                                   ->pluck('category')
                                   ->sort();

        return Inertia::render('CertificateTypes/Edit', [
            'certificateType' => $certificateType,
            'categories' => $categories
        ]);
    }

    /**
     * Update the specified certificate type
     */
    public function update(Request $request, CertificateType $certificateType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:certificate_types,name,' . $certificateType->id,
            'code' => 'required|string|max:10|unique:certificate_types,code,' . $certificateType->id,
            'category' => 'required|string|max:50',
            'validity_months' => 'required|integer|min:0|max:120',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        try {
            $certificateType->update([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'category' => strtoupper($request->category),
                'validity_months' => $request->validity_months,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', true)
            ]);

            Log::info('Certificate type updated', [
                'certificate_type_id' => $certificateType->id,
                'name' => $certificateType->name,
                'code' => $certificateType->code
            ]);

            return redirect()->route('certificate-types.index')
                           ->with('success', 'Certificate type updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update certificate type', [
                'certificate_type_id' => $certificateType->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to update certificate type: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified certificate type
     */
    public function destroy(CertificateType $certificateType)
    {
        try {
            // Check if certificate type has associated certificates
            $certificateCount = $certificateType->employeeCertificates()->count();

            if ($certificateCount > 0) {
                return back()->withErrors([
                    'error' => "Cannot delete certificate type. It has {$certificateCount} certificates associated with it."
                ]);
            }

            $name = $certificateType->name;
            $certificateType->delete();

            Log::info('Certificate type deleted', [
                'name' => $name
            ]);

            return redirect()->route('certificate-types.index')
                           ->with('success', 'Certificate type deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete certificate type', [
                'certificate_type_id' => $certificateType->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to delete certificate type: ' . $e->getMessage()]);
        }
    }

    /**
     * Get statistics for a certificate type
     */
    public function statistics(CertificateType $certificateType)
    {
        try {
            $statistics = $certificateType->getStatistics();

            // Additional analytics
            $monthlyData = $certificateType->employeeCertificates()
                                         ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
                                         ->groupBy('year', 'month')
                                         ->orderBy('year', 'desc')
                                         ->orderBy('month', 'desc')
                                         ->limit(12)
                                         ->get();

            $statusBreakdown = $certificateType->employeeCertificates()
                                             ->selectRaw('status, COUNT(*) as count')
                                             ->groupBy('status')
                                             ->get();

            return response()->json([
                'statistics' => $statistics,
                'monthly_data' => $monthlyData,
                'status_breakdown' => $statusBreakdown
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get certificate type statistics', [
                'certificate_type_id' => $certificateType->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to get statistics'], 500);
        }
    }

    /**
     * Toggle certificate type active status
     */
    public function toggleStatus(CertificateType $certificateType)
    {
        try {
            $certificateType->update(['is_active' => !$certificateType->is_active]);

            $status = $certificateType->is_active ? 'activated' : 'deactivated';

            Log::info('Certificate type status toggled', [
                'certificate_type_id' => $certificateType->id,
                'name' => $certificateType->name,
                'status' => $status
            ]);

            return back()->with('success', "Certificate type {$status} successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to toggle certificate type status', [
                'certificate_type_id' => $certificateType->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to update status.']);
        }
    }

    /**
     * Bulk actions for certificate types
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'certificate_type_ids' => 'required|array|min:1',
            'certificate_type_ids.*' => 'exists:certificate_types,id',
            'action' => 'required|in:activate,deactivate,delete'
        ]);

        try {
            $certificateTypeIds = $request->certificate_type_ids;
            $updatedCount = 0;

            switch ($request->action) {
                case 'activate':
                    $updatedCount = CertificateType::whereIn('id', $certificateTypeIds)
                                                 ->update(['is_active' => true]);
                    break;

                case 'deactivate':
                    $updatedCount = CertificateType::whereIn('id', $certificateTypeIds)
                                                 ->update(['is_active' => false]);
                    break;

                case 'delete':
                    // Check for associated certificates before deletion
                    $typesWithCertificates = CertificateType::whereIn('id', $certificateTypeIds)
                                                          ->whereHas('employeeCertificates')
                                                          ->pluck('name')
                                                          ->toArray();

                    if (!empty($typesWithCertificates)) {
                        return back()->withErrors([
                            'error' => 'Cannot delete certificate types with associated certificates: ' . implode(', ', $typesWithCertificates)
                        ]);
                    }

                    $updatedCount = CertificateType::whereIn('id', $certificateTypeIds)->delete();
                    break;
            }

            Log::info('Bulk certificate type action', [
                'action' => $request->action,
                'count' => count($certificateTypeIds),
                'updated_count' => $updatedCount
            ]);

            return back()->with('success', "{$updatedCount} certificate types {$request->action}d successfully.");

        } catch (\Exception $e) {
            Log::error('Bulk certificate type action failed', [
                'action' => $request->action,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Bulk action failed: ' . $e->getMessage()]);
        }
    }

    /**
     * API: Search certificate types (for autocomplete)
     */
    public function apiSearch(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        $certificateTypes = CertificateType::active()
                                         ->where(function($q) use ($query) {
                                             $q->where('name', 'like', "%{$query}%")
                                               ->orWhere('code', 'like', "%{$query}%");
                                         })
                                         ->limit($limit)
                                         ->get(['id', 'name', 'code', 'category', 'validity_months']);

        return response()->json($certificateTypes);
    }

    /**
     * API: Get categories
     */
    public function apiGetCategories()
    {
        $categories = CertificateType::select('category')
                                   ->distinct()
                                   ->pluck('category')
                                   ->sort()
                                   ->values();

        return response()->json($categories);
    }
}
