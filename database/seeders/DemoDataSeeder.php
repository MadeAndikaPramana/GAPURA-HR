<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Employee;
use App\Models\CertificateType;
use App\Models\EmployeeCertificate;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds for demo/testing purposes.
     */
    public function run(): void
    {
        $this->command->info('Starting demo data seeding...');

        // Create Departments
        $this->command->info('Creating departments...');
        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR', 'is_active' => true],
            ['name' => 'Engineering', 'code' => 'ENG', 'is_active' => true],
            ['name' => 'Operations', 'code' => 'OPS', 'is_active' => true],
            ['name' => 'Finance', 'code' => 'FIN', 'is_active' => true],
            ['name' => 'Marketing', 'code' => 'MKT', 'is_active' => true],
        ];

        foreach ($departments as $deptData) {
            Department::firstOrCreate(
                ['code' => $deptData['code']],
                $deptData
            );
        }

        // Create Certificate Types
        $this->command->info('Creating certificate types...');
        $certTypes = [
            [
                'name' => 'Fire Safety Training',
                'code' => 'FIRE-SAFETY',
                'category' => 'Safety',
                'validity_months' => 12,
                'warning_days' => 30,
                'is_active' => true,
                'is_recurrent' => true,
                'description' => 'Basic fire safety and emergency response training',
            ],
            [
                'name' => 'First Aid Certification',
                'code' => 'FIRST-AID',
                'category' => 'Safety',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_active' => true,
                'is_recurrent' => true,
                'description' => 'CPR and first aid certification',
            ],
            [
                'name' => 'Data Privacy Training',
                'code' => 'DATA-PRIVACY',
                'category' => 'Compliance',
                'validity_months' => 12,
                'warning_days' => 30,
                'is_active' => true,
                'is_recurrent' => true,
                'description' => 'GDPR and data protection compliance training',
            ],
            [
                'name' => 'Leadership Development',
                'code' => 'LEADERSHIP',
                'category' => 'Professional Development',
                'validity_months' => null,
                'warning_days' => 30,
                'is_active' => true,
                'is_recurrent' => false,
                'description' => 'Management and leadership skills training',
            ],
            [
                'name' => 'Technical Skills Certification',
                'code' => 'TECH-SKILLS',
                'category' => 'Technical',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_active' => true,
                'is_recurrent' => true,
                'description' => 'Technical competency certification',
            ],
        ];

        foreach ($certTypes as $certData) {
            CertificateType::firstOrCreate(
                ['code' => $certData['code']],
                $certData
            );
        }

        // Create Sample Employees
        $this->command->info('Creating employees...');
        $allDepartments = Department::all();
        $employeesData = [
            ['employee_id' => 'EMP001', 'name' => 'John Doe', 'email' => 'john.doe@company.com', 'phone' => '081234567890', 'position' => 'HR Manager'],
            ['employee_id' => 'EMP002', 'name' => 'Jane Smith', 'email' => 'jane.smith@company.com', 'phone' => '081234567891', 'position' => 'Software Engineer'],
            ['employee_id' => 'EMP003', 'name' => 'Michael Johnson', 'email' => 'michael.j@company.com', 'phone' => '081234567892', 'position' => 'Operations Lead'],
            ['employee_id' => 'EMP004', 'name' => 'Sarah Williams', 'email' => 'sarah.w@company.com', 'phone' => '081234567893', 'position' => 'Finance Analyst'],
            ['employee_id' => 'EMP005', 'name' => 'David Brown', 'email' => 'david.b@company.com', 'phone' => '081234567894', 'position' => 'Marketing Specialist'],
            ['employee_id' => 'EMP006', 'name' => 'Emily Davis', 'email' => 'emily.d@company.com', 'phone' => '081234567895', 'position' => 'Senior Developer'],
            ['employee_id' => 'EMP007', 'name' => 'Robert Miller', 'email' => 'robert.m@company.com', 'phone' => '081234567896', 'position' => 'Operations Officer'],
            ['employee_id' => 'EMP008', 'name' => 'Lisa Anderson', 'email' => 'lisa.a@company.com', 'phone' => '081234567897', 'position' => 'HR Specialist'],
            ['employee_id' => 'EMP009', 'name' => 'James Wilson', 'email' => 'james.w@company.com', 'phone' => '081234567898', 'position' => 'DevOps Engineer'],
            ['employee_id' => 'EMP010', 'name' => 'Maria Garcia', 'email' => 'maria.g@company.com', 'phone' => '081234567899', 'position' => 'Product Manager'],
        ];

        foreach ($employeesData as $index => $empData) {
            $department = $allDepartments[$index % $allDepartments->count()];

            Employee::firstOrCreate(
                ['employee_id' => $empData['employee_id']],
                array_merge($empData, [
                    'department_id' => $department->id,
                    'hire_date' => Carbon::now()->subMonths(rand(6, 36)),
                    'status' => 'active',
                ])
            );
        }

        // Create Sample Certificates
        $this->command->info('Creating certificates...');
        $allEmployees = Employee::all();
        $allCertTypes = CertificateType::all();

        foreach ($allEmployees as $employee) {
            // Give each employee 2-4 random certificates
            $numCerts = rand(2, 4);
            $certTypesForEmployee = $allCertTypes->random($numCerts);

            foreach ($certTypesForEmployee as $certType) {
                $issueDate = Carbon::now()->subMonths(rand(1, 18));
                $expiryDate = $certType->validity_months
                    ? $issueDate->copy()->addMonths($certType->validity_months)
                    : null;

                // Determine status
                $status = 'active';
                if ($expiryDate) {
                    $daysUntilExpiry = Carbon::now()->diffInDays($expiryDate, false);

                    if ($daysUntilExpiry < 0) {
                        $status = 'expired';
                    } elseif ($daysUntilExpiry <= ($certType->warning_days ?? 30)) {
                        $status = 'expiring_soon';
                    }
                }

                EmployeeCertificate::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'certificate_type_id' => $certType->id,
                    ],
                    [
                        'certificate_number' => 'CERT-' . strtoupper(substr(md5($employee->id . $certType->id), 0, 8)),
                        'issue_date' => $issueDate,
                        'expiry_date' => $expiryDate,
                        'status' => $status,
                        'issued_by' => 'Training Department',
                        'notes' => 'Auto-generated demo certificate',
                    ]
                );
            }
        }

        $this->command->info('✅ Demo data seeding completed!');
        $this->command->info('   - Departments: ' . Department::count());
        $this->command->info('   - Employees: ' . Employee::count());
        $this->command->info('   - Certificate Types: ' . CertificateType::count());
        $this->command->info('   - Certificates: ' . EmployeeCertificate::count());
    }
}
