import { useState, useEffect, useMemo } from 'react';
import { 
    UsersIcon,
    AcademicCapIcon,
    BuildingOfficeIcon,
    DocumentCheckIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    TrendingUpIcon,
    TrendingDownIcon
} from '@heroicons/react/24/outline';

/**
 * QuickStatsWidget - Dashboard widget showing key system statistics
 * Features animated counters, trend indicators, and drill-down capabilities
 */
export default function QuickStatsWidget({
    className = '',
    autoRefresh = true,
    refreshInterval = 30000,
    showTrends = true,
    onStatClick
}) {
    const [statsData, setStatsData] = useState({
        employees: { current: 0, trend: 0 },
        active_employees: { current: 0, trend: 0 },
        certificate_types: { current: 0, trend: 0 },
        active_certificates: { current: 0, trend: 0 },
        expired_certificates: { current: 0, trend: 0 },
        expiring_certificates: { current: 0, trend: 0 },
        departments: { current: 0, trend: 0 },
        compliance_rate: { current: 0, trend: 0 },
        last_updated: null
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchStatsData();
        
        if (autoRefresh) {
            const interval = setInterval(fetchStatsData, refreshInterval);
            return () => clearInterval(interval);
        }
    }, [autoRefresh, refreshInterval]);

    const fetchStatsData = async () => {
        try {
            const response = await fetch('/api/dashboard/stats');
            if (!response.ok) throw new Error('Failed to fetch stats data');
            
            const data = await response.json();
            setStatsData(data);
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    // Animated counter hook
    const useAnimatedCounter = (targetValue, duration = 1000) => {
        const [count, setCount] = useState(0);

        useEffect(() => {
            if (loading) return;

            let startTime;
            const startValue = count;
            const endValue = targetValue;

            const animate = (currentTime) => {
                if (!startTime) startTime = currentTime;
                const progress = Math.min((currentTime - startTime) / duration, 1);
                const currentCount = Math.floor(startValue + (endValue - startValue) * progress);
                
                setCount(currentCount);

                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };

            requestAnimationFrame(animate);
        }, [targetValue, loading]);

        return count;
    };

    const StatCard = ({ 
        title, 
        value, 
        trend, 
        icon: Icon, 
        color = 'blue', 
        onClick,
        formatter = (val) => val?.toLocaleString() 
    }) => {
        const animatedValue = useAnimatedCounter(value);
        
        const colorClasses = {
            blue: 'text-blue-600 bg-blue-100',
            green: 'text-green-600 bg-green-100',
            yellow: 'text-yellow-600 bg-yellow-100',
            red: 'text-red-600 bg-red-100',
            purple: 'text-purple-600 bg-purple-100',
            gray: 'text-gray-600 bg-gray-100'
        };

        const getTrendIcon = () => {
            if (!showTrends || trend === 0) return null;
            return trend > 0 ? 
                <TrendingUpIcon className="w-4 h-4 text-green-500" /> :
                <TrendingDownIcon className="w-4 h-4 text-red-500" />;
        };

        const getTrendText = () => {
            if (!showTrends || trend === 0) return null;
            return (
                <span className={`text-xs ${trend > 0 ? 'text-green-600' : 'text-red-600'}`}>
                    {trend > 0 ? '+' : ''}{trend}% from last week
                </span>
            );
        };

        return (
            <div 
                className={`
                    bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition-all duration-200
                    ${onClick ? 'cursor-pointer hover:border-blue-300' : ''}
                `}
                onClick={() => onClick?.(title.toLowerCase().replace(/\s+/g, '_'))}
            >
                <div className="flex items-center justify-between mb-3">
                    <div className={`p-2 rounded-lg ${colorClasses[color]}`}>
                        <Icon className="w-5 h-5" />
                    </div>
                    {getTrendIcon()}
                </div>
                
                <div className="space-y-1">
                    <h3 className="text-sm font-medium text-gray-600">{title}</h3>
                    <p className="text-2xl font-bold text-gray-900">
                        {loading ? '---' : formatter(animatedValue)}
                    </p>
                    {getTrendText()}
                </div>
            </div>
        );
    };

    // Memoized stats configuration
    const statsConfig = useMemo(() => [
        {
            title: 'Total Employees',
            value: statsData.employees.current,
            trend: statsData.employees.trend,
            icon: UsersIcon,
            color: 'blue',
            key: 'employees'
        },
        {
            title: 'Active Employees',
            value: statsData.active_employees.current,
            trend: statsData.active_employees.trend,
            icon: UsersIcon,
            color: 'green',
            key: 'active_employees'
        },
        {
            title: 'Certificate Types',
            value: statsData.certificate_types.current,
            trend: statsData.certificate_types.trend,
            icon: AcademicCapIcon,
            color: 'purple',
            key: 'certificate_types'
        },
        {
            title: 'Active Certificates',
            value: statsData.active_certificates.current,
            trend: statsData.active_certificates.trend,
            icon: DocumentCheckIcon,
            color: 'green',
            key: 'active_certificates'
        },
        {
            title: 'Expired Certificates',
            value: statsData.expired_certificates.current,
            trend: statsData.expired_certificates.trend,
            icon: ExclamationTriangleIcon,
            color: 'red',
            key: 'expired_certificates'
        },
        {
            title: 'Expiring Soon',
            value: statsData.expiring_certificates.current,
            trend: statsData.expiring_certificates.trend,
            icon: ClockIcon,
            color: 'yellow',
            key: 'expiring_certificates'
        },
        {
            title: 'Departments',
            value: statsData.departments.current,
            trend: statsData.departments.trend,
            icon: BuildingOfficeIcon,
            color: 'blue',
            key: 'departments'
        },
        {
            title: 'Compliance Rate',
            value: statsData.compliance_rate.current,
            trend: statsData.compliance_rate.trend,
            icon: DocumentCheckIcon,
            color: statsData.compliance_rate.current >= 90 ? 'green' : 
                   statsData.compliance_rate.current >= 75 ? 'yellow' : 'red',
            key: 'compliance_rate',
            formatter: (val) => `${val}%`
        }
    ], [statsData]);

    return (
        <div className={`bg-white rounded-lg border border-gray-200 ${className}`}>
            <div className="p-4 border-b border-gray-200">
                <div className="flex items-center justify-between">
                    <h3 className="text-lg font-semibold text-gray-900">Quick Stats</h3>
                    
                    {statsData.last_updated && (
                        <span className="text-sm text-gray-500">
                            Updated {new Date(statsData.last_updated).toLocaleTimeString()}
                        </span>
                    )}
                </div>
            </div>

            <div className="p-4">
                {error ? (
                    <div className="text-center py-8">
                        <ExclamationTriangleIcon className="w-8 h-8 text-red-500 mx-auto mb-2" />
                        <p className="text-sm text-red-600">{error}</p>
                        <button
                            onClick={fetchStatsData}
                            className="mt-2 text-sm text-blue-600 hover:text-blue-800"
                        >
                            Try again
                        </button>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {statsConfig.map((stat) => (
                            <StatCard
                                key={stat.key}
                                title={stat.title}
                                value={stat.value}
                                trend={stat.trend}
                                icon={stat.icon}
                                color={stat.color}
                                formatter={stat.formatter}
                                onClick={onStatClick}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Refresh indicator */}
            {autoRefresh && !error && (
                <div className="px-4 py-2 bg-gray-50 border-t border-gray-200">
                    <div className="flex items-center justify-center text-xs text-gray-500">
                        <div className="flex items-center space-x-2">
                            <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span>Auto-refreshing every {Math.round(refreshInterval / 1000)}s</span>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}