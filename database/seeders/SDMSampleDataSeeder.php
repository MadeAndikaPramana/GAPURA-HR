<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Department;
use Carbon\Carbon;

class SDMSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Creating 100 Sample SDM Employee Records...');
        $this->command->info('=====================================================');

        // Get all departments
        $departments = Department::where('is_active', true)->get();

        if ($departments->isEmpty()) {
            $this->command->error('❌ No active departments found. Please run DatabaseSeeder first.');
            return;
        }

        // Sample Indonesian names for realistic data
        $firstNames = [
            'Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fitri', 'Gita', 'Hadi', 'Indira', 'Joko',
            'Kartika', 'Lestari', 'Made', 'Nina', 'Omar', 'Putri', 'Qori', 'Rina', 'Sari', 'Toni',
            'Udin', 'Vira', 'Wawan', 'Xenia', 'Yudi', 'Zahra', 'Agus', 'Bambang', 'Candra', 'Dian',
            'Erna', 'Fajar', 'Gilang', 'Hesti', 'Imam', 'Julia', 'Kiki', 'Lina', 'Mario', 'Nita',
            'Oskar', 'Prita', 'Qomar', 'Rudi', 'Sinta', 'Teguh', 'Ulfa', 'Vina', 'Wahyu', 'Yanto'
        ];

        $lastNames = [
            'Kusuma', 'Suryanto', 'Pratama', 'Sari', 'Wijaya', 'Putri', 'Santoso', 'Lestari', 'Prasetyo', 'Dewi',
            'Handoko', 'Rahayu', 'Setiawan', 'Wulandari', 'Kurniawan', 'Safitri', 'Nugroho', 'Maharani', 'Susanto', 'Anggraini',
            'Gunawan', 'Puspita', 'Wibowo', 'Permata', 'Firmansyah', 'Novita', 'Suprianto', 'Cahyani', 'Budiono', 'Fitriani',
            'Hermawan', 'Yunita', 'Supriyanto', 'Kartini', 'Rachman', 'Melati', 'Iskandar', 'Sartika', 'Wahyudi', 'Pertiwi',
            'Hakim', 'Kusumawati', 'Purnomo', 'Andayani', 'Sutrisno', 'Widianti', 'Hartanto', 'Susilowati', 'Darmawan', 'Pramesti'
        ];

        // Position titles by department
        $positionsByDepartment = [
            'GSE' => [
                'Equipment Maintenance Technician', 'Ground Support Equipment Operator', 'Heavy Equipment Mechanic',
                'Electrical Systems Specialist', 'Hydraulic Systems Technician', 'GSE Supervisor', 'Maintenance Coordinator',
                'Equipment Inspector', 'Tool & Equipment Manager', 'GSE Safety Officer'
            ],
            'OPS' => [
                'Ground Operations Supervisor', 'Ramp Agent', 'Aircraft Marshaller', 'Load Master', 'Operations Coordinator',
                'Flight Operations Specialist', 'Ground Handling Supervisor', 'Aircraft Towing Operator', 'Baggage Handler',
                'Operations Control Center Staff'
            ],
            'MAINT' => [
                'Aircraft Maintenance Technician', 'Avionics Specialist', 'Engine Mechanic', 'Sheet Metal Worker',
                'Maintenance Supervisor', 'Quality Control Inspector', 'NDT Inspector', 'Line Maintenance Technician',
                'Base Maintenance Specialist', 'Maintenance Planning Coordinator'
            ],
            'SECURITY' => [
                'Aviation Security Officer', 'Security Supervisor', 'Access Control Specialist', 'CCTV Operator',
                'Security Inspector', 'Perimeter Security Guard', 'Cargo Security Officer', 'VIP Protection Officer',
                'Security Training Coordinator', 'Emergency Response Officer'
            ],
            'CARGO' => [
                'Cargo Handler', 'Cargo Supervisor', 'Dangerous Goods Specialist', 'Cargo Documentation Officer',
                'Warehouse Supervisor', 'Cargo Loading Specialist', 'Freight Forwarder Coordinator', 'Cargo Inspector',
                'Cold Chain Specialist', 'Cargo Operations Manager'
            ],
            'HR' => [
                'HR Generalist', 'Training Coordinator', 'Recruitment Specialist', 'Payroll Administrator',
                'Employee Relations Officer', 'HR Business Partner', 'Training Manager', 'Compliance Officer',
                'HR Information Systems Analyst', 'Organizational Development Specialist'
            ]
        ];

        // Background check statuses with weights (more realistic distribution)
        $backgroundCheckStatuses = [
            'completed' => 70,
            'in_progress' => 15,
            'not_started' => 10,
            'expired' => 5
        ];

        $employeeStatuses = [
            'active' => 90,
            'inactive' => 10
        ];

        // Track employee IDs to ensure uniqueness
        $usedEmployeeIds = [];

        $employees = [];
        $batchSize = 10; // Process in batches for better performance

        for ($i = 1; $i <= 100; $i++) {
            $department = $departments->random();
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $name = $firstName . ' ' . $lastName;

            // Generate unique employee ID
            do {
                $empId = 'MPGA-' . $department->code . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            } while (in_array($empId, $usedEmployeeIds));

            $usedEmployeeIds[] = $empId;

            // Get random position for this department
            $positions = $positionsByDepartment[$department->code] ?? ['General Staff'];
            $position = $positions[array_rand($positions)];

            // Random hire date (last 5 years, with more recent hires)
            $hireDate = Carbon::now()->subDays(rand(30, 1825)); // 30 days to 5 years ago

            // Background check status (weighted random)
            $bgStatus = $this->getWeightedRandom($backgroundCheckStatuses);
            $bgDate = $bgStatus !== 'not_started' ? $hireDate->copy()->subDays(rand(1, 30)) : null;

            // Employee status (weighted random)
            $empStatus = $this->getWeightedRandom($employeeStatuses);

            // Generate email
            $emailName = strtolower(str_replace(' ', '.', $name));
            $emailName = $this->removeAccents($emailName); // Remove any special characters
            $email = $emailName . '@gapura.com';

            // Generate phone (Indonesian format)
            $phone = '+62 8' . rand(10, 99) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999);

            $employees[] = [
                'employee_id' => $empId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'department_id' => $department->id,
                'position' => $position,
                'hire_date' => $hireDate,
                'status' => $empStatus,
                'background_check_status' => $bgStatus,
                'background_check_date' => $bgDate,
                'background_check_notes' => $this->generateBgCheckNotes($bgStatus),
                'background_check_files' => json_encode($this->generateBgCheckFiles($bgStatus)),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert in batches for better performance
            if (count($employees) >= $batchSize || $i == 100) {
                Employee::insert($employees);
                $this->command->info("📝 Inserted batch of " . count($employees) . " employees (Total: $i/100)");
                $employees = []; // Reset batch
            }
        }

        // Show completion summary
        $this->showCompletionSummary();
    }

    /**
     * Get weighted random selection
     */
    private function getWeightedRandom($weights)
    {
        $rand = rand(1, 100);
        $current = 0;

        foreach ($weights as $key => $weight) {
            $current += $weight;
            if ($rand <= $current) {
                return $key;
            }
        }

        return array_key_first($weights);
    }

    /**
     * Remove accents and special characters for email
     */
    private function removeAccents($string)
    {
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        return preg_replace('/[^a-zA-Z0-9.]/', '', $string);
    }

    /**
     * Generate background check notes based on status
     */
    private function generateBgCheckNotes($status)
    {
        $notesByStatus = [
            'completed' => [
                'All background verification completed successfully.',
                'Criminal background check passed. Employment history verified.',
                'Reference checks completed with positive results.',
                'Security clearance obtained. All documents verified.',
                'Background investigation completed without issues.'
            ],
            'in_progress' => [
                'Background verification currently in progress.',
                'Waiting for criminal background check results.',
                'Employment history verification pending.',
                'Reference check in progress - 2 of 3 completed.',
                'Security clearance application submitted, awaiting approval.'
            ],
            'not_started' => [
                'Background check not yet initiated.',
                'New hire - background check scheduled.',
                'Waiting for employee to submit required documents.',
                'Background check process to begin next week.',
                'Employee orientation pending - background check to follow.'
            ],
            'expired' => [
                'Background check has expired and requires renewal.',
                'Annual background verification due for renewal.',
                'Previous background check expired - renewal in progress.',
                'Background clearance expired - employee suspended pending renewal.',
                'Security clearance expired - access restricted until renewal.'
            ]
        ];

        $notes = $notesByStatus[$status] ?? ['Standard background check process.'];
        return $notes[array_rand($notes)];
    }

    /**
     * Generate background check files array based on status
     */
    private function generateBgCheckFiles($status)
    {
        if ($status === 'not_started') {
            return [];
        }

        $files = [];
        $fileTypes = [
            'Criminal Background Check Report.pdf',
            'Employment Verification Form.pdf',
            'Reference Check Summary.pdf',
            'Educational Credentials.pdf',
            'Identity Verification.pdf',
            'Security Clearance Certificate.pdf',
            'Medical Clearance.pdf'
        ];

        // Number of files based on status
        $fileCount = match($status) {
            'completed' => rand(3, 5),
            'in_progress' => rand(1, 3),
            'expired' => rand(2, 4),
            'not_started' => 0,
            default => 0
        };

        for ($i = 0; $i < $fileCount; $i++) {
            $filename = $fileTypes[array_rand($fileTypes)];
            $files[] = [
                'filename' => $filename,
                'original_name' => $filename,
                'size' => rand(50000, 500000), // 50KB to 500KB
                'uploaded_at' => Carbon::now()->subDays(rand(1, 60))->toISOString(),
                'type' => 'background_check'
            ];
        }

        return $files;
    }


    /**
     * Show completion summary with statistics
     */
    private function showCompletionSummary()
    {
        $this->command->newLine();
        $this->command->info('✅ SDM SAMPLE DATA CREATION COMPLETED!');
        $this->command->info('==========================================');

        // Get statistics
        $totalEmployees = Employee::count();
        $departments = Department::withCount('employees')->get();

        $this->command->info("📊 SUMMARY STATISTICS:");
        $this->command->line("  👥 Total Employees: {$totalEmployees}");

        $this->command->newLine();
        $this->command->info("📈 BY DEPARTMENT:");
        foreach ($departments as $dept) {
            $this->command->line("  {$dept->code}: {$dept->employees_count} employees");
        }

        // Status breakdown
        $statusCounts = Employee::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $this->command->newLine();
        $this->command->info("📋 BY STATUS:");
        foreach ($statusCounts as $status => $count) {
            $this->command->line("  " . ucfirst($status) . ": {$count} employees");
        }

        // Background check status
        $bgCounts = Employee::selectRaw('background_check_status, COUNT(*) as count')
            ->groupBy('background_check_status')
            ->pluck('count', 'background_check_status');

        $this->command->newLine();
        $this->command->info("🔍 BACKGROUND CHECK STATUS:");
        foreach ($bgCounts as $status => $count) {
            $statusLabel = str_replace('_', ' ', ucfirst($status ?? 'not_started'));
            $this->command->line("  {$statusLabel}: {$count} employees");
        }

        $this->command->newLine();
        $this->command->info('🎯 NEXT STEPS:');
        $this->command->line('1. Run: php artisan containers:create-missing');
        $this->command->line('2. Visit /employee-containers to see all containers');
        $this->command->line('3. Visit /sdm to manage employee master data');
        $this->command->line('4. Test bulk operations and search functionality');

        $this->command->newLine();
        $this->command->info('🗂️ 100 Employee Containers ready for testing!');
    }
}