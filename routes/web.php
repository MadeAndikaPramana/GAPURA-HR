<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TrainingRecordController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
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

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/dashboard/api-data', [DashboardController::class, 'apiData'])->name('dashboard.api-data');
    Route::post('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (HR Team)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->group(function () {

    // Employee Management
    Route::resource('employees', EmployeeController::class);
    Route::post('employees/import', [EmployeeController::class, 'handleImport'])->name('employees.handleImport');
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::post('employees/bulk-action', [EmployeeController::class, 'bulkAction'])->name('employees.bulkAction');

    // Training Records Management
    Route::resource('training-records', TrainingRecordController::class);
    Route::post('training-records/bulk-import', [TrainingRecordController::class, 'handleBulkImport'])->name('training-records.handleBulkImport');
    Route::get('training-records/bulk-export', [TrainingRecordController::class, 'bulkExport'])->name('training-records.bulkExport');
    Route::post('training-records/bulk-action', [TrainingRecordController::class, 'bulkAction'])->name('training-records.bulkAction');

    // Training Records - Special Routes
    Route::get('training-records-expiring', [TrainingRecordController::class, 'expiring'])->name('training-records.expiring');
    Route::post('training-records/{trainingRecord}/renew', [TrainingRecordController::class, 'renew'])->name('training-records.renew');

    // Training Records - API Routes for AJAX
    Route::prefix('api/training-records')->group(function () {
        Route::get('calculate-expiry', function(Request $request) {
            $service = app(\App\Services\TrainingStatusService::class);
            return response()->json([
                'expiry_date' => $service->calculateExpiryDate(
                    $request->issue_date,
                    $request->training_type_id
                )
            ]);
        })->name('api.training-records.calculate-expiry');

        Route::get('generate-certificate', function(Request $request) {
            $service = app(\App\Services\TrainingStatusService::class);
            return response()->json([
                'certificate_number' => $service->generateCertificateNumber(
                    $request->training_type_id,
                    $request->issuer
                )
            ]);
        })->name('api.training-records.generate-certificate');
    });
});

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:super_admin'])->group(function () {

    // Training Types Management
    Route::resource('training-types', TrainingTypeController::class);
    Route::post('training-types/{trainingType}/toggle-status', [TrainingTypeController::class, 'toggleStatus'])->name('training-types.toggle-status');

    // Department Management
    Route::resource('departments', DepartmentController::class);

    // System Management Routes
    Route::prefix('system')->name('system.')->group(function () {

        // Status Update Management
        Route::post('update-training-status', function() {
            $service = app(\App\Services\TrainingStatusService::class);
            $updated = $service->updateAllStatuses();

            return redirect()->back()->with('success', "Training statuses updated successfully. {$updated} records updated.");
        })->name('update-training-status');

        // System Statistics
        Route::get('stats', function() {
            $service = app(\App\Services\TrainingStatusService::class);
            $stats = $service->getDashboardStats();

            return Inertia::render('System/Stats', [
                'stats' => $stats,
                'system_info' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => Application::VERSION,
                    'database' => config('database.default'),
                    'cache' => config('cache.default'),
                    'queue' => config('queue.default'),
                ]
            ]);
        })->name('stats');

        // Import/Export Templates
        Route::get('templates', function() {
            return Inertia::render('System/Templates');
        })->name('templates');

        Route::get('templates/employees', function() {
            $headers = ['employee_id', 'name', 'department_id', 'position', 'status', 'background_check_date', 'background_check_notes'];
            $filename = 'employee_import_template.xlsx';

            return Excel::download(new class($headers) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
                private $headers;
                public function __construct($headers) { $this->headers = $headers; }
                public function array(): array { return []; }
                public function headings(): array { return $this->headers; }
            }, $filename);
        })->name('templates.employees');

        Route::get('templates/training-records', function() {
            $headers = ['employee_id', 'training_type', 'certificate_number', 'issuer', 'issue_date', 'expiry_date', 'notes'];
            $filename = 'training_records_import_template.xlsx';

            return Excel::download(new class($headers) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
                private $headers;
                public function __construct($headers) { $this->headers = $headers; }
                public function array(): array { return []; }
                public function headings(): array { return $this->headers; }
            }, $filename);
        })->name('templates.training-records');
    });
});

/*
|--------------------------------------------------------------------------
| API Routes for External Integration
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->prefix('api/v1')->name('api.v1.')->group(function () {

    // Compliance API
    Route::get('compliance/overview', function() {
        $service = app(\App\Services\TrainingStatusService::class);
        return response()->json($service->getDashboardStats());
    })->name('compliance.overview');

    Route::get('compliance/department/{department}', function($department) {
        $service = app(\App\Services\TrainingStatusService::class);
        $data = $service->getComplianceByDepartment();
        $departmentData = $data->where('department_name', $department)->first();

        return response()->json($departmentData ?: []);
    })->name('compliance.department');

    Route::get('expiring-certificates', function(Request $request) {
        $service = app(\App\Services\TrainingStatusService::class);
        $days = $request->get('days', 30);

        return response()->json($service->getExpiringSoon($days));
    })->name('expiring-certificates');
});

/*
|--------------------------------------------------------------------------
| Include Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return Inertia::render('Error', [
        'status' => 404,
        'message' => 'Page not found'
    ]);
});
