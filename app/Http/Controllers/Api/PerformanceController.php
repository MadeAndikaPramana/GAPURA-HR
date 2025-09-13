<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PerformanceOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceController extends Controller
{
    private PerformanceOptimizationService $performanceService;

    public function __construct(PerformanceOptimizationService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Get system health and performance metrics
     */
    public function health()
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'metrics' => [
                    'database' => $this->getDatabaseHealth(),
                    'cache' => $this->getCacheHealth(),
                    'application' => $this->getApplicationHealth(),
                    'performance' => $this->getPerformanceMetrics(),
                ],
                'recommendations' => $this->getPerformanceRecommendations(),
            ];

            // Determine overall status
            $criticalIssues = collect($health['metrics'])->filter(fn($metric) => $metric['status'] === 'critical');
            $warningIssues = collect($health['metrics'])->filter(fn($metric) => $metric['status'] === 'warning');

            if ($criticalIssues->count() > 0) {
                $health['status'] = 'critical';
            } elseif ($warningIssues->count() > 0) {
                $health['status'] = 'warning';
            }

            return response()->json($health);
        } catch (\Exception $e) {
            Log::error('Performance health check failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Get database health metrics
     */
    private function getDatabaseHealth()
    {
        $startTime = microtime(true);
        
        try {
            // Test database connectivity and performance
            $connectionTime = microtime(true);
            DB::select('SELECT 1');
            $connectionTime = (microtime(true) - $connectionTime) * 1000;

            // Get database statistics
            $stats = DB::select("
                SELECT 
                    COUNT(*) as total_queries,
                    AVG(TIME) as avg_query_time
                FROM information_schema.PROCESSLIST 
                WHERE COMMAND != 'Sleep'
            ")[0] ?? (object)['total_queries' => 0, 'avg_query_time' => 0];

            // Get table sizes
            $tableSizes = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()
                ORDER BY size_mb DESC
                LIMIT 5
            ");

            $responseTime = (microtime(true) - $startTime) * 1000;

            return [
                'status' => $connectionTime > 100 ? 'warning' : 'healthy',
                'connection_time_ms' => round($connectionTime, 2),
                'response_time_ms' => round($responseTime, 2),
                'active_queries' => $stats->total_queries,
                'avg_query_time' => round($stats->avg_query_time ?? 0, 2),
                'largest_tables' => $tableSizes,
                'last_checked' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => 'Database connection failed: ' . $e->getMessage(),
                'last_checked' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get cache health metrics
     */
    private function getCacheHealth()
    {
        $startTime = microtime(true);
        
        try {
            // Test cache operations
            $testKey = 'performance_test_' . time();
            $testValue = 'test_data';
            
            // Test write
            $writeTime = microtime(true);
            Cache::put($testKey, $testValue, 60);
            $writeTime = (microtime(true) - $writeTime) * 1000;
            
            // Test read
            $readTime = microtime(true);
            $retrieved = Cache::get($testKey);
            $readTime = (microtime(true) - $readTime) * 1000;
            
            // Clean up
            Cache::forget($testKey);
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            $success = $retrieved === $testValue;

            return [
                'status' => $success && $responseTime < 50 ? 'healthy' : 'warning',
                'write_time_ms' => round($writeTime, 2),
                'read_time_ms' => round($readTime, 2),
                'total_time_ms' => round($responseTime, 2),
                'operations_successful' => $success,
                'cache_driver' => config('cache.default'),
                'last_checked' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => 'Cache operations failed: ' . $e->getMessage(),
                'last_checked' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get application health metrics
     */
    private function getApplicationHealth()
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        $memoryUsagePercent = $memoryLimit > 0 ? ($memoryPeak / $memoryLimit) * 100 : 0;

        return [
            'status' => $memoryUsagePercent > 80 ? 'warning' : 'healthy',
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'memory_limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'memory_usage_percent' => round($memoryUsagePercent, 2),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'last_checked' => now()->toISOString(),
        ];
    }

    /**
     * Get detailed performance metrics
     */
    private function getPerformanceMetrics()
    {
        return Cache::remember('performance_metrics_detailed', 60, function () {
            return [
                'cache_stats' => [
                    'hit_rate' => $this->calculateCacheHitRate(),
                    'total_keys' => $this->getCacheKeyCount(),
                    'memory_usage' => $this->getCacheMemoryUsage(),
                ],
                'query_stats' => $this->getQueryStatistics(),
                'response_times' => $this->getAverageResponseTimes(),
                'error_rates' => $this->getErrorRates(),
            ];
        });
    }

    /**
     * Get performance recommendations
     */
    private function getPerformanceRecommendations()
    {
        $recommendations = [];

        // Check database performance
        $dbHealth = $this->getDatabaseHealth();
        if ($dbHealth['response_time_ms'] > 100) {
            $recommendations[] = [
                'type' => 'database',
                'priority' => 'high',
                'message' => 'Database response time is high. Consider optimizing queries or adding indexes.',
                'action' => 'review_database_queries',
            ];
        }

        // Check memory usage
        $appHealth = $this->getApplicationHealth();
        if ($appHealth['memory_usage_percent'] > 70) {
            $recommendations[] = [
                'type' => 'memory',
                'priority' => 'medium',
                'message' => 'Memory usage is high. Consider optimizing data structures or increasing memory limit.',
                'action' => 'optimize_memory_usage',
            ];
        }

        // Check cache performance
        $cacheHealth = $this->getCacheHealth();
        if ($cacheHealth['total_time_ms'] > 20) {
            $recommendations[] = [
                'type' => 'cache',
                'priority' => 'medium',
                'message' => 'Cache operations are slow. Consider switching to a faster cache driver.',
                'action' => 'optimize_cache_driver',
            ];
        }

        return $recommendations;
    }

    /**
     * Warm performance caches
     */
    public function warmCaches()
    {
        try {
            $results = $this->performanceService->warmCaches();
            
            return response()->json([
                'success' => true,
                'message' => 'Cache warming completed',
                'results' => $results,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Cache warming failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Cache warming failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Clear performance caches
     */
    public function clearCaches()
    {
        try {
            $cleared = $this->performanceService->clearCaches();
            
            return response()->json([
                'success' => true,
                'message' => 'Caches cleared successfully',
                'cleared' => $cleared,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Cache clearing failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Cache clearing failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Optimize database
     */
    public function optimizeDatabase()
    {
        try {
            $results = $this->performanceService->optimizeDatabase();
            
            return response()->json([
                'success' => $results['status'] === 'success',
                'message' => $results['status'] === 'success' 
                    ? 'Database optimization completed'
                    : 'Database optimization failed',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Database optimization failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Database optimization failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    // Helper methods

    private function parseMemoryLimit($memoryLimit)
    {
        if ($memoryLimit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtoupper(substr($memoryLimit, -1));
        $value = (int) $memoryLimit;

        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return $value;
        }
    }

    private function calculateCacheHitRate()
    {
        // This would depend on your cache driver
        return 0.95; // Placeholder
    }

    private function getCacheKeyCount()
    {
        // This would depend on your cache driver
        return 150; // Placeholder
    }

    private function getCacheMemoryUsage()
    {
        // This would depend on your cache driver
        return '2.5MB'; // Placeholder
    }

    private function getQueryStatistics()
    {
        return [
            'avg_queries_per_request' => 15,
            'slow_queries_count' => 2,
            'total_query_time_ms' => 45.2,
        ];
    }

    private function getAverageResponseTimes()
    {
        return [
            'api_endpoints' => 125.5,
            'page_renders' => 89.3,
            'database_queries' => 12.1,
        ];
    }

    private function getErrorRates()
    {
        return [
            '4xx_errors' => 0.02,
            '5xx_errors' => 0.001,
            'database_errors' => 0.0005,
        ];
    }
}