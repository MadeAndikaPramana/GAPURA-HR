<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class TrainingType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'validity_months',
        'category',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'validity_months' => 'integer'
    ];

    /**
     * Get all training records for this training type
     */
    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }

    /**
     * Get active training records for this training type
     */
    public function activeTrainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'active');
    }

    /**
     * Get expiring training records for this training type
     */
    public function expiringTrainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'expiring_soon');
    }

    /**
     * Get expired training records for this training type
     */
    public function expiredTrainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class)->where('status', 'expired');
    }

    /**
     * Get compliance statistics for this training type
     */
    public function getComplianceStatsAttribute(): array
    {
        $total = $this->trainingRecords()->count();
        $active = $this->activeTrainingRecords()->count();
        $expiring = $this->expiringTrainingRecords()->count();
        $expired = $this->expiredTrainingRecords()->count();

        return [
            'total_certificates' => $total,
            'active_certificates' => $active,
            'expiring_certificates' => $expiring,
            'expired_certificates' => $expired,
            'compliance_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get employees who have this training type
     */
    public function employees()
    {
        return $this->hasManyThrough(
            Employee::class,
            TrainingRecord::class,
            'training_type_id', // Foreign key on training_records table
            'id', // Foreign key on employees table
            'id', // Local key on training_types table
            'employee_id' // Local key on training_records table
        )->distinct();
    }

    /**
     * Get departments that have employees with this training
     */
    public function departments()
    {
        return $this->hasManyThrough(
            Department::class,
            TrainingRecord::class,
            'training_type_id', // Foreign key on training_records table
            'id', // Foreign key on departments table
            'id', // Local key on training_types table
            'department_id' // Local key on training_records table
        )->distinct();
    }

    /**
     * Get statistics by department for this training type
     */
    public function getDepartmentStatsAttribute(): array
    {
        return $this->departments()
            ->selectRaw('
                departments.id as department_id,
                departments.name as department_name,
                COUNT(training_records.id) as total_certificates,
                COUNT(CASE WHEN training_records.status = "active" THEN 1 END) as active_count,
                COUNT(CASE WHEN training_records.status = "expiring_soon" THEN 1 END) as expiring_count,
                COUNT(CASE WHEN training_records.status = "expired" THEN 1 END) as expired_count
            ')
            ->groupBy('departments.id', 'departments.name')
            ->get()
            ->toArray();
    }

    /**
     * Scope for active training types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('category', 'like', "%{$term}%");
        });
    }

    /**
     * Get category badge color for UI
     */
    public function getCategoryColorAttribute(): string
    {
        $colors = [
            'safety' => 'red',
            'operational' => 'blue',
            'security' => 'purple',
            'technical' => 'green'
        ];

        return $colors[$this->category] ?? 'gray';
    }

    /**
     * Get formatted validity period
     */
    public function getFormattedValidityAttribute(): string
    {
        if ($this->validity_months < 12) {
            return "{$this->validity_months} month(s)";
        }

        $years = floor($this->validity_months / 12);
        $months = $this->validity_months % 12;

        if ($months === 0) {
            return "{$years} year(s)";
        }

        return "{$years} year(s) {$months} month(s)";
    }

    /**
     * Check if training type has any certificates
     */
    public function hasCertificates(): bool
    {
        return $this->trainingRecords()->exists();
    }

    /**
     * Get upcoming expiry notifications count
     */
    public function getUpcomingExpiriesCount($days = 30): int
    {
        return $this->trainingRecords()
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now())
            ->where('status', '!=', 'expired')
            ->count();
    }

    /**
     * Generate unique code for training type
     */
    public static function generateUniqueCode($name): string
    {
        // Generate base code from name
        $baseCode = strtoupper(substr(str_replace(' ', '', $name), 0, 8));

        // Check if code exists
        $counter = 1;
        $code = $baseCode;

        while (self::where('code', $code)->exists()) {
            $code = $baseCode . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate code if not provided
        static::creating(function ($trainingType) {
            if (empty($trainingType->code)) {
                $trainingType->code = self::generateUniqueCode($trainingType->name);
            }
        });
    }

    /**
     * Get all available categories
     */
    public static function getCategories(): array
    {
        return ['safety', 'operational', 'security', 'technical'];
    }

    /**
     * Get category descriptions
     */
    public static function getCategoryDescriptions(): array
    {
        return [
            'safety' => 'Safety-related trainings including fire safety, first aid, occupational health and safety.',
            'operational' => 'Operational trainings for day-to-day work processes, ground handling, customer service.',
            'security' => 'Security and access control trainings, airport security awareness, background checks.',
            'technical' => 'Technical skills training for equipment operation, maintenance, specialized procedures.'
        ];
    }

    /**
     * Get training types with their statistics
     */
    public static function withStatistics()
    {
        return self::query()
            ->leftJoin('training_records', 'training_types.id', '=', 'training_records.training_type_id')
            ->selectRaw('
                training_types.*,
                COUNT(training_records.id) as total_certificates,
                COUNT(CASE WHEN training_records.status = "active" THEN 1 END) as active_certificates,
                COUNT(CASE WHEN training_records.status = "expiring_soon" THEN 1 END) as expiring_certificates,
                COUNT(CASE WHEN training_records.status = "expired" THEN 1 END) as expired_certificates
            ')
            ->groupBy('training_types.id')
            ->orderBy('training_types.name');
    }
}
