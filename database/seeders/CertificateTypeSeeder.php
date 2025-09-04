<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CertificateType;

class CertificateTypesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏆 Seeding Certificate Types for Employee Container System...');

        $certificateTypes = [
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
                'estimated_duration_hours' => 8
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
                'estimated_duration_hours' => 16
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
                'estimated_duration_hours' => 4
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
                'estimated_duration_hours' => 12
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
                'estimated_duration_hours' => 32
            ],
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
                'estimated_duration_hours' => 24
            ],
            [
                'name' => 'Leadership Development Program',
                'code' => 'LEADERSHIP',
                'category' => 'Management',
                'validity_months' => null,
                'warning_days' => null,
                'is_mandatory' => false,
                'is_recurrent' => false,
                'description' => 'Comprehensive leadership development program for supervisors and managers',
                'requirements' => 'For employees in or aspiring to leadership positions',
                'learning_objectives' => 'Develop leadership skills, manage teams effectively, strategic thinking',
                'estimated_cost' => 2000000,
                'estimated_duration_hours' => 40
            ]
        ];

        $created = 0;
        $updated = 0;

        foreach ($certificateTypes as $typeData) {
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
        }

        $this->command->info("📊 Certificate Types: {$created} created, {$updated} updated");
        $this->command->info("✅ Certificate types seeding completed!");
    }
}
