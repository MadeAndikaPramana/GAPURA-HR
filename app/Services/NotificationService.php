<?php
// app/Services/NotificationService.php
// TEMPORARY FIX: Disable notification methods untuk Phase 2

namespace App\Services;

use App\Models\Certificate;
use App\Models\TrainingRecord;
use App\Models\Employee;
// use App\Models\Notification;          // ❌ Comment sementara
// use App\Models\NotificationTemplate;  // ❌ Comment sementara
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationService
{
    /**
     * PHASE 2 TEMPORARY: Return early dari semua notification methods
     * TODO: Implement properly di Phase 3 setelah models dibuat
     */

    /**
     * Send certificate issued notification
     */
    public function sendCertificateIssuedNotification($certificate): bool
    {
        // PHASE 2: Skip notification, return success
        Log::info('Notification skipped - Phase 2 mode', [
            'type' => 'certificate_issued',
            'certificate_id' => $certificate->id ?? 'unknown'
        ]);
        return true;
    }

    /**
     * Send training assignment notification
     */
    public function sendTrainingAssignmentNotification($trainingRecord): bool
    {
        // PHASE 2: Skip notification, return success
        Log::info('Notification skipped - Phase 2 mode', [
            'type' => 'training_assigned',
            'training_record_id' => $trainingRecord->id ?? 'unknown'
        ]);
        return true;
    }

    /**
     * Send renewal reminder notification
     */
    public function sendRenewalReminder($certificate, int $daysUntilExpiry): bool
    {
        // PHASE 2: Skip notification, return success
        Log::info('Notification skipped - Phase 2 mode', [
            'type' => 'renewal_reminder',
            'certificate_id' => $certificate->id ?? 'unknown',
            'days_until_expiry' => $daysUntilExpiry
        ]);
        return true;
    }

    /**
     * Send expiry warning notification
     */
    public function sendExpiryWarningNotification($certificate): bool
    {
        // PHASE 2: Skip notification, return success
        Log::info('Notification skipped - Phase 2 mode', [
            'type' => 'expiry_warning',
            'certificate_id' => $certificate->id ?? 'unknown'
        ]);
        return true;
    }

    /**
     * Send bulk expiry reminders
     */
    public function sendBulkExpiryReminders(): array
    {
        // PHASE 2: Return dummy success data
        Log::info('Bulk notifications skipped - Phase 2 mode');

        return [
            'total_sent' => 0,
            'by_period' => [
                30 => ['notifications_sent' => 0, 'certificates_found' => 0],
                7 => ['notifications_sent' => 0, 'certificates_found' => 0],
            ],
            'errors' => []
        ];
    }

    /**
     * Send daily compliance digest
     */
    public function sendDailyComplianceDigest(): array
    {
        // PHASE 2: Return dummy success data
        Log::info('Compliance digest skipped - Phase 2 mode');

        return [
            [
                'status' => 'skipped',
                'manager' => 'System',
                'department' => 'All',
                'issues' => 0
            ]
        ];
    }

    /**
     * Determine notification priority (helper method yang tetap bisa digunakan)
     */
    private function determinePriority(int $daysUntilExpiry): string
    {
        if ($daysUntilExpiry <= 7) {
            return 'urgent';
        } elseif ($daysUntilExpiry <= 30) {
            return 'high';
        } elseif ($daysUntilExpiry <= 60) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Replace placeholders in template (helper method)
     */
    private function replacePlaceholders(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }

    /**
     * Send email (helper method untuk Phase 3 nanti)
     */
    private function sendEmail(string $email, string $subject, string $content, $notification = null): bool
    {
        // PHASE 2: Skip actual email sending
        Log::info('Email skipped - Phase 2 mode', [
            'to' => $email,
            'subject' => $subject
        ]);
        return true;
    }
}

/*
TODO PHASE 3: Implementasi lengkap notification system
1. Buat migration untuk notifications table
2. Buat migration untuk notification_templates table
3. Buat model Notification
4. Buat model NotificationTemplate
5. Uncomment semua use statements di atas
6. Implement actual notification logic
7. Setup email configuration
8. Create notification templates
*/
