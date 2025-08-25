// resources/js/Pages/TrainingRecords/Edit.jsx - PERBAIKAN LENGKAP

import React, { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon,
    CalendarIcon,
    DocumentIcon,
    CheckCircleIcon,
    UserIcon,
    TagIcon,
    BuildingOfficeIcon, // Fixed: use correct icon name
    ClockIcon,
    EyeIcon
} from '@heroicons/react/24/outline';

export default function Edit({ auth, trainingRecord, employees, trainingTypes, departments }) {
    const { data, setData, put, processing, errors } = useForm({
        employee_id: trainingRecord?.employee_id || '',
        training_type_id: trainingRecord?.training_type_id || '',
        certificate_number: trainingRecord?.certificate_number || '',
        issuer: trainingRecord?.issuer || '',
        issue_date: trainingRecord?.issue_date || '',
        expiry_date: trainingRecord?.expiry_date || '',
        notes: trainingRecord?.notes || ''
    });

    const [selectedTrainingType, setSelectedTrainingType] = useState(null);
    const [calculatedExpiry, setCalculatedExpiry] = useState('');
    const [hasChanges, setHasChanges] = useState(false);

    // Initialize selected training type
    useEffect(() => {
        if (data.training_type_id && trainingTypes) {
            const type = trainingTypes.find(t => t.id == data.training_type_id);
            setSelectedTrainingType(type);
        }
    }, [data.training_type_id, trainingTypes]);

    // Calculate expiry date when training type or issue date changes
    useEffect(() => {
        if (data.training_type_id && data.issue_date && trainingTypes) {
            const type = trainingTypes.find(t => t.id == data.training_type_id);
            if (type && type.validity_months) {
                const issueDate = new Date(data.issue_date);
                const expiryDate = new Date(issueDate);
                expiryDate.setMonth(expiryDate.getMonth() + type.validity_months);
                const formattedExpiry = expiryDate.toISOString().split('T')[0];
                setCalculatedExpiry(formattedExpiry);
            }
        }
    }, [data.training_type_id, data.issue_date, trainingTypes]);

    // Track changes
    useEffect(() => {
        if (!trainingRecord) return;

        const originalData = {
            employee_id: trainingRecord.employee_id,
            training_type_id: trainingRecord.training_type_id,
            certificate_number: trainingRecord.certificate_number,
            issuer: trainingRecord.issuer,
            issue_date: trainingRecord.issue_date,
            expiry_date: trainingRecord.expiry_date,
            notes: trainingRecord.notes || ''
        };

        const currentData = { ...data };
        const changed = JSON.stringify(originalData) !== JSON.stringify(currentData);
        setHasChanges(changed);
    }, [data, trainingRecord]);

    const submit = (e) => {
        e.preventDefault();
        if (processing) return;

        console.log('Submitting edit form with data:', data);
        put(route('training-records.update', trainingRecord?.id));
    };

    const getSelectedEmployee = () => {
        if (!employees || !data.employee_id) return null;
        return employees.find(emp => emp.id == data.employee_id);
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

    // PERBAIKAN: Safety check untuk trainingRecord
    if (!trainingRecord) {
        return (
            <AuthenticatedLayout user={auth.user}>
                <Head title="Training Record Not Found" />
                <div className="py-12">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 bg-white border-b border-gray-200">
                                <div className="text-center">
                                    <ExclamationTriangleIcon className="w-12 h-12 mx-auto text-yellow-400 mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">Training Record Not Found</h3>
                                    <p className="text-sm text-gray-500 mb-4">The training record you're looking for doesn't exist.</p>
                                    <Link
                                        href={route('training-records.index')}
                                        className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700"
                                    >
                                        <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                        Back to Training Records
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center space-x-4">
                    <Link
                        href={route('training-records.index')}
                        className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        <ArrowLeftIcon className="w-4 h-4 mr-2" />
                        Back to Training Records
                    </Link>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Edit Training Record
                        </h2>
                        <p className="text-sm text-gray-600">
                            {trainingRecord?.certificate_number || 'Unknown Certificate'}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Edit Training Record" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">

                    {/* Status Alert */}
                    {hasChanges && (
                        <div className="mb-6">
                            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div className="flex">
                                    <InformationCircleIcon className="w-5 h-5 text-yellow-400 mr-2" />
                                    <div>
                                        <h4 className="text-sm font-medium text-yellow-800">Unsaved Changes</h4>
                                        <p className="text-sm text-yellow-700 mt-1">
                                            You have unsaved changes. Please save or discard them.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-8">

                        {/* Employee Information */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 flex items-center">
                                    <UserIcon className="w-5 h-5 mr-2 text-green-600" />
                                    Employee Information
                                </h3>
                            </div>
                            <div className="px-6 py-4 space-y-6">
                                {/* Current Employee */}
                                {trainingRecord?.employee && (
                                    <div className="bg-gray-50 rounded-lg p-4">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center">
                                                <div className="flex-shrink-0 h-10 w-10">
                                                    <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                        <span className="text-sm font-medium text-green-800">
                                                            {trainingRecord.employee?.name?.charAt(0) || '?'}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="ml-4">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {trainingRecord.employee?.name || 'Unknown Employee'}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {trainingRecord.employee?.employee_id || 'No ID'} •
                                                        {trainingRecord.employee?.department?.name || 'No Department'} •
                                                        {trainingRecord.employee?.position || 'No Position'}
                                                    </div>
                                                </div>
                                            </div>
                                            {trainingRecord.employee?.id && (
                                                <Link
                                                    href={route('employees.show', trainingRecord.employee.id)}
                                                    className="text-green-600 hover:text-green-900"
                                                >
                                                    <EyeIcon className="w-5 h-5" />
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Employee Change */}
                                <div>
                                    <label htmlFor="employee_id" className="block text-sm font-medium text-gray-700">
                                        Change Employee (Advanced)
                                    </label>
                                    <div className="mt-1">
                                        <select
                                            id="employee_id"
                                            value={data.employee_id}
                                            onChange={(e) => setData('employee_id', e.target.value)}
                                            className={`block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                errors.employee_id ? 'border-red-300' : ''
                                            }`}
                                        >
                                            {employees && employees.map(employee => (
                                                <option key={employee.id} value={employee.id}>
                                                    {employee.name} ({employee.employee_id}) - {employee.department?.name || 'No Department'}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    {data.employee_id != trainingRecord?.employee_id && (
                                        <p className="mt-1 text-xs text-yellow-600">
                                            ⚠️ Changing employee will transfer this certificate
                                        </p>
                                    )}
                                    {errors.employee_id && (
                                        <p className="mt-2 text-sm text-red-600">{errors.employee_id}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Training Type */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 flex items-center">
                                    <TagIcon className="w-5 h-5 mr-2 text-green-600" />
                                    Training Type
                                </h3>
                            </div>
                            <div className="px-6 py-4 space-y-6">
                                <div>
                                    <label htmlFor="training_type_id" className="block text-sm font-medium text-gray-700">
                                        Training Type *
                                    </label>
                                    <div className="mt-1">
                                        <select
                                            id="training_type_id"
                                            value={data.training_type_id}
                                            onChange={(e) => setData('training_type_id', e.target.value)}
                                            className={`block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                errors.training_type_id ? 'border-red-300' : ''
                                            }`}
                                            required
                                        >
                                            <option value="">Select Training Type</option>
                                            {trainingTypes && trainingTypes.map((type) => (
                                                <option key={type.id} value={type.id}>
                                                    {type.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    {errors.training_type_id && (
                                        <p className="mt-2 text-sm text-red-600">{errors.training_type_id}</p>
                                    )}
                                </div>

                                {/* Training Type Info */}
                                {selectedTrainingType && (
                                    <div className="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                        <div className="flex items-start">
                                            <div className="flex-shrink-0">
                                                <TagIcon className="w-5 h-5 text-blue-600" />
                                            </div>
                                            <div className="ml-3">
                                                <h4 className="text-sm font-medium text-blue-900">
                                                    {selectedTrainingType.name}
                                                </h4>
                                                <div className="mt-2 text-sm text-blue-700">
                                                    <p><strong>Code:</strong> {selectedTrainingType.code}</p>
                                                    <p><strong>Validity:</strong> {selectedTrainingType.validity_months} months</p>
                                                    {calculatedExpiry && (
                                                        <p><strong>Calculated Expiry:</strong> {new Date(calculatedExpiry).toLocaleDateString()}</p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Certificate Details */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 flex items-center">
                                    <DocumentIcon className="w-5 h-5 mr-2 text-green-600" />
                                    Certificate Details
                                </h3>
                            </div>
                            <div className="px-6 py-4 space-y-6">
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    {/* Certificate Number */}
                                    <div>
                                        <label htmlFor="certificate_number" className="block text-sm font-medium text-gray-700">
                                            Certificate Number *
                                        </label>
                                        <input
                                            type="text"
                                            id="certificate_number"
                                            value={data.certificate_number}
                                            onChange={(e) => setData('certificate_number', e.target.value)}
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                errors.certificate_number ? 'border-red-300' : ''
                                            }`}
                                            required
                                        />
                                        {errors.certificate_number && (
                                            <p className="mt-2 text-sm text-red-600">{errors.certificate_number}</p>
                                        )}
                                    </div>

                                    {/* Issuer */}
                                    <div>
                                        <label htmlFor="issuer" className="block text-sm font-medium text-gray-700">
                                            Issued By *
                                        </label>
                                        <input
                                            type="text"
                                            id="issuer"
                                            value={data.issuer}
                                            onChange={(e) => setData('issuer', e.target.value)}
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                errors.issuer ? 'border-red-300' : ''
                                            }`}
                                            required
                                        />
                                        {errors.issuer && (
                                            <p className="mt-2 text-sm text-red-600">{errors.issuer}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    {/* Issue Date */}
                                    <div>
                                        <label htmlFor="issue_date" className="block text-sm font-medium text-gray-700">
                                            Issue Date *
                                        </label>
                                        <input
                                            type="date"
                                            id="issue_date"
                                            value={data.issue_date}
                                            onChange={(e) => setData('issue_date', e.target.value)}
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                errors.issue_date ? 'border-red-300' : ''
                                            }`}
                                            required
                                        />
                                        {errors.issue_date && (
                                            <p className="mt-2 text-sm text-red-600">{errors.issue_date}</p>
                                        )}
                                    </div>

                                    {/* Expiry Date */}
                                    <div>
                                        <label htmlFor="expiry_date" className="block text-sm font-medium text-gray-700">
                                            Expiry Date *
                                        </label>
                                        <input
                                            type="date"
                                            id="expiry_date"
                                            value={data.expiry_date}
                                            onChange={(e) => setData('expiry_date', e.target.value)}
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                errors.expiry_date ? 'border-red-300' : ''
                                            }`}
                                            required
                                        />
                                        {errors.expiry_date && (
                                            <p className="mt-2 text-sm text-red-600">{errors.expiry_date}</p>
                                        )}
                                        {calculatedExpiry && calculatedExpiry !== data.expiry_date && (
                                            <p className="mt-1 text-xs text-blue-600">
                                                💡 Suggested expiry based on training type: {new Date(calculatedExpiry).toLocaleDateString()}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                {/* Notes */}
                                <div>
                                    <label htmlFor="notes" className="block text-sm font-medium text-gray-700">
                                        Notes
                                    </label>
                                    <textarea
                                        id="notes"
                                        rows={3}
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        placeholder="Additional notes about this training..."
                                    />
                                    {errors.notes && (
                                        <p className="mt-2 text-sm text-red-600">{errors.notes}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Form Actions */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4">
                                <div className="flex items-center justify-end space-x-4">
                                    <Link
                                        href={route('training-records.index')}
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        Cancel
                                    </Link>
                                    <Link
                                        href={route('training-records.show', trainingRecord?.id)}
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        <EyeIcon className="w-4 h-4 mr-2" />
                                        View Details
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing || !hasChanges}
                                        className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Updating...
                                            </>
                                        ) : hasChanges ? (
                                            <>
                                                <CheckCircleIcon className="w-4 h-4 mr-2" />
                                                Update Training Record
                                            </>
                                        ) : (
                                            'No Changes to Save'
                                        )}
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Debug Info in Development */}
                        {process.env.NODE_ENV === 'development' && (
                            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <h4 className="text-sm font-medium text-yellow-800">Debug Information</h4>
                                <pre className="text-xs text-yellow-700 mt-2">
                                    Form Data: {JSON.stringify(data, null, 2)}
                                </pre>
                                <pre className="text-xs text-yellow-700 mt-2">
                                    Has Changes: {hasChanges.toString()}
                                </pre>
                            </div>
                        )}

                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
