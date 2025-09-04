<?php
// database/seeders/DatabaseSeeder.php - Updated with Certificate Types

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Department;
use App\Models\Employee;
use App\Models\CertificateType;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Gapura Employee Container System Database Seeding...');
        $this->command->info('====================================================================');

        try {
            // Create admin users
            $this->createAdminUsers();

            // Create departments
            $this->createDepartments();

            // Create certificate types for container system
            $this->createCertificateTypes();

            // Create sample employees
            $this->createSampleEmployees();

            // Show completion summary
            $this->showCompletionSummary();

        } catch (\Exception $e) {
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create admin users
     */
    protected function createAdminUsers(): void
    {
        $this->command->info('👤 Creating admin users...');

        $users = [
            [
                'name' => 'GAPURA Super Admin',
                'email' => 'admin@gapura.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'HR Manager',
                'email' => 'hr@gapura.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->line('  ✅ Created ' . count($users) . ' admin users');
    }

    /**
     * Create departments
     */
    protected function createDepartments(): void
    {
        $this->command->info('🏢 Creating departments...');

        $departments = [
            [
                'code' => 'HR',
                'name' => 'Human Resources',
                'description' => 'Human Resources Department',
                'is_active' => true
            ],
            [
                'code' => 'GSE',
                'name' => 'Ground Support Equipment',
                'description' => 'Ground Support Equipment Operations',
                'is_active' => true
            ],
            [
                'code' => 'OPS',
                'name' => 'Operations',
                'description' => 'Flight Operations Department',
                'is_active' => true
            ],
            [
                'code' => 'MAINT',
                'name' => 'Maintenance',
                'description' => 'Equipment Maintenance Department',
                'is_active' => true
            ],
            [
                'code' => 'SECURITY',
                'name' => 'Security',
                'description' => 'Airport Security Department',
                'is_active' => true
            ],
            [
                'code' => 'CARGO',
                'name' => 'Cargo Handling',
                'description' => 'Cargo and Baggage Handling',
                'is_active' => true
            ]
        ];

        foreach ($departments as $deptData) {
            Department::updateOrCreate(
                ['code' => $deptData['code']],
                $deptData
            );
        }

        $this->command->line('  ✅ Created ' . count($departments) . ' departments');
    }

    /**
     * Create certificate types for Employee Container System
     */
    protected function createCertificateTypes(): void
    {
        $this->command->info('🏆 Creating certificate types for Employee Container System...');

        $certificateTypes = [
            // SAFETY CATEGORY - Critical for airport operations
            [
                'name' => 'Fire Safety Training',
                'code' => 'FIRE_SAFETY',
                'category' => 'Safety',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Comprehensive fire safety training including fire prevention, evacuation procedures, and fire extinguisher operation',
                'requirements' => 'All employees must complete within first 3 months of employment',
                'learning_objectives' => 'Identify fire hazards, operate fire extinguishers, execute evacuation procedures',
                'estimated_cost' => 250000,
                'estimated_duration_hours' => 8,
                'is_active' => true
            ],
            [
                'name' => 'First Aid & CPR Training',
                'code' => 'FIRST_AID',
                'category' => 'Safety',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Basic first aid, CPR, and emergency medical response training',
                'requirements' => 'Mandatory for all ground staff and supervisors',
                'learning_objectives' => 'Provide basic first aid, perform CPR, handle medical emergencies',
                'estimated_cost' => 350000,
                'estimated_duration_hours' => 16,
                'is_active' => true
            ],
            [
                'name' => 'Safety Induction Program',
                'code' => 'SAFETY_INDUCTION',
                'category' => 'Safety',
                'validity_months' => 12,
                'warning_days' => 30,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'General workplace safety induction covering hazard identification, PPE usage, and safety protocols',
                'requirements' => 'Required for all new employees before starting work',
                'learning_objectives' => 'Understand safety policies, use PPE correctly, identify workplace hazards',
                'estimated_cost' => 150000,
                'estimated_duration_hours' => 4,
                'is_active' => true
            ],
            [
                'name' => 'Aviation Security Awareness',
                'code' => 'AVIATION_SECURITY',
                'category' => 'Aviation',
                'validity_months' => 24,
                'warning_days' => 90,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Aviation security awareness training covering AVSEC regulations and threat identification',
                'requirements' => 'Mandatory for all personnel with airside access',
                'learning_objectives' => 'Understand AVSEC regulations, identify security threats, implement security procedures',
                'estimated_cost' => 400000,
                'estimated_duration_hours' => 12,
                'is_active' => true
            ],
            [
                'name' => 'Ground Support Equipment Operation',
                'code' => 'GSE_OPERATION',
                'category' => 'Technical',
                'validity_months' => 60,
                'warning_days' => 120,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Certification for operating ground support equipment including tugs, loaders, and service vehicles',
                'requirements' => 'Required for GSE operators, includes practical assessment',
                'learning_objectives' => 'Safely operate GSE, perform pre-flight checks, follow operational procedures',
                'estimated_cost' => 750000,
                'estimated_duration_hours' => 32,
                'is_active' => true
            ],
            [
                'name' => 'Aircraft Marshalling Certification',
                'code' => 'AIRCRAFT_MARSHALLING',
                'category' => 'Technical',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Training for aircraft marshalling and ground guidance operations',
                'requirements' => 'For ramp personnel involved in aircraft ground movements',
                'learning_objectives' => 'Perform aircraft marshalling, use standard signals, ensure safe aircraft movement',
                'estimated_cost' => 300000,
                'estimated_duration_hours' => 16,
                'is_active' => true
            ],

            // GAPURA SPECIFIC - MPGA Certificate Types
            [
                'name' => 'Aircraft Towing Tractor (ATT)',
                'code' => 'ATT',
                'category' => 'GSE_Operator',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Certificate for operating aircraft towing tractors',
                'requirements' => 'Ground support equipment operators',
                'learning_objectives' => 'Safe operation of aircraft towing equipment',
                'estimated_cost' => 500000,
                'estimated_duration_hours' => 24,
                'is_active' => true
            ],
            [
                'name' => 'Fuel Refueling Machine (FRM)',
                'code' => 'FRM',
                'category' => 'GSE_Operator',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Certificate for operating fuel refueling machines',
                'requirements' => 'Fuel handling personnel',
                'learning_objectives' => 'Safe fuel handling and refueling procedures',
                'estimated_cost' => 600000,
                'estimated_duration_hours' => 32,
                'is_active' => true
            ],
            [
                'name' => 'Low Loader Driver (LLD)',
                'code' => 'LLD',
                'category' => 'GSE_Operator',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Certificate for operating low loader equipment',
                'requirements' => 'Equipment transport operators',
                'learning_objectives' => 'Safe operation of low loader vehicles',
                'estimated_cost' => 400000,
                'estimated_duration_hours' => 20,
                'is_active' => true
            ],
            [
                'name' => 'Belt Conveyor System (BCS)',
                'code' => 'BCS',
                'category' => 'GSE_Operator',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Certificate for operating belt conveyor systems',
                'requirements' => 'Baggage handling personnel',
                'learning_objectives' => 'Safe operation of conveyor systems',
                'estimated_cost' => 300000,
                'estimated_duration_hours' => 16,
                'is_active' => true
            ],

            // MANAGEMENT CATEGORY
            [
                'name' => 'Leadership Development Program',
                'code' => 'LEADERSHIP',
                'category' => 'Management',
                'validity_months' => null, // No expiry
                'warning_days' => null,
                'is_mandatory' => false,
                'is_recurrent' => false,
                'description' => 'Comprehensive leadership development program for supervisors and managers',
                'requirements' => 'For employees in or aspiring to leadership positions',
                'learning_objectives' => 'Develop leadership skills, manage teams effectively, strategic thinking',
                'estimated_cost' => 2000000,
                'estimated_duration_hours' => 40,
                'is_active' => true
            ],
            [
                'name' => 'Environmental Awareness Training',
                'code' => 'ENVIRONMENTAL',
                'category' => 'Compliance',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Environmental awareness and waste management training for airport operations',
                'requirements' => 'All personnel must complete environmental awareness training',
                'learning_objectives' => 'Understand environmental impact, implement waste management, comply with regulations',
                'estimated_cost' => 200000,
                'estimated_duration_hours' => 8,
                'is_active' => true
            ],
            [
                'name' => 'Computer Literacy & IT Security',
                'code' => 'IT_LITERACY',
                'category' => 'Professional',
                'validity_months' => 18,
                'warning_days' => 45,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Basic computer skills and IT security awareness training',
                'requirements' => 'All employees using computer systems',
                'learning_objectives' => 'Use computer systems effectively, understand security protocols, handle data safely',
                'estimated_cost' => 250000,
                'estimated_duration_hours' => 12,
                'is_active' => true
            ]
        ];

        $created = 0;
        $updated = 0;

        foreach ($certificateTypes as $typeData) {
            try {
                $certificateType = CertificateType::updateOrCreate(
                    ['code' => $typeData['code']],
                    $typeData
                );

                if ($certificateType->wasRecentlyCreated) {
                    $created++;
                    $this->command->line("  ✅ Created: {$typeData['name']} ({$typeData['code']})");
                } else {
                    $updated++;
                    $this->command->line("  🔄 Updated: {$typeData['name']} ({$typeData['code']})");
                }
            } catch (\Exception $e) {
                $this->command->error("  ❌ Failed to create {$typeData['name']}: " . $e->getMessage());
            }
        }

        $this->command->info("  📊 Certificate Types: {$created} created, {$updated} updated");
    }

    /**
     * Create sample employees
     */
    protected function createSampleEmployees(): void
    {
        $this->command->info('👥 Creating sample employees...');

        $gseDept = Department::where('code', 'GSE')->first();
        $opsDept = Department::where('code', 'OPS')->first();

        if ($gseDept && $opsDept) {
            $employees = [
                [
                    'employee_id' => 'MPGA-GSE-001',
                    'name' => 'Rina Kusuma',
                    'email' => 'rina.kusuma@gapura.com',
                    'department_id' => $gseDept->id,
                    'position' => 'Equipment Maintenance Technician',
                    'hire_date' => Carbon::parse('2020-03-15'),
                    'status' => 'active',
                    'background_check_status' => 'cleared',
                    'background_check_date' => Carbon::parse('2020-02-01')
                ],
                [
                    'employee_id' => 'MPGA-OPS-001',
                    'name' => 'Ahmad Suryanto',
                    'email' => 'ahmad.suryanto@gapura.com',
                    'department_id' => $opsDept->id,
                    'position' => 'Ground Operations Supervisor',
                    'hire_date' => Carbon::parse('2018-07-10'),
                    'status' => 'active',
                    'background_check_status' => 'cleared',
                    'background_check_date' => Carbon::parse('2018-06-15')
                ]
            ];

            foreach ($employees as $empData) {
                Employee::updateOrCreate(
                    ['employee_id' => $empData['employee_id']],
                    $empData
                );
            }

            $this->command->line('  ✅ Created ' . count($employees) . ' sample employees');
        }
    }

    /**
     * Show completion summary
     */
    protected function showCompletionSummary(): void
    {
        $this->command->newLine();
        $this->command->info('✅ SEEDING COMPLETED SUCCESSFULLY!');
        $this->command->info('==================================');

        $stats = [
            'Users' => User::count(),
            'Departments' => Department::count(),
            'Employees' => Employee::count(),
            'Certificate Types' => CertificateType::count(),
        ];

        foreach ($stats as $label => $count) {
            $this->command->line("  📈 {$label}: {$count}");
        }

        $this->command->newLine();
        $this->command->info('🔐 Admin Credentials:');
        $this->command->line('  📧 admin@gapura.com / password');
        $this->command->line('  📧 hr@gapura.com / password');

        $this->command->newLine();
        $this->command->info('🎯 NEXT STEPS:');
        $this->command->line('1. Access /employees to see Employee Container System');
        $this->command->line('2. Click "Container" button to view digital employee folders');
        $this->command->line('3. Import MPGA data: php artisan mpga:import [file.xlsx]');
        $this->command->line('4. Upload certificates and background check documents');
        $this->command->line('5. Test recurrent certificate management');

        $this->command->newLine();
        $this->command->info('🗂️ Employee Container System is ready!');
    }
}
