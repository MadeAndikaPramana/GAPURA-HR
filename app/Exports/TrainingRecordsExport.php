<?php

namespace App\Exports;

use App\Models\TrainingRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrainingRecordsExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return TrainingRecord::all();
    }

    public function headings(): array
    {
        return [
            'id',
            'employee_id',
            'training_type_id',
            'certificate_number',
            'issuer',
            'issue_date',
            'expiry_date',
            'status',
            'notes',
            'created_at',
            'updated_at',
        ];
    }
}
