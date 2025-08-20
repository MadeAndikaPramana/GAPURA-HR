<?php

namespace App\Exports;

use App\Models\TrainingRecord;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TrainingRecordsExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnFormatting,
    WithColumnWidths,
    WithTitle
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Query for the export
     */
    public function query()
    {
        $query = TrainingRecord::with(['employee.department', 'trainingType']);

        // Apply filters if provided
        if (!empty($this->filters)) {
            // Specific record IDs (for bulk export)
            if (isset($this->filters['record_ids'])) {
                $query->whereIn('id', $this->filters['record_ids']);
            }

            // Status filter
            if (isset($this->filters['status']) && $this->filters['status']) {
                $query->where('status', $this->filters['status']);
            }

            // Training type filter
            if (isset($this->filters['training_type']) && $this->filters['training_type']) {
                $query->where('training_type_id', $this->filters['training_type']);
            }

            // Employee filter
            if (isset($this->filters['employee']) && $this->filters['employee']) {
                $query->where('employee_id', $this->filters['employee']);
            }

            // Date range filter
            if (isset($this->filters['date_from']) && $this->filters['date_from']) {
                $query->where('expiry_date', '>=', $this->filters['date_from']);
            }
            if (isset($this->filters['date_to']) && $this->filters['date_to']) {
                $query->where('expiry_date', '<=', $this->filters['date_to']);
            }

            // Department filter (through employee relationship)
            if (isset($this->filters['department']) && $this->filters['department']) {
                $query->whereHas('employee', function($q) {
                    $q->where('department_id', $this->filters['department']);
                });
            }
        }

        return $query->orderBy('expiry_date', 'desc');
    }

    /**
     * Define the headings for the export
     */
    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',
            'Position',
            'Training Type',
            'Category',
            'Certificate Number',
            'Issuer',
            'Issue Date',
            'Expiry Date',
            'Status',
            'Days Until Expiry',
            'Validity (Months)',
            'Notes',
            'Created At',
            'Updated At'
        ];
    }

    /**
     * Map the data for each row
     */
    public function map($trainingRecord): array
    {
        $daysUntilExpiry = null;
        if ($trainingRecord->expiry_date) {
            $expiryDate = \Carbon\Carbon::parse($trainingRecord->expiry_date);
            $daysUntilExpiry = $expiryDate->diffInDays(\Carbon\Carbon::now(), false);
            if ($daysUntilExpiry < 0) {
                $daysUntilExpiry = abs($daysUntilExpiry) . ' days left';
            } else {
                $daysUntilExpiry = $daysUntilExpiry . ' days ago';
            }
        }

        return [
            $trainingRecord->employee->employee_id ?? '',
            $trainingRecord->employee->name ?? '',
            $trainingRecord->employee->department->name ?? '',
            $trainingRecord->employee->position ?? '',
            $trainingRecord->trainingType->name ?? '',
            $trainingRecord->trainingType->category ?? '',
            $trainingRecord->certificate_number,
            $trainingRecord->issuer,
            $trainingRecord->issue_date ? \Carbon\Carbon::parse($trainingRecord->issue_date)->format('Y-m-d') : '',
            $trainingRecord->expiry_date ? \Carbon\Carbon::parse($trainingRecord->expiry_date)->format('Y-m-d') : '',
            ucfirst(str_replace('_', ' ', $trainingRecord->status)),
            $daysUntilExpiry,
            $trainingRecord->trainingType->validity_months ?? '',
            $trainingRecord->notes ?? '',
            $trainingRecord->created_at ? $trainingRecord->created_at->format('Y-m-d H:i:s') : '',
            $trainingRecord->updated_at ? $trainingRecord->updated_at->format('Y-m-d H:i:s') : '',
        ];
    }

    /**
     * Define column formatting
     */
    public function columnFormats(): array
    {
        return [
            'I' => NumberFormat::FORMAT_DATE_YYYYMMDD, // Issue Date
            'J' => NumberFormat::FORMAT_DATE_YYYYMMDD, // Expiry Date
            'O' => NumberFormat::FORMAT_DATE_DATETIME, // Created At
            'P' => NumberFormat::FORMAT_DATE_DATETIME, // Updated At
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12, // Employee ID
            'B' => 25, // Employee Name
            'C' => 20, // Department
            'D' => 20, // Position
            'E' => 25, // Training Type
            'F' => 15, // Category
            'G' => 20, // Certificate Number
            'H' => 15, // Issuer
            'I' => 12, // Issue Date
            'J' => 12, // Expiry Date
            'K' => 15, // Status
            'L' => 18, // Days Until Expiry
            'M' => 12, // Validity (Months)
            'N' => 30, // Notes
            'O' => 18, // Created At
            'P' => 18, // Updated At
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'], // Gapura green
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
            ],

            // All data cells
            'A2:P1000' => [
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

            // Status column conditional formatting would go here
            // This is simplified - you'd implement conditional formatting separately
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        $title = 'Training Records';

        if (!empty($this->filters['status'])) {
            $title .= ' - ' . ucfirst($this->filters['status']);
        }

        return $title;
    }
}

// Additional class for multiple sheets export
class TrainingRecordsMultiSheetExport implements \Maatwebsite\Excel\Concerns\WithMultipleSheets
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $sheets = [];

        // All records sheet
        $sheets[] = new TrainingRecordsExport($this->filters);

        // Active records sheet
        $activeFilters = array_merge($this->filters, ['status' => 'active']);
        $sheets[] = new TrainingRecordsExport($activeFilters);

        // Expiring soon sheet
        $expiringFilters = array_merge($this->filters, ['status' => 'expiring_soon']);
        $sheets[] = new TrainingRecordsExport($expiringFilters);

        // Expired records sheet
        $expiredFilters = array_merge($this->filters, ['status' => 'expired']);
        $sheets[] = new TrainingRecordsExport($expiredFilters);

        return $sheets;
    }
}
