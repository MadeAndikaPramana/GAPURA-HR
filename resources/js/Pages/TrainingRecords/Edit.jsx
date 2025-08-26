import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon,
    PencilIcon,
    CalendarIcon,
    AcademicCapIcon,
    BuildingOfficeIcon,
    UserIcon,
    ExclamationTriangleIcon
} from '@heroicons/react/24/outline';

export default function Edit({
    auth,
    trainingRecord,
    employees,
    trainingTypes,
    trainingProviders, // ✅ Provider list
    departments
}) {
    const { data, setData, put, processing, errors } = useForm({
        employee_id: trainingRecord.employee_id || '',
        training_type_id: trainingRecord.training_type_id || '',
        training_provider_id: trainingRecord.training_provider_id || '', // ✅ Provider field
        certificate_number: trainingRecord.certificate_number || '',
        issuer: trainingRecord.issuer || '',
        issue_date: trainingRecord.issue_date || '',
        expiry_date: trainingRecord.expiry_date || '',
        completion_date: trainingRecord.completion_date || '',
        training_date: trainingRecord.training_date || '',
        score: trainingRecord.score || '',
        training_hours: trainingRecord.training_hours || '',
        cost: trainingRecord.cost || '',
        location: trainingRecord.location || '',
        instructor_name: trainingRecord.instructor_name || '',
        notes: trainingRecord.notes || ''
    });

    const [isExpired, setIsExpired] = useState(false);
    const [isExpiringSoon, setIsExpiringSoon] = useState(false);

    // Check certificate status
    useEffect(() => {
        if (data.expiry_date) {
            const expiryDate = new Date(data.expiry_date);
            const now = new Date();
            const thirtyDaysFromNow = new Date();
            thirtyDaysFromNow.setDate(now.getDate() + 30);

            setIsExpired(expiryDate < now);
            setIsExpiringSoon(!isExpired && expiryDate <= thirtyDaysFromNow);
        }
    }, [data.expiry_date]);

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('training-records.update', trainingRecord.id));
    };

    // Handle provider selection and auto-fill issuer
    const handleProviderChange = (providerId) => {
        setData('training_provider_id', providerId);

        // Auto-fill issuer based on selected provider (but only if current issuer matches provider name)
        const selectedProvider = trainingProviders.find(p => p.id == providerId);
        const currentProvider = trainingProviders.find(p => p.id == data.training_provider_id);

        if (selectedProvider && (!data.issuer || data.issuer === currentProvider?.name)) {
            setData(prev => ({
                ...prev,
                training_provider_id: providerId,
                issuer: selectedProvider.name
            }));
        } else {
            setData('training_provider_id', providerId);
        }
    };

    // Calculate expiry date based on training type validity
    const handleTrainingTypeChange = (trainingTypeId) => {
        setData('training_type_id', trainingTypeId);

        // Only auto-calculate expiry date if issue date is set and expiry is not manually set
        if (data.issue_date && trainingTypeId && !data.expiry_date) {
            const selectedType = trainingTypes.find(t => t.id == trainingTypeId);
            if (selectedType && selectedType.validity_months) {
                const issueDate = new Date(data.issue_date);
                const expiryDate = new Date(issueDate);
                expiryDate.setMonth(expiryDate.getMonth() + selectedType.validity_months);

                setData(prev => ({
                    ...prev,
                    training_type_id: trainingTypeId,
                    expiry_date: expiryDate.toISOString().split('T')[0]
                }));
            }
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center space-x-4">
                    <button
                        onClick={() => window.history.back()}
                        className="inline-flex items-center text-gray-600 hover:text-gray-900"
                    >
                        <ArrowLeftIcon className="w-5 h-5 mr-1" />
                        Back
                    </button>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Edit Training Record
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Update training certificate record for {trainingRecord.employee?.name}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Edit Training Record" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {/* Certificate Status Alert */}
                    {(isExpired || isExpiringSoon) && (
                        <div className={`rounded-md p-4 mb-6 ${isExpired ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200'}`}>
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <ExclamationTriangleIcon
                                        className={`h-5 w-5 ${isExpired ? 'text-red-400' : 'text-yellow-400'}`}
                                        aria-hidden="true"
                                    />
                                </div>
                                <div className="ml-3">
                                    <h3 className={`text-sm font-medium ${isExpired ? 'text-red-800' : 'text-yellow-800'}`}>
                                        {isExpired ? 'Certificate Expired' : 'Certificate Expiring Soon'}
                                    </h3>
                                    <div className={`mt-2 text-sm ${isExpired ? 'text-red-700' : 'text-yellow-700'}`}>
                                        <p>
                                            This certificate {isExpired ? 'expired' : 'expires'} on{' '}
                                            {new Date(data.expiry_date).toLocaleDateString()}.
                                            {isExpired ? ' Consider renewing it.' : ' Please plan for renewal.'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {/* Header */}
                            <div className="flex items-center mb-6">
                                <PencilIcon className="w-6 h-6 text-green-600 mr-3" />
                                <h3 className="text-lg font-medium text-gray-900">Edit Training Record Details</h3>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Employee & Training Info Section */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Employee Selection */}
                                    <div>
                                        <label htmlFor="employee_id" className="block text-sm font-medium text-gray-700 mb-2">
                                            <UserIcon className="w-4 h-4 inline mr-1" />
                                            Employee *
                                        </label>
                                        <select
                                            id="employee_id"
                                            value={data.employee_id}
                                            onChange={(e) => setData('employee_id', e.target.value)}
                                            required
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.employee_id ? 'border-red-300' : ''}`}
                                        >
                                            <option value="">Select Employee</option>
                                            {employees && employees.map((employee) => (
                                                <option key={employee.id} value={employee.id}>
                                                    {employee.employee_id} - {employee.name}
                                                    {employee.department && ` (${employee.department.name})`}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.employee_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.employee_id}</p>
                                        )}
                                    </div>

                                    {/* Training Type */}
                                    <div>
                                        <label htmlFor="training_type_id" className="block text-sm font-medium text-gray-700 mb-2">
                                            <AcademicCapIcon className="w-4 h-4 inline mr-1" />
                                            Training Type *
                                        </label>
                                        <select
                                            id="training_type_id"
                                            value={data.training_type_id}
                                            onChange={(e) => handleTrainingTypeChange(e.target.value)}
                                            required
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.training_type_id ? 'border-red-300' : ''}`}
                                        >
                                            <option value="">Select Training Type</option>
                                            {trainingTypes && trainingTypes.map((type) => (
                                                <option key={type.id} value={type.id}>
                                                    {type.name} ({type.code})
                                                    {type.validity_months && ` - Valid for ${type.validity_months} months`}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.training_type_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.training_type_id}</p>
                                        )}
                                    </div>
                                </div>

                                {/* ✅ Provider & Issuer Section */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Training Provider Dropdown */}
                                    <div>
                                        <label htmlFor="training_provider_id" className="block text-sm font-medium text-gray-700 mb-2">
                                            <BuildingOfficeIcon className="w-4 h-4 inline mr-1" />
                                            Training Provider
                                        </label>
                                        <select
                                            id="training_provider_id"
                                            value={data.training_provider_id}
                                            onChange={(e) => handleProviderChange(e.target.value)}
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.training_provider_id ? 'border-red-300' : ''}`}
                                        >
                                            <option value="">Select Provider (Optional)</option>
                                            {trainingProviders && trainingProviders.map((provider) => (
                                                <option key={provider.id} value={provider.id}>
                                                    {provider.code ? `${provider.code} - ${provider.name}` : provider.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.training_provider_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.training_provider_id}</p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500">
                                            Current provider: {trainingRecord.training_provider?.name || 'None selected'}
                                        </p>
                                    </div>

                                    {/* Issuer (Auto-filled from provider or manual input) */}
                                    <div>
                                        <label htmlFor="issuer" className="block text-sm font-medium text-gray-700 mb-2">
                                            Certificate Issuer *
                                        </label>
                                        <input
                                            type="text"
                                            id="issuer"
                                            value={data.issuer}
                                            onChange={(e) => setData('issuer', e.target.value)}
                                            required
                                            placeholder="Organization that issued the certificate"
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.issuer ? 'border-red-300' : ''}`}
                                        />
                                        {errors.issuer && (
                                            <p className="mt-1 text-sm text-red-600">{errors.issuer}</p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500">
                                            Auto-filled when provider is selected, or enter manually
                                        </p>
                                    </div>
                                </div>

                                {/* Certificate Information */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Certificate Number */}
                                    <div>
                                        <label htmlFor="certificate_number" className="block text-sm font-medium text-gray-700 mb-2">
                                            Certificate Number *
                                        </label>
                                        <input
                                            type="text"
                                            id="certificate_number"
                                            value={data.certificate_number}
                                            onChange={(e) => setData('certificate_number', e.target.value)}
                                            required
                                            placeholder="e.g., MPGA-2024-001"
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.certificate_number ? 'border-red-300' : ''}`}
                                        />
                                        {errors.certificate_number && (
                                            <p className="mt-1 text-sm text-red-600">{errors.certificate_number}</p>
                                        )}
                                    </div>

                                    {/* Score */}
                                    <div>
                                        <label htmlFor="score" className="block text-sm font-medium text-gray-700 mb-2">
                                            Training Score
                                        </label>
                                        <input
                                            type="number"
                                            id="score"
                                            value={data.score}
                                            onChange={(e) => setData('score', e.target.value)}
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            placeholder="0-100"
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.score ? 'border-red-300' : ''}`}
                                        />
                                        {errors.score && (
                                            <p className="mt-1 text-sm text-red-600">{errors.score}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Dates Section */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    {/* Issue Date */}
                                    <div>
                                        <label htmlFor="issue_date" className="block text-sm font-medium text-gray-700 mb-2">
                                            <CalendarIcon className="w-4 h-4 inline mr-1" />
                                            Issue Date *
                                        </label>
                                        <input
                                            type="date"
                                            id="issue_date"
                                            value={data.issue_date}
                                            onChange={(e) => setData('issue_date', e.target.value)}
                                            required
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.issue_date ? 'border-red-300' : ''}`}
                                        />
                                        {errors.issue_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.issue_date}</p>
                                        )}
                                    </div>

                                    {/* Training Date */}
                                    <div>
                                        <label htmlFor="training_date" className="block text-sm font-medium text-gray-700 mb-2">
                                            Training Date
                                        </label>
                                        <input
                                            type="date"
                                            id="training_date"
                                            value={data.training_date}
                                            onChange={(e) => setData('training_date', e.target.value)}
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.training_date ? 'border-red-300' : ''}`}
                                        />
                                        {errors.training_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.training_date}</p>
                                        )}
                                    </div>

                                    {/* Expiry Date */}
                                    <div>
                                        <label htmlFor="expiry_date" className="block text-sm font-medium text-gray-700 mb-2">
                                            Expiry Date
                                            {(isExpired || isExpiringSoon) && (
                                                <span className={`ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${isExpired ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'}`}>
                                                    {isExpired ? 'Expired' : 'Expiring Soon'}
                                                </span>
                                            )}
                                        </label>
                                        <input
                                            type="date"
                                            id="expiry_date"
                                            value={data.expiry_date}
                                            onChange={(e) => setData('expiry_date', e.target.value)}
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.expiry_date ? 'border-red-300' : ''} ${isExpired ? 'border-red-300' : isExpiringSoon ? 'border-yellow-300' : ''}`}
                                        />
                                        {errors.expiry_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.expiry_date}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Additional Information */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    {/* Training Hours */}
                                    <div>
                                        <label htmlFor="training_hours" className="block text-sm font-medium text-gray-700 mb-2">
                                            Training Hours
                                        </label>
                                        <input
                                            type="number"
                                            id="training_hours"
                                            value={data.training_hours}
                                            onChange={(e) => setData('training_hours', e.target.value)}
                                            min="0"
                                            step="0.5"
                                            placeholder="e.g., 8.5"
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.training_hours ? 'border-red-300' : ''}`}
                                        />
                                        {errors.training_hours && (
                                            <p className="mt-1 text-sm text-red-600">{errors.training_hours}</p>
                                        )}
                                    </div>

                                    {/* Cost */}
                                    <div>
                                        <label htmlFor="cost" className="block text-sm font-medium text-gray-700 mb-2">
                                            Training Cost (IDR)
                                        </label>
                                        <input
                                            type="number"
                                            id="cost"
                                            value={data.cost}
                                            onChange={(e) => setData('cost', e.target.value)}
                                            min="0"
                                            step="1000"
                                            placeholder="e.g., 2500000"
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.cost ? 'border-red-300' : ''}`}
                                        />
                                        {errors.cost && (
                                            <p className="mt-1 text-sm text-red-600">{errors.cost}</p>
                                        )}
                                    </div>

                                    {/* Location */}
                                    <div>
                                        <label htmlFor="location" className="block text-sm font-medium text-gray-700 mb-2">
                                            Training Location
                                        </label>
                                        <input
                                            type="text"
                                            id="location"
                                            value={data.location}
                                            onChange={(e) => setData('location', e.target.value)}
                                            placeholder="e.g., Jakarta Training Center"
                                            className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.location ? 'border-red-300' : ''}`}
                                        />
                                        {errors.location && (
                                            <p className="mt-1 text-sm text-red-600">{errors.location}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Instructor Name */}
                                <div>
                                    <label htmlFor="instructor_name" className="block text-sm font-medium text-gray-700 mb-2">
                                        Instructor Name
                                    </label>
                                    <input
                                        type="text"
                                        id="instructor_name"
                                        value={data.instructor_name}
                                        onChange={(e) => setData('instructor_name', e.target.value)}
                                        placeholder="Name of the training instructor"
                                        className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.instructor_name ? 'border-red-300' : ''}`}
                                    />
                                    {errors.instructor_name && (
                                        <p className="mt-1 text-sm text-red-600">{errors.instructor_name}</p>
                                    )}
                                </div>

                                {/* Notes */}
                                <div>
                                    <label htmlFor="notes" className="block text-sm font-medium text-gray-700 mb-2">
                                        Notes
                                    </label>
                                    <textarea
                                        id="notes"
                                        rows={3}
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        placeholder="Additional notes about the training..."
                                        className={`mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${errors.notes ? 'border-red-300' : ''}`}
                                    />
                                    {errors.notes && (
                                        <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
                                    )}
                                </div>

                                {/* Submit Buttons */}
                                <div className="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                                    <button
                                        type="button"
                                        onClick={() => window.history.back()}
                                        className="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'Updating...' : 'Update Training Record'}
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
