<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContainersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Get employee containers data
     */
    public function collection()
    {
        $query = Employee::with(['department', 'employeeCertificates.certificateType'])
            ->withCount([
                'employeeCertificates',
                'employeeCertificates as active_certificates_count' => function ($query) {
                    $query->where('status', 'active');
                },
                'employeeCertificates as expired_certificates_count' => function ($query) {
                    $query->where('status', 'expired');
                },
                'employeeCertificates as expiring_soon_certificates_count' => function ($query) {
                    $query->where('status', 'expiring_soon');
                }
            ])
            ->orderBy('employee_id');

        // Apply filters
        if (!empty($this->filters['department_id'])) {
            $query->byDepartment($this->filters['department_id']);
        }

        if (!empty($this->filters['search'])) {
            $query->search($this->filters['search']);
        }

        if (!empty($this->filters['status'])) {
            switch ($this->filters['status']) {
                case 'has_expired':
                    $query->whereHas('employeeCertificates', function ($q) {
                        $q->where('status', 'expired');
                    });
                    break;
                case 'expiring_soon':
                    $query->whereHas('employeeCertificates', function ($q) {
                        $q->where('status', 'expiring_soon');
                    });
                    break;
                case 'all_valid':
                    $query->whereDoesntHave('employeeCertificates', function ($q) {
                        $q->whereIn('status', ['expired', 'expiring_soon']);
                    });
                    break;
            }
        }

        return $query->get();
    }

    /**
     * Map employee container data to Excel columns
     */
    public function map($employee): array
    {
        // Get background check count
        $backgroundCheckFiles = is_string($employee->background_check_files)
            ? json_decode($employee->background_check_files, true)
            : $employee->background_check_files;
        $backgroundCheckCount = is_array($backgroundCheckFiles) ? count($backgroundCheckFiles) : 0;

        // Get latest certificate info
        $latestCert = $employee->employeeCertificates->sortByDesc('created_at')->first();

        return [
            $employee->employee_id ?? $employee->nip,
            $employee->name,
            $employee->department?->name ?? 'No Department',
            $employee->position ?? '-',
            $employee->email ?? '-',
            $employee->phone ?? '-',
            $backgroundCheckCount,
            $employee->background_check_date?->format('Y-m-d') ?? '-',
            $employee->background_check_status ?? '-',
            $employee->employee_certificates_count ?? 0,
            $employee->active_certificates_count ?? 0,
            $employee->expired_certificates_count ?? 0,
            $employee->expiring_soon_certificates_count ?? 0,
            $latestCert?->certificateType?->name ?? '-',
            $latestCert?->certificate_number ?? '-',
            $latestCert?->issue_date?->format('Y-m-d') ?? '-',
            $latestCert?->expiry_date?->format('Y-m-d') ?? '-',
            $latestCert?->status ? ucfirst($latestCert->status) : '-',
        ];
    }

    /**
     * Excel column headings
     */
    public function headings(): array
    {
        return [
            'Employee ID',
            'Name',
            'Department',
            'Position',
            'Email',
            'Phone',
            'Background Check Files',
            'Background Check Date',
            'Background Check Status',
            'Total Certificates',
            'Active Certificates',
            'Expired Certificates',
            'Expiring Soon',
            'Latest Certificate Type',
            'Latest Certificate Number',
            'Latest Issue Date',
            'Latest Expiry Date',
            'Latest Certificate Status',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Get highest row
        $highestRow = $sheet->getHighestRow();

        // Add borders
        $sheet->getStyle("A1:R{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Freeze header row
        $sheet->freezePane('A2');

        // Set row height for header
        $sheet->getRowDimension('1')->setRowHeight(25);

        return $sheet;
    }
}
