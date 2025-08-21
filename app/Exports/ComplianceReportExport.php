<?php
// app/Exports/ComplianceReportExport.php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Department;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ComplianceReportExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            'Summary' => new ComplianceSummarySheet($this->filters),
            'Employee Details' => new EmployeeComplianceSheet($this->filters),
            'Department Analysis' => new DepartmentComplianceSheet($this->filters),
            'Training Type Analysis' => new TrainingTypeComplianceSheet($this->filters),
            'Expiring Certificates' => new ExpiringCertificatesSheet($this->filters)
        ];
    }
}

class ComplianceSummarySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Get summary statistics
        $totalEmployees = Employee::where('status', 'active')->count();
        $totalCertificates = TrainingRecord::count();
        $activeCertificates = TrainingRecord::where('status', 'active')->count();
        $expiringSoon = TrainingRecord::where('status', 'expiring_soon')->count();
        $expired = TrainingRecord::where('status', 'expired')->count();

        $complianceRate = $totalCertificates > 0 ? round(($activeCertificates / $totalCertificates) * 100, 2) : 0;

        return collect([
            [
                'metric' => 'Total Active Employees',
                'value' => $totalEmployees,
                'percentage' => null,
                'status' => 'info'
            ],
            [
                'metric' => 'Total Certificates',
                'value' => $totalCertificates,
                'percentage' => null,
                'status' => 'info'
            ],
            [
                'metric' => 'Active Certificates',
                'value' => $activeCertificates,
                'percentage' => $complianceRate,
                'status' => $complianceRate >= 90 ? 'good' : ($complianceRate >= 80 ? 'warning' : 'critical')
            ],
            [
                'metric' => 'Certificates Expiring Soon',
                'value' => $expiringSoon,
                'percentage' => $totalCertificates > 0 ? round(($expiringSoon / $totalCertificates) * 100, 2) : 0,
                'status' => $expiringSoon > 0 ? 'warning' : 'good'
            ],
            [
                'metric' => 'Expired Certificates',
                'value' => $expired,
                'percentage' => $totalCertificates > 0 ? round(($expired / $totalCertificates) * 100, 2) : 0,
                'status' => $expired > 0 ? 'critical' : 'good'
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Metric',
            'Value',
            'Percentage',
            'Status'
        ];
    }

    public function map($row): array
    {
        return [
            $row['metric'],
            $row['value'],
            $row['percentage'] ? $row['percentage'] . '%' : 'N/A',
            ucfirst($row['status'])
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4CAF50']
                ]
            ],
        ];
    }
}

class EmployeeComplianceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Employee::with(['department', 'trainingRecords.trainingType'])
                          ->where('status', 'active');

        if (!empty($this->filters['department_id'])) {
            $query->where('department_id', $this->filters['department_id']);
        }

        return $query->get()->map(function ($employee) {
            $totalCertificates = $employee->trainingRecords->count();
            $activeCertificates = $employee->trainingRecords->where('status', 'active')->count();
            $expiringSoon = $employee->trainingRecords->where('status', 'expiring_soon')->count();
            $expired = $employee->trainingRecords->where('status', 'expired')->count();

            $complianceRate = $totalCertificates > 0 ? round(($activeCertificates / $totalCertificates) * 100, 2) : 0;

            return [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'department' => $employee->department ? $employee->department->name : 'No Department',
                'position' => $employee->position,
                'total_certificates' => $totalCertificates,
                'active_certificates' => $activeCertificates,
                'expiring_soon' => $expiringSoon,
                'expired' => $expired,
                'compliance_rate' => $complianceRate,
                'status' => $complianceRate >= 90 ? 'Excellent' :
                           ($complianceRate >= 80 ? 'Good' :
                           ($complianceRate >= 70 ? 'Needs Attention' : 'Critical'))
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',
            'Position',
            'Total Certificates',
            'Active Certificates',
            'Expiring Soon',
            'Expired',
            'Compliance Rate',
            'Status'
        ];
    }

    public function map($row): array
    {
        return [
            $row['employee_id'],
            $row['name'],
            $row['department'],
            $row['position'],
            $row['total_certificates'],
            $row['active_certificates'],
            $row['expiring_soon'],
            $row['expired'],
            $row['compliance_rate'] . '%',
            $row['status']
        ];
    }

    public function title(): string
    {
        return 'Employee Details';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2196F3']
                ]
            ],
        ];
    }
}

class DepartmentComplianceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Department::with(['employees.trainingRecords'])
                         ->get()
                         ->map(function ($department) {
                             $totalEmployees = $department->employees->where('status', 'active')->count();
                             $totalCertificates = $department->employees->flatMap->trainingRecords->count();
                             $activeCertificates = $department->employees->flatMap->trainingRecords->where('status', 'active')->count();
                             $expiringSoon = $department->employees->flatMap->trainingRecords->where('status', 'expiring_soon')->count();
                             $expired = $department->employees->flatMap->trainingRecords->where('status', 'expired')->count();

                             $complianceRate = $totalCertificates > 0 ? round(($activeCertificates / $totalCertificates) * 100, 2) : 0;

                             return [
                                 'department_name' => $department->name,
                                 'department_code' => $department->code,
                                 'total_employees' => $totalEmployees,
                                 'total_certificates' => $totalCertificates,
                                 'active_certificates' => $activeCertificates,
                                 'expiring_soon' => $expiringSoon,
                                 'expired' => $expired,
                                 'compliance_rate' => $complianceRate,
                                 'avg_certificates_per_employee' => $totalEmployees > 0 ? round($totalCertificates / $totalEmployees, 2) : 0
                             ];
                         });
    }

    public function headings(): array
    {
        return [
            'Department Name',
            'Department Code',
            'Total Employees',
            'Total Certificates',
            'Active Certificates',
            'Expiring Soon',
            'Expired',
            'Compliance Rate',
            'Avg Certificates per Employee'
        ];
    }

    public function map($row): array
    {
        return [
            $row['department_name'],
            $row['department_code'],
            $row['total_employees'],
            $row['total_certificates'],
            $row['active_certificates'],
            $row['expiring_soon'],
            $row['expired'],
            $row['compliance_rate'] . '%',
            $row['avg_certificates_per_employee']
        ];
    }

    public function title(): string
    {
        return 'Department Analysis';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFF9800']
                ]
            ],
        ];
    }
}

class TrainingTypeComplianceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return TrainingType::with('trainingRecords')
                          ->get()
                          ->map(function ($trainingType) {
                              $totalCertificates = $trainingType->trainingRecords->count();
                              $activeCertificates = $trainingType->trainingRecords->where('status', 'active')->count();
                              $expiringSoon = $trainingType->trainingRecords->where('status', 'expiring_soon')->count();
                              $expired = $trainingType->trainingRecords->where('status', 'expired')->count();

                              $complianceRate = $totalCertificates > 0 ? round(($activeCertificates / $totalCertificates) * 100, 2) : 0;

                              return [
                                  'training_name' => $trainingType->name,
                                  'training_code' => $trainingType->code,
                                  'category' => $trainingType->category,
                                  'validity_months' => $trainingType->validity_months,
                                  'total_certificates' => $totalCertificates,
                                  'active_certificates' => $activeCertificates,
                                  'expiring_soon' => $expiringSoon,
                                  'expired' => $expired,
                                  'compliance_rate' => $complianceRate
                              ];
                          });
    }

    public function headings(): array
    {
        return [
            'Training Name',
            'Training Code',
            'Category',
            'Validity (Months)',
            'Total Certificates',
            'Active Certificates',
            'Expiring Soon',
            'Expired',
            'Compliance Rate'
        ];
    }

    public function map($row): array
    {
        return [
            $row['training_name'],
            $row['training_code'],
            $row['category'],
            $row['validity_months'],
            $row['total_certificates'],
            $row['active_certificates'],
            $row['expiring_soon'],
            $row['expired'],
            $row['compliance_rate'] . '%'
        ];
    }

    public function title(): string
    {
        return 'Training Type Analysis';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF9C27B0']
                ]
            ],
        ];
    }
}

class ExpiringCertificatesSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return TrainingRecord::with(['employee.department', 'trainingType'])
                            ->where(function($query) {
                                $query->where('status', 'expiring_soon')
                                      ->orWhere('status', 'expired')
                                      ->orWhere('expiry_date', '<=', now()->addDays(60));
                            })
                            ->orderBy('expiry_date', 'asc')
                            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',
            'Training Type',
            'Certificate Number',
            'Issue Date',
            'Expiry Date',
            'Days Until Expiry',
            'Status',
            'Priority'
        ];
    }

    public function map($record): array
    {
        $daysUntilExpiry = \Carbon\Carbon::parse($record->expiry_date)->diffInDays(\Carbon\Carbon::now(), false);

        $priority = 'Low';
        if ($daysUntilExpiry <= 0) {
            $priority = 'Critical (Expired)';
        } elseif ($daysUntilExpiry <= 7) {
            $priority = 'High (1 Week)';
        } elseif ($daysUntilExpiry <= 30) {
            $priority = 'Medium (1 Month)';
        }

        return [
            $record->employee->employee_id,
            $record->employee->name,
            $record->employee->department ? $record->employee->department->name : 'No Department',
            $record->trainingType->name,
            $record->certificate_number,
            $record->issue_date->format('Y-m-d'),
            $record->expiry_date->format('Y-m-d'),
            $daysUntilExpiry <= 0 ? 'Expired' : $daysUntilExpiry . ' days',
            ucfirst(str_replace('_', ' ', $record->status)),
            $priority
        ];
    }

    public function title(): string
    {
        return 'Expiring Certificates';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF44336']
                ]
            ],
        ];
    }
}
