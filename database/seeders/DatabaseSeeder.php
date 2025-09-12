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
            $this->call(CertificateTypeSeeder::class);

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
