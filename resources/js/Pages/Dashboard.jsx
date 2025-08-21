// resources/js/Pages/Dashboard.jsx

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    UsersIcon,
    ClipboardDocumentListIcon,
    BuildingOfficeIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    ChartBarIcon,
    CalendarDaysIcon,
    BellIcon,
    DocumentArrowDownIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    TrophyIcon
} from '@heroicons/react/24/outline';

export default function Dashboard({
    auth,
    stats,
    complianceByDepartment,
    complianceByType,
    recentActivities,
    expiringCertificates
}) {
    const getComplianceColor = (rate) => {
        if (rate >= 90) return 'text-green-600';
        if (rate >= 80) return 'text-yellow-600';
        if (rate >= 70) return 'text-orange-600';
        return 'text-red-600';
    };

    const getComplianceProgressColor = (rate) => {
        if (rate >= 90) return 'bg-green-600';
        if (rate >= 80) return 'bg-yellow-600';
        if (rate >= 70) return 'bg-orange-600';
        return 'bg-red-600';
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID');
    };

    const getDaysUntilExpiry = (expiryDate) => {
        const today = new Date();
        const expiry = new Date(expiryDate);
        const diffTime = expiry - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays < 0) return `Expired ${Math.abs(diffDays)} days ago`;
        if (diffDays === 0) return 'Expires today';
        return `${diffDays} days remaining`;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Training Dashboard
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Overview of training records and compliance status
                        </p>
                    </div>
                    <div className="flex items-center space-x-4">
                        <select className="text-sm border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="365">Last year</option>
                        </select>
                        <button className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
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
                                    <p className="text-3xl font-bold">{stats.total_employees}</p>
                                    <p className="text-blue-100 text-xs mt-1">Active staff members</p>
                                </div>
                                <UsersIcon className="w-12 h-12 text-blue-200" />
                            </div>
                        </div>

                        <div className="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-green-100 text-sm font-medium">Total Trainings</p>
                                    <p className="text-3xl font-bold">{stats.total_training_records}</p>
                                    <p className="text-green-100 text-xs mt-1">All training records</p>
                                </div>
                                <ClipboardDocumentListIcon className="w-12 h-12 text-green-200" />
                            </div>
                        </div>

                        <div className="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-lg p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-emerald-100 text-sm font-medium">Valid Certificates</p>
                                    <p className="text-3xl font-bold">{stats.active_certificates}</p>
                                    <p className="text-emerald-100 text-xs mt-1">Currently active</p>
                                </div>
                                <CheckCircleIcon className="w-12 h-12 text-emerald-200" />
                            </div>
                        </div>

                        <div className="bg-gradient-to-r from-amber-500 to-amber-600 rounded-lg p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-amber-100 text-sm font-medium">Expiring Soon</p>
                                    <p className="text-3xl font-bold">{stats.expiring_soon}</p>
                                    <p className="text-amber-100 text-xs mt-1">Next 30 days</p>
                                </div>
                                <ExclamationTriangleIcon className="w-12 h-12 text-amber-200" />
                            </div>
                        </div>
                    </div>

                    {/* Charts Section */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        {/* Training Compliance by Type */}
                        <div className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-medium text-gray-900">Training Compliance by Type</h3>
                                    <Link
                                        href={route('training-types.index')}
                                        className="text-sm text-green-600 hover:text-green-900"
                                    >
                                        View All
                                    </Link>
                                </div>
                            </div>
                            <div className="p-6">
                                {/* Simple bar chart representation */}
                                <div className="space-y-4">
                                    {complianceByType && complianceByType.slice(0, 5).map((type, index) => (
                                        <div key={index} className="flex items-center justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center justify-between mb-1">
                                                    <span className="text-sm font-medium text-gray-900">
                                                        {type.training_type}
                                                    </span>
                                                    <span className="text-sm text-gray-500">
                                                        {type.active}/{type.total}
                                                    </span>
                                                </div>
                                                <div className="w-full bg-gray-200 rounded-full h-2">
                                                    <div
                                                        className={`h-2 rounded-full ${getComplianceProgressColor(type.compliance_rate)}`}
                                                        style={{ width: `${type.compliance_rate}%` }}
                                                    />
                                                </div>
                                                <div className="text-xs text-gray-500 mt-1">
                                                    {type.compliance_rate}% compliance
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Chart Legend */}
                                <div className="mt-6 pt-4 border-t border-gray-200">
                                    <div className="flex items-center justify-between text-xs text-gray-500">
                                        <div className="flex items-center space-x-4">
                                            <div className="flex items-center">
                                                <div className="w-3 h-3 bg-green-600 rounded mr-2"></div>
                                                <span>Active</span>
                                            </div>
                                            <div className="flex items-center">
                                                <div className="w-3 h-3 bg-red-600 rounded mr-2"></div>
                                                <span>Expired</span>
                                            </div>
                                        </div>
                                        <div>Updated {formatDate(new Date())}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Department Compliance */}
                        <div className="bg-white rounded-lg shadow">
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
                                    {complianceByDepartment && complianceByDepartment.slice(0, 5).map((dept, index) => (
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
                                                        className={`h-2 rounded-full ${getComplianceProgressColor(dept.compliance_rate)}`}
                                                        style={{ width: `${dept.compliance_rate}%` }}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Bottom Section - Activities and Alerts */}
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
                                    <div className="flow-root">
                                        <ul className="-mb-8">
                                            {recentActivities.slice(0, 5).map((activity, index) => (
                                                <li key={activity.id}>
                                                    <div className="relative pb-8">
                                                        {index !== recentActivities.slice(0, 5).length - 1 && (
                                                            <span className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" />
                                                        )}
                                                        <div className="relative flex space-x-3">
                                                            <div>
                                                                <span className="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                                                    <ClipboardDocumentListIcon className="w-4 h-4 text-white" />
                                                                </span>
                                                            </div>
                                                            <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                                <div>
                                                                    <p className="text-sm text-gray-900">
                                                                        <span className="font-medium">{activity.employee_name}</span> completed{' '}
                                                                        <span className="font-medium">{activity.training_type}</span>
                                                                    </p>
                                                                    <p className="text-xs text-gray-500">
                                                                        Status: {activity.status.replace('_', ' ')}
                                                                    </p>
                                                                </div>
                                                                <div className="text-right text-sm whitespace-nowrap text-gray-500">
                                                                    <time>{activity.created_at}</time>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ) : (
                                    <div className="text-center text-gray-500 py-4">
                                        <ClipboardDocumentListIcon className="w-8 h-8 mx-auto mb-2 text-gray-400" />
                                        <p className="text-sm">No recent activities</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Expiring Certificates Alert */}
                        <div className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                        <ExclamationTriangleIcon className="w-5 h-5 text-yellow-500 mr-2" />
                                        Expiring Certificates
                                    </h3>
                                    <Link
                                        href={route('training-records.index', { status: 'expiring_soon' })}
                                        className="text-sm text-yellow-600 hover:text-yellow-900"
                                    >
                                        View All
                                    </Link>
                                </div>
                            </div>
                            <div className="px-6 py-4">
                                {expiringCertificates && expiringCertificates.length > 0 ? (
                                    <div className="space-y-4">
                                        {expiringCertificates.map((cert) => (
                                            <div key={cert.id} className="flex items-start space-x-3 p-3 bg-yellow-50 rounded-md border border-yellow-200">
                                                <ExclamationTriangleIcon className="w-5 h-5 text-yellow-500 mt-0.5" />
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900">
                                                        {cert.employee_name}
                                                    </p>
                                                    <p className="text-sm text-gray-600">
                                                        {cert.training_type} • {cert.certificate_number}
                                                    </p>
                                                    <p className="text-xs text-yellow-600">
                                                        Expires: {formatDate(cert.expiry_date)} • {getDaysUntilExpiry(cert.expiry_date)}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center text-gray-500 py-4">
                                        <CheckCircleIcon className="w-8 h-8 mx-auto mb-2 text-green-400" />
                                        <p className="text-sm">All certificates are up to date!</p>
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
                                href={route('import-export.index')}
                                className="flex items-center p-3 bg-white border border-gray-200 rounded-md hover:bg-gray-50 transition-colors"
                            >
                                <DocumentArrowDownIcon className="w-5 h-5 text-purple-600 mr-3" />
                                <span className="text-sm font-medium text-gray-900">Import Data</span>
                            </Link>
                            <Link
                                href={route('training-records.index', { status: 'expiring_soon' })}
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
