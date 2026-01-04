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
        $this->command->info('🚀 Starting CertManager Employee Container System Database Seeding...');
        $this->command->info('🏢 PT Gapura Angkasa - Aviation Ground Handling Company');
        $this->command->info('====================================================================');

        try {
            // Create admin users
            $this->createAdminUsers();

            // Create comprehensive PT Gapura Angkasa departments (43 aviation departments)
            $this->call(DepartmentSeeder::class);

            // Create certificate types for aviation industry (30 types)
            $this->call(CertificateTypeSeeder::class);

            // Create realistic PT Gapura Angkasa employees (20 employees)
            $this->call(RealisticEmployeeSeeder::class);

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
                'name' => 'System Administrator',
                'email' => 'admin@certmanager.local',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'HR Manager',
                'email' => 'hr@certmanager.local',
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
     * Show completion summary
     */
    protected function showCompletionSummary(): void
    {
        $this->command->newLine();
        $this->command->info('✅ PT GAPURA ANGKASA DATABASE SEEDING COMPLETED!');
        $this->command->info('=================================================');

        $stats = [
            'Admin Users' => User::count(),
            'Departments' => Department::count(),
            'Employees' => Employee::count(),
            'Certificate Types' => CertificateType::count(),
        ];

        $this->command->info('📊 SEEDED DATA:');
        foreach ($stats as $label => $count) {
            $this->command->line("  • {$label}: {$count}");
        }

        // Department breakdown
        $this->command->newLine();
        $this->command->info('🏢 DEPARTMENT STRUCTURE:');
        $this->command->line('  • C-Level & Management (4)');
        $this->command->line('  • Operations Division (6)');
        $this->command->line('  • Commercial Division (4)');
        $this->command->line('  • Finance & Accounting (4)');
        $this->command->line('  • Human Resources (4)');
        $this->command->line('  • Information Technology (4)');
        $this->command->line('  • QHSE Division (5)');
        $this->command->line('  • Logistics Division (4)');
        $this->command->line('  • Engineering Division (3)');
        $this->command->line('  • Legal & Compliance (3)');

        $this->command->newLine();
        $this->command->info('🔐 LOGIN CREDENTIALS:');
        $this->command->line('  📧 admin@certmanager.local / password');
        $this->command->line('  📧 hr@certmanager.local / password');

        $this->command->newLine();
        $this->command->info('🎯 NEXT STEPS:');
        $this->command->line('1. Visit /employee-containers to see employee digital folders');
        $this->command->line('2. Visit /training-types to manage 30 aviation certificate types');
        $this->command->line('3. Visit /departments to view organizational structure');
        $this->command->line('4. Upload certificates and background check documents');
        $this->command->line('5. Optional: Run SDMSampleDataSeeder for 100 test employees');

        $this->command->newLine();
        $this->command->info('✈️ PT Gapura Angkasa CertManager System Ready!');
    }
}
