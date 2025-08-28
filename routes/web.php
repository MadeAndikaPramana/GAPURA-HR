<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\TrainingRecordController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\TrainingProviderController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ImportExportController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
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
    // Dashboard dengan error handling untuk departments yang dihapus
    $stats = [
        'total_employees' => \App\Models\Employee::where('status', 'active')->count(),
        'total_training_records' => \App\Models\TrainingRecord::count(),
        'total_training_providers' => \App\Models\TrainingProvider::count(),
        'active_certificates' => \App\Models\TrainingRecord::where('compliance_status', 'compliant')->count(),
        'expiring_soon' => \App\Models\TrainingRecord::where('compliance_status', 'expiring_soon')->count(),
        'expired_certificates' => \App\Models\TrainingRecord::where('compliance_status', 'expired')->count(),
    ];

    // Only include departments if table exists
    if (Schema::hasTable('departments')) {
        $stats['total_departments'] = \App\Models\Department::count();
    } else {
        $stats['total_departments'] = 0;
    }

    // Add certificate stats if table exists
    if (Schema::hasTable('certificates')) {
        $stats['total_certificates'] = \App\Models\Certificate::count();
        $stats['active_certificates_new'] = \App\Models\Certificate::active()->count();
        $stats['verified_certificates'] = \App\Models\Certificate::where('verification_status', 'verified')->count();
    }

    // Calculate compliance rate
    $totalRecords = $stats['total_training_records'];
    $stats['compliance_rate'] = $totalRecords > 0
        ? round(($stats['active_certificates'] / $totalRecords) * 100, 2)
        : 100;

    return Inertia::render('Dashboard', [
        'stats' => $stats,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// =====================================
// AUTHENTICATED ROUTES
// =====================================
Route::middleware('auth')->group(function () {
    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // =====================================
    // EMPLOYEE MANAGEMENT
    // =====================================
    Route::resource('employees', EmployeeController::class);
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::post('/import', [EmployeeController::class, 'import'])->name('import');
        Route::get('/export/template', [EmployeeController::class, 'exportTemplate'])->name('export-template');
        Route::post('/bulk-action', [EmployeeController::class, 'bulkAction'])->name('bulk-action');
    });

    // =====================================
    // DEPARTMENT MANAGEMENT (Conditional)
    // =====================================
    if (Schema::hasTable('departments')) {
        Route::resource('departments', DepartmentController::class);
    }

    // =====================================
    // TRAINING PROVIDER MANAGEMENT
    // =====================================
    Route::prefix('training-providers')->name('training-providers.')->group(function () {
        // Basic CRUD operations
        Route::get('/', [TrainingProviderController::class, 'index'])->name('index');
        Route::get('/create', [TrainingProviderController::class, 'create'])->name('create');
        Route::post('/', [TrainingProviderController::class, 'store'])->name('store');
        Route::get('/{trainingProvider}', [TrainingProviderController::class, 'show'])->name('show');
        Route::get('/{trainingProvider}/edit', [TrainingProviderController::class, 'edit'])->name('edit');
        Route::put('/{trainingProvider}', [TrainingProviderController::class, 'update'])->name('update');
        Route::delete('/{trainingProvider}', [TrainingProviderController::class, 'destroy'])->name('destroy');

        // Provider operations
        Route::post('/bulk-action', [TrainingProviderController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/{trainingProvider}/toggle-status', [TrainingProviderController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{trainingProvider}/statistics', [TrainingProviderController::class, 'getStatistics'])->name('statistics');
        Route::post('/{trainingProvider}/update-rating', [TrainingProviderController::class, 'updateRating'])->name('update-rating');

        // Export operations
        Route::get('/export/excel', [TrainingProviderController::class, 'exportExcel'])->name('export-excel');
        Route::get('/export/pdf', [TrainingProviderController::class, 'exportPdf'])->name('export-pdf');
    });

    // =====================================
    // TRAINING TYPE MANAGEMENT
    // =====================================
    Route::prefix('training-types')->name('training-types.')->group(function () {
        // Basic CRUD
        Route::get('/', [TrainingTypeController::class, 'index'])->name('index');
        Route::get('/create', [TrainingTypeController::class, 'create'])->name('create');
        Route::post('/', [TrainingTypeController::class, 'store'])->name('store');
        Route::get('/{trainingType}', [TrainingTypeController::class, 'show'])->name('show');
        Route::get('/{trainingType}/edit', [TrainingTypeController::class, 'edit'])->name('edit');
        Route::put('/{trainingType}', [TrainingTypeController::class, 'update'])->name('update');
        Route::delete('/{trainingType}', [TrainingTypeController::class, 'destroy'])->name('destroy');

        // Status management
        Route::post('/{trainingType}/toggle-status', [TrainingTypeController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/bulk-action', [TrainingTypeController::class, 'bulkAction'])->name('bulk-action');

        // Statistics and analytics
        Route::get('/{trainingType}/statistics', [TrainingTypeController::class, 'getStatistics'])->name('statistics');

        // Phase 3 features (conditional)
        if (method_exists(TrainingTypeController::class, 'analyticsDashboard')) {
            Route::get('/analytics/dashboard', [TrainingTypeController::class, 'analyticsDashboard'])->name('analytics-dashboard');
            Route::get('/compliance/report', [TrainingTypeController::class, 'complianceReport'])->name('compliance-report');
            Route::get('/{trainingType}/analytics', [TrainingTypeController::class, 'analytics'])->name('analytics');
            Route::get('/export/compliance-excel', [TrainingTypeController::class, 'exportComplianceExcel'])->name('export-compliance');
            Route::post('/{trainingType}/update-priority', [TrainingTypeController::class, 'updatePriority'])->name('update-priority');
        }
    });

    // =====================================
    // TRAINING RECORD MANAGEMENT
    // =====================================
    Route::resource('training-records', TrainingRecordController::class);

    // Employee-specific training records
    Route::prefix('training-records')->name('training-records.')->group(function () {
        Route::get('/employee/{employee}/certificates', [TrainingRecordController::class, 'getEmployeeCertificates'])->name('employee-certificates');
        Route::get('/employee/{employee}/edit', [TrainingRecordController::class, 'editEmployee'])->name('edit-employee');
        Route::post('/employee/{employee}/update-training', [TrainingRecordController::class, 'updateEmployeeTraining'])->name('update-employee-training');
    });

    // Bulk operations and utilities
    Route::prefix('training-records')->name('training-records.')->group(function () {
        Route::post('/bulk-action', [TrainingRecordController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/{trainingRecord}/mark-completed', [TrainingRecordController::class, 'markCompleted'])->name('mark-completed');
        Route::post('/{trainingRecord}/renew', [TrainingRecordController::class, 'renewCertificate'])->name('renew');
        Route::post('/{trainingRecord}/suspend', [TrainingRecordController::class, 'suspendCertificate'])->name('suspend');
        Route::post('/{trainingRecord}/reactivate', [TrainingRecordController::class, 'reactivateCertificate'])->name('reactivate');
    });

    // Import/Export operations
    Route::prefix('training-records')->name('training-records.')->group(function () {
        Route::post('/import', [TrainingRecordController::class, 'import'])->name('import');
        Route::get('/export/template', [TrainingRecordController::class, 'exportTemplate'])->name('export-template');
        Route::get('/export', [TrainingRecordController::class, 'export'])->name('export');
    });

    // Compliance and reporting
    Route::prefix('training-records')->name('training-records.')->group(function () {
        Route::get('/compliance/dashboard', [TrainingRecordController::class, 'complianceDashboard'])->name('compliance-dashboard');
        Route::get('/reports/expiring', [TrainingRecordController::class, 'expiringCertificatesReport'])->name('reports.expiring');
        Route::get('/reports/expired', [TrainingRecordController::class, 'expiredCertificatesReport'])->name('reports.expired');

        // Department reports (conditional)
        if (Schema::hasTable('departments')) {
            Route::get('/reports/department/{department}', [TrainingRecordController::class, 'departmentReport'])->name('reports.department');
        }
    });

    // Reminders and notifications
    Route::prefix('training-records')->name('training-records.')->group(function () {
        Route::post('/reminders/send', [TrainingRecordController::class, 'sendReminders'])->name('reminders.send');
        Route::get('/reminders/queue', [TrainingRecordController::class, 'getReminderQueue'])->name('reminders.queue');
    });

    // Analytics
    Route::prefix('training-records')->name('training-records.')->group(function () {
        Route::get('/analytics/overview', [TrainingRecordController::class, 'analyticsOverview'])->name('analytics.overview');
        Route::get('/analytics/trends', [TrainingRecordController::class, 'analyticsTrends'])->name('analytics.trends');
        Route::get('/analytics/provider-performance', [TrainingRecordController::class, 'providerPerformanceAnalytics'])->name('analytics.provider-performance');
    });

    // =====================================
    // CERTIFICATE MANAGEMENT - OPTIMIZED
    // =====================================
    Route::prefix('certificates')->name('certificates.')->group(function () {
        // Basic CRUD operations
        Route::get('/', [CertificateController::class, 'index'])->name('index');
        Route::get('/create', [CertificateController::class, 'create'])->name('create');
        Route::post('/', [CertificateController::class, 'store'])->name('store');
        Route::get('/{certificate}', [CertificateController::class, 'show'])->name('show');
        Route::get('/{certificate}/edit', [CertificateController::class, 'edit'])->name('edit');
        Route::put('/{certificate}', [CertificateController::class, 'update'])->name('update');
        Route::delete('/{certificate}', [CertificateController::class, 'destroy'])->name('destroy');

        // Bulk operations
        Route::post('/bulk-action', [CertificateController::class, 'bulkAction'])->name('bulk-action');

        // Certificate operations
        Route::post('/{certificate}/generate-pdf', [CertificateController::class, 'generatePDF'])->name('generate-pdf');
        Route::post('/{certificate}/revoke', [CertificateController::class, 'revoke'])->name('revoke');
        Route::post('/{certificate}/renew', [CertificateController::class, 'createRenewal'])->name('renew');

        // Export functionality
        Route::get('/export/excel', [CertificateController::class, 'exportExcel'])->name('export-excel');
        Route::get('/export/pdf', [CertificateController::class, 'exportPDF'])->name('export-pdf');

        // Analytics and reports
        Route::get('/reports/compliance', [CertificateController::class, 'complianceReport'])->name('compliance-report');
        Route::get('/reports/expiring', [CertificateController::class, 'expiringReport'])->name('expiring-report');

        // Employee-specific certificates
        Route::get('/employee/{employee}/certificates', [CertificateController::class, 'employeeCertificates'])->name('employee-certificates');

        // Training type specific certificates
        Route::get('/training-type/{trainingType}/certificates', [CertificateController::class, 'trainingTypeCertificates'])->name('training-type-certificates');

        // Department specific certificates
        Route::get('/department/{department}/certificates', [CertificateController::class, 'departmentCertificates'])->name('department-certificates');
    });

    // =====================================
    // API ROUTES (for AJAX/JSON requests)
    // =====================================
    Route::prefix('api')->name('api.')->group(function () {
        // Training provider API
        Route::get('/training-providers/filter', [TrainingRecordController::class, 'getProvidersForFilters'])->name('training-providers.filter');

        // Training types API
        Route::prefix('training-types')->name('training-types.')->group(function () {
            Route::get('/search', [TrainingTypeController::class, 'apiSearch'])->name('search');
            Route::get('/categories', [TrainingTypeController::class, 'apiGetCategories'])->name('categories');

            // Phase 3 API (conditional)
            if (method_exists(TrainingTypeController::class, 'apiQuickStats')) {
                Route::get('/{trainingType}/quick-stats', [TrainingTypeController::class, 'apiQuickStats'])->name('quick-stats');
                Route::get('/monthly-trends/{months?}', [TrainingTypeController::class, 'apiMonthlyTrends'])->name('monthly-trends');
                Route::get('/cost-analytics/{year?}', [TrainingTypeController::class, 'apiCostAnalytics'])->name('cost-analytics');
            }
        });

        // Certificate API - Simplified
        Route::prefix('certificates')->name('certificates.')->group(function () {
            Route::get('/search', [CertificateController::class, 'apiSearch'])->name('search');
            Route::get('/quick-stats', [CertificateController::class, 'apiQuickStats'])->name('quick-stats');
            Route::get('/expiry-alerts', [CertificateController::class, 'apiExpiryAlerts'])->name('expiry-alerts');
        });
    });

    // =====================================
    // IMPORT/EXPORT & SYSTEM MANAGEMENT
    // =====================================
    Route::get('/import-export', [ImportExportController::class, 'index'])->name('import-export.index');

    // System routes
    Route::get('/system/templates', function() {
        return Inertia::render('System/Templates');
    })->name('system.templates');

    Route::get('/system/stats', function() {
        return Inertia::render('System/Stats');
    })->name('system.stats');
});

// =====================================
// PUBLIC ROUTES (No Authentication) - OPTIMIZED
// =====================================
// Public certificate verification - Simplified
Route::prefix('verify')->name('certificates.')->group(function () {
    Route::post('/', [CertificateController::class, 'verify'])->name('verify');
    Route::get('/{verificationCode}', [CertificateController::class, 'verifyPublic'])->name('verify-public');
});

require __DIR__.'/auth.php';
