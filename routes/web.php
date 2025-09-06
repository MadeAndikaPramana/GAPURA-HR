<?php
// routes/web.php - Fixed Employee Container System Routes

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeContainerController;
use App\Http\Controllers\CertificateStatusController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\CertificateTypeController;
use App\Http\Controllers\SDMController;
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

    // Dashboard - redirect to employee containers
    Route::get('/', function () {
        return redirect()->route('employee-containers.index');
    });

    Route::get('/dashboard', function () {
        return redirect()->route('employee-containers.index');
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE CONTAINERS ROUTES (Main Feature) - PRIMARY
    |--------------------------------------------------------------------------
    */
    Route::prefix('employee-containers')->name('employee-containers.')->group(function () {

        // Main container list page (Grid View)
        Route::get('/', [EmployeeContainerController::class, 'index'])->name('index');

        // Individual container view (Digital folder)
        Route::get('/{employee}', [EmployeeContainerController::class, 'show'])->name('show');

        // Background Check Operations
        Route::post('/{employee}/background-check/upload', [EmployeeContainerController::class, 'uploadBackgroundCheckFiles'])
             ->name('background-check.upload');

        Route::get('/{employee}/background-check/download/{fileIndex}', [EmployeeContainerController::class, 'downloadBackgroundCheckFile'])
             ->name('background-check.download');

        Route::put('/{employee}/background-check', [EmployeeContainerController::class, 'updateBackgroundCheck'])
             ->name('background-check.update');

        Route::delete('/{employee}/background-check/{fileIndex}', [EmployeeContainerController::class, 'deleteBackgroundCheckFile'])
             ->name('background-check.delete');

        // Certificate Operations within Containers
        Route::post('/{employee}/certificates', [EmployeeContainerController::class, 'storeCertificate'])
             ->name('certificates.store');

        Route::put('/{employee}/certificates/{certificate}', [EmployeeContainerController::class, 'updateCertificate'])
             ->name('certificates.update');

        Route::delete('/{employee}/certificates/{certificate}', [EmployeeContainerController::class, 'deleteCertificate'])
             ->name('certificates.destroy');

        Route::get('/{employee}/certificates/{certificate}/download/{fileIndex}', [EmployeeContainerController::class, 'downloadCertificateFile'])
             ->name('certificates.download');

        // Bulk operations for containers
        Route::post('/bulk-update-status', [EmployeeContainerController::class, 'bulkUpdateCertificateStatus'])
             ->name('bulk-update-status');

        // Search and filter containers
        Route::get('/search', [EmployeeContainerController::class, 'search'])->name('search');

        // Export container data
        Route::get('/export', [EmployeeContainerController::class, 'exportContainers'])->name('export');
    });

    /*
    |--------------------------------------------------------------------------
    | SDM MODULE ROUTES (Employee Master Data Management)
    |--------------------------------------------------------------------------
    */
    Route::prefix('sdm')->name('sdm.')->group(function () {

        // Basic CRUD Operations
        Route::get('/', [SDMController::class, 'index'])->name('index');
        Route::get('/create', [SDMController::class, 'create'])->name('create');
        Route::post('/', [SDMController::class, 'store'])->name('store');
        Route::get('/{employee}/edit', [SDMController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [SDMController::class, 'update'])->name('update');
        Route::delete('/{employee}', [SDMController::class, 'destroy'])->name('destroy');

        // Excel Integration Routes
        Route::get('/import', [SDMController::class, 'showImport'])->name('import');
        Route::post('/import', [SDMController::class, 'import'])->name('import.process');
        Route::get('/template/download', [SDMController::class, 'downloadTemplate'])->name('download-template');
        Route::get('/export', [SDMController::class, 'export'])->name('export');

        // Bulk Operations
        Route::post('/bulk-action', [SDMController::class, 'bulkAction'])->name('bulk-action');

        // AJAX Endpoints
        Route::get('/search', [SDMController::class, 'search'])->name('search');
        Route::get('/api/stats', [SDMController::class, 'getStatistics'])->name('api.stats');
    });

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEES ROUTES (Legacy/Admin CRUD) - SECONDARY
    |--------------------------------------------------------------------------
    */
    Route::prefix('employees')->name('employees.')->group(function () {
        // Admin CRUD operations for employees (if needed)
        Route::get('/admin', [EmployeeController::class, 'index'])->name('admin.index');
        Route::get('/admin/create', [EmployeeController::class, 'create'])->name('admin.create');
        Route::post('/admin', [EmployeeController::class, 'store'])->name('admin.store');
        Route::get('/admin/{employee}/edit', [EmployeeController::class, 'edit'])->name('admin.edit');
        Route::put('/admin/{employee}', [EmployeeController::class, 'update'])->name('admin.update');
        Route::delete('/admin/{employee}', [EmployeeController::class, 'destroy'])->name('admin.destroy');

        // Export and utility operations
        Route::post('/admin/bulk-action', [EmployeeController::class, 'bulkAction'])->name('admin.bulk-action');
        Route::get('/admin/export', [EmployeeController::class, 'export'])->name('admin.export');
        Route::get('/admin/search', [EmployeeController::class, 'search'])->name('admin.search');
    });

    /*
    |--------------------------------------------------------------------------
    | CERTIFICATE TYPES MANAGEMENT (Training Type)
    |--------------------------------------------------------------------------
    */
    Route::prefix('certificate-types')->name('certificate-types.')->group(function () {
        Route::get('/', [CertificateTypeController::class, 'index'])->name('index');
        Route::get('/create', [CertificateTypeController::class, 'create'])->name('create');
        Route::post('/', [CertificateTypeController::class, 'store'])->name('store');
        Route::get('/{certificateType}/edit', [CertificateTypeController::class, 'edit'])->name('edit');
        Route::put('/{certificateType}', [CertificateTypeController::class, 'update'])->name('update');
        Route::delete('/{certificateType}', [CertificateTypeController::class, 'destroy'])->name('destroy');

        // Analytics and reporting for training types
        Route::get('/{certificateType}/analytics', [CertificateTypeController::class, 'analytics'])->name('analytics');
        Route::get('/{certificateType}/employees', [CertificateTypeController::class, 'employeesList'])->name('employees');
    });

    /*
    |--------------------------------------------------------------------------
    | DEPARTMENT ROUTES (Optional)
    |--------------------------------------------------------------------------
    */
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
        Route::get('/create', [DepartmentController::class, 'create'])->name('create');
        Route::post('/', [DepartmentController::class, 'store'])->name('store');
        Route::get('/{department}/edit', [DepartmentController::class, 'edit'])->name('edit');
        Route::put('/{department}', [DepartmentController::class, 'update'])->name('update');
        Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('destroy');
    });

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

        // System health check
        Route::get('/health', function () {
            return response()->json([
                'status' => 'ok',
                'timestamp' => now(),
                'employees' => \App\Models\Employee::count(),
                'certificates' => \App\Models\EmployeeCertificate::count(),
                'storage_used' => \Storage::disk('private')->size('containers')
            ]);
        })->name('health');
    });

    /*
    |--------------------------------------------------------------------------
    | API ROUTES for AJAX calls and Dashboard widgets
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {

        // Container statistics
        Route::get('/container-stats', [EmployeeContainerController::class, 'getContainerStatistics'])
             ->name('container-stats');

        // Quick search across all containers
        Route::get('/quick-search', [EmployeeContainerController::class, 'quickSearch'])
             ->name('quick-search');

        // Certificate expiry notifications
        Route::get('/certificate-alerts', [EmployeeContainerController::class, 'getCertificateAlerts'])
             ->name('certificate-alerts');

        // File upload progress
        Route::post('/upload-progress', function () {
            return response()->json(['status' => 'uploading']);
        })->name('upload-progress');
    });

    /*
    |--------------------------------------------------------------------------
    | FILE SERVING ROUTES (Secure file access)
    |--------------------------------------------------------------------------
    */
    Route::prefix('files')->name('files.')->middleware(['auth'])->group(function () {

        // Serve private files securely
        Route::get('/containers/{employee}/{type}/{filename}', function ($employee, $type, $filename) {
            $employee = \App\Models\Employee::findOrFail($employee);

            // Security check - ensure user has access
            if (!auth()->user()->canAccessEmployeeContainer($employee)) {
                abort(403);
            }

            $path = "containers/employee-{$employee->id}/{$type}/{$filename}";

            if (!\Storage::disk('private')->exists($path)) {
                abort(404);
            }

            return \Storage::disk('private')->response($path);
        })->where([
            'type' => '(background-checks|certificates)',
            'filename' => '[a-zA-Z0-9\-_\.]+'
        ])->name('serve');

        // Serve certificate files with additional type folder
        Route::get('/containers/{employee}/certificates/{typeCode}/{filename}', function ($employee, $typeCode, $filename) {
            $employee = \App\Models\Employee::findOrFail($employee);

            if (!auth()->user()->canAccessEmployeeContainer($employee)) {
                abort(403);
            }

            $path = "containers/employee-{$employee->id}/certificates/{$typeCode}/{$filename}";

            if (!\Storage::disk('private')->exists($path)) {
                abort(404);
            }

            return \Storage::disk('private')->response($path);
        })->where([
            'typeCode' => '[a-zA-Z0-9\-_]+',
            'filename' => '[a-zA-Z0-9\-_\.]+'
        ])->name('serve-certificate');
    });
});

/*
|--------------------------------------------------------------------------
| SCHEDULED TASK ENDPOINTS (for cron jobs)
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

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (No authentication required)
|--------------------------------------------------------------------------
*/

// Welcome page with system info
Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => \Illuminate\Foundation\Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

// Health check for monitoring
Route::get('/health-check', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => config('app.version', '1.0.0')
    ]);
})->name('health-check');
