<?php
// database/factories/CertificateFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Certificate;
use App\Models\Employee;
use App\Models\TrainingType;
use App\Models\TrainingProvider;
use App\Models\TrainingRecord;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Certificate>
 */
class CertificateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Certificate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-2 years', 'now');
        $validityMonths = fake()->randomElement([12, 24, 36, null]);
        $expiryDate = $validityMonths ? Carbon::parse($issueDate)->addMonths($validityMonths) : null;

        return [
            'certificate_number' => $this->generateCertificateNumber(),
            'certificate_type' => fake()->randomElement(['completion', 'competency', 'compliance']),
            'template_type' => fake()->randomElement(['standard', 'premium', 'compliance']),
            'employee_id' => Employee::factory(),
            'training_type_id' => TrainingType::factory(),
            'training_provider_id' => TrainingProvider::factory(),
            'issuer_name' => fake()->name(),
            'issuer_title' => fake()->randomElement([
                'Training Manager',
                'Director',
                'Certification Officer',
                'Head of Training',
                'Training Coordinator'
            ]),
            'issuer_organization' => fake()->company(),
            'issue_date' => $issueDate,
            'effective_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'issued_at' => Carbon::parse($issueDate)->addHours(fake()->numberBetween(1, 8)),
            'status' => fake()->randomElement(['draft', 'issued', 'expired', 'revoked']),
            'verification_status' => fake()->randomElement(['pending', 'verified', 'invalid', 'under_review']),
            'verification_code' => strtoupper(fake()->bothify('??##??##')),
            'score' => fake()->optional(0.8)->numberBetween(70, 100),
            'passing_score' => fake()->randomElement([70, 75, 80, 85]),
            'achievements' => fake()->optional(0.7)->sentences(3, true),
            'remarks' => fake()->optional(0.3)->sentence(),
            'is_renewable' => fake()->boolean(80),
            'renewal_count' => fake()->numberBetween(0, 3),
            'next_renewal_date' => $expiryDate ? Carbon::parse($expiryDate)->subDays(30) : null,
            'is_compliance_required' => fake()->boolean(60),
            'compliance_status' => fake()->randomElement(['compliant', 'non_compliant', 'pending', 'exempt']),
            'notes' => fake()->optional(0.4)->paragraph(),
            'print_status' => fake()->randomElement(['not_printed', 'printed', 'reprinted']),
            'print_count' => fake()->numberBetween(0, 2),
            'printed_at' => fake()->optional(0.6)->dateTimeBetween($issueDate, 'now'),
            'custom_fields' => fake()->optional(0.3)->randomElement([
                ['course_hours' => fake()->numberBetween(8, 40)],
                ['location' => fake()->city()],
                ['instructor' => fake()->name()]
            ]),
        ];
    }

    /**
     * Generate a unique certificate number
     */
    private function generateCertificateNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $sequence = fake()->unique()->numberBetween(1, 9999);

        return sprintf('CERT-%d%s-%04d', $year, $month, $sequence);
    }

    /**
     * Indicate that the certificate should be issued
     */
    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'issued',
            'verification_status' => fake()->randomElement(['verified', 'pending']),
            'issued_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the certificate should be active (issued and not expired)
     */
    public function active(): static
    {
        $issueDate = fake()->dateTimeBetween('-1 year', '-1 month');
        $expiryDate = Carbon::parse($issueDate)->addMonths(24); // 2 years validity

        return $this->state(fn (array $attributes) => [
            'status' => 'issued',
            'verification_status' => 'verified',
            'issue_date' => $issueDate,
            'effective_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'issued_at' => Carbon::parse($issueDate)->addHours(2),
            'compliance_status' => 'compliant',
        ]);
    }

    /**
     * Indicate that the certificate should be expired
     */
    public function expired(): static
    {
        $issueDate = fake()->dateTimeBetween('-3 years', '-1 year');
        $expiryDate = Carbon::parse($issueDate)->addMonths(24);

        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'verification_status' => 'verified',
            'issue_date' => $issueDate,
            'effective_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'issued_at' => Carbon::parse($issueDate)->addHours(2),
            'compliance_status' => 'non_compliant',
        ]);
    }

    /**
     * Indicate that the certificate should be expiring soon
     */
    public function expiringSoon(): static
    {
        $issueDate = fake()->dateTimeBetween('-2 years', '-1 year');
        $expiryDate = now()->addDays(fake()->numberBetween(1, 30)); // Expires within 30 days

        return $this->state(fn (array $attributes) => [
            'status' => 'issued',
            'verification_status' => 'verified',
            'issue_date' => $issueDate,
            'effective_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'issued_at' => Carbon::parse($issueDate)->addHours(2),
            'compliance_status' => 'compliant',
            'next_renewal_date' => $expiryDate->copy()->subDays(30),
        ]);
    }

    /**
     * Indicate that the certificate should be a draft
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'verification_status' => 'pending',
            'verification_code' => null,
            'issued_at' => null,
        ]);
    }

    /**
     * Indicate that the certificate should be revoked
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'revoked',
            'verification_status' => 'invalid',
            'compliance_status' => 'non_compliant',
            'notes' => 'Certificate has been revoked due to compliance issues.',
        ]);
    }

    /**
     * Indicate that the certificate should be renewable
     */
    public function renewable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_renewable' => true,
            'renewal_count' => fake()->numberBetween(0, 2),
        ]);
    }

    /**
     * Indicate that the certificate should be a renewal of another certificate
     */
    public function renewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_certificate_id' => Certificate::factory(),
            'renewal_count' => fake()->numberBetween(1, 3),
            'status' => 'issued',
            'verification_status' => 'verified',
            'notes' => 'Renewal of previous certificate',
        ]);
    }

    /**
     * Indicate that the certificate should have high score
     */
    public function highScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(90, 100),
            'passing_score' => 80,
        ]);
    }

    /**
     * Indicate that the certificate should be compliance-required
     */
    public function complianceRequired(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_compliance_required' => true,
            'compliance_status' => fake()->randomElement(['compliant', 'pending']),
        ]);
    }

    /**
     * Indicate that the certificate should be verified
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'verified',
            'last_verified_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'verified_by' => fake()->name(),
        ]);
    }

    /**
     * Indicate that the certificate should be printed
     */
    public function printed(): static
    {
        return $this->state(fn (array $attributes) => [
            'print_status' => fake()->randomElement(['printed', 'reprinted']),
            'print_count' => fake()->numberBetween(1, 3),
            'printed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Configure the model factories.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Certificate $certificate) {
            // Automatically set expiry date based on training type if not set
            if (!$certificate->expiry_date && $certificate->trainingType) {
                $validityMonths = $certificate->trainingType->validity_period_months ?? 24;
                if ($validityMonths > 0) {
                    $certificate->expiry_date = Carbon::parse($certificate->issue_date)->addMonths($validityMonths);
                }
            }
        })->afterCreating(function (Certificate $certificate) {
            // Update related training record if exists
            if ($certificate->training_record_id) {
                $certificate->trainingRecord()->update([
                    'certificate_number' => $certificate->certificate_number,
                    'status' => 'completed'
                ]);
            }
        });
    }
}
