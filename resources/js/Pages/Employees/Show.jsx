import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    UserIcon,
    BuildingOfficeIcon,
    BriefcaseIcon,
    CalendarIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    PlusIcon,
    PencilIcon,
    EyeIcon,
    ArrowDownTrayIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, employee, trainingStats }) {
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

    const addTraining = () => {
        router.get(route('training-records.create'), {
            employee_id: employee.id
        });
    };

    const exportTrainingRecords = () => {
        router.get(route('training-records.bulkExport'), {
            employee_id: employee.id
        });
    };

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
                                Employee Details & Training Records
                            </p>
                        </div>
                    </div>
                    <div className="flex space-x-2">
                        <button
                            onClick={addTraining}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <PlusIcon className="w-4 h-4 mr-2" />
                            Add Training
                        </button>
                        <Link
                            href={route('employees.edit', employee.id)}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit Employee
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Employee - ${employee.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        {/* Employee Information Card */}
                        <div className="lg:col-span-1">
                            <div className="bg-white shadow rounded-lg p-6">
                                <div className="flex items-center space-x-4 mb-6">
                                    <div className="flex-shrink-0">
                                        <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                            <UserIcon className="w-8 h-8 text-green-600" />
                                        </div>
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900">
                                            {employee.name}
                                        </h3>
                                        <p className="text-sm text-gray-500">
                                            ID: {employee.employee_id}
                                        </p>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <div className="flex items-center">
                                        <BuildingOfficeIcon className="w-5 h-5 text-gray-400 mr-3" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">
                                                {employee.department?.name || 'No Department'}
                                            </p>
                                            <p className="text-xs text-gray-500">Department</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center">
                                        <BriefcaseIcon className="w-5 h-5 text-gray-400 mr-3" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">
                                                {employee.position || 'No Position'}
                                            </p>
                                            <p className="text-xs text-gray-500">Position</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center">
                                        <CalendarIcon className="w-5 h-5 text-gray-400 mr-3" />
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                    employee.status === 'active'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {employee.status.charAt(0).toUpperCase() + employee.status.slice(1)}
                                                </span>
                                            </p>
                                            <p className="text-xs text-gray-500">Status</p>
                                        </div>
                                    </div>

                                    {employee.background_check_date && (
                                        <div className="pt-4 border-t border-gray-200">
                                            <h4 className="text-sm font-medium text-gray-900 mb-2">Background Check</h4>
                                            <p className="text-sm text-gray-600">
                                                Date: {formatDate(employee.background_check_date)}
                                            </p>
                                            {employee.background_check_notes && (
                                                <p className="text-xs text-gray-500 mt-1">
                                                    {employee.background_check_notes}
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Training Statistics */}
                            <div className="bg-white shadow rounded-lg p-6 mt-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Training Statistics</h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-green-600">{trainingStats.active}</div>
                                        <div className="text-xs text-gray-500">Active</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-yellow-600">{trainingStats.expiring_soon}</div>
                                        <div className="text-xs text-gray-500">Expiring</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-red-600">{trainingStats.expired}</div>
                                        <div className="text-xs text-gray-500">Expired</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-gray-600">{trainingStats.total}</div>
                                        <div className="text-xs text-gray-500">Total</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Training Records */}
                        <div className="lg:col-span-2">
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
                                            <div key={record.id} className="p-6">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex-1">
                                                        <div className="flex items-center space-x-2 mb-2">
                                                            <h4 className="text-sm font-medium text-gray-900">
                                                                {record.training_type?.name}
                                                            </h4>
                                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getCategoryColor(record.training_type?.category)}`}>
                                                                {record.training_type?.category}
                                                            </span>
                                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(record.status)}`}>
                                                                {getStatusIcon(record.status)}
                                                                <span className="ml-1">{record.status.replace('_', ' ')}</span>
                                                            </span>
                                                        </div>
                                                        <div className="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                                            <div>
                                                                <span className="font-medium">Certificate:</span> {record.certificate_number}
                                                            </div>
                                                            <div>
                                                                <span className="font-medium">Issuer:</span> {record.issuer}
                                                            </div>
                                                            <div>
                                                                <span className="font-medium">Issue Date:</span> {formatDate(record.issue_date)}
                                                            </div>
                                                            <div>
                                                                <span className="font-medium">Expiry Date:</span> {formatDate(record.expiry_date)}
                                                            </div>
                                                        </div>
                                                        {record.notes && (
                                                            <div className="mt-2 text-sm text-gray-600">
                                                                <span className="font-medium">Notes:</span> {record.notes}
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="flex items-center space-x-2 ml-4">
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
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="p-6 text-center">
                                            <UserIcon className="mx-auto h-12 w-12 text-gray-400" />
                                            <h3 className="mt-2 text-sm font-medium text-gray-900">No training records</h3>
                                            <p className="mt-1 text-sm text-gray-500">
                                                This employee doesn't have any training records yet.
                                            </p>
                                            <div className="mt-6">
                                                <button
                                                    onClick={addTraining}
                                                    className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                                >
                                                    <PlusIcon className="-ml-1 mr-2 h-5 w-5" />
                                                    Add First Training
                                                </button>
                                            </div>
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
