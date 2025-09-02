<?php
// routes/web.php (Updated for Employee Container System)

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
        Route::post('/{certificate}/extend', [EmployeeCertificatesController::class, 'extend'])->name('extend');
        Route::post('/{certificate}/revoke', [EmployeeCertificatesController::class, 'revoke'])->name('revoke');

        // Bulk operations
        Route::post('/bulk-update', [EmployeeCertificatesController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/expiring/export', [EmployeeCertificatesController::class, 'exportExpiring'])->name('export-expiring');
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

        // Certificate type statistics
        Route::get('/{certificateType}/statistics', [CertificateTypesController::class, 'statistics'])->name('statistics');
    });

    // NEW: File serving route (for local file access)
    Route::get('/files/serve/{path}', [FileController::class, 'serve'])
         ->where('path', '.*')
         ->name('files.serve');

    // NEW: API Routes for AJAX calls
    Route::prefix('api')->name('api.')->group(function () {

        // Employee search/autocomplete
        Route::get('/employees/search', [EmployeesController::class, 'search'])->name('employees.search');

        // Certificate validation
        Route::get('/certificates/validate/{number}', [EmployeeCertificatesController::class, 'validateCertificate'])->name('certificates.validate');

        // Quick stats for dashboard
        Route::get('/dashboard/stats', function () {
            return response()->json([
                'employees' => [
                    'total' => \App\Models\Employee::count(),
                    'active' => \App\Models\Employee::where('status', 'active')->count(),
                ],
                'certificates' => [
                    'total' => \App\Models\EmployeeCertificate::count(),
                    'active' => \App\Models\EmployeeCertificate::where('status', 'active')->count(),
                    'expired' => \App\Models\EmployeeCertificate::where('status', 'expired')->count(),
                    'expiring_soon' => \App\Models\EmployeeCertificate::where('status', 'expiring_soon')->count(),
                ],
                'departments' => [
                    'total' => \App\Models\Department::count(),
                    'active' => \App\Models\Department::where('is_active', true)->count(),
                ]
            ]);
        })->name('dashboard.stats');

        // Background check status update
        Route::patch('/employees/{employee}/background-check-status', function (\App\Models\Employee $employee, \Illuminate\Http\Request $request) {
            $request->validate(['status' => 'required|string']);

            $employee->update(['background_check_status' => $request->status]);

            return response()->json(['success' => true, 'message' => 'Status updated successfully']);
        })->name('employees.update-background-check-status');
    });

    // NEW: Reports and Analytics Routes
    Route::prefix('reports')->name('reports.')->group(function () {

        // Compliance reports
        Route::get('/compliance', function () {
            return Inertia::render('Reports/Compliance', [
                'stats' => [
                    'total_employees' => \App\Models\Employee::count(),
                    'compliant_employees' => \App\Models\Employee::whereDoesntHave('expiredCertificates')->count(),
                    'departments' => \App\Models\Department::with(['employees' => function($query) {
                        $query->withCount(['activeCertificates', 'expiredCertificates']);
                    }])->get()
                ]
            ]);
        })->name('compliance');

        // Certificate expiry report
        Route::get('/expiry', function () {
            $expiring = \App\Models\EmployeeCertificate::with(['employee', 'certificateType'])
                ->whereIn('status', ['expiring_soon', 'expired'])
                ->orderBy('expiry_date', 'asc')
                ->get();

            return Inertia::render('Reports/Expiry', [
                'certificates' => $expiring
            ]);
        })->name('expiry');

        // Employee container summary
        Route::get('/containers', function () {
            $employees = \App\Models\Employee::with(['department', 'employeeCertificates'])
                ->get()
                ->map(function ($employee) {
                    return $employee->getContainerSummary();
                });

            return Inertia::render('Reports/Containers', [
                'employees' => $employees
            ]);
        })->name('containers');
    });
});

/*
|--------------------------------------------------------------------------
| LEGACY Routes (Keep for backward compatibility)
|--------------------------------------------------------------------------
*/

// Keep existing training routes if they exist
if (class_exists(\App\Http\Controllers\TrainingRecordsController::class)) {
    Route::middleware(['auth'])->group(function () {
        Route::resource('training-records', \App\Http\Controllers\TrainingRecordsController::class);
        Route::resource('training-types', \App\Http\Controllers\TrainingTypesController::class);
        Route::resource('training-providers', \App\Http\Controllers\TrainingProvidersController::class);
    });
}

// Keep existing department routes
if (class_exists(\App\Http\Controllers\DepartmentsController::class)) {
    Route::middleware(['auth'])->group(function () {
        Route::resource('departments', \App\Http\Controllers\DepartmentsController::class);
    });
}

require __DIR__.'/auth.php';
