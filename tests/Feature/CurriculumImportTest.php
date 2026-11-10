<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CurriculumImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_import_csv_curriculum_and_create_missing_subjects(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $file = UploadedFile::fake()->createWithContent(
            'bsit-curriculum.csv',
            "subject_code,subject_name,units,year_level,semester\nCS101,Computer Fundamentals,3,First Year,First\nMATH101,College Algebra,3,First Year,First\nCS101,Computer Fundamentals,3,First Year,Second\n"
        );

        $this->actingAs($administrator)
            ->post(route('curricula.import'), [
                'program' => 'BS Information Technology',
                'name' => '2026 Curriculum',
                'file' => $file,
            ])
            ->assertRedirect(route('curricula.index'))
            ->assertSessionHas('success');

        $curriculum = Curriculum::with('subjects')->firstOrFail();
        $this->assertSame(3, $curriculum->subjects->count());
        $this->assertDatabaseCount('subjects', 2);
        $this->assertDatabaseHas('curriculum_subject', [
            'curriculum_id' => $curriculum->id,
            'subject_id' => Subject::where('code', 'CS101')->value('id'),
            'year_level' => 'First Year',
            'semester' => 'Second',
        ]);
        $this->assertSame($administrator->id, $curriculum->created_by);
        $this->get(route('curricula.index'))
            ->assertOk()
            ->assertSee('Upload curriculum')
            ->assertSee('href="'.route('curricula.template').'"', false)
            ->assertSee('href="'.route('curricula.show', $curriculum).'"', false);
        $this->actingAs($administrator)
            ->get(route('curricula.show', $curriculum))
            ->assertOk()
            ->assertSee('BS Information Technology')
            ->assertSee('Computer Fundamentals')
            ->assertSee('First Year')
            ->assertSee('page-landscape', false)
            ->assertSee('year-board', false)
            ->assertSee('@page { size:landscape;', false);

        $this->delete(route('subjects.destroy', Subject::where('code', 'CS101')->firstOrFail()))
            ->assertRedirect(route('subjects.index'))
            ->assertSessionHas('error');
    }

    public function test_import_preserves_curriculum_snapshot_without_changing_existing_subject(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $subject = Subject::factory()->create([
            'code' => 'CS101',
            'name' => 'Computer Fundamentals',
            'units' => 3,
        ]);
        $file = UploadedFile::fake()->createWithContent(
            'curriculum.csv',
            "subject_code,subject_name,units,year_level,semester\nCS101,Computer Fundamentals,3,First Year,First\n"
        );

        $this->actingAs($administrator)
            ->post(route('curricula.import'), [
                'program' => 'BSIT',
                'name' => '2026',
                'file' => $file,
            ])
            ->assertRedirect(route('curricula.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('subjects', 1);
        $this->assertDatabaseHas('curriculum_subject', [
            'curriculum_id' => Curriculum::firstOrFail()->id,
            'subject_id' => $subject->id,
        ]);

        $conflictFile = UploadedFile::fake()->createWithContent(
            'conflict.csv',
            "subject_code,subject_name,units,year_level,semester\nCS101,Changed Name,3,Second Year,First\n"
        );

        $this->from(route('curricula.index'))
            ->post(route('curricula.import'), [
                'program' => 'BSCS',
                'name' => '2026',
                'file' => $conflictFile,
            ])
            ->assertRedirect(route('curricula.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('curricula', 2);
        $this->assertSame('Computer Fundamentals', $subject->fresh()->name);
        $this->assertDatabaseHas('curriculum_subject', [
            'curriculum_id' => Curriculum::where('program', 'BSCS')->value('id'),
            'subject_id' => $subject->id,
            'subject_name' => 'Changed Name',
            'units' => 3,
        ]);
    }

    public function test_invalid_csv_rows_do_not_create_partial_records(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $file = UploadedFile::fake()->createWithContent(
            'invalid.csv',
            "subject_code,subject_name,units,year_level,semester\nCS101,Computer Fundamentals,-1,First Year,First\n"
        );

        $this->from(route('curricula.index'))
            ->actingAs($administrator)
            ->post(route('curricula.import'), [
                'program' => 'BSIT',
                'name' => '2026',
                'file' => $file,
            ])
            ->assertRedirect(route('curricula.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('curricula', 0);
        $this->assertDatabaseCount('subjects', 0);
    }

    public function test_administrator_can_import_xlsx_curriculum(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'sims-curriculum-');
        $path = $temporaryPath.'.xlsx';
        unlink($temporaryPath);
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['subject_code', 'subject_name', 'units', 'year_level', 'semester'],
            ['ENG101', 'Academic Writing', 3, 'First Year', 'First'],
        ]);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        try {
            $file = new UploadedFile(
                $path,
                'curriculum.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            );

            $this->actingAs($administrator)
                ->post(route('curricula.import'), [
                    'program' => 'BS English',
                    'name' => '2026 Curriculum',
                    'file' => $file,
                ])
                ->assertRedirect(route('curricula.index'))
                ->assertSessionHas('success');

            $this->assertDatabaseHas('subjects', ['code' => 'ENG101', 'name' => 'Academic Writing']);
            $this->assertDatabaseCount('curriculum_subject', 1);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_program_workbook_imports_metadata_zero_unit_courses_and_electives(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'sims-curriculum-');
        $path = $temporaryPath.'.xlsx';
        unlink($temporaryPath);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Bachelor of Science in Information Technology (BSIT)');
        $sheet->setCellValue('A6', 'Revision');
        $sheet->setCellValue('B6', 'Rev. 3, 2025');
        $existingSubject = Subject::factory()->create([
            'code' => 'IT 110',
            'name' => 'Web Systems & Technologies',
            'units' => 5,
        ]);
        $sheet->fromArray([
            ['Year', 'Semester', 'Course Code', 'Descriptive Title', 'Total Units', 'Lecture Units', 'Lab Units', 'Contact Hours / Week', 'Lecture Hours / Week', 'Lab Hours / Week', 'Prerequisite(s)', 'Co-requisite(s)', 'Course Group'],
            ['First Year', 'First Semester', 'IT 110', 'Web Systems and Technologies 1', 3, 2, 1, 5, 2, 3, 'ITE 16', 'None', 'IT Core'],
            ['First Year', 'First Semester', 'SE 1', 'The University Life', 0, 0, 0, 1.5, 1.5, 0, 'None', 'None', 'Non-credit'],
        ], null, 'A17', true);
        $sheet->fromArray([
            ['Approved Elective Options'],
            ['Elective Type', 'Course Code', 'Descriptive Title', 'Total Units', 'Lecture Units', 'Lab Units', 'Contact Hours / Week', 'Lecture Hours / Week', 'Lab Hours / Week', 'Prerequisite(s)', 'Co-requisite(s)'],
            ['Professional', 'IT 111', 'Platform Technologies', 3, 2, 1, 5, 2, 3, 'CSC 106', 'None'],
        ], null, 'A22', true);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        try {
            $this->actingAs($administrator)
                ->post(route('curricula.import'), [
                    'file' => new UploadedFile(
                        $path,
                        'BSIT_Program_Curriculum.xlsx',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        null,
                        true
                    ),
                ])
                ->assertRedirect(route('curricula.index'))
                ->assertSessionHas('success');

            $curriculum = Curriculum::with('subjects')->firstOrFail();
            $this->assertSame('Bachelor of Science in Information Technology (BSIT)', $curriculum->program);
            $this->assertSame('Rev. 3, 2025', $curriculum->name);
            $this->assertSame(3, $curriculum->subjects->count());
            $this->assertSame('Web Systems & Technologies', $existingSubject->fresh()->name);
            $this->assertSame(5, $existingSubject->fresh()->units);
            $this->assertDatabaseHas('curriculum_subject', [
                'curriculum_id' => $curriculum->id,
                'subject_id' => $existingSubject->id,
                'curriculum_code' => 'IT 110',
                'subject_name' => 'Web Systems and Technologies 1',
                'units' => 3,
            ]);
            $this->assertDatabaseHas('subjects', ['code' => 'SE 1', 'units' => 0]);
            $this->assertDatabaseHas('curriculum_subject', [
                'curriculum_id' => $curriculum->id,
                'year_level' => 'First Year',
                'semester' => 'First Semester',
                'prerequisites' => 'ITE 16',
                'lecture_units' => 2,
                'lab_units' => 1,
            ]);
            $this->assertDatabaseHas('curriculum_subject', [
                'curriculum_id' => $curriculum->id,
                'year_level' => 'Electives',
                'semester' => 'Professional',
                'course_group' => 'Professional',
                'prerequisites' => 'CSC 106',
            ]);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_staff_cannot_view_or_import_curricula(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('curricula.index'))
            ->assertForbidden();

        $this->post(route('curricula.import'))->assertForbidden();
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('curricula.index').'"', false);
    }

    public function test_curriculum_template_is_downloadable(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($administrator)
            ->get(route('curricula.template'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertStreamedContent("subject_code,subject_name,units,year_level,semester\n");
    }
}
