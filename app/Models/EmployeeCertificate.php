<?php
// app/Models/EmployeeCertificate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeCertificate extends Model
{
    protected $fillable = [
        'employee_id', 'certificate_type_id', 'certificate_number',
        'issuer', 'training_provider', 'issue_date', 'expiry_date',
        'completion_date', 'training_date', 'status', 'certificate_files',
        'training_hours', 'cost', 'score', 'location', 'instructor_name',
        'notes', 'reminder_sent_at', 'reminder_count', 'created_by_id', 'updated_by_id'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'completion_date' => 'date',
        'training_date' => 'date',
        'certificate_files' => 'array',
        'reminder_sent_at' => 'datetime',
        'training_hours' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    // ===== RELATIONSHIPS =====

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    // ===== STATUS MANAGEMENT =====

    /**
     * Update certificate status based on dates
     */
    public function updateStatusBasedOnDates()
    {
        if (!$this->expiry_date) {
            $this->status = $this->completion_date ? 'completed' : 'pending';
        } else {
            $now = Carbon::now();
            $expiryDate = $this->expiry_date;

            // Get warning days from certificate type or use default
            $warningDays = $this->certificateType->warning_days ?? 90;
            $warningDate = $expiryDate->subDays($warningDays);

            if ($expiryDate->isPast()) {
                $this->status = 'expired';
            } elseif ($now->greaterThanOrEqualTo($warningDate)) {
                $this->status = 'expiring_soon';
            } elseif ($this->completion_date) {
                $this->status = 'active';
            } else {
                $this->status = 'pending';
            }
        }

        $this->save();
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiringSoon($query)
    {
        return $query->where('status', 'expiring_soon');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ===== UTILITY METHODS =====

    /**
     * Check if certificate is expired
     */
    public function isExpired()
    {
        return $this->status === 'expired';
    }

    /**
     * Check if certificate is expiring soon
     */
    public function isExpiringSoon()
    {
        return $this->status === 'expiring_soon';
    }

    /**
     * Check if certificate is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry()
    {
        if (!$this->expiry_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get formatted expiry status
     */
    public function getExpiryStatus()
    {
        if (!$this->expiry_date) {
            return ['status' => 'no_expiry', 'message' => 'No expiry date'];
        }

        $days = $this->getDaysUntilExpiry();

        if ($days < 0) {
            return [
                'status' => 'expired',
                'message' => 'Expired ' . abs($days) . ' days ago',
                'color' => 'red'
            ];
        } elseif ($days <= 30) {
            return [
                'status' => 'expiring_soon',
                'message' => 'Expires in ' . $days . ' days',
                'color' => 'yellow'
            ];
        } else {
            return [
                'status' => 'active',
                'message' => 'Expires in ' . $days . ' days',
                'color' => 'green'
            ];
        }
    }

    /**
     * Get certificate file count
     */
    public function getFileCount()
    {
        return count($this->certificate_files ?? []);
    }

    /**
     * Check if certificate has files
     */
    public function hasFiles()
    {
        return !empty($this->certificate_files);
    }
}
