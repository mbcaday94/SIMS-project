<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index', [
            'totalStudents' => Student::count(),
            'totalSubjects' => Subject::count(),
            'totalEnrollments' => Enrollment::count(),
            'totalUnits' => Subject::sum('units'),
            'studentsByCourse' => $this->chartRows(
                Student::query()
                    ->select('course')
                    ->selectRaw('COUNT(*) AS total')
                    ->groupBy('course')
                    ->orderByDesc('total')
                    ->get(),
                fn ($row) => $row->course ?: 'Not specified',
            ),
            'studentsByYearLevel' => $this->chartRows(
                Student::query()
                    ->select('year_level')
                    ->selectRaw('COUNT(*) AS total')
                    ->groupBy('year_level')
                    ->orderByDesc('total')
                    ->get(),
                fn ($row) => $row->year_level ?: 'Not specified',
            ),
            'enrollmentsByTerm' => $this->chartRows(
                Enrollment::query()
                    ->select('school_year', 'semester')
                    ->selectRaw('COUNT(*) AS total')
                    ->groupBy('school_year', 'semester')
                    ->orderByDesc('total')
                    ->limit(8)
                    ->get(),
                fn ($row) => "{$row->school_year} · {$row->semester}",
            ),
            'enrollmentsByStatus' => $this->chartRows(
                Enrollment::query()
                    ->select('status')
                    ->selectRaw('COUNT(*) AS total')
                    ->groupBy('status')
                    ->orderByDesc('total')
                    ->get(),
                fn ($row) => ucfirst($row->status),
            ),
            'generatedAt' => now(),
        ]);
    }

    private function chartRows(Collection $rows, callable $label): Collection
    {
        $max = max(1, (int) $rows->max('total'));

        return $rows->map(fn ($row) => [
            'label' => $label($row),
            'total' => (int) $row->total,
            'width' => max(4, (int) round(((int) $row->total / $max) * 100)),
        ]);
    }
}
