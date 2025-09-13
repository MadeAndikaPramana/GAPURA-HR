<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\CertificateType;
use App\Models\EmployeeCertificate;

class PerformanceOptimizationService
{
    /**
     * Cache duration for different data types (in seconds)
     */
    const CACHE_DURATIONS = [
        'statistics' => 300,      // 5 minutes
        'dashboard_data' => 600,  // 10 minutes
        'employee_list' => 180,   // 3 minutes
        'certificate_counts' => 240, // 4 minutes
        'department_stats' => 600,   // 10 minutes
    ];

    /**
     * Get cached employee statistics with optimized queries
     */
    public function getEmployeeStatistics()
    {
        return Cache::remember('employee_statistics', self::CACHE_DURATIONS['statistics'], function () {
            return DB::transaction(function () {
                // Single optimized query to get all statistics
                $stats = DB::table('employees as e')
                    ->leftJoin('departments as d', 'e.department_id', '=', 'd.id')
                    ->leftJoin('employee_certificates as ec', 'e.id', '=', 'ec.employee_id')
                    ->select([
                        DB::raw('COUNT(DISTINCT e.id) as total_employees'),
                        DB::raw('COUNT(DISTINCT CASE WHEN e.status = "active" THEN e.id END) as active_employees'),
                        DB::raw('COUNT(DISTINCT CASE WHEN e.background_check_status = "cleared" THEN e.id END) as cleared_background'),
                        DB::raw('COUNT(DISTINCT ec.id) as total_certificates'),
                        DB::raw('COUNT(DISTINCT CASE WHEN ec.status = "active" THEN ec.id END) as active_certificates'),
                        DB::raw('COUNT(DISTINCT CASE WHEN ec.status = "expired" THEN ec.id END) as expired_certificates'),
                        DB::raw('COUNT(DISTINCT CASE WHEN ec.status = "expiring_soon" THEN ec.id END) as expiring_certificates'),
                        DB::raw('COUNT(DISTINCT d.id) as departments_count'),
                    ])
                    ->first();

                return (array) $stats;
            });
        });
    }

    /**
     * Get cached certificate type statistics
     */
    public function getCertificateTypeStatistics()
    {
        return Cache::remember('certificate_type_statistics', self::CACHE_DURATIONS['statistics'], function () {
            return DB::table('certificate_types as ct')
                ->leftJoin('employee_certificates as ec', 'ct.id', '=', 'ec.certificate_type_id')
                ->select([
                    'ct.id',
                    'ct.name',
                    'ct.category',
                    'ct.is_mandatory',
                    'ct.is_active',
                    DB::raw('COUNT(DISTINCT ec.employee_id) as unique_employees'),
                    DB::raw('COUNT(ec.id) as total_certificates'),
                    DB::raw('COUNT(CASE WHEN ec.status = "active" THEN 1 END) as active_certificates'),
                    DB::raw('COUNT(CASE WHEN ec.status = "expired" THEN 1 END) as expired_certificates'),
                    DB::raw('COUNT(CASE WHEN ec.status = "expiring_soon" THEN 1 END) as expiring_certificates'),
                ])
                ->where('ct.is_active', true)
                ->groupBy('ct.id', 'ct.name', 'ct.category', 'ct.is_mandatory', 'ct.is_active')
                ->orderBy('ct.name')
                ->get()
                ->keyBy('id');
        });
    }

    /**
     * Get paginated employees with optimized relationships
     */
    public function getOptimizedEmployeesList($filters = [], $perPage = 15)
    {
        $cacheKey = 'employee_list_' . md5(serialize($filters)) . '_page_' . request('page', 1) . '_per_' . $perPage;
        
        return Cache::remember($cacheKey, self::CACHE_DURATIONS['employee_list'], function () use ($filters, $perPage) {
            $query = Employee::with([
                'department:id,name',
                'employeeCertificates' => function ($query) {
                    $query->select('employee_id', 'certificate_type_id', 'status', 'expiry_date')
                          ->with('certificateType:id,name,category');
                }
            ])
            ->select([
                'id', 'employee_id', 'name', 'position', 'department_id', 'status',
                'background_check_status', 'container_file_count', 'container_last_updated'
            ]);

            // Apply filters
            if (!empty($filters['search'])) {
                $query->search($filters['search']);
            }

            if (!empty($filters['department_id'])) {
                $query->where('department_id', $filters['department_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['background_check_status'])) {
                $query->where('background_check_status', $filters['background_check_status']);
            }

            return $query->paginate($perPage);
        });
    }

    /**
     * Get optimized dashboard data
     */
    public function getDashboardData()
    {
        return Cache::remember('dashboard_data', self::CACHE_DURATIONS['dashboard_data'], function () {
            $data = [];

            // Get employee statistics
            $data['employee_stats'] = $this->getEmployeeStatistics();

            // Get certificate type statistics
            $data['certificate_types'] = $this->getCertificateTypeStatistics();

            // Get department statistics
            $data['department_stats'] = DB::table('departments as d')
                ->leftJoin('employees as e', 'd.id', '=', 'e.department_id')
                ->select([
                    'd.id',
                    'd.name',
                    DB::raw('COUNT(e.id) as employee_count'),
                    DB::raw('COUNT(CASE WHEN e.status = "active" THEN 1 END) as active_employees'),
                ])
                ->groupBy('d.id', 'd.name')
                ->orderBy('d.name')
                ->get();

            // Get recent activity (certificates added/updated in last 7 days)
            $data['recent_activity'] = DB::table('employee_certificates as ec')
                ->join('employees as e', 'ec.employee_id', '=', 'e.id')
                ->join('certificate_types as ct', 'ec.certificate_type_id', '=', 'ct.id')
                ->select([
                    'e.name as employee_name',
                    'e.employee_id',
                    'ct.name as certificate_name',
                    'ec.status',
                    'ec.created_at',
                    'ec.updated_at'
                ])
                ->where('ec.created_at', '>=', now()->subDays(7))
                ->orderBy('ec.created_at', 'desc')
                ->limit(20)
                ->get();

            // Get compliance overview
            $data['compliance'] = $this->getComplianceOverview();

            return $data;
        });
    }

    /**
     * Get compliance overview with optimized queries
     */
    public function getComplianceOverview()
    {
        return Cache::remember('compliance_overview', self::CACHE_DURATIONS['statistics'], function () {
            // Get mandatory certificate types
            $mandatoryTypes = CertificateType::where('is_mandatory', true)
                ->select('id', 'name')
                ->get();

            $compliance = [];
            $totalActiveEmployees = Employee::where('status', 'active')->count();

            foreach ($mandatoryTypes as $type) {
                $employeesWithValidCert = DB::table('employee_certificates')
                    ->join('employees', 'employee_certificates.employee_id', '=', 'employees.id')
                    ->where('employee_certificates.certificate_type_id', $type->id)
                    ->where('employee_certificates.status', 'active')
                    ->where('employees.status', 'active')
                    ->distinct('employees.id')
                    ->count();

                $complianceRate = $totalActiveEmployees > 0 
                    ? round(($employeesWithValidCert / $totalActiveEmployees) * 100, 2)
                    : 0;

                $compliance[] = [
                    'certificate_type' => $type->name,
                    'compliant_employees' => $employeesWithValidCert,
                    'total_employees' => $totalActiveEmployees,
                    'compliance_rate' => $complianceRate,
                    'non_compliant' => $totalActiveEmployees - $employeesWithValidCert,
                ];
            }

            return $compliance;
        });
    }

    /**
     * Optimize certificate data for specific employee
     */
    public function getEmployeeCertificateData($employeeId)
    {
        $cacheKey = "employee_certificates_{$employeeId}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATIONS['employee_list'], function () use ($employeeId) {
            return Employee::with([
                'department:id,name',
                'employeeCertificates' => function ($query) {
                    $query->with('certificateType:id,name,category,validity_months')
                          ->orderBy('issue_date', 'desc');
                }
            ])->find($employeeId);
        });
    }

    /**
     * Bulk cache warming for frequently accessed data
     */
    public function warmCaches()
    {
        $warmingTasks = [
            'employee_statistics' => fn() => $this->getEmployeeStatistics(),
            'certificate_type_statistics' => fn() => $this->getCertificateTypeStatistics(),
            'dashboard_data' => fn() => $this->getDashboardData(),
        ];

        $results = [];
        foreach ($warmingTasks as $key => $task) {
            try {
                $start = microtime(true);
                $task();
                $duration = round((microtime(true) - $start) * 1000, 2);
                $results[$key] = ['status' => 'success', 'duration_ms' => $duration];
            } catch (\Exception $e) {
                $results[$key] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Clear all performance caches
     */
    public function clearCaches()
    {
        $patterns = [
            'employee_statistics',
            'certificate_type_statistics',
            'dashboard_data',
            'employee_list_*',
            'employee_certificates_*',
            'compliance_overview',
        ];

        $cleared = [];
        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // Clear pattern-based cache keys
                $keys = Cache::getRedis()->keys($pattern);
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
                $cleared[] = $pattern;
            } else {
                Cache::forget($pattern);
                $cleared[] = $pattern;
            }
        }

        return $cleared;
    }

    /**
     * Get database query performance metrics
     */
    public function getQueryPerformanceMetrics()
    {
        return Cache::remember('query_performance_metrics', 300, function () {
            // Get slow query statistics from MySQL
            $slowQueries = DB::select("
                SELECT 
                    COUNT(*) as total_slow_queries,
                    AVG(query_time) as avg_query_time
                FROM information_schema.PROCESSLIST 
                WHERE command != 'Sleep' 
                AND time > 1
            ");

            // Get table statistics
            $tableStats = DB::select("
                SELECT 
                    table_name,
                    table_rows,
                    data_length,
                    index_length
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()
                ORDER BY data_length DESC
                LIMIT 10
            ");

            return [
                'slow_queries' => $slowQueries[0] ?? null,
                'large_tables' => $tableStats,
                'cache_hit_ratio' => $this->getCacheHitRatio(),
                'last_updated' => now()->toISOString(),
            ];
        });
    }

    /**
     * Calculate cache hit ratio
     */
    private function getCacheHitRatio()
    {
        try {
            // This would need to be implemented based on your cache driver
            // For Redis, you could use Redis::info()
            return ['status' => 'not_implemented'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Optimize database with indexes and maintenance
     */
    public function optimizeDatabase()
    {
        $results = [];

        try {
            // Analyze tables for optimization opportunities
            $tables = ['employees', 'employee_certificates', 'certificate_types', 'departments'];
            
            foreach ($tables as $table) {
                DB::statement("ANALYZE TABLE {$table}");
                $results['analyzed_tables'][] = $table;
            }

            // Check for missing indexes
            $indexRecommendations = $this->getIndexRecommendations();
            $results['index_recommendations'] = $indexRecommendations;

            return [
                'status' => 'success',
                'results' => $results,
                'timestamp' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get index recommendations based on query patterns
     */
    private function getIndexRecommendations()
    {
        return [
            'employees' => [
                'recommended' => ['status', 'department_id', 'background_check_status'],
                'existing' => ['id', 'employee_id'],
            ],
            'employee_certificates' => [
                'recommended' => ['employee_id,status', 'certificate_type_id,status', 'expiry_date'],
                'existing' => ['employee_id', 'certificate_type_id'],
            ],
            'certificate_types' => [
                'recommended' => ['is_active', 'is_mandatory', 'category'],
                'existing' => ['id'],
            ],
        ];
    }
}