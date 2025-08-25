<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employee_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Get the employee record associated with the user.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function hasRole($roles): bool
{
    // Jika user belum punya kolom role, return true untuk backward compatibility
    if (!isset($this->role)) {
        return true;
    }

    // Jika roles adalah array
    if (is_array($roles)) {
        return in_array($this->role, $roles);
    }

    // Jika roles adalah string tunggal
    return $this->role === $roles;
}

    /**
     * Check if user is admin or super admin
     */
    public function isAdmin()
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
 * Check if user can edit training records
 */
public function canEditTrainingRecords(): bool
{
    return $this->hasRole(['admin', 'hr_staff', 'super_admin']);
}

/**
 * Check if user can view training records
 */
public function canViewTrainingRecords(): bool
{
    // Semua user yang sudah login bisa view
    return true;
}

/**
 * Check if user can manage training types
 */
public function canManageTrainingTypes(): bool
{
    return $this->hasRole(['admin', 'super_admin']);
}

/**
 * Get user role display name
 */
public function getRoleDisplayNameAttribute(): string
{
    $roles = [
        'admin' => 'Administrator',
        'hr_staff' => 'HR Staff',
        'super_admin' => 'Super Administrator',
        'user' => 'User',
        'employee' => 'Employee'
    ];

    return $roles[$this->role ?? 'user'] ?? 'User';
}

}
