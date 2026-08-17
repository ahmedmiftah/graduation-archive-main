<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportedStudentRow extends Model
{
    public const STATUS_SUCCESS                    = 'success';
    public const STATUS_DUPLICATE_IN_FILE           = 'duplicate_in_file';
    public const STATUS_ALREADY_EXISTS              = 'already_exists';
    public const STATUS_INVALID_NATIONAL_ID         = 'invalid_national_id';
    public const STATUS_INVALID_REGISTRATION_NUMBER = 'invalid_registration_number';
    public const STATUS_INVALID_DATA                = 'invalid_data';

    protected $fillable = [
        'batch_id',
        'row_number',
        'full_name',
        'national_id',
        'registration_number',
        'status',
        'error_message',
        'student_id',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportedStudentBatch::class, 'batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
