// components/ContainerManagement/AnalyticsDashboard.jsx
import { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import {
    ChartBarIcon,
    UsersIcon,
    AcademicCapIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    TrendingUpIcon,
    TrendingDownIcon,
    ArrowRefreshIcon,
    CalendarIcon,
    BuildingOfficeIcon,
    DocumentChartBarIcon
} from '@heroicons/react/24/outline';

// Chart components (would typically use a library like Chart.js or Recharts)
const ProgressBar = ({ value, max, label, color = 'blue', showPercentage = true }) => {
    const percentage = max > 0 ? (value / max) * 100 : 0;
    const colorClasses = {
        blue: 'bg-blue-500',
        green: 'bg-green-500',
        yellow: 'bg-yellow-500',
        red: 'bg-red-500',
        purple: 'bg-purple-500'
    };

    return (
        <div className="w-full">
            <div className="flex justify-between items-center mb-1">
                <span className="text-sm font-medium text-gray-700">{label}</span>
                {showPercentage && (
                    <span className="text-sm text-gray-500">{percentage.toFixed(1)}%</span>
                )}
            </div>
            <div className="w-full bg-gray-200 rounded-full h-2">
                <div
                    className={`h-2 rounded-full transition-all duration-300 ${colorClasses[color]}`}
                    style={{ width: `${Math.min(percentage, 100)}%` }}
                />
            </div>
            <div className="flex justify-between items-center mt-1">
                <span className="text-xs text-gray-500">{value} of {max}</span>
            </div>
        </div>
    );
};

const MetricCard = ({ title, value, change, changeType, icon: Icon, color = 'blue', subtitle }) => {
    const colorClasses = {
        blue: 'bg-blue-50 text-blue-600',
        green: 'bg-green-50 text-green-600',
        yellow: 'bg-yellow-50 text-yellow-600',
        red: 'bg-red-50 text-red-600',
        purple: 'bg-purple-50 text-purple-600'
    };

    const changeIcon = changeType === 'increase' ? TrendingUpIcon : TrendingDownIcon;
    const changeColor = changeType === 'increase' ? 'text-green-600' : 'text-red-600';
    const ChangeIcon = changeIcon;

    return (
        <div className="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-sm transition-shadow">
            <div className="flex items-center justify-between">
                <div className="flex-1">
                    <p className="text-sm font-medium text-gray-600">{title}</p>
                    <p className="text-3xl font-bold text-gray-900 mt-1">{value}</p>
                    {subtitle && (
                        <p className="text-sm text-gray-500 mt-1">{subtitle}</p>
                    )}
                    {change && (
                        <div className="flex items-center mt-2">
                            <ChangeIcon className={`w-4 h-4 mr-1 ${changeColor}`} />
                            <span className={`text-sm font-medium ${changeColor}`}>
                                {Math.abs(change)}% from last month
                            </span>
                        </div>
                    )}
                </div>
                <div className={`p-3 rounded-full ${colorClasses[color]}`}>
                    <Icon className="w-6 h-6" />
                </div>
            </div>
        </div>
    );
};

const ComplianceChart = ({ data, title }) => {
    const total = data.compliant + data.non_compliant;
    const complianceRate = total > 0 ? (data.compliant / total) * 100 : 0;

    return (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
            
            {/* Donut Chart Simulation */}
            <div className="flex items-center justify-center mb-4">
                <div className="relative w-32 h-32">
                    <svg className="w-32 h-32 transform -rotate-90" viewBox="0 0 32 32">
                        <circle
                            cx="16"
                            cy="16"
                            r="14"
                            fill="none"
                            stroke="#f3f4f6"
                            strokeWidth="4"
                        />
                        <circle
                            cx="16"
                            cy="16"
                            r="14"
                            fill="none"
                            stroke="#10b981"
                            strokeWidth="4"
                            strokeDasharray={`${complianceRate * 0.88} 88`}
                            strokeLinecap="round"
                        />
                    </svg>
                    <div className="absolute inset-0 flex items-center justify-center">
                        <div className="text-center">
                            <div className="text-2xl font-bold text-gray-900">
                                {complianceRate.toFixed(1)}%
                            </div>
                            <div className="text-xs text-gray-500">Compliant</div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Legend */}
            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <div className="w-3 h-3 bg-green-500 rounded-full mr-2" />
                        <span className="text-sm text-gray-600">Compliant</span>
                    </div>
                    <span className="text-sm font-medium text-gray-900">{data.compliant}</span>
                </div>
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <div className="w-3 h-3 bg-red-500 rounded-full mr-2" />
                        <span className="text-sm text-gray-600">Non-Compliant</span>
                    </div>
                    <span className="text-sm font-medium text-gray-900">{data.non_compliant}</span>
                </div>
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <div className="w-3 h-3 bg-yellow-500 rounded-full mr-2" />
                        <span className="text-sm text-gray-600">Expiring Soon</span>
                    </div>
                    <span className="text-sm font-medium text-gray-900">{data.expiring_soon || 0}</span>
                </div>
            </div>
        </div>
    );
};

const DepartmentComplianceTable = ({ departments, onDepartmentClick }) => {
    const sortedDepartments = departments.sort((a, b) => b.compliance_rate - a.compliance_rate);

    const getComplianceColor = (rate) => {
        if (rate >= 90) return 'text-green-600 bg-green-50';
        if (rate >= 70) return 'text-yellow-600 bg-yellow-50';
        return 'text-red-600 bg-red-50';
    };

    return (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Department Compliance</h3>
            
            <div className="overflow-x-auto">
                <table className="min-w-full">
                    <thead>
                        <tr className="border-b border-gray-200">
                            <th className="text-left py-3 px-2 text-sm font-semibold text-gray-900">
                                Department
                            </th>
                            <th className="text-center py-3 px-2 text-sm font-semibold text-gray-900">
                                Employees
                            </th>
                            <th className="text-center py-3 px-2 text-sm font-semibold text-gray-900">
                                Compliance Rate
                            </th>
                            <th className="text-center py-3 px-2 text-sm font-semibold text-gray-900">
                                Expiring Soon
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {sortedDepartments.map((dept) => (
                            <tr
                                key={dept.id}
                                className="border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors"
                                onClick={() => onDepartmentClick?.(dept)}
                            >
                                <td className="py-3 px-2">
                                    <div className="flex items-center">
                                        <BuildingOfficeIcon className="w-4 h-4 text-gray-400 mr-2" />
                                        <span className="font-medium text-gray-900">{dept.name}</span>
                                    </div>
                                </td>
                                <td className="text-center py-3 px-2 text-sm text-gray-600">
                                    {dept.employee_count}
                                </td>
                                <td className="text-center py-3 px-2">
                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                        getComplianceColor(dept.compliance_rate)
                                    }`}>
                                        {dept.compliance_rate.toFixed(1)}%
                                    </span>
                                </td>
                                <td className="text-center py-3 px-2">
                                    {dept.expiring_soon > 0 ? (
                                        <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-yellow-800 bg-yellow-100">
                                            {dept.expiring_soon}
                                        </span>
                                    ) : (
                                        <span className="text-sm text-gray-400">0</span>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

const CertificateTypeAnalytics = ({ certificateTypes }) => {
    return (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Certificate Type Analytics</h3>
            
            <div className="space-y-4">
                {certificateTypes.map((type) => (
                    <div key={type.id} className="border border-gray-100 rounded-lg p-4">
                        <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center">
                                <AcademicCapIcon className="w-4 h-4 text-gray-400 mr-2" />
                                <span className="font-medium text-gray-900">{type.name}</span>
                                {type.is_mandatory && (
                                    <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-red-700 bg-red-100">
                                        Mandatory
                                    </span>
                                )}
                            </div>
                            <span className="text-sm text-gray-500">
                                {type.total_certificates} certificates
                            </span>
                        </div>
                        
                        <div className="grid grid-cols-3 gap-4 mb-3">
                            <div className="text-center">
                                <div className="text-lg font-semibold text-green-600">
                                    {type.active_certificates}
                                </div>
                                <div className="text-xs text-gray-500">Active</div>
                            </div>
                            <div className="text-center">
                                <div className="text-lg font-semibold text-yellow-600">
                                    {type.expiring_certificates}
                                </div>
                                <div className="text-xs text-gray-500">Expiring</div>
                            </div>
                            <div className="text-center">
                                <div className="text-lg font-semibold text-red-600">
                                    {type.expired_certificates}
                                </div>
                                <div className="text-xs text-gray-500">Expired</div>
                            </div>
                        </div>
                        
                        <ProgressBar
                            value={type.active_certificates}
                            max={type.total_certificates}
                            label="Compliance Rate"
                            color={type.compliance_rate >= 90 ? 'green' : type.compliance_rate >= 70 ? 'yellow' : 'red'}
                        />
                    </div>
                ))}
            </div>
        </div>
    );
};

const TrendChart = ({ data, title, metric }) => {
    const maxValue = Math.max(...data.map(d => d.value));
    
    return (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
            
            <div className="h-40 flex items-end space-x-1 mb-4">
                {data.map((point, index) => {
                    const height = maxValue > 0 ? (point.value / maxValue) * 100 : 0;
                    return (
                        <div key={index} className="flex-1 flex flex-col items-center">
                            <div
                                className="w-full bg-blue-500 rounded-t transition-all duration-300 hover:bg-blue-600"
                                style={{ height: `${Math.max(height, 2)}%` }}
                                title={`${point.label}: ${point.value}`}
                            />
                        </div>
                    );
                })}
            </div>
            
            <div className="flex justify-between text-xs text-gray-500">
                {data.map((point, index) => (
                    <span key={index} className="text-center">
                        {point.label}
                    </span>
                ))}
            </div>
        </div>
    );
};

export default function AnalyticsDashboard({ 
    refreshInterval = 300000, // 5 minutes
    onDataRefresh,
    className = "" 
}) {
    const [data, setData] = useState({
        overview: {
            total_employees: 0,
            total_certificates: 0,
            compliance_rate: 0,
            expiring_soon: 0,
            expired: 0
        },
        departments: [],
        certificate_types: [],
        trends: [],
        compliance_data: {
            compliant: 0,
            non_compliant: 0,
            expiring_soon: 0
        }
    });
    const [loading, setLoading] = useState(true);
    const [lastRefresh, setLastRefresh] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchDashboardData();
        
        const interval = setInterval(fetchDashboardData, refreshInterval);
        return () => clearInterval(interval);
    }, [refreshInterval]);

    const fetchDashboardData = async () => {
        try {
            setLoading(true);
            setError(null);
            
            const response = await fetch('/api/container-analytics/dashboard');
            
            if (!response.ok) {
                throw new Error('Failed to fetch dashboard data');
            }
            
            const dashboardData = await response.json();
            setData(dashboardData);
            setLastRefresh(new Date());
            
            if (onDataRefresh) {
                onDataRefresh(dashboardData);
            }
        } catch (err) {
            console.error('Dashboard data fetch error:', err);
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleRefresh = () => {
        fetchDashboardData();
    };

    const handleDepartmentClick = (department) => {
        // Navigate to department detail view or apply department filter
        console.log('Department clicked:', department);
    };

    if (loading && !data.overview.total_employees) {
        return (
            <div className={`flex items-center justify-center py-12 ${className}`}>
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p className="text-gray-600">Loading analytics dashboard...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className={`bg-red-50 border border-red-200 rounded-lg p-6 ${className}`}>
                <div className="flex items-center">
                    <ExclamationTriangleIcon className="w-6 h-6 text-red-600 mr-3" />
                    <div>
                        <h3 className="text-lg font-medium text-red-900">Dashboard Error</h3>
                        <p className="text-red-700 mt-1">{error}</p>
                        <button
                            onClick={handleRefresh}
                            className="mt-3 inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700"
                        >
                            <ArrowRefreshIcon className="w-4 h-4 mr-1" />
                            Retry
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className={`space-y-6 ${className}`}>
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Container Analytics</h1>
                    <p className="text-gray-600">Monitor compliance and certificate status across all containers</p>
                </div>
                
                <div className="flex items-center space-x-4">
                    {lastRefresh && (
                        <span className="text-sm text-gray-500">
                            Last updated: {lastRefresh.toLocaleTimeString()}
                        </span>
                    )}
                    <button
                        onClick={handleRefresh}
                        disabled={loading}
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <ArrowRefreshIcon className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                        Refresh
                    </button>
                </div>
            </div>

            {/* Overview Metrics */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <MetricCard
                    title="Total Employees"
                    value={data.overview.total_employees.toLocaleString()}
                    icon={UsersIcon}
                    color="blue"
                    change={data.overview.employee_growth}
                    changeType={data.overview.employee_growth > 0 ? 'increase' : 'decrease'}
                />
                
                <MetricCard
                    title="Total Certificates"
                    value={data.overview.total_certificates.toLocaleString()}
                    icon={AcademicCapIcon}
                    color="green"
                    change={data.overview.certificate_growth}
                    changeType={data.overview.certificate_growth > 0 ? 'increase' : 'decrease'}
                />
                
                <MetricCard
                    title="Compliance Rate"
                    value={`${data.overview.compliance_rate.toFixed(1)}%`}
                    icon={CheckCircleIcon}
                    color={data.overview.compliance_rate >= 90 ? 'green' : data.overview.compliance_rate >= 70 ? 'yellow' : 'red'}
                    change={data.overview.compliance_change}
                    changeType={data.overview.compliance_change > 0 ? 'increase' : 'decrease'}
                />
                
                <MetricCard
                    title="Expiring Soon"
                    value={data.overview.expiring_soon.toLocaleString()}
                    icon={ExclamationTriangleIcon}
                    color="yellow"
                    subtitle={`${data.overview.expired} expired`}
                />
            </div>

            {/* Charts Row */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <ComplianceChart
                    data={data.compliance_data}
                    title="Overall Compliance Status"
                />
                
                <TrendChart
                    data={data.trends}
                    title="Certificate Issuance Trends"
                    metric="certificates"
                />
            </div>

            {/* Department Compliance and Certificate Analytics */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <DepartmentComplianceTable
                    departments={data.departments}
                    onDepartmentClick={handleDepartmentClick}
                />
                
                <CertificateTypeAnalytics
                    certificateTypes={data.certificate_types}
                />
            </div>

            {/* Additional Insights */}
            <div className="bg-white rounded-lg border border-gray-200 p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Key Insights</h3>
                
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="text-center">
                        <div className="text-2xl font-bold text-blue-600 mb-2">
                            {data.insights?.avg_certificates_per_employee?.toFixed(1) || '0'}
                        </div>
                        <div className="text-sm text-gray-600">Avg Certificates per Employee</div>
                    </div>
                    
                    <div className="text-center">
                        <div className="text-2xl font-bold text-green-600 mb-2">
                            {data.insights?.most_compliant_department || 'N/A'}
                        </div>
                        <div className="text-sm text-gray-600">Most Compliant Department</div>
                    </div>
                    
                    <div className="text-center">
                        <div className="text-2xl font-bold text-red-600 mb-2">
                            {data.insights?.certificates_expiring_this_month || '0'}
                        </div>
                        <div className="text-sm text-gray-600">Expiring This Month</div>
                    </div>
                </div>
            </div>
        </div>
    );
}

ProgressBar.propTypes = {
    value: PropTypes.number.isRequired,
    max: PropTypes.number.isRequired,
    label: PropTypes.string.isRequired,
    color: PropTypes.string,
    showPercentage: PropTypes.bool
};

MetricCard.propTypes = {
    title: PropTypes.string.isRequired,
    value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    change: PropTypes.number,
    changeType: PropTypes.oneOf(['increase', 'decrease']),
    icon: PropTypes.elementType.isRequired,
    color: PropTypes.string,
    subtitle: PropTypes.string
};

ComplianceChart.propTypes = {
    data: PropTypes.object.isRequired,
    title: PropTypes.string.isRequired
};

DepartmentComplianceTable.propTypes = {
    departments: PropTypes.array.isRequired,
    onDepartmentClick: PropTypes.func
};

CertificateTypeAnalytics.propTypes = {
    certificateTypes: PropTypes.array.isRequired
};

TrendChart.propTypes = {
    data: PropTypes.array.isRequired,
    title: PropTypes.string.isRequired,
    metric: PropTypes.string.isRequired
};

AnalyticsDashboard.propTypes = {
    refreshInterval: PropTypes.number,
    onDataRefresh: PropTypes.func,
    className: PropTypes.string
};