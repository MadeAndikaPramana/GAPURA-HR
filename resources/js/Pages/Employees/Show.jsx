// resources/js/Pages/Employees/Show.jsx

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    ArrowLeftIcon,
    UserIcon,
    PencilIcon,
    BuildingOfficeIcon,
    ClipboardDocumentListIcon,
    CalendarDaysIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    PlusIcon,
    ArrowDownTrayIcon,
    EyeIcon,
    TrashIcon,
    ChartBarIcon,
    TrophyIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, employee, trainingStats, recentActivities }) {
    const [activeTab, setActiveTab] = useState('overview');

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    const formatDateTime = (dateString) => {
        return new Date(dateString).toLocaleString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
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

    const getStatusBadge = (status) => {
        const statusConfig = {
            active: {
                color: 'bg-green-100 text-green-800',
                icon: CheckCircleIcon,
                text: 'Active'
            },
            expiring_soon: {
                color: 'bg-yellow-100 text-yellow-800',
                icon: ExclamationTriangleIcon,
                text: 'Expiring Soon'
            },
            expired: {
                color: 'bg-red-100 text-red-800',
                icon: XCircleIcon,
                text: 'Expired'
            }
        };

        const config = statusConfig[status] || statusConfig.active;
        const IconComponent = config.icon;

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.color}`}>
                <IconComponent className="w-3 h-3 mr-1" />
                {config.text}
            </span>
        );
    };

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

    const addTraining = () => {
        router.get(route('training-records.create', { employee_id: employee.id }));
    };

    const exportTrainingRecords = () => {
        router.get(route('import-export.training-records.export', { employee_id: employee.id }));
    };

    const deleteTrainingRecord = (recordId) => {
        if (confirm('Are you sure you want to delete this training record?')) {
            router.delete(route('training-records.destroy', recordId));
        }
    };

    const tabs = [
        { id: 'overview', name: 'Overview', icon: ChartBarIcon },
        { id: 'training', name: 'Training Records', icon: ClipboardDocumentListIcon },
        { id: 'profile', name: 'Profile Details', icon: UserIcon }
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('employees.index')}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-2" />
                            Back to Employees
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {employee.name}
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                {employee.employee_id} • {employee.department?.name || 'No Department'} • {employee.position || 'No Position'}
                            </p>
                        </div>
                    </div>
                    <div className="flex space-x-2">
                        <button
                            onClick={addTraining}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        >
                            <PlusIcon className="w-4 h-4 mr-2" />
                            Add Training
                        </button>
                        <Link
                            href={route('employees.edit', employee.id)}
                            className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit Employee
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={employee.name} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Employee Header Card */}
                    <div className="bg-white shadow rounded-lg mb-8">
                        <div className="px-6 py-8">
                            <div className="flex items-start space-x-6">
                                <div className="flex-shrink-0">
                                    <div className="h-20 w-20 rounded-full bg-green-500 flex items-center justify-center">
                                        <span className="text-2xl font-bold text-white">
                                            {employee.name.charAt(0).toUpperCase()}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex-1 min-w-0">
                                    <h1 className="text-2xl font-bold text-gray-900">{employee.name}</h1>
                                    <p className="text-lg text-gray-600 mt-1">
                                        {employee.position || 'No Position Assigned'}
                                    </p>
                                    <div className="mt-4 flex items-center space-x-6 text-sm text-gray-500">
                                        <div className="flex items-center">
                                            <UserIcon className="w-4 h-4 mr-2" />
                                            {employee.employee_id}
                                        </div>
                                        <div className="flex items-center">
                                            <BuildingOfficeIcon className="w-4 h-4 mr-2" />
                                            {employee.department?.name || 'No Department'}
                                        </div>
                                        <div className="flex items-center">
                                            <CalendarDaysIcon className="w-4 h-4 mr-2" />
                                            Joined {formatDate(employee.hire_date)}
                                        </div>
                                        <div className="flex items-center">
                                            {employee.status === 'active' ? (
                                                <CheckCircleIcon className="w-4 h-4 mr-2 text-green-500" />
                                            ) : (
                                                <XCircleIcon className="w-4 h-4 mr-2 text-red-500" />
                                            )}
                                            {employee.status === 'active' ? 'Active Employee' : 'Inactive Employee'}
                                        </div>
                                    </div>
                                </div>
                                <div className="flex-shrink-0 flex space-x-4">
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-green-600">
                                            {trainingStats.total}
                                        </div>
                                        <div className="text-sm text-gray-500">Total Trainings</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-blue-600">
                                            {trainingStats.active}
                                        </div>
                                        <div className="text-sm text-gray-500">Active Certificates</div>
                                    </div>
                                    <div className="text-center">
                                        <div className={`text-2xl font-bold ${getComplianceColor(trainingStats.compliance_rate)}`}>
                                            {trainingStats.compliance_rate}%
                                        </div>
                                        <div className="text-sm text-gray-500">Compliance Rate</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Tabs Navigation */}
                    <div className="bg-white shadow rounded-lg mb-8">
                        <div className="border-b border-gray-200">
                            <nav className="flex space-x-8 px-6" aria-label="Tabs">
                                {tabs.map((tab) => {
                                    const IconComponent = tab.icon;
                                    return (
                                        <button
                                            key={tab.id}
                                            onClick={() => setActiveTab(tab.id)}
                                            className={`${
                                                activeTab === tab.id
                                                    ? 'border-green-500 text-green-600'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center`}
                                        >
                                            <IconComponent className="w-4 h-4 mr-2" />
                                            {tab.name}
                                        </button>
                                    );
                                })}
                            </nav>
                        </div>
                    </div>

                    {/* Tab Content */}
                    {activeTab === 'overview' && (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            {/* Training Statistics */}
                            <div className="lg:col-span-2">
                                <div className="bg-white shadow rounded-lg">
                                    <div className="px-6 py-4 border-b border-gray-200">
                                        <h3 className="text-lg font-medium text-gray-900">Training Overview</h3>
                                    </div>
                                    <div className="p-6">
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                                            <div className="text-center">
                                                <div className="text-3xl font-bold text-blue-600">{trainingStats.total}</div>
                                                <div className="text-sm text-gray-500">Total Trainings</div>
                                            </div>
                                            <div className="text-center">
                                                <div className="text-3xl font-bold text-green-600">{trainingStats.active}</div>
                                                <div className="text-sm text-gray-500">Active</div>
                                            </div>
                                            <div className="text-center">
                                                <div className="text-3xl font-bold text-yellow-600">{trainingStats.expiring_soon}</div>
                                                <div className="text-sm text-gray-500">Expiring Soon</div>
                                            </div>
                                            <div className="text-center">
                                                <div className="text-3xl font-bold text-red-600">{trainingStats.expired}</div>
                                                <div className="text-sm text-gray-500">Expired</div>
                                            </div>
                                        </div>

                                        {/* Compliance Progress */}
                                        <div className="mb-6">
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="text-sm font-medium text-gray-700">Training Compliance</span>
                                                <span className={`text-sm font-bold ${getComplianceColor(trainingStats.compliance_rate)}`}>
                                                    {trainingStats.compliance_rate}%
                                                </span>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-3">
                                                <div
                                                    className={`h-3 rounded-full ${getComplianceProgressColor(trainingStats.compliance_rate)}`}
                                                    style={{ width: `${trainingStats.compliance_rate}%` }}
                                                />
                                            </div>
                                            <p className="text-xs text-gray-500 mt-1">
                                                {trainingStats.active} out of {trainingStats.total} certifications are active
                                            </p>
                                        </div>

                                        {/* Quick Actions */}
                                        <div className="flex space-x-3">
                                            <button
                                                onClick={addTraining}
                                                className="flex-1 inline-flex items-center justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                            >
                                                <PlusIcon className="w-4 h-4 mr-2" />
                                                Add Training
                                            </button>
                                            <button
                                                onClick={exportTrainingRecords}
                                                className="flex-1 inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                            >
                                                <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                                                Export Records
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Recent Activities */}
                            <div>
                                <div className="bg-white shadow rounded-lg">
                                    <div className="px-6 py-4 border-b border-gray-200">
                                        <h3 className="text-lg font-medium text-gray-900">Recent Activities</h3>
                                    </div>
                                    <div className="px-6 py-4">
                                        {recentActivities && recentActivities.length > 0 ? (
                                            <div className="space-y-4">
                                                {recentActivities.map((activity) => (
                                                    <div key={activity.id} className="flex items-start space-x-3">
                                                        <div className="flex-shrink-0">
                                                            <ClipboardDocumentListIcon className="w-5 h-5 text-green-500" />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm text-gray-900">
                                                                Completed <span className="font-medium">{activity.training_type.name}</span>
                                                            </p>
                                                            <div className="flex items-center space-x-2 mt-1">
                                                                {getStatusBadge(activity.status)}
                                                                <span className="text-xs text-gray-500">
                                                                    {formatDateTime(activity.created_at)}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="text-center py-4">
                                                <ClipboardDocumentListIcon className="w-8 h-8 mx-auto text-gray-400 mb-2" />
                                                <p className="text-sm text-gray-500">No recent training activities</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'training' && (
                        <div className="bg-white shadow rounded-lg">
                            <div className="border-b border-gray-200">
                                <div className="px-6 py-4 flex items-center justify-between">
                                    <h3 className="text-lg font-medium text-gray-900">
                                        Training Records ({trainingStats.total})
                                    </h3>
                                    <button
                                        onClick={exportTrainingRecords}
                                        className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                                        Export
                                    </button>
                                </div>
                            </div>

                            <div className="divide-y divide-gray-200">
                                {employee.training_records && employee.training_records.length > 0 ? (
                                    employee.training_records.map((record) => (
                                        <div key={record.id} className="px-6 py-4 hover:bg-gray-50">
                                            <div className="flex items-center justify-between">
                                                <div className="flex-1">
                                                    <div className="flex items-center space-x-3">
                                                        <h4 className="text-sm font-medium text-gray-900">
                                                            {record.training_type.name}
                                                        </h4>
                                                        {getStatusBadge(record.status)}
                                                    </div>
                                                    <div className="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                                        <span>Certificate: {record.certificate_number}</span>
                                                        <span>Issued: {formatDate(record.issue_date)}</span>
                                                        <span>Expires: {formatDate(record.expiry_date)}</span>
                                                        <span>{getDaysUntilExpiry(record.expiry_date)}</span>
                                                    </div>
                                                    <div className="mt-1 text-xs text-gray-400">
                                                        Provider: {record.issuer}
                                                    </div>
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <Link
                                                        href={route('training-records.show', record.id)}
                                                        className="text-green-600 hover:text-green-900"
                                                        title="View Details"
                                                    >
                                                        <EyeIcon className="w-4 h-4" />
                                                    </Link>
                                                    <Link
                                                        href={route('training-records.edit', record.id)}
                                                        className="text-blue-600 hover:text-blue-900"
                                                        title="Edit"
                                                    >
                                                        <PencilIcon className="w-4 h-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => deleteTrainingRecord(record.id)}
                                                        className="text-red-600 hover:text-red-900"
                                                        title="Delete"
                                                    >
                                                        <TrashIcon className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="px-6 py-8 text-center">
                                        <ClipboardDocumentListIcon className="w-12 h-12 mx-auto text-gray-400 mb-4" />
                                        <p className="text-lg font-medium text-gray-900">No training records found</p>
                                        <p className="text-sm text-gray-500 mb-4">
                                            This employee doesn't have any training records yet.
                                        </p>
                                        <button
                                            onClick={addTraining}
                                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            <PlusIcon className="w-4 h-4 mr-2" />
                                            Add First Training
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {activeTab === 'profile' && (
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            {/* Basic Information */}
                            <div className="bg-white shadow rounded-lg">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                        <UserIcon className="w-5 h-5 mr-2" />
                                        Basic Information
                                    </h3>
                                </div>
                                <div className="px-6 py-4 space-y-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">Full Name</label>
                                        <p className="text-sm text-gray-900">{employee.name}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">Employee ID</label>
                                        <p className="text-sm text-gray-900 font-mono">{employee.employee_id}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">Department</label>
                                        <p className="text-sm text-gray-900">
                                            {employee.department ? `${employee.department.name} (${employee.department.code})` : 'Not assigned'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">Position</label>
                                        <p className="text-sm text-gray-900">{employee.position || 'Not specified'}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">Employment Status</label>
                                        <div className="flex items-center space-x-2">
                                            {employee.status === 'active' ? (
                                                <CheckCircleIcon className="w-4 h-4 text-green-500" />
                                            ) : (
                                                <XCircleIcon className="w-4 h-4 text-red-500" />
                                            )}
                                            <span className="text-sm text-gray-900 capitalize">{employee.status}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">Hire Date</label>
                                        <p className="text-sm text-gray-900">{formatDate(employee.hire_date)}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Background Check Information */}
                            <div className="bg-white shadow rounded-lg">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                        <DocumentTextIcon className="w-5 h-5 mr-2" />
                                        Background Check
                                    </h3>
                                </div>
                                <div className="px-6 py-4 space-y-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">Check Date</label>
                                        <p className="text-sm text-gray-900">
                                            {employee.background_check_date ? formatDate(employee.background_check_date) : 'Not completed'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">Status</label>
                                        <p className="text-sm text-gray-900 capitalize">
                                            {employee.background_check_status || 'Not specified'}
                                        </p>
                                    </div>
                                    {employee.background_check_notes && (
                                        <div>
                                            <label className="text-sm font-medium text-gray-700">Notes</label>
                                            <p className="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                                                {employee.background_check_notes}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* System Information */}
                            <div className="bg-white shadow rounded-lg lg:col-span-2">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900">System Information</h3>
                                </div>
                                <div className="px-6 py-4">
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <label className="text-sm font-medium text-gray-700">Created</label>
                                            <p className="text-sm text-gray-900">{formatDateTime(employee.created_at)}</p>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-gray-700">Last Updated</label>
                                            <p className="text-sm text-gray-900">{formatDateTime(employee.updated_at)}</p>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-gray-700">Record ID</label>
                                            <p className="text-sm text-gray-900 font-mono">#{employee.id}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
