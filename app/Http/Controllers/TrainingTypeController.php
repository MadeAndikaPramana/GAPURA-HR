<?php
// app/Http/Controllers/TrainingTypeController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TrainingType;
use App\Models\TrainingRecord;

class TrainingTypeController extends Controller
{
    /**
     * Display a listing of training types
     */
    public function index(Request $request)
    {
        $query = TrainingType::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%');
            });
        }

        // Category filter
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        $trainingTypes = $query->withCount(['trainingRecords', 'activeTrainingRecords', 'expiredTrainingRecords'])
            ->paginate(15)
            ->withQueryString();

        // Get categories for filter
        $categories = TrainingType::distinct('category')->pluck('category')->filter();

        return Inertia::render('TrainingTypes/Index', [
            'trainingTypes' => $trainingTypes,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status']),
            'stats' => [
                'total' => TrainingType::count(),
                'active' => TrainingType::where('is_active', true)->count(),
                'categories' => TrainingType::distinct('category')->count(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new training type
     */
    public function create()
    {
        $categories = TrainingType::distinct('category')->pluck('category')->filter();

        return Inertia::render('TrainingTypes/Create', [
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created training type
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:training_types',
            'code' => 'required|string|max:20|unique:training_types',
            'category' => 'required|string|max:100',
            'validity_months' => 'required|integer|min:1|max:120',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        TrainingType::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'category' => $request->category,
            'validity_months' => $request->validity_months,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true)
        ]);

        return redirect()->route('training-types.index')
            ->with('success', 'Training type berhasil ditambahkan.');
    }

    /**
     * Display the specified training type
     */
    public function show(TrainingType $trainingType)
    {
        $trainingType->load(['trainingRecords.employee.department']);

        // Get statistics
        $stats = $trainingType->compliance_stats;

        // Get training records by status
        $recordsByStatus = $trainingType->trainingRecords()
            ->with(['employee.department'])
            ->get()
            ->groupBy('status')
            ->map(function($records) {
                return $records->count();
            });

        // Get recent training records
        $recentRecords = $trainingType->trainingRecords()
            ->with(['employee.department'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get employees by department
        $employeesByDepartment = $trainingType->trainingRecords()
            ->with(['employee.department'])
            ->get()
            ->groupBy('employee.department.name')
            ->map(function($records) {
                return $records->count();
            });

        return Inertia::render('TrainingTypes/Show', [
            'trainingType' => $trainingType,
            'stats' => $stats,
            'recordsByStatus' => $recordsByStatus,
            'recentRecords' => $recentRecords,
            'employeesByDepartment' => $employeesByDepartment
        ]);
    }

    /**
     * Show the form for editing the specified training type
     */
    public function edit(TrainingType $trainingType)
    {
        $categories = TrainingType::distinct('category')->pluck('category')->filter();

        return Inertia::render('TrainingTypes/Edit', [
            'trainingType' => $trainingType,
            'categories' => $categories
        ]);
    }

    /**
     * Update the specified training type
     */
    public function update(Request $request, TrainingType $trainingType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:training_types,name,' . $trainingType->id,
            'code' => 'required|string|max:20|unique:training_types,code,' . $trainingType->id,
            'category' => 'required|string|max:100',
            'validity_months' => 'required|integer|min:1|max:120',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $trainingType->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'category' => $request->category,
            'validity_months' => $request->validity_months,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active')
        ]);

        return redirect()->route('training-types.index')
            ->with('success', 'Training type berhasil diupdate.');
    }

    /**
     * Remove the specified training type
     */
    public function destroy(TrainingType $trainingType)
    {
        // Check if training type has training records
        if ($trainingType->trainingRecords()->count() > 0) {
            return redirect()->route('training-types.index')
                ->with('error', 'Tidak dapat menghapus training type yang memiliki training records.');
        }

        $trainingType->delete();

        return redirect()->route('training-types.index')
            ->with('success', 'Training type berhasil dihapus.');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(TrainingType $trainingType)
    {
        $trainingType->update([
            'is_active' => !$trainingType->is_active
        ]);

        $status = $trainingType->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Training type berhasil {$status}.");
    }
}
