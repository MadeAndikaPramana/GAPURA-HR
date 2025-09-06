<?php
// routes/web.php - Complete updated routes

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeContainerController;
use App\Http\Controllers\CertificateStatusController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\TrainingTypeController; // ADD THIS
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

    // Dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | SDM MODULE ROUTES (Clean Structure)
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
    | EMPLOYEE CONTAINERS ROUTES (Main Feature)
    |--------------------------------------------------------------------------
    */
    Route::prefix('employee-containers')->name('employee-containers.')->group(function () {
        // Main container list page
        Route::get('/', [EmployeeContainerController::class, 'index'])->name('index');

        // Individual container view (digital folder)
        Route::get('/{employee}', [EmployeeContainerController::class, 'show'])->name('show');

        // Background Check Operations
        Route::post('/{employee}/background-check/upload', [EmployeeContainerController::class, 'uploadBackgroundCheckFiles'])
             ->name('background-check.upload');

        Route::get('/{employee}/background-check/download/{fileIndex}', [EmployeeContainerController::class, 'downloadBackgroundCheckFile'])
             ->name('background-check.download');

        Route::put('/{employee}/background-check', [EmployeeContainerController::class, 'updateBackgroundCheck'])
             ->name('background-check.update');

        // Certificate Operations within Containers
        Route::post('/{employee}/certificates', [EmployeeContainerController::class, 'storeCertificate'])
             ->name('certificates.store');

        Route::put('/{employee}/certificates/{certificate}', [EmployeeContainerController::class, 'updateCertificate'])
             ->name('certificates.update');

        Route::delete('/{employee}/certificates/{certificate}', [EmployeeContainerController::class, 'deleteCertificate'])
             ->name('certificates.destroy');

        Route::get('/certificates/{certificate}/download/{fileIndex}', [EmployeeContainerController::class, 'downloadCertificateFile'])
             ->name('certificates.download');
    });

    /*
    |--------------------------------------------------------------------------
    | TRAINING TYPES MANAGEMENT (Certificate Type Reverse Lookup) - NEW
    |--------------------------------------------------------------------------
    */
    Route::prefix('training-types')->name('training-types.')->middleware(['auth', 'verified'])->group(function () {

    // Basic CRUD Operations
    Route::get('/', [TrainingTypeController::class, 'index'])->name('index');
    Route::get('/create', [TrainingTypeController::class, 'create'])->name('create');
    Route::post('/', [TrainingTypeController::class, 'store'])->name('store');
    Route::get('/{certificateType}/edit', [TrainingTypeController::class, 'edit'])->name('edit');
    Route::put('/{certificateType}', [TrainingTypeController::class, 'update'])->name('update');
    Route::delete('/{certificateType}', [TrainingTypeController::class, 'destroy'])->name('destroy');

    // Training Type Container (Reverse Lookup - "certificate jenis ini dimiliki siapa saja")
    Route::get('/{certificateType}/container', [TrainingTypeController::class, 'showContainer'])->name('container');

    // API Routes for AJAX/Dynamic Loading
    Route::get('/{certificateType}/employees', [TrainingTypeController::class, 'getEmployeesList'])->name('employees-list');
    Route::get('/{certificateType}/stats', [TrainingTypeController::class, 'getContainerStats'])->name('stats');
    Route::get('/search', [TrainingTypeController::class, 'search'])->name('search');
});
    /*
    |--------------------------------------------------------------------------
    | EMPLOYEES MANAGEMENT ROUTES (Admin/CRUD Operations) - LEGACY SUPPORT
    |--------------------------------------------------------------------------
    */
    Route::prefix('employees')->name('employees.')->group(function () {
        // Basic employee CRUD (admin operations)
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');

        // Export and utility operations
        Route::post('/bulk-action', [EmployeeController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/export/excel', [EmployeeController::class, 'export'])->name('export');
        Route::get('/search', [EmployeeController::class, 'search'])->name('search');
    });

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
    | API ROUTES for AJAX calls and Dashboard widgets
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {

        // Container statistics for dashboard
        Route::get('/container-stats', [EmployeeContainerController::class, 'getContainerStatistics'])
             ->name('container-stats');

        // Certificate status distribution
        Route::get('/certificate-distribution', [EmployeeContainerController::class, 'getCertificateDistribution'])
             ->name('certificate-distribution');

        // Employee search autocomplete
        Route::get('/employees/search', [EmployeeController::class, 'searchEmployees'])
             ->name('employees.search');

        // Training Types search autocomplete
        Route::get('/training-types/search', [TrainingTypeController::class, 'search'])
             ->name('training-types.search');
    });
});

/*
|--------------------------------------------------------------------------
| FILE SERVING ROUTES (Protected)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Serve employee files (background checks, certificates)
    Route::get('/employee-files/{employee}/{type}/{filename}', [EmployeeContainerController::class, 'serveFile'])
         ->where(['filename' => '.*\.(pdf|jpg|jpeg|png)'])
         ->name('employee.files.serve');
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

/*
|--------------------------------------------------------------------------
| REDIRECT LEGACY ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Redirect old employee show route to container view
    Route::get('/employees/{employee}/show', function ($employee) {
        return redirect()->route('employee-containers.show', $employee);
    })->name('employees.show.redirect');

    // Redirect old certificate-types routes to training-types
    Route::get('/certificate-types', function () {
        return redirect()->route('training-types.index');
    });

    Route::get('/certificate-types/{id}', function ($id) {
        return redirect()->route('training-types.container', $id);
    });
});
