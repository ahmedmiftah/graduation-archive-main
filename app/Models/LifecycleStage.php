<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LifecycleStage extends Model
{
    protected $fillable = ['key', 'name_ar', 'sort_order', 'requires_student_action'];

    protected function casts(): array
    {
        return [
            'requires_student_action' => 'boolean',
        ];
    }
}
