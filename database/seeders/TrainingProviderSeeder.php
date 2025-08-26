<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        // Internal Training Providers
        TrainingProvider::create([
            'name' => 'Gapura Learning Center',
            'code' => 'GLC',
            'contact_person' => 'Training Manager',
            'email' => 'training@gapura-angkasa.co.id',
            'phone' => '+62-21-5505555',
            'address' => 'Jl. Angkasa Blok B15 Kav.2&3, Jakarta 10720',
            'website' => 'https://gapura-angkasa.co.id',
            'accreditation_number' => 'ACC-GLC-2024',
            'accreditation_expiry' => Carbon::now()->addYears(3),
            'contract_start_date' => Carbon::now()->subYears(1),
            'contract_end_date' => Carbon::now()->addYears(2),
            'rating' => 4.8,
            'notes' => 'Internal training center for MPGA and safety training',
            'is_active' => true,
        ]);

        // External Aviation Training Providers
        TrainingProvider::create([
            'name' => 'Aviation Training Organization Indonesia',
            'code' => 'ATOI',
            'contact_person' => 'Capt. Ahmad Suryadi',
            'email' => 'contact@atoi.co.id',
            'phone' => '+62-21-5902000',
            'address' => 'Jakarta International Airport Complex',
            'website' => 'https://atoi.co.id',
            'accreditation_number' => 'DGCA-ATO-001',
            'accreditation_expiry' => Carbon::now()->addYears(2),
            'contract_start_date' => Carbon::now()->subMonths(6),
            'contract_end_date' => Carbon::now()->addYears(1),
            'rating' => 4.5,
            'notes' => 'DGCA approved training organization for aviation personnel',
            'is_active' => true,
        ]);

        TrainingProvider::create([
            'name' => 'Indonesia Aviation Academy',
            'code' => 'IAA',
            'contact_person' => 'Dr. Siti Rahmawati',
            'email' => 'academy@iaa.ac.id',
            'phone' => '+62-21-7918888',
            'address' => 'Jl. Raya Serpong, Tangerang Selatan',
            'website' => 'https://iaa.ac.id',
            'accreditation_number' => 'DGCA-AA-005',
            'accreditation_expiry' => Carbon::now()->addMonths(18),
            'rating' => 4.7,
            'notes' => 'Leading aviation training academy in Indonesia',
            'is_active' => true,
        ]);

        // Safety and Emergency Training Providers
        TrainingProvider::create([
            'name' => 'Indonesian Safety Training Center',
            'code' => 'ISTC',
            'contact_person' => 'Ir. Bambang Wijaya',
            'email' => 'safety@istc.co.id',
            'phone' => '+62-21-3456789',
            'address' => 'Jl. Industri Raya, Jakarta Timur',
            'website' => 'https://istc.co.id',
            'accreditation_number' => 'K3-ISTC-2024',
            'accreditation_expiry' => Carbon::now()->addYears(3),
            'rating' => 4.6,
            'notes' => 'Specialized in occupational health and safety training',
            'is_active' => true,
        ]);

        TrainingProvider::create([
            'name' => 'Fire Safety Training Institute',
            'code' => 'FSTI',
            'contact_person' => 'Drs. Agus Santoso',
            'email' => 'info@fsti.org',
            'phone' => '+62-21-2876543',
            'address' => 'Jl. Keselamatan No. 15, Jakarta Selatan',
            'accreditation_number' => 'FIRE-CERT-001',
            'accreditation_expiry' => Carbon::now()->addYears(2),
            'rating' => 4.4,
            'notes' => 'Emergency response and fire safety training specialist',
            'is_active' => true,
        ]);

        // International Training Providers
        TrainingProvider::create([
            'name' => 'International Civil Aviation Organization',
            'code' => 'ICAO',
            'contact_person' => 'Regional Training Coordinator',
            'email' => 'training@icao.int',
            'phone' => '+1-514-954-8219',
            'address' => '999 Robert-Bourassa Boulevard, Montreal, Quebec H3C 5H7, Canada',
            'website' => 'https://icao.int',
            'accreditation_number' => 'ICAO-GLOBAL',
            'rating' => 5.0,
            'notes' => 'International aviation standards and training',
            'is_active' => true,
        ]);

        TrainingProvider::create([
            'name' => 'Airports Council International',
            'code' => 'ACI',
            'contact_person' => 'Training Director',
            'email' => 'training@aci.aero',
            'phone' => '+1-514-373-1200',
            'address' => '800 Rue du Square Victoria, Montreal, QC H4Z 1G8, Canada',
            'website' => 'https://aci.aero',
            'accreditation_number' => 'ACI-WORLD',
            'rating' => 4.9,
            'notes' => 'Airport operations and management training worldwide',
            'is_active' => true,
        ]);

        // Technology Training Providers
        TrainingProvider::create([
            'name' => 'Digital Aviation Technology Institute',
            'code' => 'DATI',
            'contact_person' => 'Dr. Tech. Rudi Hartono',
            'email' => 'digital@dati.co.id',
            'phone' => '+62-21-8765432',
            'address' => 'Jl. Teknologi Digital, Jakarta Pusat',
            'website' => 'https://dati.co.id',
            'accreditation_number' => 'TECH-DATI-2024',
            'accreditation_expiry' => Carbon::now()->addYears(2),
            'rating' => 4.3,
            'notes' => 'Specialized in aviation technology and digital transformation',
            'is_active' => true,
        ]);

        // Regulatory and Compliance Training
        TrainingProvider::create([
            'name' => 'Directorate General of Civil Aviation',
            'code' => 'DGCA',
            'contact_person' => 'Training Division Head',
            'email' => 'training@dephub.go.id',
            'phone' => '+62-21-6546789',
            'address' => 'Jl. Merdeka Barat No. 8, Jakarta Pusat',
            'website' => 'https://hubud.dephub.go.id',
            'accreditation_number' => 'DGCA-OFFICIAL',
            'rating' => 4.7,
            'notes' => 'Official government aviation training and certification',
            'is_active' => true,
        ]);

        // Quality Management Training
        TrainingProvider::create([
            'name' => 'International Quality Management Institute',
            'code' => 'IQMI',
            'contact_person' => 'Quality Manager',
            'email' => 'quality@iqmi.org',
            'phone' => '+62-21-4567890',
            'address' => 'Jl. Kualitas Tinggi, Jakarta Selatan',
            'website' => 'https://iqmi.org',
            'accreditation_number' => 'ISO-IQMI-2024',
            'accreditation_expiry' => Carbon::now()->addYears(3),
            'rating' => 4.5,
            'notes' => 'ISO certification and quality management training',
            'is_active' => true,
        ]);
    }
}
