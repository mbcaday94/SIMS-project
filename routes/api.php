<?php

use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->middleware(['auth.basic', 'role:administrator,staff'])->group(function () {
    Route::apiResource('students', StudentController::class)->except('destroy');

    Route::delete('/students/{student}', [StudentController::class, 'destroy'])
        ->middleware('role:administrator')
        ->name('students.destroy');
});
