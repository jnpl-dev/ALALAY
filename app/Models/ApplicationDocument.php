<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'application_id',
        'required_doc_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'is_resubmission',
        'resubmission_number',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_resubmission' => 'boolean',
            'resubmission_number' => 'integer',
        ];
    }

    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function requiredDocument()
    {
        return $this->belongsTo(RequiredDocument::class, 'required_doc_id');
    }

    public function scopeResubmissions($query)
    {
        return $query->where('is_resubmission', true);
    }

    public function scopeOriginals($query)
    {
        return $query->where('is_resubmission', false);
    }
}
