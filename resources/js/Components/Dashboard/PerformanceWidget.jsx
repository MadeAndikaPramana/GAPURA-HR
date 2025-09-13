import { useState, useEffect } from 'react';
import { 
    ChartBarIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    CpuChipIcon
} from '@heroicons/react/24/outline';

/**
 * PerformanceWidget - Real-time performance monitoring widget
 * Shows system health, response times, and performance metrics
 */
export default function PerformanceWidget({
    className = '',
    autoRefresh = true,
    refreshInterval = 30000,
    showDetails = false
}) {
    const [performanceData, setPerformanceData] = useState({
        status: 'loading',
        metrics: {
            response_time: { value: 0, trend: 0, unit: 'ms' },
            memory_usage: { value: 0, trend: 0, unit: '%' },
            database_queries: { value: 0, trend: 0, unit: '/min' },
            cache_hit_rate: { value: 0, trend: 0, unit: '%' },
        },
        last_updated: null
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchPerformanceData();
        
        if (autoRefresh) {
            const interval = setInterval(fetchPerformanceData, refreshInterval);
            return () => clearInterval(interval);
        }
    }, [autoRefresh, refreshInterval]);

    const fetchPerformanceData = async () => {
        try {
            const response = await fetch('/api/performance/health');
            if (!response.ok) throw new Error('Failed to fetch performance data');
            
            const data = await response.json();
            setPerformanceData(data);
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const getStatusColor = (status) => {
        const colors = {
            healthy: 'text-green-600 bg-green-100',
            warning: 'text-yellow-600 bg-yellow-100',
            critical: 'text-red-600 bg-red-100',
            loading: 'text-gray-600 bg-gray-100'
        };
        return colors[status] || colors.loading;
    };

    const getStatusIcon = (status) => {
        const icons = {
            healthy: CheckCircleIcon,
            warning: ExclamationTriangleIcon,
            critical: ExclamationTriangleIcon,
            loading: ClockIcon
        };
        const Icon = icons[status] || ClockIcon;
        return <Icon className="w-4 h-4" />;
    };

    const getTrendIcon = (trend) => {
        if (trend > 0) return <ArrowTrendingUpIcon className="w-3 h-3 text-red-500" />;
        if (trend < 0) return <ArrowTrendingDownIcon className="w-3 h-3 text-green-500" />;
        return null;
    };

    const MetricCard = ({ title, metric, icon: Icon }) => (
        <div className="bg-white rounded-lg border border-gray-200 p-3">
            <div className="flex items-center justify-between mb-2">
                <div className="flex items-center space-x-2">
                    <Icon className="w-4 h-4 text-gray-600" />
                    <span className="text-sm font-medium text-gray-900">{title}</span>
                </div>
                {getTrendIcon(metric.trend)}
            </div>
            <div className="flex items-baseline space-x-2">
                <span className="text-2xl font-bold text-gray-900">
                    {loading ? '---' : metric.value}
                </span>
                <span className="text-sm text-gray-500">{metric.unit}</span>
            </div>
            {metric.trend !== 0 && (
                <div className="text-xs text-gray-500 mt-1">
                    {Math.abs(metric.trend)}% from last check
                </div>
            )}
        </div>
    );

    return (
        <div className={`bg-white rounded-lg border border-gray-200 ${className}`}>
            <div className="p-4 border-b border-gray-200">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                        <CpuChipIcon className="w-6 h-6 text-blue-600" />
                        <h3 className="text-lg font-semibold text-gray-900">System Performance</h3>
                    </div>
                    
                    <div className={`inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium ${getStatusColor(performanceData.status)}`}>
                        {getStatusIcon(performanceData.status)}
                        <span className="ml-1.5 capitalize">{performanceData.status}</span>
                    </div>
                </div>
            </div>

            <div className="p-4">
                {error ? (
                    <div className="text-center py-8">
                        <ExclamationTriangleIcon className="w-8 h-8 text-red-500 mx-auto mb-2" />
                        <p className="text-sm text-red-600">{error}</p>
                        <button
                            onClick={fetchPerformanceData}
                            className="mt-2 text-sm text-blue-600 hover:text-blue-800"
                        >
                            Try again
                        </button>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {/* Key Metrics Grid */}
                        <div className="grid grid-cols-2 gap-3">
                            <MetricCard
                                title="Response Time"
                                metric={performanceData.metrics.response_time}
                                icon={ClockIcon}
                            />
                            <MetricCard
                                title="Memory Usage"
                                metric={performanceData.metrics.memory_usage}
                                icon={ChartBarIcon}
                            />
                        </div>

                        {showDetails && (
                            <div className="grid grid-cols-2 gap-3">
                                <MetricCard
                                    title="DB Queries"
                                    metric={performanceData.metrics.database_queries}
                                    icon={ChartBarIcon}
                                />
                                <MetricCard
                                    title="Cache Hit Rate"
                                    metric={performanceData.metrics.cache_hit_rate}
                                    icon={ChartBarIcon}
                                />
                            </div>
                        )}

                        {/* Last Updated */}
                        {performanceData.last_updated && (
                            <div className="text-xs text-gray-500 text-center pt-2 border-t border-gray-100">
                                Last updated: {new Date(performanceData.last_updated).toLocaleTimeString()}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}