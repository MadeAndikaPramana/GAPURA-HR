<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TrainingProvider;
use Carbon\Carbon;

class TrainingProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🏢 Seeding Training Providers...\n";

        $providers = [
            [
                'name' => 'PT. Aviasi Training Center',
                'code' => 'ATC001',
                'contact_person' => 'Budi Santoso',
                'email' => 'info@aviasitraining.co.id',
                'phone' => '+62 21 5550 1234',
                'address' => 'Jl. Bandara Soekarno-Hatta No. 123, Tangerang, Banten 15126',
                'website' => 'https://www.aviasitraining.co.id',
                'accreditation_number' => 'LSP-AVI-2024-001',
                'accreditation_expiry' => '2025-12-31',
                'contract_start_date' => '2024-01-01',
                'contract_end_date' => '2025-12-31',
                'rating' => 4.5,
                'notes' => 'Specialized in aviation safety and security training. Excellent track record with ground handling procedures.',
                'is_active' => true,
            ],
            [
                'name' => 'Indonesia Safety Institute',
                'code' => 'ISI002',
                'contact_person' => 'Sari Wijayanti',
                'email' => 'training@safeinstitute.id',
                'phone' => '+62 21 8880 5678',
                'address' => 'Gedung Safety Center Lt. 5, Jl. Sudirman No. 45, Jakarta Pusat 10220',
                'website' => 'https://www.safeinstitute.id',
                'accreditation_number' => 'LSP-SAF-2024-002',
                'accreditation_expiry' => '2026-06-30',
                'contract_start_date' => '2024-03-01',
                'contract_end_date' => '2026-02-28',
                'rating' => 4.8,
                'notes' => 'Premier safety training provider with international certifications. Specializes in workplace safety and emergency procedures.',
                'is_active' => true,
            ],
            [
                'name' => 'Garuda Training Solutions',
                'code' => 'GTS003',
                'contact_person' => 'Ahmad Rahman',
                'email' => 'contact@garudatraining.com',
                'phone' => '+62 21 7770 9012',
                'address' => 'Training Complex Garuda Indonesia, Jl. Airport Raya, Jakarta 14110',
                'website' => 'https://www.garudatraining.com',
                'accreditation_number' => 'LSP-GTS-2023-003',
                'accreditation_expiry' => '2025-03-15', // Expiring soon - for demo
                'contract_start_date' => '2023-06-01',
                'contract_end_date' => '2025-05-31',
                'rating' => 4.2,
                'notes' => 'Comprehensive aviation training including cabin crew, ground staff, and technical maintenance programs.',
                'is_active' => true,
            ],
            [
                'name' => 'Security Pro Training',
                'code' => 'SPT004',
                'contact_person' => 'Linda Setiawati',
                'email' => 'admin@securitypro.co.id',
                'phone' => '+62 21 6660 3456',
                'address' => 'Jl. Keamanan Raya No. 88, South Jakarta 12960',
                'website' => 'https://www.securitypro.co.id',
                'accreditation_number' => 'LSP-SEC-2024-004',
                'accreditation_expiry' => '2025-09-30',
                'contract_start_date' => '2024-02-15',
                'contract_end_date' => '2025-02-14',
                'rating' => 4.0,
                'notes' => 'Specialized in airport security, access control, and security awareness training programs.',
                'is_active' => true,
            ],
            [
                'name' => 'TechSkill Development Center',
                'code' => 'TDC005',
                'contact_person' => 'Eko Prasetyo',
                'email' => 'info@techskill.training',
                'phone' => '+62 21 4440 7890',
                'address' => 'Cyber Park Building, Jl. Technology Boulevard No. 12, BSD City, Tangerang',
                'website' => 'https://www.techskill.training',
                'accreditation_number' => 'LSP-TECH-2024-005',
                'accreditation_expiry' => '2026-11-30',
                'contract_start_date' => '2024-04-01',
                'contract_end_date' => '2026-03-31',
                'rating' => 4.3,
                'notes' => 'Technical training provider focusing on equipment operation, maintenance procedures, and technical competency development.',
                'is_active' => true,
            ],
            [
                'name' => 'Customer Excellence Academy',
                'code' => 'CEA006',
                'contact_person' => 'Maya Sinta',
                'email' => 'training@customerexcellence.id',
                'phone' => '+62 21 3330 2468',
                'address' => 'Service Training Hub, Jl. Pelayanan Prima No. 99, Jakarta',
                'website' => 'https://www.customerexcellence.id',
                'accreditation_number' => 'LSP-SVC-2024-006',
                'accreditation_expiry' => '2025-08-15',
                'contract_start_date' => '2024-01-15',
                'contract_end_date' => '2025-01-14',
                'rating' => 4.6,
                'notes' => 'Customer service and passenger assistance training specialist. Excellent for hospitality and service quality improvement.',
                'is_active' => true,
            ],
            [
                'name' => 'International Quality Systems',
                'code' => 'IQS007',
                'contact_person' => 'Robert Thompson',
                'email' => 'indonesia@iqsystems.com',
                'phone' => '+62 21 2220 1357',
                'address' => 'IQS Training Center, Jl. International Plaza No. 15, Jakarta',
                'website' => 'https://www.iqsystems.com',
                'accreditation_number' => 'ISO-QMS-2024-007',
                'accreditation_expiry' => '2027-01-31',
                'contract_start_date' => '2024-05-01',
                'contract_end_date' => '2026-04-30',
                'rating' => 4.7,
                'notes' => 'International standard ISO 9001 quality management training. High-quality delivery with global best practices.',
                'is_active' => true,
            ],
            [
                'name' => 'Legacy Training Institute',
                'code' => 'LTI008',
                'contact_person' => 'Indra Wijaya',
                'email' => 'legacy@training.net',
                'phone' => '+62 21 1110 8642',
                'address' => 'Old Training Complex, Jl. Veteran No. 67, Jakarta',
                'website' => null,
                'accreditation_number' => 'LSP-OLD-2022-008',
                'accreditation_expiry' => '2024-06-30', // Expired - for demo
                'contract_start_date' => '2022-01-01',
                'contract_end_date' => '2024-12-31',
                'rating' => 3.2,
                'notes' => 'Older training provider with traditional methods. Contract ending soon, accreditation expired.',
                'is_active' => false, // Inactive for demo
            ],
        ];

        foreach ($providers as $providerData) {
            $provider = TrainingProvider::updateOrCreate(
                ['code' => $providerData['code']], // Match by code
                $providerData
            );

            echo "   ✅ Created/Updated: {$provider->name} ({$provider->code})\n";
        }

        $totalProviders = TrainingProvider::count();
        $activeProviders = TrainingProvider::where('is_active', true)->count();
        $withAccreditation = TrainingProvider::whereNotNull('accreditation_number')->count();
        $expiringSoon = TrainingProvider::whereBetween('accreditation_expiry', [now(), now()->addDays(90)])->count();

        echo "\n📊 TRAINING PROVIDER SUMMARY:\n";
        echo "   📋 Total Providers: {$totalProviders}\n";
        echo "   ✅ Active Providers: {$activeProviders}\n";
        echo "   🛡️  With Accreditation: {$withAccreditation}\n";
        echo "   ⚠️  Expiring Soon (90 days): {$expiringSoon}\n";
        echo "\n🎯 Training Provider seeding completed!\n\n";
    }
}
