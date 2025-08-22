<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TrainingRecord;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments with statistics
     */
    public function index(Request $request)
    {
        $query = Department::withCount(['employees', 'activeEmployees']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $departments = $query->paginate(15)->withQueryString();

        // Add training statistics for each department
        $departments->getCollection()->transform(function ($department) {
            $trainingStats = DB::table('training_records')
                ->join('employees', 'training_records.employee_id', '=', 'employees.id')
                ->where('employees.department_id', $department->id)
                ->selectRaw('
                    COUNT(*) as total_certificates,
                    COUNT(CASE WHEN training_records.status = "active" THEN 1 END) as active_certificates,
                    COUNT(CASE WHEN training_records.status = "expiring_soon" THEN 1 END) as expiring_certificates,
                    COUNT(CASE WHEN training_records.status = "expired" THEN 1 END) as expired_certificates
                ')
                ->first();

            $department->training_stats = $trainingStats;
            $department->compliance_rate = $trainingStats->total_certificates > 0
                ? round(($trainingStats->active_certificates / $trainingStats->total_certificates) * 100, 1)
                : 0;

            return $department;
        });

        return Inertia::render('Departments/Index', [
            'departments' => $departments,
            'filters' => $request->only(['search']),
            'stats' => [
                'total_departments' => Department::count(),
                'departments_with_employees' => Department::has('employees')->count(),
                'total_employees' => Employee::count(),
                'average_employees_per_department' => round(Employee::count() / max(Department::count(), 1), 1)
            ]
        ]);
    }

    /**
     * Show the form for creating a new department
     */
    public function create()
    {
        return Inertia::render('Departments/Create');
    }

    /**
     * Store a newly created department
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments',
            'code' => 'required|string|max:10|unique:departments',
            'description' => 'nullable|string|max:500'
        ]);

        Department::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description
        ]);

        return redirect()->route('departments.index')
            ->with('success', 'Department berhasil ditambahkan.');
    }

    /**
     * Display the specified department with detailed statistics
     */
    public function show(Department $department)
    {
        // Load department with employees and their training records
        $department->load([
            'employees' => function($query) {
                $query->with(['trainingRecords.trainingType']);
            }
        ]);

        // Get department training statistics
        $trainingStats = DB::table('training_records')
            ->join('employees', 'training_records.employee_id', '=', 'employees.id')
            ->join('training_types', 'training_records.training_type_id', '=', 'training_types.id')
            ->where('employees.department_id', $department->id)
            ->selectRaw('
                COUNT(*) as total_certificates,
                COUNT(CASE WHEN training_records.status = "active" THEN 1 END) as active_certificates,
                COUNT(CASE WHEN training_records.status = "expiring_soon" THEN 1 END) as expiring_certificates,
                COUNT(CASE WHEN training_records.status = "expired" THEN 1 END) as expired_certificates,
                COUNT(DISTINCT training_records.employee_id) as employees_with_training,
                COUNT(DISTINCT training_types.id) as unique_training_types
            ')
            ->first();

        // Get training by category breakdown
        $trainingByCategory = DB::table('training_records')
            ->join('employees', 'training_records.employee_id', '=', 'employees.id')
            ->join('training_types', 'training_records.training_type_id', '=', 'training_types.id')
            ->where('employees.department_id', $department->id)
            ->selectRaw('
                training_types.category,
                COUNT(*) as total,
                COUNT(CASE WHEN training_records.status = "active" THEN 1 END) as active,
                COUNT(CASE WHEN training_records.status = "expiring_soon" THEN 1 END) as expiring,
                COUNT(CASE WHEN training_records.status = "expired" THEN 1 END) as expired
            ')
            ->groupBy('training_types.category')
            ->get();

        // Get employees without any training
        $employeesWithoutTraining = Employee::where('department_id', $department->id)
            ->doesntHave('trainingRecords')
            ->get();

        // Get recent training activities
        $recentActivities = TrainingRecord::join('employees', 'training_records.employee_id', '=', 'employees.id')
            ->join('training_types', 'training_records.training_type_id', '=', 'training_types.id')
            ->where('employees.department_id', $department->id)
            ->select('training_records.*', 'employees.name as employee_name', 'training_types.name as training_name')
            ->orderBy('training_records.created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Departments/Show', [
            'department' => $department,
            'trainingStats' => $trainingStats,
            'trainingByCategory' => $trainingByCategory,
            'employeesWithoutTraining' => $employeesWithoutTraining,
            'recentActivities' => $recentActivities,
            'complianceRate' => $trainingStats->total_certificates > 0
                ? round(($trainingStats->active_certificates / $trainingStats->total_certificates) * 100, 1)
                : 0
        ]);
    }

    /**
     * Show the form for editing the specified department
     */
    public function edit(Department $department)
    {
        return Inertia::render('Departments/Edit', [
            'department' => $department
        ]);
    }

    /**
     * Update the specified department
     */
    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'code' => 'required|string|max:10|unique:departments,code,' . $department->id,
            'description' => 'nullable|string|max:500'
        ]);

        $department->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description
        ]);

        return redirect()->route('departments.index')
            ->with('success', 'Department berhasil diupdate.');
    }

    /**
     * Remove the specified department
     */
    public function destroy(Department $department)
    {
        // Check if department has employees
        if ($department->employees()->count() > 0) {
            return redirect()->route('departments.index')
                ->with('error', 'Tidak dapat menghapus department yang masih memiliki karyawan.');
        }

        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Department berhasil dihapus.');
    }

    /**
     * Get department statistics for API
     */
    public function getStatistics()
    {
        $departments = Department::with(['employees', 'activeEmployees'])->get();

        $stats = $departments->map(function ($department) {
            $trainingStats = DB::table('training_records')
                ->join('employees', 'training_records.employee_id', '=', 'employees.id')
                ->where('employees.department_id', $department->id)
                ->selectRaw('
                    COUNT(*) as total_certificates,
                    COUNT(CASE WHEN training_records.status = "active" THEN 1 END) as active_certificates,
                    COUNT(CASE WHEN training_records.status = "expiring_soon" THEN 1 END) as expiring_certificates,
                    COUNT(CASE WHEN training_records.status = "expired" THEN 1 END) as expired_certificates
                ')
                ->first();

            return [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'total_employees' => $department->employees_count,
                'active_employees' => $department->active_employees_count,
                'total_certificates' => $trainingStats->total_certificates,
                'active_certificates' => $trainingStats->active_certificates,
                'expiring_certificates' => $trainingStats->expiring_certificates,
                'expired_certificates' => $trainingStats->expired_certificates,
                'compliance_rate' => $trainingStats->total_certificates > 0
                    ? round(($trainingStats->active_certificates / $trainingStats->total_certificates) * 100, 1)
                    : 0
            ];
        });

        return response()->json([
            'departments' => $stats,
            'summary' => [
                'total_departments' => $departments->count(),
                'total_employees' => $departments->sum('employees_count'),
                'departments_with_high_compliance' => $stats->where('compliance_rate', '>=', 90)->count(),
                'departments_needing_attention' => $stats->where('compliance_rate', '<', 80)->count()
            ]
        ]);
    }

    /**
     * Get department compliance report
     */
    public function getComplianceReport(Department $department)
    {
        $employees = Employee::where('department_id', $department->id)
            ->with(['trainingRecords.trainingType'])
            ->get();

        $complianceData = $employees->map(function ($employee) {
            $records = $employee->trainingRecords;
            $activeRecords = $records->where('status', 'active');
            $expiringRecords = $records->where('status', 'expiring_soon');
            $expiredRecords = $records->where('status', 'expired');

            return [
                'employee_id' => $employee->employee_id,
                'employee_name' => $employee->name,
                'position' => $employee->position,
                'total_trainings' => $records->count(),
                'active_trainings' => $activeRecords->count(),
                'expiring_trainings' => $expiringRecords->count(),
                'expired_trainings' => $expiredRecords->count(),
                'compliance_rate' => $records->count() > 0
                    ? round(($activeRecords->count() / $records->count()) * 100, 1)
                    : 0,
                'last_training_date' => $records->max('issue_date'),
                'next_expiry_date' => $records->where('status', '!=', 'expired')->min('expiry_date')
            ];
        });

        return response()->json([
            'department' => $department,
            'compliance_data' => $complianceData,
            'summary' => [
                'total_employees' => $employees->count(),
                'employees_fully_compliant' => $complianceData->where('compliance_rate', 100)->count(),
                'employees_with_issues' => $complianceData->where('compliance_rate', '<', 100)->count(),
                'average_compliance_rate' => round($complianceData->avg('compliance_rate'), 1)
            ]
        ]);
    }

    /**
     * Export department data
     */
    public function export(Request $request)
    {
        $departments = Department::with(['employees.trainingRecords'])->get();

        $exportData = $departments->map(function ($department) {
            $trainingStats = DB::table('training_records')
                ->join('employees', 'training_records.employee_id', '=', 'employees.id')
                ->where('employees.department_id', $department->id)
                ->selectRaw('
                    COUNT(*) as total_certificates,
                    COUNT(CASE WHEN training_records.status = "active" THEN 1 END) as active_certificates,
                    COUNT(CASE WHEN training_records.status = "expiring_soon" THEN 1 END) as expiring_certificates,
                    COUNT(CASE WHEN training_records.status = "expired" THEN 1 END) as expired_certificates
                ')
                ->first();

            return [
                'Department Name' => $department->name,
                'Department Code' => $department->code,
                'Description' => $department->description,
                'Total Employees' => $department->employees->count(),
                'Active Employees' => $department->employees->where('status', 'active')->count(),
                'Total Certificates' => $trainingStats->total_certificates,
                'Active Certificates' => $trainingStats->active_certificates,
                'Expiring Certificates' => $trainingStats->expiring_certificates,
                'Expired Certificates' => $trainingStats->expired_certificates,
                'Compliance Rate %' => $trainingStats->total_certificates > 0
                    ? round(($trainingStats->active_certificates / $trainingStats->total_certificates) * 100, 1)
                    : 0
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
                    'Department Name', 'Department Code', 'Description',
                    'Total Employees', 'Active Employees', 'Total Certificates',
                    'Active Certificates', 'Expiring Certificates', 'Expired Certificates',
                    'Compliance Rate %'
                ];
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) {
                return [
                    1 => ['font' => ['bold' => true]],
                ];
            }

            public function columnWidths(): array {
                return [
                    'A' => 25, 'B' => 15, 'C' => 40, 'D' => 15,
                    'E' => 15, 'F' => 18, 'G' => 18, 'H' => 18,
                    'I' => 18, 'J' => 15
                ];
            }
        }, 'departments_export.xlsx');
    }
}
