<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use App\Models\TrainingType;
use App\Models\Employee;
use App\Models\TrainingRecord;
use App\Models\TrainingProvider;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Gapura Training System Database Seeding...');

        // Create departments first
        $this->createDepartments();

        // Create training types
        $this->createTrainingTypes();

        // Create admin user
        $this->createAdminUser();

        // Create sample employees
        $this->createSampleEmployees();

        // Create training providers
        $this->createTrainingProviders();

        // Create sample training records
        $this->createSampleTrainingRecords();

        $this->command->info('✅ Database seeding completed successfully!');
    }

    private function createDepartments()
    {
        $this->command->info('📂 Creating departments...');

        $departments = [
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Human Resources & Training Department - Manages employee relations, training, and development'
            ],
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'IT Systems & Digital Infrastructure - Manages technology infrastructure and digital systems'
            ],
            [
                'name' => 'Flight Operations',
                'code' => 'OPS',
                'description' => 'Flight Operations & Ground Handling - Oversees flight operations and ground service activities'
            ],
            [
                'name' => 'Ground Support Equipment',
                'code' => 'GSE',
                'description' => 'Ground Support & Equipment Maintenance - Maintains and operates ground support equipment'
            ],
            [
                'name' => 'Security Services',
                'code' => 'SEC',
                'description' => 'Airport Security & Safety - Ensures airport security and safety compliance'
            ],
            [
                'name' => 'Customer Relations',
                'code' => 'CR',
                'description' => 'Customer Service & Relations - Manages customer experience and service quality'
            ],
            [
                'name' => 'Finance & Accounting',
                'code' => 'FIN',
                'description' => 'Financial Management & Accounting - Handles financial operations and reporting'
            ],
            [
                'name' => 'Maintenance',
                'code' => 'MNT',
                'description' => 'Equipment & Facility Maintenance - Maintains facilities and equipment'
            ],
            [
                'name' => 'Quality Assurance',
                'code' => 'QA',
                'description' => 'Quality Control & Assurance - Ensures service quality and compliance standards'
            ],
            [
                'name' => 'Procurement',
                'code' => 'PROC',
                'description' => 'Procurement & Supply Chain - Manages purchasing and supplier relationships'
            ]
        ];

        foreach ($departments as $deptData) {
            $dept = Department::create($deptData);
            $this->command->line("  ✅ {$dept->name} ({$dept->code})");
        }
    }

    private function createTrainingTypes()
    {
        $this->command->info('🎓 Creating training types...');

        $trainingTypes = [
            // Safety Training
            [
                'name' => 'Fire Safety Training',
                'code' => 'FIRE',
                'category' => 'Safety',
                'validity_months' => 12,
                'description' => 'Fire prevention, suppression, and emergency evacuation procedures'
            ],
            [
                'name' => 'First Aid Training',
                'code' => 'FIRST',
                'category' => 'Safety',
                'validity_months' => 24,
                'description' => 'Basic first aid and emergency medical response training'
            ],
            [
                'name' => 'Occupational Health & Safety',
                'code' => 'OHS',
                'category' => 'Safety',
                'validity_months' => 12,
                'description' => 'Workplace safety regulations and hazard prevention'
            ],

            // Aviation Training
            [
                'name' => 'Dangerous Goods Awareness',
                'code' => 'DG',
                'category' => 'Aviation',
                'validity_months' => 24,
                'description' => 'Handling and transportation of dangerous goods by air'
            ],
            [
                'name' => 'Ground Handling Training',
                'code' => 'GH',
                'category' => 'Aviation',
                'validity_months' => 36,
                'description' => 'Aircraft ground handling procedures and safety'
            ],
            [
                'name' => 'Aviation Security Training',
                'code' => 'AVSEC',
                'category' => 'Security',
                'validity_months' => 12,
                'description' => 'Airport security procedures and threat assessment'
            ],

            // Technical Training
            [
                'name' => 'Equipment Operation Training',
                'code' => 'EQP',
                'category' => 'Technical',
                'validity_months' => 18,
                'description' => 'Safe operation of ground support equipment'
            ],
            [
                'name' => 'Maintenance Procedures',
                'code' => 'MAINT',
                'category' => 'Technical',
                'validity_months' => 24,
                'description' => 'Equipment maintenance and troubleshooting procedures'
            ],

            // Compliance Training
            [
                'name' => 'MPGA Compliance Training',
                'code' => 'MPGA',
                'category' => 'Compliance',
                'validity_months' => 12,
                'description' => 'PT. Gapura Angkasa standard operating procedures and compliance'
            ],
            [
                'name' => 'ISO 9001 Quality Management',
                'code' => 'ISO9001',
                'category' => 'Quality',
                'validity_months' => 36,
                'description' => 'Quality management system standards and procedures'
            ],

            // Customer Service
            [
                'name' => 'Customer Service Excellence',
                'code' => 'CS',
                'category' => 'Service',
                'validity_months' => 24,
                'description' => 'Customer service standards and communication skills'
            ],
            [
                'name' => 'Passenger Assistance Training',
                'code' => 'PAX',
                'category' => 'Service',
                'validity_months' => 18,
                'description' => 'Special assistance for passengers with disabilities'
            ]
        ];

        foreach ($trainingTypes as $typeData) {
            $type = TrainingType::create($typeData);
            $this->command->line("  ✅ {$type->name} ({$type->code}) - {$type->validity_months} months");
        }
    }

    private function createAdminUser()
    {
        $this->command->info('👤 Creating admin user...');

        $adminUser = User::create([
            'name' => 'GAPURA Super Admin',
            'email' => 'admin@gapura.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->command->line("  ✅ Admin user created: {$adminUser->email}");
    }

    private function createSampleEmployees()
    {
        $this->command->info('👥 Creating sample employees...');

        $departments = Department::all();
        $employees = [
            // HR Department
            ['GAP001', 'Ahmad Suryanto', 'HR Manager', 'HR'],
            ['GAP002', 'Nina Sari Dewi', 'Training Coordinator', 'HR'],
            ['GAP003', 'Budi Santoso', 'HR Officer', 'HR'],

            // IT Department
            ['GAP004', 'Rizki Pratama', 'IT Manager', 'IT'],
            ['GAP005', 'Sari Wulandari', 'System Administrator', 'IT'],
            ['GAP006', 'Dedi Kurniawan', 'Network Engineer', 'IT'],

            // Operations
            ['GAP007', 'Agus Setiawan', 'Operations Manager', 'OPS'],
            ['GAP008', 'Maya Indira', 'Ground Handling Supervisor', 'OPS'],
            ['GAP009', 'Rudi Hermawan', 'Operations Officer', 'OPS'],
            ['GAP010', 'Lestari Putri', 'Load Control Officer', 'OPS'],

            // Security
            ['GAP011', 'Bambang Wijaya', 'Security Manager', 'SEC'],
            ['GAP012', 'Dewi Kartika', 'Security Officer', 'SEC'],
            ['GAP013', 'Eko Prasetyo', 'Aviation Security Officer', 'SEC'],

            // GSE
            ['GAP014', 'Hendra Gunawan', 'GSE Manager', 'GSE'],
            ['GAP015', 'Fitri Handayani', 'Equipment Operator', 'GSE'],
            ['GAP016', 'Joko Susilo', 'Maintenance Technician', 'GSE'],

            // Customer Relations
            ['GAP017', 'Ratna Sari', 'Customer Service Manager', 'CR'],
            ['GAP018', 'Andi Kurniawan', 'Customer Service Officer', 'CR'],
            ['GAP019', 'Lisa Permata', 'Passenger Service Agent', 'CR'],

            // Additional employees for other departments
            ['GAP020', 'Fajar Nugroho', 'Finance Manager', 'FIN'],
            ['GAP021', 'Indah Permatasari', 'Accountant', 'FIN'],
            ['GAP022', 'Wahyu Setiadi', 'Maintenance Manager', 'MNT'],
            ['GAP023', 'Sinta Dewi', 'QA Inspector', 'QA'],
            ['GAP024', 'Rico Atmaja', 'Procurement Officer', 'PROC'],
        ];

        foreach ($employees as $empData) {
            $department = $departments->where('code', $empData[3])->first();

            $employee = Employee::create([
                'employee_id' => $empData[0],
                'name' => $empData[1],
                'position' => $empData[2],
                'department_id' => $department->id,
                'status' => 'active',
                'hire_date' => Carbon::now()->subDays(rand(30, 365 * 3)),
                'background_check_date' => Carbon::now()->subDays(rand(10, 100)),
                'background_check_status' => 'cleared'
            ]);

            $this->command->line("  ✅ {$employee->employee_id}: {$employee->name} ({$empData[3]})");
        }
    }

    private function createSampleTrainingRecords()
    {
        $this->command->info('📚 Creating sample training records...');

        $employees = Employee::all();
        $trainingTypes = TrainingType::all();
        $recordCount = 0;

        foreach ($employees as $employee) {
            // Each employee gets 2-5 random training records
            $numTrainings = rand(2, 5);
            $selectedTypes = $trainingTypes->random($numTrainings);

            foreach ($selectedTypes as $trainingType) {
                $issueDate = Carbon::now()->subDays(rand(30, 365));
                $expiryDate = $issueDate->copy()->addMonths($trainingType->validity_months);

                // Determine compliance_status based on expiry date
                $daysUntilExpiry = $expiryDate->diffInDays(Carbon::now(), false);
                $complianceStatus = 'compliant';

                if ($daysUntilExpiry < 0) {
                    $complianceStatus = 'expired';
                } elseif ($daysUntilExpiry <= 30) {
                    $complianceStatus = 'expiring_soon';
                }

                $certificateNumber = $trainingType->code . '-' .
                                   $issueDate->format('Ym') . '-' .
                                   str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                TrainingRecord::create([
                    'employee_id' => $employee->id,
                    'training_type_id' => $trainingType->id,
                    'certificate_number' => $certificateNumber,
                    'issuer' => $this->getRandomIssuer($trainingType->category),
                    'issue_date' => $issueDate,
                    'expiry_date' => $expiryDate,
                    'status' => 'completed', // Always completed for sample records
                    'notes' => rand(1, 3) == 1 ? 'Training completed successfully with excellent performance.' : null,
                    'compliance_status' => $complianceStatus,
                    'training_provider_id' => 1, // Assuming a default provider with ID 1 exists
                    'score' => rand(70, 100), // Add a sample score
                    'passing_score' => 70, // Add a sample passing score
                    'training_hours' => rand(4, 16), // Add sample training hours
                    'cost' => rand(100, 500), // Add sample cost
                    'location' => 'Training Center', // Add sample location
                    'instructor_name' => 'John Doe', // Add sample instructor
                    'completion_date' => $issueDate, // Set completion date to issue date for simplicity
                ]);

                $recordCount++;
            }
        }

        $this->command->line("  ✅ Created {$recordCount} training records");
    }

    private function getRandomIssuer($category)
    {
        $issuers = [
            'Safety' => ['Gapura Safety Department', 'Red Cross Indonesia', 'Safety Institute Indonesia'],
            'Aviation' => ['DGCA Indonesia', 'IATA Training Center', 'Aviation Safety Institute'],
            'Security' => ['Airport Security Institute', 'Gapura Security Department', 'AVSEC Training Center'],
            'Technical' => ['Equipment Manufacturer', 'Technical Training Institute', 'Gapura Technical Department'],
            'Compliance' => ['Gapura Training Department', 'Compliance Institute', 'Quality Assurance Center'],
            'Quality' => ['ISO Training Center', 'Quality Management Institute', 'Gapura QA Department'],
            'Service' => ['Customer Service Institute', 'Gapura Training Department', 'Service Excellence Center']
        ];

        $categoryIssuers = $issuers[$category] ?? ['Gapura Training Department'];
        return $categoryIssuers[array_rand($categoryIssuers)];
    }

    private function createTrainingProviders()
    {
        $this->command->info('🏢 Creating training providers...');

        $providers = [
            ['name' => 'Safety Institute Indonesia', 'contact_person' => 'Budi Santoso', 'phone' => '021-1234567', 'email' => 'info@safetyinstitute.co.id', 'address' => 'Jl. Keselamatan No. 10, Jakarta', 'is_active' => true],
            ['name' => 'Aviation Training Center', 'contact_person' => 'Siti Aminah', 'phone' => '021-7654321', 'email' => 'admin@aviationtc.com', 'address' => 'Jl. Penerbangan No. 5, Tangerang', 'is_active' => true],
            ['name' => 'Global Security Solutions', 'contact_person' => 'David Lee', 'phone' => '021-9876543', 'email' => 'contact@gss.com', 'address' => 'Jl. Keamanan No. 20, Bekasi', 'is_active' => true],
            ['name' => 'Tech Skills Academy', 'contact_person' => 'Rina Wijaya', 'phone' => '021-2345678', 'email' => 'info@techskills.id', 'address' => 'Jl. Teknologi No. 15, Bandung', 'is_active' => true],
            ['name' => 'Quality Management Consult', 'contact_person' => 'Faisal Rahman', 'phone' => '021-8765432', 'email' => 'office@qmc.co.id', 'address' => 'Jl. Kualitas No. 25, Surabaya', 'is_active' => true],
        ];

        foreach ($providers as $providerData) {
            $provider = TrainingProvider::create($providerData);
            $this->command->line("  ✅ {$provider->name}");
        }
    }
}
