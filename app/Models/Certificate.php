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

// app/Models/EmployeeCertificate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'certificate_type_id',
        'certificate_number',
        'issue_date',
        'expiry_date',
        'completion_date',
        'training_date',
        'issuer',
        'training_provider',
        'score',
        'passing_score',
        'training_hours',
        'cost',
        'location',
        'instructor_name',
        'status',
        'notes',
        'files',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'completion_date' => 'date',
        'training_date' => 'date',
        'files' => 'array',
        'score' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'training_hours' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    protected $dates = [
        'issue_date',
        'expiry_date',
        'completion_date',
        'training_date',
    ];

    /**
     * Get the employee that owns the certificate
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the certificate type
     */
    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class);
    }

    /**
     * Get the user who created the certificate
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the certificate
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active certificates
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired certificates
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for expiring soon certificates
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('status', 'expiring_soon');
    }

    /**
     * Update certificate status based on current date
     */
    public function updateStatus()
    {
        if (!$this->expiry_date) {
            $this->update(['status' => 'active']);
            return;
        }

        $now = Carbon::now();
        $expiry = Carbon::parse($this->expiry_date);
        $warningDate = $expiry->copy()->subDays(30); // 30 days warning

        if ($now->gt($expiry)) {
            $status = 'expired';
        } elseif ($now->gte($warningDate)) {
            $status = 'expiring_soon';
        } else {
            $status = 'active';
        }

        $this->update(['status' => $status]);
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }

        return Carbon::now()->diffInDays(Carbon::parse($this->expiry_date), false);
    }

    /**
     * Check if certificate is expired
     */
    public function getIsExpiredAttribute()
    {
        return $this->status === 'expired';
    }

    /**
     * Check if certificate is expiring soon
     */
    public function getIsExpiringSoonAttribute()
    {
        return $this->status === 'expiring_soon';
    }

    /**
     * Check if certificate is active
     */
    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'active' => 'green',
            'expiring_soon' => 'yellow',
            'expired' => 'red',
            'pending' => 'blue',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get files count
     */
    public function getFilesCountAttribute()
    {
        return count($this->files ?? []);
    }
}

// app/Models/Department.php (if not exists)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the employees for this department
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Scope for active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
