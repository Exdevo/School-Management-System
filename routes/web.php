<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/classes', [SchoolClassController::class, 'index'])->name('classes.index');
        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::get('/results', [ResultController::class, 'index'])->name('results.index');
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

        Route::post('/classes', [SchoolClassController::class, 'store'])->name('classes.store');
        Route::put('/classes/{schoolClass}', [SchoolClassController::class, 'update'])->name('classes.update');
        Route::delete('/classes/{schoolClass}', [SchoolClassController::class, 'destroy'])->name('classes.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

        Route::post('/fees/structures', [FeeController::class, 'saveStructure'])->name('fees.structures.store');
        Route::post('/fees/payments', [FeeController::class, 'storePayment'])->name('fees.payments.store');
    });

    Route::middleware('role:admin,student,parent')->get('/fees', [FeeController::class, 'index'])->name('fees.index');
    Route::get('/fees/payments/{payment}/receipt', [FeeController::class, 'receipt'])->name('fees.receipt');

    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/results/enter', [ResultController::class, 'create'])->name('results.create');
        Route::post('/results', [ResultController::class, 'store'])->name('results.store');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    });
    Route::get('/students/{student}/report-card', [ResultController::class, 'report'])->name('results.report');
    Route::get('/students/{student}/report-card/pdf', [ResultController::class, 'downloadReport'])->name('results.download');
});

require __DIR__.'/auth.php';
