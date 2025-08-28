<?php
// database/seeders/CertificateSeeder.php - FIXED VERSION

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Certificate;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingProvider;
use App\Models\TrainingRecord;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CertificateSeeder extends Seeder
{
    private static $certificateCounter = 1; // Static counter for unique numbers

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Certificate seeding...');

        // Clear existing certificates to avoid duplicates
        Certificate::truncate();

        // Reset counter
        self::$certificateCounter = 1;

        // Get necessary data
        $employees = Employee::where('status', 'active')->get();
        $trainingTypes = TrainingType::where('is_active', true)->get();
        $trainingProviders = TrainingProvider::where('is_active', true)->get();
        $trainingRecords = TrainingRecord::with(['employee', 'trainingType', 'trainingProvider'])->get();

        if ($employees->isEmpty() || $trainingTypes->isEmpty()) {
            $this->command->warn('Not enough data to create certificates. Please seed employees and training types first.');
            return;
        }

        $this->command->info("Found {$employees->count()} employees and {$trainingTypes->count()} training types");

        // Create certificates from existing training records first
        $certificatesFromRecords = $this->createCertificatesFromTrainingRecords($trainingRecords);
        $this->command->info("Created {$certificatesFromRecords} certificates from training records");

        // Create additional sample certificates
        $additionalCertificates = $this->createAdditionalCertificates($employees, $trainingTypes, $trainingProviders);
        $this->command->info("Created {$additionalCertificates} additional sample certificates");

        // Create some renewal certificates
        $renewalCertificates = $this->createRenewalCertificates();
        $this->command->info("Created {$renewalCertificates} renewal certificates");

        $total = Certificate::count();
        $this->command->info("Certificate seeding completed! Total certificates: {$total}");

        // Display statistics
        $this->displayStatistics();
    }

    /**
     * Generate unique certificate number - FIXED VERSION
     */
    private function generateUniqueCertificateNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');

        // Use static counter to ensure uniqueness
        $sequence = self::$certificateCounter;
        self::$certificateCounter++;

        $certificateNumber = sprintf('CERT-%d%s-%04d', $year, $month, $sequence);

        // Double-check uniqueness in database
        while (Certificate::where('certificate_number', $certificateNumber)->exists()) {
            $sequence = self::$certificateCounter;
            self::$certificateCounter++;
            $certificateNumber = sprintf('CERT-%d%s-%04d', $year, $month, $sequence);
        }

        return $certificateNumber;
    }

    /**
     * Create certificates from existing training records
     */
    private function createCertificatesFromTrainingRecords($trainingRecords): int
    {
        $count = 0;
        $certificateTypes = ['completion', 'competency', 'compliance'];
        $statuses = ['issued', 'draft'];
        $verificationStatuses = ['verified', 'pending'];

        foreach ($trainingRecords->take(50) as $record) {
            if (!$record->employee || !$record->trainingType) {
                continue;
            }

            // Skip if certificate already exists for this record
            if (Certificate::where('training_record_id', $record->id)->exists()) {
                continue;
            }

            try {
                $issueDate = $record->completion_date ?? $record->issue_date ?? now()->subDays(rand(1, 365));
                $expiryDate = $record->expiry_date ??
                             (($record->trainingType->validity_period_months ?? 24) > 0
                              ? $issueDate->copy()->addMonths($record->trainingType->validity_period_months ?? 24)
                              : null);

                $status = fake()->randomElement($statuses);
                $isExpired = $expiryDate && $expiryDate->isPast();

                if ($isExpired) {
                    $status = 'expired';
                }

                Certificate::create([
                    'certificate_number' => $this->generateUniqueCertificateNumber(), // Use fixed method
                    'training_record_id' => $record->id,
                    'employee_id' => $record->employee_id,
                    'training_type_id' => $record->training_type_id,
                    'training_provider_id' => $record->training_provider_id,
                    'certificate_type' => fake()->randomElement($certificateTypes),
                    'issuer_name' => $this->getIssuerName($record->trainingProvider),
                    'issuer_title' => fake()->randomElement(['Training Manager', 'Director', 'Certification Officer', 'Head of Training']),
                    'issuer_organization' => $record->trainingProvider->name ?? 'GAPURA TRAINING CENTER',
                    'issue_date' => $issueDate,
                    'effective_date' => $issueDate,
                    'expiry_date' => $expiryDate,
                    'issued_at' => $issueDate->copy()->addHours(rand(1, 8)),
                    'status' => $status,
                    'verification_status' => $status === 'issued' ? fake()->randomElement($verificationStatuses) : 'pending',
                    'score' => $record->score ?? fake()->numberBetween(75, 100),
                    'passing_score' => $record->passing_score ?? 75,
                    'achievements' => $this->generateAchievements($record->trainingType),
                    'remarks' => fake()->optional(0.3)->sentence(),
                    'is_renewable' => fake()->boolean(80),
                    'is_compliance_required' => fake()->boolean(60),
                    'compliance_status' => $this->getComplianceStatus($isExpired, $expiryDate),
                    'notes' => fake()->optional(0.4)->sentence(),
                    'created_at' => $issueDate,
                    'updated_at' => $issueDate,
                ]);

                $count++;
            } catch (\Exception $e) {
                $this->command->error("Error creating certificate for record {$record->id}: " . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Create additional sample certificates
     */
    private function createAdditionalCertificates($employees, $trainingTypes, $trainingProviders): int
    {
        $count = 0;
        $certificateTypes = ['completion', 'competency', 'compliance'];
        $statuses = ['issued', 'draft', 'expired'];
        $verificationStatuses = ['verified', 'pending', 'under_review'];

        // Create 100 additional certificates
        for ($i = 0; $i < 100; $i++) {
            try {
                $employee = $employees->random();
                $trainingType = $trainingTypes->random();
                $trainingProvider = $trainingProviders->isNotEmpty() ? $trainingProviders->random() : null;

                $issueDate = fake()->dateTimeBetween('-2 years', 'now');
                $validityMonths = $trainingType->validity_period_months ?? fake()->randomElement([12, 24, 36]);
                $expiryDate = $validityMonths > 0 ? Carbon::parse($issueDate)->addMonths($validityMonths) : null;

                $status = fake()->randomElement($statuses);
                $isExpired = $expiryDate && $expiryDate->isPast();

                if ($isExpired && fake()->boolean(70)) {
                    $status = 'expired';
                }

                Certificate::create([
                    'certificate_number' => $this->generateUniqueCertificateNumber(), // Use fixed method
                    'employee_id' => $employee->id,
                    'training_type_id' => $trainingType->id,
                    'training_provider_id' => $trainingProvider?->id,
                    'certificate_type' => fake()->randomElement($certificateTypes),
                    'issuer_name' => $this->getIssuerName($trainingProvider),
                    'issuer_title' => fake()->randomElement([
                        'Training Manager', 'Director', 'Certification Officer',
                        'Head of Training', 'Training Coordinator', 'Principal Instructor'
                    ]),
                    'issuer_organization' => $trainingProvider?->name ?? 'GAPURA TRAINING CENTER',
                    'issue_date' => $issueDate,
                    'effective_date' => $issueDate,
                    'expiry_date' => $expiryDate,
                    'issued_at' => Carbon::parse($issueDate)->addHours(rand(1, 8)),
                    'status' => $status,
                    'verification_status' => $status === 'issued' ? fake()->randomElement($verificationStatuses) : 'pending',
                    'score' => fake()->optional(0.8)->numberBetween(65, 100),
                    'passing_score' => fake()->randomElement([70, 75, 80, 85]),
                    'achievements' => $this->generateAchievements($trainingType),
                    'remarks' => fake()->optional(0.3)->sentence(),
                    'is_renewable' => fake()->boolean(80),
                    'is_compliance_required' => fake()->boolean(60),
                    'compliance_status' => $this->getComplianceStatus($isExpired, $expiryDate),
                    'notes' => fake()->optional(0.4)->sentence(),
                    'print_status' => fake()->randomElement(['not_printed', 'printed', 'reprinted']),
                    'print_count' => fake()->numberBetween(0, 3),
                    'printed_at' => fake()->optional(0.6)->dateTimeBetween($issueDate, 'now'),
                    'created_at' => $issueDate,
                    'updated_at' => fake()->dateTimeBetween($issueDate, 'now'),
                ]);

                $count++;
            } catch (\Exception $e) {
                $this->command->error("Error creating additional certificate {$i}: " . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Create renewal certificates
     */
    private function createRenewalCertificates(): int
    {
        $count = 0;

        // Get expired certificates that are renewable
        $expiredCertificates = Certificate::where('status', 'expired')
                                        ->where('is_renewable', true)
                                        ->where('expiry_date', '>=', now()->subMonths(6)) // Recently expired
                                        ->take(20)
                                        ->get();

        foreach ($expiredCertificates as $expiredCert) {
            try {
                // Create renewal with 70% chance
                if (fake()->boolean(70)) {
                    $renewalData = [
                        'certificate_number' => $this->generateUniqueCertificateNumber(), // Use fixed method
                        'employee_id' => $expiredCert->employee_id,
                        'training_type_id' => $expiredCert->training_type_id,
                        'training_provider_id' => $expiredCert->training_provider_id,
                        'parent_certificate_id' => $expiredCert->id,
                        'certificate_type' => $expiredCert->certificate_type,
                        'issuer_name' => $expiredCert->issuer_name,
                        'issuer_title' => $expiredCert->issuer_title,
                        'issuer_organization' => $expiredCert->issuer_organization,
                        'issue_date' => fake()->dateTimeBetween($expiredCert->expiry_date, 'now'),
                        'renewal_count' => $expiredCert->renewal_count + 1,
                        'status' => 'issued',
                        'verification_status' => 'verified',
                        'is_renewable' => true,
                        'score' => fake()->numberBetween(75, 100),
                        'passing_score' => 75,
                    ];

                    $renewalData['effective_date'] = $renewalData['issue_date'];
                    $renewalData['issued_at'] = Carbon::parse($renewalData['issue_date'])->addHours(rand(1, 8));

                    // Set expiry date
                    $validityMonths = $expiredCert->trainingType->validity_period_months ?? 24;
                    if ($validityMonths > 0) {
                        $renewalData['expiry_date'] = Carbon::parse($renewalData['issue_date'])->addMonths($validityMonths);
                        $renewalData['next_renewal_date'] = Carbon::parse($renewalData['expiry_date'])->subDays(30);
                    }

                    $renewalData['compliance_status'] = 'compliant';
                    $renewalData['achievements'] = $this->generateAchievements($expiredCert->trainingType);
                    $renewalData['notes'] = "Renewal of certificate #{$expiredCert->certificate_number}";

                    Certificate::create($renewalData);
                    $count++;

                    // Mark original certificate as renewed
                    $expiredCert->update(['status' => 'renewed']);
                }
            } catch (\Exception $e) {
                $this->command->error("Error creating renewal for certificate {$expiredCert->id}: " . $e->getMessage());
            }
        }

        return $count;
    }

    // ... rest of the helper methods remain the same ...

    /**
     * Generate achievements text based on training type
     */
    private function generateAchievements($trainingType): string
    {
        $baseAchievements = [
            'Successfully completed all training modules',
            'Demonstrated competency in required skills',
            'Passed practical and theoretical assessments',
            'Met all compliance requirements',
        ];

        $specificAchievements = [
            'MPGA' => ['Ground handling procedures', 'Safety protocols', 'Equipment operation'],
            'DG Awareness' => ['Dangerous goods identification', 'Handling procedures', 'Documentation requirements'],
            'Safety' => ['Safety management systems', 'Risk assessment', 'Emergency procedures'],
            'Security' => ['Security protocols', 'Threat recognition', 'Access control procedures'],
        ];

        $achievements = fake()->randomElements($baseAchievements, rand(2, 3));

        if ($trainingType && isset($specificAchievements[$trainingType->category])) {
            $specific = fake()->randomElements($specificAchievements[$trainingType->category], rand(1, 2));
            $achievements = array_merge($achievements, $specific);
        }

        return implode('; ', $achievements);
    }

    /**
     * Get issuer name based on provider
     */
    private function getIssuerName($trainingProvider): string
    {
        if ($trainingProvider && $trainingProvider->contact_person) {
            return $trainingProvider->contact_person;
        }

        return fake()->randomElement([
            'Dr. Ahmad Wijaya',
            'Ir. Sari Kusuma',
            'Drs. Budi Santoso',
            'M. Rizki Pratama',
            'Indira Sari, M.Si',
            'Bambang Surya, SE',
            'Rina Kartika, S.T',
            'Hendra Kusuma, M.T'
        ]);
    }

    /**
     * Determine compliance status
     */
    private function getComplianceStatus(bool $isExpired, ?Carbon $expiryDate): string
    {
        if ($isExpired) {
            return 'non_compliant';
        }

        if ($expiryDate && $expiryDate->diffInDays(now()) <= 30) {
            return fake()->randomElement(['compliant', 'pending']);
        }

        return fake()->randomElement(['compliant', 'pending', 'exempt']);
    }

    /**
     * Display seeding statistics
     */
    private function displayStatistics(): void
    {
        $this->command->info("\n=== Certificate Statistics ===");

        $stats = [
            'Total Certificates' => Certificate::count(),
            'Issued' => Certificate::where('status', 'issued')->count(),
            'Active' => Certificate::active()->count(),
            'Expired' => Certificate::expired()->count(),
            'Expiring Soon (30 days)' => Certificate::expiring(30)->count(),
            'Draft' => Certificate::where('status', 'draft')->count(),
            'Revoked' => Certificate::where('status', 'revoked')->count(),
            'Verified' => Certificate::where('verification_status', 'verified')->count(),
            'Renewable' => Certificate::where('is_renewable', true)->count(),
            'With Renewals' => Certificate::whereNotNull('parent_certificate_id')->count(),
        ];

        foreach ($stats as $label => $count) {
            $this->command->line("  {$label}: {$count}");
        }

        // Department breakdown
        $this->command->info("\n=== By Department ===");
        $deptStats = DB::select("
            SELECT d.name, COUNT(c.id) as certificate_count
            FROM certificates c
            JOIN employees e ON c.employee_id = e.id
            JOIN departments d ON e.department_id = d.id
            GROUP BY d.id, d.name
            ORDER BY certificate_count DESC
        ");

        foreach ($deptStats as $stat) {
            $this->command->line("  {$stat->name}: {$stat->certificate_count}");
        }

        $this->command->info("\n=== Seeding Complete! ===");
    }
}
