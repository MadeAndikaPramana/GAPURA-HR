<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TrainingRecordController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('employees', EmployeeController::class);
    Route::post('employees/import', [EmployeeController::class, 'handleImport'])->name('employees.handleImport');
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::post('training-records/bulk-import', [TrainingRecordController::class, 'handleBulkImport'])->name('training-records.handleBulkImport');
    Route::get('training-records/bulk-export', [TrainingRecordController::class, 'bulkExport'])->name('training-records.bulkExport');
    Route::resource('training-records', TrainingRecordController::class);
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::resource('training-types', TrainingTypeController::class);
    Route::resource('departments', DepartmentController::class);
});