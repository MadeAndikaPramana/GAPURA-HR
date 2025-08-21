<?php
// app/Exports/EmployeesExport.php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        $query = Employee::with('department');

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
                  ->orWhere('employee_id', 'like', '%' . $this->filters['search'] . '%');
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Name',
            'Department',
            'Position',
            'Status',
            'Hire Date',
            'Background Check Date',
            'Background Check Status',
            'Created At',
            'Updated At'
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->employee_id,
            $employee->name,
            $employee->department ? $employee->department->name : 'N/A',
            $employee->position,
            ucfirst($employee->status),
            $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '',
            $employee->background_check_date ? $employee->background_check_date->format('Y-m-d') : '',
            $employee->background_check_status ? ucfirst($employee->background_check_status) : '',
            $employee->created_at->format('Y-m-d H:i:s'),
            $employee->updated_at->format('Y-m-d H:i:s')
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
