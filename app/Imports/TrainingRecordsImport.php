<?php

namespace App\Imports;

use App\Models\TrainingRecord;
use App\Models\Employee;
use App\Models\TrainingType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TrainingRecordsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $employee = Employee::where('employee_id', $row['employee_id'])->first();
        $trainingType = TrainingType::where('name', $row['training_type'])->first();

        if (!$employee || !$trainingType) {
            return null; // Skip this row if employee or training type not found
        }

        return new TrainingRecord([
            'employee_id' => $employee->id,
            'training_type_id' => $trainingType->id,
            'certificate_number' => $row['certificate_number'],
            'issuer' => $row['issuer'],
            'issue_date' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['issue_date']),
            'expiry_date' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['expiry_date']),
            'notes' => $row['notes'] ?? null,
        ]);
    }
}
