<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingLoad extends Model
{
    protected $fillable = [
        'faculty_id',
        'subject_id',
        'units',
        'program',
        'year_level',
        'section',
        'school_year',
        'semester',
        'days',
        'lab_days',
        'start_time',
        'end_time',
        'lab_start_time',
        'lab_end_time',
        'lecture_room',
        'lab_room',
        'room',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'array',
            'lab_days' => 'array',
            'units' => 'integer',
        ];
    }

    /**
     * @return array<int, array{type: string, days: array<int, string>, start_time: string, end_time: string}>
     */
    public function meetings(): array
    {
        $meetings = [[
            'type' => 'Lecture',
            'days' => $this->days ?? [],
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'room' => $this->lecture_room ?: $this->room,
        ]];

        if ($this->lab_days && $this->lab_start_time && $this->lab_end_time) {
            $meetings[] = [
                'type' => 'Laboratory',
                'days' => $this->lab_days,
                'start_time' => $this->lab_start_time,
                'end_time' => $this->lab_end_time,
                'room' => $this->lab_room ?: $this->room,
            ];
        }

        return $meetings;
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
