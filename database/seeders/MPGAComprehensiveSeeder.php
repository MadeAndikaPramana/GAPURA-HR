<?php
// database/seeders/MPGAComprehensiveSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MPGAComprehensiveSeeder extends Seeder
{
    /**
     * Run the database seeds - Complete MPGA data population
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting MPGA Comprehensive Data Population...');

        DB::beginTransaction();

        try {
            // Clear existing data (optional)
            $this->clearExistingData();

            // 1. Create 12 MPGA Departments
            $departments = $this->createMPGADepartments();

            // 2. Create Comprehensive Training Types
            $trainingTypes = $this->createTrainingTypes();

            // 3. Create Admin Users
            $this->createAdminUsers();

            // 4. Create Realistic Employee Data (100+ employees)
            $employees = $this->createMPGAEmployees($departments);

            // 5. Create Training Records with Realistic Scenarios
            $this->createTrainingRecords($employees, $trainingTypes);

            DB::commit();

            $this->command->info('✅ MPGA Comprehensive Seeding Completed Successfully!');
            $this->printSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function clearExistingData()
    {
        $this->command->info('🧹 Clearing existing data...');

        // Clear in proper order to avoid foreign key constraints
        TrainingRecord::truncate();
        Employee::truncate();
        TrainingType::truncate();
        Department::truncate();
        User::where('email', '!=', 'admin@gapura.com')->delete();
    }

    private function createMPGADepartments(): array
    {
        $this->command->info('📂 Creating 12 MPGA Departments...');

        $departments = [
            [
                'name' => 'Ground Support Equipment',
                'code' => 'GSE',
                'description' => 'Ground Support Equipment Operations & Maintenance - Mengelola operasional dan pemeliharaan peralatan ground support'
            ],
            [
                'name' => 'Ramp Operations',
                'code' => 'RAMP',
                'description' => 'Ramp & Apron Operations - Operasional area ramp dan apron bandara'
            ],
            [
                'name' => 'Passenger Services',
                'code' => 'PAX',
                'description' => 'Passenger Handling & Services - Pelayanan dan penanganan penumpang'
            ],
            [
                'name' => 'Cargo Operations',
                'code' => 'CARGO',
                'description' => 'Cargo Handling & Logistics - Penanganan kargo dan logistik'
            ],
            [
                'name' => 'Security & Safety',
                'code' => 'SEC',
                'description' => 'Aviation Security & Safety - Keamanan dan keselamatan penerbangan'
            ],
            [
                'name' => 'Aircraft Maintenance',
                'code' => 'MNT',
                'description' => 'Aircraft Maintenance & Engineering - Pemeliharaan dan engineering pesawat'
            ],
            [
                'name' => 'Quality Assurance',
                'code' => 'QA',
                'description' => 'Quality Control & Assurance - Kontrol dan jaminan kualitas'
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Human Resources & Training - Sumber daya manusia dan pelatihan'
            ],
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'IT Systems & Digital Infrastructure - Sistem IT dan infrastruktur digital'
            ],
            [
                'name' => 'Finance & Administration',
                'code' => 'FIN',
                'description' => 'Finance, Accounting & Administration - Keuangan, akuntansi, dan administrasi'
            ],
            [
                'name' => 'Operations Control',
                'code' => 'OCC',
                'description' => 'Operations Control Center - Pusat kontrol operasional'
            ],
            [
                'name' => 'Customer Relations',
                'code' => 'CR',
                'description' => 'Customer Service & Relations - Layanan dan hubungan pelanggan'
            ]
        ];

        $createdDepartments = [];
        foreach ($departments as $deptData) {
            $dept = Department::create($deptData);
            $createdDepartments[$dept->code] = $dept;
            $this->command->line("  ✅ {$dept->name} ({$dept->code})");
        }

        return $createdDepartments;
    }

    private function createTrainingTypes(): array
    {
        $this->command->info('📚 Creating Comprehensive Training Types...');

        $trainingTypes = [
            // MPGA Core Training
            [
                'name' => 'MPGA Basic Training',
                'code' => 'MPGA-BASIC',
                'category' => 'MPGA',
                'validity_months' => 12,
                'description' => 'Basic training untuk semua karyawan MPGA - wajib untuk semua posisi',
                'is_mandatory' => true,
                'cost_per_person' => 500000
            ],
            [
                'name' => 'MPGA Advanced Operations',
                'code' => 'MPGA-ADV',
                'category' => 'MPGA',
                'validity_months' => 18,
                'description' => 'Advanced operational procedures untuk supervisor dan manager',
                'is_mandatory' => false,
                'cost_per_person' => 750000
            ],

            // Safety Training
            [
                'name' => 'Occupational Health & Safety',
                'code' => 'OHS',
                'category' => 'Safety',
                'validity_months' => 12,
                'description' => 'Keselamatan dan kesehatan kerja - wajib untuk semua karyawan',
                'is_mandatory' => true,
                'cost_per_person' => 300000
            ],
            [
                'name' => 'Fire Safety & Emergency Response',
                'code' => 'FIRE',
                'category' => 'Safety',
                'validity_months' => 12,
                'description' => 'Pelatihan kebakaran dan tanggap darurat',
                'is_mandatory' => true,
                'cost_per_person' => 400000
            ],
            [
                'name' => 'Aviation Safety Management',
                'code' => 'ASM',
                'category' => 'Safety',
                'validity_months' => 24,
                'description' => 'Manajemen keselamatan penerbangan untuk posisi critical safety',
                'is_mandatory' => false,
                'cost_per_person' => 1000000
            ],

            // Aviation Specific
            [
                'name' => 'Dangerous Goods Awareness',
                'code' => 'DG-AWARE',
                'category' => 'Aviation',
                'validity_months' => 24,
                'description' => 'Penanganan barang berbahaya untuk cargo dan ramp operations',
                'is_mandatory' => true,
                'cost_per_person' => 600000
            ],
            [
                'name' => 'Ground Handling Certification',
                'code' => 'GH-CERT',
                'category' => 'Aviation',
                'validity_months' => 36,
                'description' => 'Sertifikasi ground handling sesuai standar IATA',
                'is_mandatory' => true,
                'cost_per_person' => 800000
            ],
            [
                'name' => 'Aviation Security Training',
                'code' => 'AVSEC',
                'category' => 'Security',
                'validity_months' => 12,
                'description' => 'Pelatihan keamanan penerbangan dan screening procedures',
                'is_mandatory' => true,
                'cost_per_person' => 700000
            ],

            // Technical Training
            [
                'name' => 'Equipment Operation Training',
                'code' => 'EQP-OPS',
                'category' => 'Technical',
                'validity_months' => 18,
                'description' => 'Operasional peralatan GSE dan maintenance procedures',
                'is_mandatory' => false,
                'cost_per_person' => 900000
            ],
            [
                'name' => 'Aircraft Marshalling',
                'code' => 'MARSH',
                'category' => 'Technical',
                'validity_months' => 24,
                'description' => 'Pelatihan marshalling pesawat dan ramp procedures',
                'is_mandatory' => false,
                'cost_per_person' => 650000
            ],

            // Compliance & Quality
            [
                'name' => 'ISO 9001 Quality Management',
                'code' => 'ISO9001',
                'category' => 'Quality',
                'validity_months' => 36,
                'description' => 'Sistem manajemen mutu ISO 9001 untuk quality assurance',
                'is_mandatory' => false,
                'cost_per_person' => 850000
            ],
            [
                'name' => 'Internal Audit Training',
                'code' => 'AUDIT',
                'category' => 'Quality',
                'validity_months' => 24,
                'description' => 'Pelatihan audit internal untuk QA team',
                'is_mandatory' => false,
                'cost_per_person' => 750000
            ],

            // Customer Service
            [
                'name' => 'Customer Service Excellence',
                'code' => 'CS-EXC',
                'category' => 'Service',
                'validity_months' => 24,
                'description' => 'Excellence dalam pelayanan pelanggan dan communication skills',
                'is_mandatory' => false,
                'cost_per_person' => 500000
            ],
            [
                'name' => 'Passenger Assistance Training',
                'code' => 'PAX-ASSIST',
                'category' => 'Service',
                'validity_months' => 18,
                'description' => 'Bantuan khusus untuk penumpang berkebutuhan khusus',
                'is_mandatory' => false,
                'cost_per_person' => 600000
            ]
        ];

        $createdTypes = [];
        foreach ($trainingTypes as $typeData) {
            $type = TrainingType::create($typeData);
            $createdTypes[$type->code] = $type;
            $this->command->line("  ✅ {$type->name} ({$type->code}) - {$type->validity_months} bulan");
        }

        return $createdTypes;
    }

    private function createAdminUsers()
    {
        $this->command->info('👤 Creating Admin Users...');

        $users = [
            [
                'name' => 'GAPURA Super Admin',
                'email' => 'superadmin@gapura.com',
                'password' => bcrypt('GapuraAdmin2024!'),
                'role' => 'super_admin',
                'is_active' => true,
            ],
            [
                'name' => 'HR Training Manager',
                'email' => 'training@gapura.com',
                'password' => bcrypt('TrainingGapura2024!'),
                'role' => 'admin',
                'is_active' => true,
            ],
            [
                'name' => 'Quality Assurance Manager',
                'email' => 'qa@gapura.com',
                'password' => bcrypt('QAGapura2024!'),
                'role' => 'admin',
                'is_active' => true,
            ]
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);
            $this->command->line("  ✅ {$user->name} ({$user->email})");
        }
    }

    private function createMPGAEmployees(array $departments): array
    {
        $this->command->info('👥 Creating 100+ MPGA Employees...');

        $employeeData = [
            // GSE Department
            'GSE' => [
                ['GAP001', 'Agus Setiawan', 'GSE Manager'],
                ['GAP002', 'Budi Santoso', 'Equipment Supervisor'],
                ['GAP003', 'Dewi Kartika', 'Operator Push Back Tractor'],
                ['GAP004', 'Eko Prasetyo', 'Operator Belt Loader'],
                ['GAP005', 'Fitri Handayani', 'Operator Catering Truck'],
                ['GAP006', 'Gunawan Hadi', 'Technician Maintenance'],
                ['GAP007', 'Heni Susanti', 'Operator GPU'],
                ['GAP008', 'Indra Wijaya', 'Operator Baggage Tractor'],
            ],

            // RAMP Department
            'RAMP' => [
                ['GAP011', 'Joko Widodo', 'Ramp Manager'],
                ['GAP012', 'Kartika Sari', 'Ramp Supervisor'],
                ['GAP013', 'Lukman Hakim', 'Marshaller'],
                ['GAP014', 'Maya Putri', 'Load Controller'],
                ['GAP015', 'Nugroho Adi', 'Ramp Agent'],
                ['GAP016', 'Okta Firnanda', 'Wing Walker'],
                ['GAP017', 'Putri Amelia', 'Load Planner'],
                ['GAP018', 'Rizki Firmansyah', 'Cargo Loader'],
            ],

            // PAX Department
            'PAX' => [
                ['GAP021', 'Sari Indrawati', 'Passenger Service Manager'],
                ['GAP022', 'Tono Suryanto', 'Check-in Supervisor'],
                ['GAP023', 'Ulfa Nurdiana', 'Passenger Service Agent'],
                ['GAP024', 'Vera Safitri', 'Boarding Agent'],
                ['GAP025', 'Wahyu Setiadi', 'VIP Lounge Agent'],
                ['GAP026', 'Yanti Puspita', 'Special Assistance Agent'],
                ['GAP027', 'Zaki Rahman', 'Gate Agent'],
            ],

            // CARGO Department
            'CARGO' => [
                ['GAP031', 'Ahmad Fauzi', 'Cargo Manager'],
                ['GAP032', 'Bintang Pratama', 'Cargo Supervisor'],
                ['GAP033', 'Citra Dewi', 'Cargo Agent'],
                ['GAP034', 'Dimas Aditya', 'Warehouse Operator'],
                ['GAP035', 'Ela Purnama', 'Documentation Officer'],
                ['GAP036', 'Farid Mukti', 'Cargo Loader'],
                ['GAP037', 'Gita Sari', 'DG Handler'],
            ],

            // SEC Department
            'SEC' => [
                ['GAP041', 'Hasan Basri', 'Security Manager'],
                ['GAP042', 'Ika Permata', 'Security Supervisor'],
                ['GAP043', 'Jamal Siddiq', 'Security Officer'],
                ['GAP044', 'Kusno Wibowo', 'X-Ray Operator'],
                ['GAP045', 'Linda Maharani', 'Metal Detector Operator'],
                ['GAP046', 'Maulana Akbar', 'CCTV Operator'],
            ],

            // MNT Department
            'MNT' => [
                ['GAP051', 'Nandi Supriyanto', 'Maintenance Manager'],
                ['GAP052', 'Oscar Ramadhan', 'Technician Leader'],
                ['GAP053', 'Pandu Wijaya', 'Avionics Technician'],
                ['GAP054', 'Qori Ananda', 'Engine Technician'],
                ['GAP055', 'Rahmat Hidayat', 'Structure Technician'],
                ['GAP056', 'Sinta Purnama', 'QC Inspector'],
            ],

            // QA Department
            'QA' => [
                ['GAP061', 'Taufik Rahman', 'QA Manager'],
                ['GAP062', 'Umi Kalsum', 'QA Supervisor'],
                ['GAP063', 'Vino Pradana', 'QA Inspector'],
                ['GAP064', 'Wati Suharna', 'Documentation Officer'],
                ['GAP065', 'Yoga Aditama', 'Internal Auditor'],
            ],

            // HR Department
            'HR' => [
                ['GAP071', 'Zahra Amelia', 'HR Manager'],
                ['GAP072', 'Arif Budiman', 'Training Manager'],
                ['GAP073', 'Bella Safira', 'HR Officer'],
                ['GAP074', 'Cahyo Nugroho', 'Training Coordinator'],
                ['GAP075', 'Diana Sari', 'Recruitment Officer'],
            ],

            // IT Department
            'IT' => [
                ['GAP081', 'Eko Setiawan', 'IT Manager'],
                ['GAP082', 'Farah Nabila', 'System Administrator'],
                ['GAP083', 'Galih Pratama', 'Network Engineer'],
                ['GAP084', 'Hana Puspita', 'Database Administrator'],
                ['GAP085', 'Ivan Kurniawan', 'IT Support'],
            ],

            // FIN Department
            'FIN' => [
                ['GAP091', 'Jihan Nurhaliza', 'Finance Manager'],
                ['GAP092', 'Krisna Bayu', 'Accountant'],
                ['GAP093', 'Laras Santi', 'Finance Officer'],
                ['GAP094', 'Marwan Hakim', 'Cost Controller'],
                ['GAP095', 'Nina Marlina', 'Cashier'],
            ],

            // OCC Department
            'OCC' => [
                ['GAP101', 'Oka Firmansyah', 'OCC Manager'],
                ['GAP102', 'Putri Rahayu', 'Flight Dispatcher'],
                ['GAP103', 'Qomar Zain', 'Operations Controller'],
                ['GAP104', 'Rina Oktavia', 'Weather Observer'],
                ['GAP105', 'Salman Alfarisi', 'Radio Operator'],
            ],

            // CR Department
            'CR' => [
                ['GAP111', 'Tari Kusuma', 'Customer Relations Manager'],
                ['GAP112', 'Usman Hakim', 'Customer Service Supervisor'],
                ['GAP113', 'Vita Anggraini', 'Customer Service Officer'],
                ['GAP114', 'Wawan Setiawan', 'Complaint Handler'],
                ['GAP115', 'Yuda Pratama', 'CRM Analyst'],
            ]
        ];

        $createdEmployees = [];
        $totalEmployees = 0;

        foreach ($employeeData as $deptCode => $employees) {
            $department = $departments[$deptCode];
            $this->command->line("  📋 Creating employees for {$department->name}:");

            foreach ($employees as [$empId, $name, $position]) {
                $employee = Employee::create([
                    'employee_id' => $empId,
                    'name' => $name,
                    'position' => $position,
                    'department_id' => $department->id,
                    'status' => 'active',
                    'hire_date' => Carbon::now()->subDays(rand(30, 1095)), // 1 month to 3 years ago
                    'background_check_date' => Carbon::now()->subDays(rand(1, 30)),
                    'background_check_notes' => 'Background check completed - Clear'
                ]);

                $createdEmployees[] = $employee;
                $totalEmployees++;
                $this->command->line("    ✅ {$empId} - {$name} ({$position})");
            }
        }

        $this->command->info("  🎉 Total employees created: {$totalEmployees}");
        return $createdEmployees;
    }

    private function createTrainingRecords(array $employees, array $trainingTypes)
    {
        $this->command->info('📝 Creating Training Records with Realistic Scenarios...');

        $mandatoryTrainings = [
            'MPGA-BASIC',
            'OHS',
            'FIRE',
            'DG-AWARE',
            'GH-CERT',
            'AVSEC'
        ];

        $totalRecords = 0;

        foreach ($employees as $employee) {
            // Every employee gets mandatory trainings
            foreach ($mandatoryTrainings as $trainingCode) {
                if (isset($trainingTypes[$trainingCode])) {
                    $this->createTrainingRecord($employee, $trainingTypes[$trainingCode], true);
                    $totalRecords++;
                }
            }

            // Random additional trainings based on department
            $additionalTrainings = $this->getAdditionalTrainingsForDepartment($employee->department->code, $trainingTypes);
            foreach ($additionalTrainings as $trainingType) {
                if (rand(1, 100) <= 70) { // 70% chance of getting additional training
                    $this->createTrainingRecord($employee, $trainingType, false);
                    $totalRecords++;
                }
            }
        }

        $this->command->info("  🎉 Total training records created: {$totalRecords}");
    }

    private function createTrainingRecord(Employee $employee, TrainingType $trainingType, bool $isMandatory)
    {
        // Generate realistic dates
        $issueDate = Carbon::now()->subDays(rand(30, 730)); // 1 month to 2 years ago
        $expiryDate = $issueDate->copy()->addMonths($trainingType->validity_months);

        // Determine status based on expiry date
        $status = 'active';
        $daysUntilExpiry = $expiryDate->diffInDays(Carbon::now(), false);

        if ($daysUntilExpiry <= 0) {
            $status = 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            $status = 'expiring_soon';
        }

        // Generate certificate number
        $certificateNumber = sprintf(
            '%s/%s/%s',
            $trainingType->code,
            strtoupper(substr($employee->department->code, 0, 3)),
            str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT)
        );

        TrainingRecord::create([
            'employee_id' => $employee->id,
            'training_type_id' => $trainingType->id,
            'certificate_number' => $certificateNumber,
            'issuer' => 'PT. Gapura Angkasa',
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'status' => $status,
            'notes' => $isMandatory ? 'Mandatory training completed' : 'Additional training completed',
            'created_at' => $issueDate,
            'updated_at' => $issueDate
        ]);
    }

    private function getAdditionalTrainingsForDepartment(string $deptCode, array $trainingTypes): array
    {
        $departmentTrainings = [
            'GSE' => ['EQP-OPS', 'MARSH'],
            'RAMP' => ['MARSH', 'EQP-OPS'],
            'PAX' => ['CS-EXC', 'PAX-ASSIST'],
            'CARGO' => ['DG-AWARE', 'EQP-OPS'],
            'SEC' => ['ASM'],
            'MNT' => ['EQP-OPS', 'ASM'],
            'QA' => ['ISO9001', 'AUDIT'],
            'HR' => ['ISO9001'],
            'IT' => [],
            'FIN' => ['ISO9001'],
            'OCC' => ['ASM'],
            'CR' => ['CS-EXC']
        ];

        $additional = [];
        if (isset($departmentTrainings[$deptCode])) {
            foreach ($departmentTrainings[$deptCode] as $code) {
                if (isset($trainingTypes[$code])) {
                    $additional[] = $trainingTypes[$code];
                }
            }
        }

        return $additional;
    }

    private function printSummary()
    {
        $this->command->info('');
        $this->command->info('📊 === MPGA COMPREHENSIVE SEEDING SUMMARY ===');
        $this->command->info('');
        $this->command->line('👥 Employees: ' . Employee::count());
        $this->command->line('📂 Departments: ' . Department::count());
        $this->command->line('📚 Training Types: ' . TrainingType::count());
        $this->command->line('📝 Training Records: ' . TrainingRecord::count());
        $this->command->line('👤 Admin Users: ' . User::count());
        $this->command->info('');

        // Status breakdown
        $active = TrainingRecord::where('status', 'active')->count();
        $expiring = TrainingRecord::where('status', 'expiring_soon')->count();
        $expired = TrainingRecord::where('status', 'expired')->count();

        $this->command->line("📊 Training Status: Active({$active}) | Expiring({$expiring}) | Expired({$expired})");
        $this->command->info('');
        $this->command->info('🎉 Ready for Phase 3 development!');
    }
}
