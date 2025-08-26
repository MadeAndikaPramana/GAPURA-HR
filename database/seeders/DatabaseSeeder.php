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
use App\Models\TrainingTypeStatistic; // 🚀 NEW for Phase 3
use App\Models\TrainingTypeDepartmentRequirement; // 🚀 NEW for Phase 3
use App\Services\TrainingStatusService;
use App\Services\TrainingTypeAnalyticsService; // 🚀 NEW for Phase 3
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    protected $statusService;
    protected $analyticsService; // 🚀 NEW for Phase 3

    public function __construct()
    {
        $this->statusService = app(TrainingStatusService::class);
        // 🚀 NEW: Only create analytics service if it exists (Phase 3 compatibility)
        if (class_exists(\App\Services\TrainingTypeAnalyticsService::class)) {
            $this->analyticsService = app(TrainingTypeAnalyticsService::class);
        }
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Gapura Training System Database Seeding...');

        // EXISTING: Create departments first
        $this->createDepartments();

        // ENHANCED: Create training types with Phase 3 support
        $this->createTrainingTypes();

        // EXISTING: Create admin user
        $this->createAdminUser();

        // EXISTING: Create training providers
        $this->createTrainingProviders();

        // EXISTING: Create sample employees
        $this->createSampleEmployees();

        // EXISTING: Create sample training records
        $this->createSampleTrainingRecords();

        // 🚀 NEW: Phase 3 specific seeding
        $this->seedPhase3Features();

        $this->command->info('✅ Database seeding completed successfully!');
        $this->showFinalStatistics();
    }

    // EXISTING METHOD - NO CHANGES
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

    // 🚀 ENHANCED METHOD - Added Phase 3 fields while keeping existing functionality
    private function createTrainingTypes()
    {
        $this->command->info('🎓 Creating enhanced training types with Phase 3 support...');

        // Get training providers for default assignment
        $providers = TrainingProvider::all();
        $defaultProvider = $providers->first();

        $trainingTypes = [
            // Safety Training - ENHANCED with Phase 3 fields
            [
                'name' => 'Fire Safety Training',
                'code' => 'FIRE',
                'category' => 'Safety',
                'validity_months' => 12,
                'description' => 'Fire prevention, suppression, and emergency evacuation procedures',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => true,
                'validity_period_months' => 12,
                'warning_period_days' => 30,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 750000,
                'estimated_duration_hours' => 8,
                'requirements' => 'All operational staff must complete this training within 30 days of joining',
                'learning_objectives' => 'Understand fire safety protocols, use fire extinguishers, execute evacuation procedures',
                'requires_certification' => true,
                'priority_score' => 90,
                'compliance_target_percentage' => 100.00
            ],
            [
                'name' => 'First Aid Training',
                'code' => 'FIRST',
                'category' => 'Safety',
                'validity_months' => 24,
                'description' => 'Basic first aid and emergency medical response training',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => true,
                'validity_period_months' => 24,
                'warning_period_days' => 60,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 600000,
                'estimated_duration_hours' => 16,
                'requirements' => 'Required for all employees in operational areas',
                'learning_objectives' => 'Provide basic first aid, CPR, handle medical emergencies',
                'requires_certification' => true,
                'priority_score' => 85,
                'compliance_target_percentage' => 100.00
            ],
            [
                'name' => 'Occupational Health & Safety',
                'code' => 'OHS',
                'category' => 'Safety',
                'validity_months' => 12,
                'description' => 'Workplace safety regulations and hazard prevention',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => true,
                'validity_period_months' => 12,
                'warning_period_days' => 30,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 500000,
                'estimated_duration_hours' => 6,
                'requirements' => 'All employees must complete within first month of employment',
                'learning_objectives' => 'Identify workplace hazards, apply safety procedures, use PPE correctly',
                'requires_certification' => true,
                'priority_score' => 95,
                'compliance_target_percentage' => 100.00
            ],

            // Aviation Training - ENHANCED
            [
                'name' => 'Dangerous Goods Awareness',
                'code' => 'DG',
                'category' => 'Aviation',
                'validity_months' => 24,
                'description' => 'Handling and transportation of dangerous goods by air',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => true,
                'validity_period_months' => 24,
                'warning_period_days' => 60,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 1200000,
                'estimated_duration_hours' => 24,
                'requirements' => 'Required for cargo handling and operations staff',
                'learning_objectives' => 'Classify dangerous goods, apply IATA DGR regulations, handle DG safely',
                'requires_certification' => true,
                'priority_score' => 100,
                'compliance_target_percentage' => 100.00
            ],
            [
                'name' => 'Ground Handling Training',
                'code' => 'GH',
                'category' => 'Aviation',
                'validity_months' => 36,
                'description' => 'Aircraft ground handling procedures and safety',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => true,
                'validity_period_months' => 36,
                'warning_period_days' => 90,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 900000,
                'estimated_duration_hours' => 20,
                'requirements' => 'Required for all ground handling operations staff',
                'learning_objectives' => 'Execute ground handling procedures, operate GSE safely, coordinate turnaround',
                'requires_certification' => true,
                'priority_score' => 88,
                'compliance_target_percentage' => 95.00
            ],
            [
                'name' => 'Aviation Security Training',
                'code' => 'AVSEC',
                'category' => 'Security',
                'validity_months' => 12,
                'description' => 'Airport security procedures and threat assessment',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => true,
                'validity_period_months' => 12,
                'warning_period_days' => 30,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 800000,
                'estimated_duration_hours' => 12,
                'requirements' => 'Required for all personnel with airside access',
                'learning_objectives' => 'Identify security threats, apply screening procedures, understand access control',
                'requires_certification' => true,
                'priority_score' => 92,
                'compliance_target_percentage' => 100.00
            ],

            // Technical Training - ENHANCED
            [
                'name' => 'Equipment Operation Training',
                'code' => 'EQP',
                'category' => 'Technical',
                'validity_months' => 18,
                'description' => 'Safe operation of ground support equipment',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => false,
                'validity_period_months' => 18,
                'warning_period_days' => 45,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 1500000,
                'estimated_duration_hours' => 32,
                'requirements' => 'Required for GSE operators only',
                'learning_objectives' => 'Operate GSE safely, perform pre-operation checks, basic troubleshooting',
                'requires_certification' => true,
                'priority_score' => 70,
                'compliance_target_percentage' => 85.00
            ],
            [
                'name' => 'Maintenance Procedures',
                'code' => 'MAINT',
                'category' => 'Technical',
                'validity_months' => 24,
                'description' => 'Equipment maintenance and troubleshooting procedures',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => false,
                'validity_period_months' => 24,
                'warning_period_days' => 60,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 1800000,
                'estimated_duration_hours' => 40,
                'requirements' => 'Required for maintenance technicians and supervisors',
                'learning_objectives' => 'Perform scheduled maintenance, troubleshoot equipment, follow safety procedures',
                'requires_certification' => true,
                'priority_score' => 75,
                'compliance_target_percentage' => 90.00
            ],

            // Compliance Training - ENHANCED
            [
                'name' => 'MPGA Compliance Training',
                'code' => 'MPGA',
                'category' => 'Compliance',
                'validity_months' => 12,
                'description' => 'PT. Gapura Angkasa standard operating procedures and compliance',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => true,
                'validity_period_months' => 12,
                'warning_period_days' => 30,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 400000,
                'estimated_duration_hours' => 8,
                'requirements' => 'All employees must complete within first 60 days',
                'learning_objectives' => 'Understand MPGA policies, apply SOPs, maintain compliance standards',
                'requires_certification' => true,
                'priority_score' => 80,
                'compliance_target_percentage' => 100.00
            ],
            [
                'name' => 'ISO 9001 Quality Management',
                'code' => 'ISO9001',
                'category' => 'Quality',
                'validity_months' => 36,
                'description' => 'Quality management system standards and procedures',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => false,
                'validity_period_months' => 36,
                'warning_period_days' => 90,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 2000000,
                'estimated_duration_hours' => 24,
                'requirements' => 'Required for quality assurance staff and managers',
                'learning_objectives' => 'Implement QMS, conduct audits, ensure continuous improvement',
                'requires_certification' => true,
                'priority_score' => 65,
                'compliance_target_percentage' => 85.00
            ],

            // Customer Service - ENHANCED
            [
                'name' => 'Customer Service Excellence',
                'code' => 'CS',
                'category' => 'Service',
                'validity_months' => 24,
                'description' => 'Customer service standards and communication skills',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => false,
                'validity_period_months' => 24,
                'warning_period_days' => 60,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 600000,
                'estimated_duration_hours' => 12,
                'requirements' => 'Required for customer-facing staff',
                'learning_objectives' => 'Deliver excellent service, handle complaints, communicate effectively',
                'requires_certification' => true,
                'priority_score' => 55,
                'compliance_target_percentage' => 80.00
            ],
            [
                'name' => 'Passenger Assistance Training',
                'code' => 'PAX',
                'category' => 'Service',
                'validity_months' => 18,
                'description' => 'Special assistance for passengers with disabilities',
                'is_active' => true,
                // 🚀 NEW Phase 3 fields
                'is_mandatory' => false,
                'validity_period_months' => 18,
                'warning_period_days' => 45,
                'default_provider_id' => $defaultProvider?->id,
                'estimated_cost' => 800000,
                'estimated_duration_hours' => 16,
                'requirements' => 'Required for passenger service agents',
                'learning_objectives' => 'Assist PRM passengers, operate accessibility equipment, ensure dignity',
                'requires_certification' => true,
                'priority_score' => 60,
                'compliance_target_percentage' => 75.00
            ]
        ];

        foreach ($trainingTypes as $typeData) {
            // Create or update training type
            $type = TrainingType::updateOrCreate(
                ['code' => $typeData['code']], // Find by code
                $typeData // Update with all data
            );

            $this->command->line("  ✅ {$type->name} ({$type->code}) - {$type->validity_period_months} months - " .
                ($type->is_mandatory ? 'MANDATORY' : 'Optional'));
        }
    }

    // EXISTING METHOD - NO CHANGES
    private function createAdminUser()
    {
        $this->command->info('👤 Creating admin user...');

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@gapura.com'],
            [
                'name' => 'GAPURA Super Admin',
                'email' => 'admin@gapura.com',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        );

        $this->command->line("  ✅ Admin user created: {$adminUser->email}");
        $this->command->line("  🔑 Password: password");
    }

    // EXISTING METHOD - NO CHANGES (keeping all existing logic)
    private function createTrainingProviders()
    {
        $this->command->info('🏢 Creating training providers...');

        $providers = [
            [
                'name' => 'Safety Institute Indonesia',
                'code' => 'SII',
                'contact_person' => 'Budi Santoso',
                'phone' => '021-1234567',
                'email' => 'info@safetyinstitute.co.id',
                'address' => 'Jl. Keselamatan No. 10, Jakarta',
                'website' => 'https://safetyinstitute.co.id',
                'accreditation_number' => 'ACC-SII-2024-001',
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
            $provider = TrainingProvider::updateOrCreate(
                ['code' => $providerData['code']],
                $providerData
            );
            $this->command->line("  ✅ {$provider->name} ({$provider->code}) - Rating: {$provider->rating}");
        }
    }

    // EXISTING METHOD - NO CHANGES (keeping all existing employee creation logic)
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

            $employee = Employee::updateOrCreate(
                ['employee_id' => $empData[0]],
                [
                    'employee_id' => $empData[0],
                    'name' => $empData[1],
                    'position' => $empData[2],
                    'department_id' => $department->id,
                    'status' => 'active',
                    'hire_date' => Carbon::now()->subDays(rand(30, 365 * 3)),
                    'background_check_date' => Carbon::now()->subDays(rand(10, 100)),
                    'background_check_status' => 'cleared'
                ]
            );

            $this->command->line("  ✅ {$employee->employee_id}: {$employee->name} ({$empData[3]})");
        }
    }

    // EXISTING METHOD - Keeping all existing training record creation logic
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
                    $expiryDate = $issueDate->copy()->addMonths($trainingType->validity_period_months ?? $trainingType->validity_months);

                    // Use TrainingStatusService untuk generate certificate number
                    $certificateNumber = $this->statusService->generateCertificateNumber($employee, $trainingType);

                    // Use service untuk calculate status
                    $status = $this->statusService->calculateStatus($expiryDate->format('Y-m-d'));
                    $complianceStatus = $this->statusService->calculateComplianceStatus($status);

                    // Random provider
                    $provider = $providers->random();

                    TrainingRecord::create([
                        'employee_id' => $employee->id,
                        'training_type_id' => $trainingType->id,
                        'training_provider_id' => $provider->id,
                        'certificate_number' => $certificateNumber,
                        'issuer' => $provider->name,
                        'issue_date' => $issueDate->format('Y-m-d'),
                        'completion_date' => $completionDate->format('Y-m-d'),
                        'expiry_date' => $expiryDate->format('Y-m-d'),
                        'training_date' => $issueDate->subDays(rand(1, 5))->format('Y-m-d'),
                        'status' => $status,
                        'compliance_status' => $complianceStatus,
                        'batch_number' => 'BATCH-' . $issueDate->format('Ym') . '-' . str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT),
                        'score' => rand(1, 10) > 2 ? rand(70, 100) : null,
                        'passing_score' => $trainingType->category === 'Safety' ? 80 : 70,
                        'training_hours' => rand(4, 40),
                        'cost' => rand(500000, 8000000),
                        'location' => $locations[array_rand($locations)],
                        'instructor_name' => rand(1, 10) > 3 ? $instructors[array_rand($instructors)] : null,
                        'notes' => rand(1, 10) > 7 ? $this->generateRealisticNotes($trainingType) : null,
                        'reminder_sent_at' => null,
                        'reminder_count' => 0,
                        'created_by_id' => 1,
                        'updated_by_id' => null,
                        'created_at' => $issueDate,
                        'updated_at' => $issueDate,
                    ]);

                    $recordCount++;

                    // Progress indicator
                    if ($recordCount % 25 === 0) {
                        $this->command->line("  📄 Created {$recordCount} training records...");
                    }
                }
            }
        }

        $this->command->line("  ✅ Successfully created {$recordCount} training records");
    }

    private function seedPhase3Features()
    {
    $this->command->info('🎯 Setting up Phase 3 features...');

    // Check if the model exists and table has correct structure
    if (class_exists(\App\Models\TrainingTypeDepartmentRequirement::class)) {
        try {
            // Test if table structure is correct
            DB::select("SELECT training_type_id FROM training_type_department_requirements LIMIT 1");
            $this->createDepartmentRequirements();
        } catch (\Exception $e) {
            $this->command->warn("⚠️ Skipping department requirements: {$e->getMessage()}");
        }
    }

    if (class_exists(\App\Models\TrainingTypeStatistic::class) && $this->analyticsService) {
        try {
            $this->generateInitialStatistics();
        } catch (\Exception $e) {
            $this->command->warn("⚠️ Skipping statistics generation: {$e->getMessage()}");
        }
    }
}

    // 🚀 NEW METHOD: Create department-specific requirements
    private function createDepartmentRequirements()
    {
        $this->command->info('🏢 Setting up department-specific training requirements...');

        $departments = Department::all();
        $trainingTypes = TrainingType::all();

        // Define which training types are required for which departments
        $requirements = [
            'HR' => ['Fire Safety Training', 'First Aid Training', 'Occupational Health & Safety', 'MPGA Compliance Training'],
            'IT' => ['Fire Safety Training', 'Occupational Health & Safety', 'MPGA Compliance Training'],
            'OPS' => ['Fire Safety Training', 'First Aid Training', 'Occupational Health & Safety', 'Dangerous Goods Awareness', 'Ground Handling Training', 'MPGA Compliance Training'],
            'GSE' => ['Fire Safety Training', 'First Aid Training', 'Occupational Health & Safety', 'Equipment Operation Training', 'Maintenance Procedures', 'MPGA Compliance Training'],
            'SEC' => ['Fire Safety Training', 'First Aid Training', 'Occupational Health & Safety', 'Aviation Security Training', 'MPGA Compliance Training'],
            'CR' => ['Fire Safety Training', 'Occupational Health & Safety', 'Customer Service Excellence', 'Passenger Assistance Training', 'MPGA Compliance Training'],
            'FIN' => ['Fire Safety Training', 'Occupational Health & Safety', 'MPGA Compliance Training'],
            'MNT' => ['Fire Safety Training', 'First Aid Training', 'Occupational Health & Safety', 'Equipment Operation Training', 'Maintenance Procedures', 'MPGA Compliance Training'],
            'QA' => ['Fire Safety Training', 'Occupational Health & Safety', 'ISO 9001 Quality Management', 'MPGA Compliance Training'],
            'PROC' => ['Fire Safety Training', 'Occupational Health & Safety', 'MPGA Compliance Training']
        ];

        foreach ($departments as $department) {
            $deptRequiredTrainings = $requirements[$department->code] ?? [];

            foreach ($deptRequiredTrainings as $trainingName) {
                $trainingType = $trainingTypes->where('name', $trainingName)->first();

                if ($trainingType) {
                    TrainingTypeDepartmentRequirement::updateOrCreate([
                        'training_type_id' => $trainingType->id,
                        'department_id' => $department->id
                    ], [
                        'is_required' => true,
                        'frequency_months' => $trainingType->validity_period_months,
                        'target_compliance_rate' => $trainingType->compliance_target_percentage,
                        'department_specific_requirements' => "Required for all {$department->name} staff"
                    ]);
                }
            }

            $this->command->line("  ✅ Set requirements for {$department->name}");
        }
    }

    // 🚀 NEW METHOD: Generate initial statistics
    private function generateInitialStatistics()
    {
        $this->command->info('📊 Generating initial training type statistics...');

        $trainingTypes = TrainingType::all();
        $statisticsGenerated = 0;

        foreach ($trainingTypes as $trainingType) {
            try {
                if (method_exists($trainingType, 'updateStatistics')) {
                    $trainingType->updateStatistics();
                    $statisticsGenerated++;
                }
            } catch (\Exception $e) {
                $this->command->warn("  ⚠️ Could not generate statistics for {$trainingType->name}: {$e->getMessage()}");
            }
        }

        $this->command->line("  ✅ Generated statistics for {$statisticsGenerated} training types");
    }

    // EXISTING HELPER METHODS - NO CHANGES
    private function generateRealisticIssueDate(int $recordIndex): Carbon
    {
        $baseDate = match ($recordIndex) {
            0 => Carbon::now()->subMonths(rand(18, 36)),
            1 => Carbon::now()->subMonths(rand(6, 18)),
            default => Carbon::now()->subMonths(rand(1, 12))
        };

        return $baseDate->addDays(rand(-15, 15));
    }

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

    // ENHANCED: Show final statistics with Phase 3 data
    private function showFinalStatistics(): void
    {
        $this->command->info('📊 Final Statistics:');
        $this->command->line('─────────────────────');

        $stats = $this->statusService->getComplianceStatistics();

        $finalStats = [
            ['Departments', Department::count()],
            ['Training Types', TrainingType::count()],
            ['Training Providers', TrainingProvider::count()],
            ['Employees', Employee::count()],
            ['Training Records', TrainingRecord::count()],
            ['Active Certificates', $stats['active_certificates']],
            ['Expiring Certificates', $stats['expiring_certificates']],
            ['Expired Certificates', $stats['expired_certificates']],
            ['Compliance Rate', $stats['compliance_rate'] . '%']
        ];

        // 🚀 Add Phase 3 statistics if available
        if (class_exists(\App\Models\TrainingTypeDepartmentRequirement::class)) {
            $finalStats[] = ['Department Requirements', TrainingTypeDepartmentRequirement::count()];
        }

        if (class_exists(\App\Models\TrainingTypeStatistic::class)) {
            $finalStats[] = ['Training Type Statistics', TrainingTypeStatistic::count()];
        }

        $this->command->table(['Metric', 'Count'], $finalStats);

        $this->command->info('💡 Next Steps:');
        $this->command->line('  • Login: admin@gapura.com / password');
        $this->command->line('  • Visit: /training-records to see the data');
        $this->command->line('  • Visit: /training-types for Phase 3 analytics (if available)');
        $this->command->line('  • Test: php artisan training:update-status --dry-run');
        $this->command->line('  • Command: php artisan serve');
    }
}
