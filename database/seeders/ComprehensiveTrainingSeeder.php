<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use Carbon\Carbon;

class ComprehensiveTrainingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎯 Creating comprehensive sample data for Gapura Training System...');

        // Clear existing data (optional - comment out if you want to keep existing data)
        // TrainingRecord::truncate();
        // Employee::truncate();
        // TrainingType::truncate();
        // Department::truncate();

        // ========================================================================
        // 1. CREATE DEPARTMENTS
        // ========================================================================

        $this->command->info('📂 Creating departments...');

        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'Human Resources & Training Department'],
            ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'IT Systems & Digital Infrastructure'],
            ['name' => 'Flight Operations', 'code' => 'OPS', 'description' => 'Flight Operations & Ground Handling'],
            ['name' => 'Ground Support Equipment', 'code' => 'GSE', 'description' => 'Ground Support & Equipment Maintenance'],
            ['name' => 'Security Services', 'code' => 'SEC', 'description' => 'Airport Security & Safety'],
            ['name' => 'Customer Relations', 'code' => 'CR', 'description' => 'Customer Service & Relations'],
        ];

        foreach ($departments as $deptData) {
            $dept = Department::create($deptData);
            $this->command->line("✅ {$dept->name} ({$dept->code})");
        }

        // ========================================================================
        // 2. CREATE TRAINING TYPES
        // ========================================================================

        $this->command->info('📚 Creating training types...');

        $trainingTypes = [
            // Safety Training
            ['name' => 'Fire Safety & Emergency Response', 'code' => 'FIRE', 'validity_months' => 12, 'category' => 'safety', 'description' => 'Fire safety procedures and emergency evacuation protocols'],
            ['name' => 'First Aid & CPR Certification', 'code' => 'FIRST', 'validity_months' => 24, 'category' => 'safety', 'description' => 'Emergency first aid and cardiopulmonary resuscitation'],
            ['name' => 'Occupational Health & Safety', 'code' => 'OHS', 'validity_months' => 12, 'category' => 'safety', 'description' => 'Workplace safety standards and procedures'],

            // Security Training
            ['name' => 'Airport Security Awareness', 'code' => 'SECAW', 'validity_months' => 12, 'category' => 'security', 'description' => 'Airport security protocols and threat awareness'],
            ['name' => 'Access Control Systems', 'code' => 'ACCESS', 'validity_months' => 18, 'category' => 'security', 'description' => 'Security access control and monitoring'],

            // Operational Training
            ['name' => 'Dangerous Goods Regulations', 'code' => 'DGR', 'validity_months' => 24, 'category' => 'operational', 'description' => 'IATA Dangerous Goods Regulations certification'],
            ['name' => 'Ground Handling Operations', 'code' => 'GHO', 'validity_months' => 36, 'category' => 'operational', 'description' => 'Aircraft ground handling procedures'],
            ['name' => 'Customer Service Excellence', 'code' => 'CSE', 'validity_months' => 18, 'category' => 'operational', 'description' => 'Customer service best practices'],

            // Technical Training
            ['name' => 'Ground Support Equipment', 'code' => 'GSE_TECH', 'validity_months' => 36, 'category' => 'technical', 'description' => 'GSE operation and maintenance certification'],
            ['name' => 'Quality Management Systems', 'code' => 'QMS', 'validity_months' => 24, 'category' => 'technical', 'description' => 'Quality management and compliance'],
        ];

        foreach ($trainingTypes as $ttData) {
            $tt = TrainingType::create($ttData);
            $this->command->line("✅ {$tt->name} ({$tt->code}) - {$tt->validity_months}mo - {$tt->category}");
        }

        // ========================================================================
        // 3. CREATE EMPLOYEES
        // ========================================================================

        $this->command->info('👥 Creating employees...');

        $departments = Department::all()->keyBy('code');

        $employees = [
            // HR Department
            ['employee_id' => 'GAP001', 'name' => 'Ahmad Suryanto', 'position' => 'HR Manager', 'department' => 'HR'],
            ['employee_id' => 'GAP002', 'name' => 'Nina Sari Dewi', 'position' => 'Training Coordinator', 'department' => 'HR'],
            ['employee_id' => 'GAP003', 'name' => 'Budi Santoso', 'position' => 'Safety Officer', 'department' => 'HR'],

            // IT Department
            ['employee_id' => 'GAP011', 'name' => 'Sari Indrawati', 'position' => 'IT Manager', 'department' => 'IT'],
            ['employee_id' => 'GAP012', 'name' => 'Rizky Firmansyah', 'position' => 'System Administrator', 'department' => 'IT'],
            ['employee_id' => 'GAP013', 'name' => 'Dewi Kartika', 'position' => 'Database Analyst', 'department' => 'IT'],

            // Operations Department
            ['employee_id' => 'GAP021', 'name' => 'Eko Wahyudi', 'position' => 'Operations Manager', 'department' => 'OPS'],
            ['employee_id' => 'GAP022', 'name' => 'Lisa Andriani', 'position' => 'Ground Handling Supervisor', 'department' => 'OPS'],
            ['employee_id' => 'GAP023', 'name' => 'Doni Prasetyo', 'position' => 'Ramp Agent', 'department' => 'OPS'],
            ['employee_id' => 'GAP024', 'name' => 'Maya Putri', 'position' => 'Load Controller', 'department' => 'OPS'],

            // GSE Department
            ['employee_id' => 'GAP031', 'name' => 'Agus Setiawan', 'position' => 'GSE Supervisor', 'department' => 'GSE'],
            ['employee_id' => 'GAP032', 'name' => 'Joko Widodo', 'position' => 'Equipment Operator', 'department' => 'GSE'],

            // Security Department
            ['employee_id' => 'GAP041', 'name' => 'Bambang Susilo', 'position' => 'Security Manager', 'department' => 'SEC'],
            ['employee_id' => 'GAP042', 'name' => 'Ani Yudhoyono', 'position' => 'Security Officer', 'department' => 'SEC'],

            // Customer Relations
            ['employee_id' => 'GAP051', 'name' => 'Retno Marsudi', 'position' => 'Customer Service Manager', 'department' => 'CR'],
            ['employee_id' => 'GAP052', 'name' => 'Susi Pudjiastuti', 'position' => 'Customer Service Agent', 'department' => 'CR'],
        ];

        foreach ($employees as $empData) {
            $dept = $departments[$empData['department']];
            $employee = Employee::create([
                'employee_id' => $empData['employee_id'],
                'name' => $empData['name'],
                'position' => $empData['position'],
                'department_id' => $dept->id,
                'status' => 'active'
            ]);
            $this->command->line("✅ {$employee->name} ({$employee->employee_id}) - {$empData['position']} - {$empData['department']}");
        }

        // ========================================================================
        // 4. CREATE TRAINING RECORDS
        // ========================================================================

        $this->command->info('📋 Creating training records with various statuses...');

        $employees = Employee::all()->keyBy('employee_id');
        $trainingTypes = TrainingType::all()->keyBy('code');

        $trainingAssignments = [
            // HR Department - Safety focused
            ['employee' => 'GAP001', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 3, 'status' => 'active'],
            ['employee' => 'GAP001', 'training' => 'FIRST', 'issuer' => 'RED CROSS INDONESIA', 'months_ago' => 8, 'status' => 'active'],
            ['employee' => 'GAP001', 'training' => 'OHS', 'issuer' => 'SAFETY INSTITUTE', 'months_ago' => 6, 'status' => 'active'],

            ['employee' => 'GAP002', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 2, 'status' => 'active'],
            ['employee' => 'GAP002', 'training' => 'CSE', 'issuer' => 'SERVICE ACADEMY', 'months_ago' => 12, 'status' => 'active'],

            ['employee' => 'GAP003', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 1, 'status' => 'active'],
            ['employee' => 'GAP003', 'training' => 'FIRST', 'issuer' => 'RED CROSS INDONESIA', 'months_ago' => 15, 'status' => 'expiring_soon'],
            ['employee' => 'GAP003', 'training' => 'OHS', 'issuer' => 'SAFETY INSTITUTE', 'months_ago' => 2, 'status' => 'active'],

            // IT Department - Technical + Safety
            ['employee' => 'GAP011', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 4, 'status' => 'active'],
            ['employee' => 'GAP011', 'training' => 'SECAW', 'issuer' => 'AIRPORT SECURITY', 'months_ago' => 8, 'status' => 'active'],
            ['employee' => 'GAP011', 'training' => 'ACCESS', 'issuer' => 'SECURITY SYSTEMS', 'months_ago' => 12, 'status' => 'active'],

            ['employee' => 'GAP012', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 13, 'status' => 'expiring_soon'],
            ['employee' => 'GAP012', 'training' => 'ACCESS', 'issuer' => 'SECURITY SYSTEMS', 'months_ago' => 6, 'status' => 'active'],

            ['employee' => 'GAP013', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 15, 'status' => 'expired'],
            ['employee' => 'GAP013', 'training' => 'QMS', 'issuer' => 'QUALITY INSTITUTE', 'months_ago' => 18, 'status' => 'active'],

            // Operations Department - Operational focus
            ['employee' => 'GAP021', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 5, 'status' => 'active'],
            ['employee' => 'GAP021', 'training' => 'DGR', 'issuer' => 'IATA TRAINING', 'months_ago' => 18, 'status' => 'active'],
            ['employee' => 'GAP021', 'training' => 'GHO', 'issuer' => 'GROUND HANDLING ACADEMY', 'months_ago' => 24, 'status' => 'active'],
            ['employee' => 'GAP021', 'training' => 'OHS', 'issuer' => 'SAFETY INSTITUTE', 'months_ago' => 4, 'status' => 'active'],

            ['employee' => 'GAP022', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 7, 'status' => 'active'],
            ['employee' => 'GAP022', 'training' => 'GHO', 'issuer' => 'GROUND HANDLING ACADEMY', 'months_ago' => 20, 'status' => 'active'],

            ['employee' => 'GAP023', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 14, 'status' => 'expired'],
            ['employee' => 'GAP023', 'training' => 'GHO', 'issuer' => 'GROUND HANDLING ACADEMY', 'months_ago' => 30, 'status' => 'active'],

            ['employee' => 'GAP024', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 11, 'status' => 'expiring_soon'],

            // GSE Department - Technical focus
            ['employee' => 'GAP031', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 6, 'status' => 'active'],
            ['employee' => 'GAP031', 'training' => 'GSE_TECH', 'issuer' => 'GSE MANUFACTURER', 'months_ago' => 28, 'status' => 'active'],
            ['employee' => 'GAP031', 'training' => 'OHS', 'issuer' => 'SAFETY INSTITUTE', 'months_ago' => 8, 'status' => 'active'],

            ['employee' => 'GAP032', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 16, 'status' => 'expired'],
            ['employee' => 'GAP032', 'training' => 'GSE_TECH', 'issuer' => 'GSE MANUFACTURER', 'months_ago' => 32, 'status' => 'active'],

            // Security Department - Security focus
            ['employee' => 'GAP041', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 3, 'status' => 'active'],
            ['employee' => 'GAP041', 'training' => 'SECAW', 'issuer' => 'AIRPORT SECURITY', 'months_ago' => 6, 'status' => 'active'],
            ['employee' => 'GAP041', 'training' => 'ACCESS', 'issuer' => 'SECURITY SYSTEMS', 'months_ago' => 10, 'status' => 'active'],

            ['employee' => 'GAP042', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 12, 'status' => 'expiring_soon'],
            ['employee' => 'GAP042', 'training' => 'SECAW', 'issuer' => 'AIRPORT SECURITY', 'months_ago' => 11, 'status' => 'expiring_soon'],

            // Customer Relations
            ['employee' => 'GAP051', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 8, 'status' => 'active'],
            ['employee' => 'GAP051', 'training' => 'CSE', 'issuer' => 'SERVICE ACADEMY', 'months_ago' => 15, 'status' => 'active'],

            ['employee' => 'GAP052', 'training' => 'FIRE', 'issuer' => 'GAPURA SAFETY DEPT', 'months_ago' => 9, 'status' => 'active'],
            ['employee' => 'GAP052', 'training' => 'CSE', 'issuer' => 'SERVICE ACADEMY', 'months_ago' => 10, 'status' => 'active'],
        ];

        $certificateCounter = 1;

        foreach ($trainingAssignments as $assignment) {
            $employee = $employees[$assignment['employee']];
            $trainingType = $trainingTypes[$assignment['training']];

            $issueDate = Carbon::now()->subMonths($assignment['months_ago']);
            $expiryDate = $issueDate->copy()->addMonths($trainingType->validity_months);

            // Determine status based on expiry date
            $now = Carbon::now();
            $daysUntilExpiry = $now->diffInDays($expiryDate, false);

            if ($daysUntilExpiry < 0) {
                $status = 'expired';
            } elseif ($daysUntilExpiry <= 30) {
                $status = 'expiring_soon';
            } else {
                $status = 'active';
            }

            // Override with specified status for testing variety
            if (isset($assignment['status'])) {
                $status = $assignment['status'];
            }

            $training = TrainingRecord::create([
                'employee_id' => $employee->id,
                'training_type_id' => $trainingType->id,
                'certificate_number' => sprintf('GAP/%s-%03d/2024', $trainingType->code, $certificateCounter),
                'issuer' => $assignment['issuer'],
                'issue_date' => $issueDate->format('Y-m-d'),
                'expiry_date' => $expiryDate->format('Y-m-d'),
                'status' => $status,
                'notes' => 'Sample training record for comprehensive testing'
            ]);

            $this->command->line("✅ {$employee->name} - {$trainingType->name} ({$status})");
            $certificateCounter++;
        }

        // ========================================================================
        // 5. FINAL STATISTICS
        // ========================================================================

        $this->command->info('🎉 Sample data creation completed!');
        $this->command->info('📊 Final Statistics:');
        $this->command->line("Departments: " . Department::count());
        $this->command->line("Employees: " . Employee::count());
        $this->command->line("Training Types: " . TrainingType::count());
        $this->command->line("Training Records: " . TrainingRecord::count());

        $this->command->info('📈 Training Status Breakdown:');
        $this->command->line("Active: " . TrainingRecord::where('status', 'active')->count());
        $this->command->line("Expiring Soon: " . TrainingRecord::where('status', 'expiring_soon')->count());
        $this->command->line("Expired: " . TrainingRecord::where('status', 'expired')->count());

        $this->command->info('✅ Ready for comprehensive testing!');
    }
}
