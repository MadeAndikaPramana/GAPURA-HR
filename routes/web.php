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

    // 🚀 PHASE 3: Enhanced Training Type Management
    Route::prefix('training-types')->name('training-types.')->group(function () {
        // Basic CRUD (existing)
        Route::get('/', [TrainingTypeController::class, 'index'])->name('index');
        Route::get('/create', [TrainingTypeController::class, 'create'])->name('create');
        Route::post('/', [TrainingTypeController::class, 'store'])->name('store');
        Route::get('/{trainingType}', [TrainingTypeController::class, 'show'])->name('show');
        Route::get('/{trainingType}/edit', [TrainingTypeController::class, 'edit'])->name('edit');
        Route::put('/{trainingType}', [TrainingTypeController::class, 'update'])->name('update');
        Route::delete('/{trainingType}', [TrainingTypeController::class, 'destroy'])->name('destroy');

        // ⭐ TAMBAHKAN ROUTE INI UNTUK FIX DELETE PROBLEM
        Route::post('/{trainingType}/toggle-status', [TrainingTypeController::class, 'toggleStatus'])->name('toggle-status');

        // Existing routes (keep these)
        Route::get('/{trainingType}/statistics', [TrainingTypeController::class, 'getStatistics'])
            ->name('statistics');
        Route::post('/bulk-action', [TrainingTypeController::class, 'bulkAction'])
            ->name('bulk-action');

        // 🎯 NEW PHASE 3: Advanced Analytics & Management
        Route::get('/analytics/dashboard', [TrainingTypeController::class, 'analyticsDashboard'])->name('analytics-dashboard');
        Route::get('/compliance/report', [TrainingTypeController::class, 'complianceReport'])->name('compliance-report');
        Route::get('/{trainingType}/analytics', [TrainingTypeController::class, 'analytics'])->name('analytics');
        Route::get('/{trainingType}/department-breakdown', [TrainingTypeController::class, 'departmentBreakdown'])->name('department-breakdown');

        // 📊 NEW PHASE 3: Export & Reporting
        Route::get('/export/compliance-excel', [TrainingTypeController::class, 'exportComplianceExcel'])->name('export-compliance');
        Route::get('/export/statistics-pdf', [TrainingTypeController::class, 'exportStatisticsPdf'])->name('export-statistics');
        Route::get('/export/cost-analysis', [TrainingTypeController::class, 'exportCostAnalysis'])->name('export-cost-analysis');

        // 🔧 NEW PHASE 3: Management Operations
        Route::post('/{trainingType}/update-priority', [TrainingTypeController::class, 'updatePriority'])->name('update-priority');
        Route::post('/{trainingType}/update-compliance-target', [TrainingTypeController::class, 'updateComplianceTarget'])->name('update-compliance-target');
        Route::post('/bulk-update-statistics', [TrainingTypeController::class, 'bulkUpdateStatistics'])->name('bulk-update-statistics');
    });

    // 🔍 NEW PHASE 3: API Routes for AJAX requests
    Route::prefix('api/training-types')->name('api.training-types.')->group(function () {
        Route::get('/search', [TrainingTypeController::class, 'apiSearch'])->name('search');
        Route::get('/{trainingType}/quick-stats', [TrainingTypeController::class, 'apiQuickStats'])->name('quick-stats');
        Route::get('/categories', [TrainingTypeController::class, 'apiGetCategories'])->name('categories');
        Route::get('/monthly-trends/{months?}', [TrainingTypeController::class, 'apiMonthlyTrends'])->name('monthly-trends');
        Route::get('/cost-analytics/{year?}', [TrainingTypeController::class, 'apiCostAnalytics'])->name('cost-analytics');
    });

    // Training Record Management (EXISTING - NO CHANGES)
    Route::resource('training-records', TrainingRecordController::class);

    // ===== EXISTING ROUTES - Keep all these =====
    Route::get('/training-records/employee/{employee}/certificates', [TrainingRecordController::class, 'getEmployeeCertificates'])
        ->name('training-records.employee-certificates');

    Route::get('/api/training-providers/filter', [TrainingRecordController::class, 'getProvidersForFilters'])
        ->name('api.training-providers.filter');

    Route::get('/training-records/employee/{employee}/edit', [TrainingRecordController::class, 'editEmployee'])
        ->name('training-records.edit-employee');
    Route::post('/training-records/employee/{employee}/update-training', [TrainingRecordController::class, 'updateEmployeeTraining'])
        ->name('training-records.update-employee-training');

    Route::post('/training-records/bulk-action', [TrainingRecordController::class, 'bulkAction'])
        ->name('training-records.bulk-action');
    Route::get('/training-records/{trainingRecord}/certificates', [TrainingRecordController::class, 'getCertificates'])
        ->name('training-records.certificates');
    Route::post('/training-records/{trainingRecord}/mark-completed', [TrainingRecordController::class, 'markCompleted'])
        ->name('training-records.mark-completed');

    Route::post('/training-records/import', [TrainingRecordController::class, 'import'])
        ->name('training-records.import');
    Route::get('/training-records/export/template', [TrainingRecordController::class, 'exportTemplate'])
        ->name('training-records.export-template');
    Route::get('/training-records/export', [TrainingRecordController::class, 'export'])
        ->name('training-records.export');

    Route::get('/training-records/compliance/dashboard', [TrainingRecordController::class, 'complianceDashboard'])
        ->name('training-records.compliance-dashboard');
    Route::get('/training-records/reports/expiring', [TrainingRecordController::class, 'expiringCertificatesReport'])
        ->name('training-records.reports.expiring');
    Route::get('/training-records/reports/expired', [TrainingRecordController::class, 'expiredCertificatesReport'])
        ->name('training-records.reports.expired');
    Route::get('/training-records/reports/department/{department}', [TrainingRecordController::class, 'departmentReport'])
        ->name('training-records.reports.department');

    Route::post('/training-records/reminders/send', [TrainingRecordController::class, 'sendReminders'])
        ->name('training-records.reminders.send');
    Route::get('/training-records/reminders/queue', [TrainingRecordController::class, 'getReminderQueue'])
        ->name('training-records.reminders.queue');

    Route::post('/training-records/{trainingRecord}/renew', [TrainingRecordController::class, 'renewCertificate'])
        ->name('training-records.renew');
    Route::post('/training-records/{trainingRecord}/suspend', [TrainingRecordController::class, 'suspendCertificate'])
        ->name('training-records.suspend');
    Route::post('/training-records/{trainingRecord}/reactivate', [TrainingRecordController::class, 'reactivateCertificate'])
        ->name('training-records.reactivate');

    Route::get('/training-records/analytics/overview', [TrainingRecordController::class, 'analyticsOverview'])
        ->name('training-records.analytics.overview');
    Route::get('/training-records/analytics/trends', [TrainingRecordController::class, 'analyticsTrends'])
        ->name('training-records.analytics.trends');
    Route::get('/training-records/analytics/provider-performance', [TrainingRecordController::class, 'providerPerformanceAnalytics'])
        ->name('training-records.analytics.provider-performance');
    // ===== END EXISTING ROUTES =====

    // Import/Export Management
    Route::get('/import-export', [ImportExportController::class, 'index'])->name('import-export.index');

    // System Templates route (for Ziggy)
    Route::get('/system/templates', function() {
        return Inertia::render('System/Templates');
    })->name('system.templates');

    // System Stats route (for Ziggy)
    Route::get('/system/stats', function() {
        return Inertia::render('System/Stats');
    })->name('system.stats');
});

require __DIR__.'/auth.php';
