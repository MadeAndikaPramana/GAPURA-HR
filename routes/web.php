<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\TrainingRecordController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\TrainingProviderController;
use App\Http\Controllers\ImportExportController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
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
    // ✅ Dashboard dengan data lengkap untuk mencegah undefined error
    $stats = [
        'total_employees' => \App\Models\Employee::where('status', 'active')->count(),
        'total_departments' => \App\Models\Department::count(),
        'total_training_records' => \App\Models\TrainingRecord::count(),
        'active_certificates' => \App\Models\TrainingRecord::where('compliance_status', 'compliant')->count(),
        'expiring_soon' => \App\Models\TrainingRecord::where('compliance_status', 'expiring_soon')->count(),
        'expired_certificates' => \App\Models\TrainingRecord::where('compliance_status', 'expired')->count(),
    ];

    // Calculate compliance rate
    $totalRecords = $stats['total_training_records'];
    $stats['compliance_rate'] = $totalRecords > 0
        ? round(($stats['active_certificates'] / $totalRecords) * 100, 2)
        : 100;

    return Inertia::render('Dashboard', [
        'stats' => $stats,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Employee Management
    Route::resource('employees', EmployeeController::class);
    Route::post('/employees/import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::get('/employees/export/template', [EmployeeController::class, 'exportTemplate'])->name('employees.export-template');
    Route::post('/employees/bulk-action', [EmployeeController::class, 'bulkAction'])->name('employees.bulk-action');

    // Department Management
    Route::resource('departments', DepartmentController::class);

    // Training Provider Management
    Route::resource('training-providers', TrainingProviderController::class);
    Route::post('/training-providers/bulk-action', [TrainingProviderController::class, 'bulkAction'])
        ->name('training-providers.bulk-action');
    Route::get('/training-providers/{provider}/statistics', [TrainingProviderController::class, 'getStatistics'])
        ->name('training-providers.statistics');
    Route::post('/training-providers/{provider}/update-rating', [TrainingProviderController::class, 'updateRating'])
        ->name('training-providers.update-rating');

    // Training Type Management
    Route::resource('training-types', TrainingTypeController::class);
    Route::get('/training-types/{trainingType}/statistics', [TrainingTypeController::class, 'getStatistics'])
        ->name('training-types.statistics');
    Route::post('/training-types/bulk-action', [TrainingTypeController::class, 'bulkAction'])
        ->name('training-types.bulk-action');

    // Training Record Management (UPDATED - ADDED PROVIDER FILTER ROUTE)
    Route::resource('training-records', TrainingRecordController::class);

    // ===== NEW AJAX ROUTE FOR EMPLOYEE CERTIFICATES =====
    Route::get('/training-records/employee/{employee}/certificates', [TrainingRecordController::class, 'getEmployeeCertificates'])
        ->name('training-records.employee-certificates');

    // ✅ NEW ROUTE FOR PROVIDER FILTERING
    Route::get('/api/training-providers/filter', [TrainingRecordController::class, 'getProvidersForFilters'])
        ->name('api.training-providers.filter');

    // ===== EMPLOYEE-CENTRIC TRAINING ROUTES =====
    Route::get('/training-records/employee/{employee}/edit', [TrainingRecordController::class, 'editEmployee'])
        ->name('training-records.edit-employee');
    Route::post('/training-records/employee/{employee}/update-training', [TrainingRecordController::class, 'updateEmployeeTraining'])
        ->name('training-records.update-employee-training');

    // Other training record routes
    Route::post('/training-records/bulk-action', [TrainingRecordController::class, 'bulkAction'])
        ->name('training-records.bulk-action');
    Route::get('/training-records/{trainingRecord}/certificates', [TrainingRecordController::class, 'getCertificates'])
        ->name('training-records.certificates');
    Route::post('/training-records/{trainingRecord}/mark-completed', [TrainingRecordController::class, 'markCompleted'])
        ->name('training-records.mark-completed');

    // Import/Export routes for training records
    Route::post('/training-records/import', [TrainingRecordController::class, 'import'])
        ->name('training-records.import');
    Route::get('/training-records/export/template', [TrainingRecordController::class, 'exportTemplate'])
        ->name('training-records.export-template');
    Route::get('/training-records/export', [TrainingRecordController::class, 'export'])
        ->name('training-records.export');

    // Compliance and reporting routes
    Route::get('/training-records/compliance/dashboard', [TrainingRecordController::class, 'complianceDashboard'])
        ->name('training-records.compliance-dashboard');
    Route::get('/training-records/reports/expiring', [TrainingRecordController::class, 'expiringCertificatesReport'])
        ->name('training-records.reports.expiring');
    Route::get('/training-records/reports/expired', [TrainingRecordController::class, 'expiredCertificatesReport'])
        ->name('training-records.reports.expired');
    Route::get('/training-records/reports/department/{department}', [TrainingRecordController::class, 'departmentReport'])
        ->name('training-records.reports.department');

    // Reminder system routes
    Route::post('/training-records/reminders/send', [TrainingRecordController::class, 'sendReminders'])
        ->name('training-records.reminders.send');
    Route::get('/training-records/reminders/queue', [TrainingRecordController::class, 'getReminderQueue'])
        ->name('training-records.reminders.queue');

    // Certificate lifecycle management
    Route::post('/training-records/{trainingRecord}/renew', [TrainingRecordController::class, 'renewCertificate'])
        ->name('training-records.renew');
    Route::post('/training-records/{trainingRecord}/suspend', [TrainingRecordController::class, 'suspendCertificate'])
        ->name('training-records.suspend');
    Route::post('/training-records/{trainingRecord}/reactivate', [TrainingRecordController::class, 'reactivateCertificate'])
        ->name('training-records.reactivate');

    // Analytics and statistics
    Route::get('/training-records/analytics/overview', [TrainingRecordController::class, 'analyticsOverview'])
        ->name('training-records.analytics.overview');
    Route::get('/training-records/analytics/trends', [TrainingRecordController::class, 'analyticsTrends'])
        ->name('training-records.analytics.trends');
    Route::get('/training-records/analytics/provider-performance', [TrainingRecordController::class, 'providerPerformanceAnalytics'])
        ->name('training-records.analytics.provider-performance');

    // Import/Export Management
    Route::get('/import-export', [ImportExportController::class, 'index'])->name('import-export.index');

    // System Templates route (for Ziggy)
    Route::get('/system/templates', function() {
        // You can return a view or Inertia page here, or point to a controller if you have one
        return Inertia::render('System/Templates');
    })->name('system.templates');

    // System Stats route (for Ziggy)
    Route::get('/system/stats', function() {
        // You can return a view or Inertia page here, or point to a controller if you have one
        return Inertia::render('System/Stats');
    })->name('system.stats');
});

require __DIR__.'/auth.php';
