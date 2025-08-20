import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    InformationCircleIcon,
    BuildingOfficeIcon
} from '@heroicons/react/24/outline';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        code: '',
        description: ''
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('departments.store'), {
            onSuccess: () => reset(),
        });
    };

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
                            Create New Department
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Tambah departemen atau unit organisasi baru
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Create Department" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {/* Info Banner */}
                            <div className="mb-6 bg-blue-50 border border-blue-200 rounded-md p-4">
                                <div className="flex">
                                    <InformationCircleIcon className="w-5 h-5 text-blue-400 mt-0.5" />
                                    <div className="ml-3">
                                        <h3 className="text-sm font-medium text-blue-800">
                                            Department Guidelines
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

                                {/* Preview */}
                                {(data.name || data.code) && (
                                    <div className="bg-gray-50 border border-gray-200 rounded-md p-4">
                                        <h4 className="text-sm font-medium text-gray-900 mb-2 flex items-center">
                                            <BuildingOfficeIcon className="w-4 h-4 mr-2" />
                                            Department Preview
                                        </h4>
                                        <div className="space-y-2 text-sm">
                                            {data.name && (
                                                <div>
                                                    <span className="font-medium text-gray-700">Name:</span>
                                                    <span className="ml-2 text-gray-900">{data.name}</span>
                                                </div>
                                            )}
                                            {data.code && (
                                                <div>
                                                    <span className="font-medium text-gray-700">Code:</span>
                                                    <span className="ml-2 text-gray-900 font-mono">{data.code}</span>
                                                </div>
                                            )}
                                            {data.description && (
                                                <div>
                                                    <span className="font-medium text-gray-700">Description:</span>
                                                    <span className="ml-2 text-gray-900">{data.description}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Example Departments */}
                                <div className="bg-green-50 border border-green-200 rounded-md p-4">
                                    <h4 className="text-sm font-medium text-green-900 mb-2">
                                        Example Departments
                                    </h4>
                                    <div className="grid grid-cols-2 gap-4 text-sm text-green-800">
                                        <div>
                                            <div className="font-medium">Human Resources</div>
                                            <div className="text-xs text-green-600">Code: HR</div>
                                        </div>
                                        <div>
                                            <div className="font-medium">Information Technology</div>
                                            <div className="text-xs text-green-600">Code: IT</div>
                                        </div>
                                        <div>
                                            <div className="font-medium">Flight Operations</div>
                                            <div className="text-xs text-green-600">Code: OPS</div>
                                        </div>
                                        <div>
                                            <div className="font-medium">Ground Support Equipment</div>
                                            <div className="text-xs text-green-600">Code: GSE</div>
                                        </div>
                                        <div>
                                            <div className="font-medium">Security Services</div>
                                            <div className="text-xs text-green-600">Code: SEC</div>
                                        </div>
                                        <div>
                                            <div className="font-medium">Customer Relations</div>
                                            <div className="text-xs text-green-600">Code: CR</div>
                                        </div>
                                    </div>
                                </div>

                                {/* Form Actions */}
                                <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                                    <Link
                                        href={route('departments.index')}
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        Cancel
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'Creating...' : 'Create Department'}
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
