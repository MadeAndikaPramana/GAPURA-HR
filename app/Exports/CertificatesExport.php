<?php

namespace App\Exports;

use App\Models\Certificate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class CertificatesExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    ShouldAutoSize,
    WithEvents
{
    protected $certificates;
    protected $includeExpired;
    protected $department;
    protected $trainingType;

    public function __construct($certificates = null, $includeExpired = true, $department = null, $trainingType = null)
    {
        $this->certificates = $certificates;
        $this->includeExpired = $includeExpired;
        $this->department = $department;
        $this->trainingType = $trainingType;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        if ($this->certificates) {
            return $this->certificates;
        }

        $query = Certificate::with([
            'trainingRecord.employee.department',
            'trainingRecord.trainingType.category',
            'trainingRecord.trainingProvider',
            'verifiedBy'
        ]);

        // Apply filters
        if (!$this->includeExpired) {
            $query->active();
        }

        if ($this->department) {
            $query->whereHas('trainingRecord.employee', function ($q) {
                $q->where('department_id', $this->department);
            });
        }

        if ($this->trainingType) {
            $query->whereHas('trainingRecord', function ($q) {
                $q->where('training_type_id', $this->trainingType);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Map each certificate to a row
     */
    public function map($certificate): array
    {
        return [
            $certificate->certificate_number ?: 'N/A',
            $certificate->trainingRecord->employee->employee_id,
            $certificate->trainingRecord->employee->name,
            $certificate->trainingRecord->employee->department->name ?? 'N/A',
            $certificate->trainingRecord->employee->position ?? 'N/A',
            $certificate->trainingRecord->trainingType->name,
            $certificate->trainingRecord->trainingType->category->name ?? 'N/A',
            $certificate->trainingRecord->trainingProvider->name ?? 'N/A',
            $certificate->issued_by,
            $certificate->issue_date ? $certificate->issue_date->format('d/m/Y') : 'N/A',
            $certificate->expiry_date ? $certificate->expiry_date->format('d/m/Y') : 'No Expiry',
            $this->getStatusText($certificate->status),
            $certificate->days_until_expiry !== null ?
                ($certificate->days_until_expiry < 0 ?
                    'Expired ' . abs($certificate->days_until_expiry) . ' days ago' :
                    $certificate->days_until_expiry . ' days remaining'
                ) : 'N/A',
            $certificate->is_verified ? 'Yes' : 'No',
            $certificate->verifiedBy?->name ?? 'N/A',
            $certificate->verification_date ? $certificate->verification_date->format('d/m/Y H:i') : 'N/A',
            $certificate->verification_code ?? 'N/A',
            $certificate->trainingRecord->score ?? 'N/A',
            $certificate->trainingRecord->cost ? 'Rp ' . number_format($certificate->trainingRecord->cost, 0, ',', '.') : 'N/A',
            $certificate->trainingRecord->training_hours ?? 'N/A',
            $certificate->trainingRecord->location ?? 'N/A',
            $certificate->trainingRecord->instructor_name ?? 'N/A',
            $certificate->trainingRecord->batch_number ?? 'N/A',
            $certificate->notes ?? '',
            $certificate->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Certificate Number',
            'Employee ID',
            'Employee Name',
            'Department',
            'Position',
            'Training Type',
            'Training Category',
            'Training Provider',
            'Issued By',
            'Issue Date',
            'Expiry Date',
            'Status',
            'Days Until Expiry',
            'Verified',
            'Verified By',
            'Verification Date',
            'Verification Code',
            'Training Score',
            'Training Cost',
            'Training Hours',
            'Training Location',
            'Instructor Name',
            'Batch Number',
            'Notes',
            'Created Date',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:Y1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'], // Gapura green
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        return [
            // Style for all cells
            'A:Y' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Set column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 20, // Certificate Number
            'B' => 15, // Employee ID
            'C' => 25, // Employee Name
            'D' => 20, // Department
            'E' => 20, // Position
            'F' => 30, // Training Type
            'G' => 18, // Training Category
            'H' => 25, // Training Provider
            'I' => 20, // Issued By
            'J' => 12, // Issue Date
            'K' => 12, // Expiry Date
            'L' => 15, // Status
            'M' => 18, // Days Until Expiry
            'N' => 10, // Verified
            'O' => 20, // Verified By
            'P' => 18, // Verification Date
            'Q' => 18, // Verification Code
            'R' => 12, // Training Score
            'S' => 15, // Training Cost
            'T' => 12, // Training Hours
            'U' => 20, // Training Location
            'V' => 20, // Instructor Name
            'W' => 15, // Batch Number
            'X' => 30, // Notes
            'Y' => 18, // Created Date
        ];
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        return 'Certificates Export';
    }

    /**
     * Register events for additional formatting
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Apply conditional formatting for status column (L)
                for ($row = 2; $row <= $highestRow; $row++) {
                    $statusCell = 'L' . $row;
                    $status = $sheet->getCell($statusCell)->getValue();

                    $statusColor = $this->getStatusColor($status);
                    if ($statusColor) {
                        $sheet->getStyle($statusCell)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $statusColor],
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => $this->getStatusTextColor($status)],
                            ],
                        ]);
                    }
                }

                // Apply conditional formatting for verification column (N)
                for ($row = 2; $row <= $highestRow; $row++) {
                    $verifiedCell = 'N' . $row;
                    $verified = $sheet->getCell($verifiedCell)->getValue();

                    if ($verified === 'Yes') {
                        $sheet->getStyle($verifiedCell)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'D1FAE5'], // Light green
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => '065F46'], // Dark green
                            ],
                        ]);
                    } else {
                        $sheet->getStyle($verifiedCell)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FEE2E2'], // Light red
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => '991B1B'], // Dark red
                            ],
                        ]);
                    }
                }

                // Format date columns
                $dateColumns = ['J', 'K', 'P', 'Y']; // Issue Date, Expiry Date, Verification Date, Created Date
                foreach ($dateColumns as $column) {
                    $sheet->getStyle($column . '2:' . $column . $highestRow)->getNumberFormat()
                        ->setFormatCode('dd/mm/yyyy');
                }

                // Format cost column (S)
                $sheet->getStyle('S2:S' . $highestRow)->getNumberFormat()
                    ->setFormatCode('#,##0');

                // Freeze the header row
                $sheet->freezePane('A2');

                // Set auto filter
                $sheet->setAutoFilter('A1:Y1');

                // Add summary at the bottom
                $summaryRow = $highestRow + 3;
                $sheet->setCellValue('A' . $summaryRow, 'SUMMARY STATISTICS');
                $sheet->getStyle('A' . $summaryRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                ]);

                // Calculate statistics
                $totalCertificates = $highestRow - 1;
                $sheet->setCellValue('A' . ($summaryRow + 1), 'Total Certificates:');
                $sheet->setCellValue('B' . ($summaryRow + 1), $totalCertificates);

                // Count by status
                $sheet->setCellValue('A' . ($summaryRow + 2), 'Active Certificates:');
                $sheet->setCellValue('B' . ($summaryRow + 2), "=COUNTIF(L2:L{$highestRow},\"Active\")");

                $sheet->setCellValue('A' . ($summaryRow + 3), 'Expired Certificates:');
                $sheet->setCellValue('B' . ($summaryRow + 3), "=COUNTIF(L2:L{$highestRow},\"Expired\")");

                $sheet->setCellValue('A' . ($summaryRow + 4), 'Expiring Soon:');
                $sheet->setCellValue('B' . ($summaryRow + 4), "=COUNTIF(L2:L{$highestRow},\"Expiring Soon\")");

                $sheet->setCellValue('A' . ($summaryRow + 5), 'Verified Certificates:');
                $sheet->setCellValue('B' . ($summaryRow + 5), "=COUNTIF(N2:N{$highestRow},\"Yes\")");

                // Average training score
                $sheet->setCellValue('A' . ($summaryRow + 6), 'Average Training Score:');
                $sheet->setCellValue('B' . ($summaryRow + 6), "=AVERAGE(R2:R{$highestRow})");

                // Total training cost
                $sheet->setCellValue('A' . ($summaryRow + 7), 'Total Training Cost:');
                $sheet->setCellValue('B' . ($summaryRow + 7), "=SUM(S2:S{$highestRow})");

                // Style summary section
                $sheet->getStyle('A' . ($summaryRow + 1) . ':B' . ($summaryRow + 7))->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Add generation info
                $infoRow = $summaryRow + 9;
                $sheet->setCellValue('A' . $infoRow, 'Report Generated:');
                $sheet->setCellValue('B' . $infoRow, now()->format('d/m/Y H:i:s'));
                $sheet->setCellValue('A' . ($infoRow + 1), 'Generated By:');
                $sheet->setCellValue('B' . ($infoRow + 1), auth()->user()->name ?? 'System');
                $sheet->setCellValue('A' . ($infoRow + 2), 'System:');
                $sheet->setCellValue('B' . ($infoRow + 2), 'Gapura Training Management System');
            },
        ];
    }

    /**
     * Get status text for display
     */
    private function getStatusText($status)
    {
        return match($status) {
            'active' => 'Active',
            'expired' => 'Expired',
            'expiring_soon' => 'Expiring Soon',
            'expiring' => 'Expiring',
            'permanent' => 'Permanent',
            default => ucfirst(str_replace('_', ' ', $status))
        };
    }

    /**
     * Get status background color
     */
    private function getStatusColor($status)
    {
        return match($status) {
            'Active' => 'D1FAE5', // Light green
            'Expired' => 'FEE2E2', // Light red
            'Expiring Soon' => 'FEF3C7', // Light yellow
            'Expiring' => 'FED7AA', // Light orange
            default => null
        };
    }

    /**
     * Get status text color
     */
    private function getStatusTextColor($status)
    {
        return match($status) {
            'Active' => '065F46', // Dark green
            'Expired' => '991B1B', // Dark red
            'Expiring Soon' => '92400E', // Dark yellow
            'Expiring' => '9A3412', // Dark orange
            default => '000000' // Black
        };
    }
}
