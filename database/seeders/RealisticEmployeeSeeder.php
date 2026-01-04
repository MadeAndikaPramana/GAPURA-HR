<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RealisticEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds for realistic PT Gapura Angkasa employees
     */
    public function run(): void
    {
        $this->command->info('👥 Seeding Realistic Employees for PT Gapura Angkasa...');

        // Get all departments
        $departments = Department::all()->keyBy('code');

        if ($departments->isEmpty()) {
            $this->command->error('❌ No departments found! Please run DepartmentSeeder first.');
            return;
        }

        $employees = [
            // C-LEVEL & MANAGEMENT
            [
                'employee_id' => 'GAP-2018-0001',
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@gapura.com',
                'phone' => '+62 811-2345-6001',
                'department_code' => 'CEO',
                'position' => 'Chief Executive Officer',
                'hire_date' => '2018-01-15',
                'status' => 'active'
            ],
            [
                'employee_id' => 'GAP-2019-0002',
                'name' => 'Siti Aminah',
                'email' => 'siti.aminah@gapura.com',
                'phone' => '+62 812-3456-7002',
                'department_code' => 'CFO',
                'position' => 'Chief Financial Officer',
                'hire_date' => '2019-03-20',
                'status' => 'active'
            ],
            [
                'employee_id' => 'GAP-2020-0003',
                'name' => 'Ahmad Rizki',
                'email' => 'ahmad.rizki@gapura.com',
                'phone' => '+62 813-4567-8003',
                'department_code' => 'COO',
                'position' => 'Chief Operating Officer',
                'hire_date' => '2020-02-10',
                'status' => 'active'
            ],

            // OPERATIONS
            [
                'employee_id' => 'GAP-2020-0101',
                'name' => 'Dedi Kurniawan',
                'email' => 'dedi.kurniawan@gapura.com',
                'phone' => '+62 814-5678-9004',
                'department_code' => 'OPS-GRD',
                'position' => 'Ground Operations Manager',
                'hire_date' => '2020-06-15',
                'status' => 'active'
            ],
            [
                'employee_id' => 'GAP-2021-0102',
                'name' => 'Rina Wulandari',
                'email' => 'rina.wulandari@gapura.com',
                'phone' => '+62 815-6789-0005',
                'department_code' => 'OPS-RAMP',
                'position' => 'Ramp Supervisor',
                'hire_date' => '2021-03-10',
                'status' => 'active'
            ],
            [
                'employee_id' => 'GAP-2021-0103',
                'name' => 'Joko Widodo',
                'email' => 'joko.widodo@gapura.com',
                'phone' => '+62 816-7890-1006',
                'department_code' => 'OPS-CARGO',
                'position' => 'Cargo Handler',
                'hire_date' => '2021-08-20',
                'status' => 'active'
            ],
            [
                'employee_id' => 'GAP-2022-0104',
                'name' => 'Dewi Sartika',
                'email' => 'dewi.sartika@gapura.com',
                'phone' => '+62 817-8901-2007',
                'department_code' => 'OPS-BAG',
                'position' => 'Baggage Handler',
                'hire_date' => '2022-01-15',
                'status' => 'active'
            ],

            // COMMERCIAL
            [
                'employee_id' => 'GAP-2020-0201',
                'name' => 'Hendra Gunawan',
                'email' => 'hendra.gunawan@gapura.com',
                'phone' => '+62 818-9012-3008',
                'department_code' => 'COM-SALES',
                'position' => 'Sales Manager',
                'hire_date' => '2020-09-01',
                'status' => 'active'
            ],
            [
                'employee_id' => 'GAP-2021-0202',
                'name' => 'Maya Puspita',
                'email' => 'maya.puspita@gapura.com',
                'phone' => '+62 819-0123-4009',
                'department_code' => 'COM-CS',
                'position' => 'Customer Service Officer',
                'hire_date' => '2021-05-12',
                'status' => 'active'
            ],

            // FINANCE
            [
                'employee_id' => 'GAP-2019-0301',
                'name' => 'Rudi Hartono',
                'email' => 'rudi.hartono@gapura.com',
                'phone' => '+62 821-1234-5010',
                'department_code' => 'FIN-ACC',
                'position' => 'Senior Accountant',
                'hire_date' => '2019-11-20',
                'status' => 'active'
            ],
            [
                'employee_id' => 'GAP-2022-0302',
                'name' => 'Ani Suryani',
                'email' => 'ani.suryani@gapura.com',
                'phone' => '+62 822-2345-6011',
                'department_code' => 'FIN-BUDGET',
                'position' => 'Budget Analyst',
                'hire_date' => '2022-04-05',
                'status' => 'active'
            ],

            // HUMAN RESOURCES
            [
                'employee_id' => 'GAP-2020-0401',
                'name' => 'Tri Wahyuni',
                'email' => 'tri.wahyuni@gapura.com',
                'phone' => '+62 823-3456-7012',
                'department_code' => 'HR-MGMT',
                'position' => 'HR Manager',
                'hire_date' => '2020-07-15',
                'status' => 'active'
            ],
            [
                'employee_id' => 'GAP-2021-0402',
                'name' => 'Bambang Susilo',
                'email' => 'bambang.susilo@gapura.com',
                'phone' => '+62 824-4567-8013',
                'department_code' => 'HR-TRAIN',
                'position' => 'Training Officer',
                'hire_date' => '2021-10-10',
                'status' => 'active'
            ],

            // IT
            [
                'employee_id' => 'GAP-2020-0501',
                'name' => 'Agus Prasetyo',
                'email' => 'agus.prasetyo@gapura.com',
                'phone' => '+62 825-5678-9014',
                'department_code' => 'IT-SUPPORT',
                'position' => 'IT Support Specialist',
                'hire_date' => '2020-12-01',
                'status' => 'active'
            ],
            [
                'employee_id' => 'GAP-2022-0502',
                'name' => 'Linda Kusuma',
                'email' => 'linda.kusuma@gapura.com',
                'phone' => '+62 826-6789-0015',
                'department_code' => 'IT-DEV',
                'position' => 'System Developer',
                'hire_date' => '2022-06-15',
                'status' => 'active'
            ],

            // QHSE
            [
                'employee_id' => 'GAP-2019-0601',
                'name' => 'Fajar Nugroho',
                'email' => 'fajar.nugroho@gapura.com',
                'phone' => '+62 827-7890-1016',
                'department_code' => 'QHSE-SAFETY',
                'position' => 'Safety Officer',
                'hire_date' => '2019-08-20',
                'status' => 'active'
            ],
            [
                'employee_id' => 'GAP-2021-0602',
                'name' => 'Sari Indah',
                'email' => 'sari.indah@gapura.com',
                'phone' => '+62 828-8901-2017',
                'department_code' => 'QHSE-QA',
                'position' => 'Quality Assurance Officer',
                'hire_date' => '2021-04-15',
                'status' => 'active'
            ],

            // ENGINEERING
            [
                'employee_id' => 'GAP-2020-0701',
                'name' => 'Yudi Setiawan',
                'email' => 'yudi.setiawan@gapura.com',
                'phone' => '+62 829-9012-3018',
                'department_code' => 'ENG-GSE',
                'position' => 'GSE Technician',
                'hire_date' => '2020-11-10',
                'status' => 'active'
            ],

            // LOGISTICS
            [
                'employee_id' => 'GAP-2021-0801',
                'name' => 'Wati Rahayu',
                'email' => 'wati.rahayu@gapura.com',
                'phone' => '+62 831-0123-4019',
                'department_code' => 'LOG-PROC',
                'position' => 'Procurement Officer',
                'hire_date' => '2021-09-05',
                'status' => 'active'
            ],

            // LEGAL
            [
                'employee_id' => 'GAP-2023-0901',
                'name' => 'Hadi Firmansyah',
                'email' => 'hadi.firmansyah@gapura.com',
                'phone' => '+62 832-1234-5020',
                'department_code' => 'LEGAL-COMP',
                'position' => 'Compliance Officer',
                'hire_date' => '2023-02-10',
                'status' => 'active'
            ],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($employees as $empData) {
            try {
                // Get department ID from code
                $department = $departments->get($empData['department_code']);

                if (!$department) {
                    $this->command->warn("  ⚠️  Department {$empData['department_code']} not found, skipping {$empData['name']}");
                    $skipped++;
                    continue;
                }

                // Remove department_code and add department_id
                unset($empData['department_code']);
                $empData['department_id'] = $department->id;
                $empData['hire_date'] = Carbon::parse($empData['hire_date']);

                Employee::create($empData);
                $this->command->info("  ✓ Created: {$empData['employee_id']} - {$empData['name']} ({$empData['position']})");
                $created++;

            } catch (\Exception $e) {
                $this->command->error("  ✗ Failed to create {$empData['name']}: " . $e->getMessage());
                $skipped++;
            }
        }

        $this->command->newLine();
        $this->command->info("✅ Successfully seeded {$created} realistic employees for PT Gapura Angkasa");
        if ($skipped > 0) {
            $this->command->warn("⚠️  Skipped {$skipped} employees due to errors");
        }
        $this->command->info('📋 Employees cover: C-Level, Operations, Commercial, Finance, HR, IT, QHSE, Engineering, Logistics, Legal');
    }
}
