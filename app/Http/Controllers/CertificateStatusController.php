<?php
// app/Http/Controllers/CertificateStatusController.php

namespace App\Http\Controllers;

use App\Models\EmployeeCertificate;
use Illuminate\Http\Request;

class CertificateStatusController extends Controller
{
    /**
     * Update all certificate statuses based on expiry dates
     * This should be run daily via CRON
     */
    public function updateAllCertificateStatuses()
    {
        $today = now()->startOfDay();
        $updated = 0;

        // Get all active certificates with expiry dates
        EmployeeCertificate::whereIn('status', ['active', 'expiring_soon', 'completed'])
            ->whereNotNull('expiry_date')
            ->chunk(100, function ($certificates) use ($today, &$updated) {
                foreach ($certificates as $certificate) {
                    $oldStatus = $certificate->status;
                    $certificate->updateStatusBasedOnDates();

                    if ($certificate->status !== $oldStatus) {
                        $updated++;
                    }
                }
            });

        return response()->json([
            'message' => 'Certificate statuses updated successfully',
            'updated_count' => $updated,
            'processed_at' => now()->toISOString()
        ]);
    }

    /**
     * Get certificates requiring attention (expired, expiring soon)
     */
    public function getCertificatesRequiringAttention()
    {
        $certificates = EmployeeCertificate::with(['employee', 'certificateType'])
            ->whereIn('status', ['expired', 'expiring_soon'])
            ->orderBy('expiry_date')
            ->get();

        return response()->json([
            'certificates' => $certificates->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'employee_name' => $cert->employee->name,
                    'employee_id' => $cert->employee->employee_id,
                    'certificate_type' => $cert->certificateType->name,
                    'certificate_number' => $cert->certificate_number,
                    'status' => $cert->status,
                    'expiry_date' => $cert->expiry_date?->format('Y-m-d'),
                    'days_until_expiry' => $cert->expiry_date ?
                        now()->diffInDays($cert->expiry_date, false) : null
                ];
            })
        ]);
    }
}
