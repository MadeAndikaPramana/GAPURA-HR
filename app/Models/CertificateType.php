<?php
// app/Models/CertificateType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'typical_validity_months',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the employee certificates for this type
     */
    public function employeeCertificates()
    {
        return $this->hasMany(EmployeeCertificate::class);
    }

    /**
     * Scope for active certificate types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
