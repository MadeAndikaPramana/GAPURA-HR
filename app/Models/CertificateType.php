<?php
// app/Models/CertificateType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CertificateType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'validity_months',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'validity_months' => 'integer'
    ];

    /**
     * Get all employee certificates of this type
     */
    public function employeeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    /**
     * Get active certificates of this type
     */
    public function activeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->where('status', 'active');
    }

    /**
     * Get expired certificates of this type
     */
    public function expiredCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->where('status', 'expired');
    }

    /**
     * Get certificates expiring soon
     */
    public function expiringSoonCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class)->where('status', 'expiring_soon');
    }

    /**
     * Scope to get only active certificate types
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get certificate types by category
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Get certificate types for a specific department
     */
    public function scopeForDepartment(Builder $query, string $departmentCode): Builder
    {
        // Map department codes to certificate categories
        $categoryMap = [
            'GSE' => 'GSE_OPERATOR',
            'OPS' => 'OPERATIONS',
            'SEC' => 'AVSEC',
            'PAX' => 'PASSENGER_HANDLING',
            'CAR' => 'CARGO',
            'RAM' => 'RAMP'
        ];

        $category = $categoryMap[$departmentCode] ?? $departmentCode;

        return $query->where('category', $category);
    }

    /**
     * Generate standard certificate number for this type
     */
    public function generateCertificateNumber($employee = null): string
    {
        $year = now()->year;
        $month = now()->format('m');

        // Format: [ISSUER]/[CODE]-[SEQUENCE]/[MONTH]/[YEAR]
        // Example: GLC/GSEOP-400669/JUN/2024

        $monthName = now()->format('M'); // JUN, JUL, etc.

        $prefix = "GLC/{$this->code}";

        // Get next sequence number for this type and month
        $lastCertificate = EmployeeCertificate::where('certificate_type_id', $this->id)
            ->where('certificate_number', 'LIKE', "{$prefix}-%/{$monthName}/%")
            ->orderBy('certificate_number', 'desc')
            ->first();

        $sequence = 400001; // Start from MPGA's numbering system
        if ($lastCertificate) {
            $parts = explode('/', $lastCertificate->certificate_number);
            if (count($parts) >= 2) {
                $numberPart = explode('-', $parts[1]);
                if (count($numberPart) >= 2) {
                    $lastSequence = intval($numberPart[1]);
                    $sequence = $lastSequence + 1;
                }
            }
        }

        return "{$prefix}-{$sequence}/{$monthName}/{$year}";
    }

    /**
     * Get statistics for this certificate type
     */
    public function getStatistics(): array
    {
        $total = $this->employeeCertificates()->count();
        $active = $this->activeCertificates()->count();
        $expired = $this->expiredCertificates()->count();
        $expiring = $this->expiringSoonCertificates()->count();

        return [
            'total_certificates' => $total,
            'active_certificates' => $active,
            'expired_certificates' => $expired,
            'expiring_soon_certificates' => $expiring,
            'compliance_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 100
        ];
    }

    /**
     * Check if this certificate type requires renewal
     */
    public function requiresRenewal(): bool
    {
        return $this->validity_months > 0;
    }

    /**
     * Get the validity period in human readable format
     */
    public function getValidityPeriodAttribute(): string
    {
        if ($this->validity_months === 0) {
            return 'Lifetime';
        }

        if ($this->validity_months === 12) {
            return '1 Year';
        }

        if ($this->validity_months === 24) {
            return '2 Years';
        }

        if ($this->validity_months === 36) {
            return '3 Years';
        }

        return $this->validity_months . ' Months';
    }
}
