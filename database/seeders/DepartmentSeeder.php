<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds for PT Gapura Angkasa (Aviation Ground Handling Company)
     */
    public function run(): void
    {
        $this->command->info('🏢 Seeding PT Gapura Angkasa Departments...');

        DB::table('departments')->delete();

        $departments = [
            // ========================================
            // C-LEVEL & BOARD
            // ========================================
            [
                'code' => 'BOD',
                'name' => 'Board of Directors',
                'description' => 'Board of Directors responsible for strategic oversight and corporate governance',
                'is_active' => true
            ],
            [
                'code' => 'CEO',
                'name' => 'Chief Executive Officer',
                'description' => 'CEO Office managing overall company operations and strategy',
                'is_active' => true
            ],
            [
                'code' => 'CFO',
                'name' => 'Chief Financial Officer',
                'description' => 'CFO Office overseeing financial strategy and management',
                'is_active' => true
            ],
            [
                'code' => 'COO',
                'name' => 'Chief Operating Officer',
                'description' => 'COO Office managing day-to-day operations',
                'is_active' => true
            ],

            // ========================================
            // OPERATIONS DIVISION
            // ========================================
            [
                'code' => 'OPS',
                'name' => 'Operations Division',
                'description' => 'Overall operations management for ground handling services',
                'is_active' => true
            ],
            [
                'code' => 'OPS-GRD',
                'name' => 'Ground Operations',
                'description' => 'Aircraft ground handling operations including marshalling, pushback, and towing',
                'is_active' => true
            ],
            [
                'code' => 'OPS-RAMP',
                'name' => 'Ramp Services',
                'description' => 'Ramp handling services including aircraft servicing and turnaround coordination',
                'is_active' => true
            ],
            [
                'code' => 'OPS-CARGO',
                'name' => 'Cargo Handling',
                'description' => 'Air cargo loading, unloading, and documentation services',
                'is_active' => true
            ],
            [
                'code' => 'OPS-BAG',
                'name' => 'Baggage Handling',
                'description' => 'Passenger baggage loading, unloading, sorting, and delivery',
                'is_active' => true
            ],
            [
                'code' => 'OPS-CLEAN',
                'name' => 'Aircraft Cleaning',
                'description' => 'Aircraft cabin and exterior cleaning services',
                'is_active' => true
            ],

            // ========================================
            // COMMERCIAL DIVISION
            // ========================================
            [
                'code' => 'COM',
                'name' => 'Commercial Division',
                'description' => 'Commercial and business development division',
                'is_active' => true
            ],
            [
                'code' => 'COM-SALES',
                'name' => 'Sales & Marketing',
                'description' => 'Sales and marketing activities for ground handling services',
                'is_active' => true
            ],
            [
                'code' => 'COM-CS',
                'name' => 'Customer Service',
                'description' => 'Customer relations and service quality management',
                'is_active' => true
            ],
            [
                'code' => 'COM-BIZDEV',
                'name' => 'Business Development',
                'description' => 'New business opportunities and strategic partnerships',
                'is_active' => true
            ],

            // ========================================
            // FINANCE & ACCOUNTING
            // ========================================
            [
                'code' => 'FIN',
                'name' => 'Finance Division',
                'description' => 'Financial management and control division',
                'is_active' => true
            ],
            [
                'code' => 'FIN-ACC',
                'name' => 'Accounting',
                'description' => 'Financial accounting, reporting, and bookkeeping',
                'is_active' => true
            ],
            [
                'code' => 'FIN-BUDGET',
                'name' => 'Budgeting & Planning',
                'description' => 'Financial planning, budgeting, and forecasting',
                'is_active' => true
            ],
            [
                'code' => 'FIN-TAX',
                'name' => 'Tax & Treasury',
                'description' => 'Tax compliance and treasury management',
                'is_active' => true
            ],

            // ========================================
            // HUMAN RESOURCES
            // ========================================
            [
                'code' => 'HR',
                'name' => 'Human Resources Division',
                'description' => 'Human capital management and development',
                'is_active' => true
            ],
            [
                'code' => 'HR-MGMT',
                'name' => 'HR Management',
                'description' => 'HR policy, employee relations, and administration',
                'is_active' => true
            ],
            [
                'code' => 'HR-TRAIN',
                'name' => 'Training & Development',
                'description' => 'Employee training, certification, and competency development',
                'is_active' => true
            ],
            [
                'code' => 'HR-REC',
                'name' => 'Recruitment',
                'description' => 'Talent acquisition and onboarding',
                'is_active' => true
            ],

            // ========================================
            // INFORMATION TECHNOLOGY
            // ========================================
            [
                'code' => 'IT',
                'name' => 'Information Technology Division',
                'description' => 'IT infrastructure and systems management',
                'is_active' => true
            ],
            [
                'code' => 'IT-SUPPORT',
                'name' => 'IT Support',
                'description' => 'Technical support and helpdesk services',
                'is_active' => true
            ],
            [
                'code' => 'IT-DEV',
                'name' => 'System Development',
                'description' => 'Application development and system integration',
                'is_active' => true
            ],
            [
                'code' => 'IT-INFRA',
                'name' => 'Infrastructure',
                'description' => 'Network, server, and infrastructure management',
                'is_active' => true
            ],

            // ========================================
            // QHSE (Quality, Health, Safety, Environment)
            // ========================================
            [
                'code' => 'QHSE',
                'name' => 'QHSE Division',
                'description' => 'Quality, Health, Safety, and Environment management',
                'is_active' => true
            ],
            [
                'code' => 'QHSE-QA',
                'name' => 'Quality Assurance',
                'description' => 'Quality management systems and service standards',
                'is_active' => true
            ],
            [
                'code' => 'QHSE-SAFETY',
                'name' => 'Health & Safety',
                'description' => 'Occupational health and safety management',
                'is_active' => true
            ],
            [
                'code' => 'QHSE-ENV',
                'name' => 'Environment',
                'description' => 'Environmental management and sustainability',
                'is_active' => true
            ],
            [
                'code' => 'QHSE-SEC',
                'name' => 'Security',
                'description' => 'Aviation security and access control',
                'is_active' => true
            ],

            // ========================================
            // LOGISTICS
            // ========================================
            [
                'code' => 'LOG',
                'name' => 'Logistics Division',
                'description' => 'Supply chain and logistics management',
                'is_active' => true
            ],
            [
                'code' => 'LOG-WH',
                'name' => 'Warehouse',
                'description' => 'Warehouse management and inventory control',
                'is_active' => true
            ],
            [
                'code' => 'LOG-PROC',
                'name' => 'Procurement',
                'description' => 'Purchasing and supplier management',
                'is_active' => true
            ],
            [
                'code' => 'LOG-SC',
                'name' => 'Supply Chain',
                'description' => 'Supply chain planning and optimization',
                'is_active' => true
            ],

            // ========================================
            // ENGINEERING
            // ========================================
            [
                'code' => 'ENG',
                'name' => 'Engineering Division',
                'description' => 'Engineering and maintenance support',
                'is_active' => true
            ],
            [
                'code' => 'ENG-AMC',
                'name' => 'Aircraft Maintenance Support',
                'description' => 'Support services for aircraft maintenance operations',
                'is_active' => true
            ],
            [
                'code' => 'ENG-GSE',
                'name' => 'Ground Support Equipment',
                'description' => 'GSE maintenance, repair, and management',
                'is_active' => true
            ],

            // ========================================
            // LEGAL & COMPLIANCE
            // ========================================
            [
                'code' => 'LEGAL',
                'name' => 'Legal & Compliance Division',
                'description' => 'Legal affairs and regulatory compliance',
                'is_active' => true
            ],
            [
                'code' => 'LEGAL-AFFAIRS',
                'name' => 'Legal Affairs',
                'description' => 'Corporate legal matters and contract management',
                'is_active' => true
            ],
            [
                'code' => 'LEGAL-COMP',
                'name' => 'Regulatory Compliance',
                'description' => 'Aviation regulations and compliance management',
                'is_active' => true
            ],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
            $this->command->info("  ✓ Created: {$dept['code']} - {$dept['name']}");
        }

        $count = count($departments);
        $this->command->newLine();
        $this->command->info("✅ Successfully seeded {$count} departments for PT Gapura Angkasa");
        $this->command->info('📋 Departments include: C-Level, Operations, Commercial, Finance, HR, IT, QHSE, Logistics, Engineering, Legal');
    }
}
