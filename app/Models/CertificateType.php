<?php
// app/Models/CertificateType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateType extends Model
{
    protected $fillable = [
        'name', 'code', 'category', 'validity_months', 'warning_days',
        'is_mandatory', 'is_recurrent', 'description', 'requirements',
        'learning_objectives', 'is_active', 'estimated_cost', 'estimated_duration_hours'
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_recurrent' => 'boolean',
        'is_active' => 'boolean',
        'estimated_cost' => 'decimal:2',
        'estimated_duration_hours' => 'decimal:2',
    ];

    // ===== RELATIONSHIPS =====

    public function employeeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeRecurrent($query)
    {
        return $query->where('is_recurrent', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // ===== UTILITY METHODS =====

    /**
     * Get active certificates count for this type
     */
    public function getActiveCertificatesCount()
    {
        return $this->employeeCertificates()->where('status', 'active')->count();
    }

    /**
     * Get expired certificates count for this type
     */
    public function getExpiredCertificatesCount()
    {
        return $this->employeeCertificates()->where('status', 'expired')->count();
    }

    /**
     * Get expiring soon certificates count for this type
     */
    public function getExpiringSoonCertificatesCount()
    {
        return $this->employeeCertificates()->where('status', 'expiring_soon')->count();
    }

    /**
     * Get total certificates count for this type
     */
    public function getTotalCertificatesCount()
    {
        return $this->employeeCertificates()->count();
    }

    /**
     * Get employees who have this certificate type
     */
    public function getEmployeesWithCertificate()
    {
        return Employee::whereHas('employeeCertificates', function($query) {
            $query->where('certificate_type_id', $this->id);
        })->get();
    }

    /**
     * Check if certificate type is required
     */
    public function isRequired()
    {
        return $this->is_mandatory;
    }

    /**
     * Check if certificate type can be renewed
     */
    public function canBeRenewed()
    {
        return $this->is_recurrent;
    }

    /**
     * Get validity period in days
     */
    public function getValidityInDays()
    {
        return $this->validity_months ? $this->validity_months * 30 : null;
    }

    /**
     * Get warning period in days
     */
    public function getWarningDays()
    {
        return $this->warning_days ?? 90;
    }
}
