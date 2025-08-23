<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingRecord;
use App\Services\TrainingStatusService;
use Carbon\Carbon;
use Faker\Factory as Faker;

class TrainingRecordsSeeder extends Seeder
{
    /**
     * Seed the training records table.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $statusService = app(TrainingStatusService::class);

        $this->command->info('🌱 Seeding Training Records...');

        // Get all employees and training types
        $employees = Employee::with('department')->get();
        $trainingTypes = TrainingType::where('is_active', true)->get();

        if ($employees->isEmpty() || $trainingTypes->isEmpty()) {
            $this->command->warn('⚠️  No employees or training types found. Please seed them first.');
            return;
        }

        $trainingProviders = [
            'PT Safety Training Indonesia',
            'Aviation Training Center',
            'GAPURA Training Academy',
            'International Safety Institute',
            'DG Awareness Training',
            'Security Training Solutions',
            'Professional Development Center',
            'Technical Training Institute'
        ];

        $locations = [
            'Jakarta Training Center',
            'Surabaya Branch Office',
            'Denpasar Training Facility',
            'Makassar Regional Office',
            'Online Training Platform',
            'Head Office Meeting Room',
            'External Training Venue',
            'Airport Training Facility'
        ];

        $instructors = [
            'Dr. Ahmad Susanto',
            'Capt. Maria Sari',
            'Eng. Budi Hartono',
            'Prof. Sri Wahyuni',
            'Mr. John Anderson',
            'Ms. Lisa Chen',
            'Ir. Agus Pratama',
            'Dr. Rina Kusuma'
        ];

        $recordsCreated = 0;
        $batchSize = 50;

        // Create training records for each employee
        foreach ($employees as $employee) {
            // Each employee gets 2-5 different training records
            $numRecords = $faker->numberBetween(2, 5);
            $selectedTrainingTypes = $trainingTypes->random($numRecords);

            foreach ($selectedTrainingTypes as $trainingType) {
                // Create 1-3 records per training type (including renewals)
                $recordsPerType = $faker->numberBetween(1, 3);

                for ($i = 0; $i < $recordsPerType; $i++) {
                    // Generate dates
                    $issueDate = $this->generateRealisticIssueDate($faker, $i);
                    $completionDate = $issueDate->copy()->addDays($faker->numberBetween(0, 7));

                    // Calculate expiry date
                    $expiryDate = null;
                    if ($trainingType->validity_months) {
                        $expiryDate = $issueDate->copy()->addMonths($trainingType->validity_months);
                    }

                    // Generate certificate number
                    $certificateNumber = $statusService->generateCertificateNumber($employee, $trainingType);

                    // Calculate status
                    $status = $statusService->calculateStatus($expiryDate?->format('Y-m-d'));
                    $complianceStatus = $statusService->calculateComplianceStatus($status);

                    // Create training record
                    TrainingRecord::create([
                        'employee_id' => $employee->id,
                        'training_type_id' => $trainingType->id,
                        'certificate_number' => $certificateNumber,
                        'issuer' => $faker->randomElement($trainingProviders),
                        'issue_date' => $issueDate->format('Y-m-d'),
                        'completion_date' => $completionDate->format('Y-m-d'),
                        'expiry_date' => $expiryDate?->format('Y-m-d'),
                        'status' => $status,
                        'compliance_status' => $complianceStatus,
                        'score' => $faker->optional(0.8)->randomFloat(1, 70, 100),
                        'training_hours' => $faker->optional(0.7)->numberBetween(8, 40),
                        'cost' => $faker->optional(0.6)->numberBetween(500000, 5000000),
                        'location' => $faker->randomElement($locations),
                        'instructor_name' => $faker->optional(0.8)->randomElement($instructors),
                        'notes' => $faker->optional(0.4)->sentence(),
                        'created_by_id' => 1, // Assuming admin user ID is 1
                        'created_at' => $issueDate,
                        'updated_at' => $issueDate,
                    ]);

                    $recordsCreated++;

                    // Progress indicator
                    if ($recordsCreated % $batchSize === 0) {
                        $this->command->info("✅ Created {$recordsCreated} training records...");
                    }
                }
            }
        }

        $this->command->info("🎉 Successfully created {$recordsCreated} training records!");

        // Show statistics
        $this->showStatistics();
    }

    /**
     * Generate realistic issue dates (older records first, newer ones later)
     */
    private function generateRealisticIssueDate($faker, int $recordIndex): Carbon
    {
        $baseDate = match ($recordIndex) {
            0 => Carbon::now()->subMonths($faker->numberBetween(12, 36)), // First record: 1-3 years ago
            1 => Carbon::now()->subMonths($faker->numberBetween(6, 18)),  // Second record: 6-18 months ago
            default => Carbon::now()->subMonths($faker->numberBetween(1, 12)) // Recent records: 1-12 months ago
        };

        return $baseDate->addDays($faker->numberBetween(-30, 30));
    }

    /**
     * Show seeding statistics
     */
    private function showStatistics(): void
    {
        $statusService = app(TrainingStatusService::class);
        $stats = $statusService->getComplianceStatistics();

        $this->command->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Total Certificates', $stats['total_certificates'], '100%'],
                ['Active', $stats['active_certificates'], $stats['compliance_rate'] . '%'],
                ['Expiring Soon', $stats['expiring_certificates'], $stats['expiring_rate'] . '%'],
                ['Expired', $stats['expired_certificates'], $stats['expired_rate'] . '%'],
            ]
        );
    }
}
