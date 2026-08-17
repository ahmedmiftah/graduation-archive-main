<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportedStudentBatch extends Model
{
    protected $fillable = [
        'imported_by',
        'total_rows',
        'success_count',
        'failed_count',
    ];

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportedStudentRow::class, 'batch_id');
    }
}
