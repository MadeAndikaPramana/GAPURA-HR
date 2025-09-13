import { useState, useEffect, useMemo } from 'react';
import { 
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    UserGroupIcon,
    DocumentCheckIcon,
    ClockIcon,
    ChartPieIcon
} from '@heroicons/react/24/outline';

/**
 * ComplianceOverviewWidget - Shows compliance status across all mandatory certificates
 * Features real-time updates and drill-down capabilities
 */
export default function ComplianceOverviewWidget({
    className = '',
    autoRefresh = true,
    refreshInterval = 60000, // 1 minute
    showDetails = true,
    onDrillDown
}) {
    const [complianceData, setComplianceData] = useState({
        overall_compliance: 0,
        total_employees: 0,
        compliant_employees: 0,
        non_compliant_employees: 0,
        certificate_types: [],
        expiring_soon: 0,
        last_updated: null
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchComplianceData();
        
        if (autoRefresh) {
            const interval = setInterval(fetchComplianceData, refreshInterval);
            return () => clearInterval(interval);
        }
    }, [autoRefresh, refreshInterval]);

    const fetchComplianceData = async () => {
        try {
            const response = await fetch('/api/compliance/overview');
            if (!response.ok) throw new Error('Failed to fetch compliance data');
            
            const data = await response.json();
            setComplianceData(data);
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    // Memoized calculations
    const complianceMetrics = useMemo(() => {
        const { overall_compliance, total_employees, compliant_employees, expiring_soon } = complianceData;
        
        return {
            complianceRate: Math.round(overall_compliance),
            complianceStatus: overall_compliance >= 90 ? 'excellent' : 
                             overall_compliance >= 75 ? 'good' :
                             overall_compliance >= 50 ? 'warning' : 'critical',
            totalEmployees: total_employees,
            compliantEmployees: compliant_employees,
            nonCompliantEmployees: total_employees - compliant_employees,
            expiringCount: expiring_soon,
            riskLevel: expiring_soon > 10 ? 'high' : 
                      expiring_soon > 5 ? 'medium' : 'low'
        };
    }, [complianceData]);

    const getComplianceColor = (status) => {
        const colors = {
            excellent: 'text-green-600 bg-green-100',
            good: 'text-blue-600 bg-blue-100',
            warning: 'text-yellow-600 bg-yellow-100',
            critical: 'text-red-600 bg-red-100'
        };
        return colors[status] || colors.critical;
    };

    const getComplianceIcon = (status) => {
        const icons = {
            excellent: ShieldCheckIcon,
            good: DocumentCheckIcon,
            warning: ExclamationTriangleIcon,
            critical: ExclamationTriangleIcon
        };
        const Icon = icons[status] || ExclamationTriangleIcon;
        return <Icon className="w-5 h-5" />;
    };

    const CircularProgress = ({ percentage, size = 80, strokeWidth = 8 }) => {
        const radius = (size - strokeWidth) / 2;
        const circumference = radius * 2 * Math.PI;
        const strokeDasharray = circumference;
        const strokeDashoffset = circumference - (percentage / 100) * circumference;

        return (
            <div className="relative inline-flex items-center justify-center">
                <svg width={size} height={size} className="transform -rotate-90">
                    {/* Background circle */}
                    <circle
                        cx={size / 2}
                        cy={size / 2}
                        r={radius}
                        stroke="currentColor"
                        strokeWidth={strokeWidth}
                        fill="transparent"
                        className="text-gray-200"
                    />
                    {/* Progress circle */}
                    <circle
                        cx={size / 2}
                        cy={size / 2}
                        r={radius}
                        stroke="currentColor"
                        strokeWidth={strokeWidth}
                        fill="transparent"
                        strokeLinecap="round"
                        strokeDasharray={strokeDasharray}
                        strokeDashoffset={strokeDashoffset}
                        className={
                            percentage >= 90 ? 'text-green-500' :
                            percentage >= 75 ? 'text-blue-500' :
                            percentage >= 50 ? 'text-yellow-500' : 'text-red-500'
                        }
                        style={{
                            transition: 'stroke-dashoffset 0.5s ease-in-out',
                        }}
                    />
                </svg>
                <div className="absolute inset-0 flex items-center justify-center">
                    <span className="text-xl font-bold text-gray-900">
                        {Math.round(percentage)}%
                    </span>
                </div>
            </div>
        );
    };

    const MetricCard = ({ title, value, icon: Icon, color = 'text-gray-600', onClick }) => (
        <div 
            className={`p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors ${onClick ? 'cursor-pointer' : ''}`}
            onClick={onClick}
        >
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm font-medium text-gray-600">{title}</p>
                    <p className={`text-2xl font-bold ${color}`}>
                        {loading ? '---' : value?.toLocaleString() || 0}
                    </p>
                </div>
                <Icon className={`w-8 h-8 ${color}`} />
            </div>
        </div>
    );

    return (
        <div className={`bg-white rounded-lg border border-gray-200 ${className}`}>
            <div className="p-4 border-b border-gray-200">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                        <ShieldCheckIcon className="w-6 h-6 text-green-600" />
                        <h3 className="text-lg font-semibold text-gray-900">Compliance Overview</h3>
                    </div>
                    
                    <div className={`inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium ${getComplianceColor(complianceMetrics.complianceStatus)}`}>
                        {getComplianceIcon(complianceMetrics.complianceStatus)}
                        <span className="ml-1.5 capitalize">{complianceMetrics.complianceStatus}</span>
                    </div>
                </div>
            </div>

            <div className="p-4">
                {error ? (
                    <div className="text-center py-8">
                        <ExclamationTriangleIcon className="w-8 h-8 text-red-500 mx-auto mb-2" />
                        <p className="text-sm text-red-600">{error}</p>
                        <button
                            onClick={fetchComplianceData}
                            className="mt-2 text-sm text-blue-600 hover:text-blue-800"
                        >
                            Try again
                        </button>
                    </div>
                ) : (
                    <div className="space-y-6">
                        {/* Overall Compliance Circle */}
                        <div className="flex items-center justify-center">
                            <CircularProgress 
                                percentage={complianceMetrics.complianceRate} 
                                size={120}
                                strokeWidth={10}
                            />
                        </div>

                        {/* Key Metrics */}
                        <div className="grid grid-cols-2 gap-3">
                            <MetricCard
                                title="Total Employees"
                                value={complianceMetrics.totalEmployees}
                                icon={UserGroupIcon}
                                color="text-blue-600"
                                onClick={() => onDrillDown?.('all-employees')}
                            />
                            <MetricCard
                                title="Compliant"
                                value={complianceMetrics.compliantEmployees}
                                icon={DocumentCheckIcon}
                                color="text-green-600"
                                onClick={() => onDrillDown?.('compliant-employees')}
                            />
                        </div>

                        {showDetails && (
                            <div className="grid grid-cols-2 gap-3">
                                <MetricCard
                                    title="Non-Compliant"
                                    value={complianceMetrics.nonCompliantEmployees}
                                    icon={ExclamationTriangleIcon}
                                    color="text-red-600"
                                    onClick={() => onDrillDown?.('non-compliant-employees')}
                                />
                                <MetricCard
                                    title="Expiring Soon"
                                    value={complianceMetrics.expiringCount}
                                    icon={ClockIcon}
                                    color="text-yellow-600"
                                    onClick={() => onDrillDown?.('expiring-certificates')}
                                />
                            </div>
                        )}

                        {/* Certificate Types Breakdown */}
                        {showDetails && complianceData.certificate_types?.length > 0 && (
                            <div className="border-t border-gray-100 pt-4">
                                <h4 className="text-sm font-medium text-gray-900 mb-3">
                                    Mandatory Certificate Types
                                </h4>
                                <div className="space-y-2">
                                    {complianceData.certificate_types.slice(0, 3).map((cert, index) => (
                                        <div 
                                            key={index}
                                            className="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100"
                                            onClick={() => onDrillDown?.('certificate-type', cert.id)}
                                        >
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">
                                                    {cert.name}
                                                </p>
                                                <p className="text-xs text-gray-600">
                                                    {cert.compliant_employees} / {cert.total_employees} compliant
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <span className={`text-sm font-bold ${
                                                    cert.compliance_rate >= 90 ? 'text-green-600' :
                                                    cert.compliance_rate >= 75 ? 'text-blue-600' :
                                                    cert.compliance_rate >= 50 ? 'text-yellow-600' : 'text-red-600'
                                                }`}>
                                                    {Math.round(cert.compliance_rate)}%
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                    
                                    {complianceData.certificate_types.length > 3 && (
                                        <button 
                                            onClick={() => onDrillDown?.('all-certificate-types')}
                                            className="w-full text-sm text-blue-600 hover:text-blue-800 py-2"
                                        >
                                            View all {complianceData.certificate_types.length} certificate types
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Last Updated */}
                        {complianceData.last_updated && (
                            <div className="text-xs text-gray-500 text-center pt-2 border-t border-gray-100">
                                Last updated: {new Date(complianceData.last_updated).toLocaleTimeString()}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}