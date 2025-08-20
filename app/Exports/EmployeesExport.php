<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeesExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Employee::all();
    }

    public function headings(): array
    {
        return [
            'id',
            'employee_id',
            'name',
            'department_id',
            'position',
            'status',
            'background_check_date',
            'background_check_notes',
            'created_at',
            'updated_at',
        ];
    }
}
