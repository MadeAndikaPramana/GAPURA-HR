<?php
// app/Console/Commands/TestComplianceSystem.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Department;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TestComplianceSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mpga:test-compliance
                            {--employee= : Test specific employee ID}
                            {--department= : Test specific department code}
                            {--generate-report : Generate detailed compliance report}
                            {--export-csv : Export results to CSV}
                            {--show-details : Show detailed breakdown}';

    /**
     * The console command description.
     */
    protected $description = 'Test and validate MPGA compliance system functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayHeader();

        try {
            if ($employeeId = $this->option('employee')) {
                $this->testSpecificEmployee($employeeId);
            } elseif ($departmentCode = $this->option('department')) {
                $this->testSpecificDepartment($departmentCode);
            } else {
                $this->testOverallSystem();
            }

            if ($this->option('generate-report')) {
                $this->generateComplianceReport();
            }

            if ($this->option('export-csv')) {
                $this->exportComplianceCSV();
            }

            $this->displayRecommendations();

        } catch (\Exception $e) {
            $this->error('❌ Compliance testing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function displayHeader()
    {
        $this->info('');
        $this->info('🧪 ========================================');
        $this->info('   MPGA COMPLIANCE SYSTEM TESTING');
        $this->info('   Training Compliance Validation');
        $this->info('🧪 ========================================');
        $this->newLine();
    }

    private function testSpecificEmployee(string $employeeId)
    {
        $this->info("👤 Testing compliance for employee: {$employeeId}");
        $this->newLine();

        $employee = Employee::where('employee_id', $employeeId)
            ->with(['department', 'trainingRecords.trainingType'])
            ->first();

        if (!$employee) {
            $this->error("❌ Employee {$employeeId} not found");
            return;
        }

        $this->line("📋 Employee Details:");
        $this->line("   Name: {$employee->name}");
        $this->line("   Department: {$employee->department->name ?? 'N/A'}");
        $this->line("   Position: {$employee->position ?? 'N/A'}");
        $this->line("   Status: {$employee->status}");
        $this->newLine();

        $compliance = $this->calculateEmployeeCompliance($employee);
        $this->displayEmployeeCompliance($compliance);

        if ($this->option('show-details')) {
            $this->displayEmployeeTrainingDetails($employee);
        }
    }

    private function testSpecificDepartment(string $departmentCode)
    {
        $this->info("🏢 Testing compliance for department: {$departmentCode}");
        $this->newLine();

        $department = Department::where('code', $departmentCode)
            ->with(['employees.trainingRecords.trainingType'])
            ->first();

        if (!$department) {
            $this->error("❌ Department {$departmentCode} not found");
            return;
        }

        $this->line("📋 Department Details:");
        $this->line("   Name: {$department->name}");
        $this->line("   Code: {$department->code}");
        $this->line("   Total Employees: {$department->employees->count()}");
        $this->newLine();

        $compliance = $this->calculateDepartmentCompliance($department);
        $this->displayDepartmentCompliance($compliance);

        if ($this->option('show-details')) {
            $this->displayDepartmentDetails($department);
        }
    }

    private function testOverallSystem()
    {
        $this->info('🌐 Testing overall system compliance...');
        $this->newLine();

        // Overall system statistics
        $overallStats = $this->calculateOverallCompliance();
        $this->displayOverallCompliance($overallStats);

        $this->newLine();

        // Department-wise breakdown
        $this->info('🏢 Department-wise Compliance Breakdown:');
        $departments = Department::with(['employees.trainingRecords.trainingType'])->get();

        $departmentCompliance = [];
        foreach ($departments as $dept) {
            $compliance = $this->calculateDepartmentCompliance($dept);
            $departmentCompliance[] = [
                'department' => $dept,
                'compliance' => $compliance
            ];

            $statusIcon = $this->getComplianceStatusIcon($compliance['compliance_rate']);
            $this->line(sprintf(
                '   %s %-15s: %5.1f%% (%d/%d employees compliant)',
                $statusIcon,
                $dept->code,
                $compliance['compliance_rate'],
                $compliance['compliant_employees'],
                $compliance['total_employees']
            ));
        }

        $this->newLine();
        $this->displayCriticalIssues();
        $this->displayExpiryForecast();
    }

    private function calculateEmployeeCompliance(Employee $employee): array
    {
        $mandatoryTrainings = TrainingType::where('is_mandatory', true)->get();
        $employeeRecords = $employee->trainingRecords->keyBy('training_type_id');

        $compliance = [
            'employee' => $employee,
            'total_mandatory' => $mandatoryTrainings->count(),
            'completed_mandatory' => 0,
            'compliance_rate' => 0,
            'status' => 'non_compliant',
            'critical_issues' => [],
            'warnings' => [],
            'active_certificates' => 0,
            'expiring_certificates' => 0,
            'expired_certificates' => 0
        ];

        foreach ($mandatoryTrainings as $training) {
            if (isset($employeeRecords[$training->id])) {
                $record = $employeeRecords[$training->id];

                switch ($record->status) {
                    case 'active':
                        $compliance['completed_mandatory']++;
                        $compliance['active_certificates']++;
                        break;
                    case 'expiring_soon':
                        $compliance['completed_mandatory']++;
                        $compliance['expiring_certificates']++;
                        $daysLeft = Carbon::parse($record->expiry_date)->diffInDays(Carbon::now());
                        $compliance['warnings'][] = [
                            'training' => $training->name,
                            'days_left' => $daysLeft,
                            'expiry_date' => $record->expiry_date
                        ];
                        break;
                    case 'expired':
                        $compliance['expired_certificates']++;
                        $daysOverdue = Carbon::now()->diffInDays(Carbon::parse($record->expiry_date));
                        $compliance['critical_issues'][] = [
                            'type' => 'expired',
                            'training' => $training->name,
                            'days_overdue' => $daysOverdue,
                            'expiry_date' => $record->expiry_date
                        ];
                        break;
                }
            } else {
                $compliance['critical_issues'][] = [
                    'type' => 'missing',
                    'training' => $training->name
                ];
            }
        }

        // Calculate compliance rate
        if ($compliance['total_mandatory'] > 0) {
            $compliance['compliance_rate'] = round(
                ($compliance['completed_mandatory'] / $compliance['total_mandatory']) * 100,
                2
            );
        }

        // Determine overall status
        if (empty($compliance['critical_issues'])) {
            if (empty($compliance['warnings'])) {
                $compliance['status'] = 'compliant';
            } else {
                $compliance['status'] = 'warning';
            }
        } else {
            $compliance['status'] = 'non_compliant';
        }

        return $compliance;
    }

    private function calculateDepartmentCompliance(Department $department): array
    {
        $employees = $department->employees;

        $compliance = [
            'department' => $department,
            'total_employees' => $employees->count(),
            'compliant_employees' => 0,
            'warning_employees' => 0,
            'non_compliant_employees' => 0,
            'compliance_rate' => 0,
            'total_critical_issues' => 0,
            'total_warnings' => 0,
            'employee_details' => []
        ];

        foreach ($employees as $employee) {
            $empCompliance = $this->calculateEmployeeCompliance($employee);
            $compliance['employee_details'][] = $empCompliance;

            switch ($empCompliance['status']) {
                case 'compliant':
                    $compliance['compliant_employees']++;
                    break;
                case 'warning':
                    $compliance['warning_employees']++;
                    break;
                case 'non_compliant':
                    $compliance['non_compliant_employees']++;
                    break;
            }

            $compliance['total_critical_issues'] += count($empCompliance['critical_issues']);
            $compliance['total_warnings'] += count($empCompliance['warnings']);
        }

        // Calculate department compliance rate
        if ($compliance['total_employees'] > 0) {
            $compliance['compliance_rate'] = round(
                ($compliance['compliant_employees'] / $compliance['total_employees']) * 100,
                2
            );
        }

        return $compliance;
    }

    private function calculateOverallCompliance(): array
    {
        $totalEmployees = Employee::count();
        $totalRecords = TrainingRecord::count();
        $mandatoryTrainings = TrainingType::where('is_mandatory', true)->count();

        return [
            'total_employees' => $totalEmployees,
            'total_training_records' => $totalRecords,
            'mandatory_training_types' => $mandatoryTrainings,
            'active_certificates' => TrainingRecord::where('status', 'active')->count(),
            'expiring_certificates' => TrainingRecord::where('status', 'expiring_soon')->count(),
            'expired_certificates' => TrainingRecord::where('status', 'expired')->count(),
            'overall_compliance_rate' => $this->calculateSystemComplianceRate()
        ];
    }

    private function calculateSystemComplianceRate(): float
    {
        $employees = Employee::with('trainingRecords.trainingType')->get();
        $compliantCount = 0;

        foreach ($employees as $employee) {
            $compliance = $this->calculateEmployeeCompliance($employee);
            if ($compliance['status'] === 'compliant') {
                $compliantCount++;
            }
        }

        return $employees->count() > 0 ? round(($compliantCount / $employees->count()) * 100, 2) : 0;
    }

    private function displayEmployeeCompliance(array $compliance)
    {
        $statusColors = [
            'compliant' => 'info',
            'warning' => 'comment',
            'non_compliant' => 'error'
        ];

        $statusIcon = $this->getComplianceStatusIcon($compliance['compliance_rate']);

        $this->line("🎯 Compliance Status: {$statusIcon} " . strtoupper($compliance['status']),
                   $statusColors[$compliance['status']] ?? 'line');
        $this->line("📊 Compliance Rate: {$compliance['compliance_rate']}%");
        $this->line("✅ Completed Mandatory: {$compliance['completed_mandatory']}/{$compliance['total_mandatory']}");
        $this->line("📜 Active Certificates: {$compliance['active_certificates']}");
        $this->line("⏰ Expiring Soon: {$compliance['expiring_certificates']}");
        $this->line("❌ Expired: {$compliance['expired_certificates']}");

        if (!empty($compliance['critical_issues'])) {
            $this->newLine();
            $this->error('🚨 Critical Issues:');
            foreach ($compliance['critical_issues'] as $issue) {
                if ($issue['type'] === 'expired') {
                    $this->line("   ❌ {$issue['training']}: Expired {$issue['days_overdue']} days ago");
                } else {
                    $this->line("   ⚠️  {$issue['training']}: Missing mandatory training");
                }
            }
        }

        if (!empty($compliance['warnings'])) {
            $this->newLine();
            $this->warn('⚠️  Warnings:');
            foreach ($compliance['warnings'] as $warning) {
                $this->line("   ⏰ {$warning['training']}: Expires in {$warning['days_left']} days");
            }
        }
    }

    private function displayDepartmentCompliance(array $compliance)
    {
        $dept = $compliance['department'];

        $this->line("📊 Department Compliance Summary:");
        $this->line("   Total Employees: {$compliance['total_employees']}");
        $this->line("   Overall Compliance: {$compliance['compliance_rate']}%");
        $this->newLine();

        $this->line("📈 Status Breakdown:");
        $this->line("   ✅ Compliant: {$compliance['compliant_employees']}");
        $this->line("   ⚠️  Warning: {$compliance['warning_employees']}");
        $this->line("   ❌ Non-Compliant: {$compliance['non_compliant_employees']}");
        $this->newLine();

        $this->line("🚨 Issues Summary:");
        $this->line("   Critical Issues: {$compliance['total_critical_issues']}");
        $this->line("   Warnings: {$compliance['total_warnings']}");
    }

    private function displayOverallCompliance(array $stats)
    {
        $this->line("📊 System-wide Statistics:");
        $this->line("   Total Employees: " . number_format($stats['total_employees']));
        $this->line("   Total Training Records: " . number_format($stats['total_training_records']));
        $this->line("   Mandatory Training Types: {$stats['mandatory_training_types']}");
        $this->line("   Overall Compliance Rate: {$stats['overall_compliance_rate']}%");
        $this->newLine();

        $this->line("📈 Certificate Status Distribution:");
        $total = $stats['active_certificates'] + $stats['expiring_certificates'] + $stats['expired_certificates'];

        if ($total > 0) {
            $activePercent = round(($stats['active_certificates'] / $total) * 100, 1);
            $expiringPercent = round(($stats['expiring_certificates'] / $total) * 100, 1);
            $expiredPercent = round(($stats['expired_certificates'] / $total) * 100, 1);

            $this->line("   ✅ Active: {$stats['active_certificates']} ({$activePercent}%)");
            $this->line("   ⏰ Expiring Soon: {$stats['expiring_certificates']} ({$expiringPercent}%)");
            $this->line("   ❌ Expired: {$stats['expired_certificates']} ({$expiredPercent}%)");
        }
    }

    private function displayCriticalIssues()
    {
        $this->info('🚨 Critical Issues Requiring Immediate Attention:');

        $criticalIssues = TrainingRecord::where('status', 'expired')
            ->with(['employee', 'trainingType'])
            ->whereHas('trainingType', function($q) {
                $q->where('is_mandatory', true);
            })
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();

        if ($criticalIssues->isEmpty()) {
            $this->line('   🎉 No critical issues found!');
        } else {
            foreach ($criticalIssues as $issue) {
                $daysOverdue = Carbon::now()->diffInDays(Carbon::parse($issue->expiry_date));
                $this->line("   ❌ {$issue->employee->name} ({$issue->employee->employee_id}): {$issue->trainingType->name} - {$daysOverdue} days overdue");
            }
        }
    }

    private function displayExpiryForecast()
    {
        $this->newLine();
        $this->info('📅 Expiry Forecast (Next 90 Days):');

        $forecast = TrainingRecord::where('status', 'active')
            ->where('expiry_date', '<=', Carbon::now()->addDays(90))
            ->with(['employee', 'trainingType'])
            ->orderBy('expiry_date')
            ->get()
            ->groupBy(function($item) {
                $daysLeft = Carbon::parse($item->expiry_date)->diffInDays(Carbon::now());
                if ($daysLeft <= 7) return 'next_week';
                if ($daysLeft <= 30) return 'next_month';
                return 'next_quarter';
            });

        $periods = [
            'next_week' => '📍 Next 7 Days',
            'next_month' => '📅 Next 30 Days',
            'next_quarter' => '🗓️  Next 90 Days'
        ];

        foreach ($periods as $period => $label) {
            if (isset($forecast[$period])) {
                $count = $forecast[$period]->count();
                $this->line("   {$label}: {$count} certificates expiring");
            }
        }
    }

    private function displayEmployeeTrainingDetails(Employee $employee)
    {
        $this->newLine();
        $this->info('📋 Detailed Training Records:');

        $records = $employee->trainingRecords()->with('trainingType')->orderBy('expiry_date')->get();

        foreach ($records as $record) {
            $statusIcon = $this->getStatusIcon($record->status);
            $daysInfo = '';

            if ($record->expiry_date) {
                $daysLeft = Carbon::parse($record->expiry_date)->diffInDays(Carbon::now(), false);
                if ($daysLeft < 0) {
                    $daysInfo = " ({$record->status}, " . abs($daysLeft) . " days overdue)";
                } else {
                    $daysInfo = " ({$record->status}, {$daysLeft} days left)";
                }
            }

            $this->line("   {$statusIcon} {$record->trainingType->name}{$daysInfo}");
        }
    }

    private function displayDepartmentDetails(Department $department)
    {
        $this->newLine();
        $this->info('👥 Employee Compliance Details:');

        $employees = $department->employees()->with('trainingRecords.trainingType')->get();

        foreach ($employees as $employee) {
            $compliance = $this->calculateEmployeeCompliance($employee);
            $statusIcon = $this->getComplianceStatusIcon($compliance['compliance_rate']);

            $this->line("   {$statusIcon} {$employee->name} ({$employee->employee_id}): {$compliance['compliance_rate']}%");
        }
    }

    private function getComplianceStatusIcon(float $rate): string
    {
        if ($rate >= 100) return '🟢';
        if ($rate >= 80) return '🟡';
        if ($rate >= 60) return '🟠';
        return '🔴';
    }

    private function getStatusIcon(string $status): string
    {
        return match($status) {
            'active' => '✅',
            'expiring_soon' => '⏰',
            'expired' => '❌',
            'registered' => '📝',
            'completed' => '✅',
            default => '❓'
        };
    }

    private function generateComplianceReport()
    {
        $this->newLine();
        $this->info('📋 Generating detailed compliance report...');

        $reportData = [
            'generated_at' => Carbon::now()->toDateTimeString(),
            'generator' => 'MPGA Compliance System',
            'overall_stats' => $this->calculateOverallCompliance(),
            'department_breakdown' => [],
            'critical_issues' => [],
            'recommendations' => []
        ];

        // Generate department breakdown
        $departments = Department::with(['employees.trainingRecords.trainingType'])->get();
        foreach ($departments as $dept) {
            $reportData['department_breakdown'][$dept->code] = $this->calculateDepartmentCompliance($dept);
        }

        // Get critical issues
        $reportData['critical_issues'] = TrainingRecord::where('status', 'expired')
            ->with(['employee', 'trainingType'])
            ->get()
            ->map(function($record) {
                return [
                    'employee_id' => $record->employee->employee_id,
                    'employee_name' => $record->employee->name,
                    'department' => $record->employee->department->code ?? 'N/A',
                    'training' => $record->trainingType->name,
                    'expiry_date' => $record->expiry_date,
                    'days_overdue' => Carbon::now()->diffInDays(Carbon::parse($record->expiry_date))
                ];
            })->toArray();

        // Generate recommendations
        $reportData['recommendations'] = $this->generateSystemRecommendations($reportData);

        // Save report
        $filename = 'mpga_compliance_report_' . Carbon::now()->format('Y_m_d_H_i_s') . '.json';
        $path = storage_path('app/reports/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode($reportData, JSON_PRETTY_PRINT));

        $this->info("📄 Report saved to: {$path}");
    }

    private function exportComplianceCSV()
    {
        $this->newLine();
        $this->info('📊 Exporting compliance data to CSV...');

        $employees = Employee::with(['department', 'trainingRecords.trainingType'])->get();
        $csvData = [];

        foreach ($employees as $employee) {
            $compliance = $this->calculateEmployeeCompliance($employee);

            $csvData[] = [
                'Employee ID' => $employee->employee_id,
                'Name' => $employee->name,
                'Department' => $employee->department->name ?? 'N/A',
                'Position' => $employee->position ?? 'N/A',
                'Compliance Rate' => $compliance['compliance_rate'] . '%',
                'Status' => $compliance['status'],
                'Active Certificates' => $compliance['active_certificates'],
                'Expiring Soon' => $compliance['expiring_certificates'],
                'Expired' => $compliance['expired_certificates'],
                'Critical Issues' => count($compliance['critical_issues']),
                'Warnings' => count($compliance['warnings'])
            ];
        }

        $filename = 'mpga_compliance_export_' . Carbon::now()->format('Y_m_d_H_i_s') . '.csv';
        $path = storage_path('app/exports/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $file = fopen($path, 'w');

        // Write headers
        if (!empty($csvData)) {
            fputcsv($file, array_keys($csvData[0]));

            // Write data
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
        }

        fclose($file);

        $this->info("📊 CSV exported to: {$path}");
    }

    private function generateSystemRecommendations(array $reportData): array
    {
        $recommendations = [];

        // Critical issues recommendation
        if (count($reportData['critical_issues']) > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'compliance',
                'message' => 'Immediate action required: ' . count($reportData['critical_issues']) . ' expired mandatory certificates need renewal'
            ];
        }

        // Department-specific recommendations
        foreach ($reportData['department_breakdown'] as $deptCode => $deptData) {
            if ($deptData['compliance_rate'] < 80) {
                $recommendations[] = [
                    'priority' => 'medium',
                    'category' => 'department',
                    'message' => "Department {$deptCode} compliance rate ({$deptData['compliance_rate']}%) is below target (80%)"
                ];
            }
        }

        // System-wide recommendations
        if ($reportData['overall_stats']['overall_compliance_rate'] < 90) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'system',
                'message' => 'Overall system compliance below 90% - review training scheduling and notification processes'
            ];
        }

        return $recommendations;
    }

    private function displayRecommendations()
    {
        $this->newLine();
        $this->info('💡 System Recommendations:');
        $this->line('   1. 🔄 Run compliance checks weekly using: php artisan mpga:test-compliance');
        $this->line('   2. 📧 Set up automated email notifications for expiring certificates');
        $this->line('   3. 📊 Generate monthly compliance reports for management review');
        $this->line('   4. 🎯 Focus on departments with <80% compliance rates');
        $this->line('   5. 📅 Schedule training renewals 60 days before expiry');
    }
}
