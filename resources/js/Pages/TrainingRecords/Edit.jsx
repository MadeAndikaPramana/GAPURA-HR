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
    BuildingOfficeIcon2,
    ClockIcon,
    EyeIcon
} from '@heroicons/react/24/outline';

export default function Edit({ auth, trainingRecord, employees, trainingTypes, departments }) {
    const { data, setData, put, processing, errors } = useForm({
        employee_id: trainingRecord.employee_id || '',
        training_type_id: trainingRecord.training_type_id || '',
        certificate_number: trainingRecord.certificate_number || '',
        issuer: trainingRecord.issuer || '',
        issue_date: trainingRecord.issue_date || '',
        expiry_date: trainingRecord.expiry_date || '',
        notes: trainingRecord.notes || ''
    });

    const [selectedTrainingType, setSelectedTrainingType] = useState(null);
    const [calculatedExpiry, setCalculatedExpiry] = useState('');
    const [hasChanges, setHasChanges] = useState(false);

    // Initialize selected training type
    useEffect(() => {
        if (data.training_type_id) {
            const type = trainingTypes.find(t => t.id == data.training_type_id);
            setSelectedTrainingType(type);
        }
    }, [data.training_type_id, trainingTypes]);

    // Calculate expiry date when training type or issue date changes
    useEffect(() => {
        if (data.training_type_id && data.issue_date) {
            const type = trainingTypes.find(t => t.id == data.training_type_id);
            if (type) {
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
        put(route('training-records.update', trainingRecord.id));
    };

    const getSelectedEmployee = () => {
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

    const getStatusColor = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            expiring_soon: 'bg-yellow-100 text-yellow-800',
            expired: 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const getValidityDescription = (months) => {
        if (months < 12) return `${months} month(s)`;
        const years = Math.floor(months / 12);
        const remainingMonths = months % 12;
        if (remainingMonths === 0) return `${years} year(s)`;
        return `${years} year(s) ${remainingMonths} month(s)`;
    };

    const getDaysUntilExpiry = () => {
        const expiry = new Date(data.expiry_date);
        const today = new Date();
        const diffTime = expiry - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    };

    const getExpiryWarning = () => {
        const daysLeft = getDaysUntilExpiry();
        if (daysLeft < 0) return { type: 'expired', message: `Expired ${Math.abs(daysLeft)} days ago` };
        if (daysLeft <= 7) return { type: 'critical', message: `Expires in ${daysLeft} days` };
        if (daysLeft <= 30) return { type: 'warning', message: `Expires in ${daysLeft} days` };
        return { type: 'good', message: `Valid for ${daysLeft} more days` };
    };

    const expiryWarning = getExpiryWarning();

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
                        <p className="text-sm text-gray-600 mt-1">
                            Certificate: {trainingRecord.certificate_number}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title={`Edit Training Record - ${trainingRecord.certificate_number}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">

                            {/* Current Status Warning */}
                            <div className={`mb-6 border rounded-md p-4 ${
                                expiryWarning.type === 'expired' ? 'bg-red-50 border-red-200' :
                                expiryWarning.type === 'critical' ? 'bg-orange-50 border-orange-200' :
                                expiryWarning.type === 'warning' ? 'bg-yellow-50 border-yellow-200' :
                                'bg-green-50 border-green-200'
                            }`}>
                                <div className="flex">
                                    <ExclamationTriangleIcon className={`w-5 h-5 mt-0.5 ${
                                        expiryWarning.type === 'expired' ? 'text-red-400' :
                                        expiryWarning.type === 'critical' ? 'text-orange-400' :
                                        expiryWarning.type === 'warning' ? 'text-yellow-400' :
                                        'text-green-400'
                                    }`} />
                                    <div className="ml-3">
                                        <h3 className={`text-sm font-medium ${
                                            expiryWarning.type === 'expired' ? 'text-red-800' :
                                            expiryWarning.type === 'critical' ? 'text-orange-800' :
                                            expiryWarning.type === 'warning' ? 'text-yellow-800' :
                                            'text-green-800'
                                        }`}>
                                            Certificate Status: {trainingRecord.status?.replace('_', ' ').toUpperCase()}
                                        </h3>
                                        <div className={`mt-2 text-sm ${
                                            expiryWarning.type === 'expired' ? 'text-red-700' :
                                            expiryWarning.type === 'critical' ? 'text-orange-700' :
                                            expiryWarning.type === 'warning' ? 'text-yellow-700' :
                                            'text-green-700'
                                        }`}>
                                            <p>{expiryWarning.message}</p>
                                            {(expiryWarning.type === 'expired' || expiryWarning.type === 'critical') && (
                                                <div className="mt-2">
                                                    <Link
                                                        href={route('training-records.renew', trainingRecord.id)}
                                                        className="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                                                    >
                                                        Renew Certificate
                                                    </Link>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>

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
                                                <li>Changes to validity dates will affect certificate status</li>
                                                <li>Employee and training type changes may require approval</li>
                                                <li>Certificate number should remain unique</li>
                                                <li>Status will be recalculated automatically</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">

                                    {/* Current Employee Info */}
                                    <div className="lg:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Current Employee
                                        </label>
                                        <div className="bg-gray-50 border border-gray-200 rounded-md p-4">
                                            <div className="flex items-center">
                                                <UserIcon className="w-5 h-5 text-gray-400 mr-3" />
                                                <div className="flex-1">
                                                    <div className="font-medium text-gray-900">{trainingRecord.employee?.name}</div>
                                                    <div className="text-sm text-gray-500">
                                                        ID: {trainingRecord.employee?.employee_id} •
                                                        {trainingRecord.employee?.department?.name} •
                                                        {trainingRecord.employee?.position}
                                                    </div>
                                                </div>
                                                <Link
                                                    href={route('employees.show', trainingRecord.employee?.id)}
                                                    className="text-green-600 hover:text-green-900"
                                                >
                                                    <EyeIcon className="w-5 h-5" />
                                                </Link>
                                            </div>
                                        </div>

                                        {/* Employee Change */}
                                        <div className="mt-3">
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
                                                    {employees.map(employee => (
                                                        <option key={employee.id} value={employee.id}>
                                                            {employee.name} ({employee.employee_id}) - {employee.department?.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            {data.employee_id != trainingRecord.employee_id && (
                                                <p className="mt-1 text-xs text-yellow-600">
                                                    ⚠️ Changing employee will transfer this certificate
                                                </p>
                                            )}
                                            {errors.employee_id && (
                                                <p className="mt-2 text-sm text-red-600">{errors.employee_id}</p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Training Type */}
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
                                                {trainingTypes.map(type => (
                                                    <option key={type.id} value={type.id}>
                                                        {type.name} ({type.code}) - {getValidityDescription(type.validity_months)}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        {data.training_type_id != trainingRecord.training_type_id && (
                                            <p className="mt-1 text-xs text-yellow-600">
                                                ⚠️ Changing training type will affect validity period
                                            </p>
                                        )}
                                        {errors.training_type_id && (
                                            <p className="mt-2 text-sm text-red-600">{errors.training_type_id}</p>
                                        )}
                                    </div>

                                    {/* Training Type Info */}
                                    {selectedTrainingType && (
                                        <div className="bg-gray-50 border border-gray-200 rounded-md p-4">
                                            <div className="flex items-center mb-2">
                                                <TagIcon className="w-5 h-5 text-gray-400 mr-2" />
                                                <h4 className="text-sm font-medium text-gray-900">Training Details</h4>
                                            </div>
                                            <div className="space-y-2 text-sm text-gray-600">
                                                <div className="flex items-center">
                                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getCategoryColor(selectedTrainingType.category)}`}>
                                                        {selectedTrainingType.category.charAt(0).toUpperCase() + selectedTrainingType.category.slice(1)}
                                                    </span>
                                                    <span className="ml-2">Validity: {getValidityDescription(selectedTrainingType.validity_months)}</span>
                                                </div>
                                                {selectedTrainingType.description && (
                                                    <p className="text-xs">{selectedTrainingType.description}</p>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    {/* Certificate Number */}
                                    <div>
                                        <label htmlFor="certificate_number" className="block text-sm font-medium text-gray-700">
                                            Certificate Number *
                                        </label>
                                        <div className="mt-1">
                                            <input
                                                type="text"
                                                id="certificate_number"
                                                value={data.certificate_number}
                                                onChange={(e) => setData('certificate_number', e.target.value)}
                                                className={`block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                    errors.certificate_number ? 'border-red-300' : ''
                                                }`}
                                                required
                                            />
                                        </div>
                                        {data.certificate_number !== trainingRecord.certificate_number && (
                                            <p className="mt-1 text-xs text-yellow-600">
                                                ⚠️ Ensure certificate number remains unique
                                            </p>
                                        )}
                                        {errors.certificate_number && (
                                            <p className="mt-2 text-sm text-red-600">{errors.certificate_number}</p>
                                        )}
                                    </div>

                                    {/* Issuer */}
                                    <div>
                                        <label htmlFor="issuer" className="block text-sm font-medium text-gray-700">
                                            Issuing Organization *
                                        </label>
                                        <div className="mt-1">
                                            <input
                                                type="text"
                                                id="issuer"
                                                value={data.issuer}
                                                onChange={(e) => setData('issuer', e.target.value)}
                                                className={`block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                    errors.issuer ? 'border-red-300' : ''
                                                }`}
                                                required
                                            />
                                        </div>
                                        {errors.issuer && (
                                            <p className="mt-2 text-sm text-red-600">{errors.issuer}</p>
                                        )}
                                    </div>

                                    {/* Issue Date */}
                                    <div>
                                        <label htmlFor="issue_date" className="block text-sm font-medium text-gray-700">
                                            Issue Date *
                                        </label>
                                        <div className="mt-1 relative">
                                            <CalendarIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                            <input
                                                type="date"
                                                id="issue_date"
                                                value={data.issue_date}
                                                onChange={(e) => setData('issue_date', e.target.value)}
                                                className={`block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                    errors.issue_date ? 'border-red-300' : ''
                                                }`}
                                                required
                                            />
                                        </div>
                                        {errors.issue_date && (
                                            <p className="mt-2 text-sm text-red-600">{errors.issue_date}</p>
                                        )}
                                    </div>

                                    {/* Expiry Date */}
                                    <div>
                                        <label htmlFor="expiry_date" className="block text-sm font-medium text-gray-700">
                                            Expiry Date *
                                        </label>
                                        <div className="mt-1 relative">
                                            <CalendarIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                            <input
                                                type="date"
                                                id="expiry_date"
                                                value={data.expiry_date}
                                                onChange={(e) => setData('expiry_date', e.target.value)}
                                                className={`block w-full pl-10 border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                    errors.expiry_date ? 'border-red-300' : ''
                                                }`}
                                                required
                                            />
                                        </div>
                                        {calculatedExpiry && data.expiry_date !== calculatedExpiry && (
                                            <p className="mt-1 text-xs text-blue-600">
                                                Suggested: {formatDate(calculatedExpiry)} (based on training validity)
                                            </p>
                                        )}
                                        {errors.expiry_date && (
                                            <p className="mt-2 text-sm text-red-600">{errors.expiry_date}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Notes */}
                                <div>
                                    <label htmlFor="notes" className="block text-sm font-medium text-gray-700">
                                        Notes
                                    </label>
                                    <div className="mt-1">
                                        <textarea
                                            id="notes"
                                            rows={3}
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            className={`block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm ${
                                                errors.notes ? 'border-red-300' : ''
                                            }`}
                                            placeholder="Additional notes about this training record..."
                                        />
                                    </div>
                                    {errors.notes && (
                                        <p className="mt-2 text-sm text-red-600">{errors.notes}</p>
                                    )}
                                </div>

                                {/* Changes Preview */}
                                {hasChanges && (
                                    <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                        <h4 className="text-sm font-medium text-yellow-800 mb-3 flex items-center">
                                            <ExclamationTriangleIcon className="w-4 h-4 mr-2" />
                                            Pending Changes
                                        </h4>
                                        <div className="text-sm text-yellow-700 space-y-2">
                                            {data.employee_id !== trainingRecord.employee_id && (
                                                <div>• Employee will be changed</div>
                                            )}
                                            {data.training_type_id !== trainingRecord.training_type_id && (
                                                <div>• Training type will be changed</div>
                                            )}
                                            {data.certificate_number !== trainingRecord.certificate_number && (
                                                <div>• Certificate number will be updated</div>
                                            )}
                                            {data.issuer !== trainingRecord.issuer && (
                                                <div>• Issuer will be updated</div>
                                            )}
                                            {data.issue_date !== trainingRecord.issue_date && (
                                                <div>• Issue date will be changed</div>
                                            )}
                                            {data.expiry_date !== trainingRecord.expiry_date && (
                                                <div>• Expiry date will be changed (may affect status)</div>
                                            )}
                                            {data.notes !== (trainingRecord.notes || '') && (
                                                <div>• Notes will be updated</div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Original Record Info */}
                                <div className="bg-gray-50 border border-gray-200 rounded-md p-4">
                                    <h4 className="text-sm font-medium text-gray-900 mb-3 flex items-center">
                                        <ClockIcon className="w-4 h-4 mr-2" />
                                        Original Record Information
                                    </h4>
                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span className="font-medium text-gray-700">Created:</span>
                                            <div className="text-gray-900">{formatDate(trainingRecord.created_at)}</div>
                                        </div>
                                        <div>
                                            <span className="font-medium text-gray-700">Last Updated:</span>
                                            <div className="text-gray-900">{formatDate(trainingRecord.updated_at)}</div>
                                        </div>
                                        <div>
                                            <span className="font-medium text-gray-700">Current Status:</span>
                                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(trainingRecord.status)}`}>
                                                {trainingRecord.status?.replace('_', ' ').toUpperCase()}
                                            </span>
                                        </div>
                                        <div>
                                            <span className="font-medium text-gray-700">Actions:</span>
                                            <div className="space-x-2">
                                                <Link
                                                    href={route('training-records.show', trainingRecord.id)}
                                                    className="text-green-600 hover:text-green-900 text-xs"
                                                >
                                                    View Details
                                                </Link>
                                                {(expiryWarning.type === 'expired' || expiryWarning.type === 'critical') && (
                                                    <Link
                                                        href={route('training-records.renew', trainingRecord.id)}
                                                        className="text-blue-600 hover:text-blue-900 text-xs"
                                                    >
                                                        Renew Certificate
                                                    </Link>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Form Actions */}
                                <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                                    <Link
                                        href={route('training-records.index')}
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        Cancel
                                    </Link>
                                    <Link
                                        href={route('training-records.show', trainingRecord.id)}
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
                                        {processing ? 'Updating...' : hasChanges ? 'Update Training Record' : 'No Changes to Save'}
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
