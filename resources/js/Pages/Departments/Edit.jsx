import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { LoadingButton } from '@/Components/UI';
import { BuildingOfficeIcon } from '@heroicons/react/24/outline';

export default function Edit({ auth, department }) {
    const { data, setData, put, processing, errors } = useForm({
        name: department.name || '',
        code: department.code || '',
        description: department.description || ''
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('departments.update', department.id));
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Edit Department - ${department.name}`} />

            <div className="min-h-screen bg-gray-50 py-8">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Header */}
                    <div className="bg-white rounded-lg shadow-sm border p-6 mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <Link
                                    href={route('departments.index')}
                                    className="text-blue-600 hover:text-blue-800 font-medium text-sm mb-2 inline-block"
                                >
                                    ← Back to Departments
                                </Link>
                                <h1 className="text-3xl font-bold text-gray-900 flex items-center">
                                    <BuildingOfficeIcon className="w-8 h-8 mr-3 text-blue-600" />
                                    Edit Department
                                </h1>
                                <p className="text-gray-600 mt-1">
                                    Update department information
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="bg-white rounded-lg shadow-sm border">
                        <form onSubmit={handleSubmit}>

                            {/* Form Fields */}
                            <div className="p-6 space-y-6">

                                {/* Department Name */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Department Name *
                                    </label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors ${
                                            errors.name ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                        }`}
                                        placeholder="e.g., Human Resources"
                                        required
                                    />
                                    {errors.name && (
                                        <p className="text-red-600 text-sm mt-2 flex items-center">
                                            <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                            </svg>
                                            {errors.name}
                                        </p>
                                    )}
                                </div>

                                {/* Department Code */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Department Code *
                                    </label>
                                    <input
                                        type="text"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                        className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors ${
                                            errors.code ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                        }`}
                                        placeholder="e.g., HR"
                                        maxLength={10}
                                        required
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Maximum 10 characters
                                    </p>
                                    {errors.code && (
                                        <p className="text-red-600 text-sm mt-2 flex items-center">
                                            <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                            </svg>
                                            {errors.code}
                                        </p>
                                    )}
                                </div>

                                {/* Description */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Description (Optional)
                                    </label>
                                    <textarea
                                        rows="4"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none ${
                                            errors.description ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                        }`}
                                        placeholder="Brief description of the department's role and responsibilities..."
                                        maxLength={500}
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        {data.description.length}/500 characters
                                    </p>
                                    {errors.description && (
                                        <p className="text-red-600 text-sm mt-2 flex items-center">
                                            <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                            </svg>
                                            {errors.description}
                                        </p>
                                    )}
                                </div>

                                {/* Info Box - Employee Count */}
                                {department.employees_count > 0 && (
                                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                        <div className="flex">
                                            <div className="flex-shrink-0">
                                                <svg className="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                            </div>
                                            <div className="ml-3">
                                                <h3 className="text-sm font-medium text-yellow-800">
                                                    This department has {department.employees_count} employee{department.employees_count !== 1 ? 's' : ''}
                                                </h3>
                                                <p className="mt-1 text-sm text-yellow-700">
                                                    Changing the department code will not affect existing employees, but may impact reporting and analytics.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                            </div>

                            {/* Submit Buttons */}
                            <div className="p-6 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                                <div className="flex items-center justify-end space-x-4">
                                    <Link
                                        href={route('departments.index')}
                                        className="px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                                    >
                                        Cancel
                                    </Link>
                                    <LoadingButton
                                        type="submit"
                                        processing={processing}
                                        variant="primary"
                                    >
                                        {processing ? 'Updating...' : 'Update Department'}
                                    </LoadingButton>
                                </div>
                            </div>
                        </form>
                    </div>

                    {/* Help Text */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-blue-800">
                                    Important notes
                                </h3>
                                <div className="mt-2 text-sm text-blue-700">
                                    <ul className="list-disc list-inside space-y-1">
                                        <li>Department name and code must be unique</li>
                                        <li>Changes will be reflected across all reports and analytics</li>
                                        <li>Employees in this department will see the updated information</li>
                                        <li>You cannot delete a department that has employees assigned to it</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
