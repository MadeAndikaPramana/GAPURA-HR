<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CertificateType;

class CertificateTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏆 Seeding Certificate Types for Employee Container System...');

        $certificateTypes = [
            // === EXISTING TYPES (MAINTAINED FOR COMPATIBILITY) ===
            [
                'name' => 'Aircraft Towing Tractor (ATT)',
                'code' => 'ATT',
                'category' => 'Ground Support Equipment',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Certificate for operating aircraft towing tractors',
                'requirements' => 'Ground support equipment operators, practical assessment required',
                'learning_objectives' => 'Safe operation of aircraft towing equipment, pre-flight checks, emergency procedures',
                'estimated_cost' => 500000,
                'estimated_duration_hours' => 24,
                'is_active' => true
            ],
            [
                'name' => 'Fork Lift Management (FRM)',
                'code' => 'FRM',
                'category' => 'Ground Support Equipment',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Forklift operation and management certification',
                'requirements' => 'Cargo handling and warehouse operations',
                'learning_objectives' => 'Safe forklift operation, load handling, maintenance procedures',
                'estimated_cost' => 450000,
                'estimated_duration_hours' => 20,
                'is_active' => true
            ],
            [
                'name' => 'Leadership Development (LLD)',
                'code' => 'LLD',
                'category' => 'Management',
                'validity_months' => null,
                'warning_days' => null,
                'is_mandatory' => false,
                'is_recurrent' => false,
                'description' => 'Comprehensive leadership development program for supervisors and managers',
                'requirements' => 'For employees in or aspiring to leadership positions',
                'learning_objectives' => 'Develop leadership skills, manage teams effectively, strategic thinking',
                'estimated_cost' => 2000000,
                'estimated_duration_hours' => 40,
                'is_active' => true
            ],

            // === AVIATION SAFETY & EMERGENCY RESPONSE ===
            [
                'name' => 'Fire Safety & Emergency Response',
                'code' => 'FIRE_SAFETY',
                'category' => 'Aviation Safety',
                'validity_months' => 24,
                'warning_days' => 90,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Comprehensive fire safety training including aircraft fire suppression, evacuation procedures, and emergency equipment operation',
                'requirements' => 'Mandatory for all airport ground personnel, firefighting certification',
                'learning_objectives' => 'Aircraft fire suppression, emergency evacuation, fire extinguisher operation, hazmat response',
                'estimated_cost' => 750000,
                'estimated_duration_hours' => 16,
                'is_active' => true
            ],
            [
                'name' => 'First Aid & Medical Emergency Response',
                'code' => 'FIRST_AID_CPR',
                'category' => 'Aviation Safety',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Aviation-specific first aid, CPR, and emergency medical response training',
                'requirements' => 'Mandatory for all ground staff and supervisors, medical certification required',
                'learning_objectives' => 'Aviation medical emergencies, CPR, defibrillator use, trauma response, oxygen therapy',
                'estimated_cost' => 650000,
                'estimated_duration_hours' => 20,
                'is_active' => true
            ],
            [
                'name' => 'Emergency Response Procedures',
                'code' => 'EMERGENCY_RESPONSE',
                'category' => 'Aviation Safety',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Comprehensive emergency response training for aviation incidents and accidents',
                'requirements' => 'All airside personnel, emergency drill participation',
                'learning_objectives' => 'Emergency communication, evacuation procedures, incident command system, crisis management',
                'estimated_cost' => 850000,
                'estimated_duration_hours' => 24,
                'is_active' => true
            ],

            // === AVIATION SECURITY ===
            [
                'name' => 'Aviation Security Awareness (AVSEC)',
                'code' => 'AVSEC_BASIC',
                'category' => 'Aviation Security',
                'validity_months' => 24,
                'warning_days' => 90,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Basic aviation security awareness training covering ICAO and national AVSEC regulations',
                'requirements' => 'Mandatory for all personnel with airside access, security clearance required',
                'learning_objectives' => 'AVSEC regulations, threat identification, security procedures, access control',
                'estimated_cost' => 400000,
                'estimated_duration_hours' => 12,
                'is_active' => true
            ],
            [
                'name' => 'Airside Security & Access Control',
                'code' => 'AIRSIDE_SECURITY',
                'category' => 'Aviation Security',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Advanced airside security training including access control, restricted area procedures, and security screening',
                'requirements' => 'Personnel working in restricted areas, background check required',
                'learning_objectives' => 'Access control systems, restricted area procedures, security screening, incident reporting',
                'estimated_cost' => 550000,
                'estimated_duration_hours' => 16,
                'is_active' => true
            ],

            // === GROUND HANDLING OPERATIONS ===
            [
                'name' => 'Ground Handling Basic Safety',
                'code' => 'GH_BASIC_SAFETY',
                'category' => 'Ground Handling',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Basic ground handling safety training covering ramp safety, aircraft proximity, and personal protective equipment',
                'requirements' => 'All ground handling personnel, safety orientation required',
                'learning_objectives' => 'Ramp safety procedures, aircraft proximity protocols, PPE usage, hazard recognition',
                'estimated_cost' => 350000,
                'estimated_duration_hours' => 12,
                'is_active' => true
            ],
            [
                'name' => 'Aircraft Marshalling & Positioning',
                'code' => 'AIRCRAFT_MARSHALLING',
                'category' => 'Ground Handling',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Training for aircraft marshalling, positioning, and ground movement coordination',
                'requirements' => 'Ramp operations personnel, practical assessment required',
                'learning_objectives' => 'Standard marshalling signals, aircraft positioning, communication protocols, safety zones',
                'estimated_cost' => 450000,
                'estimated_duration_hours' => 16,
                'is_active' => true
            ],
            [
                'name' => 'Cargo & Baggage Handling',
                'code' => 'CARGO_HANDLING',
                'category' => 'Ground Handling',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Cargo and baggage handling procedures including dangerous goods awareness and load planning',
                'requirements' => 'Cargo handling personnel, physical fitness assessment',
                'learning_objectives' => 'Load planning, weight distribution, dangerous goods identification, handling procedures',
                'estimated_cost' => 400000,
                'estimated_duration_hours' => 20,
                'is_active' => true
            ],

            // === DANGEROUS GOODS & HAZMAT ===
            [
                'name' => 'Dangerous Goods Awareness (DGA)',
                'code' => 'DGA',
                'category' => 'Dangerous Goods',
                'validity_months' => 24,
                'warning_days' => 90,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'IATA/ICAO Dangerous Goods Regulations awareness training for aviation personnel',
                'requirements' => 'All personnel handling cargo, mail, or passenger baggage',
                'learning_objectives' => 'DGR classification, packaging requirements, documentation, emergency response',
                'estimated_cost' => 600000,
                'estimated_duration_hours' => 16,
                'is_active' => true
            ],
            [
                'name' => 'Dangerous Goods Acceptance (Category 6)',
                'code' => 'DG_CAT6',
                'category' => 'Dangerous Goods',
                'validity_months' => 24,
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'IATA Category 6 dangerous goods acceptance training for cargo operations',
                'requirements' => 'Cargo acceptance personnel, DGA prerequisite required',
                'learning_objectives' => 'Acceptance procedures, documentation verification, packaging inspection, storage requirements',
                'estimated_cost' => 950000,
                'estimated_duration_hours' => 32,
                'is_active' => true
            ],
            [
                'name' => 'Lithium Battery Handling',
                'code' => 'LITHIUM_BATTERY',
                'category' => 'Dangerous Goods',
                'validity_months' => 24,
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Specialized training for handling lithium batteries in aviation transport',
                'requirements' => 'Personnel handling electronic devices and cargo containing lithium batteries',
                'learning_objectives' => 'Battery classification, damage assessment, fire suppression, regulatory compliance',
                'estimated_cost' => 400000,
                'estimated_duration_hours' => 8,
                'is_active' => true
            ],

            // === DGCA COMPLIANCE & REGULATORY ===
            [
                'name' => 'DGCA Basic Safety Training',
                'code' => 'DGCA_BASIC_SAFETY',
                'category' => 'Regulatory Compliance',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => true,
                'is_recurrent' => true,
                'description' => 'Indonesian DGCA mandated basic safety training for aviation personnel',
                'requirements' => 'All aviation personnel as per DGCA regulations, government certification',
                'learning_objectives' => 'DGCA safety standards, regulatory compliance, incident reporting, safety management systems',
                'estimated_cost' => 750000,
                'estimated_duration_hours' => 20,
                'is_active' => true
            ],
            [
                'name' => 'Air Traffic Control Awareness',
                'code' => 'ATC_AWARENESS',
                'category' => 'Regulatory Compliance',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'ATC procedures and communication training for ground personnel',
                'requirements' => 'Ground operations and flight line personnel',
                'learning_objectives' => 'ATC communication, radio procedures, airspace awareness, coordination protocols',
                'estimated_cost' => 500000,
                'estimated_duration_hours' => 16,
                'is_active' => true
            ],

            // === GROUND SUPPORT EQUIPMENT ===
            [
                'name' => 'Ground Support Equipment Basic Operation',
                'code' => 'GSE_BASIC',
                'category' => 'Ground Support Equipment',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Basic operation training for common ground support equipment',
                'requirements' => 'GSE operators, mechanical aptitude assessment',
                'learning_objectives' => 'Equipment operation, safety procedures, basic maintenance, troubleshooting',
                'estimated_cost' => 400000,
                'estimated_duration_hours' => 16,
                'is_active' => true
            ],
            [
                'name' => 'Aircraft Towing & Pushback Operations',
                'code' => 'TOWING_PUSHBACK',
                'category' => 'Ground Support Equipment',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Specialized training for aircraft towing and pushback operations',
                'requirements' => 'Experienced GSE operators, aircraft type endorsement',
                'learning_objectives' => 'Aircraft towing procedures, pushback operations, communication protocols, emergency procedures',
                'estimated_cost' => 650000,
                'estimated_duration_hours' => 20,
                'is_active' => true
            ],
            [
                'name' => 'Aircraft Loading Equipment Operation',
                'code' => 'LOADING_EQUIPMENT',
                'category' => 'Ground Support Equipment',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Operation of aircraft loading equipment including belt loaders, container loaders, and cargo loaders',
                'requirements' => 'Cargo handling personnel, equipment-specific endorsement',
                'learning_objectives' => 'Loading equipment operation, load distribution, safety protocols, maintenance checks',
                'estimated_cost' => 550000,
                'estimated_duration_hours' => 24,
                'is_active' => true
            ],

            // === FUEL & FLUID HANDLING ===
            [
                'name' => 'Aircraft Fuel Handling & Safety',
                'code' => 'FUEL_HANDLING',
                'category' => 'Fuel Operations',
                'validity_months' => 24,
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Training for aircraft fuel handling, quality control, and safety procedures',
                'requirements' => 'Fuel operations personnel, hazmat certification required',
                'learning_objectives' => 'Fuel quality testing, contamination prevention, fire safety, environmental protection',
                'estimated_cost' => 800000,
                'estimated_duration_hours' => 28,
                'is_active' => true
            ],
            [
                'name' => 'De-icing & Anti-icing Operations',
                'code' => 'DEICING_OPERATIONS',
                'category' => 'Fuel Operations',
                'validity_months' => 12,
                'warning_days' => 30,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Aircraft de-icing and anti-icing procedures for cold weather operations',
                'requirements' => 'Ground operations personnel in cold climates, seasonal certification',
                'learning_objectives' => 'De-icing procedures, fluid application, contamination assessment, weather monitoring',
                'estimated_cost' => 450000,
                'estimated_duration_hours' => 12,
                'is_active' => true
            ],

            // === QUALITY ASSURANCE & MANAGEMENT ===
            [
                'name' => 'Safety Management System (SMS)',
                'code' => 'SMS_TRAINING',
                'category' => 'Quality Management',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Safety Management System training covering risk management and safety culture',
                'requirements' => 'Supervisors and safety personnel, management endorsement',
                'learning_objectives' => 'Risk assessment, safety culture, incident investigation, continuous improvement',
                'estimated_cost' => 900000,
                'estimated_duration_hours' => 24,
                'is_active' => true
            ],
            [
                'name' => 'Human Factors in Aviation',
                'code' => 'HUMAN_FACTORS',
                'category' => 'Quality Management',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Human factors training focusing on error prevention and performance optimization',
                'requirements' => 'All operational personnel, psychological assessment recommended',
                'learning_objectives' => 'Error prevention, situational awareness, communication, fatigue management, stress handling',
                'estimated_cost' => 650000,
                'estimated_duration_hours' => 16,
                'is_active' => true
            ],

            // === ENVIRONMENTAL & COMPLIANCE ===
            [
                'name' => 'Environmental Management & Compliance',
                'code' => 'ENVIRONMENTAL_MGMT',
                'category' => 'Environmental',
                'validity_months' => 36,
                'warning_days' => 90,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Environmental management training covering waste handling, spill response, and regulatory compliance',
                'requirements' => 'Operations and maintenance personnel, environmental certification',
                'learning_objectives' => 'Waste management, spill response, environmental regulations, pollution prevention',
                'estimated_cost' => 400000,
                'estimated_duration_hours' => 12,
                'is_active' => true
            ],
            [
                'name' => 'Noise Abatement Procedures',
                'code' => 'NOISE_ABATEMENT',
                'category' => 'Environmental',
                'validity_months' => 24,
                'warning_days' => 60,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Airport noise abatement procedures and community relations training',
                'requirements' => 'Ground operations and flight line personnel',
                'learning_objectives' => 'Noise reduction procedures, community relations, operational restrictions, monitoring compliance',
                'estimated_cost' => 250000,
                'estimated_duration_hours' => 8,
                'is_active' => true
            ],

            // === BASIC COMPETENCY TRAINING ===
            [
                'name' => 'Airport Familiarization & Orientation',
                'code' => 'AIRPORT_ORIENTATION',
                'category' => 'Basic Competency',
                'validity_months' => 12,
                'warning_days' => 30,
                'is_mandatory' => true,
                'is_recurrent' => false,
                'description' => 'Comprehensive airport familiarization and safety orientation for new personnel',
                'requirements' => 'All new employees before airside access, mandatory induction',
                'learning_objectives' => 'Airport layout, safety zones, emergency procedures, basic regulations, communication systems',
                'estimated_cost' => 200000,
                'estimated_duration_hours' => 8,
                'is_active' => true
            ],
            [
                'name' => 'English Proficiency for Aviation',
                'code' => 'AVIATION_ENGLISH',
                'category' => 'Basic Competency',
                'validity_months' => 60,
                'warning_days' => 120,
                'is_mandatory' => false,
                'is_recurrent' => true,
                'description' => 'Aviation English proficiency training for international operations and communications',
                'requirements' => 'Personnel involved in international operations, ICAO Level 4 minimum',
                'learning_objectives' => 'Aviation terminology, radio communications, technical English, emergency phraseology',
                'estimated_cost' => 1200000,
                'estimated_duration_hours' => 40,
                'is_active' => true
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
