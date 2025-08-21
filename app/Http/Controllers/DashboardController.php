<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Department;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard
     */
    public function index()
    {
        // Basic statistics
        $stats = [
            'total_employees' => Employee::where('status', 'active')->count(),
            'total_departments' => Department::count(),
            'total_training_records' => TrainingRecord::count(),
            'active_certificates' => TrainingRecord::where('status', 'active')->count(),
            'expiring_soon' => TrainingRecord::where('status', 'expiring_soon')->count(),
            'expired_certificates' => TrainingRecord::where('status', 'expired')->count(),
        ];

        // Compliance by department
        $complianceByDepartment = Department::with(['employees.trainingRecords'])
            ->get()
            ->map(function ($department) {
                $totalEmployees = $department->employees->where('status', 'active')->count();
                $totalCertificates = $department->employees->flatMap->trainingRecords->count();
                $activeCertificates = $department->employees->flatMap->trainingRecords->where('status', 'active')->count();

                $complianceRate = $totalCertificates > 0 ? round(($activeCertificates / $totalCertificates) * 100, 1) : 0;

                return [
                    'department_name' => $department->name,
                    'total_employees' => $totalEmployees,
                    'total_certificates' => $totalCertificates,
                    'active_certificates' => $activeCertificates,
                    'compliance_rate' => $complianceRate,
                ];
            })
            ->sortByDesc('compliance_rate')
            ->values();

        // Training compliance by type
        $complianceByType = TrainingType::with('trainingRecords')
            ->get()
            ->map(function ($type) {
                $total = $type->trainingRecords->count();
                $active = $type->trainingRecords->where('status', 'active')->count();
                $expired = $type->trainingRecords->where('status', 'expired')->count();

                return [
                    'training_type' => $type->name,
                    'total' => $total,
                    'active' => $active,
                    'expired' => $expired,
                    'compliance_rate' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();

        // Recent training activities
        $recentActivities = TrainingRecord::with(['employee', 'trainingType'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'employee_name' => $record->employee->name,
                    'training_type' => $record->trainingType->name,
                    'status' => $record->status,
                    'created_at' => $record->created_at->format('Y-m-d H:i'),
                    'expiry_date' => $record->expiry_date,
                ];
            });

        // Expiring certificates in next 30 days
        $expiringCertificates = TrainingRecord::with(['employee', 'trainingType'])
            ->where('status', 'expiring_soon')
            ->orWhere(function($query) {
                $query->where('expiry_date', '>=', Carbon::now())
                      ->where('expiry_date', '<=', Carbon::now()->addDays(30));
            })
            ->orderBy('expiry_date', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'employee_name' => $record->employee->name,
                    'training_type' => $record->trainingType->name,
                    'certificate_number' => $record->certificate_number,
                    'expiry_date' => $record->expiry_date,
                    'days_until_expiry' => Carbon::parse($record->expiry_date)->diffInDays(Carbon::now()),
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'complianceByDepartment' => $complianceByDepartment,
            'complianceByType' => $complianceByType,
            'recentActivities' => $recentActivities,
            'expiringCertificates' => $expiringCertificates,
        ]);
    }

    /**
     * Get dashboard statistics for API calls
     */
    public function getStats()
    {
        return response()->json([
            'total_employees' => Employee::where('status', 'active')->count(),
            'total_departments' => Department::count(),
            'total_training_records' => TrainingRecord::count(),
            'compliance_rate' => $this->calculateOverallComplianceRate(),
        ]);
    }

    /**
     * Calculate overall compliance rate
     */
    private function calculateOverallComplianceRate(): float
    {
        $totalCertificates = TrainingRecord::count();
        $activeCertificates = TrainingRecord::where('status', 'active')->count();

        return $totalCertificates > 0 ? round(($activeCertificates / $totalCertificates) * 100, 2) : 0;
    }
}
