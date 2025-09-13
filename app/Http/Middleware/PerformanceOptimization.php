<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PerformanceOptimization
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $queryCount = 0;
        $queryTime = 0;

        // Enable query logging for performance monitoring
        if (config('app.debug')) {
            DB::enableQueryLog();
        }

        // Track query performance
        DB::listen(function ($query) use (&$queryCount, &$queryTime) {
            $queryCount++;
            $queryTime += $query->time;
        });

        $response = $next($request);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Log performance metrics for slow requests
        if ($executionTime > 1000) { // Log requests taking more than 1 second
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time_ms' => round($executionTime, 2),
                'query_count' => $queryCount,
                'query_time_ms' => round($queryTime, 2),
                'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'user_id' => $request->user()?->id,
            ]);
        }

        // Add performance headers for debugging
        if (config('app.debug')) {
            $response->headers->set('X-Query-Count', $queryCount);
            $response->headers->set('X-Query-Time', round($queryTime, 2));
            $response->headers->set('X-Execution-Time', round($executionTime, 2));
            $response->headers->set('X-Memory-Usage', round(memory_get_peak_usage(true) / 1024 / 1024, 2));
        }

        // Cache warming for frequently accessed routes
        $this->warmCacheForRoute($request);

        return $response;
    }

    /**
     * Warm cache for specific routes that are frequently accessed
     */
    private function warmCacheForRoute(Request $request)
    {
        $route = $request->route()->getName();
        
        $warmingRoutes = [
            'training-types.index',
            'employees.index', 
            'dashboard',
        ];

        if (in_array($route, $warmingRoutes)) {
            // Warm cache in background job to avoid blocking the response
            dispatch(function () use ($route) {
                $this->performRouteSpecificWarming($route);
            })->afterResponse();
        }
    }

    /**
     * Perform route-specific cache warming
     */
    private function performRouteSpecificWarming($route)
    {
        try {
            switch ($route) {
                case 'training-types.index':
                    Cache::remember('certificate_types_stats', 300, function () {
                        return \App\Models\CertificateType::with(['employeeCertificates:certificate_type_id,status'])
                            ->get(['id', 'name', 'category', 'is_active']);
                    });
                    break;

                case 'employees.index':
                    Cache::remember('employees_overview', 180, function () {
                        return \App\Models\Employee::with(['department:id,name'])
                            ->select(['id', 'employee_id', 'name', 'department_id', 'status'])
                            ->take(50)
                            ->get();
                    });
                    break;

                case 'dashboard':
                    $performanceService = app(\App\Services\PerformanceOptimizationService::class);
                    $performanceService->warmCaches();
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Cache warming failed', [
                'route' => $route,
                'error' => $e->getMessage(),
            ]);
        }
    }
}