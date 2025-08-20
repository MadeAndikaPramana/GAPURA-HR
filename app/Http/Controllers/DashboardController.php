<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Employee;
use App\Models\TrainingRecord;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'total_employees' => Employee::count(),
            'active_certificates' => TrainingRecord::where('status', 'active')->count(),
            'expiring_soon' => TrainingRecord::where('status', 'expiring_soon')->count(),
            'expired' => TrainingRecord::where('status', 'expired')->count(),
            // 'compliance_by_type' => $this->getComplianceByType(),
            // 'compliance_by_department' => $this->getComplianceByDepartment(),
            // 'recent_activities' => $this->getRecentActivities()
        ]);
    }
}
