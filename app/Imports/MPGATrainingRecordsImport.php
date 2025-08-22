<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class MPGATrainingRecordsImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * Transform a row into a training record model
     */
    public function model(array $row)
    {
        // Find or create employee
        $employee = $this->findOrCreateEmployee($row);

        // Find training type
        $trainingType = $this->findTrainingType($row['training_type']);

        if (!$employee || !$trainingType) {
            return null;
        }

        // Parse dates
        $issueDate = $this->parseDate($row['issue_date']);
        $expiryDate = $this->parseDate($row['expiry_date']);

        // Determine status
        $status = $this->determineStatus($expiryDate);
        $complianceStatus = $status === 'active' ? 'compliant' : 'non_compliant';

        return new TrainingRecord([
            'employee_id' => $employee->id,
            'training_type_id' => $trainingType->id,
            'certificate_number' => $row['certificate_number'],
            'issuer' => $row['issuer'] ?? 'GAPURA TRAINING CENTER',
            'training_provider' => $row['training_provider'] ?? 'GLC (Gapura Learning Center)',
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'completion_date' => $issueDate,
            'status' => $status,
            'compliance_status' => $complianceStatus,
            'notes' => $row['notes'] ?? 'Imported from MPGA training records',
        ]);
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'employee_name' => 'required|string',
            'nip' => 'required|string',
            'department' => 'required|string',
            'training_type' => 'required|string',
            'certificate_number' => 'required|string|unique:training_records,certificate_number',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
        ];
    }

    private function findOrCreateEmployee($row)
    {
        // Find by NIP first
        $employee = Employee::where('nip', $row['nip'])->first();

        if (!$employee) {
            // Create new employee
            $department = Department::where('name', 'like', '%' . $row['department'] . '%')
                         ->orWhere('code', strtoupper($row['department']))
                         ->first();

            if (!$department) {
                return null;
            }

            $employee = Employee::create([
                'employee_id' => 'GAP' . str_pad(Employee::count() + 1, 3, '0', STR_PAD_LEFT),
                'name' => $row['employee_name'],
                'nip' => $row['nip'],
                'department_id' => $department->id,
                'position' => $row['position'] ?? 'Staff',
                'status' => 'active',
                'hire_date' => Carbon::now()->subYear(),
                'email' => strtolower(str_replace(' ', '.', $row['employee_name'])) . '@gapura.com',
            ]);
        }

        return $employee;
    }

    private function findTrainingType($trainingName)
    {
        // Map MPGA training names to system codes
        $trainingMap = [
            'PAX & BAGGAGE HANDLING' => 'PAX_BAG',
            'SAFETY TRAINING' => 'SMS',
            'SMS' => 'SMS',
            'HUMAN FACTOR' => 'HF',
            'DANGEROUS GOODS' => 'DGA',
            'DANGEROUS GOODS AWARENESS' => 'DGA',
            'AVIATION SECURITY' => 'AVSEC_AWR',
            'AVSEC AWARENESS' => 'AVSEC_AWR',
            'PORTER TRAINING' => 'PORTER',
            'GSE OPERATOR' => 'GSE_OPR',
            'FOO LICENSE' => 'FOO',
        ];

        $trainingCode = null;
        foreach ($trainingMap as $pattern => $code) {
            if (stripos($trainingName, $pattern) !== false) {
                $trainingCode = $code;
                break;
            }
        }

        if ($trainingCode) {
            return TrainingType::where('code', $trainingCode)->first();
        }

        return null;
    }

    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        // Handle various date formats
        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'd-M-y', 'd-M-Y'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $dateString);
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try default parsing
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function determineStatus($expiryDate)
    {
        if (!$expiryDate) {
            return 'unknown';
        }

        $daysDiff = Carbon::now()->diffInDays($expiryDate, false);

        if ($daysDiff < 0) {
            return 'expired';
        } elseif ($daysDiff <= 30) {
            return 'expiring_soon';
        } elseif ($daysDiff <= 90) {
            return 'expiring';
        } else {
            return 'active';
        }
    }
}

/**
 * MPGA Training Records Export Template
 */
class MPGATrainingRecordsTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Return sample data based on MPGA format
        return collect([
            [
                'employee_name' => 'PUTU EKA RESMAWAN',
                'nip' => '21608001',
                'department' => 'DEDICATED',
                'position' => 'AE Operator',
                'training_type' => 'PAX & BAGGAGE HANDLING',
                'certificate_number' => 'GLC/OPR-001129/OCT/2024',
                'issuer' => 'GAPURA TRAINING CENTER',
                'training_provider' => 'GLC (Gapura Learning Center)',
                'issue_date' => '2024-10-07',
                'expiry_date' => '2027-10-07',
                'notes' => 'Training completed successfully'
            ],
            [
                'employee_name' => 'I MADE PARTANA',
                'nip' => '21020059',
                'department' => 'PORTER',
                'position' => 'Porter',
                'training_type' => 'PORTER TRAINING',
                'certificate_number' => 'GLC/OPR-003895/SEP/2022',
                'issuer' => 'GAPURA TRAINING CENTER',
                'training_provider' => 'GLC (Gapura Learning Center)',
                'issue_date' => '2022-09-02',
                'expiry_date' => '2025-09-02',
                'notes' => 'Porter certification completed'
            ],
            [
                'employee_name' => 'I KETUT SUWITRA',
                'nip' => '21060078',
                'department' => 'AVSEC',
                'position' => 'Security Officer',
                'training_type' => 'AVIATION SECURITY AWARENESS',
                'certificate_number' => 'GLC/OPR-000800/APR/2025',
                'issuer' => 'GAPURA TRAINING CENTER',
                'training_provider' => 'GLC (Gapura Learning Center)',
                'issue_date' => '2025-04-11',
                'expiry_date' => '2026-04-11',
                'notes' => 'AVSEC awareness training completed'
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'NIP',
            'Department',
            'Position',
            'Training Type',
            'Certificate Number',
            'Issuer',
            'Training Provider',
            'Issue Date',
            'Expiry Date',
            'Notes'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '22C55E'], // Green color matching GAPURA theme
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Data styling
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A2:K{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Auto-fit columns
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return [];
    }
}

/**
 * Validation Rules Helper
 */
class MPGAValidationHelper
{
    public static function getValidationRules()
    {
        return [
            'employee_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\.]+$/' // Only letters, spaces, and dots
            ],
            'nip' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9]+$/' // Only numbers
            ],
            'department' => [
                'required',
                'string',
                'in:DEDICATED,LOADING,RAMP,LOCO,ULD,LNF,CARGO,ARRIVAL,GSE,FLOP,AVSEC,PORTER'
            ],
            'position' => [
                'required',
                'string',
                'max:100'
            ],
            'training_type' => [
                'required',
                'string',
                'max:255'
            ],
            'certificate_number' => [
                'required',
                'string',
                'max:100',
                'regex:/^GLC\/OPR-[0-9]+\/[A-Z]{3}\/[0-9]{4}$/', // MPGA format
                'unique:training_records,certificate_number'
            ],
            'issue_date' => [
                'required',
                'date',
                'before_or_equal:today'
            ],
            'expiry_date' => [
                'required',
                'date',
                'after:issue_date'
            ]
        ];
    }

    public static function getCertificateNumberFormat()
    {
        return 'GLC/OPR-XXXXXX/MONTH/YEAR (e.g., GLC/OPR-003895/SEP/2022)';
    }

    public static function getTrainingTypes()
    {
        return [
            'PAX & BAGGAGE HANDLING (36 Months)',
            'SAFETY TRAINING (SMS) (36 Months)',
            'HUMAN FACTOR (36 Months)',
            'DANGEROUS GOODS AWARENESS (24 Months)',
            'AVIATION SECURITY AWARENESS (12 Months)',
            'PORTER TRAINING (36 Months)',
            'GSE OPERATOR TRAINING (36 Months)',
            'FOO LICENSE TRAINING (60 Months)'
        ];
    }

    public static function getDepartments()
    {
        return [
            'DEDICATED' => 'Dedicated Services',
            'LOADING' => 'Loading Operations',
            'RAMP' => 'Ramp Operations',
            'LOCO' => 'Locomotive Operations',
            'ULD' => 'ULD Operations',
            'LNF' => 'Lost & Found',
            'CARGO' => 'Cargo Operations',
            'ARRIVAL' => 'Arrival Services',
            'GSE' => 'GSE Operations',
            'FLOP' => 'Flight Operations',
            'AVSEC' => 'Aviation Security',
            'PORTER' => 'Porter Services'
        ];
    }
}
