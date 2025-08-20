import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    UsersIcon,
    ClipboardDocumentListIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    BuildingOfficeIcon,
    ChartBarIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    CalendarIcon,
    BellIcon,
    DocumentArrowDownIcon,
    EyeIcon
} from '@heroicons/react/24/outline';

export default function Dashboard({ auth, stats, complianceByDepartment, upcomingExpirations, recentActivities, monthlyTrends }) {
    const [selectedPeriod, setSelectedPeriod] = useState('30');

    const getComplianceColor = (rate) => {
        if (rate >= 90) return 'text-green-600';
        if (rate >= 80) return 'text-yellow-600';
        if (rate >= 70) return 'text-orange-600';
        return 'text-red-600';
    };

    const getComplianceBadgeColor = (rate) => {
        if (rate >= 90) return 'bg-green-100 text-green-800 border-green-200';
        if (rate >= 80) return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        if (rate >= 70) return 'bg-orange-100 text-orange-800 border-orange-200';
        return 'bg-red-100 text-red-800 border-red-200';
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    };

    const getTrendIcon = (trend) => {
        if (trend > 0) return <ArrowTrendingUpIcon className="w-4 h-4 text-green-500" />;
        if (trend < 0) return <ArrowTrendingDownIcon className="w-4 h-4 text-red-500" />;
        return <div className="w-4 h-4" />;
    };

    const getUrgencyColor = (daysLeft) => {
        if (daysLeft <= 7) return 'bg-red-100 text-red-800 border-red-200';
        if (daysLeft <= 14) return 'bg-orange-100 text-orange-800 border-orange-200';
        if (daysLeft <= 30) return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        return 'bg-blue-100 text-blue-800 border-blue-200';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Training Dashboard
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Overview of training records and compliance status
                        </p>
                    </div>
                    <div className="flex items-center space-x-3">
                        <select
                            value={selectedPeriod}
                            onChange={(e) => setSelectedPeriod(e.target.value)}
                            className="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-green-500 focus:border-green-500"
                        >
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                        </select>
                        <Link
                            href={route('dashboard.export')}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <DocumentArrowDownIcon className="w-4 h-4 mr-2" />
                            Export Report
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Main Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-blue-100 text-blue-600">
                                    <UsersIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4 flex-1">
                                    <p className="text-sm font-medium text-gray-600">Total Employees</p>
                                    <div className="flex items-center justify-between">
                                        <p className="text-2xl font-bold text-gray-900">{stats.total_employees}</p>
                                        {getTrendIcon(stats.employee_trend)}
                                    </div>
                                    <p className="text-xs text-gray-500">{stats.active_employees} active</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-green-100 text-green-600">
                                    <ClipboardDocumentListIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4 flex-1">
                                    <p className="text-sm font-medium text-gray-600">Total Trainings</p>
                                    <div className="flex items-center justify-between">
                                        <p className="text-2xl font-bold text-gray-900">{stats.total_certificates}</p>
                                        {getTrendIcon(stats.certificate_trend)}
                                    </div>
                                    <p className="text-xs text-gray-500">All certificates</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-green-100 text-green-600">
                                    <CheckCircleIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4 flex-1">
                                    <p className="text-sm font-medium text-gray-600">Valid Certificates</p>
                                    <div className="flex items-center justify-between">
                                        <p className="text-2xl font-bold text-gray-900">{stats.active_certificates}</p>
                                        {getTrendIcon(stats.active_trend)}
                                    </div>
                                    <p className="text-xs text-gray-500">{Math.round((stats.active_certificates / stats.total_certificates) * 100)}% of total</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-red-100 text-red-600">
                                    <ExclamationTriangleIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4 flex-1">
                                    <p className="text-sm font-medium text-gray-600">Expiring Soon</p>
                                    <div className="flex items-center justify-between">
                                        <p className="text-2xl font-bold text-gray-900">{stats.expiring_certificates}</p>
                                        {stats.expired_certificates > 0 && (
                                            <span className="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">
                                                +{stats.expired_certificates} expired
                                            </span>
                                        )}
                                    </div>
                                    <p className="text-xs text-gray-500">Next 30 days</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Compliance Overview */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                        {/* Department Compliance */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-medium text-gray-900">Department Compliance</h3>
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
                                    {complianceByDepartment.slice(0, 5).map((dept, index) => (
                                        <div key={index} className="flex items-center justify-between">
                                            <div className="flex items-center flex-1">
                                                <BuildingOfficeIcon className="w-5 h-5 text-gray-400 mr-3" />
                                                <div className="flex-1">
                                                    <p className="text-sm font-medium text-gray-900">{dept.department_name}</p>
                                                    <p className="text-xs text-gray-500">{dept.total_employees} employees</p>
                                                </div>
                                            </div>
                                            <div className="flex items-center ml-4">
                                                <span className={`text-sm font-medium ${getComplianceColor(dept.compliance_rate)}`}>
                                                    {dept.compliance_rate}%
                                                </span>
                                                <div className="ml-2 w-16 bg-gray-200 rounded-full h-2">
                                                    <div
                                                        className={`h-2 rounded-full ${
                                                            dept.compliance_rate >= 90 ? 'bg-green-600' :
                                                            dept.compliance_rate >= 80 ? 'bg-yellow-600' :
                                                            dept.compliance_rate >= 70 ? 'bg-orange-600' : 'bg-red-600'
                                                        }`}
                                                        style={{ width: `${dept.compliance_rate}%` }}
                                                    ></div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Training Activity Trends */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">Training Activity</h3>
                            </div>
                            <div className="px-6 py-4">
                                <div className="space-y-4">
                                    {monthlyTrends && monthlyTrends.length > 0 ? (
                                        monthlyTrends.slice(-6).map((trend, index) => (
                                            <div key={index} className="flex items-center justify-between">
                                                <div className="flex items-center">
                                                    <CalendarIcon className="w-5 h-5 text-gray-400 mr-3" />
                                                    <span className="text-sm font-medium text-gray-900">
                                                        {new Date(trend.year, trend.month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}
                                                    </span>
                                                </div>
                                                <div className="flex items-center">
                                                    <span className="text-sm text-gray-600 mr-2">{trend.certificates_issued} certificates</span>
                                                    <div className="w-12 bg-gray-200 rounded-full h-2">
                                                        <div
                                                            className="bg-green-600 h-2 rounded-full"
                                                            style={{ width: `${Math.min((trend.certificates_issued / Math.max(...monthlyTrends.map(t => t.certificates_issued))) * 100, 100)}%` }}
                                                        ></div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="text-center text-gray-500 py-4">
                                            <ChartBarIcon className="w-8 h-8 mx-auto mb-2 text-gray-400" />
                                            <p className="text-sm">No trend data available</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Upcoming Expirations & Recent Activities */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        {/* Upcoming Expirations */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center">
                                        <BellIcon className="w-5 h-5 text-yellow-500 mr-2" />
                                        <h3 className="text-lg font-medium text-gray-900">Upcoming Expirations</h3>
                                    </div>
                                    <Link
                                        href={route('training-records.expiring')}
                                        className="text-sm text-green-600 hover:text-green-900"
                                    >
                                        View All
                                    </Link>
                                </div>
                            </div>
                            <div className="px-6 py-4">
                                {upcomingExpirations && upcomingExpirations.length > 0 ? (
                                    <div className="space-y-3">
                                        {upcomingExpirations.slice(0, 5).map((expiration, index) => {
                                            const daysLeft = Math.ceil((new Date(expiration.expiry_date) - new Date()) / (1000 * 60 * 60 * 24));
                                            return (
                                                <div key={index} className="flex items-center justify-between py-2 border-l-4 border-yellow-400 pl-3">
                                                    <div className="flex-1">
                                                        <p className="text-sm font-medium text-gray-900">{expiration.employee_name}</p>
                                                        <p className="text-xs text-gray-500">{expiration.training_type_name}</p>
                                                        <p className="text-xs text-gray-400">Cert: {expiration.certificate_number}</p>
                                                    </div>
                                                    <div className="text-right">
                                                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border ${getUrgencyColor(daysLeft)}`}>
                                                            {daysLeft === 0 ? 'Today' :
                                                             daysLeft === 1 ? 'Tomorrow' :
                                                             daysLeft < 0 ? 'Overdue' :
                                                             `${daysLeft} days`}
                                                        </span>
                                                        <p className="text-xs text-gray-500 mt-1">{formatDate(expiration.expiry_date)}</p>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <div className="text-center text-gray-500 py-4">
                                        <CheckCircleIcon className="w-8 h-8 mx-auto mb-2 text-green-400" />
                                        <p className="text-sm">No upcoming expirations</p>
                                        <p className="text-xs">All certificates are up to date</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Recent Activities */}
                        <div className="bg-white shadow rounded-lg">
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
                                    <div className="space-y-3">
                                        {recentActivities.slice(0, 5).map((activity, index) => (
                                            <div key={index} className="flex items-center space-x-3">
                                                <div className={`w-2 h-2 rounded-full ${
                                                    activity.status === 'active' ? 'bg-green-500' :
                                                    activity.status === 'expiring_soon' ? 'bg-yellow-500' :
                                                    'bg-red-500'
                                                }`}></div>
                                                <div className="flex-1">
                                                    <p className="text-sm font-medium text-gray-900">{activity.employee_name}</p>
                                                    <p className="text-xs text-gray-500">{activity.training_type_name}</p>
                                                    <p className="text-xs text-gray-400">
                                                        {formatDate(activity.created_at)} • {activity.certificate_number}
                                                    </p>
                                                </div>
                                                <Link
                                                    href={route('training-records.show', activity.id)}
                                                    className="text-gray-400 hover:text-gray-600"
                                                >
                                                    <EyeIcon className="w-4 h-4" />
                                                </Link>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center text-gray-500 py-4">
                                        <ClipboardDocumentListIcon className="w-8 h-8 mx-auto mb-2 text-gray-400" />
                                        <p className="text-sm">No recent activities</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="mt-8 bg-gray-50 rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <Link
                                href={route('employees.create')}
                                className="flex items-center p-3 bg-white border border-gray-200 rounded-md hover:bg-gray-50 transition-colors"
                            >
                                <UsersIcon className="w-5 h-5 text-blue-600 mr-3" />
                                <span className="text-sm font-medium text-gray-900">Add Employee</span>
                            </Link>
                            <Link
                                href={route('training-records.create')}
                                className="flex items-center p-3 bg-white border border-gray-200 rounded-md hover:bg-gray-50 transition-colors"
                            >
                                <ClipboardDocumentListIcon className="w-5 h-5 text-green-600 mr-3" />
                                <span className="text-sm font-medium text-gray-900">Add Training</span>
                            </Link>
                            <Link
                                href={route('system.templates')}
                                className="flex items-center p-3 bg-white border border-gray-200 rounded-md hover:bg-gray-50 transition-colors"
                            >
                                <DocumentArrowDownIcon className="w-5 h-5 text-purple-600 mr-3" />
                                <span className="text-sm font-medium text-gray-900">Import Data</span>
                            </Link>
                            <Link
                                href={route('training-records.expiring')}
                                className="flex items-center p-3 bg-white border border-gray-200 rounded-md hover:bg-gray-50 transition-colors"
                            >
                                <BellIcon className="w-5 h-5 text-yellow-600 mr-3" />
                                <span className="text-sm font-medium text-gray-900">View Alerts</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
