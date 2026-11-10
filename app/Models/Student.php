<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    public const COURSES = [
        'Bachelor of Science in Information Technology',
        'Bachelor of Science in Information System',
        'Bachelor of Science in Computer Science',
        'Bachelor of Science in Computer Engineering',
    ];

    public const SEMESTERS = ['First', 'Second', 'Summer'];

    protected $fillable = [
        'user_id',
        'student_number',
        'first_name',
        'last_name',
        'email',
        'course',
        'year_level',
        'semester',
        'section',
        'date_of_birth',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
