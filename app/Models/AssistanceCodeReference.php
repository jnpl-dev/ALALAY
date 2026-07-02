<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssistanceCodeReference extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code_type',
        'default_amount',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function assistanceCodes()
    {
        return $this->hasMany(AssistanceCode::class, 'assistance_code_reference_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
