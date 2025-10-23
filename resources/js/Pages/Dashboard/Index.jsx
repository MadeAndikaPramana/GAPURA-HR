import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    UsersIcon,
    AcademicCapIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    ArrowTrendingUpIcon,
    DocumentChartBarIcon,
    PlusIcon,
    FolderOpenIcon,
    BellAlertIcon,
    BuildingOfficeIcon
} from '@heroicons/react/24/outline';
import {
    PieChart,
    Pie,
    Cell,
    BarChart,
    Bar,
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer
} from 'recharts';

export default function Dashboard({ auth, statistics = {}, charts = {}, recentActivities = [] }) {
    // Default statistics
    const stats = {
        total_employees: statistics.total_employees || 0,
        total_certificates: statistics.total_certificates || 0,
        active_certificates: statistics.active_certificates || 0,
        expired_certificates: statistics.expired_certificates || 0,
        expiring_soon: statistics.expiring_soon || 0,
        compliance_rate: statistics.compliance_rate || 0,
        total_departments: statistics.total_departments || 0,
        total_training_types: statistics.total_training_types || 0,
    };

    // Chart data
    const statusData = charts.statusData || [
        { name: 'Active', value: stats.active_certificates, color: '#10b981' },
        { name: 'Expiring Soon', value: stats.expiring_soon, color: '#f59e0b' },
        { name: 'Expired', value: stats.expired_certificates, color: '#ef4444' },
    ].filter(item => item.value > 0);

    const departmentData = charts.departmentData || [];
    const trendData = charts.trendData || [];

    // Colors
    const COLORS = {
        active: '#10b981',
        expiring: '#f59e0b',
        expired: '#ef4444',
        primary: '#3b82f6'
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Dashboard" />

            <div className="min-h-screen bg-gray-50 py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
                        <p className="text-gray-600 mt-1">
                            Welcome back, {auth.user.name}! Here's an overview of your HR training system.
                        </p>
                    </div>

                    {/* Main Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        {/* Total Employees */}
                        <div className="bg-white rounded-lg shadow-sm border p-6 hover:shadow-md transition-shadow">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Total Employees</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.total_employees}</p>
                                    <Link
                                        href={route('sdm.index')}
                                        className="text-sm text-blue-600 hover:text-blue-800 mt-2 inline-block"
                                    >
                                        View all →
                                    </Link>
                                </div>
                                <div className="p-3 bg-blue-100 rounded-full">
                                    <UsersIcon className="w-8 h-8 text-blue-600" />
                                </div>
                            </div>
                        </div>

                        {/* Total Certificates */}
                        <div className="bg-white rounded-lg shadow-sm border p-6 hover:shadow-md transition-shadow">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Total Certificates</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.total_certificates}</p>
                                    <Link
                                        href={route('employee-containers.index')}
                                        className="text-sm text-blue-600 hover:text-blue-800 mt-2 inline-block"
                                    >
                                        View containers →
                                    </Link>
                                </div>
                                <div className="p-3 bg-purple-100 rounded-full">
                                    <AcademicCapIcon className="w-8 h-8 text-purple-600" />
                                </div>
                            </div>
                        </div>

                        {/* Active Certificates */}
                        <div className="bg-white rounded-lg shadow-sm border p-6 hover:shadow-md transition-shadow">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Active Certificates</p>
                                    <p className="text-3xl font-bold text-green-600 mt-2">{stats.active_certificates}</p>
                                    <p className="text-sm text-gray-500 mt-2">
                                        {stats.total_certificates > 0
                                            ? `${Math.round((stats.active_certificates / stats.total_certificates) * 100)}% of total`
                                            : 'No data'
                                        }
                                    </p>
                                </div>
                                <div className="p-3 bg-green-100 rounded-full">
                                    <CheckCircleIcon className="w-8 h-8 text-green-600" />
                                </div>
                            </div>
                        </div>

                        {/* Expiring Soon */}
                        <div className="bg-white rounded-lg shadow-sm border p-6 hover:shadow-md transition-shadow">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Expiring Soon</p>
                                    <p className="text-3xl font-bold text-yellow-600 mt-2">{stats.expiring_soon}</p>
                                    <p className="text-sm text-yellow-600 mt-2 font-medium">
                                        {stats.expiring_soon > 0 ? 'Requires attention!' : 'All good!'}
                                    </p>
                                </div>
                                <div className="p-3 bg-yellow-100 rounded-full">
                                    <ClockIcon className="w-8 h-8 text-yellow-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Secondary Statistics */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        {/* Expired Certificates */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Expired Certificates</p>
                                    <p className="text-2xl font-bold text-red-600 mt-2">{stats.expired_certificates}</p>
                                </div>
                                <ExclamationTriangleIcon className="w-10 h-10 text-red-400" />
                            </div>
                        </div>

                        {/* Compliance Rate */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Compliance Rate</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-2">{stats.compliance_rate}%</p>
                                </div>
                                <DocumentChartBarIcon className="w-10 h-10 text-gray-400" />
                            </div>
                        </div>

                        {/* Departments */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Departments</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-2">{stats.total_departments}</p>
                                </div>
                                <BuildingOfficeIcon className="w-10 h-10 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    {/* Charts Section */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        {/* Certificate Status Distribution */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <CheckCircleIcon className="w-5 h-5 mr-2 text-blue-600" />
                                Certificate Status Distribution
                            </h2>
                            {statusData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={300}>
                                    <PieChart>
                                        <Pie
                                            data={statusData}
                                            cx="50%"
                                            cy="50%"
                                            labelLine={false}
                                            label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                            outerRadius={100}
                                            fill="#8884d8"
                                            dataKey="value"
                                        >
                                            {statusData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                        <Legend />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex items-center justify-center h-64 text-gray-500">
                                    No certificate data available
                                </div>
                            )}
                        </div>

                        {/* Certificates by Department */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <BuildingOfficeIcon className="w-5 h-5 mr-2 text-blue-600" />
                                Certificates by Department
                            </h2>
                            {departmentData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={300}>
                                    <BarChart data={departmentData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="department" angle={-45} textAnchor="end" height={100} />
                                        <YAxis />
                                        <Tooltip />
                                        <Legend />
                                        <Bar dataKey="active" fill={COLORS.active} name="Active" />
                                        <Bar dataKey="expired" fill={COLORS.expired} name="Expired" />
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex items-center justify-center h-64 text-gray-500">
                                    No department data available
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Trend Chart (Full Width) */}
                    {trendData.length > 0 && (
                        <div className="bg-white rounded-lg shadow-sm border p-6 mb-8">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <ArrowTrendingUpIcon className="w-5 h-5 mr-2 text-blue-600" />
                                Training Completion Trend (Last 6 Months)
                            </h2>
                            <ResponsiveContainer width="100%" height={300}>
                                <LineChart data={trendData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="month" />
                                    <YAxis />
                                    <Tooltip />
                                    <Legend />
                                    <Line
                                        type="monotone"
                                        dataKey="completed"
                                        stroke={COLORS.active}
                                        strokeWidth={2}
                                        name="Certificates Issued"
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    )}

                    {/* Bottom Section: Recent Activities & Quick Actions */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Recent Activities (2/3 width) */}
                        <div className="lg:col-span-2 bg-white rounded-lg shadow-sm border p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <BellAlertIcon className="w-5 h-5 mr-2 text-blue-600" />
                                Recent Alerts & Notifications
                            </h2>

                            {recentActivities.length > 0 ? (
                                <div className="space-y-3">
                                    {recentActivities.slice(0, 5).map((activity, index) => (
                                        <div key={index} className="flex items-start p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                            <div className={`p-2 rounded-full mr-3 ${
                                                activity.type === 'expired' ? 'bg-red-100' :
                                                activity.type === 'expiring' ? 'bg-yellow-100' :
                                                'bg-blue-100'
                                            }`}>
                                                {activity.type === 'expired' ? (
                                                    <ExclamationTriangleIcon className="w-5 h-5 text-red-600" />
                                                ) : activity.type === 'expiring' ? (
                                                    <ClockIcon className="w-5 h-5 text-yellow-600" />
                                                ) : (
                                                    <CheckCircleIcon className="w-5 h-5 text-blue-600" />
                                                )}
                                            </div>
                                            <div className="flex-1">
                                                <p className="text-sm font-medium text-gray-900">{activity.title}</p>
                                                <p className="text-sm text-gray-600 mt-1">{activity.description}</p>
                                                <p className="text-xs text-gray-500 mt-1">{activity.time}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12 text-gray-500">
                                    <BellAlertIcon className="w-12 h-12 mx-auto mb-3 text-gray-400" />
                                    <p>No recent activities</p>
                                </div>
                            )}
                        </div>

                        {/* Quick Actions (1/3 width) */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>

                            <div className="space-y-3">
                                <Link
                                    href={route('sdm.create')}
                                    className="flex items-center p-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors"
                                >
                                    <PlusIcon className="w-5 h-5 mr-3" />
                                    <span className="font-medium">Add Employee</span>
                                </Link>

                                <Link
                                    href={route('training-types.create')}
                                    className="flex items-center p-3 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors"
                                >
                                    <PlusIcon className="w-5 h-5 mr-3" />
                                    <span className="font-medium">Add Training Type</span>
                                </Link>

                                <Link
                                    href={route('employee-containers.index')}
                                    className="flex items-center p-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors"
                                >
                                    <FolderOpenIcon className="w-5 h-5 mr-3" />
                                    <span className="font-medium">View Containers</span>
                                </Link>

                                <Link
                                    href={route('system.compliance-report')}
                                    className="flex items-center p-3 bg-orange-50 text-orange-700 rounded-lg hover:bg-orange-100 transition-colors"
                                >
                                    <DocumentChartBarIcon className="w-5 h-5 mr-3" />
                                    <span className="font-medium">Generate Report</span>
                                </Link>

                                <Link
                                    href={route('sdm.import')}
                                    className="flex items-center p-3 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    <ArrowTrendingUpIcon className="w-5 h-5 mr-3" />
                                    <span className="font-medium">Import Data</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
