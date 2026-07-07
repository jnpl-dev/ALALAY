<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'application_id',
        'assistance_code_id',
        'prepared_by',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'version',
        'page_count',
        'prepared_at',
        'adjustment_remarks',
        'returned_at',
        'returned_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'version' => 'integer',
            'page_count' => 'integer',
            'prepared_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function assistanceCode()
    {
        return $this->belongsTo(AssistanceCode::class, 'assistance_code_id');
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function getFileSizeLabelAttribute(): string
    {
        $kb = $this->file_size / 1024;
        if ($kb < 1024) return round($kb, 1) . ' KB';
        return round($kb / 1024, 2) . ' MB';
    }

    public function isReturned(): bool
    {
        return !is_null($this->returned_at);
    }
}
