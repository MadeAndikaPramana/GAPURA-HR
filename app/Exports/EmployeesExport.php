<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class EmployeesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    private array $filters;
    private bool $includeCertificates;

    public function __construct(array $filters = [], bool $includeCertificates = false)
    {
        $this->filters = $filters;
        $this->includeCertificates = $includeCertificates;
    }

    public function query()
    {
        $query = Employee::with(['department', 'employeeCertificates.certificateType']);

        // Apply filters
        if (!empty($this->filters['department'])) {
            $query->where('department_id', $this->filters['department']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['employee_ids'])) {
            $query->whereIn('id', $this->filters['employee_ids']);
        }

        return $query;
    }

    public function headings(): array
    {
        $headings = [
            'Employee ID',
            'Name',
            'Email',
            'Phone',
            'Department',
            'Position',
            'Hire Date',
            'Status'
        ];

        if ($this->includeCertificates) {
            $headings = array_merge($headings, [
                'Total Certificates',
                'Active Certificates',
                'Expired Certificates',
                'Latest Certificate'
            ]);
        }

        return $headings;
    }

    public function map($employee): array
    {
        $row = [
            $employee->employee_id,
            $employee->name,
            $employee->email,
            $employee->phone,
            $employee->department?->name,
            $employee->position,
            $employee->hire_date?->format('Y-m-d'),
            $employee->status
        ];

        if ($this->includeCertificates) {
            $activeCerts = $employee->employeeCertificates->where('status', 'active')->count();
            $expiredCerts = $employee->employeeCertificates->where('status', 'expired')->count();
            $latestCert = $employee->employeeCertificates->sortByDesc('issue_date')->first();

            $row = array_merge($row, [
                $employee->employeeCertificates->count(),
                $activeCerts,
                $expiredCerts,
                $latestCert ? $latestCert->certificateType->name . ' (' . $latestCert->issue_date->format('Y-m-d') . ')' : 'None'
            ]);
        }

        return $row;
    }
}