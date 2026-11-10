<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\Subject;
use App\Services\CurriculumSpreadsheetImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CurriculumController extends Controller
{
    public function index(): View
    {
        $curricula = Curriculum::query()
            ->withCount('subjects')
            ->orderBy('program')
            ->orderBy('name')
            ->paginate(15);

        return view('curricula.index', compact('curricula'));
    }

    public function show(Curriculum $curriculum): View
    {
        $subjects = $curriculum->subjects()
            ->orderByPivot('year_level')
            ->orderByPivot('semester')
            ->orderBy('code')
            ->get()
            ->groupBy(fn (Subject $subject) => $subject->pivot->year_level)
            ->sortBy(fn ($yearSubjects, string $yearLevel) => match ($yearLevel) {
                'First Year' => 1,
                'Second Year' => 2,
                'Third Year' => 3,
                'Fourth Year' => 4,
                'Electives' => 5,
                default => 6,
            })
            ->map(fn ($yearSubjects) => $yearSubjects
                ->groupBy(fn (Subject $subject) => $subject->pivot->semester)
                ->sortBy(fn ($termSubjects, string $semester) => match ($semester) {
                    'First Semester' => 1,
                    'Second Semester' => 2,
                    default => 3,
                }));

        return view('curricula.show', compact('curriculum', 'subjects'));
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                throw new \RuntimeException('Unable to generate the curriculum import template.');
            }

            fputcsv($stream, ['subject_code', 'subject_name', 'units', 'year_level', 'semester']);
            fclose($stream);
        }, 'curriculum-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request, CurriculumSpreadsheetImporter $importer): RedirectResponse
    {
        $validatedFile = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'extensions:csv,xlsx', 'max:10240'],
        ]);
        $parsed = $importer->parse($validatedFile['file']);

        $request->merge([
            'program' => trim((string) ($request->input('program') ?: $parsed['program'])),
            'name' => trim((string) ($request->input('name') ?: $parsed['name'])),
        ]);

        $validated = $request->validate([
            'program' => ['required', 'string', 'max:255'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('curricula', 'name')->where(
                    fn ($query) => $query->where('program', $request->input('program'))
                ),
            ],
        ]);

        $rows = $parsed['rows'];
        $errors = [];
        $subjectsByCode = [];
        $curriculumTerms = [];

        foreach ($rows as $row) {
            $units = is_numeric($row['units']) ? (float) $row['units'] : null;
            $rowErrors = [];

            if ($row['subject_code'] === '' || mb_strlen($row['subject_code']) > 255) {
                $rowErrors[] = 'subject_code is required and must be at most 255 characters';
            }
            if ($row['subject_name'] === '' || mb_strlen($row['subject_name']) > 255) {
                $rowErrors[] = 'subject_name is required and must be at most 255 characters';
            }
            if ($units === null || floor($units) !== $units || $units < 0 || $units > 255) {
                $rowErrors[] = 'units must be a whole number from 0 to 255';
            }
            if ($row['year_level'] === '' || mb_strlen($row['year_level']) > 50) {
                $rowErrors[] = 'year_level is required and must be at most 50 characters';
            }
            if ($row['semester'] === '' || mb_strlen($row['semester']) > 50) {
                $rowErrors[] = 'semester is required and must be at most 50 characters';
            }
            foreach (['lecture_units', 'lab_units', 'contact_hours', 'lecture_hours', 'lab_hours'] as $field) {
                if ($row[$field] !== '' && (! is_numeric($row[$field]) || (float) $row[$field] < 0 || (float) $row[$field] > 999.99)) {
                    $rowErrors[] = "{$field} must be a number from 0 to 999.99";
                }
            }
            foreach (['prerequisites', 'co_requisites', 'course_group'] as $field) {
                if ($row[$field] !== '' && mb_strlen($row[$field]) > 255) {
                    $rowErrors[] = "{$field} must be at most 255 characters";
                }
            }

            if ($rowErrors !== []) {
                $errors[] = 'Row '.$row['row_number'].': '.implode('; ', $rowErrors).'.';

                continue;
            }

            $row['units'] = (int) $units;
            $subjectCodeKey = strtoupper($row['subject_code']);
            $existingCode = $subjectsByCode[$subjectCodeKey] ?? null;
            if ($existingCode && (
                strcasecmp($existingCode['name'], $row['subject_name']) !== 0
                || $existingCode['units'] !== $row['units']
            )) {
                $errors[] = 'Row '.$row['row_number'].': subject '.$row['subject_code']
                    .' has conflicting name or units elsewhere in the file.';

                continue;
            }

            $subjectsByCode[$subjectCodeKey] = [
                'code' => $row['subject_code'],
                'name' => $row['subject_name'],
                'units' => $row['units'],
            ];

            $termKey = implode('|', [
                $subjectCodeKey,
                strtolower($row['year_level']),
                strtolower($row['semester']),
            ]);
            if (isset($curriculumTerms[$termKey])) {
                $errors[] = 'Row '.$row['row_number'].': subject '.$row['subject_code']
                    .' is listed more than once for the same year and semester.';

                continue;
            }
            $curriculumTerms[$termKey] = true;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => implode("\n", array_slice($errors, 0, 20))]);
        }

        $existingSubjects = Subject::query()
            ->whereIn(DB::raw('UPPER(code)'), array_keys($subjectsByCode))
            ->get()
            ->keyBy(fn (Subject $subject) => strtoupper($subject->code));

        $sourceFile = $validatedFile['file'];

        DB::transaction(function () use ($validated, $rows, $existingSubjects, $subjectsByCode, $request, $sourceFile) {
            $curriculum = Curriculum::create([
                'program' => trim($validated['program']),
                'name' => trim($validated['name']),
                'source_filename' => basename(str_replace('\\', '/', $sourceFile->getClientOriginalName())),
                'created_by' => $request->user()->id,
            ]);

            foreach ($subjectsByCode as $codeKey => $details) {
                if (! $existingSubjects->has($codeKey)) {
                    $existingSubjects->put($codeKey, Subject::create([
                        'code' => $details['code'],
                        'name' => $details['name'],
                        'units' => $details['units'],
                    ]));
                }
            }

            foreach ($rows as $row) {
                $codeKey = strtoupper($row['subject_code']);
                $curriculum->subjects()->attach($existingSubjects->get($codeKey)->id, [
                    'year_level' => $row['year_level'],
                    'semester' => $row['semester'],
                    'curriculum_code' => $row['subject_code'],
                    'subject_name' => $row['subject_name'],
                    'units' => (int) $row['units'],
                    'lecture_units' => $row['lecture_units'] === '' ? null : $row['lecture_units'],
                    'lab_units' => $row['lab_units'] === '' ? null : $row['lab_units'],
                    'contact_hours' => $row['contact_hours'] === '' ? null : $row['contact_hours'],
                    'lecture_hours' => $row['lecture_hours'] === '' ? null : $row['lecture_hours'],
                    'lab_hours' => $row['lab_hours'] === '' ? null : $row['lab_hours'],
                    'prerequisites' => $row['prerequisites'] === '' ? null : $row['prerequisites'],
                    'co_requisites' => $row['co_requisites'] === '' ? null : $row['co_requisites'],
                    'course_group' => $row['course_group'],
                ]);
            }
        });

        return redirect()->route('curricula.index')
            ->with('success', "Curriculum {$validated['name']} for {$validated['program']} imported successfully.");
    }
}
