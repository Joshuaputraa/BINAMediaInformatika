<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BkTeacherController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KesiswaanUserController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect('/dashboard'));
    Route::post('/logout', [AuthController::class, 'destroy']);

    Route::get('/dashboard', DashboardController::class);
    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students/{student}/handlings', [StudentController::class, 'handle']);

    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/pdf', [ReportController::class, 'pdf']);

    Route::middleware('role:kesiswaan')->group(function () {
        Route::post('/students', [StudentController::class, 'store']);

        Route::get('/teachers', [TeacherController::class, 'index']);
        Route::post('/teachers', [TeacherController::class, 'store']);

        Route::get('/bk-teachers', [BkTeacherController::class, 'index']);
        Route::post('/bk-teachers', [BkTeacherController::class, 'store']);

        Route::put('/students/{student}', [StudentController::class, 'update']);
        Route::delete('/students/{student}', [StudentController::class, 'destroy']);

        Route::put('/teachers/{teacher}', [TeacherController::class, 'update']);
        Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy']);

        Route::put('/bk-teachers/{bk}', [BkTeacherController::class, 'update']);
        Route::delete('/bk-teachers/{bk}', [BkTeacherController::class, 'destroy']);

        Route::get('/settings', [ProfileController::class, 'edit']);
        Route::put('/settings/profile', [ProfileController::class, 'update']);
        Route::put('/settings/password', [ProfileController::class, 'password']);

        Route::post('/kesiswaan-users', [KesiswaanUserController::class, 'store']);
        Route::put('/kesiswaan-users/{kesiswaan}', [KesiswaanUserController::class, 'update']);
        Route::delete('/kesiswaan-users/{kesiswaan}', [KesiswaanUserController::class, 'destroy']);
    });
});