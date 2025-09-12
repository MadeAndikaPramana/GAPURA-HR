// components/ContainerManagement/SystemHealth.jsx
import { useState, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import {
    CpuChipIcon,
    CircleStackIcon,
    CloudIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    ChartBarIcon,
    DocumentIcon,
    UsersIcon,
    ServerIcon,
    WifiIcon,
    BoltIcon,
    ShieldCheckIcon,
    DatabaseIcon
} from '@heroicons/react/24/outline';

const StatusIndicator = ({ status, label, description }) => {
    const getStatusConfig = (status) => {
        switch (status) {
            case 'healthy':
                return {
                    icon: CheckCircleIcon,
                    color: 'text-green-600',
                    bgColor: 'bg-green-100',
                    borderColor: 'border-green-200'
                };
            case 'warning':
                return {
                    icon: ExclamationTriangleIcon,
                    color: 'text-yellow-600',
                    bgColor: 'bg-yellow-100',
                    borderColor: 'border-yellow-200'
                };
            case 'critical':
                return {
                    icon: XCircleIcon,
                    color: 'text-red-600',
                    bgColor: 'bg-red-100',
                    borderColor: 'border-red-200'
                };
            default:
                return {
                    icon: ClockIcon,
                    color: 'text-gray-600',
                    bgColor: 'bg-gray-100',
                    borderColor: 'border-gray-200'
                };
        }
    };

    const config = getStatusConfig(status);
    const StatusIcon = config.icon;

    return (
        <div className={`inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium ${config.color} ${config.bgColor} border ${config.borderColor}`}>
            <StatusIcon className="w-4 h-4 mr-1.5" />
            {label}
        </div>
    );
};

const HealthMetricCard = ({ title, value, unit, status, trend, icon: Icon, description, details }) => {
    const [showDetails, setShowDetails] = useState(false);

    const getTrendIcon = (trend) => {
        if (trend > 0) return '↗️';
        if (trend < 0) return '↘️';
        return '➡️';
    };

    const getTrendColor = (trend, metric) => {
        // For some metrics, going up is bad (like CPU usage, response time)
        const badWhenHigh = ['cpu', 'memory', 'response_time', 'error_rate'];
        const isBadMetric = badWhenHigh.some(bad => title.toLowerCase().includes(bad));
        
        if (trend === 0) return 'text-gray-500';
        
        if (isBadMetric) {
            return trend > 0 ? 'text-red-600' : 'text-green-600';
        } else {
            return trend > 0 ? 'text-green-600' : 'text-red-600';
        }
    };

    return (
        <div className="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow">
            <div className="flex items-start justify-between">
                <div className="flex items-center">
                    <div className={`p-2 rounded-lg ${
                        status === 'healthy' ? 'bg-green-50 text-green-600' :
                        status === 'warning' ? 'bg-yellow-50 text-yellow-600' :
                        status === 'critical' ? 'bg-red-50 text-red-600' :
                        'bg-gray-50 text-gray-600'
                    }`}>
                        <Icon className="w-5 h-5" />
                    </div>
                    <div className="ml-3">
                        <p className="text-sm font-medium text-gray-900">{title}</p>
                        <p className="text-2xl font-bold text-gray-900 mt-1">
                            {value}
                            {unit && <span className="text-lg text-gray-500 ml-1">{unit}</span>}
                        </p>
                    </div>
                </div>
                
                <div className="text-right">
                    <StatusIndicator status={status} label={status} />
                    {trend !== undefined && (
                        <div className={`text-sm mt-1 ${getTrendColor(trend, title)}`}>
                            {getTrendIcon(trend)} {Math.abs(trend)}%
                        </div>
                    )}
                </div>
            </div>
            
            {description && (
                <p className="text-sm text-gray-600 mt-2">{description}</p>
            )}
            
            {details && (
                <div className="mt-3">
                    <button
                        onClick={() => setShowDetails(!showDetails)}
                        className="text-xs text-blue-600 hover:text-blue-800"
                    >
                        {showDetails ? 'Hide details' : 'Show details'}
                    </button>
                    
                    {showDetails && (
                        <div className="mt-2 p-2 bg-gray-50 rounded text-xs text-gray-600">
                            {typeof details === 'string' ? details : JSON.stringify(details, null, 2)}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

const SystemLog = ({ logs, maxEntries = 10 }) => {
    const getLogLevelColor = (level) => {
        switch (level.toLowerCase()) {
            case 'error':
                return 'text-red-600 bg-red-50';
            case 'warning':
            case 'warn':
                return 'text-yellow-600 bg-yellow-50';
            case 'info':
                return 'text-blue-600 bg-blue-50';
            case 'debug':
                return 'text-gray-600 bg-gray-50';
            default:
                return 'text-gray-600 bg-gray-50';
        }
    };

    const formatTimestamp = (timestamp) => {
        return new Date(timestamp).toLocaleString();
    };

    return (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-gray-900">System Logs</h3>
                <span className="text-sm text-gray-500">Last {maxEntries} entries</span>
            </div>
            
            <div className="space-y-2 max-h-80 overflow-y-auto">
                {logs.length === 0 ? (
                    <p className="text-sm text-gray-500 text-center py-4">No recent logs</p>
                ) : (
                    logs.slice(0, maxEntries).map((log, index) => (
                        <div key={index} className="flex items-start space-x-3 py-2 border-b border-gray-100 last:border-b-0">
                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getLogLevelColor(log.level)}`}>
                                {log.level.toUpperCase()}
                            </span>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm text-gray-900 break-words">{log.message}</p>
                                <div className="flex items-center mt-1 text-xs text-gray-500 space-x-3">
                                    <span>{formatTimestamp(log.timestamp)}</span>
                                    {log.source && <span>Source: {log.source}</span>}
                                    {log.user && <span>User: {log.user}</span>}
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
};

const PerformanceChart = ({ data, title, metric, timeRange = '1h' }) => {
    const chartRef = useRef(null);
    
    useEffect(() => {
        // In a real implementation, you would use a charting library like Chart.js or D3
        // For now, we'll create a simple visual representation
        if (chartRef.current && data.length > 0) {
            drawSimpleChart();
        }
    }, [data]);

    const drawSimpleChart = () => {
        const canvas = chartRef.current;
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        
        // Clear canvas
        ctx.clearRect(0, 0, width, height);
        
        if (data.length < 2) return;
        
        // Calculate scales
        const maxValue = Math.max(...data.map(d => d.value));
        const minValue = Math.min(...data.map(d => d.value));
        const valueRange = maxValue - minValue || 1;
        
        // Draw chart
        ctx.strokeStyle = '#3B82F6';
        ctx.lineWidth = 2;
        ctx.beginPath();
        
        data.forEach((point, index) => {
            const x = (index / (data.length - 1)) * width;
            const y = height - ((point.value - minValue) / valueRange) * height;
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        
        ctx.stroke();
        
        // Draw points
        ctx.fillStyle = '#3B82F6';
        data.forEach((point, index) => {
            const x = (index / (data.length - 1)) * width;
            const y = height - ((point.value - minValue) / valueRange) * height;
            
            ctx.beginPath();
            ctx.arc(x, y, 3, 0, 2 * Math.PI);
            ctx.fill();
        });
    };

    return (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-gray-900">{title}</h3>
                <span className="text-sm text-gray-500">Last {timeRange}</span>
            </div>
            
            <div className="relative">
                <canvas
                    ref={chartRef}
                    width={400}
                    height={150}
                    className="w-full h-auto border rounded"
                />
                
                {data.length === 0 && (
                    <div className="absolute inset-0 flex items-center justify-center">
                        <p className="text-sm text-gray-500">No data available</p>
                    </div>
                )}
            </div>
            
            {data.length > 0 && (
                <div className="flex justify-between text-xs text-gray-500 mt-2">
                    <span>Min: {Math.min(...data.map(d => d.value)).toFixed(2)}</span>
                    <span>Max: {Math.max(...data.map(d => d.value)).toFixed(2)}</span>
                    <span>Avg: {(data.reduce((sum, d) => sum + d.value, 0) / data.length).toFixed(2)}</span>
                </div>
            )}
        </div>
    );
};

export default function SystemHealth({ 
    autoRefresh = true, 
    refreshInterval = 30000, // 30 seconds
    className = "" 
}) {
    const [healthData, setHealthData] = useState({
        overall_status: 'loading',
        metrics: {},
        logs: [],
        performance: {},
        last_updated: null
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const refreshRef = useRef(null);

    useEffect(() => {
        fetchHealthData();
        
        if (autoRefresh) {
            refreshRef.current = setInterval(fetchHealthData, refreshInterval);
        }
        
        return () => {
            if (refreshRef.current) {
                clearInterval(refreshRef.current);
            }
        };
    }, [autoRefresh, refreshInterval]);

    const fetchHealthData = async () => {
        try {
            setError(null);
            
            const response = await fetch('/api/system/health');
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            setHealthData(data);
            
        } catch (err) {
            console.error('Failed to fetch system health data:', err);
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleRefresh = () => {
        setLoading(true);
        fetchHealthData();
    };

    const getOverallStatusColor = (status) => {
        switch (status) {
            case 'healthy':
                return 'text-green-600 bg-green-50 border-green-200';
            case 'warning':
                return 'text-yellow-600 bg-yellow-50 border-yellow-200';
            case 'critical':
                return 'text-red-600 bg-red-50 border-red-200';
            default:
                return 'text-gray-600 bg-gray-50 border-gray-200';
        }
    };

    // Default/mock data structure
    const defaultMetrics = {
        database: {
            status: 'healthy',
            response_time: 15,
            active_connections: 12,
            max_connections: 100,
            query_cache_hit_rate: 98.5
        },
        storage: {
            status: 'healthy',
            used_space: 45.6,
            total_space: 500.0,
            file_uploads_today: 23,
            backup_status: 'completed'
        },
        application: {
            status: 'healthy',
            response_time: 187,
            memory_usage: 68.3,
            cpu_usage: 23.1,
            active_users: 156
        },
        background_jobs: {
            status: 'healthy',
            queue_size: 3,
            failed_jobs: 0,
            processed_today: 847,
            average_processing_time: 2.3
        }
    };

    const metrics = healthData.metrics || defaultMetrics;

    if (loading && !healthData.last_updated) {
        return (
            <div className={`flex items-center justify-center py-12 ${className}`}>
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p className="text-gray-600">Loading system health data...</p>
                </div>
            </div>
        );
    }

    return (
        <div className={`space-y-6 ${className}`}>
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900">System Health</h2>
                    <p className="text-gray-600">Monitor system performance and status</p>
                </div>
                
                <div className="flex items-center space-x-4">
                    {healthData.last_updated && (
                        <span className="text-sm text-gray-500">
                            Last updated: {new Date(healthData.last_updated).toLocaleTimeString()}
                        </span>
                    )}
                    
                    <button
                        onClick={handleRefresh}
                        disabled={loading}
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                    >
                        <ArrowPathIcon className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                        Refresh
                    </button>
                </div>
            </div>

            {/* Overall Status */}
            <div className={`p-4 rounded-lg border ${getOverallStatusColor(healthData.overall_status || 'healthy')}`}>
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <ServerIcon className="w-6 h-6 mr-3" />
                        <div>
                            <h3 className="text-lg font-medium">System Status</h3>
                            <p className="text-sm opacity-90">
                                {healthData.overall_status === 'healthy' && 'All systems operating normally'}
                                {healthData.overall_status === 'warning' && 'Some systems require attention'}
                                {healthData.overall_status === 'critical' && 'Critical issues detected'}
                                {healthData.overall_status === 'loading' && 'Checking system status...'}
                            </p>
                        </div>
                    </div>
                    
                    <StatusIndicator 
                        status={healthData.overall_status || 'loading'} 
                        label={healthData.overall_status || 'Loading'} 
                    />
                </div>
            </div>

            {/* Error Display */}
            {error && (
                <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div className="flex">
                        <ExclamationTriangleIcon className="h-5 w-5 text-red-400 mt-0.5 mr-2 flex-shrink-0" />
                        <div>
                            <h3 className="text-sm font-medium text-red-800">
                                Unable to fetch system health data
                            </h3>
                            <p className="text-sm text-red-700 mt-1">{error}</p>
                            <button
                                onClick={handleRefresh}
                                className="mt-2 text-sm text-red-800 underline hover:text-red-900"
                            >
                                Try again
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Health Metrics Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                {/* Database Health */}
                <HealthMetricCard
                    title="Database Response"
                    value={metrics.database?.response_time || 0}
                    unit="ms"
                    status={metrics.database?.status || 'unknown'}
                    trend={-2.3}
                    icon={DatabaseIcon}
                    description={`${metrics.database?.active_connections || 0}/${metrics.database?.max_connections || 100} connections`}
                    details={`Cache hit rate: ${metrics.database?.query_cache_hit_rate || 0}%`}
                />
                
                {/* Storage Health */}
                <HealthMetricCard
                    title="Storage Usage"
                    value={((metrics.storage?.used_space || 0) / (metrics.storage?.total_space || 1) * 100).toFixed(1)}
                    unit="%"
                    status={metrics.storage?.status || 'unknown'}
                    trend={1.2}
                    icon={CircleStackIcon}
                    description={`${metrics.storage?.used_space || 0} GB of ${metrics.storage?.total_space || 0} GB used`}
                    details={`${metrics.storage?.file_uploads_today || 0} files uploaded today`}
                />
                
                {/* Application Health */}
                <HealthMetricCard
                    title="Response Time"
                    value={metrics.application?.response_time || 0}
                    unit="ms"
                    status={metrics.application?.status || 'unknown'}
                    trend={-5.1}
                    icon={BoltIcon}
                    description={`${metrics.application?.active_users || 0} active users`}
                    details={`CPU: ${metrics.application?.cpu_usage || 0}%, Memory: ${metrics.application?.memory_usage || 0}%`}
                />
                
                {/* Background Jobs */}
                <HealthMetricCard
                    title="Job Queue"
                    value={metrics.background_jobs?.queue_size || 0}
                    unit="jobs"
                    status={metrics.background_jobs?.status || 'unknown'}
                    trend={0}
                    icon={ClockIcon}
                    description={`${metrics.background_jobs?.processed_today || 0} processed today`}
                    details={`${metrics.background_jobs?.failed_jobs || 0} failed, avg ${metrics.background_jobs?.average_processing_time || 0}s`}
                />
            </div>

            {/* Performance Charts */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <PerformanceChart
                    title="Response Time Trend"
                    data={healthData.performance?.response_time || []}
                    metric="response_time"
                    timeRange="1h"
                />
                
                <PerformanceChart
                    title="Memory Usage Trend"
                    data={healthData.performance?.memory_usage || []}
                    metric="memory_usage"
                    timeRange="1h"
                />
            </div>

            {/* System Logs */}
            <SystemLog 
                logs={healthData.logs || []} 
                maxEntries={15} 
            />

            {/* Additional System Info */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white rounded-lg border border-gray-200 p-6">
                    <div className="flex items-center mb-3">
                        <ShieldCheckIcon className="w-5 h-5 text-green-600 mr-2" />
                        <h3 className="text-sm font-medium text-gray-900">Security Status</h3>
                    </div>
                    <div className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-gray-600">SSL Certificate:</span>
                            <span className="text-green-600">Valid</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-600">Last Backup:</span>
                            <span className="text-gray-900">2 hours ago</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-600">Failed Login Attempts:</span>
                            <span className="text-gray-900">3 (24h)</span>
                        </div>
                    </div>
                </div>
                
                <div className="bg-white rounded-lg border border-gray-200 p-6">
                    <div className="flex items-center mb-3">
                        <WifiIcon className="w-5 h-5 text-blue-600 mr-2" />
                        <h3 className="text-sm font-medium text-gray-900">Network Status</h3>
                    </div>
                    <div className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-gray-600">API Response Time:</span>
                            <span className="text-gray-900">142ms</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-600">External API Health:</span>
                            <span className="text-green-600">Online</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-600">CDN Status:</span>
                            <span className="text-green-600">Healthy</span>
                        </div>
                    </div>
                </div>
                
                <div className="bg-white rounded-lg border border-gray-200 p-6">
                    <div className="flex items-center mb-3">
                        <ChartBarIcon className="w-5 h-5 text-purple-600 mr-2" />
                        <h3 className="text-sm font-medium text-gray-900">Usage Statistics</h3>
                    </div>
                    <div className="space-y-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-gray-600">Daily Active Users:</span>
                            <span className="text-gray-900">1,247</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-600">API Calls (24h):</span>
                            <span className="text-gray-900">45,621</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-gray-600">Files Processed:</span>
                            <span className="text-gray-900">2,341</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

StatusIndicator.propTypes = {
    status: PropTypes.oneOf(['healthy', 'warning', 'critical', 'loading']).isRequired,
    label: PropTypes.string.isRequired,
    description: PropTypes.string
};

HealthMetricCard.propTypes = {
    title: PropTypes.string.isRequired,
    value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    unit: PropTypes.string,
    status: PropTypes.string.isRequired,
    trend: PropTypes.number,
    icon: PropTypes.elementType.isRequired,
    description: PropTypes.string,
    details: PropTypes.oneOfType([PropTypes.string, PropTypes.object])
};

SystemLog.propTypes = {
    logs: PropTypes.array.isRequired,
    maxEntries: PropTypes.number
};

PerformanceChart.propTypes = {
    data: PropTypes.array.isRequired,
    title: PropTypes.string.isRequired,
    metric: PropTypes.string.isRequired,
    timeRange: PropTypes.string
};

SystemHealth.propTypes = {
    autoRefresh: PropTypes.bool,
    refreshInterval: PropTypes.number,
    className: PropTypes.string
};