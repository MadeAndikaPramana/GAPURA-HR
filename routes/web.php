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
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

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

// =====================================
// DASHBOARD - FIXED & SIMPLIFIED
// =====================================
Route::get('/dashboard', function () {
    try {
        // Base employee and provider stats
        $stats = [
            'total_employees' => \App\Models\Employee::where('status', 'active')->count(),
            'total_training_records' => \App\Models\TrainingRecord::count(),
            'total_training_providers' => \App\Models\TrainingProvider::count(),
        ];

        // Training record stats using simplified status (active/expired only)
        $stats['active_certificates'] = \App\Models\TrainingRecord::where('status', 'active')->count();
        $stats['expired_certificates'] = \App\Models\TrainingRecord::where('status', 'expired')->count();

        // Expiring soon calculation (active records expiring within 30 days)
        $stats['expiring_soon'] = \App\Models\TrainingRecord::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', Carbon::now()->addDays(30))
            ->whereDate('expiry_date', '>', Carbon::now())
            ->count();

        // Department stats (conditional)
        if (Schema::hasTable('departments')) {
            $stats['total_departments'] = \App\Models\Department::count();
        } else {
            $stats['total_departments'] = 0;
        }

        // Certificate stats (if Certificate model exists)
        if (class_exists(\App\Models\Certificate::class) && Schema::hasTable('certificates')) {
            $stats['total_certificates'] = \App\Models\Certificate::count();
            $stats['active_certificates_model'] = \App\Models\Certificate::where('status', 'active')->count();
            $stats['expired_certificates_model'] = \App\Models\Certificate::where('status', 'expired')->count();

            // Certificate expiring soon
            $stats['certificates_expiring_soon'] = \App\Models\Certificate::where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', Carbon::now()->addDays(30))
                ->whereDate('expiry_date', '>', Carbon::now())
                ->count();
        } else {
            $stats['total_certificates'] = 0;
            $stats['active_certificates_model'] = 0;
            $stats['expired_certificates_model'] = 0;
            $stats['certificates_expiring_soon'] = 0;
        }

        // Calculate compliance rate
        $totalRecords = $stats['total_training_records'];
        $stats['compliance_rate'] = $totalRecords > 0
            ? round(($stats['active_certificates'] / $totalRecords) * 100, 2)
            : 100;

        // Recent activities (safe)
        $recentActivities = [];
        try {
            $recentTrainingRecords = \App\Models\TrainingRecord::with(['employee', 'trainingType'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            foreach ($recentTrainingRecords as $record) {
                $recentActivities[] = [
                    'id' => $record->id,
                    'type' => 'training_completed',
                    'title' => ($record->employee->name ?? 'Unknown') . ' completed ' . ($record->trainingType->name ?? 'Unknown'),
                    'description' => 'Certificate ' . $record->certificate_number,
                    'date' => $record->created_at,
                    'status' => $record->status
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch recent activities: ' . $e->getMessage());
        }

        // Expiring certificates alerts
        $expiringCertificates = [];
        try {
            $expiring = \App\Models\TrainingRecord::with(['employee.department', 'trainingType'])
                ->where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', Carbon::now()->addDays(30))
                ->whereDate('expiry_date', '>', Carbon::now())
                ->orderBy('expiry_date')
                ->limit(10)
                ->get();

            foreach ($expiring as $record) {
                $daysUntilExpiry = Carbon::now()->diffInDays(Carbon::parse($record->expiry_date), false);
                $expiringCertificates[] = [
                    'id' => $record->id,
                    'employee_name' => $record->employee->name ?? 'Unknown',
                    'department' => $record->employee->department->name ?? 'N/A',
                    'training_type' => $record->trainingType->name ?? 'Unknown',
                    'certificate_number' => $record->certificate_number,
                    'expiry_date' => $record->expiry_date,
                    'days_until_expiry' => $daysUntilExpiry,
                    'status' => $record->status
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch expiring certificates: ' . $e->getMessage());
        }

        // Compliance by department (safe)
        $complianceByDepartment = [];
        try {
            if (Schema::hasTable('departments')) {
                $departments = \App\Models\Department::withCount(['employees' => function ($query) {
                    $query->where('status', 'active');
                }])->get();

                foreach ($departments as $dept) {
                    $totalRecords = \App\Models\TrainingRecord::whereHas('employee', function ($query) use ($dept) {
                        $query->where('department_id', $dept->id)->where('status', 'active');
                    })->count();

                    $activeRecords = \App\Models\TrainingRecord::where('status', 'active')
                        ->whereHas('employee', function ($query) use ($dept) {
                            $query->where('department_id', $dept->id)->where('status', 'active');
                        })->count();

                    $expiredRecords = \App\Models\TrainingRecord::where('status', 'expired')
                        ->whereHas('employee', function ($query) use ($dept) {
                            $query->where('department_id', $dept->id)->where('status', 'active');
                        })->count();

                    $complianceByDepartment[] = [
                        'department' => $dept->name,
                        'total_employees' => $dept->employees_count,
                        'total_records' => $totalRecords,
                        'active_records' => $activeRecords,
                        'expired_records' => $expiredRecords,
                        'compliance_rate' => $totalRecords > 0 ? round(($activeRecords / $totalRecords) * 100, 2) : 0
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch compliance by department: ' . $e->getMessage());
        }

        return Inertia::render('Dashboard', [
            'auth' => [
                'user' => auth()->user()
            ],
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'expiringCertificates' => $expiringCertificates,
            'complianceByDepartment' => $complianceByDepartment,
        ]);

    } catch (\Exception $e) {
        Log::error('Dashboard error: ' . $e->getMessage());

        // Fallback stats
        $fallbackStats = [
            'total_employees' => 0,
            'total_departments' => 0,
            'total_training_records' => 0,
            'total_training_providers' => 0,
            'active_certificates' => 0,
            'expired_certificates' => 0,
            'expiring_soon' => 0,
            'compliance_rate' => 0,
            'total_certificates' => 0
        ];

        return Inertia::render('Dashboard', [
            'auth' => [
                'user' => auth()->user()
            ],
            'stats' => $fallbackStats,
            'recentActivities' => [],
            'expiringCertificates' => [],
            'complianceByDepartment' => [],
            'error' => 'Dashboard data could not be loaded. Please check system configuration.'
        ]);
    }
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
        Route::get('/export', [EmployeeController::class, 'export'])->name('export');
        Route::post('/bulk-action', [EmployeeController::class, 'bulkAction'])->name('bulk-action');
    });

    // =====================================
    // DEPARTMENT MANAGEMENT (Conditional)
    // =====================================
    if (Schema::hasTable('departments')) {
        Route::resource('departments', DepartmentController::class);
        Route::prefix('departments')->name('departments.')->group(function () {
            Route::get('/{department}/employees', [DepartmentController::class, 'getEmployees'])->name('employees');
            Route::get('/{department}/compliance-report', [DepartmentController::class, 'complianceReport'])->name('compliance-report');
        });
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

        // Statistics and reports
        if (method_exists(TrainingProviderController::class, 'getStatistics')) {
            Route::get('/{trainingProvider}/statistics', [TrainingProviderController::class, 'getStatistics'])->name('statistics');
            Route::post('/{trainingProvider}/update-rating', [TrainingProviderController::class, 'updateRating'])->name('update-rating');
        }

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

        // Statistics and analytics (conditional)
        if (method_exists(TrainingTypeController::class, 'getStatistics')) {
            Route::get('/{trainingType}/statistics', [TrainingTypeController::class, 'getStatistics'])->name('statistics');
        }

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
    // TRAINING RECORD MANAGEMENT - SIMPLIFIED
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
        Route::post('/{trainingRecord}/update-status', [TrainingRecordController::class, 'updateStatus'])->name('update-status');

        // Simplified certificate lifecycle operations
        Route::post('/{trainingRecord}/renew', [TrainingRecordController::class, 'renewCertificate'])->name('renew');
        Route::post('/{trainingRecord}/expire', [TrainingRecordController::class, 'expireCertificate'])->name('expire');
        Route::post('/{trainingRecord}/reactivate', [TrainingRecordController::class, 'reactivateCertificate'])->name('reactivate');
    });

    // Import/Export operations
    Route::prefix('training-records')->name('training-records.')->group(function () {
        Route::post('/import', [TrainingRecordController::class, 'import'])->name('import');
        Route::get('/export/template', [TrainingRecordController::class, 'exportTemplate'])->name('export-template');
        Route::get('/export', [TrainingRecordController::class, 'export'])->name('export');
        Route::get('/export/excel', [TrainingRecordController::class, 'exportExcel'])->name('export-excel');
    });

    // Compliance and reporting - simplified
    Route::prefix('training-records')->name('training-records.')->group(function () {
        Route::get('/compliance/dashboard', [TrainingRecordController::class, 'complianceDashboard'])->name('compliance-dashboard');
        Route::get('/reports/expiring', [TrainingRecordController::class, 'expiringCertificatesReport'])->name('reports.expiring');
        Route::get('/reports/expired', [TrainingRecordController::class, 'expiredCertificatesReport'])->name('reports.expired');
        Route::get('/reports/active', [TrainingRecordController::class, 'activeCertificatesReport'])->name('reports.active');

        // Department reports (conditional)
        if (Schema::hasTable('departments')) {
            Route::get('/reports/department/{department}', [TrainingRecordController::class, 'departmentReport'])->name('reports.department');
            Route::get('/reports/department/{department}/export', [TrainingRecordController::class, 'exportDepartmentReport'])->name('reports.department.export');
        }
    });

    // Notifications and reminders
    Route::prefix('training-records')->name('training-records.')->group(function () {
        Route::get('/reminders/queue', [TrainingRecordController::class, 'getReminderQueue'])->name('reminders.queue');
        Route::post('/reminders/send', [TrainingRecordController::class, 'sendReminders'])->name('reminders.send');
        Route::get('/notifications/expiry-alerts', [TrainingRecordController::class, 'getExpiryAlerts'])->name('notifications.expiry-alerts');
    });

    // Analytics (conditional)
    if (method_exists(TrainingRecordController::class, 'analyticsOverview')) {
        Route::prefix('training-records')->name('training-records.')->group(function () {
            Route::get('/analytics/overview', [TrainingRecordController::class, 'analyticsOverview'])->name('analytics.overview');
            Route::get('/analytics/trends', [TrainingRecordController::class, 'analyticsTrends'])->name('analytics.trends');
            Route::get('/analytics/provider-performance', [TrainingRecordController::class, 'providerPerformanceAnalytics'])->name('analytics.provider-performance');
        });
    }

    // =====================================
    // CERTIFICATE MANAGEMENT - SIMPLIFIED & OPTIMIZED
    // =====================================
    if (class_exists(\App\Http\Controllers\CertificateController::class)) {
        Route::prefix('certificates')->name('certificates.')->group(function () {
            // Basic CRUD operations
            Route::get('/', [CertificateController::class, 'index'])->name('index');
            Route::get('/create', [CertificateController::class, 'create'])->name('create');
            Route::post('/', [CertificateController::class, 'store'])->name('store');
            Route::get('/{certificate}', [CertificateController::class, 'show'])->name('show');
            Route::get('/{certificate}/edit', [CertificateController::class, 'edit'])->name('edit');
            Route::put('/{certificate}', [CertificateController::class, 'update'])->name('update');
            Route::delete('/{certificate}', [CertificateController::class, 'destroy'])->name('destroy');

            // Simplified certificate operations
            Route::post('/bulk-action', [CertificateController::class, 'bulkAction'])->name('bulk-action');
            Route::post('/{certificate}/update-status', [CertificateController::class, 'updateStatus'])->name('update-status');

            // File operations
            Route::get('/{certificate}/download', [CertificateController::class, 'download'])->name('download');
            Route::post('/{certificate}/upload', [CertificateController::class, 'uploadFile'])->name('upload-file');

            // Export functionality
            Route::get('/export/excel', [CertificateController::class, 'exportExcel'])->name('export-excel');
            Route::get('/export/csv', [CertificateController::class, 'exportCsv'])->name('export-csv');

            // Reports - simplified
            Route::get('/reports/compliance', [CertificateController::class, 'complianceReport'])->name('compliance-report');
            Route::get('/reports/expiring', [CertificateController::class, 'expiringReport'])->name('expiring-report');
            Route::get('/reports/expired', [CertificateController::class, 'expiredReport'])->name('expired-report');

            // Employee-specific certificates
            Route::get('/employee/{employee}/certificates', [CertificateController::class, 'employeeCertificates'])->name('employee-certificates');

            // Training type specific certificates
            Route::get('/training-type/{trainingType}/certificates', [CertificateController::class, 'trainingTypeCertificates'])->name('training-type-certificates');

            // Department specific certificates (conditional)
            if (Schema::hasTable('departments')) {
                Route::get('/department/{department}/certificates', [CertificateController::class, 'departmentCertificates'])->name('department-certificates');
            }
        });
    }

    // =====================================
    // API ROUTES - SIMPLIFIED
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

        // Certificate API - Simplified & Safe
        if (class_exists(\App\Http\Controllers\CertificateController::class)) {
            Route::prefix('certificates')->name('certificates.')->group(function () {
                Route::get('/search', [CertificateController::class, 'apiSearch'])->name('search');
                Route::get('/quick-stats', [CertificateController::class, 'apiQuickStats'])->name('quick-stats');
                Route::get('/expiry-alerts', [CertificateController::class, 'apiExpiryAlerts'])->name('expiry-alerts');
                Route::get('/status-summary', [CertificateController::class, 'apiStatusSummary'])->name('status-summary');
            });
        }

        // Employee API
        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/search', [EmployeeController::class, 'apiSearch'])->name('search');
            Route::get('/{employee}/training-summary', [EmployeeController::class, 'apiTrainingSummary'])->name('training-summary');
        });

        // Dashboard API
        Route::get('/dashboard/stats', function() {
            try {
                return response()->json([
                    'total_employees' => \App\Models\Employee::where('status', 'active')->count(),
                    'active_certificates' => \App\Models\TrainingRecord::where('status', 'active')->count(),
                    'expired_certificates' => \App\Models\TrainingRecord::where('status', 'expired')->count(),
                    'expiring_soon' => \App\Models\TrainingRecord::where('status', 'active')
                        ->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '<=', Carbon::now()->addDays(30))
                        ->count()
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Could not fetch stats'], 500);
            }
        })->name('api.dashboard.stats');
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
        try {
            $stats = [
                'database' => [
                    'employees' => \App\Models\Employee::count(),
                    'training_records' => \App\Models\TrainingRecord::count(),
                    'training_types' => \App\Models\TrainingType::count(),
                    'training_providers' => \App\Models\TrainingProvider::count(),
                ],
                'status_distribution' => [
                    'active' => \App\Models\TrainingRecord::where('status', 'active')->count(),
                    'expired' => \App\Models\TrainingRecord::where('status', 'expired')->count(),
                ],
                'system_health' => 'operational'
            ];

            return Inertia::render('System/Stats', ['stats' => $stats]);
        } catch (\Exception $e) {
            return Inertia::render('System/Stats', [
                'stats' => ['error' => 'Could not load system statistics'],
                'error' => $e->getMessage()
            ]);
        }
    })->name('system.stats');
});

// =====================================
// PUBLIC ROUTES - SIMPLIFIED
// =====================================
// Public certificate verification (if Certificate controller exists)
if (class_exists(\App\Http\Controllers\CertificateController::class)) {
    Route::prefix('verify')->name('certificates.')->group(function () {
        Route::post('/', [CertificateController::class, 'verify'])->name('verify');
        Route::get('/{verificationCode}', [CertificateController::class, 'verifyPublic'])->name('verify-public');
    });
}

require __DIR__.'/auth.php';
