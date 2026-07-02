<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'name_extension',
        'email',
        'password',
        'role',
        'status',
        'is_online',
        'profile_picture_name',
        'profile_picture_path',
        'profile_picture_size',
        'profile_picture_mime_type',
        'acceptable_use_policy_accepted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_online' => 'boolean',
            'profile_picture_size' => 'integer',
            'acceptable_use_policy_accepted_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
    }

    public function encodedApplications()
    {
        return $this->hasMany(Application::class, 'encoded_by');
    }

    public function reviewedApplications()
    {
        return $this->hasMany(Application::class, 'reviewed_by');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewed_by');
    }

    public function socialCaseStudies()
    {
        return $this->hasMany(SocialCaseStudy::class, 'conducted_by');
    }

    public function assistanceCodes()
    {
        return $this->hasMany(AssistanceCode::class, 'assigned_by');
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'prepared_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function updatedSettings()
    {
        return $this->hasMany(SystemSetting::class, 'updated_by');
    }

    public function emailOtps()
    {
        return $this->hasMany(EmailOtp::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }
}
