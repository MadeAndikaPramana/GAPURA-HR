<?php
// routes/web.php - Digital File Container System

use App\Http\Controllers\SdmController;
use App\Http\Controllers\EmployeeContainerController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| MAIN APPLICATION ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard redirect
    Route::get('/', function () {
        return redirect()->route('employee-containers.index');
    });

    Route::get('/dashboard', function () {
        return redirect()->route('employee-containers.index');
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | SDM (EMPLOYEE MANAGEMENT) - Master Data + Excel Integration
    |--------------------------------------------------------------------------
    */
    Route::prefix('sdm')->name('sdm.')->group(function () {
        // Basic employee CRUD
        Route::get('/', [SdmController::class, 'index'])->name('index');
        Route::get('/create', [SdmController::class, 'create'])->name('create');
        Route::post('/', [SdmController::class, 'store'])->name('store');
        Route::get('/{employee}/edit', [SdmController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [SdmController::class, 'update'])->name('update');
        Route::delete('/{employee}', [SdmController::class, 'destroy'])->name('destroy');

        // Excel Integration
        Route::get('/excel/template', [SdmController::class, 'downloadExcelTemplate'])->name('excel.template');
        Route::post('/excel/sync', [SdmController::class, 'syncExcelData'])->name('excel.sync');
        Route::get('/excel/sync-status', [SdmController::class, 'getSyncStatus'])->name('excel.sync-status');

        // Search & Filter
        Route::get('/search', [SdmController::class, 'search'])->name('search');
    });

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE CONTAINERS - Digital File Storage (MAIN FEATURE)
    |--------------------------------------------------------------------------
    */
    Route::prefix('employee-containers')->name('employee-containers.')->group(function () {
        // Container list (Google Drive style)
        Route::get('/', [EmployeeContainerController::class, 'index'])->name('index');

        // Individual container view
        Route::get('/{employee}', [EmployeeContainerController::class, 'show'])->name('show');

        // Background Check Operations
        Route::prefix('{employee}/background-check')->name('background-check.')->group(function () {
            Route::post('/upload', [EmployeeContainerController::class, 'uploadBackgroundCheck'])->name('upload');
            Route::put('/update', [EmployeeContainerController::class, 'updateBackgroundCheck'])->name('update');
            Route::get('/download/{fileIndex}', [EmployeeContainerController::class, 'downloadBackgroundCheck'])->name('download');
            Route::delete('/remove/{fileIndex}', [EmployeeContainerController::class, 'removeBackgroundCheck'])->name('remove');
        });

        // Certificate Operations (Recurrent Support)
        Route::prefix('{employee}/certificates')->name('certificates.')->group(function () {
            Route::post('/', [EmployeeContainerController::class, 'addCertificate'])->name('add');
            Route::put('/{certificate}', [EmployeeContainerController::class, 'updateCertificate'])->name('update');
            Route::delete('/{certificate}', [EmployeeContainerController::class, 'removeCertificate'])->name('remove');
            Route::get('/{certificate}/download/{fileIndex}', [EmployeeContainerController::class, 'downloadCertificate'])->name('download');
        });

        // Container Utilities
        Route::get('/{employee}/files-count', [EmployeeContainerController::class, 'getFilesCount'])->name('files-count');
        Route::post('/{employee}/refresh', [EmployeeContainerController::class, 'refreshContainer'])->name('refresh');
    });

    /*
    |--------------------------------------------------------------------------
    | TRAINING TYPES - Certificate Type Container (Reverse Lookup)
    |--------------------------------------------------------------------------
    */
    Route::prefix('training-types')->name('training-types.')->group(function () {
        // Training type CRUD
        Route::get('/', [TrainingTypeController::class, 'index'])->name('index');
        Route::get('/create', [TrainingTypeController::class, 'create'])->name('create');
        Route::post('/', [TrainingTypeController::class, 'store'])->name('store');
        Route::get('/{certificateType}/edit', [TrainingTypeController::class, 'edit'])->name('edit');
        Route::put('/{certificateType}', [TrainingTypeController::class, 'update'])->name('update');
        Route::delete('/{certificateType}', [TrainingTypeController::class, 'destroy'])->name('destroy');

        // Training Type Container (Who has this certificate?)
        Route::get('/{certificateType}/container', [TrainingTypeController::class, 'showContainer'])->name('container');
        Route::get('/{certificateType}/employees', [TrainingTypeController::class, 'getEmployeesList'])->name('employees');
        Route::get('/{certificateType}/statistics', [TrainingTypeController::class, 'getStatistics'])->name('statistics');
    });

    /*
    |--------------------------------------------------------------------------
    | FILE MANAGEMENT & SERVING
    |--------------------------------------------------------------------------
    */
    Route::prefix('files')->name('files.')->group(function () {
        // Serve employee files
        Route::get('/employee/{employee}/background-check/{fileIndex}', [FileController::class, 'serveBackgroundCheck'])
             ->name('background-check');

        Route::get('/employee/{employee}/certificate/{certificate}/{fileIndex}', [FileController::class, 'serveCertificate'])
             ->name('certificate');

        // File previews (PDF/Image)
        Route::get('/preview/background-check/{employee}/{fileIndex}', [FileController::class, 'previewBackgroundCheck'])
             ->name('preview.background-check');

        Route::get('/preview/certificate/{certificate}/{fileIndex}', [FileController::class, 'previewCertificate'])
             ->name('preview.certificate');

        // File validation (before upload)
        Route::post('/validate', [FileController::class, 'validateFile'])->name('validate');
    });

    /*
    |--------------------------------------------------------------------------
    | API ENDPOINTS (for AJAX calls)
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {
        // Search endpoints
        Route::get('/employees/search', [SdmController::class, 'apiSearch'])->name('employees.search');
        Route::get('/training-types/search', [TrainingTypeController::class, 'apiSearch'])->name('training-types.search');

        // Quick actions
        Route::post('/employee/{employee}/quick-update', [SdmController::class, 'quickUpdate'])->name('employee.quick-update');
        Route::get('/container/{employee}/stats', [EmployeeContainerController::class, 'getContainerStats'])->name('container.stats');
    });
});

/*
|--------------------------------------------------------------------------
| FILE SERVING ROUTES (Public with auth check)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/storage/containers/{path}', [FileController::class, 'serveFile'])
         ->where('path', '.*')
         ->name('storage.serve');
});
