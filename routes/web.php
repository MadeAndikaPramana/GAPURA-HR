<?php
// routes/web.php - Final Structure: /employees = Container System, /sdm = Traditional CRUD

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeContainerController;
use App\Http\Controllers\SDMController;
use App\Http\Controllers\CertificateStatusController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\CertificateTypeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Main Application Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEES ROUTES - Container System (Digital Folders)
    |--------------------------------------------------------------------------
    */
    Route::prefix('employees')->name('employees.')->group(function () {

        // ✅ CONTAINER SYSTEM - Main functionality
        Route::get('/', [EmployeeContainerController::class, 'index'])->name('index');
        Route::get('/{employee}', [EmployeeContainerController::class, 'show'])->name('show');
        Route::put('/{employee}', [EmployeeContainerController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeContainerController::class, 'destroy'])->name('destroy');

        // ✅ CONTAINER OPERATIONS
        Route::prefix('{employee}')->group(function () {

            // Background Check Operations
            Route::post('/background-check/upload', [EmployeeContainerController::class, 'uploadBackgroundCheckFiles'])
                 ->name('background-check.upload');

            Route::get('/background-check/download/{fileIndex}', [EmployeeContainerController::class, 'downloadBackgroundCheckFile'])
                 ->name('background-check.download');

            Route::put('/background-check', [EmployeeContainerController::class, 'updateBackgroundCheck'])
                 ->name('background-check.update');

            Route::delete('/background-check/file/{fileIndex}', [EmployeeContainerController::class, 'deleteBackgroundCheckFile'])
                 ->name('background-check.file.delete');

            // Certificate Operations
            Route::post('/certificates', [EmployeeContainerController::class, 'storeCertificate'])
                 ->name('certificates.store');

            Route::put('/certificates/{certificate}', [EmployeeContainerController::class, 'updateCertificate'])
                 ->name('certificates.update');

            Route::delete('/certificates/{certificate}', [EmployeeContainerController::class, 'deleteCertificate'])
                 ->name('certificates.destroy');

            Route::post('/certificates/{certificate}/files', [EmployeeContainerController::class, 'addCertificateFiles'])
                 ->name('certificates.files.add');

            Route::delete('/certificates/{certificate}/files/{fileIndex}', [EmployeeContainerController::class, 'removeCertificateFile'])
                 ->name('certificates.files.remove');

            // Container Export & Stats
            Route::get('/container/export', [EmployeeContainerController::class, 'exportContainer'])
                 ->name('container.export');

            Route::get('/container/stats', [EmployeeContainerController::class, 'getEmployeeContainerStats'])
                 ->name('container.stats');
        });

        // ✅ SEARCH & UTILITY
        Route::get('/search', [EmployeeContainerController::class, 'search'])->name('search');
        Route::get('/export/excel', [EmployeeContainerController::class, 'exportAll'])->name('export');
    });

    /*
    |--------------------------------------------------------------------------
    | SDM ROUTES - Traditional Employee CRUD Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('sdm')->name('sdm.')->group(function () {

        // ✅ TRADITIONAL CRUD OPERATIONS
        Route::get('/', [SDMController::class, 'index'])->name('index');
        Route::get('/create', [SDMController::class, 'create'])->name('create');
        Route::post('/', [SDMController::class, 'store'])->name('store');
        Route::get('/{employee}', [SDMController::class, 'show'])->name('show');
        Route::get('/{employee}/edit', [SDMController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [SDMController::class, 'update'])->name('update');
        Route::delete('/{employee}', [SDMController::class, 'destroy'])->name('destroy');

        // ✅ UTILITY OPERATIONS
        Route::post('/bulk-action', [SDMController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export/excel', [SDMController::class, 'export'])->name('export');
        Route::get('/search', [SDMController::class, 'search'])->name('search');
    });

    /*
    |--------------------------------------------------------------------------
    | CERTIFICATE FILE DOWNLOADS (Separate for easier access control)
    |--------------------------------------------------------------------------
    */
    Route::prefix('certificates')->name('certificates.')->middleware(['auth'])->group(function () {
        Route::get('/{certificate}/download/{fileIndex}', [EmployeeContainerController::class, 'downloadCertificateFile'])
             ->name('download');

        Route::get('/{certificate}/view', [EmployeeContainerController::class, 'viewCertificate'])
             ->name('view');
    });

    /*
    |--------------------------------------------------------------------------
    | CERTIFICATE TYPES MANAGEMENT
    |--------------------------------------------------------------------------
    */
    if (class_exists('App\Http\Controllers\CertificateTypeController')) {
        Route::resource('certificate-types', CertificateTypeController::class)
             ->except(['show']);
    }

    /*
    |--------------------------------------------------------------------------
    | DEPARTMENT ROUTES (Optional)
    |--------------------------------------------------------------------------
    */
    if (class_exists('App\Http\Controllers\DepartmentController')) {
        Route::resource('departments', DepartmentController::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SYSTEM MANAGEMENT & REPORTS
    |--------------------------------------------------------------------------
    */
    Route::prefix('system')->name('system.')->group(function () {

        // Certificate Status Updates
        Route::post('/update-certificate-statuses', [CertificateStatusController::class, 'updateAllCertificateStatuses'])
             ->name('update-certificates');

        // Compliance Reports
        Route::get('/compliance-report', [EmployeeContainerController::class, 'generateComplianceReport'])
             ->name('compliance-report');

        // Export all containers
        Route::get('/export-all-containers', [EmployeeContainerController::class, 'exportAllContainers'])
             ->name('export-containers');
    });

    /*
    |--------------------------------------------------------------------------
    | API ROUTES for AJAX calls
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {

        // Dashboard Statistics
        Route::get('/dashboard/stats', function () {
            try {
                return response()->json([
                    'employees' => [
                        'total' => \App\Models\Employee::count(),
                        'active' => \App\Models\Employee::where('status', 'active')->count(),
                        'with_certificates' => \App\Models\Employee::has('employeeCertificates')->count(),
                        'with_background_check' => \App\Models\Employee::whereNotNull('background_check_date')->count(),
                    ],
                    'certificates' => [
                        'total' => \App\Models\EmployeeCertificate::count(),
                        'active' => \App\Models\EmployeeCertificate::where('status', 'active')->count(),
                        'expired' => \App\Models\EmployeeCertificate::where('status', 'expired')->count(),
                        'expiring_soon' => \App\Models\EmployeeCertificate::where('status', 'expiring_soon')->count(),
                    ],
                    'last_updated' => now()->format('Y-m-d H:i:s'),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'employees' => ['total' => 0, 'active' => 0],
                    'certificates' => ['total' => 0, 'active' => 0, 'expired' => 0, 'expiring_soon' => 0],
                    'last_updated' => now()->format('Y-m-d H:i:s'),
                    'error' => 'Database not ready'
                ]);
            }
        })->name('dashboard.stats');

        // Employee search
        Route::get('/employees/search', function () {
            $query = request('q', '');
            if (empty($query)) {
                return response()->json([]);
            }

            try {
                return \App\Models\Employee::where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('nip', 'LIKE', "%{$query}%")
                      ->orWhere('employee_id', 'LIKE', "%{$query}%");
                })
                ->with(['department:id,name'])
                ->select(['id', 'name', 'nip', 'employee_id', 'position', 'department_id'])
                ->limit(10)
                ->get()
                ->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'nip' => $employee->nip ?? $employee->employee_id,
                        'position' => $employee->position,
                        'department' => $employee->department?->name,
                    ];
                });
            } catch (\Exception $e) {
                return response()->json([]);
            }
        })->name('employees.search');

        // Container summary
        Route::get('/employees/{employee}/summary', function (\App\Models\Employee $employee) {
            try {
                $employee->load(['employeeCertificates', 'department']);

                return response()->json([
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'nip' => $employee->nip,
                        'position' => $employee->position,
                        'department' => $employee->department?->name,
                        'background_check_status' => $employee->background_check_status,
                        'background_check_date' => $employee->background_check_date?->format('Y-m-d'),
                    ],
                    'certificates' => [
                        'total' => $employee->employeeCertificates->count(),
                        'active' => $employee->employeeCertificates->where('status', 'active')->count(),
                        'expired' => $employee->employeeCertificates->where('status', 'expired')->count(),
                        'expiring_soon' => $employee->employeeCertificates->where('status', 'expiring_soon')->count(),
                    ],
                    'background_check' => [
                        'has_files' => !empty($employee->background_check_files),
                        'file_count' => count($employee->background_check_files ?? []),
                        'status' => $employee->background_check_status,
                        'last_update' => $employee->background_check_date?->format('Y-m-d H:i:s'),
                    ]
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        })->name('employees.summary');
    });
});

/*
|--------------------------------------------------------------------------
| SECURE FILE ACCESS (Employee Documents)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/employee-files/{employee}/{type}/{filename}', function ($employee, $type, $filename) {
        try {
            // Security check: ensure user has access to this employee's files
            $employeeModel = \App\Models\Employee::findOrFail($employee);

            $filePath = "employees/{$employee}/{$type}/{$filename}";

            if (!Storage::disk('private')->exists($filePath)) {
                abort(404, 'File not found');
            }

            // Log file access for audit trail
            \Log::info("File accessed: {$filePath} by user " . auth()->id());

            return Storage::disk('private')->response($filePath);
        } catch (\Exception $e) {
            abort(404, 'File access error');
        }
    })->where([
        'employee' => '[A-Za-z0-9\-]+',
        'type' => 'background-check|certificates',
        'filename' => '[A-Za-z0-9\-_\.]+\.(pdf|jpg|jpeg|png|doc|docx)'
    ])->name('employee.files.serve');
});

/*
|--------------------------------------------------------------------------
| SCHEDULED TASK ENDPOINTS (for monitoring/cron)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('cron')->name('cron.')->group(function () {

    // Manual trigger for certificate status updates
    Route::post('/update-certificate-statuses', [CertificateStatusController::class, 'updateAllCertificateStatuses'])
         ->name('update-certificates');

    // Generate daily compliance report
    Route::post('/generate-compliance-report', [EmployeeContainerController::class, 'generateDailyComplianceReport'])
         ->name('compliance-report');

    // Cleanup orphaned files
    Route::post('/cleanup-files', [EmployeeContainerController::class, 'cleanupOrphanedFiles'])
         ->name('cleanup-files');
});
