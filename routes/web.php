<?php
// routes/web.php - Complete Routes File

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployeeController; // Singular
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Welcome page
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Dashboard
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Profile Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Employee Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('employees')->name('employees.')->group(function () {
        // Core CRUD operations
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');

        // Additional operations
        Route::post('/bulk-action', [EmployeeController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export/excel', [EmployeeController::class, 'export'])->name('export');
        Route::get('/search', [EmployeeController::class, 'search'])->name('search');
    });

    /*
    |--------------------------------------------------------------------------
    | API Routes for Dashboard & AJAX calls
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {

        // Employee statistics for dashboard
        Route::get('/employees/stats', [EmployeeController::class, 'getStatistics'])->name('employees.stats');

        // Quick dashboard stats
        Route::get('/dashboard/stats', function () {
            return response()->json([
                'employees' => [
                    'total' => \App\Models\Employee::count(),
                    'active' => \App\Models\Employee::where('status', 'active')->count(),
                    'inactive' => \App\Models\Employee::where('status', 'inactive')->count(),
                ],
                'departments' => [
                    'total' => \App\Models\Department::count(),
                    'with_employees' => \App\Models\Department::has('employees')->count(),
                ],
                'last_updated' => now()->format('Y-m-d H:i:s'),
            ]);
        })->name('dashboard.stats');
    });
});

/*
|--------------------------------------------------------------------------
| Department Routes (if Department controller exists)
|--------------------------------------------------------------------------
*/

// Check if DepartmentController exists before adding routes
if (class_exists('App\Http\Controllers\DepartmentController')) {
    Route::middleware(['auth'])->group(function () {
        Route::resource('departments', App\Http\Controllers\DepartmentController::class);
    });
}

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
