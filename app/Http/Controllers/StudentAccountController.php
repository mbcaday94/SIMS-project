<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class StudentAccountController extends Controller
{
    public function store(Request $request, Student $student): RedirectResponse
    {
        if ($student->user_id !== null) {
            return back()->with('error', 'This student already has a login account.');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        validator(
            ['email' => $student->email],
            ['email' => ['required', 'email', Rule::unique('users', 'email')]]
        )->validate();

        DB::transaction(function () use ($student, $validated): void {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);

            if ($student->user_id !== null) {
                throw ValidationException::withMessages([
                    'account' => 'This student already has a login account.',
                ]);
            }

            $user = new User([
                'name' => "{$student->first_name} {$student->last_name}",
                'email' => $student->email,
                'password' => $validated['password'],
            ]);
            $user->role = 'student';
            $user->save();

            $student->user()->associate($user);
            $student->save();
        });

        return redirect()->route('students.show', $student)
            ->with('success', 'Student login created. Share the email and password with the student securely.');
    }
}
