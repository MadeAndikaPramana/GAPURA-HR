<?php
// database/seeders/CertificateTypesSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CertificateType;
use Illuminate\Support\Facades\DB;

class CertificateTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏆 Seeding MPGA Certificate Types...');

        DB::beginTransaction();

        try {
            $certificateTypes = [
                // GSE OPERATOR Certificates
                [
                    'name' => 'Aircraft Towing Tractor (ATT)',
                    'code' => 'ATT',
                    'category' => 'GSE_OPERATOR',
                    'validity_months' => 24,
                    'description' => 'Certificate for operating Aircraft Towing Tractor equipment'
                ],
                [
                    'name' => 'Fork Lift / Ramp Equipment (FRM)',
                    'code' => 'FRM',
                    'category' => 'GSE_OPERATOR',
                    'validity_months' => 24,
                    'description' => 'Certificate for operating Fork Lift and Ramp Equipment'
                ],
                [
                    'name' => 'Low Loader (LLD)',
                    'code' => 'LLD',
                    'category' => 'GSE_OPERATOR',
                    'validity_months' => 24,
                    'description' => 'Certificate for operating Low Loader equipment'
                ],
                [
                    'name' => 'Baggage Towing Tractor (BTT)',
                    'code' => 'BTT',
                    'category' => 'GSE_OPERATOR',
                    'validity_months' => 24,
                    'description' => 'Certificate for operating Baggage Towing Tractor equipment'
                ],
                [
                    'name' => 'Belt Conveyor System (BCS)',
                    'code' => 'BCS',
                    'category' => 'GSE_OPERATOR',
                    'validity_months' => 24,
                    'description' => 'Certificate for operating Belt Conveyor System equipment'
                ],
                [
                    'name' => 'Pushback System (PBS)',
                    'code' => 'PBS',
                    'category' => 'GSE_OPERATOR',
                    'validity_months' => 24,
                    'description' => 'Certificate for operating Pushback System equipment'
                ],

                // AVSEC (Aviation Security) Certificates
                [
                    'name' => 'Aviation Security Basic',
                    'code' => 'AVSEC_BASIC',
                    'category' => 'AVSEC',
                    'validity_months' => 36,
                    'description' => 'Basic Aviation Security training and certification'
                ],
                [
                    'name' => 'Aviation Security Advanced',
                    'code' => 'AVSEC_ADV',
                    'category' => 'AVSEC',
                    'validity_months' => 36,
                    'description' => 'Advanced Aviation Security training and certification'
                ],
                [
                    'name' => 'Aviation Security Screening',
                    'code' => 'AVSEC_SCREEN',
                    'category' => 'AVSEC',
                    'validity_months' => 24,
                    'description' => 'Aviation Security Screening procedures certification'
                ],

                // PASSENGER HANDLING Certificates
                [
                    'name' => 'Passenger & Baggage Handling',
                    'code' => 'PAX_HANDLING',
                    'category' => 'PASSENGER_HANDLING',
                    'validity_months' => 36,
                    'description' => 'Comprehensive passenger and baggage handling procedures'
                ],
                [
                    'name' => 'Passenger Handling Dedicated',
                    'code' => 'PAX_DEDICATED',
                    'category' => 'PASSENGER_HANDLING',
                    'validity_months' => 36,
                    'description' => 'Dedicated passenger handling services certification'
                ],
                [
                    'name' => 'Special Assistance Passenger Handling',
                    'code' => 'PAX_SPECIAL',
                    'category' => 'PASSENGER_HANDLING',
                    'validity_months' => 36,
                    'description' => 'Special assistance and disabled passenger handling'
                ],

                // RAMP OPERATIONS Certificates
                [
                    'name' => 'Ramp Operations',
                    'code' => 'RAMP_OPS',
                    'category' => 'RAMP',
                    'validity_months' => 24,
                    'description' => 'General ramp operations and safety procedures'
                ],
                [
                    'name' => 'Ramp Safety',
                    'code' => 'RAMP_SAFETY',
                    'category' => 'RAMP',
                    'validity_months' => 24,
                    'description' => 'Ramp safety protocols and emergency procedures'
                ],
                [
                    'name' => 'Aircraft Marshalling',
                    'code' => 'RAMP_MARSHAL',
                    'category' => 'RAMP',
                    'validity_months' => 24,
                    'description' => 'Aircraft marshalling and guidance procedures'
                ],

                // LOADING OPERATIONS Certificates
                [
                    'name' => 'Loading Operations',
                    'code' => 'LOADING_OPS',
                    'category' => 'LOADING',
                    'validity_months' => 24,
                    'description' => 'Aircraft loading and unloading operations'
                ],
                [
                    'name' => 'Weight & Balance',
                    'code' => 'LOADING_WB',
                    'category' => 'LOADING',
                    'validity_months' => 24,
                    'description' => 'Weight and balance calculations for aircraft loading'
                ],
                [
                    'name' => 'Dangerous Goods Loading',
                    'code' => 'LOADING_DG',
                    'category' => 'LOADING',
                    'validity_months' => 24,
                    'description' => 'Dangerous goods handling and loading procedures'
                ],

                // CARGO OPERATIONS Certificates
                [
                    'name' => 'Cargo Operations',
                    'code' => 'CARGO_OPS',
                    'category' => 'CARGO',
                    'validity_months' => 24,
                    'description' => 'General cargo handling and operations'
                ],
                [
                    'name' => 'Cargo Screening',
                    'code' => 'CARGO_SCREEN',
                    'category' => 'CARGO',
                    'validity_months' => 24,
                    'description' => 'Cargo security screening procedures'
                ],
                [
                    'name' => 'Cold Chain Cargo',
                    'code' => 'CARGO_COLD',
                    'category' => 'CARGO',
                    'validity_months' => 24,
                    'description' => 'Temperature-controlled cargo handling'
                ],

                // ULD (Unit Load Device) Certificates
                [
                    'name' => 'ULD Operations',
                    'code' => 'ULD_OPS',
                    'category' => 'ULD',
                    'validity_months' => 24,
                    'description' => 'Unit Load Device handling and operations'
                ],
                [
                    'name' => 'ULD Inspection',
                    'code' => 'ULD_INSPECT',
                    'category' => 'ULD',
                    'validity_months' => 24,
                    'description' => 'ULD inspection and serviceability checks'
                ],

                // ARRIVAL OPERATIONS Certificates
                [
                    'name' => 'Arrival Operations',
                    'code' => 'ARRIVAL_OPS',
                    'category' => 'ARRIVAL',
                    'validity_months' => 36,
                    'description' => 'Aircraft arrival and passenger disembarkation procedures'
                ],
                [
                    'name' => 'Baggage Arrival Handling',
                    'code' => 'ARRIVAL_BAG',
                    'category' => 'ARRIVAL',
                    'validity_months' => 36,
                    'description' => 'Baggage arrival and delivery procedures'
                ],

                // PORTER Certificates
                [
                    'name' => 'Porter Training',
                    'code' => 'PORTER',
                    'category' => 'PORTER',
                    'validity_months' => 36,
                    'description' => 'Porter services and customer handling'
                ],
                [
                    'name' => 'VIP Porter Services',
                    'code' => 'PORTER_VIP',
                    'category' => 'PORTER',
                    'validity_months' => 36,
                    'description' => 'VIP and premium passenger services'
                ],

                // LOST & FOUND Certificates
                [
                    'name' => 'Lost & Found Operations',
                    'code' => 'LOST_FOUND',
                    'category' => 'LOST_FOUND',
                    'validity_months' => 36,
                    'description' => 'Lost and found baggage handling procedures'
                ],

                // FLOP (Flight Operations) Certificates
                [
                    'name' => 'Flight Operations Officer',
                    'code' => 'FLOP',
                    'category' => 'FLOP',
                    'validity_months' => 12,
                    'description' => 'Flight operations officer procedures and responsibilities'
                ],

                // GENERAL Training Certificates
                [
                    'name' => 'Human Factor Training',
                    'code' => 'HUMAN_FACTOR',
                    'category' => 'GENERAL',
                    'validity_months' => 36,
                    'description' => 'Human factors in aviation operations'
                ],
                [
                    'name' => 'Safety Management System',
                    'code' => 'SMS',
                    'category' => 'GENERAL',
                    'validity_months' => 24,
                    'description' => 'Safety Management System training'
                ],
                [
                    'name' => 'Emergency Response',
                    'code' => 'EMERGENCY',
                    'category' => 'GENERAL',
                    'validity_months' => 24,
                    'description' => 'Emergency response procedures and protocols'
                ],
                [
                    'name' => 'First Aid & CPR',
                    'code' => 'FIRST_AID',
                    'category' => 'GENERAL',
                    'validity_months' => 24,
                    'description' => 'First aid and CPR certification'
                ],
                [
                    'name' => 'Fire Safety',
                    'code' => 'FIRE_SAFETY',
                    'category' => 'GENERAL',
                    'validity_months' => 12,
                    'description' => 'Fire safety and prevention training'
                ]
            ];

            foreach ($certificateTypes as $type) {
                CertificateType::updateOrCreate(
                    ['code' => $type['code']],
                    $type
                );

                $this->command->line("  ✅ {$type['name']} ({$type['code']}) - {$type['validity_months']} months");
            }

            DB::commit();

            $this->command->newLine();
            $this->command->info("🎉 Successfully seeded " . count($certificateTypes) . " certificate types!");
            $this->command->info("📋 Certificate categories created:");

            $categories = collect($certificateTypes)->pluck('category')->unique();
            foreach ($categories as $category) {
                $count = collect($certificateTypes)->where('category', $category)->count();
                $this->command->line("   🏷️  {$category}: {$count} types");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Failed to seed certificate types: ' . $e->getMessage());
            throw $e;
        }
    }
}
