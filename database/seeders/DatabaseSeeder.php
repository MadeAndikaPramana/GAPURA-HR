<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Department;
use App\Models\TrainingType;
use App\Models\TrainingProvider;
use App\Models\Employee;
use App\Models\TrainingRecord;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Comprehensive MPGA Training System Seeding...');
        $this->command->info('=====================================================');

        DB::beginTransaction();

        try {
            // 1. Create Admin Users
            $this->createAdminUsers();

            // 2. Create Departments
            $departments = $this->createDepartments();

            // 3. Create Training Providers (Fixed structure)
            $providers = $this->createTrainingProviders();

            // 4. Create Training Types (Simplified structure)
            $trainingTypes = $this->createTrainingTypes();

            // 5. Create 5 Comprehensive Employees
            $employees = $this->createComprehensiveEmployees($departments);

            // 6. Create Training Records
            $this->createTrainingRecords($employees, $trainingTypes, $providers);

            DB::commit();

            $this->command->info('✅ Comprehensive seeding completed successfully!');
            $this->printFinalSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Create admin users
     */
    private function createAdminUsers()
    {
        $this->command->info('👤 Creating admin users...');

        // Super Admin
        User::updateOrCreate(
            ['email' => 'admin@gapura.com'],
            [
                'name' => 'Super Administrator',
                'email' => 'admin@gapura.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // HR Admin
        User::updateOrCreate(
            ['email' => 'hr@gapura.com'],
            [
                'name' => 'HR Administrator',
                'email' => 'hr@gapura.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->line('  ✅ Created 2 admin users');
    }

    /**
     * Create departments
     */
    private function createDepartments()
    {
        $this->command->info('🏢 Creating departments...');

        $departments = [
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Human Resources and Administration Department'
            ],
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'Information Technology and Systems Department'
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Ground Operations and Services Department'
            ],
            [
                'name' => 'Ground Support Equipment',
                'code' => 'GSE',
                'description' => 'Ground Support Equipment and Maintenance Department'
            ],
            [
                'name' => 'Security',
                'code' => 'SEC',
                'description' => 'Aviation Security and Safety Department'
            ]
        ];

        $createdDepartments = collect();

        foreach ($departments as $deptData) {
            $dept = Department::updateOrCreate(
                ['code' => $deptData['code']],
                $deptData
            );
            $createdDepartments->push($dept);
            $this->command->line("  📋 {$dept->name} ({$dept->code})");
        }

        return $createdDepartments;
    }

    /**
     * Create training providers with correct table structure
     */
    private function createTrainingProviders()
    {
        $this->command->info('🎓 Creating training providers...');

        $providers = [
            [
                'name' => 'PT Gapura Training Center',
                'code' => 'GTC',
                'contact_person' => 'Ahmad Suryanto',
                'email' => 'training@gapura.com',
                'phone' => '+62-21-5505-5000',
                'address' => 'Soekarno-Hatta International Airport, Tangerang',
                'website' => 'https://training.gapura.com',
                'accreditation_number' => 'ISO-9001-2015-001',
                'notes' => 'Internal training center of PT Gapura Angkasa. ISO 9001:2015 and ICAO certified.',
                'is_active' => true
            ],
            [
                'name' => 'International Safety Training Institute',
                'code' => 'ISTI',
                'contact_person' => 'Maria Gonzales',
                'email' => 'info@isti.com',
                'phone' => '+62-21-8750-3000',
                'address' => 'Jakarta Aviation Training Center, Kemayoran',
                'website' => 'https://isti.com',
                'accreditation_number' => 'IATA-CERT-2023-045',
                'notes' => 'Aviation safety and security training specialist. IATA certified and CAA approved.',
                'is_active' => true
            ],
            [
                'name' => 'TechPro Academy',
                'code' => 'TPA',
                'contact_person' => 'Budi Hartono',
                'email' => 'academy@techpro.id',
                'phone' => '+62-21-2950-7000',
                'address' => 'Kemayoran Business District, Jakarta Pusat',
                'website' => 'https://techpro.academy',
                'accreditation_number' => 'MSFT-GOLD-2024-012',
                'notes' => 'Technical and IT training solutions. Microsoft certified partner and Oracle authorized training center.',
                'is_active' => true
            ]
        ];

        $createdProviders = collect();

        foreach ($providers as $providerData) {
            $provider = TrainingProvider::updateOrCreate(
                ['code' => $providerData['code']],
                $providerData
            );
            $createdProviders->push($provider);
            $this->command->line("  🏛️  {$provider->name} ({$provider->code})");
        }

        return $createdProviders;
    }

    /**
     * Create training types with basic structure only
     */
    private function createTrainingTypes()
    {
        $this->command->info('📚 Creating training types...');

        $trainingTypes = [
            [
                'name' => 'Fire Safety Training',
                'code' => 'FIRE-SAFETY',
                'category' => 'Safety',
                'description' => 'Fire prevention, suppression, and emergency evacuation procedures',
                'validity_months' => 12,
                'is_active' => true
            ],
            [
                'name' => 'First Aid Training',
                'code' => 'FIRST-AID',
                'category' => 'Safety',
                'description' => 'Basic first aid and emergency medical response training',
                'validity_months' => 24,
                'is_active' => true
            ],
            [
                'name' => 'Occupational Health & Safety',
                'code' => 'OHS',
                'category' => 'Safety',
                'description' => 'Workplace health and safety standards and procedures',
                'validity_months' => 12,
                'is_active' => true
            ],
            [
                'name' => 'Aviation Security Training',
                'code' => 'AVSEC',
                'category' => 'Security',
                'description' => 'Aviation security protocols and threat assessment',
                'validity_months' => 12,
                'is_active' => true
            ],
            [
                'name' => 'Ground Handling Training',
                'code' => 'GROUND-HANDLING',
                'category' => 'Aviation',
                'description' => 'Aircraft ground handling operations and safety procedures',
                'validity_months' => 18,
                'is_active' => true
            ],
            [
                'name' => 'Equipment Operation Training',
                'code' => 'EQUIPMENT-OPS',
                'category' => 'Technical',
                'description' => 'Ground support equipment operation and maintenance',
                'validity_months' => 24,
                'is_active' => true
            ],
            [
                'name' => 'IT Security Awareness',
                'code' => 'IT-SECURITY',
                'category' => 'Technical',
                'description' => 'Information security and cyber security awareness',
                'validity_months' => 12,
                'is_active' => true
            ],
            [
                'name' => 'Customer Service Excellence',
                'code' => 'CUSTOMER-SERVICE',
                'category' => 'Service',
                'description' => 'Customer service standards and communication skills',
                'validity_months' => 24,
                'is_active' => true
            ],
            [
                'name' => 'MPGA Compliance Training',
                'code' => 'MPGA-COMPLIANCE',
                'category' => 'Compliance',
                'description' => 'PT Gapura Angkasa operational procedures and compliance standards',
                'validity_months' => 12,
                'is_active' => true
            ],
            [
                'name' => 'Dangerous Goods Awareness',
                'code' => 'DG-AWARENESS',
                'category' => 'Aviation',
                'description' => 'Dangerous goods handling and transportation regulations',
                'validity_months' => 24,
                'is_active' => true
            ]
        ];

        $createdTypes = collect();

        foreach ($trainingTypes as $typeData) {
            $type = TrainingType::updateOrCreate(
                ['code' => $typeData['code']],
                $typeData
            );
            $createdTypes->push($type);
            $this->command->line("  🎯 {$type->name} ({$type->code}) - {$type->validity_months} months");
        }

        return $createdTypes;
    }

    /**
     * Create 5 comprehensive employees with all fields
     */
    private function createComprehensiveEmployees($departments)
    {
        $this->command->info('👥 Creating 5 comprehensive employees with all fields...');

        $employees = [
            [
                'employee_id' => 'MPGA-HR-001',
                'name' => 'Ahmad Suryanto',
                'email' => 'ahmad.suryanto@gapura.com',
                'phone' => '+62-812-3456-7890',
                'department_id' => $departments->where('code', 'HR')->first()->id,
                'position' => 'HR Manager',
                'position_level' => 'Manager',
                'employment_type' => 'Full Time',
                'hire_date' => '2020-01-15',
                'supervisor_id' => null, // Manager level
                'status' => 'active',
                'background_check_date' => '2019-12-10',
                'background_check_status' => 'cleared',
                'background_check_notes' => 'Background verification completed successfully. All references verified.',
                'emergency_contact_name' => 'Siti Suryanto',
                'emergency_contact_phone' => '+62-813-9876-5432',
                'address' => 'Jl. Sudirman No. 123, Tangerang Selatan, Banten 15145',
                'profile_photo_path' => null
            ],
            [
                'employee_id' => 'MPGA-IT-002',
                'name' => 'Nina Sari Dewi',
                'email' => 'nina.dewi@gapura.com',
                'phone' => '+62-821-7654-3210',
                'department_id' => $departments->where('code', 'IT')->first()->id,
                'position' => 'Senior System Administrator',
                'position_level' => 'Senior Staff',
                'employment_type' => 'Full Time',
                'hire_date' => '2021-03-22',
                'supervisor_id' => null, // Will be set to first employee later
                'status' => 'active',
                'background_check_date' => '2021-02-15',
                'background_check_status' => 'cleared',
                'background_check_notes' => 'IT security clearance obtained. Technical competency verified.',
                'emergency_contact_name' => 'Budi Dewi',
                'emergency_contact_phone' => '+62-822-1111-2222',
                'address' => 'Jl. Gatot Subroto No. 456, Jakarta Selatan 12930',
                'profile_photo_path' => null
            ],
            [
                'employee_id' => 'MPGA-OPS-003',
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@gapura.com',
                'phone' => '+62-831-5678-9012',
                'department_id' => $departments->where('code', 'OPS')->first()->id,
                'position' => 'Ground Operations Supervisor',
                'position_level' => 'Supervisor',
                'employment_type' => 'Full Time',
                'hire_date' => '2019-08-10',
                'supervisor_id' => null, // Will be set to first employee later
                'status' => 'active',
                'background_check_date' => '2019-07-20',
                'background_check_status' => 'cleared',
                'background_check_notes' => 'Aviation security background check completed. Operations experience verified.',
                'emergency_contact_name' => 'Ani Santoso',
                'emergency_contact_phone' => '+62-832-3333-4444',
                'address' => 'Jl. Soekarno-Hatta No. 789, Cengkareng, Jakarta Barat 11720',
                'profile_photo_path' => null
            ],
            [
                'employee_id' => 'MPGA-GSE-004',
                'name' => 'Rina Kusuma',
                'email' => 'rina.kusuma@gapura.com',
                'phone' => '+62-841-2345-6789',
                'department_id' => $departments->where('code', 'GSE')->first()->id,
                'position' => 'Equipment Maintenance Technician',
                'position_level' => 'Staff',
                'employment_type' => 'Full Time',
                'hire_date' => '2022-05-16',
                'supervisor_id' => null, // Will be set to Budi Santoso later
                'status' => 'active',
                'background_check_date' => '2022-04-25',
                'background_check_status' => 'cleared',
                'background_check_notes' => 'Technical certification verified. Safety training records up to date.',
                'emergency_contact_name' => 'Agus Kusuma',
                'emergency_contact_phone' => '+62-842-5555-6666',
                'address' => 'Jl. Raya Airport No. 321, Tangerang, Banten 15125',
                'profile_photo_path' => null
            ],
            [
                'employee_id' => 'MPGA-SEC-005',
                'name' => 'Dedi Kurniawan',
                'email' => 'dedi.kurniawan@gapura.com',
                'phone' => '+62-851-8765-4321',
                'department_id' => $departments->where('code', 'SEC')->first()->id,
                'position' => 'Aviation Security Officer',
                'position_level' => 'Staff',
                'employment_type' => 'Full Time',
                'hire_date' => '2023-01-08',
                'supervisor_id' => null, // Will be set to Ahmad Suryanto later
                'status' => 'active',
                'background_check_date' => '2022-12-15',
                'background_check_status' => 'cleared',
                'background_check_notes' => 'Security clearance level 3 obtained. AVSEC certification current.',
                'emergency_contact_name' => 'Maya Kurniawan',
                'emergency_contact_phone' => '+62-852-7777-8888',
                'address' => 'Jl. Pajajaran No. 654, Bogor, Jawa Barat 16143',
                'profile_photo_path' => null
            ]
        ];

        $createdEmployees = collect();

        foreach ($employees as $empData) {
            $empData['hire_date'] = Carbon::parse($empData['hire_date']);
            $empData['background_check_date'] = Carbon::parse($empData['background_check_date']);

            $employee = Employee::updateOrCreate(
                ['employee_id' => $empData['employee_id']],
                $empData
            );

            $createdEmployees->push($employee);

            $this->command->line("  👤 {$employee->name} ({$employee->employee_id}) - {$employee->position}");
            $this->command->line("      📧 {$employee->email}");
            $this->command->line("      🏢 {$employee->department->name}");
            $this->command->line("      📅 Hired: {$employee->hire_date->format('d M Y')}");
            $this->command->line("      🏠 {$employee->address}");
            $this->command->newLine();
        }

        // Set up supervisor relationships
        $ahmad = $createdEmployees->where('employee_id', 'MPGA-HR-001')->first();

        // Ahmad supervises Nina and Dedi
        $createdEmployees->where('employee_id', 'MPGA-IT-002')->first()->update(['supervisor_id' => $ahmad->id]);
        $createdEmployees->where('employee_id', 'MPGA-SEC-005')->first()->update(['supervisor_id' => $ahmad->id]);

        // Budi supervises Rina
        $budi = $createdEmployees->where('employee_id', 'MPGA-OPS-003')->first();
        $createdEmployees->where('employee_id', 'MPGA-GSE-004')->first()->update(['supervisor_id' => $budi->id]);

        $this->command->info('  ✅ Supervisor relationships established');

        return $createdEmployees;
    }

    /**
     * Create training records with simplified status
     */
    private function createTrainingRecords($employees, $trainingTypes, $providers)
    {
        $this->command->info('📋 Creating training records with simplified status...');

        $recordsCreated = 0;

        foreach ($employees as $employee) {
            // Each employee gets 3-5 different training records
            $selectedTrainingTypes = $trainingTypes->random(rand(3, 5));

            foreach ($selectedTrainingTypes as $trainingType) {
                // Create 1-2 records per training type (original + renewal)
                $recordsPerType = rand(1, 2);

                for ($i = 0; $i < $recordsPerType; $i++) {
                    // Generate realistic dates
                    $issueDate = $this->generateRealisticIssueDate($i);
                    $completionDate = $issueDate->copy()->addDays(rand(0, 5));

                    // Calculate expiry date
                    $expiryDate = null;
                    if ($trainingType->validity_months) {
                        $expiryDate = $issueDate->copy()->addMonths($trainingType->validity_months);
                    }

                    // Generate certificate number
                    $certificateNumber = $this->generateCertificateNumber($trainingType, $issueDate);

                    // Calculate simplified status
                    $status = $this->calculateSimpleStatus($expiryDate);

                    // Create training record
                    $record = TrainingRecord::create([
                        'employee_id' => $employee->id,
                        'training_type_id' => $trainingType->id,
                        'training_provider_id' => $providers->random()->id,
                        'certificate_number' => $certificateNumber,
                        'issuer' => $providers->random()->name,
                        'issue_date' => $issueDate->format('Y-m-d'),
                        'completion_date' => $completionDate->format('Y-m-d'),
                        'expiry_date' => $expiryDate?->format('Y-m-d'),
                        'training_date' => $issueDate->copy()->subDays(rand(1, 3))->format('Y-m-d'),
                        'status' => $status,
                        'compliance_status' => $status, // Sync for backward compatibility
                        'batch_number' => 'BATCH-' . $issueDate->format('Ym') . '-' . str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT),
                        'score' => rand(75, 98),
                        'passing_score' => $trainingType->category === 'Safety' ? 80 : 70,
                        'training_hours' => rand(8, 24),
                        'cost' => rand(500000, 3000000),
                        'location' => $this->getTrainingLocation(),
                        'instructor_name' => $this->getInstructorName(),
                        'notes' => rand(1, 10) > 7 ? $this->generateTrainingNotes($trainingType) : null,
                        'reminder_sent_at' => null,
                        'reminder_count' => 0,
                        'created_by_id' => 1,
                        'updated_by_id' => null,
                        'created_at' => $issueDate,
                        'updated_at' => $issueDate
                    ]);

                    $recordsCreated++;
                }
            }
        }

        $this->command->line("  ✅ Created {$recordsCreated} training records");

        // Create certificates for training records
        $this->createCertificates();

        $this->showTrainingStatistics();
    }

    /**
     * Create certificates based on training records
     */
    private function createCertificates()
    {
        $this->command->info('🏆 Creating certificates from training records...');

        if (!class_exists(\App\Models\Certificate::class)) {
            $this->command->warn('  ⚠️  Certificate model not found, skipping certificate creation');
            return;
        }

        $trainingRecords = TrainingRecord::with(['employee', 'trainingType', 'trainingProvider'])->get();
        $certificatesCreated = 0;

        foreach ($trainingRecords as $record) {
            // Create certificate for this training record
            try {
                $certificate = \App\Models\Certificate::create([
                    'training_record_id' => $record->id,
                    'training_type_id' => $record->training_type_id,
                    'employee_id' => $record->employee_id,
                    'training_provider_id' => $record->training_provider_id,
                    'certificate_number' => $record->certificate_number,
                    'verification_code' => $this->generateVerificationCode(),
                    'issued_by' => $record->trainingProvider->name ?? $record->issuer,
                    'issue_date' => $record->issue_date,
                    'expiry_date' => $record->expiry_date,
                    'status' => $record->status,
                    'notes' => $record->notes,
                    'created_by_id' => 1,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at
                ]);

                $certificatesCreated++;

                // Progress indicator
                if ($certificatesCreated % 10 === 0) {
                    $this->command->line("  📄 Created {$certificatesCreated} certificates...");
                }

            } catch (\Exception $e) {
                $this->command->warn("  ⚠️  Failed to create certificate for record {$record->id}: " . $e->getMessage());
            }
        }

        $this->command->line("  ✅ Created {$certificatesCreated} certificates");
    }

    /**
     * Generate verification code for certificates
     */
    private function generateVerificationCode(): string
    {
        do {
            $code = strtoupper(\Str::random(8));
        } while (class_exists(\App\Models\Certificate::class) && \App\Models\Certificate::where('verification_code', $code)->exists());

        return $code;
    }

    /**
     * Generate realistic issue date
     */
    private function generateRealisticIssueDate($recordIndex): Carbon
    {
        $baseDate = match($recordIndex) {
            0 => Carbon::now()->subMonths(rand(6, 24)), // Original: 6-24 months ago
            default => Carbon::now()->subMonths(rand(1, 12)) // Renewal: 1-12 months ago
        };

        return $baseDate->addDays(rand(-15, 15));
    }

    /**
     * Generate certificate number
     */
    private function generateCertificateNumber(TrainingType $trainingType, Carbon $issueDate): string
    {
        $prefix = substr($trainingType->code, 0, 3);
        $year = $issueDate->format('Y');
        $month = $issueDate->format('m');
        $sequence = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}{$month}-{$sequence}";
    }

    /**
     * Calculate simple status (active/expired only)
     */
    private function calculateSimpleStatus($expiryDate): string
    {
        if (!$expiryDate) {
            return 'active'; // No expiry means permanent
        }

        $now = Carbon::now()->startOfDay();
        $expiry = Carbon::parse($expiryDate)->startOfDay();

        return $expiry->lt($now) ? 'expired' : 'active';
    }

    /**
     * Get random training location
     */
    private function getTrainingLocation(): string
    {
        $locations = [
            'Gapura Training Center - Soekarno Hatta',
            'Jakarta Aviation Training Institute',
            'Online Training Platform',
            'Tangerang Corporate Training Center',
            'Head Office Conference Room A',
            'Regional Training Facility - Jakarta'
        ];

        return $locations[array_rand($locations)];
    }

    /**
     * Get random instructor name
     */
    private function getInstructorName(): string
    {
        $instructors = [
            'Capt. Ahmad Wijaya',
            'Ir. Sari Indrawati',
            'Dr. Budi Hartono',
            'Prof. Maya Kusuma',
            'Eng. Rizki Pratama',
            'Ms. Linda Chen'
        ];

        return $instructors[array_rand($instructors)];
    }

    /**
     * Generate training notes based on category
     */
    private function generateTrainingNotes(TrainingType $trainingType): string
    {
        $notes = [
            'Safety' => [
                'Excellent understanding of safety procedures demonstrated.',
                'Requires refresher on emergency evacuation protocols.',
                'Outstanding performance in practical safety drills.'
            ],
            'Security' => [
                'Strong grasp of security protocols and threat assessment.',
                'Demonstrated proficiency in security equipment operation.',
                'Recommended for advanced security training.'
            ],
            'Technical' => [
                'Technical competency successfully demonstrated.',
                'Requires additional practice on equipment maintenance.',
                'Excellent troubleshooting skills displayed.'
            ],
            'Aviation' => [
                'Comprehensive understanding of aviation operations.',
                'Strong performance in ground handling procedures.',
                'Recommended for specialized aviation training.'
            ],
            'default' => [
                'Training objectives successfully completed.',
                'Good performance throughout training program.',
                'Demonstrated competency in all required areas.'
            ]
        ];

        $categoryNotes = $notes[$trainingType->category] ?? $notes['default'];
        return $categoryNotes[array_rand($categoryNotes)];
    }

    /**
     * Show training statistics
     */
    private function showTrainingStatistics()
    {
        $total = TrainingRecord::count();
        $active = TrainingRecord::where('status', 'active')->count();
        $expired = TrainingRecord::where('status', 'expired')->count();

        // Calculate expiring soon (active but expiring within 30 days)
        $expiringSoon = TrainingRecord::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', Carbon::now()->addDays(30))
            ->count();

        $this->command->newLine();
        $this->command->info('📊 Training Records Summary:');
        $this->command->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Total Records', $total, '100%'],
                ['Active Certificates', $active, $total > 0 ? round(($active/$total)*100, 1).'%' : '0%'],
                ['Expired Certificates', $expired, $total > 0 ? round(($expired/$total)*100, 1).'%' : '0%'],
                ['Expiring Soon (30 days)', $expiringSoon, $total > 0 ? round(($expiringSoon/$total)*100, 1).'%' : '0%']
            ]
        );
    }

    /**
     * Print final summary
     */
    private function printFinalSummary()
    {
        $this->command->newLine();
        $this->command->info('🎉 COMPREHENSIVE SEEDING COMPLETED!');
        $this->command->info('=====================================');

        $stats = [
            'Users' => User::count(),
            'Departments' => Department::count(),
            'Training Providers' => TrainingProvider::count(),
            'Training Types' => TrainingType::count(),
            'Employees (Comprehensive)' => Employee::count(),
            'Training Records' => TrainingRecord::count(),
            'Active Training Records' => TrainingRecord::where('status', 'active')->count(),
            'Expired Training Records' => TrainingRecord::where('status', 'expired')->count()
        ];

        // Add certificate stats if Certificate model exists
        if (class_exists(\App\Models\Certificate::class)) {
            $stats['Certificates'] = \App\Models\Certificate::count();
            $stats['Active Certificates'] = \App\Models\Certificate::where('status', 'active')->count();
            $stats['Expired Certificates'] = \App\Models\Certificate::where('status', 'expired')->count();
        }

        foreach ($stats as $label => $count) {
            $this->command->line("  📈 {$label}: {$count}");
        }

        $this->command->newLine();
        $this->command->info('🔐 Admin Credentials:');
        $this->command->line('  📧 admin@gapura.com / password');
        $this->command->line('  📧 hr@gapura.com / password');

        $this->command->newLine();
        $this->command->info('👥 Sample Employees Created:');
        $employees = Employee::with('department')->get();
        foreach ($employees as $emp) {
            $this->command->line("  🧑‍💼 {$emp->name} ({$emp->employee_id}) - {$emp->position} @ {$emp->department->name}");
        }

        // Show certificate summary if available
        if (class_exists(\App\Models\Certificate::class)) {
            $this->command->newLine();
            $this->command->info('🏆 Certificate Summary:');

            $total = \App\Models\Certificate::count();
            $active = \App\Models\Certificate::where('status', 'active')->count();
            $expired = \App\Models\Certificate::where('status', 'expired')->count();

            $this->command->table(
                ['Status', 'Count', 'Percentage'],
                [
                    ['Total Certificates', $total, '100%'],
                    ['Active Certificates', $active, $total > 0 ? round(($active/$total)*100, 1).'%' : '0%'],
                    ['Expired Certificates', $expired, $total > 0 ? round(($expired/$total)*100, 1).'%' : '0%'],
                ]
            );
        }

        $this->command->newLine();
        $this->command->info('🚀 System is ready for use!');
        $this->command->info('   • Dashboard: /dashboard');
        $this->command->info('   • Training Records: /training-records');
        $this->command->info('   • Employees: /employees');
        $this->command->info('   • Certificates: /certificates');
    }
}
