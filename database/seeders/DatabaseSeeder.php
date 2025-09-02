<?php
// database/seeders/DatabaseSeeder.php (Fixed for Phase 1)

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Department;
use App\Models\Employee;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Phase 1 Database Seeding...');
        $this->command->info('========================================');

        DB::beginTransaction();

        try {
            // 1. Create Admin Users
            $this->createAdminUsers();

            // 2. Create Departments (if not exists)
            $this->createDepartments();

            // 3. Create Sample Employees (if table is empty)
            $this->createSampleEmployees();

            // 4. Seed Certificate Types (if seeder exists)
            if (class_exists('Database\Seeders\CertificateTypeSeeder')) {
                $this->call(CertificateTypeSeeder::class);
            }

            DB::commit();

            $this->command->info('✅ Phase 1 seeding completed successfully!');
            $this->printSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Seeding failed: ' . $e->getMessage());
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
     * Create departments if they don't exist
     */
    private function createDepartments()
    {
        $this->command->info('🏢 Creating departments...');

        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'Human Resources Department'],
            ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'Information Technology Department'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Operations Department'],
            ['name' => 'Ground Support Equipment', 'code' => 'GSE', 'description' => 'Ground Support Equipment Department'],
            ['name' => 'Aviation Security', 'code' => 'SEC', 'description' => 'Aviation Security Department'],
            ['name' => 'Passenger Handling', 'code' => 'PAX', 'description' => 'Passenger Handling Department'],
            ['name' => 'Cargo Operations', 'code' => 'CAR', 'description' => 'Cargo Operations Department'],
            ['name' => 'Ramp Operations', 'code' => 'RAM', 'description' => 'Ramp Operations Department'],
        ];

        $createdCount = 0;
        foreach ($departments as $deptData) {
            $dept = Department::updateOrCreate(
                ['code' => $deptData['code']],
                array_merge($deptData, ['is_active' => true])
            );

            if ($dept->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        $this->command->line("  ✅ Created/updated {$createdCount} departments");
    }

    /**
     * Create sample employees if table is empty
     */
    private function createSampleEmployees()
    {
        if (Employee::count() > 0) {
            $this->command->line('  ℹ️  Employees already exist, skipping sample creation');
            return;
        }

        $this->command->info('👥 Creating sample employees...');

        $hrDept = Department::where('code', 'HR')->first();
        $itDept = Department::where('code', 'IT')->first();
        $opsDept = Department::where('code', 'OPS')->first();

        if (!$hrDept || !$itDept || !$opsDept) {
            $this->command->warn('  ⚠️  Required departments not found, skipping sample employees');
            return;
        }

        $employees = [
            [
                'employee_id' => 'MPGA-HR-001',
                'name' => 'AHMAD SURYANTO',
                'email' => 'ahmad.suryanto@gapura.com',
                'phone' => '+62-811-1234-5678',
                'department_id' => $hrDept->id,
                'position' => 'HR Manager',
                'position_level' => 'Manager',
                'employment_type' => 'Full Time',
                'hire_date' => '2020-01-15',
                'status' => 'active',
                'background_check_date' => '2019-12-20',
                'background_check_status' => 'cleared',
                'background_check_notes' => 'Background check completed successfully.',
                'emergency_contact_name' => 'Siti Suryanto',
                'emergency_contact_phone' => '+62-812-9999-8888',
                'address' => 'Jl. Imam Bonjol No. 123, Denpasar, Bali 80235',
            ],
            [
                'employee_id' => 'MPGA-IT-002',
                'name' => 'NINA KARTIKA',
                'email' => 'nina.kartika@gapura.com',
                'phone' => '+62-821-2345-6789',
                'department_id' => $itDept->id,
                'position' => 'IT Specialist',
                'position_level' => 'Staff',
                'employment_type' => 'Full Time',
                'hire_date' => '2021-03-10',
                'status' => 'active',
                'background_check_date' => '2021-02-15',
                'background_check_status' => 'cleared',
                'background_check_notes' => 'Technical competency verified.',
                'emergency_contact_name' => 'Budi Kartika',
                'emergency_contact_phone' => '+62-822-1111-2222',
                'address' => 'Jl. Gatot Subroto No. 456, Jakarta Selatan 12930',
            ],
            [
                'employee_id' => 'MPGA-OPS-003',
                'name' => 'BUDI SANTOSO',
                'email' => 'budi.santoso@gapura.com',
                'phone' => '+62-831-5678-9012',
                'department_id' => $opsDept->id,
                'position' => 'Ground Operations Supervisor',
                'position_level' => 'Supervisor',
                'employment_type' => 'Full Time',
                'hire_date' => '2019-08-10',
                'status' => 'active',
                'background_check_date' => '2019-07-20',
                'background_check_status' => 'cleared',
                'background_check_notes' => 'Aviation security background check completed.',
                'emergency_contact_name' => 'Ani Santoso',
                'emergency_contact_phone' => '+62-832-3333-4444',
                'address' => 'Jl. Soekarno-Hatta No. 789, Cengkareng, Jakarta Barat 11720',
            ]
        ];

        $createdCount = 0;
        foreach ($employees as $empData) {
            $empData['hire_date'] = Carbon::parse($empData['hire_date']);
            $empData['background_check_date'] = Carbon::parse($empData['background_check_date']);

            Employee::create($empData);
            $createdCount++;
        }

        $this->command->line("  ✅ Created {$createdCount} sample employees");
    }

    /**
     * Print final summary
     */
    private function printSummary()
    {
        $this->command->newLine();
        $this->command->info('🎉 PHASE 1 SEEDING COMPLETED!');
        $this->command->info('=============================');

        $stats = [
            'Users' => User::count(),
            'Departments' => Department::count(),
            'Employees' => Employee::count(),
        ];

        // Add certificate stats if models exist
        if (class_exists('App\Models\CertificateType')) {
            $stats['Certificate Types'] = \App\Models\CertificateType::count();
        }

        if (class_exists('App\Models\EmployeeCertificate')) {
            $stats['Employee Certificates'] = \App\Models\EmployeeCertificate::count();
        }

        foreach ($stats as $label => $count) {
            $this->command->line("  📈 {$label}: {$count}");
        }

        $this->command->newLine();
        $this->command->info('🔐 Admin Credentials:');
        $this->command->line('  📧 admin@gapura.com / password');
        $this->command->line('  📧 hr@gapura.com / password');

        $this->command->newLine();
        $this->command->info('🎯 NEXT STEPS:');
        $this->command->line('1. Import MPGA data: php artisan mpga:import [file.xlsx]');
        $this->command->line('2. Test employee container interface');
        $this->command->line('3. Upload background check documents');
        $this->command->line('4. Verify certificate status calculations');

        $this->command->newLine();
        $this->command->info('🏁 Phase 1 Foundation Ready!');
    }
}
