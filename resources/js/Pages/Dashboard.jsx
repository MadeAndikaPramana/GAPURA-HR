import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    UsersIcon,
    BuildingOfficeIcon,
    ClipboardDocumentListIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    XCircleIcon,
    DocumentArrowDownIcon,
    ChartBarIcon,
    BellIcon,
    ArrowRightIcon
} from '@heroicons/react/24/outline';

export default function Dashboard({
    auth,
    stats,
    complianceByDepartment = [],
    complianceByType = [],
    recentActivities = [],
    expiringCertificates = []
}) {
    // ✅ Fallback untuk stats untuk mencegah undefined error
    const safeStats = stats || {
        total_employees: 0,
        total_departments: 0,
        total_training_records: 0,
        active_certificates: 0,
        expiring_soon: 0,
        expired_certificates: 0,
        compliance_rate: 0
    };

    const [selectedPeriod, setSelectedPeriod] = useState(30);

    // Helper functions
    const getComplianceColor = (rate) => {
        if (rate >= 95) return 'text-green-600';
        if (rate >= 85) return 'text-blue-600';
        if (rate >= 75) return 'text-yellow-600';
        return 'text-red-600';
    };

    const getComplianceProgressColor = (rate) => {
        if (rate >= 95) return 'bg-green-500';
        if (rate >= 85) return 'bg-blue-500';
        if (rate >= 75) return 'bg-yellow-500';
        return 'bg-red-500';
    };

    const getStatusBadgeClass = (status) => {
        const statusMap = {
            'active': 'bg-green-100 text-green-800',
            'compliant': 'bg-green-100 text-green-800',
            'expiring_soon': 'bg-yellow-100 text-yellow-800',
            'expired': 'bg-red-100 text-red-800',
            'completed': 'bg-blue-100 text-blue-800',
            'in_progress': 'bg-purple-100 text-purple-800'
        };
        return statusMap[status] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Training Management Dashboard
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Overview of employee training compliance and system activities
                        </p>
                    </div>
                    <div className="flex items-center space-x-4">
                        <select
                            value={selectedPeriod}
                            onChange={(e) => setSelectedPeriod(e.target.value)}
                            className="border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                        >
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="365">Last year</option>
                        </select>
                        <button className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <DocumentArrowDownIcon className="w-4 h-4 mr-2" />
                            Export Report
                        </button>
                    </div>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Main Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-blue-100 text-sm font-medium">Total Employees</p>
                                    <p className="text-3xl font-bold">{safeStats.total_employees.toLocaleString()}</p>
                                    <p className="text-blue-100 text-xs mt-1">Active staff members</p>
                                </div>
                                <UsersIcon className="w-12 h-12 text-blue-200" />
                            </div>
                        </div>

                        <div className="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-green-100 text-sm font-medium">Total Trainings</p>
                                    <p className="text-3xl font-bold">{safeStats.total_training_records.toLocaleString()}</p>
                                    <p className="text-green-100 text-xs mt-1">All training records</p>
                                </div>
                                <ClipboardDocumentListIcon className="w-12 h-12 text-green-200" />
                            </div>
                        </div>

                        <div className="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-yellow-100 text-sm font-medium">Expiring Soon</p>
                                    <p className="text-3xl font-bold">{safeStats.expiring_soon.toLocaleString()}</p>
                                    <p className="text-yellow-100 text-xs mt-1">Certificates expiring</p>
                                </div>
                                <ClockIcon className="w-12 h-12 text-yellow-200" />
                            </div>
                        </div>

                        <div className="bg-gradient-to-r from-red-500 to-red-600 rounded-lg p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-red-100 text-sm font-medium">Expired</p>
                                    <p className="text-3xl font-bold">{safeStats.expired_certificates.toLocaleString()}</p>
                                    <p className="text-red-100 text-xs mt-1">Certificates expired</p>
                                </div>
                                <XCircleIcon className="w-12 h-12 text-red-200" />
                            </div>
                        </div>
                    </div>

                    {/* Compliance Overview */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                        {/* Compliance Rate Card */}
                        <div className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">Overall Compliance</h3>
                            </div>
                            <div className="px-6 py-6">
                                <div className="flex items-center justify-center">
                                    <div className="relative">
                                        <div className="w-32 h-32">
                                            <svg className="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                                <path
                                                    d="M18 2.0845
                                                      a 15.9155 15.9155 0 0 1 0 31.831
                                                      a 15.9155 15.9155 0 0 1 0 -31.831"
                                                    fill="none"
                                                    stroke="#E5E7EB"
                                                    strokeWidth="3"
                                                />
                                                <path
                                                    d="M18 2.0845
                                                      a 15.9155 15.9155 0 0 1 0 31.831
                                                      a 15.9155 15.9155 0 0 1 0 -31.831"
                                                    fill="none"
                                                    stroke="#10B981"
                                                    strokeWidth="3"
                                                    strokeDasharray={`${safeStats.compliance_rate}, 100`}
                                                />
                                            </svg>
                                            <div className="absolute inset-0 flex items-center justify-center">
                                                <div className="text-center">
                                                    <div className="text-2xl font-bold text-gray-900">{safeStats.compliance_rate}%</div>
                                                    <div className="text-sm text-gray-500">Compliant</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-6 grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div className="text-lg font-semibold text-green-600">{safeStats.active_certificates}</div>
                                        <div className="text-xs text-gray-500">Active</div>
                                    </div>
                                    <div>
                                        <div className="text-lg font-semibold text-yellow-600">{safeStats.expiring_soon}</div>
                                        <div className="text-xs text-gray-500">Expiring</div>
                                    </div>
                                    <div>
                                        <div className="text-lg font-semibold text-red-600">{safeStats.expired_certificates}</div>
                                        <div className="text-xs text-gray-500">Expired</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Quick Actions */}
                        <div className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">Quick Actions</h3>
                            </div>
                            <div className="px-6 py-4">
                                <div className="space-y-3">
                                    <Link
                                        href={route('training-records.create')}
                                        className="flex items-center justify-between p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors"
                                    >
                                        <div className="flex items-center">
                                            <div className="flex-shrink-0">
                                                <ClipboardDocumentListIcon className="w-5 h-5 text-green-600" />
                                            </div>
                                            <div className="ml-3">
                                                <div className="text-sm font-medium text-gray-900">Add Training Record</div>
                                                <div className="text-xs text-gray-500">Create new training entry</div>
                                            </div>
                                        </div>
                                        <ArrowRightIcon className="w-4 h-4 text-green-600" />
                                    </Link>

                                    <Link
                                        href={route('employees.create')}
                                        className="flex items-center justify-between p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
                                    >
                                        <div className="flex items-center">
                                            <div className="flex-shrink-0">
                                                <UsersIcon className="w-5 h-5 text-blue-600" />
                                            </div>
                                            <div className="ml-3">
                                                <div className="text-sm font-medium text-gray-900">Add Employee</div>
                                                <div className="text-xs text-gray-500">Register new staff member</div>
                                            </div>
                                        </div>
                                        <ArrowRightIcon className="w-4 h-4 text-blue-600" />
                                    </Link>

                                    <Link
                                        href={route('training-records.index', { status: 'expiring_soon' })}
                                        className="flex items-center justify-between p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors"
                                    >
                                        <div className="flex items-center">
                                            <div className="flex-shrink-0">
                                                <ClockIcon className="w-5 h-5 text-yellow-600" />
                                            </div>
                                            <div className="ml-3">
                                                <div className="text-sm font-medium text-gray-900">Review Expiring</div>
                                                <div className="text-xs text-gray-500">Check expiring certificates</div>
                                            </div>
                                        </div>
                                        <ArrowRightIcon className="w-4 h-4 text-yellow-600" />
                                    </Link>
                                </div>
                            </div>
                        </div>

                        {/* Alerts & Notifications */}
                        <div className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">Alerts</h3>
                            </div>
                            <div className="px-6 py-4">
                                <div className="space-y-3">
                                    {safeStats.expired_certificates > 0 && (
                                        <div className="flex items-center p-3 bg-red-50 rounded-lg">
                                            <XCircleIcon className="w-5 h-5 text-red-600 flex-shrink-0" />
                                            <div className="ml-3">
                                                <div className="text-sm font-medium text-red-800">
                                                    {safeStats.expired_certificates} certificates expired
                                                </div>
                                                <div className="text-xs text-red-600">Immediate action required</div>
                                            </div>
                                        </div>
                                    )}

                                    {safeStats.expiring_soon > 0 && (
                                        <div className="flex items-center p-3 bg-yellow-50 rounded-lg">
                                            <ExclamationTriangleIcon className="w-5 h-5 text-yellow-600 flex-shrink-0" />
                                            <div className="ml-3">
                                                <div className="text-sm font-medium text-yellow-800">
                                                    {safeStats.expiring_soon} certificates expiring soon
                                                </div>
                                                <div className="text-xs text-yellow-600">Plan for renewal</div>
                                            </div>
                                        </div>
                                    )}

                                    {safeStats.expired_certificates === 0 && safeStats.expiring_soon === 0 && (
                                        <div className="flex items-center p-3 bg-green-50 rounded-lg">
                                            <CheckCircleIcon className="w-5 h-5 text-green-600 flex-shrink-0" />
                                            <div className="ml-3">
                                                <div className="text-sm font-medium text-green-800">All certificates current</div>
                                                <div className="text-xs text-green-600">No immediate action needed</div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Department Compliance */}
                    {complianceByDepartment && complianceByDepartment.length > 0 && (
                        <div className="bg-white rounded-lg shadow mb-8">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-medium text-gray-900">Compliance by Department</h3>
                                    <Link
                                        href={route('departments.index')}
                                        className="text-sm text-green-600 hover:text-green-900"
                                    >
                                        View All
                                    </Link>
                                </div>
                            </div>
                            <div className="px-6 py-4">
                                <div className="space-y-4">
                                    {complianceByDepartment.map((dept, index) => (
                                        <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div className="flex-1">
                                                <div className="flex items-center justify-between mb-2">
                                                    <h4 className="text-sm font-medium text-gray-900">{dept.department_name || 'Unknown Department'}</h4>
                                                    <span className={`text-sm font-semibold ${getComplianceColor(dept.compliance_rate || 0)}`}>
                                                        {dept.compliance_rate || 0}%
                                                    </span>
                                                </div>
                                                <div className="w-full bg-gray-200 rounded-full h-2">
                                                    <div
                                                        className={`h-2 rounded-full ${getComplianceProgressColor(dept.compliance_rate || 0)}`}
                                                        style={{ width: `${dept.compliance_rate || 0}%` }}
                                                    />
                                                </div>
                                                <div className="flex justify-between text-xs text-gray-500 mt-1">
                                                    <span>{dept.total_employees || 0} employees</span>
                                                    <span>{dept.active_certificates || 0}/{dept.total_certificates || 0} certificates active</span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Recent Activities & Expiring Certificates */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* Recent Activities */}
                        <div className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-medium text-gray-900">Recent Activities</h3>
                                    <Link
                                        href={route('training-records.index')}
                                        className="text-sm text-green-600 hover:text-green-900"
                                    >
                                        View All
                                    </Link>
                                </div>
                            </div>
                            <div className="px-6 py-4">
                                {recentActivities && recentActivities.length > 0 ? (
                                    <div className="space-y-4">
                                        {recentActivities.slice(0, 5).map((activity, index) => (
                                            <div key={activity.id || index} className="flex items-center space-x-3">
                                                <div className="flex-shrink-0">
                                                    <div className={`w-2 h-2 rounded-full ${
                                                        activity.status === 'completed' ? 'bg-green-400' :
                                                        activity.status === 'active' ? 'bg-blue-400' :
                                                        activity.status === 'expired' ? 'bg-red-400' : 'bg-gray-400'
                                                    }`} />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 truncate">
                                                        {activity.employee_name || 'Unknown Employee'}
                                                    </p>
                                                    <p className="text-sm text-gray-500 truncate">
                                                        {activity.training_type || 'Unknown Training'}
                                                    </p>
                                                </div>
                                                <div className="flex-shrink-0">
                                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusBadgeClass(activity.status || 'unknown')}`}>
                                                        {activity.status || 'Unknown'}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-6">
                                        <ClipboardDocumentListIcon className="mx-auto h-12 w-12 text-gray-400" />
                                        <p className="mt-2 text-sm text-gray-500">No recent activities</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Expiring Certificates */}
                        <div className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-medium text-gray-900">Expiring Soon</h3>
                                    <Link
                                        href={route('training-records.index', { status: 'expiring_soon' })}
                                        className="text-sm text-green-600 hover:text-green-900"
                                    >
                                        View All
                                    </Link>
                                </div>
                            </div>
                            <div className="px-6 py-4">
                                {expiringCertificates && expiringCertificates.length > 0 ? (
                                    <div className="space-y-4">
                                        {expiringCertificates.slice(0, 5).map((cert, index) => (
                                            <div key={cert.id || index} className="flex items-center justify-between">
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 truncate">
                                                        {cert.employee_name || 'Unknown Employee'}
                                                    </p>
                                                    <p className="text-sm text-gray-500 truncate">
                                                        {cert.training_type || 'Unknown Training'}
                                                    </p>
                                                </div>
                                                <div className="flex-shrink-0 text-right">
                                                    <div className="text-sm font-medium text-yellow-600">
                                                        {cert.days_until_expiry || 0} days
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {cert.expiry_date || 'No date'}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-6">
                                        <CheckCircleIcon className="mx-auto h-12 w-12 text-green-400" />
                                        <p className="mt-2 text-sm text-gray-500">No certificates expiring soon</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
