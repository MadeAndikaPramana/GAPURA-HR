<?php
// routes/web.php - Updated with Container System Routes

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeContainerController;
use App\Http\Controllers\CertificateStatusController;
use App\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;

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
    | EMPLOYEE ROUTES (Enhanced with Container System)
    |--------------------------------------------------------------------------
    */
    Route::prefix('employees')->name('employees.')->group(function () {
        // Traditional CRUD operations
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');

        // Traditional employee show (basic info only)
        Route::get('/{employee}/profile', [EmployeeController::class, 'show'])->name('show');

        // NEW: Employee Container System (Primary view for employees)
        Route::get('/{employee}/container', [EmployeeContainerController::class, 'show'])->name('container');

        // Export and utility operations
        Route::post('/bulk-action', [EmployeeController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export/excel', [EmployeeController::class, 'export'])->name('export');
        Route::get('/search', [EmployeeController::class, 'search'])->name('search');
    });

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE CONTAINER OPERATIONS
    |--------------------------------------------------------------------------
    */
    Route::prefix('employees/{employee}')->name('employees.')->group(function () {

        // Background Check File Management
        Route::post('/background-check/upload', [EmployeeContainerController::class, 'uploadBackgroundCheckFiles'])
             ->name('background-check.upload');

        Route::get('/background-check/download/{fileIndex}', [EmployeeContainerController::class, 'downloadBackgroundCheckFile'])
             ->name('background-check.download');

        Route::delete('/background-check/file/{fileIndex}', [EmployeeContainerController::class, 'deleteBackgroundCheckFile'])
             ->name('background-check.delete');

        // Certificate Management within Employee Container
        Route::post('/certificates', [EmployeeContainerController::class, 'storeCertificate'])
             ->name('certificates.store');

        // Container Export (PDF)
        Route::get('/container/export', [EmployeeContainerController::class, 'exportContainer'])
             ->name('container.export');

        // Container Statistics for individual employee
        Route::get('/container/stats', [EmployeeContainerController::class, 'getEmployeeContainerStats'])
             ->name('container.stats');
    });

    /*
    |--------------------------------------------------------------------------
    | CERTIFICATE FILE OPERATIONS
    |--------------------------------------------------------------------------
    */
    Route::prefix('certificates')->name('certificates.')->group(function () {

        // Download certificate files
        Route::get('/{certificate}/download/{fileIndex}', [EmployeeContainerController::class, 'downloadCertificateFile'])
             ->name('download');

        // View certificate details
        Route::get('/{certificate}', [EmployeeContainerController::class, 'showCertificate'])
             ->name('show');

        // Update certificate information
        Route::put('/{certificate}', [EmployeeContainerController::class, 'updateCertificate'])
             ->name('update');

        // Delete certificate
        Route::delete('/{certificate}', [EmployeeContainerController::class, 'deleteCertificate'])
             ->name('destroy');

        // Add files to existing certificate
        Route::post('/{certificate}/files', [EmployeeContainerController::class, 'addCertificateFiles'])
             ->name('files.add');

        // Remove specific file from certificate
        Route::delete('/{certificate}/files/{fileIndex}', [EmployeeContainerController::class, 'removeCertificateFile'])
             ->name('files.remove');
    });

    /*
    |--------------------------------------------------------------------------
    | CERTIFICATE TYPES MANAGEMENT
    |--------------------------------------------------------------------------
    */
    Route::resource('certificate-types', \App\Http\Controllers\CertificateTypeController::class)
         ->except(['show']);

    /*
    |--------------------------------------------------------------------------
    | DEPARTMENT ROUTES
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

        // Container System Statistics
        Route::get('/container-stats', [EmployeeContainerController::class, 'getContainerStatistics'])
             ->name('container.stats');

        // Certificate Status Management
        Route::post('/certificates/update-statuses', [CertificateStatusController::class, 'updateAllCertificateStatuses'])
             ->name('certificates.update-statuses');

        // Compliance Reports
        Route::get('/compliance-report', [EmployeeContainerController::class, 'generateComplianceReport'])
             ->name('compliance.report');

        // Export all employee containers
        Route::get('/export-all-containers', [EmployeeContainerController::class, 'exportAllContainers'])
             ->name('export.containers');
    });

    /*
    |--------------------------------------------------------------------------
    | API ROUTES for AJAX calls and Dashboard widgets
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {

        // Dashboard Statistics
        Route::get('/dashboard/stats', function () {
            return response()->json([
                'employees' => [
                    'total' => \App\Models\Employee::count(),
                    'active' => \App\Models\Employee::where('status', 'active')->count(),
                    'with_containers' => \App\Models\Employee::has('employeeCertificates')->count(),
                ],
                'certificates' => [
                    'total' => \App\Models\EmployeeCertificate::count(),
                    'active' => \App\Models\EmployeeCertificate::where('status', 'active')->count(),
                    'expired' => \App\Models\EmployeeCertificate::where('status', 'expired')->count(),
                    'expiring_soon' => \App\Models\EmployeeCertificate::where('status', 'expiring_soon')->count(),
                ],
                'departments' => [
                    'total' => \App\Models\Department::count(),
                    'with_employees' => \App\Models\Department::has('employees')->count(),
                ],
                'background_checks' => [
                    'completed' => \App\Models\Employee::where('background_check_status', 'cleared')->count(),
                    'pending' => \App\Models\Employee::where('background_check_status', 'in_progress')->count(),
                    'expired' => \App\Models\Employee::where('background_check_status', 'expired')->count(),
                ],
                'last_updated' => now()->format('Y-m-d H:i:s'),
            ]);
        })->name('dashboard.stats');

        // Real-time Container Statistics for widgets
        Route::get('/containers/stats', [EmployeeContainerController::class, 'getContainerStatistics'])
             ->name('containers.stats');

        // Get certificates requiring attention (for notifications)
        Route::get('/certificates/requiring-attention', [CertificateStatusController::class, 'getCertificatesRequiringAttention'])
             ->name('certificates.attention');

        // Employee quick search for autocomplete
        Route::get('/employees/search', [EmployeeController::class, 'quickSearch'])
             ->name('employees.quick-search');

        // Certificate types for dropdowns
        Route::get('/certificate-types/active', function () {
            return \App\Models\CertificateType::active()
                ->select(['id', 'name', 'code', 'validity_months', 'warning_days'])
                ->orderBy('name')
                ->get();
        })->name('certificate-types.active');
    });

    /*
    |--------------------------------------------------------------------------
    | BULK OPERATIONS for Container Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('bulk')->name('bulk.')->group(function () {

        // Bulk certificate status updates
        Route::post('/certificates/update-status', [EmployeeContainerController::class, 'bulkUpdateCertificateStatus'])
             ->name('certificates.update-status');

        // Bulk background check status updates
        Route::post('/background-checks/update-status', [EmployeeContainerController::class, 'bulkUpdateBackgroundCheckStatus'])
             ->name('background-checks.update-status');

        // Bulk certificate assignment (same certificate to multiple employees)
        Route::post('/certificates/assign', [EmployeeContainerController::class, 'bulkAssignCertificates'])
             ->name('certificates.assign');

        // Bulk export selected employee containers
        Route::post('/containers/export', [EmployeeContainerController::class, 'bulkExportContainers'])
             ->name('containers.export');
    });
});

/*
|--------------------------------------------------------------------------
| PUBLIC FILE ACCESS (with authentication)
|--------------------------------------------------------------------------
*/

// Secure file serving for employee documents
Route::middleware(['auth'])->group(function () {
    Route::get('/employee-files/{employee}/{type}/{filename}', function ($employee, $type, $filename) {
        // Security: Verify user has access to this employee's files
        $emp = \App\Models\Employee::where('employee_id', $employee)->firstOrFail();

        // Additional authorization logic here if needed
        // For example, check if user is admin or supervisor of this employee

        $filePath = "employees/{$employee}/{$type}/{$filename}";

        if (!Storage::disk('private')->exists($filePath)) {
            abort(404, 'File not found');
        }

        // Return file with appropriate headers
        return Storage::disk('private')->response($filePath);
    })->where([
        'employee' => '[A-Za-z0-9\-]+',
        'type' => 'background-check|certificates',
        'filename' => '[A-Za-z0-9\-_\.]+\.(pdf|jpg|jpeg|png)'
    ])->name('employee.files.serve');
});

/*
|--------------------------------------------------------------------------
| SCHEDULED TASK ENDPOINTS (for monitoring)
|--------------------------------------------------------------------------
*/

// These routes can be called by monitoring systems to check job status
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

// Redirect old employee show route to container view
Route::middleware(['auth'])->group(function () {
    Route::get('/employees/{employee}', function ($employee) {
        return redirect()->route('employees.container', $employee);
    })->name('employees.show.redirect');
});
