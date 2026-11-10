<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'units',
    ];

    protected function casts(): array
    {
        return [
            'units' => 'integer',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function curricula(): BelongsToMany
    {
        return $this->belongsToMany(Curriculum::class)
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
}
