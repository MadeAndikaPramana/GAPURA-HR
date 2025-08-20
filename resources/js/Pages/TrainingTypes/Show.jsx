import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    PencilIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    ChartBarIcon,
    UsersIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    ArrowDownTrayIcon,
    ClockIcon,
    TagIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, trainingType, statistics }) {
    const [activeTab, setActiveTab] = useState('overview');

    const getStatusColor = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            expiring_soon: 'bg-yellow-100 text-yellow-800',
            expired: 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getStatusIcon = (status) => {
        const icons = {
            active: <CheckCircleIcon className="w-4 h-4" />,
            expiring_soon: <ExclamationTriangleIcon className="w-4 h-4" />,
            expired: <XCircleIcon className="w-4 h-4" />
        };
        return icons[status] || <XCircleIcon className="w-4 h-4" />;
    };

    const getCategoryColor = (category) => {
        const colors = {
            safety: 'bg-red-100 text-red-800',
            operational: 'bg-blue-100 text-blue-800',
            security: 'bg-purple-100 text-purple-800',
            technical: 'bg-green-100 text-green-800'
        };
        return colors[category] || 'bg-gray-100 text-gray-800';
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const getComplianceRate = () => {
        if (statistics.total === 0) return 0;
        return Math.round((statistics.active / statistics.total) * 100);
    };

    const exportTrainingType = () => {
        router.get(route('training-types.export'), {
            training_type_id: trainingType.id
        });
    };

    const tabs = [
        { id: 'overview', name: 'Overview', icon: ChartBarIcon },
        { id: 'departments', name: 'By Department', icon: BuildingOfficeIcon },
        { id: 'certificates', name: 'Recent Certificates', icon: TagIcon }
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('training-types.index')}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-2" />
                            Back to Training Types
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {trainingType.name}
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Training Type Details & Statistics
                            </p>
                        </div>
                    </div>
                    <div className="flex space-x-2">
                        <button
                            onClick={exportTrainingType}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                            Export Data
                        </button>
                        <Link
                            href={route('training-types.edit', trainingType.id)}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit Training Type
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Training Type - ${trainingType.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">

                        {/* Training Type Information Card */}
                        <div className="lg:col-span-1">
                            <div className="bg-white shadow rounded-lg p-6">
                                <div className="flex items-center space-x-4 mb-6">
                                    <div className="flex-shrink-0">
                                        <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                            <TagIcon className="w-8 h-8 text-green-600" />
                                        </div>
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900">
                                            {trainingType.name}
                                        </h3>
                                        {trainingType.code && (
                                            <p className="text-sm text-gray-500">
                                                Code: {trainingType.code}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <div className="flex items-center">
                                        <TagIcon className="w-5 h-5 text-gray-400 mr-3" />
                                        <div>
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getCategoryColor(trainingType.category)}`}>
                                                {trainingType.category.charAt(0).toUpperCase() + trainingType.category.slice(1)}
                                            </span>
                                            <p className="text-xs text-gray-500 mt-1">Category</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center">
                                        <ClockIcon className="w-5 h-5 text-gray-400 mr-3" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">
                                                {trainingType.validity_months} months
                                            </p>
                                            <p className="text-xs text-gray-500">Validity Period</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center">
                                        <CheckCircleIcon className="w-5 h-5 text-gray-400 mr-3" />
                                        <div>
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                trainingType.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800'
                                            }`}>
                                                {trainingType.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                            <p className="text-xs text-gray-500 mt-1">Status</p>
                                        </div>
                                    </div>

                                    {trainingType.description && (
                                        <div className="pt-4 border-t border-gray-200">
                                            <h4 className="text-sm font-medium text-gray-900 mb-2">Description</h4>
                                            <p className="text-sm text-gray-600">
                                                {trainingType.description}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Quick Statistics */}
                            <div className="bg-white shadow rounded-lg p-6 mt-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Stats</h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-green-600">{statistics.active}</div>
                                        <div className="text-xs text-gray-500">Active</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-yellow-600">{statistics.expiring_soon}</div>
                                        <div className="text-xs text-gray-500">Expiring</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-red-600">{statistics.expired}</div>
                                        <div className="text-xs text-gray-500">Expired</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-gray-600">{statistics.total}</div>
                                        <div className="text-xs text-gray-500">Total</div>
                                    </div>
                                </div>

                                {/* Compliance Rate */}
                                <div className="mt-4 pt-4 border-t border-gray-200">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-sm font-medium text-gray-900">Compliance Rate</span>
                                        <span className="text-sm font-bold text-green-600">{getComplianceRate()}%</span>
                                    </div>
                                    <div className="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            className="bg-green-600 h-2 rounded-full transition-all duration-300"
                                            style={{ width: `${getComplianceRate()}%` }}
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Main Content */}
                        <div className="lg:col-span-3">
                            <div className="bg-white shadow rounded-lg">
                                {/* Tabs */}
                                <div className="border-b border-gray-200">
                                    <nav className="-mb-px flex">
                                        {tabs.map((tab) => {
                                            const Icon = tab.icon;
                                            return (
                                                <button
                                                    key={tab.id}
                                                    onClick={() => setActiveTab(tab.id)}
                                                    className={`${
                                                        activeTab === tab.id
                                                            ? 'border-green-500 text-green-600'
                                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                                    } w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm flex items-center justify-center space-x-2`}
                                                >
                                                    <Icon className="w-5 h-5" />
                                                    <span>{tab.name}</span>
                                                </button>
                                            );
                                        })}
                                    </nav>
                                </div>

                                {/* Tab Content */}
                                <div className="p-6">
                                    {activeTab === 'overview' && (
                                        <div className="space-y-6">
                                            {/* Overall Statistics */}
                                            <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                                                <div className="bg-blue-50 rounded-lg p-4">
                                                    <div className="flex items-center">
                                                        <UsersIcon className="w-8 h-8 text-blue-600" />
                                                        <div className="ml-3">
                                                            <p className="text-sm font-medium text-blue-600">Total Certificates</p>
                                                            <p className="text-2xl font-bold text-blue-900">{statistics.total}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="bg-green-50 rounded-lg p-4">
                                                    <div className="flex items-center">
                                                        <CheckCircleIcon className="w-8 h-8 text-green-600" />
                                                        <div className="ml-3">
                                                            <p className="text-sm font-medium text-green-600">Active</p>
                                                            <p className="text-2xl font-bold text-green-900">{statistics.active}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="bg-yellow-50 rounded-lg p-4">
                                                    <div className="flex items-center">
                                                        <ExclamationTriangleIcon className="w-8 h-8 text-yellow-600" />
                                                        <div className="ml-3">
                                                            <p className="text-sm font-medium text-yellow-600">Expiring Soon</p>
                                                            <p className="text-2xl font-bold text-yellow-900">{statistics.expiring_soon}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="bg-red-50 rounded-lg p-4">
                                                    <div className="flex items-center">
                                                        <XCircleIcon className="w-8 h-8 text-red-600" />
                                                        <div className="ml-3">
                                                            <p className="text-sm font-medium text-red-600">Expired</p>
                                                            <p className="text-2xl font-bold text-red-900">{statistics.expired}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Category Information */}
                                            <div className="bg-gray-50 border border-gray-200 rounded-md p-4">
                                                <h4 className="text-sm font-medium text-gray-900 mb-2">
                                                    About {trainingType.category.charAt(0).toUpperCase() + trainingType.category.slice(1)} Training
                                                </h4>
                                                <p className="text-sm text-gray-600">
                                                    {trainingType.category === 'safety' && 'Safety-related trainings including fire safety, first aid, occupational health and safety procedures required for airport operations.'}
                                                    {trainingType.category === 'operational' && 'Operational trainings for day-to-day work processes, ground handling procedures, customer service standards and operational protocols.'}
                                                    {trainingType.category === 'security' && 'Security and access control trainings, airport security awareness, background check procedures and security compliance requirements.'}
                                                    {trainingType.category === 'technical' && 'Technical skills training for specialized equipment operation, maintenance procedures, and technical competency requirements.'}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {activeTab === 'departments' && (
                                        <div className="space-y-4">
                                            <h3 className="text-lg font-medium text-gray-900">Department Breakdown</h3>
                                            {statistics.by_department && statistics.by_department.length > 0 ? (
                                                <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                                    <table className="min-w-full divide-y divide-gray-300">
                                                        <thead className="bg-gray-50">
                                                            <tr>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Department
                                                                </th>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Total
                                                                </th>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Active
                                                                </th>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Expiring
                                                                </th>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Expired
                                                                </th>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Compliance
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody className="bg-white divide-y divide-gray-200">
                                                            {statistics.by_department.map((dept, index) => {
                                                                const complianceRate = dept.total_certificates > 0 ? Math.round((dept.active_count / dept.total_certificates) * 100) : 0;
                                                                return (
                                                                    <tr key={index}>
                                                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                            {dept.department_name}
                                                                        </td>
                                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                            {dept.total_certificates}
                                                                        </td>
                                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                                                            {dept.active_count}
                                                                        </td>
                                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">
                                                                            {dept.expiring_count}
                                                                        </td>
                                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                                                            {dept.expired_count}
                                                                        </td>
                                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                                            <div className="flex items-center">
                                                                                <span className={`text-sm font-medium ${
                                                                                    complianceRate >= 80 ? 'text-green-600' :
                                                                                    complianceRate >= 60 ? 'text-yellow-600' : 'text-red-600'
                                                                                }`}>
                                                                                    {complianceRate}%
                                                                                </span>
                                                                                <div className="ml-2 w-16 bg-gray-200 rounded-full h-2">
                                                                                    <div
                                                                                        className={`h-2 rounded-full ${
                                                                                            complianceRate >= 80 ? 'bg-green-600' :
                                                                                            complianceRate >= 60 ? 'bg-yellow-600' : 'bg-red-600'
                                                                                        }`}
                                                                                        style={{ width: `${complianceRate}%` }}
                                                                                    ></div>
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                );
                                                            })}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            ) : (
                                                <div className="text-center py-6">
                                                    <BuildingOfficeIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No department data</h3>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        No certificates found for this training type.
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {activeTab === 'certificates' && (
                                        <div className="space-y-4">
                                            <h3 className="text-lg font-medium text-gray-900">Recent Certificates</h3>
                                            {statistics.recent_certificates && statistics.recent_certificates.length > 0 ? (
                                                <div className="divide-y divide-gray-200">
                                                    {statistics.recent_certificates.map((cert, index) => (
                                                        <div key={index} className="py-4">
                                                            <div className="flex items-center justify-between">
                                                                <div className="flex-1">
                                                                    <div className="flex items-center space-x-2 mb-2">
                                                                        <h4 className="text-sm font-medium text-gray-900">
                                                                            {cert.employee.name}
                                                                        </h4>
                                                                        <span className="text-xs text-gray-500">
                                                                            ({cert.employee.employee_id})
                                                                        </span>
                                                                        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getStatusColor(cert.status)}`}>
                                                                            {getStatusIcon(cert.status)}
                                                                            <span className="ml-1">{cert.status.replace('_', ' ')}</span>
                                                                        </span>
                                                                    </div>
                                                                    <div className="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                                                        <div>
                                                                            <span className="font-medium">Certificate:</span> {cert.certificate_number}
                                                                        </div>
                                                                        <div>
                                                                            <span className="font-medium">Department:</span> {cert.employee.department?.name}
                                                                        </div>
                                                                        <div>
                                                                            <span className="font-medium">Issue Date:</span> {formatDate(cert.issue_date)}
                                                                        </div>
                                                                        <div>
                                                                            <span className="font-medium">Expiry Date:</span> {formatDate(cert.expiry_date)}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div className="ml-4">
                                                                    <Link
                                                                        href={route('training-records.show', cert.id)}
                                                                        className="text-green-600 hover:text-green-900 text-sm font-medium"
                                                                    >
                                                                        View Details
                                                                    </Link>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <div className="text-center py-6">
                                                    <TagIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No certificates found</h3>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        No certificates have been issued for this training type yet.
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
