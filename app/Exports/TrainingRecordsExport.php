<?php
// app/Exports/TrainingRecordsExport.php

namespace App\Exports;

use App\Models\TrainingRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainingRecordsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = TrainingRecord::with(['employee.department', 'trainingType']);

        // Apply filters if provided
        if (!empty($this->filters['employee_id'])) {
            $query->where('employee_id', $this->filters['employee_id']);
        }

        if (!empty($this->filters['training_type_id'])) {
            $query->where('training_type_id', $this->filters['training_type_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['department_id'])) {
            $query->whereHas('employee', function($q) {
                $q->where('department_id', $this->filters['department_id']);
            });
        }

        if (!empty($this->filters['date_from'])) {
            $query->where('issue_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->where('issue_date', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('issue_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',
            'Training Type',
            'Certificate Number',
            'Issuer',
            'Issue Date',
            'Expiry Date',
            'Status',
            'Days Until Expiry',
            'Notes',
            'Created At'
        ];
    }

    public function map($record): array
    {
        $daysUntilExpiry = \Carbon\Carbon::parse($record->expiry_date)->diffInDays(\Carbon\Carbon::now(), false);

        return [
            $record->employee->employee_id,
            $record->employee->name,
            $record->employee->department ? $record->employee->department->name : 'N/A',
            $record->trainingType->name,
            $record->certificate_number,
            $record->issuer,
            $record->issue_date,
            $record->expiry_date,
            ucfirst(str_replace('_', ' ', $record->status)),
            $daysUntilExpiry <= 0 ? 'Expired' : $daysUntilExpiry . ' days',
            $record->notes,
            $record->created_at->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as header
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
