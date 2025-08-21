<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\TrainingRecord;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Send certificate issued notification
     */
    public function sendCertificateIssuedNotification(Certificate $certificate): bool
    {
        try {
            $template = NotificationTemplate::where('trigger_event', 'training_completed')
                ->where('is_active', true)
                ->first();

            if (!$template) {
                Log::warning('No template found for training_completed event');
                return false;
            }

            $employee = $certificate->trainingRecord->employee;
            $trainingType = $certificate->trainingRecord->trainingType;

            $variables = [
                'employee_name' => $employee->name,
                'training_name' => $trainingType->name,
                'completion_date' => $certificate->issue_date->format('d M Y'),
                'score' => $certificate->trainingRecord->score ?? 'N/A',
                'expiry_date' => $certificate->expiry_date?->format('d M Y') ?? 'No expiry',
                'certificate_number' => $certificate->certificate_number
            ];

            $content = $this->replacePlaceholders($template->content, $variables);
            $subject = $this->replacePlaceholders($template->subject, $variables);

            // Create system notification
            $notification = Notification::create([
                'recipient_id' => $employee->id,
                'type' => 'system',
                'title' => $subject,
                'message' => $content,
                'priority' => 'normal',
                'related_type' => 'certificate',
                'related_id' => $certificate->id,
                'data' => json_encode([
                    'certificate_id' => $certificate->id,
                    'verification_url' => $certificate->verification_url
                ])
            ]);

            // Send email if employee has email
            if ($employee->email && $template->type === 'email') {
                $this->sendEmail($employee->email, $subject, $content, $notification);
            }

            $notification->update(['status' => 'sent', 'sent_at' => now()]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send certificate issued notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send renewal reminder notification
     */
    public function sendRenewalReminder(Certificate $certificate, int $daysUntilExpiry): bool
    {
        try {
            $eventMap = [
                90 => 'certificate_expiry_90_days',
                60 => 'certificate_expiry_60_days',
                30 => 'certificate_expiry_30_days',
                7 => 'certificate_expiry_7_days'
            ];

            $event = $eventMap[$daysUntilExpiry] ?? 'certificate_expiry_30_days';

            $template = NotificationTemplate::where('trigger_event', $event)
                ->where('is_active', true)
                ->first();

            if (!$template) {
                Log::warning("No template found for {$event} event");
                return false;
            }

            $employee = $certificate->trainingRecord->employee;
            $trainingType = $certificate->trainingRecord->trainingType;

            $variables = [
                'employee_name' => $employee->name,
                'training_name' => $trainingType->name,
                'expiry_date' => $certificate->expiry_date->format('d M Y'),
                'certificate_number' => $certificate->certificate_number,
                'provider_name' => $certificate->trainingRecord->trainingProvider?->name ?? 'N/A',
                'status' => $certificate->status,
                'days_remaining' => $daysUntilExpiry
            ];

            $content = $this->replacePlaceholders($template->content, $variables);
            $subject = $this->replacePlaceholders($template->subject, $variables);

            // Determine priority based on days until expiry
            $priority = $this->determinePriority($daysUntilExpiry);

            // Create system notification
            $notification = Notification::create([
                'recipient_id' => $employee->id,
                'type' => 'system',
                'title' => $subject,
                'message' => $content,
                'priority' => $priority,
                'related_type' => 'certificate',
                'related_id' => $certificate->id,
                'data' => json_encode([
                    'certificate_id' => $certificate->id,
                    'days_until_expiry' => $daysUntilExpiry,
                    'action_required' => true
                ])
            ]);

            // Send email if employee has email
            if ($employee->email && $template->type === 'email') {
                $this->sendEmail($employee->email, $subject, $content, $notification);
            }

            // Also notify supervisor/manager for critical expiries
            if ($daysUntilExpiry <= 30 && $employee->supervisor_id) {
                $this->notifySupervisor($employee, $certificate, $daysUntilExpiry);
            }

            $notification->update(['status' => 'sent', 'sent_at' => now()]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send renewal reminder: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send expired certificate notification
     */
    public function sendExpiredCertificateNotification(Certificate $certificate): bool
    {
        try {
            $template = NotificationTemplate::where('trigger_event', 'certificate_expired')
                ->where('is_active', true)
                ->first();

            if (!$template) {
                Log::warning('No template found for certificate_expired event');
                return false;
            }

            $employee = $certificate->trainingRecord->employee;
            $trainingType = $certificate->trainingRecord->trainingType;

            $variables = [
                'employee_name' => $employee->name,
                'training_name' => $trainingType->name,
                'expiry_date' => $certificate->expiry_date->format('d M Y'),
                'certificate_number' => $certificate->certificate_number
            ];

            $content = $this->replacePlaceholders($template->content, $variables);
            $subject = $this->replacePlaceholders($template->subject, $variables);

            // Create urgent system notification
            $notification = Notification::create([
                'recipient_id' => $employee->id,
                'type' => 'system',
                'title' => $subject,
                'message' => $content,
                'priority' => 'urgent',
                'related_type' => 'certificate',
                'related_id' => $certificate->id,
                'data' => json_encode([
                    'certificate_id' => $certificate->id,
                    'compliance_issue' => true,
                    'action_required' => true
                ])
            ]);

            // Send email if employee has email
            if ($employee->email && $template->type === 'email') {
                $this->sendEmail($employee->email, $subject, $content, $notification);
            }

            // Notify supervisor and HR
            $this->notifySupervisor($employee, $certificate, 0);
            $this->notifyHR($employee, $certificate);

            $notification->update(['status' => 'sent', 'sent_at' => now()]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send expired certificate notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send training assignment notification
     */
    public function sendTrainingAssignmentNotification(TrainingRecord $trainingRecord): bool
    {
        try {
            $template = NotificationTemplate::where('trigger_event', 'training_assigned')
                ->where('is_active', true)
                ->first();

            if (!$template) {
                Log::warning('No template found for training_assigned event');
                return false;
            }

            $employee = $trainingRecord->employee;
            $trainingType = $trainingRecord->trainingType;

            $variables = [
                'employee_name' => $employee->name,
                'training_name' => $trainingType->name,
                'due_date' => $trainingRecord->training_date?->format('d M Y') ?? 'TBD',
                'provider_name' => $trainingRecord->trainingProvider?->name ?? 'TBD'
            ];

            $content = $this->replacePlaceholders($template->content, $variables);
            $subject = $this->replacePlaceholders($template->subject, $variables);

            // Create system notification
            $notification = Notification::create([
                'recipient_id' => $employee->id,
                'type' => 'system',
                'title' => $subject,
                'message' => $content,
                'priority' => $trainingType->is_mandatory ? 'high' : 'normal',
                'related_type' => 'training_record',
                'related_id' => $trainingRecord->id,
                'data' => json_encode([
                    'training_record_id' => $trainingRecord->id,
                    'is_mandatory' => $trainingType->is_mandatory
                ])
            ]);

            // Send email if employee has email
            if ($employee->email && $template->type === 'email') {
                $this->sendEmail($employee->email, $subject, $content, $notification);
            }

            $notification->update(['status' => 'sent', 'sent_at' => now()]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send training assignment notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send bulk notifications for expiring certificates
     */
    public function sendBulkExpiryReminders(): array
    {
        $results = [
            'total_sent' => 0,
            'by_period' => [],
            'errors' => []
        ];

        $periods = [90, 60, 30, 7];

        foreach ($periods as $days) {
            try {
                $certificates = Certificate::expiringIn($days)->get();
                $sent = 0;

                foreach ($certificates as $certificate) {
                    if ($this->sendRenewalReminder($certificate, $days)) {
                        $sent++;
                    }
                }

                $results['by_period'][$days] = [
                    'certificates_found' => $certificates->count(),
                    'notifications_sent' => $sent
                ];

                $results['total_sent'] += $sent;

            } catch (\Exception $e) {
                $results['errors'][] = "Failed to process {$days}-day reminders: " . $e->getMessage();
                Log::error("Bulk expiry reminder error for {$days} days: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Send daily compliance digest to managers
     */
    public function sendDailyComplianceDigest(): array
    {
        $results = [];

        try {
            // Get all department managers
            $managers = Employee::whereHas('managedDepartments')
                ->where('status', 'active')
                ->whereNotNull('email')
                ->with('managedDepartments')
                ->get();

            foreach ($managers as $manager) {
                $digestData = $this->generateComplianceDigest($manager);

                if ($digestData['total_issues'] > 0) {
                    $this->sendComplianceDigestEmail($manager, $digestData);
                    $results[] = [
                        'manager' => $manager->name,
                        'department' => $manager->managedDepartments->first()->name,
                        'issues' => $digestData['total_issues'],
                        'status' => 'sent'
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to send daily compliance digest: ' . $e->getMessage());
            $results[] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return $results;
    }

    /**
     * Replace template placeholders with actual values
     */
    private function replacePlaceholders(string $template, array $variables): string
    {
        $content = $template;

        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }

        return $content;
    }

    /**
     * Determine notification priority based on days until expiry
     */
    private function determinePriority(int $daysUntilExpiry): string
    {
        if ($daysUntilExpiry <= 7) return 'urgent';
        if ($daysUntilExpiry <= 30) return 'high';
        if ($daysUntilExpiry <= 60) return 'normal';
        return 'low';
    }

    /**
     * Send email notification
     */
    private function sendEmail(string $email, string $subject, string $content, Notification $notification): void
    {
        try {
            // Create email notification record
            $emailNotification = Notification::create([
                'recipient_id' => $notification->recipient_id,
                'type' => 'email',
                'title' => $subject,
                'message' => $content,
                'priority' => $notification->priority,
                'related_type' => $notification->related_type,
                'related_id' => $notification->related_id,
                'data' => $notification->data
            ]);

            // Send email using Laravel Mail
            Mail::send('emails.notification', compact('content'), function ($message) use ($email, $subject) {
                $message->to($email)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $emailNotification->update(['status' => 'sent', 'sent_at' => now()]);

        } catch (\Exception $e) {
            Log::error('Failed to send email notification: ' . $e->getMessage());

            if (isset($emailNotification)) {
                $emailNotification->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'retry_count' => $emailNotification->retry_count + 1
                ]);
            }
        }
    }

    /**
     * Notify supervisor about employee's certificate status
     */
    private function notifySupervisor(Employee $employee, Certificate $certificate, int $daysUntilExpiry): void
    {
        if (!$employee->supervisor_id) return;

        $supervisor = Employee::find($employee->supervisor_id);
        if (!$supervisor) return;

        $urgencyLevel = $daysUntilExpiry <= 0 ? 'EXPIRED' : 'EXPIRING';
        $timeframe = $daysUntilExpiry <= 0 ? 'has expired' : "expires in {$daysUntilExpiry} days";

        $subject = "[{$urgencyLevel}] Employee Certificate - {$employee->name}";
        $content = "Dear {$supervisor->name},

This is to inform you that {$employee->name}'s {$certificate->trainingRecord->trainingType->name} certificate {$timeframe}.

Employee Details:
- Name: {$employee->name}
- Employee ID: {$employee->employee_id}
- Department: {$employee->department->name}

Certificate Details:
- Training: {$certificate->trainingRecord->trainingType->name}
- Certificate Number: {$certificate->certificate_number}
- Expiry Date: {$certificate->expiry_date->format('d M Y')}

Please ensure the employee completes renewal training as soon as possible.

Best regards,
Gapura Training System";

        Notification::create([
            'recipient_id' => $supervisor->id,
            'type' => 'system',
            'title' => $subject,
            'message' => $content,
            'priority' => $daysUntilExpiry <= 0 ? 'urgent' : 'high',
            'related_type' => 'certificate',
            'related_id' => $certificate->id,
            'data' => json_encode([
                'employee_id' => $employee->id,
                'certificate_id' => $certificate->id,
                'supervisor_notification' => true
            ])
        ]);
    }

    /**
     * Notify HR about expired certificate
     */
    private function notifyHR(Employee $employee, Certificate $certificate): void
    {
        $hrEmployees = Employee::whereHas('department', function ($query) {
            $query->where('code', 'HR');
        })->where('status', 'active')->get();

        $subject = "[COMPLIANCE ALERT] Expired Certificate - {$employee->name}";
        $content = "COMPLIANCE ALERT

Employee {$employee->name} ({$employee->employee_id}) has an expired certificate:

Certificate Details:
- Training: {$certificate->trainingRecord->trainingType->name}
- Certificate Number: {$certificate->certificate_number}
- Expired On: {$certificate->expiry_date->format('d M Y')}
- Department: {$employee->department->name}

Immediate action required to restore compliance.

Best regards,
Gapura Training System";

        foreach ($hrEmployees as $hrEmployee) {
            Notification::create([
                'recipient_id' => $hrEmployee->id,
                'type' => 'system',
                'title' => $subject,
                'message' => $content,
                'priority' => 'urgent',
                'related_type' => 'certificate',
                'related_id' => $certificate->id,
                'data' => json_encode([
                    'employee_id' => $employee->id,
                    'certificate_id' => $certificate->id,
                    'hr_notification' => true,
                    'compliance_issue' => true
                ])
            ]);
        }
    }

    /**
     * Generate compliance digest data for a manager
     */
    private function generateComplianceDigest(Employee $manager): array
    {
        $departments = $manager->managedDepartments;
        $digestData = [
            'total_issues' => 0,
            'expired_certificates' => 0,
            'expiring_soon' => 0,
            'departments' => []
        ];

        foreach ($departments as $department) {
            $expired = Certificate::expired()
                ->whereHas('trainingRecord.employee', function ($query) use ($department) {
                    $query->where('department_id', $department->id);
                })->count();

            $expiringSoon = Certificate::expiringSoon(30)
                ->whereHas('trainingRecord.employee', function ($query) use ($department) {
                    $query->where('department_id', $department->id);
                })->count();

            $digestData['departments'][$department->name] = [
                'expired' => $expired,
                'expiring_soon' => $expiringSoon
            ];

            $digestData['expired_certificates'] += $expired;
            $digestData['expiring_soon'] += $expiringSoon;
        }

        $digestData['total_issues'] = $digestData['expired_certificates'] + $digestData['expiring_soon'];

        return $digestData;
    }

    /**
     * Send compliance digest email to manager
     */
    private function sendComplianceDigestEmail(Employee $manager, array $digestData): void
    {
        $subject = "Daily Compliance Digest - {$digestData['total_issues']} issues require attention";

        $content = "Dear {$manager->name},

Here's your daily training compliance summary:

SUMMARY:
- Expired Certificates: {$digestData['expired_certificates']}
- Expiring Soon (30 days): {$digestData['expiring_soon']}
- Total Issues: {$digestData['total_issues']}

DEPARTMENT BREAKDOWN:";

        foreach ($digestData['departments'] as $deptName => $deptData) {
            $content .= "\n- {$deptName}: {$deptData['expired']} expired, {$deptData['expiring_soon']} expiring soon";
        }

        $content .= "\n\nPlease review and take necessary action to maintain compliance.

Best regards,
Gapura Training System";

        if ($manager->email) {
            $this->sendEmail($manager->email, $subject, $content, new Notification([
                'recipient_id' => $manager->id,
                'type' => 'email',
                'title' => $subject,
                'message' => $content,
                'priority' => 'normal'
            ]));
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->update([
            'status' => 'read',
            'read_at' => now()
        ]);
    }

    /**
     * Get unread notifications for user
     */
    public function getUnreadNotifications(Employee $employee, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Notification::where('recipient_id', $employee->id)
            ->where('status', '!=', 'read')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
