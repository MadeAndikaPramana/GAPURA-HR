<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EmployeeDataExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, ShouldAutoSize
{
    private array $filters;
    private bool $includeInactive;

    public function __construct(array $filters = [], bool $includeInactive = false)
    {
        $this->filters = $filters;
        $this->includeInactive = $includeInactive;
    }

    /**
     * Get all current employee data
     */
    public function collection()
    {
        $query = Employee::with(['department'])
                        ->orderBy('employee_id');

        // Apply filters if provided
        if (!empty($this->filters['department_id'])) {
            $query->where('department_id', $this->filters['department_id']);
        }

        if (!empty($this->filters['search'])) {
            $query->search($this->filters['search']);
        }

        // Include/exclude inactive employees
        if (!$this->includeInactive) {
            $query->where('status', 'active');
        }

        return $query->get();
    }

    /**
     * Map employee data to Excel columns
     */
    public function map($employee): array
    {
        return [
            $employee->employee_id ?? $employee->nip,          // A: NIP/Employee ID
            $employee->name,                                    // B: Full Name
            $employee->department?->name ?? '',                // C: Department
            $employee->position ?? '',                         // D: Position
            $employee->email ?? '',                            // E: Email
            $employee->phone ?? '',                            // F: Phone
            $employee->hire_date?->format('Y-m-d') ?? '',     // G: Hire Date
            '',                                                // H: Birth Date (not in table)
            '',                                                // I: Birth Place (not in table)
            '',                                                // J: Gender (not in table)
            '',                                                // K: Address (not in table)
            $employee->status ?? 'active',                     // L: Employment Status
            $this->formatStatus($employee->status),            // M: Active Status
            '',                                                // N: Education (not in table)
            '',                                                // O: Marital Status (not in table)
            '',                                                // P: Religion (not in table)
            '',                                                // Q: Nationality (not in table)
            '',                                                // R: ID Number/NIK (not in table)
            '',                                                // S: Salary (not in table)
            '',                                                // T: Notes (not in table)
        ];
    }

    /**
     * Excel column headings
     */
    public function headings(): array
    {
        return [
            'NIP',                    // A
            'Nama Lengkap',          // B
            'Department',            // C
            'Jabatan',               // D
            'Email',                 // E
            'No. HP',                // F
            'Tanggal Masuk',         // G
            'Tanggal Lahir',         // H
            'Tempat Lahir',          // I
            'Jenis Kelamin',         // J
            'Alamat',                // K
            'Status Pegawai',        // L
            'Status Aktif',          // M
            'Pendidikan',            // N
            'Status Pernikahan',     // O
            'Agama',                 // P
            'Kewarganegaraan',       // Q
            'NIK',                   // R
            'Gaji',                  // S
            'Catatan',               // T
        ];
    }

    /**
     * Format gender for display
     */
    private function formatGender(?string $gender): string
    {
        if (empty($gender)) {
            return '';
        }

        return match (strtolower($gender)) {
            'male', 'laki-laki', 'l', 'm' => 'Laki-laki',
            'female', 'perempuan', 'p', 'f' => 'Perempuan',
            default => $gender
        };
    }

    /**
     * Format status for display
     */
    private function formatStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
            default => 'Aktif'
        };
    }

    /**
     * Column formatting
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,          // NIP as text
            'G' => NumberFormat::FORMAT_DATE_YYYYMMDD, // Hire Date
            'H' => NumberFormat::FORMAT_DATE_YYYYMMDD, // Birth Date
            'R' => NumberFormat::FORMAT_TEXT,          // ID Number as text
            'S' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Salary
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:T1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Data validation and protection
        $highestRow = $sheet->getHighestRow();

        // Add data validation for specific columns
        $this->addValidationRules($sheet, $highestRow);

        // Add borders to all data
        $sheet->getStyle("A1:T{$highestRow}")->applyFromArray([
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

    /**
     * Add data validation rules to ensure data integrity
     */
    private function addValidationRules(Worksheet $sheet, int $highestRow): void
    {
        // Gender validation (Column J)
        $genderValidation = $sheet->getCell('J2')->getDataValidation();
        $genderValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $genderValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
        $genderValidation->setAllowBlank(true);
        $genderValidation->setShowDropDown(true);
        $genderValidation->setFormula1('"Laki-laki,Perempuan"');
        $genderValidation->setErrorTitle('Invalid Gender');
        $genderValidation->setError('Please select either "Laki-laki" or "Perempuan"');

        // Copy validation to all rows
        if ($highestRow > 2) {
            $sheet->duplicateStyle($sheet->getStyle('J2'), "J3:J{$highestRow}");
        }

        // Active Status validation (Column M)
        $statusValidation = $sheet->getCell('M2')->getDataValidation();
        $statusValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $statusValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
        $statusValidation->setAllowBlank(false);
        $statusValidation->setShowDropDown(true);
        $statusValidation->setFormula1('"Aktif,Nonaktif"');
        $statusValidation->setErrorTitle('Invalid Status');
        $statusValidation->setError('Please select either "Aktif" or "Nonaktif"');

        // Copy validation to all rows
        if ($highestRow > 2) {
            $sheet->duplicateStyle($sheet->getStyle('M2'), "M3:M{$highestRow}");
        }

        // Employment Status validation (Column L)
        $empStatusValidation = $sheet->getCell('L2')->getDataValidation();
        $empStatusValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $empStatusValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_WARNING);
        $empStatusValidation->setAllowBlank(true);
        $empStatusValidation->setShowDropDown(true);
        $empStatusValidation->setFormula1('"permanent,contract,intern,temporary"');
        $empStatusValidation->setErrorTitle('Invalid Employment Status');
        $empStatusValidation->setError('Recommended values: permanent, contract, intern, temporary');

        // Copy validation to all rows
        if ($highestRow > 2) {
            $sheet->duplicateStyle($sheet->getStyle('L2'), "L3:L{$highestRow}");
        }

        // Department validation with dynamic list
        $departments = Department::where('is_active', true)->pluck('name')->toArray();
        if (!empty($departments)) {
            $departmentList = '"' . implode(',', $departments) . '"';

            $deptValidation = $sheet->getCell('C2')->getDataValidation();
            $deptValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $deptValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_WARNING);
            $deptValidation->setAllowBlank(true);
            $deptValidation->setShowDropDown(true);
            $deptValidation->setFormula1($departmentList);
            $deptValidation->setErrorTitle('Invalid Department');
            $deptValidation->setError('Please select from existing departments or create a new one');

            // Copy validation to all rows
            if ($highestRow > 2) {
                $sheet->duplicateStyle($sheet->getStyle('C2'), "C3:C{$highestRow}");
            }
        }

        // Date format validation for hire date (Column G)
        $hireDateValidation = $sheet->getCell('G2')->getDataValidation();
        $hireDateValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DATE);
        $hireDateValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_WARNING);
        $hireDateValidation->setAllowBlank(true);
        $hireDateValidation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_BETWEEN);
        $hireDateValidation->setFormula1('1900-01-01');
        $hireDateValidation->setFormula2('2099-12-31');
        $hireDateValidation->setErrorTitle('Invalid Date');
        $hireDateValidation->setError('Please enter date in YYYY-MM-DD format');

        // Copy validation to all rows
        if ($highestRow > 2) {
            $sheet->duplicateStyle($sheet->getStyle('G2'), "G3:G{$highestRow}");
        }

        // Date format validation for birth date (Column H)
        $birthDateValidation = $sheet->getCell('H2')->getDataValidation();
        $birthDateValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DATE);
        $birthDateValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_WARNING);
        $birthDateValidation->setAllowBlank(true);
        $birthDateValidation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_BETWEEN);
        $birthDateValidation->setFormula1('1920-01-01');
        $birthDateValidation->setFormula2('2010-12-31');
        $birthDateValidation->setErrorTitle('Invalid Birth Date');
        $birthDateValidation->setError('Please enter birth date in YYYY-MM-DD format');

        // Copy validation to all rows
        if ($highestRow > 2) {
            $sheet->duplicateStyle($sheet->getStyle('H2'), "H3:H{$highestRow}");
        }
    }

    /**
     * Get export statistics
     */
    public function getExportStatistics(): array
    {
        $collection = $this->collection();

        $stats = [
            'total_employees' => $collection->count(),
            'active_employees' => $collection->where('is_active', true)->count(),
            'inactive_employees' => $collection->where('is_active', false)->count(),
            'departments' => $collection->pluck('department.name')->filter()->unique()->count(),
            'with_email' => $collection->whereNotNull('email')->count(),
            'with_phone' => $collection->whereNotNull('phone')->count(),
            'export_timestamp' => now()->format('Y-m-d H:i:s'),
        ];

        return $stats;
    }
}