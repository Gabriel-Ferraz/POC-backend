<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimamExport extends Model
{
    protected $fillable = [
        'year',
        'month',
        'module',
        'generation_type',
        'only_active',
        'zip_name',
        'zip_path',
        'status',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'only_active' => 'boolean',
    ];

    public function generatedFiles(): HasMany
    {
        return $this->hasMany(SimamGeneratedFile::class, 'export_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
