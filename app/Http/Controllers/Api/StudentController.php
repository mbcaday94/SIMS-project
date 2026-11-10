<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentSectionCapacityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return StudentResource::collection(
            Student::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(15)
        );
    }

    public function show(Student $student): StudentResource
    {
        return new StudentResource($student);
    }

    public function store(Request $request, StudentSectionCapacityService $sectionCapacity): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $sectionCapacity->ensureCapacity($validated);
        $student = Student::create($validated);

        return (new StudentResource($student))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Student $student, StudentSectionCapacityService $sectionCapacity): StudentResource
    {
        $validated = $request->validate($this->rules($student));
        $sectionCapacity->ensureCapacity($validated, $student);
        $student->update($validated);

        return new StudentResource($student->refresh());
    }

    public function destroy(Student $student): JsonResponse
    {
        if ($student->enrollments()->exists()) {
            return response()->json([
                'message' => 'This student cannot be deleted while they have enrollments.',
            ], 409);
        }

        $student->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?Student $student = null): array
    {
        return [
            'student_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'student_number')->ignore($student?->id),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('students', 'email')->ignore($student?->id),
            ],
            'course' => ['required', 'string', Rule::in(Student::COURSES)],
            'year_level' => ['required', 'string', 'max:50'],
            'semester' => ['nullable', 'string', Rule::in(Student::SEMESTERS)],
            'section' => ['nullable', 'string', Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
        ];
    }
}
