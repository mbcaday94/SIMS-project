<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // ──────────────────────────────────────────────
    //  REGISTER
    // ──────────────────────────────────────────────

    // ──────────────────────────────────────────────
    //  LOGIN
    // ──────────────────────────────────────────────

    /** Show the login form. */
    public function showLogin()
    {
        return view('auth.login');
    }

    /** Handle the login form submission. */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (Auth::user()->role === 'student') {
                return redirect()->route('student.enrollments')
                    ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'The provided credentials do not match our records.']);
    }

    // ──────────────────────────────────────────────
    //  LOGOUT
    // ──────────────────────────────────────────────

    /** Log the user out and redirect to login. */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }

    // ──────────────────────────────────────────────
    //  DASHBOARD (protected)
    // ──────────────────────────────────────────────

    /** Show a simple dashboard page after login. */
    public function dashboard()
    {
        if (Auth::user()->role === 'student') {
            return redirect()->route('student.enrollments');
        }

        abort_unless(Auth::user()->hasAnyRole(['administrator', 'staff']), 403);

        return view('auth.dashboard', [
            'totalStudents' => Student::count(),
            'totalSubjects' => Subject::count(),
            'totalEnrollments' => Enrollment::count(),
            'currentSemesterEnrollments' => Enrollment::where('school_year', $this->currentSchoolYear())
                ->where('semester', $this->currentSemester())
                ->count(),
            'recentStudents' => Student::latest()->limit(6)->get(),
            'recentEnrollments' => Enrollment::with(['student', 'subject'])->latest()->limit(7)->get(),
        ]);
    }

    private function currentSchoolYear(): string
    {
        $year = now()->year;

        return now()->month >= 8
            ? "{$year}-".($year + 1)
            : ($year - 1)."-{$year}";
    }

    private function currentSemester(): string
    {
        $month = now()->month;

        return match (true) {
            $month >= 8 && $month <= 12 => 'First',
            $month >= 1 && $month <= 5 => 'Second',
            default => 'Summer',
        };
    }
}
