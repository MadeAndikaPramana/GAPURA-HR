import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    UserGroupIcon,
    AcademicCapIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    DocumentTextIcon,
    ChartBarIcon,
    ArrowTrendingUpIcon,   // ✅ TAMBAH INI
    ArrowTrendingDownIcon, // ✅ TAMBAH INI
    CalendarIcon,
    BellIcon,
    CurrencyDollarIcon,
    StarIcon,
    ArrowRightIcon,
    CheckCircleIcon,
    XCircleIcon
} from '@heroicons/react/24/outline';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, LineChart, Line, PieChart, Pie, Cell } from 'recharts';

export default function HRDashboard({
    quickStats,
    departmentCompliance,
    completionTrends,
    trainingByCategory,
    urgentActions,
    recentActivities,
    providerPerformance
}) {
    const [selectedTimeRange, setSelectedTimeRange] = useState('30');
    const [showNotifications, setShowNotifications] = useState(false);

    // Colors for charts
    const COLORS = ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4'];

    // Format currency
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    };

    // Get status color
    const getStatusColor = (rate) => {
        if (rate >= 95) return 'text-green-600';
        if (rate >= 85) return 'text-blue-600';
        if (rate >= 75) return 'text-yellow-600';
        return 'text-red-600';
    };

    // Get trend icon
    const getTrendIcon = (current, previous) => {
        if (current > previous) {
            return <ArrowTrendingUpIconTrendingUpIcon className="h-4 w-4 text-green-500" />;
        } else if (current < previous) {
            return <ArrowTrendingDownIcon className="h-4 w-4 text-red-500" />;
        }
        return <div className="h-4 w-4" />;
    };

    // Calculate compliance trend
    const calculateComplianceTrend = () => {
        const currentMonth = completionTrends[completionTrends.length - 1];
        const previousMonth = completionTrends[completionTrends.length - 2];

        if (!currentMonth || !previousMonth) return 0;

        return ((currentMonth.completed_count - previousMonth.completed_count) / previousMonth.completed_count * 100).toFixed(1);
    };

    return (
        <AuthenticatedLayout>
            <Head title="HR Training Dashboard" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="md:flex md:items-center md:justify-between mb-6">
                        <div className="min-w-0 flex-1">
                            <h2 className="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                                Training Dashboard
                            </h2>
                            <p className="mt-1 text-sm text-gray-500">
                                Overview of training records and compliance status
                            </p>
                        </div>
                        <div className="mt-4 flex md:ml-4 md:mt-0">
                            <select
                                value={selectedTimeRange}
                                onChange={(e) => setSelectedTimeRange(e.target.value)}
                                className="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                            >
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="365">Last year</option>
                            </select>
                        </div>
                    </div>

                    {/* Quick Stats */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-6 mb-8">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <UserGroupIcon className="h-6 w-6 text-blue-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Employees
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {quickStats.total_employees.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <AcademicCapIcon className="h-6 w-6 text-green-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Trainings
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {quickStats.total_trainings.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ShieldCheckIcon className="h-6 w-6 text-green-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Valid Certificates
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {quickStats.valid_certificates.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ExclamationTriangleIcon className="h-6 w-6 text-yellow-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Expiring Soon
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {quickStats.expiring_soon.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <XCircleIcon className="h-6 w-6 text-red-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Expired
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {quickStats.expired_certificates.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ClockIcon className="h-6 w-6 text-blue-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Pending
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {quickStats.pending_registrations.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                        {/* Department Compliance */}
                        <div className="lg:col-span-2 bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                    Department Compliance Overview
                                </h3>
                            </div>
                            <div className="p-6">
                                <div className="space-y-4">
                                    {departmentCompliance.map((dept) => (
                                        <div key={dept.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                            <div className="flex-1">
                                                <div className="flex items-center justify-between mb-2">
                                                    <h4 className="text-sm font-medium text-gray-900">{dept.name}</h4>
                                                    <span className={`text-sm font-semibold ${getStatusColor(dept.compliance_rate)}`}>
                                                        {dept.compliance_rate}%
                                                    </span>
                                                </div>
                                                <div className="w-full bg-gray-200 rounded-full h-2">
                                                    <div
                                                        className={`h-2 rounded-full ${
                                                            dept.compliance_rate >= 95 ? 'bg-green-500' :
                                                            dept.compliance_rate >= 85 ? 'bg-blue-500' :
                                                            dept.compliance_rate >= 75 ? 'bg-yellow-500' :
                                                            'bg-red-500'
                                                        }`}
                                                        style={{ width: `${dept.compliance_rate}%` }}
                                                    ></div>
                                                </div>
                                                <div className="flex justify-between text-xs text-gray-500 mt-1">
                                                    <span>{dept.compliant_employees}/{dept.total_employees} compliant</span>
                                                    <span>
                                                        {dept.expiring_soon > 0 && (
                                                            <span className="text-yellow-600">{dept.expiring_soon} expiring, </span>
                                                        )}
                                                        {dept.expired > 0 && (
                                                            <span className="text-red-600">{dept.expired} expired</span>
                                                        )}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Urgent Actions */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                    Urgent Actions Required
                                </h3>
                            </div>
                            <div className="p-6">
                                <div className="space-y-4">
                                    {urgentActions.expired_certificates.length > 0 && (
                                        <div className="border-l-4 border-red-400 bg-red-50 p-4">
                                            <div className="flex">
                                                <div className="flex-shrink-0">
                                                    <XCircleIcon className="h-5 w-5 text-red-400" />
                                                </div>
                                                <div className="ml-3">
                                                    <p className="text-sm text-red-700">
                                                        <strong>{urgentActions.expired_certificates.length}</strong> expired certificates
                                                    </p>
                                                    <div className="mt-2">
                                                        <Link
                                                            href={route('certificates.index', { status: 'expired' })}
                                                            className="text-sm font-medium text-red-700 hover:text-red-600"
                                                        >
                                                            View all expired →
                                                        </Link>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {urgentActions.expiring_this_week.length > 0 && (
                                        <div className="border-l-4 border-yellow-400 bg-yellow-50 p-4">
                                            <div className="flex">
                                                <div className="flex-shrink-0">
                                                    <ExclamationTriangleIcon className="h-5 w-5 text-yellow-400" />
                                                </div>
                                                <div className="ml-3">
                                                    <p className="text-sm text-yellow-700">
                                                        <strong>{urgentActions.expiring_this_week.length}</strong> expiring this week
                                                    </p>
                                                    <div className="mt-2">
                                                        <Link
                                                            href={route('certificates.index', { status: 'expiring_soon' })}
                                                            className="text-sm font-medium text-yellow-700 hover:text-yellow-600"
                                                        >
                                                            Schedule renewals →
                                                        </Link>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {urgentActions.overdue_training.length > 0 && (
                                        <div className="border-l-4 border-blue-400 bg-blue-50 p-4">
                                            <div className="flex">
                                                <div className="flex-shrink-0">
                                                    <ClockIcon className="h-5 w-5 text-blue-400" />
                                                </div>
                                                <div className="ml-3">
                                                    <p className="text-sm text-blue-700">
                                                        <strong>{urgentActions.overdue_training.length}</strong> overdue trainings
                                                    </p>
                                                    <div className="mt-2">
                                                        <Link
                                                            href={route('training-records.index', { status: 'overdue' })}
                                                            className="text-sm font-medium text-blue-700 hover:text-blue-600"
                                                        >
                                                            Follow up →
                                                        </Link>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {urgentActions.missing_mandatory.length > 0 && (
                                        <div className="border-l-4 border-purple-400 bg-purple-50 p-4">
                                            <div className="flex">
                                                <div className="flex-shrink-0">
                                                    <UserGroupIcon className="h-5 w-5 text-purple-400" />
                                                </div>
                                                <div className="ml-3">
                                                    <p className="text-sm text-purple-700">
                                                        <strong>{urgentActions.missing_mandatory.length}</strong> missing mandatory training
                                                    </p>
                                                    <div className="mt-2">
                                                        <Link
                                                            href={route('employees.missing-mandatory')}
                                                            className="text-sm font-medium text-purple-700 hover:text-purple-600"
                                                        >
                                                            Assign trainings →
                                                        </Link>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        {/* Training Completion Trends */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                    Training Completion Trends
                                </h3>
                            </div>
                            <div className="p-6">
                                <ResponsiveContainer width="100%" height={300}>
                                    <LineChart data={completionTrends}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="month" />
                                        <YAxis />
                                        <Tooltip />
                                        <Legend />
                                        <Line
                                            type="monotone"
                                            dataKey="completed_count"
                                            stroke="#10B981"
                                            strokeWidth={2}
                                            name="Completed Trainings"
                                        />
                                        <Line
                                            type="monotone"
                                            dataKey="average_score"
                                            stroke="#3B82F6"
                                            strokeWidth={2}
                                            name="Average Score"
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* Training by Category */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                    Training Distribution by Category
                                </h3>
                            </div>
                            <div className="p-6">
                                <ResponsiveContainer width="100%" height={300}>
                                    <PieChart>
                                        <Pie
                                            data={trainingByCategory}
                                            cx="50%"
                                            cy="50%"
                                            labelLine={false}
                                            label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                            outerRadius={80}
                                            fill="#8884d8"
                                            dataKey="completed_count"
                                        >
                                            {trainingByCategory.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                            ))}
                                        </Pie>
                                        <Tooltip />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Recent Activities */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                    Recent Activities
                                </h3>
                            </div>
                            <div className="p-6">
                                <div className="flow-root">
                                    <ul role="list" className="-mb-8">
                                        {recentActivities.map((activity, idx) => (
                                            <li key={idx}>
                                                <div className="relative pb-8">
                                                    {idx !== recentActivities.length - 1 && (
                                                        <span className="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                                                    )}
                                                    <div className="relative flex space-x-3">
                                                        <div>
                                                            <span className={`h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white ${
                                                                activity.type === 'training_completed' ? 'bg-green-500' : 'bg-blue-500'
                                                            }`}>
                                                                {activity.type === 'training_completed' ? (
                                                                    <CheckCircleIcon className="h-5 w-5 text-white" />
                                                                ) : (
                                                                    <AcademicCapIcon className="h-5 w-5 text-white" />
                                                                )}
                                                            </span>
                                                        </div>
                                                        <div className="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                            <div>
                                                                <p className="text-sm text-gray-500">
                                                                    <span className="font-medium text-gray-900">{activity.employee_name}</span>
                                                                    {' '}
                                                                    {activity.type === 'training_completed' ? 'completed' : 'registered for'}
                                                                    {' '}
                                                                    <span className="font-medium text-gray-900">{activity.training_name}</span>
                                                                    {activity.score && (
                                                                        <span className="text-green-600"> (Score: {activity.score}%)</span>
                                                                    )}
                                                                </p>
                                                                <p className="text-xs text-gray-400">{activity.department}</p>
                                                            </div>
                                                            <div className="whitespace-nowrap text-right text-sm text-gray-500">
                                                                {new Date(activity.date).toLocaleDateString()}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {/* Provider Performance */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                    Training Provider Performance
                                </h3>
                            </div>
                            <div className="p-6">
                                <div className="space-y-4">
                                    {providerPerformance.map((provider, idx) => (
                                        <div key={idx} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                            <div className="flex-1">
                                                <h4 className="text-sm font-medium text-gray-900">{provider.name}</h4>
                                                <div className="flex items-center mt-1">
                                                    <div className="flex items-center">
                                                        {[...Array(5)].map((_, i) => (
                                                            <StarIcon
                                                                key={i}
                                                                className={`h-4 w-4 ${
                                                                    i < Math.floor(provider.rating) ? 'text-yellow-400' : 'text-gray-300'
                                                                }`}
                                                                fill="currentColor"
                                                            />
                                                        ))}
                                                        <span className="ml-2 text-sm text-gray-600">
                                                            {provider.rating.toFixed(1)}
                                                        </span>
                                                    </div>
                                                    <span className="mx-2 text-gray-300">•</span>
                                                    <span className="text-sm text-gray-600">
                                                        {provider.completed_trainings} trainings
                                                    </span>
                                                </div>
                                                <div className="flex items-center mt-1">
                                                    <span className="text-sm text-gray-600">
                                                        Avg Score: {provider.average_score}%
                                                    </span>
                                                    <span className={`ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                        provider.performance_trend === 'excellent' ? 'bg-green-100 text-green-800' :
                                                        provider.performance_trend === 'good' ? 'bg-blue-100 text-blue-800' :
                                                        'bg-yellow-100 text-yellow-800'
                                                    }`}>
                                                        {provider.performance_trend === 'excellent' ? 'Excellent' :
                                                         provider.performance_trend === 'good' ? 'Good' : 'Needs Review'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
