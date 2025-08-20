<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeesImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Employee([
            'employee_id' => $row['employee_id'],
            'name' => $row['name'],
            'department_id' => $row['department_id'] ?? null,
            'position' => $row['position'] ?? null,
            'status' => $row['status'] ?? 'active',
            'background_check_date' => $row['background_check_date'] ?? null,
            'background_check_notes' => $row['background_check_notes'] ?? null,
        ]);
    }
}
