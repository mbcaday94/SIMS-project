<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentAccountController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeachingLoadController;
use Illuminate\Support\Facades\Route;

// ── Home ──────────────────────────────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

// ── Guest routes (redirect to dashboard if already logged in) ─────────────────
Route::middleware('guest')->group(function () {

    // Login
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

});

Route::match(['get', 'post'], '/register', function () {
    return redirect()->route('login')
        ->with('error', 'Student accounts are created by a SIMS administrator.');
})->name('register');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'role:administrator,staff'])->group(function () {
    Route::resource('students', StudentController::class)->except('destroy');
    Route::resource('subjects', SubjectController::class)->except('destroy');
    Route::resource('enrollments', EnrollmentController::class)->except('destroy');
    Route::resource('faculties', FacultyController::class)->except('show');
    Route::resource('teaching-loads', TeachingLoadController::class)->except('show');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

Route::middleware(['auth', 'role:administrator'])->group(function () {
    Route::get('/admin', [AuthController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/curricula', [CurriculumController::class, 'index'])->name('curricula.index');
    Route::get('/curricula/template', [CurriculumController::class, 'template'])->name('curricula.template');
    Route::post('/curricula/import', [CurriculumController::class, 'import'])->name('curricula.import');
    Route::get('/curricula/{curriculum}', [CurriculumController::class, 'show'])->name('curricula.show');
    Route::post('/students/{student}/account', [StudentAccountController::class, 'store'])->name('students.account.store');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/my-enrollments', [StudentPortalController::class, 'enrollments'])->name('student.enrollments');
});
