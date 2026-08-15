<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'category_id',
        'reference_code',
        'status',
        'submission_type',
        'encoded_by',
        'claimant_last_name',
        'claimant_first_name',
        'claimant_middle_name',
        'claimant_name_extension',
        'claimant_sex',
        'claimant_dob',
        'claimant_address',
        'claimant_phone',
        'claimant_email',
        'claimant_relationship_to_beneficiary',
        'beneficiary_last_name',
        'beneficiary_first_name',
        'beneficiary_middle_name',
        'beneficiary_name_extension',
        'beneficiary_sex',
        'beneficiary_dob',
        'beneficiary_address',
        'beneficiary_barangay',
        'resubmission_remarks',
        'reviewed_by',
        'reviewed_at',
        'claimed_at',
        'claiming_date',
    ];

    protected function casts(): array
    {
        return [
            'claimant_address' => 'encrypted',
            'claimant_phone' => 'encrypted',
            'claimant_email' => 'encrypted',
            'beneficiary_address' => 'encrypted',
            'claimant_dob' => 'date',
            'beneficiary_dob' => 'date',
            'reviewed_at' => 'datetime',
            'claimed_at' => 'datetime',
            'claiming_date' => 'date',
        ];
    }

    public function category()
    {
        return $this->belongsTo(AssistanceCategory::class, 'category_id');
    }

    protected function setClaimantSexAttribute(?string $value): void
    {
        $this->attributes['claimant_sex'] = $value ? strtolower($value) : $value;
    }

    protected function setBeneficiarySexAttribute(?string $value): void
    {
        $this->attributes['beneficiary_sex'] = $value ? strtolower($value) : $value;
    }

    public function encoder()
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class, 'application_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'application_id');
    }

    public function socialCaseStudy()
    {
        return $this->hasOne(SocialCaseStudy::class, 'application_id');
    }

    public function assistanceCode()
    {
        return $this->hasOne(AssistanceCode::class, 'application_id');
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'application_id');
    }

    public function voucher()
    {
        return $this->hasOne(Voucher::class, 'application_id')->latestOfMany();
    }

    public function smsNotifications()
    {
        return $this->hasMany(SmsNotification::class, 'application_id');
    }

    public function getBeneficiaryBarangayAttribute()
    {
        if ($this->attributes['beneficiary_barangay'] ?? null) {
            return $this->attributes['beneficiary_barangay'];
        }
        if (!$this->beneficiary_address) return null;
        $parts = array_map('trim', explode(',', $this->beneficiary_address));
        return count($parts) >= 2 ? ($parts[count($parts) - 3] ?? null) : null;
    }

    public static function parseBarangayFromAddress(?string $address): ?string
    {
        if (!$address) return null;
        $parts = array_map('trim', explode(',', $address));
        return count($parts) >= 2 ? ($parts[count($parts) - 3] ?? null) : null;
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeOnline($query)
    {
        return $query->where('submission_type', 'online');
    }

    public function scopeWalkIn($query)
    {
        return $query->where('submission_type', 'walk_in');
    }
}
