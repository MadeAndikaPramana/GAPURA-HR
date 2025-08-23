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
use App\Services\TrainingStatusService; // TAMBAHAN: Import service
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    protected $statusService;

    public function __construct()
    {
        $this->statusService = app(TrainingStatusService::class);
    }

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

        // Create training providers
        $this->createTrainingProviders();

        // Create sample employees
        $this->createSampleEmployees();

        // Create sample training records (UPDATED dengan service baru)
        $this->createSampleTrainingRecords();

        $this->command->info('✅ Database seeding completed successfully!');
        $this->showFinalStatistics(); // TAMBAHAN: Show final stats
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
                'description' => 'Fire prevention, suppression, and emergency evacuation procedures',
                'is_active' => true // TAMBAHAN: Field is_active
            ],
            [
                'name' => 'First Aid Training',
                'code' => 'FIRST',
                'category' => 'Safety',
                'validity_months' => 24,
                'description' => 'Basic first aid and emergency medical response training',
                'is_active' => true
            ],
            [
                'name' => 'Occupational Health & Safety',
                'code' => 'OHS',
                'category' => 'Safety',
                'validity_months' => 12,
                'description' => 'Workplace safety regulations and hazard prevention',
                'is_active' => true
            ],

            // Aviation Training
            [
                'name' => 'Dangerous Goods Awareness',
                'code' => 'DG',
                'category' => 'Aviation',
                'validity_months' => 24,
                'description' => 'Handling and transportation of dangerous goods by air',
                'is_active' => true
            ],
            [
                'name' => 'Ground Handling Training',
                'code' => 'GH',
                'category' => 'Aviation',
                'validity_months' => 36,
                'description' => 'Aircraft ground handling procedures and safety',
                'is_active' => true
            ],
            [
                'name' => 'Aviation Security Training',
                'code' => 'AVSEC',
                'category' => 'Security',
                'validity_months' => 12,
                'description' => 'Airport security procedures and threat assessment',
                'is_active' => true
            ],

            // Technical Training
            [
                'name' => 'Equipment Operation Training',
                'code' => 'EQP',
                'category' => 'Technical',
                'validity_months' => 18,
                'description' => 'Safe operation of ground support equipment',
                'is_active' => true
            ],
            [
                'name' => 'Maintenance Procedures',
                'code' => 'MAINT',
                'category' => 'Technical',
                'validity_months' => 24,
                'description' => 'Equipment maintenance and troubleshooting procedures',
                'is_active' => true
            ],

            // Compliance Training
            [
                'name' => 'MPGA Compliance Training',
                'code' => 'MPGA',
                'category' => 'Compliance',
                'validity_months' => 12,
                'description' => 'PT. Gapura Angkasa standard operating procedures and compliance',
                'is_active' => true
            ],
            [
                'name' => 'ISO 9001 Quality Management',
                'code' => 'ISO9001',
                'category' => 'Quality',
                'validity_months' => 36,
                'description' => 'Quality management system standards and procedures',
                'is_active' => true
            ],

            // Customer Service
            [
                'name' => 'Customer Service Excellence',
                'code' => 'CS',
                'category' => 'Service',
                'validity_months' => 24,
                'description' => 'Customer service standards and communication skills',
                'is_active' => true
            ],
            [
                'name' => 'Passenger Assistance Training',
                'code' => 'PAX',
                'category' => 'Service',
                'validity_months' => 18,
                'description' => 'Special assistance for passengers with disabilities',
                'is_active' => true
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
            // 'role' => 'super_admin', // Uncomment jika ada role field
            // 'is_active' => true,     // Uncomment jika ada is_active field
        ]);

        $this->command->line("  ✅ Admin user created: {$adminUser->email}");
        $this->command->line("  🔑 Password: password");
    }

    private function createTrainingProviders()
    {
        $this->command->info('🏢 Creating training providers...');

        $providers = [
            [
                'name' => 'Safety Institute Indonesia',
                'code' => 'SII', // TAMBAHAN: Field code
                'contact_person' => 'Budi Santoso',
                'phone' => '021-1234567',
                'email' => 'info@safetyinstitute.co.id',
                'address' => 'Jl. Keselamatan No. 10, Jakarta',
                'website' => 'https://safetyinstitute.co.id', // TAMBAHAN: Field website
                'accreditation_number' => 'ACC-SII-2024-001', // TAMBAHAN: Accreditation
                'accreditation_expiry' => Carbon::now()->addYears(3),
                'contract_start_date' => Carbon::now()->subYear(),
                'contract_end_date' => Carbon::now()->addYears(2),
                'rating' => 4.5,
                'is_active' => true
            ],
            [
                'name' => 'Aviation Training Center',
                'code' => 'ATC',
                'contact_person' => 'Siti Aminah',
                'phone' => '021-7654321',
                'email' => 'admin@aviationtc.com',
                'address' => 'Jl. Penerbangan No. 5, Tangerang',
                'website' => 'https://aviationtc.com',
                'accreditation_number' => 'ACC-ATC-2024-002',
                'accreditation_expiry' => Carbon::now()->addYears(3),
                'contract_start_date' => Carbon::now()->subYear(),
                'contract_end_date' => Carbon::now()->addYears(2),
                'rating' => 4.8,
                'is_active' => true
            ],
            [
                'name' => 'Global Security Solutions',
                'code' => 'GSS',
                'contact_person' => 'David Lee',
                'phone' => '021-9876543',
                'email' => 'contact@gss.com',
                'address' => 'Jl. Keamanan No. 20, Bekasi',
                'website' => 'https://gss.com',
                'accreditation_number' => 'ACC-GSS-2024-003',
                'accreditation_expiry' => Carbon::now()->addYears(3),
                'contract_start_date' => Carbon::now()->subYear(),
                'contract_end_date' => Carbon::now()->addYears(2),
                'rating' => 4.2,
                'is_active' => true
            ],
            [
                'name' => 'Tech Skills Academy',
                'code' => 'TSA',
                'contact_person' => 'Rina Wijaya',
                'phone' => '021-2345678',
                'email' => 'info@techskills.id',
                'address' => 'Jl. Teknologi No. 15, Bandung',
                'website' => 'https://techskills.id',
                'accreditation_number' => 'ACC-TSA-2024-004',
                'accreditation_expiry' => Carbon::now()->addYears(3),
                'contract_start_date' => Carbon::now()->subYear(),
                'contract_end_date' => Carbon::now()->addYears(2),
                'rating' => 4.6,
                'is_active' => true
            ],
            [
                'name' => 'Quality Management Consult',
                'code' => 'QMC',
                'contact_person' => 'Faisal Rahman',
                'phone' => '021-8765432',
                'email' => 'office@qmc.co.id',
                'address' => 'Jl. Kualitas No. 25, Surabaya',
                'website' => 'https://qmc.co.id',
                'accreditation_number' => 'ACC-QMC-2024-005',
                'accreditation_expiry' => Carbon::now()->addYears(3),
                'contract_start_date' => Carbon::now()->subYear(),
                'contract_end_date' => Carbon::now()->addYears(2),
                'rating' => 4.3,
                'is_active' => true
            ]
        ];

        foreach ($providers as $providerData) {
            $provider = TrainingProvider::create($providerData);
            $this->command->line("  ✅ {$provider->name} ({$provider->code}) - Rating: {$provider->rating}");
        }
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
        $this->command->info('📚 Creating sample training records with realistic data...');

        $employees = Employee::with('department')->get();
        $trainingTypes = TrainingType::all();
        $providers = TrainingProvider::all();
        $recordCount = 0;

        $locations = [
            'Jakarta Training Center',
            'Head Office Meeting Room A',
            'Surabaya Training Facility',
            'Denpasar Airport Training Room',
            'Online Training Platform',
            'External Training Venue',
            'Simulator Training Center',
            'Safety Training Ground'
        ];

        $instructors = [
            'Capt. Budi Setiawan',
            'Dr. Sari Wulandari',
            'Eng. Ahmad Pratama',
            'Prof. Nina Kartini',
            'Mr. David Johnson',
            'Ms. Lisa Anderson',
            'Ir. Agus Permana',
            'Dr. Rina Kusumawati'
        ];

        foreach ($employees as $employee) {
            // Each employee gets 2-5 random training records dengan beberapa renewals
            $numTrainings = rand(2, 5);
            $selectedTypes = $trainingTypes->random($numTrainings);

            foreach ($selectedTypes as $trainingType) {
                // Create 1-3 records per training type (termasuk renewals)
                $recordsPerType = rand(1, 3);

                for ($i = 0; $i < $recordsPerType; $i++) {
                    // Generate realistic dates (older records first)
                    $issueDate = $this->generateRealisticIssueDate($i);
                    $completionDate = $issueDate->copy()->addDays(rand(0, 7));

                    // Calculate expiry date using training type validity
                    $expiryDate = $issueDate->copy()->addMonths($trainingType->validity_months);

                    // UPDATED: Use TrainingStatusService untuk generate certificate number
                    $certificateNumber = $this->statusService->generateCertificateNumber($employee, $trainingType);

                    // UPDATED: Use service untuk calculate status
                    $status = $this->statusService->calculateStatus($expiryDate->format('Y-m-d'));
                    $complianceStatus = $this->statusService->calculateComplianceStatus($status);

                    // Random provider
                    $provider = $providers->random();

                    TrainingRecord::create([
                        'employee_id' => $employee->id,
                        'training_type_id' => $trainingType->id,
                        'training_provider_id' => $provider->id, // UPDATED: Link to provider
                        'certificate_number' => $certificateNumber,
                        'issuer' => $provider->name, // Use provider name
                        'issue_date' => $issueDate->format('Y-m-d'),
                        'completion_date' => $completionDate->format('Y-m-d'),
                        'expiry_date' => $expiryDate->format('Y-m-d'),
                        'training_date' => $issueDate->subDays(rand(1, 5))->format('Y-m-d'), // TAMBAHAN: Training date before issue
                        'status' => $status, // UPDATED: Use calculated status
                        'compliance_status' => $complianceStatus, // UPDATED: Use calculated compliance
                        'batch_number' => 'BATCH-' . $issueDate->format('Ym') . '-' . str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT), // TAMBAHAN
                        'score' => rand(1, 10) > 2 ? rand(70, 100) : null, // 80% chance of having score
                        'passing_score' => $trainingType->category === 'Safety' ? 80 : 70, // TAMBAHAN: Different passing scores
                        'training_hours' => rand(4, 40), // UPDATED: More realistic range
                        'cost' => rand(500000, 8000000), // UPDATED: IDR format realistic costs
                        'location' => $locations[array_rand($locations)], // Random realistic location
                        'instructor_name' => rand(1, 10) > 3 ? $instructors[array_rand($instructors)] : null, // 70% chance
                        'notes' => rand(1, 10) > 7 ? $this->generateRealisticNotes($trainingType) : null, // 30% chance
                        'reminder_sent_at' => null, // TAMBAHAN: Will be updated by system
                        'reminder_count' => 0, // TAMBAHAN
                        'created_by_id' => 1, // Assuming admin user ID is 1
                        'updated_by_id' => null,
                        'created_at' => $issueDate,
                        'updated_at' => $issueDate,
                    ]);

                    $recordCount++;

                    // Progress indicator
                    if ($recordCount % 25 === 0) {
                        $this->command->line("  🔄 Created {$recordCount} training records...");
                    }
                }
            }
        }

        $this->command->line("  ✅ Successfully created {$recordCount} training records");
    }

    /**
     * Generate realistic issue dates (older records first)
     */
    private function generateRealisticIssueDate(int $recordIndex): Carbon
    {
        $baseDate = match ($recordIndex) {
            0 => Carbon::now()->subMonths(rand(18, 36)), // First record: 1.5-3 years ago
            1 => Carbon::now()->subMonths(rand(6, 18)),  // Second record: 6-18 months ago
            default => Carbon::now()->subMonths(rand(1, 12)) // Recent records: 1-12 months ago
        };

        return $baseDate->addDays(rand(-15, 15));
    }

    /**
     * Generate realistic training notes
     */
    private function generateRealisticNotes(TrainingType $trainingType): string
    {
        $notes = [
            'Safety' => [
                'Training completed with excellent safety awareness demonstration.',
                'Passed practical safety drill with flying colors.',
                'Demonstrated strong understanding of emergency procedures.',
                'Requires refresher on specific safety equipment usage.'
            ],
            'Aviation' => [
                'Excellent knowledge of aviation regulations and procedures.',
                'Demonstrated proficiency in ground handling operations.',
                'Strong performance in dangerous goods handling simulation.',
                'Needs additional practice on specific aircraft types.'
            ],
            'Technical' => [
                'Technical competency demonstrated through hands-on exercises.',
                'Successfully completed equipment operation certification.',
                'Strong troubleshooting skills shown during practical test.',
                'Recommended for advanced technical training program.'
            ],
            'Compliance' => [
                'Full compliance understanding achieved.',
                'Excellent grasp of regulatory requirements.',
                'Demonstrated commitment to quality standards.',
                'Active participation in compliance discussions.'
            ],
            'default' => [
                'Training objectives successfully achieved.',
                'Good performance throughout the training program.',
                'Demonstrated competency in all required areas.',
                'Actively participated in training discussions.'
            ]
        ];

        $categoryNotes = $notes[$trainingType->category] ?? $notes['default'];
        return $categoryNotes[array_rand($categoryNotes)];
    }

    /**
     * Show final seeding statistics
     */
    private function showFinalStatistics(): void
    {
        $this->command->info('📊 Final Statistics:');
        $this->command->line('─────────────────────');

        $stats = $this->statusService->getComplianceStatistics();

        $this->command->table(
            ['Metric', 'Count'],
            [
                ['Departments', Department::count()],
                ['Training Types', TrainingType::count()],
                ['Training Providers', TrainingProvider::count()],
                ['Employees', Employee::count()],
                ['Training Records', TrainingRecord::count()],
                ['Active Certificates', $stats['active_certificates']],
                ['Expiring Certificates', $stats['expiring_certificates']],
                ['Expired Certificates', $stats['expired_certificates']],
                ['Compliance Rate', $stats['compliance_rate'] . '%']
            ]
        );

        $this->command->info('💡 Next Steps:');
        $this->command->line('  • Login: admin@gapura.com / password');
        $this->command->line('  • Visit: /training-records to see the data');
        $this->command->line('  • Test: php artisan training:update-status --dry-run');
        $this->command->line('  • Command: php artisan serve');
    }
}
