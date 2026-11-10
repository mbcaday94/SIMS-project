<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Curriculum extends Model
{
    protected $fillable = [
        'program',
        'name',
        'source_filename',
        'created_by',
    ];

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class)
            ->withPivot([
                'year_level',
                'semester',
                'curriculum_code',
                'subject_name',
                'units',
                'lecture_units',
                'lab_units',
                'contact_hours',
                'lecture_hours',
                'lab_hours',
                'prerequisites',
                'co_requisites',
                'course_group',
            ])
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
