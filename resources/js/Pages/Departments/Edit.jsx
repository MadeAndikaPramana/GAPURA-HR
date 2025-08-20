import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    InformationCircleIcon,
    BuildingOfficeIcon,
    ExclamationTriangleIcon
} from '@heroicons/react/24/outline';

export default function Edit({ auth, department }) {
    const { data, setData, put, processing, errors } = useForm({
        name: department.name || '',
        code: department.code || '',
        description: department.description || ''
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('departments.update', department.id));
    };

    const hasEmployees = department.employees_count > 0;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center space-x-4">
                    <Link
                        href={route('departments.index')}
                        className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        <ArrowLeftIcon className="w-4 h-4 mr-2" />
                        Back to Departments
                    </Link>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Edit Department
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Update department: {department.name}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title={`Edit Department - ${department.name}`} />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">

                            {/* Warning if has employees */}
                            {hasEmployees && (
                                <div className="mb-6 bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                    <div className="flex">
                                        <ExclamationTriangleIcon className="w-5 h-5 text-yellow-400 mt-0.5" />
                                        <div className="ml-3">
                                            <h3 className="text-sm font-medium text-yellow-800">
                                                Department Has Active Employees
                                            </h3>
                                            <div className="mt-2 text-sm text-yellow-700">
                                                <p>
                                                    This department has <strong>{department.employees_count} employees</strong>.
                                                    Changes to the department code may affect reports and integrations.
                                                </p>
                                                <ul className="list-disc pl-5 mt-2 space-y-1">
                                                    <li>Department name changes will be reflected immediately</li>
                                                    <li>Code changes should be coordinated with system administrators</li>
                                                    <li>Historical data will maintain the old code references</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Info Banner */}
                            <div className="mb-6 bg-blue-50 border border-blue-200 rounded-md p-4">
                                <div className="flex">
                                    <InformationCircleIcon className="w-5 h-5 text-blue-400 mt-0.5" />
                                    <div className="ml-3">
                                        <h3 className="text-sm font-medium text-blue-800">
                                            Editing Guidelines
                                        </h3>
                                        <div className="mt-2 text-sm text-blue-700">
                                            <ul className="list-disc pl-5 space-y-1">
                                                <li>Department name should be descriptive and unique</li>
                                                <li>Code should be short (3-10 characters) and easy to remember</li>
                                                <li>Code will be automatically converted to uppercase</li>
                                                <li>Description helps identify the department's purpose</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    {/* Department Name */}
                                    <div className="sm:col-span-2">
                                        <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                            Department Name *
                                        </label>
                                        <div className="mt-1">
                                            <input
                                                type="text"
                                                id="name"
                                                value={data.name}
                                                onChange={(e) => setData('name', e.target.value)}
                                                className={`block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                    errors.name ? 'border-red-300' : ''
                                                }`}
                                                placeholder="e.g., Human Resources"
                                                required
                                            />
                                        </div>
                                        {errors.name && (
                                            <p className="mt-2 text-sm text-red-600">{errors.name}</p>
                                        )}
                                    </div>

                                    {/* Department Code */}
                                    <div>
                                        <label htmlFor="code" className="block text-sm font-medium text-gray-700">
                                            Department Code *
                                        </label>
                                        <div className="mt-1">
                                            <input
                                                type="text"
                                                id="code"
                                                value={data.code}
                                                onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                                className={`block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                    errors.code ? 'border-red-300' : ''
                                                }`}
                                                placeholder="HR"
                                                maxLength="10"
                                                required
                                            />
                                        </div>
                                        {hasEmployees && (
                                            <p className="mt-1 text-xs text-yellow-600">
                                                ⚠️ Changing code may affect existing employee records
                                            </p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500">
                                            Short code for easy identification (3-10 characters)
                                        </p>
                                        {errors.code && (
                                            <p className="mt-2 text-sm text-red-600">{errors.code}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Description */}
                                <div>
                                    <label htmlFor="description" className="block text-sm font-medium text-gray-700">
                                        Description
                                    </label>
                                    <div className="mt-1">
                                        <textarea
                                            id="description"
                                            rows={4}
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            className={`block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                errors.description ? 'border-red-300' : ''
                                            }`}
                                            placeholder="Optional description of the department's role and responsibilities..."
                                        />
                                    </div>
                                    {errors.description && (
                                        <p className="mt-2 text-sm text-red-600">{errors.description}</p>
                                    )}
                                </div>

                                {/* Preview Changes */}
                                {(data.name !== department.name || data.code !== department.code || data.description !== department.description) && (
                                    <div className="bg-gray-50 border border-gray-200 rounded-md p-4">
                                        <h4 className="text-sm font-medium text-gray-900 mb-2 flex items-center">
                                            <BuildingOfficeIcon className="w-4 h-4 mr-2" />
                                            Preview Changes
                                        </h4>
                                        <div className="space-y-3 text-sm">
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <span className="font-medium text-gray-700">Current:</span>
                                                    <div className="mt-1 space-y-1">
                                                        <div><span className="text-gray-600">Name:</span> {department.name}</div>
                                                        <div><span className="text-gray-600">Code:</span> {department.code}</div>
                                                        <div><span className="text-gray-600">Description:</span> {department.description || 'No description'}</div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <span className="font-medium text-gray-700">New:</span>
                                                    <div className="mt-1 space-y-1">
                                                        <div>
                                                            <span className="text-gray-600">Name:</span>
                                                            <span className={data.name !== department.name ? 'text-green-600 font-medium' : ''}> {data.name}</span>
                                                        </div>
                                                        <div>
                                                            <span className="text-gray-600">Code:</span>
                                                            <span className={data.code !== department.code ? 'text-green-600 font-medium font-mono' : 'font-mono'}> {data.code}</span>
                                                        </div>
                                                        <div>
                                                            <span className="text-gray-600">Description:</span>
                                                            <span className={data.description !== department.description ? 'text-green-600 font-medium' : ''}> {data.description || 'No description'}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Current Department Statistics */}
                                {hasEmployees && (
                                    <div className="bg-gray-50 border border-gray-200 rounded-md p-4">
                                        <h4 className="text-sm font-medium text-gray-900 mb-2">
                                            Current Department Statistics
                                        </h4>
                                        <div className="grid grid-cols-4 gap-4 text-sm">
                                            <div className="text-center">
                                                <div className="text-lg font-semibold text-gray-900">{department.employees_count}</div>
                                                <div className="text-xs text-gray-500">Total Employees</div>
                                            </div>
                                            <div className="text-center">
                                                <div className="text-lg font-semibold text-green-600">{department.active_employees_count || 0}</div>
                                                <div className="text-xs text-gray-500">Active</div>
                                            </div>
                                            <div className="text-center">
                                                <div className="text-lg font-semibold text-blue-600">{department.training_stats?.total_certificates || 0}</div>
                                                <div className="text-xs text-gray-500">Certificates</div>
                                            </div>
                                            <div className="text-center">
                                                <div className="text-lg font-semibold text-purple-600">{department.compliance_rate || 0}%</div>
                                                <div className="text-xs text-gray-500">Compliance</div>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Form Actions */}
                                <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                                    <Link
                                        href={route('departments.index')}
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        Cancel
                                    </Link>
                                    <Link
                                        href={route('departments.show', department.id)}
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        View Details
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'Updating...' : 'Update Department'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
