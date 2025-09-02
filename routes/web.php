<?php
// routes/web.php (Clean & Working for Phase 1)

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\EmployeeCertificatesController;
use App\Http\Controllers\CertificateTypesController;
use App\Http\Controllers\FileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Employee Container System Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    // Employee Routes (Enhanced for Container System)
    Route::prefix('employees')->name('employees.')->group(function () {

        // Core CRUD operations
        Route::get('/', [EmployeesController::class, 'index'])->name('index');
        Route::get('/create', [EmployeesController::class, 'create'])->name('create');
        Route::post('/', [EmployeesController::class, 'store'])->name('store');
        Route::get('/{employee}/edit', [EmployeesController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeesController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeesController::class, 'destroy'])->name('destroy');

        // NEW: Container view (main employee page)
        Route::get('/{employee}', [EmployeesController::class, 'show'])->name('show');
        Route::get('/{employee}/container', [EmployeesController::class, 'container'])->name('container');

        // NEW: Background Check file management
        Route::post('/{employee}/background-check/upload', [EmployeesController::class, 'uploadBackgroundCheck'])->name('background-check.upload');
        Route::get('/{employee}/background-check/download/{file}', [EmployeesController::class, 'downloadBackgroundCheck'])->name('background-check.download');
        Route::delete('/{employee}/background-check/file', [EmployeesController::class, 'deleteBackgroundCheckFile'])->name('background-check.delete-file');

        // Bulk operations
        Route::post('/bulk-update', [EmployeesController::class, 'bulkUpdate'])->name('bulk-update');

        // Export functionality
        Route::get('/export/excel', [EmployeesController::class, 'export'])->name('export');
    });

    // NEW: Employee Certificates Routes
    Route::prefix('employee-certificates')->name('employee-certificates.')->group(function () {
        Route::get('/', [EmployeeCertificatesController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeCertificatesController::class, 'create'])->name('create');
        Route::post('/', [EmployeeCertificatesController::class, 'store'])->name('store');
        Route::get('/{certificate}', [EmployeeCertificatesController::class, 'show'])->name('show');
        Route::get('/{certificate}/edit', [EmployeeCertificatesController::class, 'edit'])->name('edit');
        Route::put('/{certificate}', [EmployeeCertificatesController::class, 'update'])->name('update');
        Route::delete('/{certificate}', [EmployeeCertificatesController::class, 'destroy'])->name('destroy');

        // Certificate file management
        Route::post('/{certificate}/files/upload', [EmployeeCertificatesController::class, 'uploadFiles'])->name('files.upload');
        Route::get('/{certificate}/files/{file}/download', [EmployeeCertificatesController::class, 'downloadFile'])->name('file.download');
        Route::delete('/{certificate}/files/{file}', [EmployeeCertificatesController::class, 'deleteFile'])->name('file.delete');

        // Certificate operations
        Route::post('/{certificate}/renew', [EmployeeCertificatesController::class, 'renew'])->name('renew');
        Route::post('/bulk-update', [EmployeeCertificatesController::class, 'bulkUpdate'])->name('bulk-update');
    });

    // NEW: Certificate Types Management Routes
    Route::prefix('certificate-types')->name('certificate-types.')->group(function () {
        Route::get('/', [CertificateTypesController::class, 'index'])->name('index');
        Route::get('/create', [CertificateTypesController::class, 'create'])->name('create');
        Route::post('/', [CertificateTypesController::class, 'store'])->name('store');
        Route::get('/{certificateType}', [CertificateTypesController::class, 'show'])->name('show');
        Route::get('/{certificateType}/edit', [CertificateTypesController::class, 'edit'])->name('edit');
        Route::put('/{certificateType}', [CertificateTypesController::class, 'update'])->name('update');
        Route::delete('/{certificateType}', [CertificateTypesController::class, 'destroy'])->name('destroy');

        // Certificate type operations
        Route::post('/{certificateType}/toggle-status', [CertificateTypesController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/bulk-action', [CertificateTypesController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{certificateType}/statistics', [CertificateTypesController::class, 'statistics'])->name('statistics');
    });

    // NEW: File serving route (for local file access)
    Route::get('/files/serve/{path}', [FileController::class, 'serve'])
         ->where('path', '.*')
         ->name('files.serve');

    // NEW: File management routes
    Route::prefix('files')->name('files.')->group(function () {
        Route::get('/download/{path}', [FileController::class, 'download'])->where('path', '.*')->name('download');
        Route::get('/info/{path}', [FileController::class, 'getFileInfo'])->where('path', '.*')->name('info');
        Route::get('/exists/{path}', [FileController::class, 'exists'])->where('path', '.*')->name('exists');
    });

    // NEW: API Routes for AJAX calls
    Route::prefix('api')->name('api.')->group(function () {

        // File operations
        Route::post('/files/upload', [FileController::class, 'upload'])->name('files.upload');
        Route::delete('/files/delete', [FileController::class, 'delete'])->name('files.delete');
        Route::get('/files/stats', [FileController::class, 'getStorageStats'])->name('files.stats');
        Route::post('/files/cleanup', [FileController::class, 'cleanup'])->name('files.cleanup');

        // Certificate Types API
        Route::get('/certificate-types/search', [CertificateTypesController::class, 'apiSearch'])->name('certificate-types.search');
        Route::get('/certificate-types/categories', [CertificateTypesController::class, 'apiGetCategories'])->name('certificate-types.categories');

        // Quick stats for dashboard
        Route::get('/dashboard/stats', function () {
            return response()->json([
                'employees' => [
                    'total' => \App\Models\Employee::count(),
                    'active' => \App\Models\Employee::where('status', 'active')->count(),
                ],
                'certificates' => class_exists('App\Models\EmployeeCertificate') ? [
                    'total' => \App\Models\EmployeeCertificate::count(),
                    'active' => \App\Models\EmployeeCertificate::where('status', 'active')->count(),
                    'expired' => \App\Models\EmployeeCertificate::where('status', 'expired')->count(),
                    'expiring_soon' => \App\Models\EmployeeCertificate::where('status', 'expiring_soon')->count(),
                ] : ['total' => 0, 'active' => 0, 'expired' => 0, 'expiring_soon' => 0],
                'departments' => [
                    'total' => \App\Models\Department::count(),
                    'active' => \App\Models\Department::where('is_active', true)->count(),
                ]
            ]);
        })->name('dashboard.stats');
    });
});

/*
|--------------------------------------------------------------------------
| LEGACY Routes (Keep existing if controllers exist)
|--------------------------------------------------------------------------
*/

// Only include if controllers exist
$legacyControllers = [
    'departments' => 'App\Http\Controllers\DepartmentController',
];

foreach ($legacyControllers as $route => $controller) {
    if (class_exists($controller)) {
        Route::middleware(['auth'])->resource($route, $controller);
    }
}

require __DIR__.'/auth.php';
