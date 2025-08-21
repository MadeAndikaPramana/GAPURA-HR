// resources/js/Pages/Departments/Create.jsx

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    BuildingOfficeIcon,
    PlusIcon
} from '@heroicons/react/24/outline';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        code: '',
        description: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('departments.store'));
    };

    const generateCode = () => {
        if (data.name) {
            // Generate code from department name
            const words = data.name.split(' ');
            let code = '';

            if (words.length === 1) {
                code = words[0].substring(0, 3).toUpperCase();
            } else {
                code = words.map(word => word.charAt(0)).join('').toUpperCase();
            }

            setData('code', code);
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
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
                                Add New Department
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Create a new department for the organization
                            </p>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title="Add Department" />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6">
                        {/* Department Information */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <BuildingOfficeIcon className="w-5 h-5 mr-2" />
                                    Department Information
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Basic information about the department
                                </p>
                            </div>
                            <div className="px-6 py-4 space-y-6">
                                {/* Department Name */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Department Name *
                                    </label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                            errors.name ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        placeholder="e.g., Human Resources, Information Technology"
                                        required
                                    />
                                    {errors.name && (
                                        <p className="mt-2 text-sm text-red-600">{errors.name}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        Full name of the department as it will appear in the system
                                    </p>
                                </div>

                                {/* Department Code */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Department Code *
                                    </label>
                                    <div className="flex space-x-2">
                                        <input
                                            type="text"
                                            value={data.code}
                                            onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                            className={`flex-1 border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.code ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            placeholder="e.g., HR, IT, OPS"
                                            maxLength="10"
                                            required
                                        />
                                        <button
                                            type="button"
                                            onClick={generateCode}
                                            className="px-3 py-2 text-sm bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500"
                                        >
                                            Generate
                                        </button>
                                    </div>
                                    {errors.code && (
                                        <p className="mt-2 text-sm text-red-600">{errors.code}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        Short code for the department (2-10 characters, uppercase)
                                    </p>
                                </div>

                                {/* Description */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={4}
                                        className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                            errors.description ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        placeholder="Describe the department's role and responsibilities..."
                                    />
                                    {errors.description && (
                                        <p className="mt-2 text-sm text-red-600">{errors.description}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        Optional description of the department's purpose and responsibilities
                                    </p>
                                </div>

                                {/* Preview */}
                                {data.name && data.code && (
                                    <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                                        <h4 className="text-sm font-medium text-blue-800 mb-2">Department Preview</h4>
                                        <div className="text-sm">
                                            <div className="flex items-center space-x-2">
                                                <div className="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                                    <span className="text-xs font-medium text-white">
                                                        {data.code.charAt(0)}
                                                    </span>
                                                </div>
                                                <div>
                                                    <div className="text-blue-900 font-medium">{data.name}</div>
                                                    <div className="text-blue-700 text-xs">Code: {data.code}</div>
                                                </div>
                                            </div>
                                            {data.description && (
                                                <div className="mt-2 text-blue-800">
                                                    {data.description}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Form Actions */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-gray-600">
                                        * Required fields
                                    </div>
                                    <div className="flex space-x-3">
                                        <Link
                                            href={route('departments.index')}
                                            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            Cancel
                                        </Link>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                                        >
                                            {processing ? (
                                                <>
                                                    <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Creating...
                                                </>
                                            ) : (
                                                <>
                                                    <PlusIcon className="w-4 h-4 mr-2" />
                                                    Create Department
                                                </>
                                            )}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
