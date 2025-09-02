<?php
// app/Exports/EmployeesExport.php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $filters;
    protected $includeCertificates;

    public function __construct($filters = [], $includeCertificates = false)
    {
        $this->filters = $filters;
        $this->includeCertificates = $includeCertificates;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Employee::with(['department']);

        // Add certificate relationships if needed
        if ($this->includeCertificates && class_exists('App\Models\EmployeeCertificate')) {
            $query->with(['employeeCertificates.certificateType']);
        }

        // Apply filters if provided
        if (!empty($this->filters['department_id'])) {
            $query->where('department_id', $this->filters['department_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['search'])) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->filters['search'] . '%')
                  ->orWhere('employee_id', 'like', '%' . $this->filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $this->filters['search'] . '%');
            });
        }

        return $query->orderBy('name')->get();
    }

    public function headings(): array
    {
        $baseHeadings = [
            'Employee ID',
            'Name',
            'Email',
            'Phone',
            'Department',
            'Position',
            'Position Level',
            'Employment Type',
            'Status',
            'Hire Date',
            'Supervisor',
            'Background Check Date',
            'Background Check Status',
            'Background Check Files Count',
            'Emergency Contact Name',
            'Emergency Contact Phone',
            'Address',
            'Created At',
            'Updated At'
        ];

        if ($this->includeCertificates) {
            $baseHeadings = array_merge($baseHeadings, [
                'Total Certificates',
                'Active Certificates',
                'Expired Certificates',
                'Expiring Soon',
                'Compliance Rate (%)'
            ]);
        }

        return $baseHeadings;
    }

    public function map($employee): array
    {
        $baseData = [
            $employee->employee_id,
            $employee->name,
            $employee->email,
            $employee->phone,
            $employee->department ? $employee->department->name : 'N/A',
            $employee->position,
            $employee->position_level,
            $employee->employment_type,
            ucfirst($employee->status),
            $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '',
            $employee->supervisor ? $employee->supervisor->name : '',
            $employee->background_check_date ? $employee->background_check_date->format('Y-m-d') : '',
            $this->formatBackgroundCheckStatus($employee->background_check_status),
            count($employee->background_check_files ?? []),
            $employee->emergency_contact_name,
            $employee->emergency_contact_phone,
            $employee->address,
            $employee->created_at->format('Y-m-d H:i:s'),
            $employee->updated_at->format('Y-m-d H:i:s')
        ];

        if ($this->includeCertificates && method_exists($employee, 'getComplianceStatistics')) {
            $compliance = $employee->getComplianceStatistics();
            $baseData = array_merge($baseData, [
                $compliance['total_certificates'] ?? 0,
                $compliance['active_certificates'] ?? 0,
                $compliance['expired_certificates'] ?? 0,
                $compliance['expiring_soon_certificates'] ?? 0,
                $compliance['compliance_rate'] ?? 100
            ]);
        } elseif ($this->includeCertificates) {
            // Fallback if methods don't exist
            $baseData = array_merge($baseData, [0, 0, 0, 0, 100]);
        }

        return $baseData;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E7D32'] // Dark green
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]
        ];

        // Add borders to all data
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $styles["A1:{$highestColumn}{$highestRow}"] = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        // Status column conditional formatting
        if ($highestRow > 1) {
            $statusColumn = 'I'; // Status is column I
            for ($row = 2; $row <= $highestRow; $row++) {
                $cellValue = $sheet->getCell($statusColumn . $row)->getValue();

                if ($cellValue === 'Active') {
                    $styles[$statusColumn . $row] = [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'C8E6C9'] // Light green
                        ]
                    ];
                } elseif ($cellValue === 'Inactive') {
                    $styles[$statusColumn . $row] = [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFCDD2'] // Light red
                        ]
                    ];
                }
            }
        }

        return $styles;
    }

    public function title(): string
    {
        return 'Employees Export';
    }

    /**
     * Format background check status for display
     */
    private function formatBackgroundCheckStatus(?string $status): string
    {
        if (!$status) {
            return 'Not Started';
        }

        return match($status) {
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'cleared' => 'Cleared',
            'pending_review' => 'Pending Review',
            'requires_follow_up' => 'Requires Follow-up',
            'expired' => 'Expired',
            'rejected' => 'Rejected',
            default => ucwords(str_replace('_', ' ', $status))
        };
    }
}
