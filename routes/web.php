<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\TrainingCategoryController;
use App\Http\Controllers\TrainingProviderController;
use App\Http\Controllers\TrainingRecordController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AnalyticsController;
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

// Public certificate verification route (no auth required)
Route::get('/verify/{verificationCode}', [CertificateController::class, 'verify'])
    ->name('certificates.verify');

// Protected routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Department Management
    Route::resource('departments', DepartmentController::class);
    Route::get('/departments/{department}/statistics', [DepartmentController::class, 'getStatistics'])
        ->name('departments.statistics');

    // Employee Management
    Route::resource('employees', EmployeeController::class);
    Route::get('/employees/{employee}/training-summary', [EmployeeController::class, 'getTrainingSummary'])
        ->name('employees.training-summary');
    Route::get('/employees/{employee}/dashboard-data', [EmployeeController::class, 'getDashboardData'])
        ->name('employees.dashboard-data');
    Route::post('/employees/{employee}/assign-training', [EmployeeController::class, 'assignTraining'])
        ->name('employees.assign-training');
    Route::get('/employees/compliance/missing-mandatory', [EmployeeController::class, 'missingMandatoryTraining'])
        ->name('employees.missing-mandatory');

    // Training Category Management
    Route::resource('training-categories', TrainingCategoryController::class);
    Route::get('/training-categories/{category}/statistics', [TrainingCategoryController::class, 'getStatistics'])
        ->name('training-categories.statistics');
    Route::post('/training-categories/reorder', [TrainingCategoryController::class, 'reorder'])
        ->name('training-categories.reorder');

    // Training Provider Management
    Route::resource('training-providers', TrainingProviderController::class);
    Route::get('/training-providers/{provider}/performance', [TrainingProviderController::class, 'getPerformanceMetrics'])
        ->name('training-providers.performance');
    Route::post('/training-providers/{provider}/update-rating', [TrainingProviderController::class, 'updateRating'])
        ->name('training-providers.update-rating');

    // Training Type Management
    Route::resource('training-types', TrainingTypeController::class);
    Route::get('/training-types/{trainingType}/statistics', [TrainingTypeController::class, 'getStatistics'])
        ->name('training-types.statistics');
    Route::post('/training-types/bulk-action', [TrainingTypeController::class, 'bulkAction'])
        ->name('training-types.bulk-action');

    // Training Record Management
    Route::resource('training-records', TrainingRecordController::class);
    Route::post('/training-records/bulk-action', [TrainingRecordController::class, 'bulkAction'])
        ->name('training-records.bulk-action');
    Route::get('/training-records/{trainingRecord}/certificates', [TrainingRecordController::class, 'getCertificates'])
        ->name('training-records.certificates');
    Route::post('/training-records/{trainingRecord}/mark-completed', [TrainingRecordController::class, 'markCompleted'])
        ->name('training-records.mark-completed');
    Route::post('/training-records/{trainingRecord}/create-renewal', [TrainingRecordController::class, 'createRenewal'])
        ->name('training-records.create-renewal');

    // Certificate Management
    Route::resource('certificates', CertificateController::class);
    Route::post('/certificates/bulk-action', [CertificateController::class, 'bulkAction'])
        ->name('certificates.bulk-action');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])
        ->name('certificates.download');
    Route::get('/certificates/analytics/dashboard', [CertificateController::class, 'analytics'])
        ->name('certificates.analytics');
    Route::post('/certificates/send-renewal-reminders', [CertificateController::class, 'sendRenewalReminders'])
        ->name('certificates.send-renewal-reminders');
    Route::get('/certificates/export/excel', [CertificateController::class, 'exportCertificates'])
        ->name('certificates.export.excel');

    // Notification Management
    Route::resource('notifications', NotificationController::class)->only(['index', 'show', 'destroy']);
    Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-read');
    Route::get('/notifications/unread/count', [NotificationController::class, 'getUnreadCount'])
        ->name('notifications.unread-count');

    // Import/Export Operations
    Route::prefix('import-export')->name('import-export.')->group(function () {
        Route::get('/', [ImportExportController::class, 'index'])->name('index');

        // Import routes
        Route::get('/import/employees', [ImportExportController::class, 'showEmployeeImport'])->name('employees.import');
        Route::post('/import/employees', [ImportExportController::class, 'importEmployees'])->name('employees.import.process');
        Route::get('/import/training-records', [ImportExportController::class, 'showTrainingRecordsImport'])->name('training-records.import');
        Route::post('/import/training-records', [ImportExportController::class, 'importTrainingRecords'])->name('training-records.import.process');
        Route::get('/import/certificates', [ImportExportController::class, 'showCertificatesImport'])->name('certificates.import');
        Route::post('/import/certificates', [ImportExportController::class, 'importCertificates'])->name('certificates.import.process');

        // Export routes
        Route::get('/export/employees', [ImportExportController::class, 'exportEmployees'])->name('employees.export');
        Route::get('/export/training-records', [ImportExportController::class, 'exportTrainingRecords'])->name('training-records.export');
        Route::get('/export/certificates', [ImportExportController::class, 'exportCertificates'])->name('certificates.export');
        Route::get('/export/compliance-report', [ImportExportController::class, 'exportComplianceReport'])->name('compliance-report.export');

        // Template downloads
        Route::get('/templates/employees', [ImportExportController::class, 'downloadEmployeeTemplate'])->name('templates.employees');
        Route::get('/templates/training-records', [ImportExportController::class, 'downloadTrainingRecordsTemplate'])->name('templates.training-records');
        Route::get('/templates/certificates', [ImportExportController::class, 'downloadCertificatesTemplate'])->name('templates.certificates');
    });

    // Reporting
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/compliance', [ReportController::class, 'compliance'])->name('compliance');
        Route::get('/training-summary', [ReportController::class, 'trainingSummary'])->name('training-summary');
        Route::get('/employee-progress', [ReportController::class, 'employeeProgress'])->name('employee-progress');
        Route::get('/cost-analysis', [ReportController::class, 'costAnalysis'])->name('cost-analysis');
        Route::get('/provider-performance', [ReportController::class, 'providerPerformance'])->name('provider-performance');
        Route::get('/expiry-forecast', [ReportController::class, 'expiryForecast'])->name('expiry-forecast');
        Route::get('/department-breakdown', [ReportController::class, 'departmentBreakdown'])->name('department-breakdown');

        // Custom report builder
        Route::get('/builder', [ReportController::class, 'builder'])->name('builder');
        Route::post('/builder/generate', [ReportController::class, 'generateCustomReport'])->name('builder.generate');

        // Scheduled reports
        Route::get('/scheduled', [ReportController::class, 'scheduled'])->name('scheduled');
        Route::post('/scheduled', [ReportController::class, 'createScheduledReport'])->name('scheduled.create');
        Route::delete('/scheduled/{report}', [ReportController::class, 'deleteScheduledReport'])->name('scheduled.delete');
    });

    // Analytics Dashboard
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/dashboard-data', [AnalyticsController::class, 'getDashboardData'])->name('dashboard-data');
        Route::get('/compliance-trends', [AnalyticsController::class, 'complianceTrends'])->name('compliance-trends');
        Route::get('/training-effectiveness', [AnalyticsController::class, 'trainingEffectiveness'])->name('training-effectiveness');
        Route::get('/cost-analysis', [AnalyticsController::class, 'costAnalysis'])->name('cost-analysis');
        Route::get('/predictive-analytics', [AnalyticsController::class, 'predictiveAnalytics'])->name('predictive-analytics');
        Route::get('/department-comparison', [AnalyticsController::class, 'departmentComparison'])->name('department-comparison');
        Route::get('/provider-performance', [AnalyticsController::class, 'providerPerformance'])->name('provider-performance');
    });

    // Quick Actions (for common tasks)
    Route::prefix('quick-actions')->name('quick-actions.')->group(function () {
        Route::get('/expiring-certificates', [CertificateController::class, 'expiringCertificates'])->name('expiring-certificates');
        Route::get('/compliance-issues', [EmployeeController::class, 'complianceIssues'])->name('compliance-issues');
        Route::get('/pending-training', [TrainingRecordController::class, 'pendingTraining'])->name('pending-training');
        Route::get('/overdue-renewals', [TrainingRecordController::class, 'overdueRenewals'])->name('overdue-renewals');
    });

    // System Settings (Admin only)
    Route::middleware(['can:manage-system'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\SettingsController::class, 'index'])->name('index');
        Route::get('/notification-templates', [App\Http\Controllers\SettingsController::class, 'notificationTemplates'])->name('notification-templates');
        Route::post('/notification-templates', [App\Http\Controllers\SettingsController::class, 'updateNotificationTemplate'])->name('notification-templates.update');
        Route::get('/system-maintenance', [App\Http\Controllers\SettingsController::class, 'systemMaintenance'])->name('system-maintenance');
        Route::post('/system-maintenance/run', [App\Http\Controllers\SettingsController::class, 'runMaintenance'])->name('system-maintenance.run');
        Route::get('/audit-logs', [App\Http\Controllers\SettingsController::class, 'auditLogs'])->name('audit-logs');
        Route::get('/backup-restore', [App\Http\Controllers\SettingsController::class, 'backupRestore'])->name('backup-restore');
    });
});

// API Routes for mobile app or external integrations
Route::prefix('api/v1')->middleware(['auth:sanctum'])->name('api.')->group(function () {
    // Employee API
    Route::get('/employees/{employee}/certificates', [App\Http\Controllers\Api\EmployeeApiController::class, 'getCertificates']);
    Route::get('/employees/{employee}/training-schedule', [App\Http\Controllers\Api\EmployeeApiController::class, 'getTrainingSchedule']);
    Route::get('/employees/{employee}/compliance-status', [App\Http\Controllers\Api\EmployeeApiController::class, 'getComplianceStatus']);

    // Training API
    Route::get('/training-types', [App\Http\Controllers\Api\TrainingApiController::class, 'getTrainingTypes']);
    Route::get('/training-schedule', [App\Http\Controllers\Api\TrainingApiController::class, 'getTrainingSchedule']);

    // Certificate Verification API
    Route::get('/certificates/{verificationCode}/verify', [App\Http\Controllers\Api\CertificateApiController::class, 'verify']);
    Route::get('/certificates/{certificate}/qr-data', [App\Http\Controllers\Api\CertificateApiController::class, 'getQrData']);

    // Notification API
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationApiController::class, 'index']);
    Route::post('/notifications/{notification}/mark-read', [App\Http\Controllers\Api\NotificationApiController::class, 'markAsRead']);

    // Analytics API
    Route::get('/analytics/dashboard', [App\Http\Controllers\Api\AnalyticsApiController::class, 'getDashboard']);
    Route::get('/analytics/compliance', [App\Http\Controllers\Api\AnalyticsApiController::class, 'getCompliance']);
});

// Webhook routes for external integrations
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/training-completion', [App\Http\Controllers\WebhookController::class, 'trainingCompletion'])->name('training-completion');
    Route::post('/certificate-issued', [App\Http\Controllers\WebhookController::class, 'certificateIssued'])->name('certificate-issued');
    Route::post('/employee-update', [App\Http\Controllers\WebhookController::class, 'employeeUpdate'])->name('employee-update');
});

// System Routes (Super Admin only)
Route::middleware(['auth', 'verified'])->prefix('system')->name('system.')->group(function () {
    // Templates & Import/Export
    Route::get('/templates', function () {
        return inertia('System/Templates');
    })->name('templates');

    Route::get('/templates/employees', function () {
        // Download employee template logic
        return response()->download(storage_path('app/templates/employees_template.xlsx'));
    })->name('templates.employees');

    Route::get('/templates/training-records', function () {
        // Download training records template logic
        return response()->download(storage_path('app/templates/training_records_template.xlsx'));
    })->name('templates.training-records');

    Route::get('/templates/training-types', function () {
        // Download training types template logic
        return response()->download(storage_path('app/templates/training_types_template.xlsx'));
    })->name('templates.training-types');

    // System Statistics
    Route::get('/stats', function () {
        return inertia('System/Stats');
    })->name('stats');
});

require __DIR__.'/auth.php';
