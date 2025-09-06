// resources/js/Pages/TrainingTypes/Create.jsx - Cleaned Create Form

import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function CreateTrainingType({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        code: '',
        validity_months: '',
        warning_days: '30',
        is_recurrent: false,
        description: '',
        requirements: '',
        learning_objectives: '',
        estimated_cost: '',
        estimated_duration_hours: '',
        is_active: true
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('training-types.store'));
    };

    // Auto-generate code from name
    const handleNameChange = (e) => {
        const name = e.target.value;
        setData('name', name);

        // Auto-generate code if code field is empty
        if (!data.code) {
            const autoCode = name
                .toUpperCase()
                .replace(/[^A-Z0-9\s]/g, '')
                .replace(/\s+/g, '_')
                .substring(0, 20);
            setData('code', autoCode);
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Create Training Type" />

            <div className="min-h-screen bg-gray-50 py-8">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Header */}
                    <div className="bg-white rounded-lg shadow-sm border p-6 mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <Link
                                    href={route('training-types.index')}
                                    className="text-blue-600 hover:text-blue-800 font-medium text-sm mb-2 inline-block"
                                >
                                    ← Back to Training Types
                                </Link>
                                <h1 className="text-3xl font-bold text-gray-900">Create Training Type</h1>
                                <p className="text-gray-600 mt-1">
                                    Add a new certificate type for employee training
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="bg-white rounded-lg shadow-sm border">
                        <form onSubmit={handleSubmit}>

                            {/* Basic Information Section */}
                            <div className="p-6 border-b border-gray-200">
                                <h2 className="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>

                                <div className="space-y-6">
                                    {/* Name */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Training Type Name *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={handleNameChange}
                                            className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors ${
                                                errors.name ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                            }`}
                                            placeholder="e.g., Fire Safety Training"
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

                                    {/* Code */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Code
                                        </label>
                                        <input
                                            type="text"
                                            value={data.code}
                                            onChange={(e) => setData('code', e.target.value)}
                                            className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors ${
                                                errors.code ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                            }`}
                                            placeholder="Auto-generated from name"
                                        />
                                        <p className="text-xs text-gray-500 mt-1">
                                            Leave empty to auto-generate from name
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
                                </div>
                            </div>

                            {/* Settings Section */}
                            <div className="p-6 border-b border-gray-200">
                                <h2 className="text-xl font-semibold text-gray-900 mb-4">Certificate Settings</h2>

                                <div className="space-y-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {/* Validity Months */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Validity Period (Months)
                                            </label>
                                            <select
                                                value={data.validity_months}
                                                onChange={(e) => setData('validity_months', e.target.value)}
                                                className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors ${
                                                    errors.validity_months ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                                }`}
                                            >
                                                <option value="">No expiration</option>
                                                <option value="6">6 months</option>
                                                <option value="12">1 year</option>
                                                <option value="24">2 years</option>
                                                <option value="36">3 years</option>
                                                <option value="60">5 years</option>
                                            </select>
                                            {errors.validity_months && (
                                                <p className="text-red-600 text-sm mt-2 flex items-center">
                                                    <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                    </svg>
                                                    {errors.validity_months}
                                                </p>
                                            )}
                                        </div>

                                        {/* Warning Days */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Warning Days Before Expiry
                                            </label>
                                            <select
                                                value={data.warning_days}
                                                onChange={(e) => setData('warning_days', e.target.value)}
                                                className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors ${
                                                    errors.warning_days ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                                }`}
                                            >
                                                <option value="7">7 days</option>
                                                <option value="14">14 days</option>
                                                <option value="30">30 days</option>
                                                <option value="60">60 days</option>
                                                <option value="90">90 days</option>
                                            </select>
                                            {errors.warning_days && (
                                                <p className="text-red-600 text-sm mt-2 flex items-center">
                                                    <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                    </svg>
                                                    {errors.warning_days}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Checkboxes */}
                                    <div className="space-y-4">
                                        <div className="flex items-start">
                                            <div className="flex items-center h-5">
                                                <input
                                                    type="checkbox"
                                                    id="is_active"
                                                    checked={data.is_active}
                                                    onChange={(e) => setData('is_active', e.target.checked)}
                                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                />
                                            </div>
                                            <div className="ml-3 text-sm">
                                                <label htmlFor="is_active" className="font-medium text-gray-700">
                                                    Active Status
                                                </label>
                                                <p className="text-gray-500">This training type can be assigned to employees</p>
                                            </div>
                                        </div>

                                        <div className="flex items-start">
                                            <div className="flex items-center h-5">
                                                <input
                                                    type="checkbox"
                                                    id="is_recurrent"
                                                    checked={data.is_recurrent}
                                                    onChange={(e) => setData('is_recurrent', e.target.checked)}
                                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                />
                                            </div>
                                            <div className="ml-3 text-sm">
                                                <label htmlFor="is_recurrent" className="font-medium text-gray-700">
                                                    Recurrent Training
                                                </label>
                                                <p className="text-gray-500">Certificate needs to be renewed periodically</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Description Section */}
                            <div className="p-6 border-b border-gray-200">
                                <h2 className="text-xl font-semibold text-gray-900 mb-4">Description & Details</h2>

                                <div className="space-y-6">
                                    {/* Description */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Description
                                        </label>
                                        <textarea
                                            rows="4"
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none ${
                                                errors.description ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                            }`}
                                            placeholder="Brief description of the training type..."
                                        />
                                        {errors.description && (
                                            <p className="text-red-600 text-sm mt-2 flex items-center">
                                                <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                                {errors.description}
                                            </p>
                                        )}
                                    </div>

                                    {/* Requirements */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Requirements & Prerequisites
                                        </label>
                                        <textarea
                                            rows="3"
                                            value={data.requirements}
                                            onChange={(e) => setData('requirements', e.target.value)}
                                            className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none ${
                                                errors.requirements ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                            }`}
                                            placeholder="Prerequisites or requirements for this training..."
                                        />
                                        {errors.requirements && (
                                            <p className="text-red-600 text-sm mt-2 flex items-center">
                                                <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                                {errors.requirements}
                                            </p>
                                        )}
                                    </div>

                                    {/* Learning Objectives */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Learning Objectives
                                        </label>
                                        <textarea
                                            rows="3"
                                            value={data.learning_objectives}
                                            onChange={(e) => setData('learning_objectives', e.target.value)}
                                            className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none ${
                                                errors.learning_objectives ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                            }`}
                                            placeholder="What participants will learn or achieve..."
                                        />
                                        {errors.learning_objectives && (
                                            <p className="text-red-600 text-sm mt-2 flex items-center">
                                                <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                                {errors.learning_objectives}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Cost & Duration Section */}
                            <div className="p-6">
                                <h2 className="text-xl font-semibold text-gray-900 mb-4">Cost & Duration (Optional)</h2>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Estimated Cost */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Estimated Cost (IDR)
                                        </label>
                                        <div className="relative">
                                            <span className="absolute left-3 top-3 text-gray-500">Rp</span>
                                            <input
                                                type="number"
                                                min="0"
                                                step="1000"
                                                value={data.estimated_cost}
                                                onChange={(e) => setData('estimated_cost', e.target.value)}
                                                className={`w-full pl-12 pr-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors ${
                                                    errors.estimated_cost ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                                }`}
                                                placeholder="500000"
                                            />
                                        </div>
                                        {errors.estimated_cost && (
                                            <p className="text-red-600 text-sm mt-2 flex items-center">
                                                <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                                {errors.estimated_cost}
                                            </p>
                                        )}
                                    </div>

                                    {/* Estimated Duration */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Estimated Duration (Hours)
                                        </label>
                                        <select
                                            value={data.estimated_duration_hours}
                                            onChange={(e) => setData('estimated_duration_hours', e.target.value)}
                                            className={`w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors ${
                                                errors.estimated_duration_hours ? 'border-red-300 bg-red-50' : 'border-gray-300'
                                            }`}
                                        >
                                            <option value="">Select duration</option>
                                            <option value="1">1 hour</option>
                                            <option value="2">2 hours</option>
                                            <option value="4">4 hours (Half day)</option>
                                            <option value="8">8 hours (Full day)</option>
                                            <option value="16">16 hours (2 days)</option>
                                            <option value="24">24 hours (3 days)</option>
                                            <option value="40">40 hours (1 week)</option>
                                        </select>
                                        {errors.estimated_duration_hours && (
                                            <p className="text-red-600 text-sm mt-2 flex items-center">
                                                <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                                {errors.estimated_duration_hours}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Submit Buttons */}
                            <div className="p-6 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                                <div className="flex items-center justify-end space-x-4">
                                    <Link
                                        href={route('training-types.index')}
                                        className="px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                                    >
                                        Cancel
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-6 py-3 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center"
                                    >
                                        {processing ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Creating...
                                            </>
                                        ) : (
                                            'Create Training Type'
                                        )}
                                    </button>
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
                                    Tips for creating training types
                                </h3>
                                <div className="mt-2 text-sm text-blue-700">
                                    <ul className="list-disc list-inside space-y-1">
                                        <li>Use clear, descriptive names that employees will easily understand</li>
                                        <li>Set appropriate validity periods based on regulatory requirements</li>
                                        <li>Enable recurrent setting for certifications that need regular renewal</li>
                                        <li>Add detailed descriptions to help HR team understand the training scope</li>
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
