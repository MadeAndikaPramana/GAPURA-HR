<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use Illuminate\Support\Facades\DB;

class TrainingTypeController extends Controller
{
    /**
     * Display a listing of training types with enhanced features
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
            $query->where('is_active', $request->status === 'active');
        }

        $trainingTypes = $query->paginate(15)->withQueryString();

        // Add statistics for each training type
        $trainingTypes->getCollection()->transform(function ($type) {
            $stats = TrainingRecord::where('training_type_id', $type->id)
                ->selectRaw('
                    COUNT(*) as total_certificates,
                    COUNT(CASE WHEN status = "active" THEN 1 END) as active_count,
                    COUNT(CASE WHEN status = "expiring_soon" THEN 1 END) as expiring_count,
                    COUNT(CASE WHEN status = "expired" THEN 1 END) as expired_count
                ')
                ->first();

            $type->statistics = $stats;
            return $type;
        });

        return Inertia::render('TrainingTypes/Index', [
            'trainingTypes' => $trainingTypes,
            'filters' => $request->only(['search', 'category', 'status']),
            'categories' => ['safety', 'operational', 'security', 'technical'],
            'stats' => [
                'total' => TrainingType::count(),
                'active' => TrainingType::where('is_active', true)->count(),
                'by_category' => TrainingType::selectRaw('category, count(*) as count')
                    ->groupBy('category')
                    ->get()
            ]
        ]);
    }

    /**
     * Show the form for creating a new training type
     */
    public function create()
    {
        return Inertia::render('TrainingTypes/Create', [
            'categories' => ['safety', 'operational', 'security', 'technical']
        ]);
    }

    /**
     * Store a newly created training type
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:training_types',
            'code' => 'nullable|string|max:50|unique:training_types',
            'validity_months' => 'required|integer|min:1|max:120',
            'category' => 'required|in:safety,operational,security,technical',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        // Auto-generate code if not provided
        if (!$request->code) {
            $request->merge([
                'code' => strtoupper(substr(str_replace(' ', '', $request->name), 0, 10))
            ]);
        }

        TrainingType::create($request->all());

        return redirect()->route('training-types.index')
            ->with('success', 'Training type berhasil ditambahkan.');
    }

    /**
     * Display the specified training type
     */
    public function show(TrainingType $trainingType)
    {
        // Get detailed statistics
        $stats = TrainingRecord::where('training_type_id', $trainingType->id)
            ->with(['employee.department'])
            ->get()
            ->groupBy('status');

        $departmentStats = TrainingRecord::where('training_type_id', $trainingType->id)
            ->join('employees', 'training_records.employee_id', '=', 'employees.id')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->selectRaw('
                departments.name as department_name,
                COUNT(*) as total_certificates,
                COUNT(CASE WHEN training_records.status = "active" THEN 1 END) as active_count,
                COUNT(CASE WHEN training_records.status = "expiring_soon" THEN 1 END) as expiring_count,
                COUNT(CASE WHEN training_records.status = "expired" THEN 1 END) as expired_count
            ')
            ->groupBy('departments.id', 'departments.name')
            ->get();

        $recentCertificates = TrainingRecord::where('training_type_id', $trainingType->id)
            ->with(['employee.department'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('TrainingTypes/Show', [
            'trainingType' => $trainingType,
            'statistics' => [
                'total' => $stats->flatten()->count(),
                'active' => $stats->get('active', collect())->count(),
                'expiring_soon' => $stats->get('expiring_soon', collect())->count(),
                'expired' => $stats->get('expired', collect())->count(),
                'by_department' => $departmentStats,
                'recent_certificates' => $recentCertificates
            ]
        ]);
    }

    /**
     * Show the form for editing the specified training type
     */
    public function edit(TrainingType $trainingType)
    {
        return Inertia::render('TrainingTypes/Edit', [
            'trainingType' => $trainingType,
            'categories' => ['safety', 'operational', 'security', 'technical']
        ]);
    }

    /**
     * Update the specified training type
     */
    public function update(Request $request, TrainingType $trainingType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:training_types,name,' . $trainingType->id,
            'code' => 'nullable|string|max:50|unique:training_types,code,' . $trainingType->id,
            'validity_months' => 'required|integer|min:1|max:120',
            'category' => 'required|in:safety,operational,security,technical',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $trainingType->update($request->all());

        return redirect()->route('training-types.index')
            ->with('success', 'Training type berhasil diupdate.');
    }

    /**
     * Remove the specified training type
     */
    public function destroy(TrainingType $trainingType)
    {
        // Check if training type has any certificates
        if ($trainingType->trainingRecords()->count() > 0) {
            return redirect()->route('training-types.index')
                ->with('error', 'Tidak dapat menghapus training type yang memiliki certificates.');
        }

        $trainingType->delete();

        return redirect()->route('training-types.index')
            ->with('success', 'Training type berhasil dihapus.');
    }

    /**
     * Toggle active status of training type
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

    /**
     * Get training type statistics for dashboard
     */
    public function getStatistics()
    {
        $stats = DB::table('training_types')
            ->leftJoin('training_records', 'training_types.id', '=', 'training_records.training_type_id')
            ->selectRaw('
                training_types.id,
                training_types.name,
                training_types.category,
                COUNT(training_records.id) as total_certificates,
                COUNT(CASE WHEN training_records.status = "active" THEN 1 END) as active_certificates,
                COUNT(CASE WHEN training_records.status = "expiring_soon" THEN 1 END) as expiring_certificates,
                COUNT(CASE WHEN training_records.status = "expired" THEN 1 END) as expired_certificates
            ')
            ->where('training_types.is_active', true)
            ->groupBy('training_types.id', 'training_types.name', 'training_types.category')
            ->orderBy('total_certificates', 'desc')
            ->get();

        return response()->json($stats);
    }

    /**
     * Export training types with their statistics
     */
    public function export()
    {
        $trainingTypes = TrainingType::with('trainingRecords')->get();

        // Transform data for export
        $exportData = $trainingTypes->map(function ($type) {
            $records = $type->trainingRecords;
            return [
                'Name' => $type->name,
                'Code' => $type->code,
                'Category' => ucfirst($type->category),
                'Validity (Months)' => $type->validity_months,
                'Status' => $type->is_active ? 'Active' : 'Inactive',
                'Total Certificates' => $records->count(),
                'Active Certificates' => $records->where('status', 'active')->count(),
                'Expiring Soon' => $records->where('status', 'expiring_soon')->count(),
                'Expired Certificates' => $records->where('status', 'expired')->count(),
                'Description' => $type->description,
            ];
        });

        return Excel::download(new class($exportData) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithStyles,
            \Maatwebsite\Excel\Concerns\WithColumnWidths
        {
            private $data;

            public function __construct($data) {
                $this->data = $data;
            }

            public function collection() {
                return collect($this->data);
            }

            public function headings(): array {
                return [
                    'Name', 'Code', 'Category', 'Validity (Months)',
                    'Status', 'Total Certificates', 'Active Certificates',
                    'Expiring Soon', 'Expired Certificates', 'Description'
                ];
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                return [
                    1 => ['font' => ['bold' => true]],
                ];
            }

            public function columnWidths(): array {
                return [
                    'A' => 30, 'B' => 15, 'C' => 15, 'D' => 20,
                    'E' => 15, 'F' => 20, 'G' => 20, 'H' => 15,
                    'I' => 20, 'J' => 40
                ];
            }
        }, 'training_types_export.xlsx');
    }
}
