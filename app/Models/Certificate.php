<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_record_id',
        'certificate_number',
        'issued_by',
        'issue_date',
        'expiry_date',
        'verification_code',
        'digital_signature',
        'certificate_file_path',
        'qr_code_path',
        'is_verified',
        'verification_date',
        'verified_by_id',
        'notes'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
        'verification_date' => 'datetime'
    ];

    protected $appends = [
        'status',
        'days_until_expiry',
        'verification_url'
    ];

    /**
     * Boot method to generate verification code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($certificate) {
            if (empty($certificate->verification_code)) {
                $certificate->verification_code = static::generateVerificationCode();
            }
        });
    }

    /**
     * Generate unique verification code
     */
    public static function generateVerificationCode()
    {
        do {
            $code = 'CERT-' . strtoupper(Str::random(8));
        } while (static::where('verification_code', $code)->exists());

        return $code;
    }

    /**
     * Generate unique certificate number
     */
    public static function generateCertificateNumber($trainingTypeCode = null)
    {
        $prefix = $trainingTypeCode ? strtoupper($trainingTypeCode) : 'GAP';
        $year = date('Y');
        $month = date('m');

        // Get next sequence number for this training type and month
        $lastCertificate = static::whereYear('issue_date', $year)
            ->whereMonth('issue_date', $month)
            ->where('certificate_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('certificate_number', 'desc')
            ->first();

        $sequence = 1;
        if ($lastCertificate) {
            $parts = explode('-', $lastCertificate->certificate_number);
            if (count($parts) >= 3) {
                $sequence = intval(end($parts)) + 1;
            }
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Get the training record this certificate belongs to
     */
    public function trainingRecord()
    {
        return $this->belongsTo(TrainingRecord::class);
    }

    /**
     * Get the employee through training record
     */
    public function employee()
    {
        return $this->hasOneThrough(Employee::class, TrainingRecord::class, 'id', 'id', 'training_record_id', 'employee_id');
    }

    /**
     * Get the training type through training record
     */
    public function trainingType()
    {
        return $this->hasOneThrough(TrainingType::class, TrainingRecord::class, 'id', 'id', 'training_record_id', 'training_type_id');
    }

    /**
     * Get the user who verified this certificate
     */
    public function verifiedBy()
    {
        return $this->belongsTo(Employee::class, 'verified_by_id');
    }

    /**
     * Get certificate status based on expiry date
     */
    public function getStatusAttribute()
    {
        if (is_null($this->expiry_date)) {
            return 'permanent';
        }

        $now = now()->startOfDay();
        $expiry = Carbon::parse($this->expiry_date)->startOfDay();
        $daysUntilExpiry = $now->diffInDays($expiry, false);

        if ($daysUntilExpiry < 0) {
            return 'expired';
        } elseif ($daysUntilExpiry <= 30) {
            return 'expiring_soon';
        } elseif ($daysUntilExpiry <= 90) {
            return 'expiring';
        }

        return 'active';
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (is_null($this->expiry_date)) {
            return null;
        }

        return now()->startOfDay()->diffInDays(Carbon::parse($this->expiry_date)->startOfDay(), false);
    }

    /**
     * Get verification URL
     */
    public function getVerificationUrlAttribute()
    {
        if (empty($this->verification_code)) {
            return null;
        }

        return route('certificates.verify', $this->verification_code);
    }

    /**
     * Scope for active certificates
     */
    public function scopeActive(Builder $query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>=', now());
        });
    }

    /**
     * Scope for expired certificates
     */
    public function scopeExpired(Builder $query)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', now());
    }

    /**
     * Scope for certificates expiring soon
     */
    public function scopeExpiringSoon(Builder $query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope for certificates expiring in specific days
     */
    public function scopeExpiringIn(Builder $query, $days)
    {
        $targetDate = now()->addDays($days);

        return $query->whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [
                        $targetDate->startOfDay(),
                        $targetDate->endOfDay()
                    ]);
    }

    /**
     * Scope for verified certificates
     */
    public function scopeVerified(Builder $query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for unverified certificates
     */
    public function scopeUnverified(Builder $query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Mark certificate as verified
     */
    public function markAsVerified($verifiedBy = null)
    {
        $this->update([
            'is_verified' => true,
            'verification_date' => now(),
            'verified_by_id' => $verifiedBy
        ]);

        return $this;
    }

    /**
     * Check if certificate is valid
     */
    public function isValid()
    {
        if (is_null($this->expiry_date)) {
            return true; // Permanent certificate
        }

        return Carbon::parse($this->expiry_date)->isFuture();
    }

    /**
     * Check if certificate is expiring soon
     */
    public function isExpiringSoon($days = 30)
    {
        if (is_null($this->expiry_date)) {
            return false;
        }

        $expiryDate = Carbon::parse($this->expiry_date);
        return $expiryDate->isFuture() && $expiryDate->diffInDays(now()) <= $days;
    }

    /**
     * Get renewal recommendation date
     */
    public function getRenewalRecommendationDate()
    {
        if (is_null($this->expiry_date)) {
            return null;
        }

        $trainingType = $this->trainingType;
        $reminderMonths = $trainingType?->renewal_reminder_months ?? 3;

        return Carbon::parse($this->expiry_date)->subMonths($reminderMonths);
    }

    /**
     * Generate QR code for certificate verification
     */
    public function generateQrCode()
    {
        // This would integrate with a QR code library
        $qrData = [
            'certificate_id' => $this->id,
            'verification_code' => $this->verification_code,
            'verification_url' => $this->verification_url,
            'issued_date' => $this->issue_date->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d')
        ];

        // Generate QR code and save to storage
        // Implementation would depend on chosen QR library (e.g., SimpleSoftwareIO/simple-qrcode)

        return $qrData;
    }

    /**
     * Get certificate download URL
     */
    public function getDownloadUrl()
    {
        if (empty($this->certificate_file_path)) {
            return null;
        }

        return route('certificates.download', $this->id);
    }

    /**
     * Search certificates
     */
    public function scopeSearch(Builder $query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('certificate_number', 'like', "%{$term}%")
              ->orWhere('verification_code', 'like', "%{$term}%")
              ->orWhere('issued_by', 'like', "%{$term}%")
              ->orWhereHas('trainingRecord.employee', function ($empQuery) use ($term) {
                  $empQuery->where('name', 'like', "%{$term}%")
                           ->orWhere('employee_id', 'like', "%{$term}%");
              })
              ->orWhereHas('trainingRecord.trainingType', function ($typeQuery) use ($term) {
                  $typeQuery->where('name', 'like', "%{$term}%")
                           ->orWhere('code', 'like', "%{$term}%");
              });
        });
    }

    /**
     * Get certificate analytics for dashboard
     */
    public static function getCertificateAnalytics()
    {
        $total = static::count();
        $active = static::active()->count();
        $expired = static::expired()->count();
        $expiringSoon = static::expiringSoon(30)->count();
        $verified = static::verified()->count();

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'verified' => $verified,
            'verification_rate' => $total > 0 ? round(($verified / $total) * 100, 2) : 0,
            'compliance_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0
        ];
    }

    /**
     * Get expiry trend data
     */
    public static function getExpiryTrend($months = 12)
    {
        $endDate = now();
        $startDate = $endDate->copy()->subMonths($months);

        return static::selectRaw('
                DATE_FORMAT(expiry_date, "%Y-%m") as month,
                COUNT(*) as expiring_count
            ')
            ->whereBetween('expiry_date', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }
}
