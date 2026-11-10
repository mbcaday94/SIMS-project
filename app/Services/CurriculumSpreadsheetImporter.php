<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetReadException;

class CurriculumSpreadsheetImporter
{
    private const REQUIRED_HEADERS = [
        'subject_code',
        'subject_name',
        'units',
        'year_level',
        'semester',
    ];

    private const HEADER_ALIASES = [
        'subject_code' => ['subject_code', 'course_code'],
        'subject_name' => ['subject_name', 'descriptive_title', 'course_title'],
        'units' => ['units', 'total_units'],
        'year_level' => ['year_level', 'year'],
        'semester' => ['semester'],
        'lecture_units' => ['lecture_units'],
        'lab_units' => ['lab_units'],
        'contact_hours' => ['contact_hours', 'contact_hours_week'],
        'lecture_hours' => ['lecture_hours', 'lecture_hours_week'],
        'lab_hours' => ['lab_hours', 'lab_hours_week'],
        'prerequisites' => ['prerequisites', 'prerequisite', 'prerequisite_s'],
        'co_requisites' => ['co_requisites', 'co_requisite', 'corequisites', 'corequisite', 'co_requisite_s'],
        'course_group' => ['course_group', 'elective_type'],
    ];

    public function parse(UploadedFile $file): array
    {
        $readerType = strtolower($file->getClientOriginalExtension()) === 'xlsx' ? 'Xlsx' : 'Csv';
        $reader = IOFactory::createReader($readerType);
        $reader->setReadDataOnly(true);

        try {
            $sheetRows = $reader->load($file->getRealPath())->getSheet(0)->toArray(null, true, true, false);
        } catch (SpreadsheetReadException $exception) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file could not be read. Please use a valid CSV or XLSX file.',
            ]);
        }

        if (count($sheetRows) > 2500) {
            throw ValidationException::withMessages([
                'file' => 'The workbook must contain no more than 2,500 rows.',
            ]);
        }

        $program = null;
        $name = null;
        foreach (array_slice($sheetRows, 0, 16) as $row) {
            $firstCell = trim((string) ($row[0] ?? ''));
            if ($program === null && preg_match('/^Bachelor of Science in /i', $firstCell)) {
                $program = $firstCell;
            }

            foreach ($row as $index => $cell) {
                if ($this->normalizeHeader($cell) === 'revision') {
                    $name = trim((string) ($row[$index + 1] ?? '')) ?: null;
                }
            }
        }

        $mainTable = $this->findTable($sheetRows, true);
        if ($mainTable === null) {
            throw ValidationException::withMessages([
                'file' => 'No curriculum table was found. Include subject_code, subject_name, units, year_level, and semester columns, or a program curriculum course table.',
            ]);
        }

        [$mainHeaderIndex, $mainHeaders] = $mainTable;
        $rows = $this->readTableRows($sheetRows, $mainHeaderIndex, $mainHeaders, false);

        $electiveTable = $this->findTable($sheetRows, false, $mainHeaderIndex);
        if ($electiveTable !== null) {
            [$electiveHeaderIndex, $electiveHeaders] = $electiveTable;
            array_push($rows, ...$this->readTableRows($sheetRows, $electiveHeaderIndex, $electiveHeaders, true));
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'The file does not contain any curriculum subjects.',
            ]);
        }

        if (count($rows) > 2000) {
            throw ValidationException::withMessages([
                'file' => 'A curriculum import can contain no more than 2,000 rows.',
            ]);
        }

        return [
            'program' => $program,
            'name' => $name,
            'rows' => $rows,
        ];
    }

    private function findTable(array $sheetRows, bool $requireTerm, int $afterRow = -1): ?array
    {
        foreach ($sheetRows as $rowIndex => $row) {
            if ($rowIndex <= $afterRow) {
                continue;
            }

            $headers = [];
            foreach ($row as $columnIndex => $cell) {
                $normalized = $this->normalizeHeader($cell);
                foreach (self::HEADER_ALIASES as $key => $aliases) {
                    if (in_array($normalized, $aliases, true)) {
                        $headers[$key] = $columnIndex;
                        break;
                    }
                }
            }

            $required = ['subject_code', 'subject_name', 'units'];
            if ($requireTerm) {
                $required = array_merge($required, ['year_level', 'semester']);
            } elseif (isset($headers['year_level']) || isset($headers['semester'])) {
                continue;
            } elseif (! isset($headers['course_group'])) {
                continue;
            }

            if (count(array_intersect($required, array_keys($headers))) === count($required)) {
                return [$rowIndex, $headers];
            }
        }

        return null;
    }

    private function readTableRows(array $sheetRows, int $headerIndex, array $headers, bool $elective): array
    {
        $rows = [];

        for ($rowIndex = $headerIndex + 1; $rowIndex < count($sheetRows); $rowIndex++) {
            $cells = $sheetRows[$rowIndex];
            $cell = fn (string $field) => isset($headers[$field]) ? ($cells[$headers[$field]] ?? null) : null;
            $code = trim((string) ($cell('subject_code') ?? ''));
            $name = trim((string) ($cell('subject_name') ?? ''));
            $units = $this->cellString($cell('units'));

            if ($code === '' && $name === '' && $units === '') {
                if ($rows !== []) {
                    break;
                }

                continue;
            }

            $courseGroup = $this->cellString($cell('course_group'));
            $rows[] = [
                'row_number' => $rowIndex + 1,
                'subject_code' => $code,
                'subject_name' => $name,
                'units' => $units,
                'year_level' => $elective ? 'Electives' : $this->cellString($cell('year_level')),
                'semester' => $elective ? ($courseGroup ?: 'Other Electives') : $this->cellString($cell('semester')),
                'lecture_units' => $this->cellString($cell('lecture_units')),
                'lab_units' => $this->cellString($cell('lab_units')),
                'contact_hours' => $this->cellString($cell('contact_hours')),
                'lecture_hours' => $this->cellString($cell('lecture_hours')),
                'lab_hours' => $this->cellString($cell('lab_hours')),
                'prerequisites' => $this->cellString($cell('prerequisites')),
                'co_requisites' => $this->cellString($cell('co_requisites')),
                'course_group' => $courseGroup ?: null,
            ];
        }

        return $rows;
    }

    private function cellString(mixed $value): string
    {
        return $value === null ? '' : trim((string) $value);
    }

    private function normalizeHeader(mixed $header): string
    {
        $header = str_replace("\xEF\xBB\xBF", '', trim((string) $header));

        return strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $header), '_'));
    }
}
