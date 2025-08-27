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
use App\Services\TrainingStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    protected $statusService;
    protected $analyticsService;

    public function __construct()
    {
        $this->statusService = app(TrainingStatusService::class);

        // Only create analytics service if it exists (Phase 3 compatibility)
        if (class_exists(\App\Services\TrainingTypeAnalyticsService::class)) {
            $this->analyticsService = app(\App\Services\TrainingTypeAnalyticsService::class);
        }
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Gapura Training System Database Seeding...');

        // STEP 1: Create departments (if table exists)
        if (Schema::hasTable('departments')) {
            $this->createDepartments();
        } else {
            $this->command->warn('⚠️  Departments table not found - skipping department creation');
        }

        // STEP 2: Create admin user first
        $this->createAdminUser();

        // STEP 3: Create training providers (UPDATED - more comprehensive)
        $this->createTrainingProviders();

        // STEP 4: Create training types with Phase 3 support
        $this->createTrainingTypes();

        // STEP 5: Create sample employees
        $this->createSampleEmployees();

        // STEP 6: Create sample training records
        $this->createSampleTrainingRecords();

        // STEP 7: Phase 3 specific seeding
        $this->seedPhase3Features();

        $this->command->info('✅ Database seeding completed successfully!');
        $this->printFinalSummary();
    }

    /**
     * Create departments
     */
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
            $dept = Department::updateOrCreate(
                ['code' => $deptData['code']],
                $deptData
            );
            $this->command->line("  ✅ {$dept->name} ({$dept->code})");
        }
    }

    /**
     * Create admin user
     */
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

    /**
     * Create comprehensive training providers
     */
    private function createTrainingProviders()
    {
        $this->command->info('🏢 Creating comprehensive training providers...');

        $providers = [
            [
                'name' => 'PT. Aviasi Training Center',
                'code' => 'ATC001',
                'contact_person' => 'Budi Santoso',
                'email' => 'info@aviasitraining.co.id',
                'phone' => '+62 21 5550 1234',
                'address' => 'Jl. Bandara Soekarno-Hatta No. 123, Tangerang, Banten 15126',
                'website' => 'https://www.aviasitraining.co.id',
                'accreditation_number' => 'LSP-AVI-2024-001',
                'accreditation_expiry' => '2025-12-31',
                'contract_start_date' => '2024-01-01',
                'contract_end_date' => '2025-12-31',
                'rating' => 4.5,
                'notes' => 'Specialized in aviation safety and security training. Excellent track record with ground handling procedures.',
                'is_active' => true,
            ],
            [
                'name' => 'Indonesia Safety Institute',
                'code' => 'ISI002',
                'contact_person' => 'Sari Wijayanti',
                'email' => 'training@safeinstitute.id',
                'phone' => '+62 21 8880 5678',
                'address' => 'Gedung Safety Center Lt. 5, Jl. Sudirman No. 45, Jakarta Pusat 10220',
                'website' => 'https://www.safeinstitute.id',
                'accreditation_number' => 'LSP-SAF-2024-002',
                'accreditation_expiry' => '2026-06-30',
                'contract_start_date' => '2024-03-01',
                'contract_end_date' => '2026-02-28',
                'rating' => 4.8,
                'notes' => 'Premier safety training provider with international certifications. Specializes in workplace safety and emergency procedures.',
                'is_active' => true,
            ],
            [
                'name' => 'Garuda Training Solutions',
                'code' => 'GTS003',
                'contact_person' => 'Ahmad Rahman',
                'email' => 'contact@garudatraining.com',
                'phone' => '+62 21 7770 9012',
                'address' => 'Training Complex Garuda Indonesia, Jl. Airport Raya, Jakarta 14110',
                'website' => 'https://www.garudatraining.com',
                'accreditation_number' => 'LSP-GTS-2023-003',
                'accreditation_expiry' => '2025-03-15', // Expiring soon - for demo
                'contract_start_date' => '2023-06-01',
                'contract_end_date' => '2025-05-31',
                'rating' => 4.2,
                'notes' => 'Comprehensive aviation training including cabin crew, ground staff, and technical maintenance programs.',
                'is_active' => true,
            ],
            [
                'name' => 'Security Pro Training',
                'code' => 'SPT004',
                'contact_person' => 'Linda Setiawati',
                'email' => 'admin@securitypro.co.id',
                'phone' => '+62 21 6660 3456',
                'address' => 'Jl. Keamanan Raya No. 88, South Jakarta 12960',
                'website' => 'https://www.securitypro.co.id',
                'accreditation_number' => 'LSP-SEC-2024-004',
                'accreditation_expiry' => '2025-09-30',
                'contract_start_date' => '2024-02-15',
                'contract_end_date' => '2025-02-14',
                'rating' => 4.0,
                'notes' => 'Specialized in airport security, access control, and security awareness training programs.',
                'is_active' => true,
            ],
            [
                'name' => 'TechSkill Development Center',
                'code' => 'TDC005',
                'contact_person' => 'Eko Prasetyo',
                'email' => 'info@techskill.training',
                'phone' => '+62 21 4440 7890',
                'address' => 'Cyber Park Building, Jl. Technology Boulevard No. 12, BSD City, Tangerang',
                'website' => 'https://www.techskill.training',
                'accreditation_number' => 'LSP-TECH-2024-005',
                'accreditation_expiry' => '2026-11-30',
                'contract_start_date' => '2024-04-01',
                'contract_end_date' => '2026-03-31',
                'rating' => 4.3,
                'notes' => 'Technical training provider focusing on equipment operation, maintenance procedures, and technical competency development.',
                'is_active' => true,
            ],
            [
                'name' => 'Customer Excellence Academy',
                'code' => 'CEA006',
                'contact_person' => 'Maya Sinta',
                'email' => 'training@customerexcellence.id',
                'phone' => '+62 21 3330 2468',
                'address' => 'Service Training Hub, Jl. Pelayanan Prima No. 99, Jakarta',
                'website' => 'https://www.customerexcellence.id',
                'accreditation_number' => 'LSP-SVC-2024-006',
                'accreditation_expiry' => '2025-08-15',
                'contract_start_date' => '2024-01-15',
                'contract_end_date' => '2025-01-14',
                'rating' => 4.6,
                'notes' => 'Customer service and passenger assistance training specialist. Excellent for hospitality and service quality improvement.',
                'is_active' => true,
            ],
            [
                'name' => 'International Quality Systems',
                'code' => 'IQS007',
                'contact_person' => 'Robert Thompson',
                'email' => 'indonesia@iqsystems.com',
                'phone' => '+62 21 2220 1357',
                'address' => 'IQS Training Center, Jl. International Plaza No. 15, Jakarta',
                'website' => 'https://www.iqsystems.com',
                'accreditation_number' => 'ISO-QMS-2024-007',
                'accreditation_expiry' => '2027-01-31',
                'contract_start_date' => '2024-05-01',
                'contract_end_date' => '2026-04-30',
                'rating' => 4.7,
                'notes' => 'International standard ISO 9001 quality management training. High-quality delivery with global best practices.',
                'is_active' => true,
            ],
            [
                'name' => 'Legacy Training Institute',
                'code' => 'LTI008',
                'contact_person' => 'Indra Wijaya',
                'email' => 'legacy@training.net',
                'phone' => '+62 21 1110 8642',
                'address' => 'Old Training Complex, Jl. Veteran No. 67, Jakarta',
                'website' => null,
                'accreditation_number' => 'LSP-OLD-2022-008',
                'accreditation_expiry' => '2024-06-30', // Expired - for demo
                'contract_start_date' => '2022-01-01',
                'contract_end_date' => '2024-12-31',
                'rating' => 3.2,
                'notes' => 'Older training provider with traditional methods. Contract ending soon, accreditation expired.',
                'is_active' => false, // Inactive for demo
            ],
        ];

        foreach ($providers as $providerData) {
            $provider = TrainingProvider::updateOrCreate(
                ['code' => $providerData['code']], // Match by code
                $providerData
            );

            $this->command->line("  ✅ Created/Updated: {$provider->name} ({$provider->code})");
        }

        $totalProviders = TrainingProvider::count();
        $activeProviders = TrainingProvider::where('is_active', true)->count();
        $withAccreditation = TrainingProvider::whereNotNull('accreditation_number')->count();
        $expiringSoon = TrainingProvider::whereBetween('accreditation_expiry', [now(), now()->addDays(90)])->count();

        $this->command->info("📊 TRAINING PROVIDER SUMMARY:");
        $this->command->line("   📋 Total Providers: {$totalProviders}");
        $this->command->line("   ✅ Active Providers: {$activeProviders}");
        $this->command->line("   🛡️ With Accreditation: {$withAccreditation}");
        $this->command->line("   ⚠️ Expiring Soon (90 days): {$expiringSoon}");
        $this->command->info("🎯 Training Provider seeding completed!");
    }

    /**
     * Create enhanced training types with Phase 3 support
     */
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
                // Phase 3 fields - only add if columns exist
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
            [
                'name' => 'Dangerous Goods Awareness',
                'code' => 'DG',
                'category' => 'Aviation',
                'validity_months' => 24,
                'description' => 'Handling and transportation of dangerous goods by air',
                'is_active' => true,
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
            [
                'name' => 'Equipment Operation Training',
                'code' => 'EQP',
                'category' => 'Technical',
                'validity_months' => 18,
                'description' => 'Safe operation of ground support equipment',
                'is_active' => true,
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
                'name' => 'MPGA Compliance Training',
                'code' => 'MPGA',
                'category' => 'Compliance',
                'validity_months' => 12,
                'description' => 'PT. Gapura Angkasa standard operating procedures and compliance',
                'is_active' => true,
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
                'name' => 'Customer Service Excellence',
                'code' => 'CS',
                'category' => 'Service',
                'validity_months' => 24,
                'description' => 'Customer service standards and communication skills',
                'is_active' => true,
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
            ]
        ];

        foreach ($trainingTypes as $typeData) {
            // Filter out Phase 3 fields if columns don't exist
            $filteredData = $this->filterTrainingTypeData($typeData);

            $type = TrainingType::updateOrCreate(
                ['code' => $typeData['code']], // Find by code
                $filteredData // Update with filtered data
            );

            $validityPeriod = $type->validity_period_months ?? $type->validity_months;
            $mandatory = isset($type->is_mandatory) ? ($type->is_mandatory ? 'MANDATORY' : 'Optional') : 'Standard';

            $this->command->line("  ✅ {$type->name} ({$type->code}) - {$validityPeriod} months - {$mandatory}");
        }
    }

    /**
     * Filter training type data based on existing columns
     */
    private function filterTrainingTypeData(array $data): array
    {
        $columns = Schema::getColumnListing('training_types');

        return array_filter($data, function($key) use ($columns) {
            return in_array($key, $columns);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Create sample employees
     */
    private function createSampleEmployees()
    {
        $this->command->info('👥 Creating sample employees...');

        // Check if departments exist
        $hasDepartments = Schema::hasTable('departments') && Department::count() > 0;

        if ($hasDepartments) {
            $departments = Department::all();
        }

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

            // Additional General Staff
            ['GAP014', 'Hendra Gunawan', 'GSE Manager', 'OPS'],
            ['GAP015', 'Fitri Handayani', 'Equipment Operator', 'OPS'],
            ['GAP016', 'Joko Susilo', 'Maintenance Technician', 'OPS'],
            ['GAP017', 'Ratna Sari', 'Customer Service Manager', 'OPS'],
            ['GAP018', 'Andi Kurniawan', 'Customer Service Officer', 'OPS'],
            ['GAP019', 'Lisa Permata', 'Passenger Service Agent', 'OPS'],
            ['GAP020', 'Fajar Nugroho', 'Finance Manager', 'OPS'],
            ['GAP021', 'Indah Permatasari', 'Accountant', 'OPS'],
            ['GAP022', 'Wahyu Setiadi', 'Maintenance Manager', 'OPS'],
            ['GAP023', 'Sinta Dewi', 'QA Inspector', 'OPS'],
            ['GAP024', 'Rico Atmaja', 'Procurement Officer', 'OPS'],
        ];

        foreach ($employees as $empData) {
            $employeeData = [
                'employee_id' => $empData[0],
                'name' => $empData[1],
                'position' => $empData[2],
                'status' => 'active',
                'hire_date' => Carbon::now()->subDays(rand(30, 365 * 3)),
                'background_check_date' => Carbon::now()->subDays(rand(10, 100)),
                'background_check_status' => 'cleared'
            ];

            // Add department if exists
            if ($hasDepartments) {
                $department = $departments->where('code', $empData[3])->first();
                if ($department) {
                    $employeeData['department_id'] = $department->id;
                }
            }

            $employee = Employee::updateOrCreate(
                ['employee_id' => $empData[0]],
                $employeeData
            );

            $dept = $hasDepartments && isset($department) ? " ({$empData[3]})" : '';
            $this->command->line("  ✅ {$employee->employee_id}: {$employee->name}{$dept}");
        }
    }

    /**
     * Create sample training records with realistic data
     */
    private function createSampleTrainingRecords()
    {
        $this->command->info('📚 Creating sample training records with realistic data...');

        $employees = Employee::all();
        $trainingTypes = TrainingType::all();
        $providers = TrainingProvider::all();

        if ($employees->isEmpty() || $trainingTypes->isEmpty() || $providers->isEmpty()) {
            $this->command->warn('⚠️  Missing required data (employees, training types, or providers). Skipping training records creation.');
            return;
        }

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
            // Each employee gets 2-4 random training records
            $numTrainings = rand(2, 4);
            $selectedTypes = $trainingTypes->random($numTrainings);

            foreach ($selectedTypes as $trainingType) {
                // Generate realistic dates
                $issueDate = $this->generateRealisticIssueDate(rand(0, 2));
                $completionDate = $issueDate->copy()->addDays(rand(0, 7));

                // Calculate expiry date using training type validity
                $validityMonths = $trainingType->validity_period_months ?? $trainingType->validity_months;
                $expiryDate = $issueDate->copy()->addMonths($validityMonths);

                // Generate certificate number
                $certificateNumber = $this->statusService->generateCertificateNumber($employee, $trainingType);

                // Calculate status using service
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

        $this->command->line("  ✅ Successfully created {$recordCount} training records");
    }

    /**
     * Seed Phase 3 features
     */
    private function seedPhase3Features()
    {
        $this->command->info('🎯 Setting up Phase 3 features...');

        // Check if Phase 3 models exist
        if (class_exists(\App\Models\TrainingTypeDepartmentRequirement::class) && Schema::hasTable('departments')) {
            try {
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

    /**
     * Create department-specific requirements
     */
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
            'GSE' => ['Fire Safety Training', 'First Aid Training', 'Occupational Health & Safety', 'Equipment Operation Training', 'MPGA Compliance Training'],
            'SEC' => ['Fire Safety Training', 'First Aid Training', 'Occupational Health & Safety', 'Aviation Security Training', 'MPGA Compliance Training'],
            'CR' => ['Fire Safety Training', 'Occupational Health & Safety', 'Customer Service Excellence', 'MPGA Compliance Training'],
        ];

        foreach ($departments as $department) {
            $deptRequiredTrainings = $requirements[$department->code] ?? ['Fire Safety Training', 'MPGA Compliance Training'];

            foreach ($deptRequiredTrainings as $trainingName) {
                $trainingType = $trainingTypes->where('name', $trainingName)->first();

                if ($trainingType) {
                    \App\Models\TrainingTypeDepartmentRequirement::updateOrCreate([
                        'training_type_id' => $trainingType->id,
                        'department_id' => $department->id
                    ], [
                        'is_required' => true,
                        'frequency_months' => $trainingType->validity_period_months ?? $trainingType->validity_months,
                        'target_compliance_rate' => $trainingType->compliance_target_percentage ?? 95.00,
                        'department_specific_requirements' => "Required for all {$department->name} staff"
                    ]);
                }
            }

            $this->command->line("  ✅ Set requirements for {$department->name}");
        }
    }

    /**
     * Generate initial statistics
     */
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

    /**
     * Generate realistic issue date based on record index
     */
    private function generateRealisticIssueDate(int $recordIndex): Carbon
    {
        $baseDate = match ($recordIndex) {
            0 => Carbon::now()->subMonths(rand(18, 36)),
            1 => Carbon::now()->subMonths(rand(6, 18)),
            default => Carbon::now()->subMonths(rand(1, 12))
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
     * Print comprehensive system summary
     */
    private function printFinalSummary()
    {
        $this->command->info('📊 FINAL SYSTEM SUMMARY:');

        try {
            $stats = [
                'Users' => User::count(),
                'Training Providers' => TrainingProvider::count(),
                'Active Providers' => TrainingProvider::where('is_active', true)->count(),
                'Training Types' => TrainingType::count(),
                'Employees' => Employee::count(),
                'Training Records' => TrainingRecord::count(),
                'Active Certificates' => TrainingRecord::where('compliance_status', 'compliant')->count(),
                'Expiring Soon' => TrainingRecord::where('compliance_status', 'expiring_soon')->count(),
                'Expired Certificates' => TrainingRecord::where('compliance_status', 'expired')->count(),
            ];

            // Add departments if table exists
            if (Schema::hasTable('departments')) {
                $stats = array_merge(['Departments' => Department::count()], $stats);
            }

            // Add Phase 3 stats if available
            if (class_exists(\App\Models\TrainingTypeDepartmentRequirement::class) && Schema::hasTable('training_type_department_requirements')) {
                $stats['Department Requirements'] = \App\Models\TrainingTypeDepartmentRequirement::count();
            }

            foreach ($stats as $label => $count) {
                $this->command->line("   📈 {$label}: {$count}");
            }

            // Calculate compliance rate
            $totalRecords = $stats['Training Records'];
            if ($totalRecords > 0) {
                $complianceRate = round(($stats['Active Certificates'] / $totalRecords) * 100, 2);
                $this->command->info("   🎯 Overall Compliance Rate: {$complianceRate}%");
            }

        } catch (\Exception $e) {
            $this->command->error("❌ Could not generate summary: {$e->getMessage()}");
        }

        $this->command->newLine();
        $this->command->info('🎉 Gapura Training Management System is ready!');
        $this->command->info('🌐 You can now access the system via web interface');

        // Show login info
        $this->command->comment('👤 Default login credentials:');
        $this->command->line('   Email: admin@gapura.com');
        $this->command->line('   Password: password');

        $this->command->newLine();
        $this->command->info('🚀 Next steps:');
        $this->command->line('   • Run: php artisan serve');
        $this->command->line('   • Visit: http://localhost:8000');
        $this->command->line('   • Check: /training-providers for provider management');
        $this->command->line('   • Import: Your MPGA Excel data via Import/Export menu');
    }
}
