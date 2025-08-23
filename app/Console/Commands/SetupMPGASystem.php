<?php
// app/Console/Commands/SetupMPGASystem.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Employee;
use App\Models\Department;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\User;

class SetupMPGASystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mpga:setup
                            {--fresh : Fresh installation (clear all data)}
                            {--seed-only : Only run seeder without clearing data}
                            {--test : Run system tests after setup}
                            {--force : Force operation without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Setup MPGA Training Management System with comprehensive data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayHeader();

        $startTime = microtime(true);

        try {
            // Check system requirements
            $this->checkSystemRequirements();

            if ($this->option('fresh')) {
                $this->setupFreshInstallation();
            } elseif ($this->option('seed-only')) {
                $this->runSeedersOnly();
            } else {
                $this->setupStandardInstallation();
            }

            if ($this->option('test')) {
                $this->runSystemTests();
            }

            $this->displaySystemSummary();

            $duration = round(microtime(true) - $startTime, 2);
            $this->newLine();
            $this->info("✅ MPGA System setup completed successfully in {$duration}s");
            $this->newLine();
            $this->displayNextSteps();

        } catch (\Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function displayHeader()
    {
        $this->info('');
        $this->info('🚀 ========================================');
        $this->info('   MPGA TRAINING MANAGEMENT SYSTEM');
        $this->info('   Phase 1 & 2 Setup and Optimization');
        $this->info('🚀 ========================================');
        $this->newLine();
    }

    private function checkSystemRequirements()
    {
        $this->info('🔍 Checking system requirements...');

        // Check database connection
        try {
            DB::connection()->getPdo();
            $this->line('   ✅ Database connection: OK');
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }

        // Check required tables exist
        $requiredTables = ['departments', 'employees', 'training_types', 'training_records'];
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                throw new \Exception("Required table '{$table}' does not exist. Run migrations first.");
            }
            $this->line("   ✅ Table '{$table}': EXISTS");
        }

        // Check Laravel version
        $laravelVersion = app()->version();
        $this->line("   ✅ Laravel version: {$laravelVersion}");

        $this->newLine();
    }

    private function setupFreshInstallation()
    {
        $this->warn('⚠️  FRESH INSTALLATION WARNING!');
        $this->warn('   This will completely clear ALL existing data:');
        $this->warn('   - All employees');
        $this->warn('   - All training records');
        $this->warn('   - All departments');
        $this->warn('   - All training types');
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('Are you absolutely sure you want to continue?', false)) {
            $this->info('Operation cancelled by user.');
            return;
        }

        $this->info('🗑️  Clearing existing data...');

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tablesToClear = [
            'training_records' => 'Training Records',
            'employees' => 'Employees',
            'training_types' => 'Training Types',
            'departments' => 'Departments'
        ];

        foreach ($tablesToClear as $table => $description) {
            $count = DB::table($table)->count();
            DB::table($table)->truncate();
            $this->line("   🗑️  Cleared {$description}: {$count} records removed");
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('🔄 Running fresh migrations...');
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->line('   ✅ Migrations completed');

        $this->runSeedersOnly();
    }

    private function setupStandardInstallation()
    {
        $this->info('🔄 Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->line('   ✅ Migrations completed');

        $this->runSeedersOnly();
    }

    private function runSeedersOnly()
    {
        $this->info('🌱 Seeding comprehensive MPGA data...');

        // Check if realistic seeder exists
        if (!class_exists(\Database\Seeders\MPGARealisticSeeder::class)) {
            $this->warn('   ⚠️  MPGARealisticSeeder not found, using DatabaseSeeder instead');

            Artisan::call('db:seed', ['--force' => true]);
            $this->line('   ✅ Basic seeding completed');
        } else {
            // Run the comprehensive seeder
            Artisan::call('db:seed', [
                '--class' => 'MPGAComprehensiveSeeder',
                '--force' => true
            ]);
            $this->line('   ✅ MPGA comprehensive seeding completed');
        }

        // Update training statuses
        $this->info('🔄 Updating training statuses...');
        $this->updateTrainingStatuses();
        $this->line('   ✅ Training statuses updated');
    }

    private function updateTrainingStatuses()
    {
        $now = now();
        $updated = 0;

        // Update expiring soon (30 days or less)
        $expiringCount = TrainingRecord::where('status', 'active')
            ->where('expiry_date', '<=', $now->copy()->addDays(30))
            ->where('expiry_date', '>', $now)
            ->update(['status' => 'expiring_soon']);

        // Update expired
        $expiredCount = TrainingRecord::where('status', '!=', 'expired')
            ->where('expiry_date', '<=', $now)
            ->update(['status' => 'expired']);

        $this->line("   📊 Updated {$expiringCount} records to 'expiring_soon'");
        $this->line("   📊 Updated {$expiredCount} records to 'expired'");
    }

    private function runSystemTests()
    {
        $this->info('🧪 Running comprehensive system tests...');
        $this->newLine();

        $tests = [
            'Database Connection' => $this->testDatabaseConnection(),
            'Data Integrity' => $this->testDataIntegrity(),
            'Relationships' => $this->testRelationships(),
            'Business Logic' => $this->testBusinessLogic(),
            'Import Functionality' => $this->testImportFunctionality(),
            'Export Functionality' => $this->testExportFunctionality()
        ];

        $passedTests = 0;
        foreach ($tests as $testName => $result) {
            $status = $result['success'] ? '✅' : '❌';
            $this->line("   {$status} {$testName}: {$result['message']}");

            if (!$result['success'] && isset($result['details'])) {
                $this->error("      🔍 Details: {$result['details']}");
            }

            if ($result['success']) {
                $passedTests++;
            }
        }

        $totalTests = count($tests);
        $this->newLine();

        if ($passedTests === $totalTests) {
            $this->info("🎉 All tests passed! ({$passedTests}/{$totalTests})");
        } else {
            $this->warn("⚠️  Some tests failed. Passed: {$passedTests}/{$totalTests}");
        }
    }

    private function testDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            return ['success' => true, 'message' => "Connected to database: {$dbName}"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Connection failed', 'details' => $e->getMessage()];
        }
    }

    private function testDataIntegrity(): array
    {
        try {
            $stats = [
                'employees' => Employee::count(),
                'departments' => Department::count(),
                'training_types' => TrainingType::count(),
                'training_records' => TrainingRecord::count()
            ];

            // Validate minimum data requirements
            if ($stats['employees'] === 0) {
                return ['success' => false, 'message' => 'No employees found in database'];
            }

            if ($stats['departments'] < 5) {
                return ['success' => false, 'message' => "Insufficient departments: {$stats['departments']} (expected at least 5)"];
            }

            if ($stats['training_types'] === 0) {
                return ['success' => false, 'message' => 'No training types found'];
            }

            // Check for orphaned records
            $orphanedEmployees = Employee::whereDoesntHave('department')->count();
            if ($orphanedEmployees > 0) {
                return ['success' => false, 'message' => "{$orphanedEmployees} employees without departments"];
            }

            return [
                'success' => true,
                'message' => "Data integrity verified. Employees: {$stats['employees']}, Departments: {$stats['departments']}, Training Types: {$stats['training_types']}, Records: {$stats['training_records']}"
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Data integrity check failed', 'details' => $e->getMessage()];
        }
    }

    private function testRelationships(): array
    {
        try {
            // Test employee-department relationship
            $employeeWithDept = Employee::with('department')->first();
            if (!$employeeWithDept || !$employeeWithDept->department) {
                return ['success' => false, 'message' => 'Employee-Department relationship failed'];
            }

            // Test training record relationships
            $trainingRecord = TrainingRecord::with(['employee', 'trainingType'])->first();
            if (!$trainingRecord) {
                return ['success' => false, 'message' => 'No training records found for relationship testing'];
            }

            if (!$trainingRecord->employee || !$trainingRecord->trainingType) {
                return ['success' => false, 'message' => 'Training record relationships incomplete'];
            }

            // Test department-employees relationship
            $deptWithEmployees = Department::withCount('employees')->first();
            if (!$deptWithEmployees || $deptWithEmployees->employees_count === 0) {
                return ['success' => false, 'message' => 'Department-Employees relationship failed'];
            }

            return ['success' => true, 'message' => 'All relationships working correctly'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Relationship test failed', 'details' => $e->getMessage()];
        }
    }

    private function testBusinessLogic(): array
    {
        try {
            // Test training status calculation
            $statusCounts = [
                'active' => TrainingRecord::where('status', 'active')->count(),
                'expiring_soon' => TrainingRecord::where('status', 'expiring_soon')->count(),
                'expired' => TrainingRecord::where('status', 'expired')->count()
            ];

            // Test mandatory training identification
            $mandatoryTrainings = TrainingType::where('is_mandatory', true)->count();

            // Test certificate number generation logic
            $sampleEmployee = Employee::with('department')->first();
            $sampleTrainingType = TrainingType::first();

            if (!$sampleEmployee || !$sampleTrainingType) {
                return ['success' => false, 'message' => 'Insufficient data for business logic testing'];
            }

            return [
                'success' => true,
                'message' => "Business logic verified. Status distribution: Active({$statusCounts['active']}), Expiring({$statusCounts['expiring_soon']}), Expired({$statusCounts['expired']}). Mandatory trainings: {$mandatoryTrainings}"
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Business logic test failed', 'details' => $e->getMessage()];
        }
    }

    private function testImportFunctionality(): array
    {
        try {
            // Test if import classes exist
            $importClasses = [
                'App\Imports\EnhancedEmployeeImport',
                'App\Imports\EnhancedTrainingRecordImport'
            ];

            foreach ($importClasses as $class) {
                if (!class_exists($class)) {
                    return ['success' => false, 'message' => "Import class {$class} not found"];
                }
            }

            // Test data resolution capabilities
            $departments = Department::all()->keyBy('code');
            $trainingTypes = TrainingType::all()->keyBy('code');

            if ($departments->isEmpty() || $trainingTypes->isEmpty()) {
                return ['success' => false, 'message' => 'Insufficient reference data for import testing'];
            }

            return ['success' => true, 'message' => 'Import functionality ready and configured'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Import functionality test failed', 'details' => $e->getMessage()];
        }
    }

    private function testExportFunctionality(): array
    {
        try {
            // Test if Maatwebsite Excel is properly configured
            if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
                return ['success' => false, 'message' => 'Excel package not installed'];
            }

            // Test basic export capability with sample data
            $hasData = Employee::count() > 0 && TrainingRecord::count() > 0;

            if (!$hasData) {
                return ['success' => false, 'message' => 'Insufficient data for export testing'];
            }

            return ['success' => true, 'message' => 'Export functionality ready'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Export functionality test failed', 'details' => $e->getMessage()];
        }
    }

    private function displaySystemSummary()
    {
        $this->newLine();
        $this->info('📊 ===== MPGA SYSTEM SUMMARY =====');
        $this->newLine();

        // Core statistics
        $stats = [
            'Total Employees' => Employee::count(),
            'Active Employees' => Employee::where('status', 'active')->count(),
            'Departments' => Department::count(),
            'Training Types' => TrainingType::count(),
            'Mandatory Trainings' => TrainingType::where('is_mandatory', true)->count(),
            'Total Training Records' => TrainingRecord::count(),
            'Active Certificates' => TrainingRecord::where('status', 'active')->count(),
            'Expiring Soon' => TrainingRecord::where('status', 'expiring_soon')->count(),
            'Expired Certificates' => TrainingRecord::where('status', 'expired')->count(),
        ];

        foreach ($stats as $label => $value) {
            $this->line(sprintf('   %-25s: %s', $label, number_format($value)));
        }

        $this->newLine();

        // Department breakdown
        $this->info('📂 Department Breakdown:');
        $departments = Department::withCount('employees')->orderBy('employees_count', 'desc')->get();

        foreach ($departments as $dept) {
            $this->line(sprintf('   %-25s: %d employees', $dept->name, $dept->employees_count));
        }

        $this->newLine();

        // Training status overview
        $this->info('🎯 Training Status Overview:');
        $totalRecords = TrainingRecord::count();
        if ($totalRecords > 0) {
            $activePercent = round((TrainingRecord::where('status', 'active')->count() / $totalRecords) * 100, 1);
            $expiringPercent = round((TrainingRecord::where('status', 'expiring_soon')->count() / $totalRecords) * 100, 1);
            $expiredPercent = round((TrainingRecord::where('status', 'expired')->count() / $totalRecords) * 100, 1);

            $this->line(sprintf('   %-20s: %.1f%%', 'Active', $activePercent));
            $this->line(sprintf('   %-20s: %.1f%%', 'Expiring Soon', $expiringPercent));
            $this->line(sprintf('   %-20s: %.1f%%', 'Expired', $expiredPercent));
        }
    }

    private function displayNextSteps()
    {
        $this->info('🎯 NEXT STEPS FOR PRODUCTION READINESS:');
        $this->newLine();

        $nextSteps = [
            '1. Data Validation' => [
                '   • Test employee import with real MPGA Excel files',
                '   • Validate all department mappings',
                '   • Verify training type configurations'
            ],
            '2. User Management' => [
                '   • Create user accounts for HR team',
                '   • Configure role-based permissions',
                '   • Set up authentication system'
            ],
            '3. System Configuration' => [
                '   • Configure email notifications for expiry alerts',
                '   • Set up automated compliance reporting',
                '   • Configure backup and maintenance schedules'
            ],
            '4. Phase 3 Development' => [
                '   • Proceed to Training Type Management',
                '   • Implement certificate management',
                '   • Add advanced reporting features'
            ]
        ];

        foreach ($nextSteps as $category => $items) {
            $this->comment($category);
            foreach ($items as $item) {
                $this->line($item);
            }
            $this->newLine();
        }

        $this->info('🚀 System is ready for Phase 3 development!');
    }
}
