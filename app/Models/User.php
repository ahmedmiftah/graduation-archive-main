<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'registration_number',
        'department_id',
        'is_active',
        'force_password_change',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'is_active'             => 'boolean',
            'force_password_change' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function facultyMember(): HasOne
    {
        return $this->hasOne(FacultyMember::class);
    }

    public function feedbackReplies(): HasMany
    {
        return $this->hasMany(ProjectFeedbackReply::class);
    }

    public function feedbackAuditLogs(): HasMany
    {
        return $this->hasMany(ProjectFeedbackAuditLog::class);
    }
}
