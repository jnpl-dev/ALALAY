<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SocialCaseStudy extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'application_id',
        'conducted_by',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'page_count',
        'conducted_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'page_count' => 'integer',
            'conducted_at' => 'datetime',
        ];
    }

    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function conductedBy()
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function getFileSizeLabelAttribute(): string
    {
        $kb = $this->file_size / 1024;
        if ($kb < 1024) return round($kb, 1) . ' KB';
        return round($kb / 1024, 2) . ' MB';
    }
}
