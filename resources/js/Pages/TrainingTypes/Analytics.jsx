import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon,
    ChartBarIcon,
    UsersIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    BuildingOfficeIcon
} from '@heroicons/react/24/outline';
import {
    BarChart,
    Bar,
    PieChart,
    Pie,
    Cell,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer
} from 'recharts';

export default function Analytics({ auth, certificateType, analyticsData = {} }) {
    // Extract data with defaults
    const stats = analyticsData.statistics || {
        total_certificates: 0,
        active_certificates: 0,
        expired_certificates: 0,
        expiring_soon_certificates: 0,
        unique_employees: 0,
        compliance_rate: 0
    };

    const departmentData = analyticsData.by_department || [];
    const statusData = analyticsData.by_status || [];
    const trendData = analyticsData.trend || [];

    // Colors for charts
    const STATUS_COLORS = {
        active: '#10b981',
        expiring_soon: '#f59e0b',
        expired: '#ef4444',
        pending: '#3b82f6'
    };

    // Prepare pie chart data
    const pieData = [
        { name: 'Active', value: stats.active_certificates, color: STATUS_COLORS.active },
        { name: 'Expiring Soon', value: stats.expiring_soon_certificates, color: STATUS_COLORS.expiring_soon },
        { name: 'Expired', value: stats.expired_certificates, color: STATUS_COLORS.expired }
    ].filter(item => item.value > 0);

    // Prepare bar chart data from departments
    const barData = departmentData.map(dept => ({
        name: dept.department_name || 'No Department',
        total: dept.total || 0,
        active: dept.active || 0,
        expired: dept.expired || 0
    }));

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Analytics - ${certificateType.name}`} />

            <div className="min-h-screen bg-gray-50 py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Header */}
                    <div className="bg-white rounded-lg shadow-sm border p-6 mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <Link
                                    href={route('training-types.index')}
                                    className="text-blue-600 hover:text-blue-800 font-medium text-sm mb-2 inline-flex items-center"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-1" />
                                    Back to Training Types
                                </Link>
                                <h1 className="text-3xl font-bold text-gray-900 flex items-center mt-2">
                                    <ChartBarIcon className="w-8 h-8 text-blue-600 mr-3" />
                                    Analytics: {certificateType.name}
                                </h1>
                                <p className="text-gray-600 mt-1">
                                    Statistical overview and insights for this training type
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        {/* Total Certificates */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Total Certificates</p>
                                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.total_certificates}</p>
                                </div>
                                <div className="p-3 bg-blue-100 rounded-full">
                                    <ChartBarIcon className="w-8 h-8 text-blue-600" />
                                </div>
                            </div>
                        </div>

                        {/* Active */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Active</p>
                                    <p className="text-3xl font-bold text-green-600 mt-2">{stats.active_certificates}</p>
                                </div>
                                <div className="p-3 bg-green-100 rounded-full">
                                    <CheckCircleIcon className="w-8 h-8 text-green-600" />
                                </div>
                            </div>
                        </div>

                        {/* Expiring Soon */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Expiring Soon</p>
                                    <p className="text-3xl font-bold text-yellow-600 mt-2">{stats.expiring_soon_certificates}</p>
                                </div>
                                <div className="p-3 bg-yellow-100 rounded-full">
                                    <ClockIcon className="w-8 h-8 text-yellow-600" />
                                </div>
                            </div>
                        </div>

                        {/* Expired */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Expired</p>
                                    <p className="text-3xl font-bold text-red-600 mt-2">{stats.expired_certificates}</p>
                                </div>
                                <div className="p-3 bg-red-100 rounded-full">
                                    <XCircleIcon className="w-8 h-8 text-red-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Additional Stats */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Unique Employees</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-2">{stats.unique_employees}</p>
                                </div>
                                <UsersIcon className="w-10 h-10 text-gray-400" />
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Compliance Rate</p>
                                    <p className="text-2xl font-bold text-gray-900 mt-2">
                                        {stats.compliance_rate !== null ? `${stats.compliance_rate}%` : 'N/A'}
                                    </p>
                                </div>
                                <CheckCircleIcon className="w-10 h-10 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    {/* Charts */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        {/* Status Distribution - Pie Chart */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Status Distribution</h2>
                            {pieData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={300}>
                                    <PieChart>
                                        <Pie
                                            data={pieData}
                                            cx="50%"
                                            cy="50%"
                                            labelLine={false}
                                            label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                            outerRadius={80}
                                            fill="#8884d8"
                                            dataKey="value"
                                        >
                                            {pieData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                        <Legend />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex items-center justify-center h-64 text-gray-500">
                                    No data available
                                </div>
                            )}
                        </div>

                        {/* Department Distribution - Bar Chart */}
                        <div className="bg-white rounded-lg shadow-sm border p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Certificates by Department</h2>
                            {barData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={300}>
                                    <BarChart data={barData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="name" angle={-45} textAnchor="end" height={100} />
                                        <YAxis />
                                        <Tooltip />
                                        <Legend />
                                        <Bar dataKey="active" fill={STATUS_COLORS.active} name="Active" />
                                        <Bar dataKey="expired" fill={STATUS_COLORS.expired} name="Expired" />
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex items-center justify-center h-64 text-gray-500">
                                    No data available
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Training Type Details */}
                    <div className="bg-white rounded-lg shadow-sm border p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Training Type Details</h2>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p className="text-sm text-gray-600 mb-1">Code</p>
                                <p className="text-base font-medium text-gray-900">{certificateType.code || 'N/A'}</p>
                            </div>

                            <div>
                                <p className="text-sm text-gray-600 mb-1">Validity Period</p>
                                <p className="text-base font-medium text-gray-900">
                                    {certificateType.validity_months
                                        ? `${certificateType.validity_months} months`
                                        : 'No expiration'}
                                </p>
                            </div>

                            <div>
                                <p className="text-sm text-gray-600 mb-1">Warning Days</p>
                                <p className="text-base font-medium text-gray-900">
                                    {certificateType.warning_days || 'N/A'} days before expiry
                                </p>
                            </div>

                            <div>
                                <p className="text-sm text-gray-600 mb-1">Status</p>
                                <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
                                    certificateType.is_active
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-100 text-gray-800'
                                }`}>
                                    {certificateType.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>

                            {certificateType.estimated_cost && (
                                <div>
                                    <p className="text-sm text-gray-600 mb-1">Estimated Cost</p>
                                    <p className="text-base font-medium text-gray-900">
                                        Rp {parseInt(certificateType.estimated_cost).toLocaleString('id-ID')}
                                    </p>
                                </div>
                            )}

                            {certificateType.estimated_duration_hours && (
                                <div>
                                    <p className="text-sm text-gray-600 mb-1">Estimated Duration</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {certificateType.estimated_duration_hours} hours
                                    </p>
                                </div>
                            )}
                        </div>

                        {certificateType.description && (
                            <div className="mt-6 pt-6 border-t">
                                <p className="text-sm text-gray-600 mb-2">Description</p>
                                <p className="text-base text-gray-900">{certificateType.description}</p>
                            </div>
                        )}
                    </div>

                    {/* Quick Actions */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-medium text-blue-800">Quick Actions</h3>
                                <p className="text-sm text-blue-700 mt-1">View detailed information or manage this training type</p>
                            </div>
                            <div className="flex space-x-3">
                                <Link
                                    href={route('training-types.container', certificateType.id)}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                                >
                                    View Employees
                                </Link>
                                <Link
                                    href={route('training-types.edit', certificateType.id)}
                                    className="px-4 py-2 bg-white border border-blue-300 text-blue-700 rounded-lg text-sm font-medium hover:bg-blue-50 transition-colors"
                                >
                                    Edit Type
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
