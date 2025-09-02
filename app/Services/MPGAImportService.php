<?php
// app/Services/MPGAImportService.php

namespace App\Services;

use App\Models\Employee;
use App\Models\Department;
use App\Models\CertificateType;
use App\Models\EmployeeCertificate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MPGAImportService
{
    protected array $departmentMapping = [
        'DEDICATED' => 'PASSENGER_HANDLING',
        'LOADING' => 'LOADING',
        'RAMP' => 'RAMP',
        'LOCO' => 'LOCO',
        'ULD' => 'ULD',
        'LOST & FOUND' => 'LOST_FOUND',
        'CARGO' => 'CARGO',
        'ARRIVAL' => 'ARRIVAL',
        'GSE OPERATOR ' => 'GSE_OPERATOR',
        'FLOP' => 'FLOP',
        'AVSEC' => 'AVSEC',
        'PORTER' => 'PORTER'
    ];

    protected array $certificateTypeMapping = [
        // GSE OPERATOR certificates
        'ATT' => ['name' => 'Aircraft Towing Tractor', 'category' => 'GSE_OPERATOR'],
        'FRM' => ['name' => 'Fork Lift / Ramp Equipment', 'category' => 'GSE_OPERATOR'],
        'LLD' => ['name' => 'Low Loader', 'category' => 'GSE_OPERATOR'],
        'BTT' => ['name' => 'Baggage Towing Tractor', 'category' => 'GSE_OPERATOR'],
        'BCS' => ['name' => 'Belt Conveyor System', 'category' => 'GSE_OPERATOR'],
        'PBS' => ['name' => 'Pushback System', 'category' => 'GSE_OPERATOR'],

        // AVSEC certificates
        'AVSEC_BASIC' => ['name' => 'Aviation Security Basic', 'category' => 'AVSEC'],
        'AVSEC_ADVANCED' => ['name' => 'Aviation Security Advanced', 'category' => 'AVSEC'],

        // Passenger Handling
        'PAX_HANDLING' => ['name' => 'Passenger & Baggage Handling', 'category' => 'PASSENGER_HANDLING'],
        'DEDICATED' => ['name' => 'Passenger Handling Dedicated', 'category' => 'PASSENGER_HANDLING'],

        // Operations
        'RAMP_OPS' => ['name' => 'Ramp Operations', 'category' => 'RAMP'],
        'LOADING_OPS' => ['name' => 'Loading Operations', 'category' => 'LOADING'],
        'CARGO_OPS' => ['name' => 'Cargo Operations', 'category' => 'CARGO'],

        // Others
        'HUMAN_FACTOR' => ['name' => 'Human Factor Training', 'category' => 'GENERAL'],
        'PORTER' => ['name' => 'Porter Training', 'category' => 'PORTER'],
        'FLOP' => ['name' => 'Flight Operations Officer', 'category' => 'FLOP'],
    ];

    protected array $stats = [
        'employees_created' => 0,
        'employees_updated' => 0,
        'certificates_created' => 0,
        'departments_created' => 0,
        'certificate_types_created' => 0,
        'errors' => []
    ];

    public function importFromExcel(string $filePath): array
    {
        try {
            Log::info('🚀 Starting MPGA Excel import', ['file' => $filePath]);

            $workbook = IOFactory::load($filePath);

            DB::beginTransaction();

            foreach ($workbook->getSheetNames() as $sheetName) {
                if ($sheetName !== 'PORTER') { // Skip hidden sheet
                    Log::info("📋 Processing sheet: {$sheetName}");
                    $this->importSheet($workbook, $sheetName);
                }
            }

            DB::commit();

            Log::info('✅ MPGA import completed successfully', $this->stats);

            return $this->stats;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ MPGA import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            $this->stats['errors'][] = 'Import failed: ' . $e->getMessage();
            return $this->stats;
        }
    }

    protected function importSheet($workbook, string $sheetName): void
    {
        $worksheet = $workbook->getSheetByName($sheetName);
        $department = $this->findOrCreateDepartment($sheetName);

        // Determine data start row based on sheet structure
        $dataStartRow = $this->findDataStartRow($worksheet);
        $highestRow = $worksheet->getHighestRow();

        Log::info("Processing rows {$dataStartRow} to {$highestRow} in {$sheetName}");

        $currentEmployee = null;

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            try {
                $rowData = $this->extractRowData($worksheet, $row);

                if (empty($rowData)) {
                    continue; // Skip empty rows
                }

                // Check if this row has a new employee (has NIPP)
                if (!empty($rowData['nipp'])) {
                    $currentEmployee = $this->findOrCreateEmployee($rowData, $department);
                }

                // Process certificate if we have valid data and current employee
                if ($currentEmployee && !empty($rowData['certificate_number'])) {
                    $this->createEmployeeCertificate($currentEmployee, $rowData, $sheetName);
                }

            } catch (\Exception $e) {
                Log::warning("Error processing row {$row} in {$sheetName}", [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
                $this->stats['errors'][] = "Row {$row} in {$sheetName}: " . $e->getMessage();
            }
        }
    }

    protected function findDataStartRow($worksheet): int
    {
        // Look for rows containing "No.", "NIPP", "NAMA" which indicate data headers
        for ($row = 1; $row <= 20; $row++) {
            $cellA = $worksheet->getCell("A{$row}")->getValue();
            $cellB = $worksheet->getCell("B{$row}")->getValue();
            $cellC = $worksheet->getCell("C{$row}")->getValue();

            if (
                (is_string($cellA) && stripos($cellA, 'No') !== false) ||
                (is_string($cellB) && stripos($cellB, 'NIPP') !== false) ||
                (is_string($cellC) && stripos($cellC, 'NAMA') !== false)
            ) {
                return $row + 1; // Data starts after headers
            }
        }

        return 10; // Default fallback based on analysis
    }

    protected function extractRowData($worksheet, int $row): array
    {
        $data = [];

        // Column mapping based on MPGA structure analysis
        $data['no'] = $worksheet->getCell("A{$row}")->getValue();
        $data['nipp'] = $worksheet->getCell("B{$row}")->getValue();
        $data['name'] = $worksheet->getCell("C{$row}")->getValue();
        $data['rating'] = $worksheet->getCell("D{$row}")->getValue();
        $data['certificate_number'] = $worksheet->getCell("E{$row}")->getValue();
        $data['issue_date'] = $this->parseDate($worksheet->getCell("F{$row}")->getValue());
        $data['expiry_date'] = $this->parseDate($worksheet->getCell("G{$row}")->getValue());

        // Additional columns might contain other training dates
        $data['training_date'] = $this->parseDate($worksheet->getCell("H{$row}")->getValue());

        // Clean up data
        $data['nipp'] = $this->cleanNipp($data['nipp']);
        $data['name'] = $this->cleanName($data['name']);
        $data['certificate_number'] = $this->cleanCertificateNumber($data['certificate_number']);

        return $data;
    }

    protected function findOrCreateEmployee(array $rowData, Department $department): Employee
    {
        if (empty($rowData['nipp']) || empty($rowData['name'])) {
            throw new \Exception('Missing required employee data (NIPP or Name)');
        }

        $employee = Employee::where('employee_id', $rowData['nipp'])->first();

        if (!$employee) {
            $employee = Employee::create([
                'employee_id' => $rowData['nipp'],
                'name' => $rowData['name'],
                'department_id' => $department->id,
                'position' => $this->inferPositionFromRating($rowData['rating'] ?? ''),
                'status' => 'active',
                'hire_date' => now()->subYear(), // Default to 1 year ago
                'background_check_status' => 'not_started'
            ]);

            $this->stats['employees_created']++;
            Log::info("✅ Created employee: {$employee->name} ({$employee->employee_id})");
        } else {
            // Update existing employee if needed
            $updated = false;
            if ($employee->name !== $rowData['name']) {
                $employee->name = $rowData['name'];
                $updated = true;
            }
            if ($employee->department_id !== $department->id) {
                $employee->department_id = $department->id;
                $updated = true;
            }

            if ($updated) {
                $employee->save();
                $this->stats['employees_updated']++;
                Log::info("📝 Updated employee: {$employee->name} ({$employee->employee_id})");
            }
        }

        return $employee;
    }

    protected function createEmployeeCertificate(Employee $employee, array $rowData, string $sheetName): void
    {
        if (empty($rowData['certificate_number'])) {
            return;
        }

        // Check if certificate already exists
        $existing = EmployeeCertificate::where('certificate_number', $rowData['certificate_number'])->first();
        if ($existing) {
            Log::info("⚠️ Certificate already exists: {$rowData['certificate_number']}");
            return;
        }

        $certificateType = $this->findOrCreateCertificateType($rowData['rating'] ?? '', $sheetName);

        $status = $this->determineStatus($rowData['expiry_date']);

        $certificate = EmployeeCertificate::create([
            'employee_id' => $employee->id,
            'certificate_type_id' => $certificateType->id,
            'certificate_number' => $rowData['certificate_number'],
            'issuer' => 'GLC (Gapura Learning Center)',
            'issue_date' => $rowData['issue_date'] ?? now(),
            'expiry_date' => $rowData['expiry_date'],
            'completion_date' => $rowData['issue_date'] ?? now(),
            'training_date' => $rowData['training_date'],
            'status' => $status,
            'compliance_status' => $status === 'active' ? 'compliant' : ($status === 'expired' ? 'expired' : 'expiring_soon'),
            'notes' => "Imported from MPGA {$sheetName} sheet"
        ]);

        $this->stats['certificates_created']++;
        Log::info("🏆 Created certificate: {$certificate->certificate_number} for {$employee->name}");
    }

    protected function findOrCreateDepartment(string $sheetName): Department
    {
        $categoryName = $this->departmentMapping[$sheetName] ?? $sheetName;
        $displayName = $this->formatDepartmentName($sheetName);
        $departmentCode = $this->generateDepartmentCode($sheetName);

        $department = Department::where('code', $departmentCode)->first();

        if (!$department) {
            $department = Department::create([
                'name' => $displayName,
                'code' => $departmentCode,
                'description' => "Department for {$displayName} operations",
                'is_active' => true
            ]);

            $this->stats['departments_created']++;
            Log::info("🏢 Created department: {$department->name} ({$department->code})");
        }

        return $department;
    }

    protected function findOrCreateCertificateType(string $rating, string $sheetName): CertificateType
    {
        $rating = strtoupper(trim($rating));

        // Map rating to certificate type
        $mapping = $this->certificateTypeMapping[$rating] ?? null;

        if (!$mapping) {
            // Create generic mapping based on sheet and rating
            $mapping = [
                'name' => $rating ? "{$rating} Certificate" : "General {$sheetName} Certificate",
                'category' => $this->departmentMapping[$sheetName] ?? 'GENERAL'
            ];
        }

        $certificateType = CertificateType::where('code', $rating ?: 'GENERAL')->first();

        if (!$certificateType) {
            $certificateType = CertificateType::create([
                'name' => $mapping['name'],
                'code' => $rating ?: 'GENERAL',
                'category' => $mapping['category'],
                'validity_months' => 24, // Default 2 years
                'description' => "Certificate type imported from MPGA {$sheetName} sheet",
                'is_active' => true
            ]);

            $this->stats['certificate_types_created']++;
            Log::info("📜 Created certificate type: {$certificateType->name} ({$certificateType->code})");
        }

        return $certificateType;
    }

    // Helper methods

    protected function parseDate($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                // Excel date serial number
                return Carbon::createFromFormat('Y-m-d', Date::excelToDateTimeObject($value)->format('Y-m-d'));
            }

            if ($value instanceof \DateTime) {
                return Carbon::parse($value);
            }

            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::warning("Could not parse date: {$value}");
            return null;
        }
    }

    protected function determineStatus(?Carbon $expiryDate): string
    {
        if (!$expiryDate) {
            return 'active';
        }

        $now = Carbon::now();
        $warningDate = $expiryDate->copy()->subDays(30);

        if ($expiryDate->isPast()) {
            return 'expired';
        } elseif ($now->gte($warningDate)) {
            return 'expiring_soon';
        }

        return 'active';
    }

    protected function cleanNipp($nipp): ?string
    {
        if (empty($nipp)) {
            return null;
        }

        return (string) intval($nipp); // Remove any formatting, keep only numbers
    }

    protected function cleanName(?string $name): ?string
    {
        return $name ? trim(strtoupper($name)) : null;
    }

    protected function cleanCertificateNumber(?string $certificateNumber): ?string
    {
        return $certificateNumber ? trim($certificateNumber) : null;
    }

    protected function formatDepartmentName(string $sheetName): string
    {
        return match($sheetName) {
            'GSE OPERATOR ' => 'Ground Support Equipment',
            'LOST & FOUND' => 'Lost & Found',
            'AVSEC' => 'Aviation Security',
            default => ucwords(strtolower(str_replace('_', ' ', $sheetName)))
        };
    }

    protected function generateDepartmentCode(string $sheetName): string
    {
        return match($sheetName) {
            'DEDICATED' => 'PAX',
            'LOADING' => 'LOD',
            'RAMP' => 'RAM',
            'LOCO' => 'LOC',
            'ULD' => 'ULD',
            'LOST & FOUND' => 'LST',
            'CARGO' => 'CAR',
            'ARRIVAL' => 'ARR',
            'GSE OPERATOR ' => 'GSE',
            'FLOP' => 'FLP',
            'AVSEC' => 'SEC',
            'PORTER' => 'POR',
            default => substr($sheetName, 0, 3)
        };
    }

    protected function inferPositionFromRating(string $rating): string
    {
        return match(strtoupper($rating)) {
            'ATT' => 'Aircraft Towing Tractor Operator',
            'FRM' => 'Fork Lift Operator',
            'LLD' => 'Low Loader Operator',
            'BTT' => 'Baggage Towing Tractor Operator',
            'BCS' => 'Belt Conveyor System Operator',
            'PBS' => 'Pushback System Operator',
            default => 'Ground Operations Staff'
        };
    }
}
