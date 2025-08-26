import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Save, X, AlertCircle } from 'lucide-react';

const CreateTrainingType = ({ auth, providers = [], categoryOptions = {}, errors = {} }) => {
    const [data, setData] = useState({
        name: '',
        code: '',
        category: '',
        description: '',
        validity_months: 12,
        is_mandatory: false,
        is_active: true,
        estimated_cost: '',
        estimated_duration_hours: '',
        requirements: '',
        learning_objectives: ''
    });

    const [processing, setProcessing] = useState(false);
    const [clientErrors, setClientErrors] = useState({});

    const updateData = (key, value) => {
        setData(prev => ({ ...prev, [key]: value }));
        // Clear client error when user starts typing
        if (clientErrors[key]) {
            setClientErrors(prev => ({ ...prev, [key]: null }));
        }
    };

    const validateForm = () => {
        const errors = {};

        if (!data.name || data.name.trim() === '') {
            errors.name = 'Training name is required';
        }

        if (!data.code || data.code.trim() === '') {
            errors.code = 'Training code is required';
        }

        if (!data.category || data.category.trim() === '') {
            errors.category = 'Category is required';
        }

        return errors;
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        // Client-side validation
        const validationErrors = validateForm();
        if (Object.keys(validationErrors).length > 0) {
            setClientErrors(validationErrors);
            return;
        }

        setProcessing(true);
        setClientErrors({});

        // Clean the data - remove empty strings for numeric fields
        const cleanedData = {
            ...data,
            estimated_cost: data.estimated_cost === '' ? null : data.estimated_cost,
            estimated_duration_hours: data.estimated_duration_hours === '' ? null : data.estimated_duration_hours,
        };

        console.log('Submitting cleaned data:', cleanedData);

        router.post(route('training-types.store'), cleanedData, {
            onSuccess: (page) => {
                console.log('Success:', page);
            },
            onError: (errors) => {
                console.log('Server validation errors:', errors);
                console.log('Error type:', typeof errors);
                console.log('Error keys:', Object.keys(errors));

                // Set server errors to state for display
                setClientErrors(errors);
            },
            onFinish: () => {
                setProcessing(false);
            }
        });
    };

    const generateCode = () => {
        if (data.category) {
            const categoryCode = data.category.substring(0, 3).toUpperCase();
            const randomNum = Math.floor(Math.random() * 999) + 1;
            const code = `${categoryCode}-${randomNum.toString().padStart(3, '0')}`;
            updateData('code', code);
        }
    };

    // Combine server errors and client errors
    const allErrors = { ...errors, ...clientErrors };

    const categories = [
        'Safety',
        'Security',
        'Aviation',
        'Technical',
        'Compliance',
        'Quality',
        'Service',
        'Operations',
        'Management',
        'Finance',
        'IT'
    ];

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Add Training Type" />

            <div className="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="bg-white shadow rounded-lg">
                    {/* Header */}
                    <div className="px-6 py-4 border-b border-gray-200">
                        <div className="flex items-center justify-between">
                            <h1 className="text-xl font-semibold text-gray-900">
                                Add New Training Type
                            </h1>
                            <Link
                                href={route('training-types.index')}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <X className="h-6 w-6" />
                            </Link>
                        </div>
                    </div>

                    {/* Debug Info */}
                    {Object.keys(allErrors).length > 0 && (
                        <div className="px-6 py-4 bg-red-50 border-b border-red-200">
                            <h3 className="text-sm font-medium text-red-800 mb-2">Validation Errors:</h3>
                            <ul className="text-sm text-red-700 list-disc list-inside">
                                {Object.entries(allErrors).map(([field, error]) => (
                                    <li key={field}><strong>{field}:</strong> {error}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {/* Form */}
                    <div className="p-6">
                        <div className="max-w-2xl">
                            <div className="grid grid-cols-1 gap-6">
                                {/* Basic Information */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Training Name *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => updateData('name', e.target.value)}
                                            className={`w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 ${
                                                allErrors.name ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            placeholder="e.g., Fire Safety Training"
                                        />
                                        {allErrors.name && (
                                            <p className="mt-1 text-sm text-red-600 flex items-center">
                                                <AlertCircle className="h-4 w-4 mr-1" />
                                                {allErrors.name}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Training Code *
                                        </label>
                                        <div className="flex">
                                            <input
                                                type="text"
                                                value={data.code}
                                                onChange={(e) => updateData('code', e.target.value.toUpperCase())}
                                                className={`flex-1 px-3 py-2 border rounded-l-md focus:ring-blue-500 focus:border-blue-500 ${
                                                    allErrors.code ? 'border-red-300' : 'border-gray-300'
                                                }`}
                                                placeholder="e.g., SAF-001"
                                            />
                                            <button
                                                type="button"
                                                onClick={generateCode}
                                                className="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md text-sm hover:bg-gray-200"
                                            >
                                                Generate
                                            </button>
                                        </div>
                                        {allErrors.code && (
                                            <p className="mt-1 text-sm text-red-600 flex items-center">
                                                <AlertCircle className="h-4 w-4 mr-1" />
                                                {allErrors.code}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Category *
                                        </label>
                                        <select
                                            value={data.category}
                                            onChange={(e) => updateData('category', e.target.value)}
                                            className={`w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 ${
                                                allErrors.category ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                        >
                                            <option value="">Select Category</option>
                                            {categories.map(category => (
                                                <option key={category} value={category}>{category}</option>
                                            ))}
                                        </select>
                                        {allErrors.category && (
                                            <p className="mt-1 text-sm text-red-600 flex items-center">
                                                <AlertCircle className="h-4 w-4 mr-1" />
                                                {allErrors.category}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Validity Period (Months)
                                        </label>
                                        <input
                                            type="number"
                                            value={data.validity_months}
                                            onChange={(e) => updateData('validity_months', parseInt(e.target.value))}
                                            min="1"
                                            max="120"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Description
                                    </label>
                                    <textarea
                                        value={data.description}
                                        onChange={(e) => updateData('description', e.target.value)}
                                        rows={3}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Brief description of the training..."
                                    />
                                </div>

                                {/* Optional Fields */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Estimated Cost (IDR)
                                        </label>
                                        <input
                                            type="number"
                                            value={data.estimated_cost}
                                            onChange={(e) => updateData('estimated_cost', e.target.value)}
                                            min="0"
                                            step="1000"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="0"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Duration (Hours)
                                        </label>
                                        <input
                                            type="number"
                                            value={data.estimated_duration_hours}
                                            onChange={(e) => updateData('estimated_duration_hours', e.target.value)}
                                            min="0.5"
                                            step="0.5"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="0"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Requirements
                                    </label>
                                    <textarea
                                        value={data.requirements}
                                        onChange={(e) => updateData('requirements', e.target.value)}
                                        rows={2}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Prerequisites, eligibility criteria, etc..."
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Learning Objectives
                                    </label>
                                    <textarea
                                        value={data.learning_objectives}
                                        onChange={(e) => updateData('learning_objectives', e.target.value)}
                                        rows={2}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="What participants will learn and achieve..."
                                    />
                                </div>

                                {/* Checkboxes */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.is_mandatory}
                                            onChange={(e) => updateData('is_mandatory', e.target.checked)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <label className="ml-2 block text-sm text-gray-900">
                                            Mandatory Training
                                        </label>
                                    </div>

                                    <div className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.is_active}
                                            onChange={(e) => updateData('is_active', e.target.checked)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <label className="ml-2 block text-sm text-gray-900">
                                            Active Status
                                        </label>
                                    </div>
                                </div>

                                {/* Debug Data Display */}
                                <div className="mt-6 p-4 bg-gray-100 rounded-md">
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">Debug - Current Form Data:</h4>
                                    <pre className="text-xs text-gray-600 overflow-x-auto">
                                        {JSON.stringify(data, null, 2)}
                                    </pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                        <div className="text-sm text-gray-500">
                            * Required fields
                        </div>
                        <div className="flex items-center space-x-3">
                            <Link
                                href={route('training-types.index')}
                                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Cancel
                            </Link>
                            <button
                                onClick={handleSubmit}
                                disabled={processing}
                                className="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Save className="h-4 w-4" />
                                <span>{processing ? 'Saving...' : 'Save Training Type'}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default CreateTrainingType;
