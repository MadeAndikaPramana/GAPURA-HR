<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MPGARealisticSeeder extends Seeder
{
    /**
     * Run the MPGA realistic training record seeder
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting MPGA Realistic Training Record Seeding...');

        DB::beginTransaction();

        try {
            // 1. Create MPGA Departments (based on actual Excel data)
            $departments = $this->createMPGADepartments();

            // 2. Create MPGA Training Types (based on actual training programs)
            $trainingTypes = $this->createMPGATrainingTypes();

            // 3. Create Admin Users
            $this->createAdminUsers();

            // 4. Create MPGA Employees (based on actual employee data patterns)
            $employees = $this->createMPGAEmployees($departments);

            // 5. Create Training Records (based on actual certificate patterns)
            $this->createMPGATrainingRecords($employees, $trainingTypes);

            DB::commit();

            $this->command->info('✅ MPGA Realistic Seeding Completed Successfully!');
            $this->printSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function createMPGADepartments()
    {
        $this->command->info('📁 Creating MPGA Departments...');

        $mpgaDepartments = [
            [
                'name' => 'Dedicated Services',
                'code' => 'DEDICATED',
                'description' => 'Passenger handling and baggage services'
            ],
            [
                'name' => 'Loading Operations',
                'code' => 'LOADING',
                'description' => 'Aircraft loading and unloading operations'
            ],
            [
                'name' => 'Ramp Operations',
                'code' => 'RAMP',
                'description' => 'Aircraft ramp handling services'
            ],
            [
                'name' => 'Locomotive Operations',
                'code' => 'LOCO',
                'description' => 'Ground vehicle operations'
            ],
            [
                'name' => 'ULD Operations',
                'code' => 'ULD',
                'description' => 'Unit Load Device handling'
            ],
            [
                'name' => 'Lost & Found',
                'code' => 'LNF',
                'description' => 'Lost and found services'
            ],
            [
                'name' => 'Cargo Operations',
                'code' => 'CARGO',
                'description' => 'Cargo handling and operations'
            ],
            [
                'name' => 'Arrival Services',
                'code' => 'ARRIVAL',
                'description' => 'Passenger arrival services'
            ],
            [
                'name' => 'GSE Operations',
                'code' => 'GSE',
                'description' => 'Ground Support Equipment operations'
            ],
            [
                'name' => 'Flight Operations',
                'code' => 'FLOP',
                'description' => 'Flight operations support'
            ],
            [
                'name' => 'Aviation Security',
                'code' => 'AVSEC',
                'description' => 'Aviation security services'
            ],
            [
                'name' => 'Porter Services',
                'code' => 'PORTER',
                'description' => 'Porter and baggage services'
            ]
        ];

        $departments = [];
        foreach ($mpgaDepartments as $deptData) {
            $department = Department::where('code', $deptData['code'])->first();
            if (!$department) {
                $department = Department::create($deptData);
            }
            $departments[] = $department;
        }

        $this->command->info('✅ Created ' . count($departments) . ' MPGA departments');
        return $departments;
    }

    private function createMPGATrainingTypes()
    {
        $this->command->info('🎓 Creating MPGA Training Types...');

        $mpgaTrainingTypes = [
            [
                'name' => 'PAX & Baggage Handling',
                'code' => 'PAX_BAG',
                'category' => 'operations',
                'validity_months' => 36,
                'description' => 'Passenger and baggage handling training',
                'is_active' => true
            ],
            [
                'name' => 'Safety Training (SMS)',
                'code' => 'SMS',
                'category' => 'safety',
                'validity_months' => 36,
                'description' => 'Safety Management System training',
                'is_active' => true
            ],
            [
                'name' => 'Human Factor',
                'code' => 'HF',
                'category' => 'safety',
                'validity_months' => 36,
                'description' => 'Human factors and error prevention',
                'is_active' => true
            ],
            [
                'name' => 'Dangerous Goods Awareness',
                'code' => 'DGA',
                'category' => 'dangerous_goods',
                'validity_months' => 24,
                'description' => 'Dangerous goods awareness training',
                'is_active' => true
            ],
            [
                'name' => 'Aviation Security Awareness',
                'code' => 'AVSEC_AWR',
                'category' => 'security',
                'validity_months' => 12,
                'description' => 'Aviation security awareness training',
                'is_active' => true
            ],
            [
                'name' => 'Porter Training',
                'code' => 'PORTER',
                'category' => 'operations',
                'validity_months' => 36,
                'description' => 'Porter specific operational training',
                'is_active' => true
            ],
            [
                'name' => 'GSE Operator Training',
                'code' => 'GSE_OPR',
                'category' => 'equipment',
                'validity_months' => 36,
                'description' => 'Ground Support Equipment operator training',
                'is_active' => true
            ],
            [
                'name' => 'FOO License Training',
                'code' => 'FOO',
                'category' => 'license',
                'validity_months' => 60,
                'description' => 'Flight Operations Officer license training',
                'is_active' => true
            ]
        ];

        $trainingTypes = [];
        foreach ($mpgaTrainingTypes as $typeData) {
            $trainingType = TrainingType::where('code', $typeData['code'])->first();
            if (!$trainingType) {
                $trainingType = TrainingType::create($typeData);
            }
            $trainingTypes[] = $trainingType;
        }

        $this->command->info('✅ Created ' . count($trainingTypes) . ' MPGA training types');
        return $trainingTypes;
    }

    private function createAdminUsers()
    {
        $this->command->info('👤 Creating Admin Users...');

        // Check if users already exist
        if (!User::where('email', 'admin@gapura.com')->exists()) {
            User::create([
                'name' => 'GAPURA Super Admin',
                'email' => 'admin@gapura.com',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]);
        }

        if (!User::where('email', 'hr@gapura.com')->exists()) {
            User::create([
                'name' => 'GAPURA HR Admin',
                'email' => 'hr@gapura.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info('✅ Created admin users');
    }

    private function createMPGAEmployees($departments)
    {
        $this->command->info('👨‍💼 Creating MPGA Employees...');

        $mpgaEmployees = [
            // DEDICATED Department
            ['name' => 'PUTU EKA RESMAWAN', 'nip' => '21608001', 'dept' => 'DEDICATED', 'position' => 'AE Operator'],
            ['name' => 'PUTU ERNAWATI', 'nip' => '29809612', 'dept' => 'DEDICATED', 'position' => 'Controller'],
            ['name' => 'KADEK MEGAYANA', 'nip' => '21607983', 'dept' => 'DEDICATED', 'position' => 'Controller'],

            // PORTER Department
            ['name' => 'I MADE PARTANA', 'nip' => '21020059', 'dept' => 'PORTER', 'position' => 'Porter'],
            ['name' => 'ANDI SOPIAN HARDI', 'nip' => '22070085', 'dept' => 'PORTER', 'position' => 'Porter'],
            ['name' => 'NURJAMAN', 'nip' => '22070090', 'dept' => 'PORTER', 'position' => 'Porter'],
            ['name' => 'DIANUR ROHMAN', 'nip' => '22070092', 'dept' => 'PORTER', 'position' => 'Porter'],
            ['name' => 'TATTO KHUZA PENAKA', 'nip' => '22080095', 'dept' => 'PORTER', 'position' => 'Porter'],

            // RAMP Department
            ['name' => 'I WAYAN SUTRISNA', 'nip' => '21050067', 'dept' => 'RAMP', 'position' => 'Ramp Agent'],
            ['name' => 'I MADE SUARTIKA', 'nip' => '21050068', 'dept' => 'RAMP', 'position' => 'Ramp Agent'],
            ['name' => 'KETUT SUPARTA', 'nip' => '21050069', 'dept' => 'RAMP', 'position' => 'Senior Ramp Agent'],

            // CARGO Department
            ['name' => 'I GEDE WIRADANA', 'nip' => '21040045', 'dept' => 'CARGO', 'position' => 'Cargo Handler'],
            ['name' => 'NI MADE SUSILAWATI', 'nip' => '21040046', 'dept' => 'CARGO', 'position' => 'Cargo Supervisor'],

            // GSE Department
            ['name' => 'I WAYAN KARIASA', 'nip' => '21030023', 'dept' => 'GSE', 'position' => 'GSE Operator'],
            ['name' => 'MADE WIRAWAN', 'nip' => '21030024', 'dept' => 'GSE', 'position' => 'GSE Mechanic'],

            // AVSEC Department
            ['name' => 'I KETUT SUWITRA', 'nip' => '21060078', 'dept' => 'AVSEC', 'position' => 'Security Officer'],
            ['name' => 'NI PUTU MARLINA', 'nip' => '21060079', 'dept' => 'AVSEC', 'position' => 'Security Supervisor'],

            // LOADING Department
            ['name' => 'I MADE SUDANA', 'nip' => '21070089', 'dept' => 'LOADING', 'position' => 'Loading Supervisor'],
            ['name' => 'KADEK SUMERTA', 'nip' => '21070090', 'dept' => 'LOADING', 'position' => 'Loading Agent'],

            // Additional employees
            ['name' => 'NI WAYAN SURIANI', 'nip' => '21080098', 'dept' => 'ARRIVAL', 'position' => 'Arrival Agent'],
            ['name' => 'I GEDE MAHENDRA', 'nip' => '21090105', 'dept' => 'LNF', 'position' => 'Lost & Found Officer'],
        ];

        $employees = [];
        $employeeIdCounter = Employee::count() + 1;

        foreach ($mpgaEmployees as $empData) {
            $department = collect($departments)->firstWhere('code', $empData['dept']);

            if (!$department) {
                $this->command->warn("Department not found: " . $empData['dept']);
                continue;
            }

            // Check if employee already exists by NIP
            $existingEmployee = Employee::where('nip', $empData['nip'])->first();
            if ($existingEmployee) {
                $employees[] = $existingEmployee;
                continue;
            }

            $employee = Employee::create([
                'employee_id' => 'GAP' . str_pad($employeeIdCounter, 4, '0', STR_PAD_LEFT),
                'name' => $empData['name'],
                'nip' => $empData['nip'],
                'department_id' => $department->id,
                'position' => $empData['position'],
                'status' => 'active',
                'hire_date' => Carbon::now()->subMonths(rand(6, 60)),
                'background_check_date' => Carbon::now()->subMonths(rand(1, 12)),
                'background_check_status' => 'cleared',
                'background_check_notes' => 'Background check completed successfully',
                'email' => strtolower(str_replace(' ', '.', $empData['name'])) . '@gapura.com',
            ]);

            $employees[] = $employee;
            $employeeIdCounter++;
        }

        $this->command->info('✅ Created ' . count($employees) . ' MPGA employees');
        return $employees;
    }

    private function createMPGATrainingRecords($employees, $trainingTypes)
    {
        $this->command->info('📜 Creating MPGA Training Records...');

        $certificateCounter = 3800; // Starting from realistic number
        $recordsCreated = 0;

        foreach ($employees as $employee) {
            // Get mandatory trainings based on department
            $mandatoryTrainings = $this->getMandatoryTrainingsForDepartment($employee->department->code, $trainingTypes);

            foreach ($mandatoryTrainings as $trainingType) {
                $certificateCounter++;

                // Generate realistic dates
                $issueDate = Carbon::now()->subMonths(rand(1, $trainingType->validity_months - 6));
                $expiryDate = $issueDate->copy()->addMonths($trainingType->validity_months);
                $completionDate = $issueDate->copy()->addDays(rand(0, 7)); // Training completed within a week of issue

                // Generate MPGA certificate number format: GLC/OPR-XXXXXX/MONTH/YEAR
                $monthName = strtoupper($issueDate->format('M'));
                $year = $issueDate->format('Y');
                $certificateNumber = "GLC/OPR-{$certificateCounter}/{$monthName}/{$year}";

                // Check if record already exists
                $existingRecord = TrainingRecord::where('employee_id', $employee->id)
                                               ->where('training_type_id', $trainingType->id)
                                               ->where('certificate_number', $certificateNumber)
                                               ->first();

                if ($existingRecord) {
                    continue;
                }

                // Determine status based on database schema (registered, in_progress, completed, cancelled)
                $daysDiff = Carbon::now()->diffInDays($expiryDate, false);
                $status = 'completed'; // All training records are completed
                $complianceStatus = $this->determineComplianceStatus($daysDiff);

                TrainingRecord::create([
                    'employee_id' => $employee->id,
                    'training_type_id' => $trainingType->id,
                    'certificate_number' => $certificateNumber,
                    'issuer' => 'GAPURA TRAINING CENTER',
                    'issue_date' => $issueDate,
                    'completion_date' => $completionDate,
                    'expiry_date' => $expiryDate,
                    'status' => $status,
                    'compliance_status' => $complianceStatus,
                    'training_hours' => $this->getTrainingHours($trainingType->code),
                    'score' => rand(80, 100), // Passing scores
                    'passing_score' => 70,
                    'location' => 'GAPURA Training Center - Ngurah Rai',
                    'instructor_name' => $this->getRandomInstructor(),
                    'notes' => 'Training completed at GAPURA Learning Center',
                ]);

                $recordsCreated++;
            }
        }

        $this->command->info('✅ Created ' . $recordsCreated . ' MPGA training records');
    }

    private function getMandatoryTrainingsForDepartment($departmentCode, $trainingTypes)
    {
        // Common mandatory trainings for all departments
        $mandatoryTrainings = ['SMS', 'HF', 'DGA', 'AVSEC_AWR'];

        // Department-specific trainings
        $departmentSpecific = [
            'DEDICATED' => ['PAX_BAG'],
            'PORTER' => ['PORTER'],
            'GSE' => ['GSE_OPR'],
            'FLOP' => ['FOO'],
            'RAMP' => ['PAX_BAG'],
            'LOADING' => ['PAX_BAG'],
            'CARGO' => ['PAX_BAG'],
            'ARRIVAL' => ['PAX_BAG'],
        ];

        // Add department-specific trainings
        if (isset($departmentSpecific[$departmentCode])) {
            $mandatoryTrainings = array_merge($mandatoryTrainings, $departmentSpecific[$departmentCode]);
        }

        // Return training type objects
        return collect($trainingTypes)->whereIn('code', $mandatoryTrainings);
    }

    private function determineComplianceStatus($daysDiff)
    {
        // Based on database schema: compliant, expiring_soon, expired, not_required
        if ($daysDiff < 0) {
            return 'expired';
        } elseif ($daysDiff <= 30) {
            return 'expiring_soon';
        } else {
            return 'compliant';
        }
    }

    private function getTrainingHours($trainingCode)
    {
        $trainingHours = [
            'PAX_BAG' => 16,
            'SMS' => 8,
            'HF' => 8,
            'DGA' => 4,
            'AVSEC_AWR' => 4,
            'PORTER' => 16,
            'GSE_OPR' => 24,
            'FOO' => 120,
        ];

        return $trainingHours[$trainingCode] ?? 8;
    }

    private function getRandomInstructor()
    {
        $instructors = [
            'Capt. I Made Sutarya',
            'Ibu Ni Luh Kompiang',
            'Pak Wayan Sudiarta',
            'Ibu Putu Sari Dewi',
            'Capt. Ketut Wirawan',
            'Pak I Gede Suparta'
        ];

        return $instructors[array_rand($instructors)];
    }

    private function printSummary()
    {
        $this->command->info('');
        $this->command->info('📊 MPGA Training System Summary:');
        $this->command->info('═══════════════════════════════');
        $this->command->info('🏢 Departments: ' . Department::count());
        $this->command->info('👨‍💼 Employees: ' . Employee::count());
        $this->command->info('🎓 Training Types: ' . TrainingType::count());
        $this->command->info('📜 Training Records: ' . TrainingRecord::count());
        $this->command->info('👤 Admin Users: ' . User::count());
        $this->command->info('');
        $this->command->info('🔐 Login Credentials:');
        $this->command->info('   Super Admin: admin@gapura.com / password');
        $this->command->info('   HR Admin: hr@gapura.com / password');
        $this->command->info('');
        $this->command->info('🎯 Certificate Number Format: GLC/OPR-XXXXXX/MONTH/YEAR');
        $this->command->info('📅 Training Validity Periods: 12-60 months');
        $this->command->info('✅ Sample Data from MPGA Excel Successfully Loaded!');
    }
}
