<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFeedbackReply extends Model
{
    use HasFactory;

    protected $table = 'project_feedback_replies';

    protected $fillable = [
        'project_feedback_id',
        'user_id',
        'message',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(ProjectFeedback::class, 'project_feedback_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
