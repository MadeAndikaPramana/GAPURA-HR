import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/react/24/outline';

export default function Edit({ auth, trainingType, categories }) {
    const { data, setData, put, processing, errors, reset } = useForm({
        name: trainingType.name || '',
        code: trainingType.code || '',
        validity_months: trainingType.validity_months || '12',
        category: trainingType.category || '',
        description: trainingType.description || '',
        is_active: trainingType.is_active ?? true
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('training-types.update', trainingType.id));
    };

    const hasCertificates = trainingType.training_records_count > 0;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
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
                            Edit Training Type
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Update training type: {trainingType.name}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title={`Edit Training Type - ${trainingType.name}`} />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">

                            {/* Warning if has certificates */}
                            {hasCertificates && (
                                <div className="mb-6 bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                    <div className="flex">
                                        <ExclamationTriangleIcon className="w-5 h-5 text-yellow-400 mt-0.5" />
                                        <div className="ml-3">
                                            <h3 className="text-sm font-medium text-yellow-800">
                                                Active Certificates Warning
                                            </h3>
                                            <div className="mt-2 text-sm text-yellow-700">
                                                <p>
                                                    This training type has <strong>{trainingType.training_records_count} active certificates</strong>.
                                                    Changes to validity period will not affect existing certificates.
                                                </p>
                                                <ul className="list-disc pl-5 mt-2 space-y-1">
                                                    <li>Changing category or validity will only apply to new certificates</li>
                                                    <li>Deactivating will prevent new certificate creation</li>
                                                    <li>Existing certificates will remain unaffected</li>
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
                                                <li>Name should be descriptive and unique</li>
                                                <li>Code changes may affect integrations</li>
                                                <li>Validity changes only apply to new certificates</li>
                                                <li>Category helps organize training types</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    {/* Training Name */}
                                    <div className="sm:col-span-2">
                                        <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                            Training Name *
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
                                                placeholder="e.g., Fire Safety Training"
                                                required
                                            />
                                        </div>
                                        {errors.name && (
                                            <p className="mt-2 text-sm text-red-600">{errors.name}</p>
                                        )}
                                    </div>

                                    {/* Training Code */}
                                    <div>
                                        <label htmlFor="code" className="block text-sm font-medium text-gray-700">
                                            Training Code
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
                                                placeholder="FIRE"
                                                maxLength="50"
                                            />
                                        </div>
                                        {hasCertificates && (
                                            <p className="mt-1 text-xs text-yellow-600">
                                                ⚠️ Changing code may affect system integrations
                                            </p>
                                        )}
                                        {errors.code && (
                                            <p className="mt-2 text-sm text-red-600">{errors.code}</p>
                                        )}
                                    </div>

                                    {/* Validity Months */}
                                    <div>
                                        <label htmlFor="validity_months" className="block text-sm font-medium text-gray-700">
                                            Validity Period (Months) *
                                        </label>
                                        <div className="mt-1">
                                            <select
                                                id="validity_months"
                                                value={data.validity_months}
                                                onChange={(e) => setData('validity_months', e.target.value)}
                                                className={`block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                    errors.validity_months ? 'border-red-300' : ''
                                                }`}
                                                required
                                            >
                                                <option value="6">6 Months</option>
                                                <option value="12">12 Months (1 Year)</option>
                                                <option value="18">18 Months</option>
                                                <option value="24">24 Months (2 Years)</option>
                                                <option value="36">36 Months (3 Years)</option>
                                                <option value="60">60 Months (5 Years)</option>
                                            </select>
                                        </div>
                                        {hasCertificates && (
                                            <p className="mt-1 text-xs text-yellow-600">
                                                ⚠️ Only affects new certificates, not existing ones
                                            </p>
                                        )}
                                        {errors.validity_months && (
                                            <p className="mt-2 text-sm text-red-600">{errors.validity_months}</p>
                                        )}
                                    </div>

                                    {/* Category */}
                                    <div>
                                        <label htmlFor="category" className="block text-sm font-medium text-gray-700">
                                            Category *
                                        </label>
                                        <div className="mt-1">
                                            <select
                                                id="category"
                                                value={data.category}
                                                onChange={(e) => setData('category', e.target.value)}
                                                className={`block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                    errors.category ? 'border-red-300' : ''
                                                }`}
                                                required
                                            >
                                                <option value="">Select Category</option>
                                                {categories.map(category => (
                                                    <option key={category} value={category}>
                                                        {category.charAt(0).toUpperCase() + category.slice(1)}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        {errors.category && (
                                            <p className="mt-2 text-sm text-red-600">{errors.category}</p>
                                        )}
                                    </div>

                                    {/* Status */}
                                    <div>
                                        <label htmlFor="is_active" className="block text-sm font-medium text-gray-700">
                                            Status
                                        </label>
                                        <div className="mt-1">
                                            <select
                                                id="is_active"
                                                value={data.is_active ? 'true' : 'false'}
                                                onChange={(e) => setData('is_active', e.target.value === 'true')}
                                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            >
                                                <option value="true">Active</option>
                                                <option value="false">Inactive</option>
                                            </select>
                                        </div>
                                        {!data.is_active && hasCertificates && (
                                            <p className="mt-1 text-xs text-yellow-600">
                                                ⚠️ Deactivating will prevent new certificate creation
                                            </p>
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
                                            placeholder="Optional description of the training type, its purpose, requirements, etc."
                                        />
                                    </div>
                                    {errors.description && (
                                        <p className="mt-2 text-sm text-red-600">{errors.description}</p>
                                    )}
                                </div>

                                {/* Category Description Helper */}
                                {data.category && (
                                    <div className="bg-gray-50 border border-gray-200 rounded-md p-4">
                                        <h4 className="text-sm font-medium text-gray-900 mb-2">
                                            {data.category.charAt(0).toUpperCase() + data.category.slice(1)} Category
                                        </h4>
                                        <p className="text-sm text-gray-600">
                                            {data.category === 'safety' && 'Safety-related trainings including fire safety, first aid, occupational health and safety.'}
                                            {data.category === 'operational' && 'Operational trainings for day-to-day work processes, ground handling, customer service.'}
                                            {data.category === 'security' && 'Security and access control trainings, airport security awareness, background checks.'}
                                            {data.category === 'technical' && 'Technical skills training for equipment operation, maintenance, specialized procedures.'}
                                        </p>
                                    </div>
                                )}

                                {/* Current Statistics */}
                                {hasCertificates && (
                                    <div className="bg-gray-50 border border-gray-200 rounded-md p-4">
                                        <h4 className="text-sm font-medium text-gray-900 mb-2">
                                            Current Training Statistics
                                        </h4>
                                        <div className="grid grid-cols-4 gap-4 text-sm">
                                            <div className="text-center">
                                                <div className="text-lg font-semibold text-gray-900">{trainingType.training_records_count}</div>
                                                <div className="text-xs text-gray-500">Total</div>
                                            </div>
                                            <div className="text-center">
                                                <div className="text-lg font-semibold text-green-600">{trainingType.active_records_count || 0}</div>
                                                <div className="text-xs text-gray-500">Active</div>
                                            </div>
                                            <div className="text-center">
                                                <div className="text-lg font-semibold text-yellow-600">{trainingType.expiring_records_count || 0}</div>
                                                <div className="text-xs text-gray-500">Expiring</div>
                                            </div>
                                            <div className="text-center">
                                                <div className="text-lg font-semibold text-red-600">{trainingType.expired_records_count || 0}</div>
                                                <div className="text-xs text-gray-500">Expired</div>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Form Actions */}
                                <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                                    <Link
                                        href={route('training-types.index')}
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        Cancel
                                    </Link>
                                    <Link
                                        href={route('training-types.show', trainingType.id)}
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        View Details
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'Updating...' : 'Update Training Type'}
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
