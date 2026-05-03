<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimamGeneratedFile extends Model
{
    protected $fillable = [
        'export_id',
        'layout_key',
        'file_name',
        'status',
        'records_count',
        'file_path',
        'error_message',
    ];

    protected $casts = [
        'records_count' => 'integer',
    ];

    public function export(): BelongsTo
    {
        return $this->belongsTo(SimamExport::class, 'export_id');
    }
}
