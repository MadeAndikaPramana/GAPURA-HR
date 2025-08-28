import React, { useState, useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon,
    UserIcon,
    AcademicCapIcon,
    BuildingOfficeIcon,
    ShieldCheckIcon,
    CalendarIcon,
    DocumentTextIcon,
    StarIcon,
    InformationCircleIcon
} from '@heroicons/react/24/outline';

export default function CertificateCreateEdit({
    auth,
    certificate = null,
    employees,
    trainingTypes,
    trainingProviders,
    prePopulateData = null
}) {
    const isEditing = !!certificate;
    const [selectedEmployee, setSelectedEmployee] = useState(null);
    const [selectedTrainingType, setSelectedTrainingType] = useState(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        training_record_id: certificate?.training_record_id || prePopulateData?.training_record_id || '',
        employee_id: certificate?.employee_id || prePopulateData?.employee_id || '',
        training_type_id: certificate?.training_type_id || prePopulateData?.training_type_id || '',
        training_provider_id: certificate?.training_provider_id || prePopulateData?.training_provider_id || '',
        certificate_type: certificate?.certificate_type || 'completion',
        issuer_name: certificate?.issuer_name || '',
        issuer_title: certificate?.issuer_title || '',
        issuer_organization: certificate?.issuer_organization || '',
        issue_date: certificate?.issue_date || prePopulateData?.issue_date || '',
        effective_date: certificate?.effective_date || '',
        expiry_date: certificate?.expiry_date || prePopulateData?.expiry_date || '',
        status: certificate?.status || 'issued',
        verification_status: certificate?.verification_status || 'pending',
        score: certificate?.score || prePopulateData?.score || '',
        passing_score: certificate?.passing_score || prePopulateData?.passing_score || '',
        achievements: certificate?.achievements || '',
        remarks: certificate?.remarks || '',
        is_renewable: certificate?.is_renewable ?? true,
        is_compliance_required: certificate?.is_compliance_required ?? false,
        compliance_status: certificate?.compliance_status || 'pending',
        notes: certificate?.notes || '',
    });

    // Update selected employee when employee_id changes
    useEffect(() => {
        if (data.employee_id) {
            const employee = employees.find(emp => emp.id.toString() === data.employee_id.toString());
            setSelectedEmployee(employee);
        }
    }, [data.employee_id, employees]);

    // Update selected training type when training_type_id changes
    useEffect(() => {
        if (data.training_type_id) {
            const trainingType = trainingTypes.find(type => type.id.toString() === data.training_type_id.toString());
            setSelectedTrainingType(trainingType);

            // Auto-set expiry date based on training type validity period
            if (trainingType && trainingType.validity_period_months && data.issue_date && !data.expiry_date) {
                const issueDate = new Date(data.issue_date);
                const expiryDate = new Date(issueDate);
                expiryDate.setMonth(expiryDate.getMonth() + trainingType.validity_period_months);
                setData('expiry_date', expiryDate.toISOString().split('T')[0]);
            }
        }
    }, [data.training_type_id, trainingTypes, data.issue_date]);

    // Auto-set issuer organization when training provider changes
    useEffect(() => {
        if (data.training_provider_id && !data.issuer_organization) {
            const provider = trainingProviders.find(prov => prov.id.toString() === data.training_provider_id.toString());
            if (provider) {
                setData('issuer_organization', provider.name);
            }
        }
    }, [data.training_provider_id, trainingProviders]);

    const handleSubmit = (e) => {
        e.preventDefault();

        if (isEditing) {
            put(route('certificates.update', certificate.id));
        } else {
            post(route('certificates.store'));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={isEditing ? route('certificates.show', certificate.id) : route('certificates.index')}
                            className="flex items-center text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeftIcon className="w-5 h-5 mr-2" />
                            {isEditing ? 'Back to Certificate' : 'Back to Certificates'}
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {isEditing ? 'Edit Certificate' : 'Create New Certificate'}
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                {isEditing
                                    ? `Update certificate details for ${certificate.certificate_number}`
                                    : 'Add a new certificate to the system'
                                }
                            </p>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title={isEditing ? 'Edit Certificate' : 'Create Certificate'} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Form */}
                    <form onSubmit={handleSubmit} className="space-y-6">

                        {/* Employee & Training Selection */}
                        <div className="bg-white shadow-sm rounded-lg border">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <UserIcon className="w-5 h-5 mr-2" />
                                    Employee & Training Selection
                                </h3>
                                <p className="text-sm text-gray-500 mt-1">
                                    Select the employee and training type for this certificate
                                </p>
                            </div>
                            <div className="px-6 py-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    <div>
                                        <label htmlFor="employee_id" className="block text-sm font-medium text-gray-700">
                                            Employee *
                                        </label>
                                        <select
                                            id="employee_id"
                                            value={data.employee_id}
                                            onChange={(e) => setData('employee_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            required
                                            disabled={isEditing}
                                        >
                                            <option value="">Select Employee</option>
                                            {employees.map((employee) => (
                                                <option key={employee.id} value={employee.id}>
                                                    {employee.name} - {employee.nip} ({employee.department.name})
                                                </option>
                                            ))}
                                        </select>
                                        {errors.employee_id && <p className="mt-1 text-sm text-red-600">{errors.employee_id}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="training_type_id" className="block text-sm font-medium text-gray-700">
                                            Training Type *
                                        </label>
                                        <select
                                            id="training_type_id"
                                            value={data.training_type_id}
                                            onChange={(e) => setData('training_type_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            required
                                            disabled={isEditing}
                                        >
                                            <option value="">Select Training Type</option>
                                            {trainingTypes.map((type) => (
                                                <option key={type.id} value={type.id}>
                                                    {type.name} ({type.category})
                                                </option>
                                            ))}
                                        </select>
                                        {errors.training_type_id && <p className="mt-1 text-sm text-red-600">{errors.training_type_id}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="training_provider_id" className="block text-sm font-medium text-gray-700">
                                            Training Provider
                                        </label>
                                        <select
                                            id="training_provider_id"
                                            value={data.training_provider_id}
                                            onChange={(e) => setData('training_provider_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        >
                                            <option value="">Select Provider (Optional)</option>
                                            {trainingProviders.map((provider) => (
                                                <option key={provider.id} value={provider.id}>
                                                    {provider.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.training_provider_id && <p className="mt-1 text-sm text-red-600">{errors.training_provider_id}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="certificate_type" className="block text-sm font-medium text-gray-700">
                                            Certificate Type *
                                        </label>
                                        <select
                                            id="certificate_type"
                                            value={data.certificate_type}
                                            onChange={(e) => setData('certificate_type', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            required
                                        >
                                            <option value="completion">Certificate of Completion</option>
                                            <option value="competency">Certificate of Competency</option>
                                            <option value="compliance">Compliance Certificate</option>
                                        </select>
                                        {errors.certificate_type && <p className="mt-1 text-sm text-red-600">{errors.certificate_type}</p>}
                                    </div>
                                </div>

                                {/* Selected Employee/Training Info */}
                                {(selectedEmployee || selectedTrainingType) && (
                                    <div className="mt-6 p-4 bg-blue-50 rounded-lg">
                                        <h4 className="text-sm font-medium text-blue-900 mb-2">Selection Summary</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                            {selectedEmployee && (
                                                <div>
                                                    <strong>Employee:</strong> {selectedEmployee.name} ({selectedEmployee.nip})
                                                    <br />
                                                    <span className="text-gray-600">Department: {selectedEmployee.department.name}</span>
                                                </div>
                                            )}
                                            {selectedTrainingType && (
                                                <div>
                                                    <strong>Training:</strong> {selectedTrainingType.name}
                                                    <br />
                                                    <span className="text-gray-600">
                                                        Category: {selectedTrainingType.category}
                                                        {selectedTrainingType.validity_period_months && (
                                                            <span> • Valid for: {selectedTrainingType.validity_period_months} months</span>
                                                        )}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Certificate Details */}
                        <div className="bg-white shadow-sm rounded-lg border">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <ShieldCheckIcon className="w-5 h-5 mr-2" />
                                    Certificate Details
                                </h3>
                                <p className="text-sm text-gray-500 mt-1">
                                    Enter certificate issuance and validity details
                                </p>
                            </div>
                            <div className="px-6 py-4">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">

                                    <div>
                                        <label htmlFor="issue_date" className="block text-sm font-medium text-gray-700">
                                            Issue Date *
                                        </label>
                                        <input
                                            type="date"
                                            id="issue_date"
                                            value={data.issue_date}
                                            onChange={(e) => setData('issue_date', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            required
                                        />
                                        {errors.issue_date && <p className="mt-1 text-sm text-red-600">{errors.issue_date}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="effective_date" className="block text-sm font-medium text-gray-700">
                                            Effective Date
                                        </label>
                                        <input
                                            type="date"
                                            id="effective_date"
                                            value={data.effective_date}
                                            onChange={(e) => setData('effective_date', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        />
                                        {errors.effective_date && <p className="mt-1 text-sm text-red-600">{errors.effective_date}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="expiry_date" className="block text-sm font-medium text-gray-700">
                                            Expiry Date
                                        </label>
                                        <input
                                            type="date"
                                            id="expiry_date"
                                            value={data.expiry_date}
                                            onChange={(e) => setData('expiry_date', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        />
                                        {errors.expiry_date && <p className="mt-1 text-sm text-red-600">{errors.expiry_date}</p>}
                                        <p className="mt-1 text-xs text-gray-500">
                                            Leave empty for certificates that don't expire
                                        </p>
                                    </div>
                                </div>

                                {isEditing && (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                        <div>
                                            <label htmlFor="status" className="block text-sm font-medium text-gray-700">
                                                Certificate Status
                                            </label>
                                            <select
                                                id="status"
                                                value={data.status}
                                                onChange={(e) => setData('status', e.target.value)}
                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            >
                                                <option value="draft">Draft</option>
                                                <option value="issued">Issued</option>
                                                <option value="revoked">Revoked</option>
                                                <option value="expired">Expired</option>
                                                <option value="renewed">Renewed</option>
                                            </select>
                                            {errors.status && <p className="mt-1 text-sm text-red-600">{errors.status}</p>}
                                        </div>

                                        <div>
                                            <label htmlFor="verification_status" className="block text-sm font-medium text-gray-700">
                                                Verification Status
                                            </label>
                                            <select
                                                id="verification_status"
                                                value={data.verification_status}
                                                onChange={(e) => setData('verification_status', e.target.value)}
                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            >
                                                <option value="pending">Pending</option>
                                                <option value="verified">Verified</option>
                                                <option value="invalid">Invalid</option>
                                                <option value="under_review">Under Review</option>
                                            </select>
                                            {errors.verification_status && <p className="mt-1 text-sm text-red-600">{errors.verification_status}</p>}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Issuer Information */}
                        <div className="bg-white shadow-sm rounded-lg border">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <BuildingOfficeIcon className="w-5 h-5 mr-2" />
                                    Issuer Information
                                </h3>
                                <p className="text-sm text-gray-500 mt-1">
                                    Details about who issued this certificate
                                </p>
                            </div>
                            <div className="px-6 py-4">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">

                                    <div>
                                        <label htmlFor="issuer_name" className="block text-sm font-medium text-gray-700">
                                            Issuer Name *
                                        </label>
                                        <input
                                            type="text"
                                            id="issuer_name"
                                            value={data.issuer_name}
                                            onChange={(e) => setData('issuer_name', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            placeholder="e.g., Dr. Ahmad Wijaya"
                                            required
                                        />
                                        {errors.issuer_name && <p className="mt-1 text-sm text-red-600">{errors.issuer_name}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="issuer_title" className="block text-sm font-medium text-gray-700">
                                            Issuer Title
                                        </label>
                                        <input
                                            type="text"
                                            id="issuer_title"
                                            value={data.issuer_title}
                                            onChange={(e) => setData('issuer_title', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            placeholder="e.g., Training Director"
                                        />
                                        {errors.issuer_title && <p className="mt-1 text-sm text-red-600">{errors.issuer_title}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="issuer_organization" className="block text-sm font-medium text-gray-700">
                                            Organization
                                        </label>
                                        <input
                                            type="text"
                                            id="issuer_organization"
                                            value={data.issuer_organization}
                                            onChange={(e) => setData('issuer_organization', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            placeholder="e.g., GAPURA TRAINING CENTER"
                                        />
                                        {errors.issuer_organization && <p className="mt-1 text-sm text-red-600">{errors.issuer_organization}</p>}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Performance & Additional Details */}
                        <div className="bg-white shadow-sm rounded-lg border">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <StarIcon className="w-5 h-5 mr-2" />
                                    Performance & Additional Details
                                </h3>
                                <p className="text-sm text-gray-500 mt-1">
                                    Training performance, achievements, and additional notes
                                </p>
                            </div>
                            <div className="px-6 py-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    <div>
                                        <label htmlFor="score" className="block text-sm font-medium text-gray-700">
                                            Score (%)
                                        </label>
                                        <input
                                            type="number"
                                            id="score"
                                            value={data.score}
                                            onChange={(e) => setData('score', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                        />
                                        {errors.score && <p className="mt-1 text-sm text-red-600">{errors.score}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="passing_score" className="block text-sm font-medium text-gray-700">
                                            Passing Score (%)
                                        </label>
                                        <input
                                            type="number"
                                            id="passing_score"
                                            value={data.passing_score}
                                            onChange={(e) => setData('passing_score', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                        />
                                        {errors.passing_score && <p className="mt-1 text-sm text-red-600">{errors.passing_score}</p>}
                                    </div>
                                </div>

                                <div className="mt-6">
                                    <label htmlFor="achievements" className="block text-sm font-medium text-gray-700">
                                        Achievements
                                    </label>
                                    <textarea
                                        id="achievements"
                                        rows={3}
                                        value={data.achievements}
                                        onChange={(e) => setData('achievements', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        placeholder="e.g., Successfully completed all training modules; Demonstrated competency in required skills; Passed practical and theoretical assessments"
                                    />
                                    {errors.achievements && <p className="mt-1 text-sm text-red-600">{errors.achievements}</p>}
                                </div>

                                <div className="mt-6">
                                    <label htmlFor="remarks" className="block text-sm font-medium text-gray-700">
                                        Remarks
                                    </label>
                                    <textarea
                                        id="remarks"
                                        rows={2}
                                        value={data.remarks}
                                        onChange={(e) => setData('remarks', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        placeholder="Any special remarks or conditions"
                                    />
                                    {errors.remarks && <p className="mt-1 text-sm text-red-600">{errors.remarks}</p>}
                                </div>

                                <div className="mt-6">
                                    <label htmlFor="notes" className="block text-sm font-medium text-gray-700">
                                        Internal Notes
                                    </label>
                                    <textarea
                                        id="notes"
                                        rows={2}
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        placeholder="Internal notes (not visible on certificate)"
                                    />
                                    {errors.notes && <p className="mt-1 text-sm text-red-600">{errors.notes}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Compliance & Settings */}
                        <div className="bg-white shadow-sm rounded-lg border">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <InformationCircleIcon className="w-5 h-5 mr-2" />
                                    Compliance & Settings
                                </h3>
                                <p className="text-sm text-gray-500 mt-1">
                                    Certificate compliance and renewal settings
                                </p>
                            </div>
                            <div className="px-6 py-4">
                                <div className="space-y-4">

                                    <div className="flex items-center">
                                        <input
                                            id="is_renewable"
                                            type="checkbox"
                                            checked={data.is_renewable}
                                            onChange={(e) => setData('is_renewable', e.target.checked)}
                                            className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="is_renewable" className="ml-2 block text-sm text-gray-900">
                                            Certificate is renewable
                                        </label>
                                    </div>

                                    <div className="flex items-center">
                                        <input
                                            id="is_compliance_required"
                                            type="checkbox"
                                            checked={data.is_compliance_required}
                                            onChange={(e) => setData('is_compliance_required', e.target.checked)}
                                            className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="is_compliance_required" className="ml-2 block text-sm text-gray-900">
                                            Compliance tracking required
                                        </label>
                                    </div>

                                    {isEditing && data.is_compliance_required && (
                                        <div>
                                            <label htmlFor="compliance_status" className="block text-sm font-medium text-gray-700">
                                                Compliance Status
                                            </label>
                                            <select
                                                id="compliance_status"
                                                value={data.compliance_status}
                                                onChange={(e) => setData('compliance_status', e.target.value)}
                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm max-w-xs"
                                            >
                                                <option value="pending">Pending</option>
                                                <option value="compliant">Compliant</option>
                                                <option value="non_compliant">Non-Compliant</option>
                                                <option value="exempt">Exempt</option>
                                            </select>
                                            {errors.compliance_status && <p className="mt-1 text-sm text-red-600">{errors.compliance_status}</p>}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Form Actions */}
                        <div className="flex items-center justify-end space-x-4 bg-white px-6 py-4 border-t border-gray-200 rounded-lg">
                            <Link
                                href={isEditing ? route('certificates.show', certificate.id) : route('certificates.index')}
                                className="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? (
                                    <>
                                        <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {isEditing ? 'Updating...' : 'Creating...'}
                                    </>
                                ) : (
                                    <>
                                        <ShieldCheckIcon className="w-4 h-4 mr-2" />
                                        {isEditing ? 'Update Certificate' : 'Create Certificate'}
                                    </>
                                )}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
