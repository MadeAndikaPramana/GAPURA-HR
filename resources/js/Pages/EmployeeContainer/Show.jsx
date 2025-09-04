// resources/js/Pages/EmployeeContainers/Show.jsx
// Simple Individual Employee Container View

import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon,
    UserIcon,
    DocumentIcon,
    PlusIcon,
    CalendarIcon,
    BuildingOfficeIcon,
    IdentificationIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, employee, containerData, certificateTypes }) {
    const [showAddCertificate, setShowAddCertificate] = useState(false);
    const [showAddBackgroundCheck, setShowAddBackgroundCheck] = useState(false);

    const { profile, background_check, certificates } = containerData;

    const getStatusBadge = (status) => {
        const configs = {
            active: { bg: 'bg-green-100', text: 'text-green-800', label: '✅ Active' },
            expired: { bg: 'bg-red-100', text: 'text-red-800', label: '❌ Expired' },
            expiring_soon: { bg: 'bg-yellow-100', text: 'text-yellow-800', label: '⚠️ Expiring Soon' },
            pending: { bg: 'bg-blue-100', text: 'text-blue-800', label: '⏳ Pending' },
            completed: { bg: 'bg-green-100', text: 'text-green-800', label: '✅ Completed' }
        };

        const config = configs[status] || configs.active;

        return (
            <span className={`inline-flex items-center px-2 py-1 rounded text-sm font-medium ${config.bg} ${config.text}`}>
                {config.label}
            </span>
        );
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`${employee.name} - Employee Container`} />

            <div className="py-6">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <Link
                                    href={route('employee-containers.index')}
                                    className="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 transition-colors"
                                >
                                    <ArrowLeftIcon className="w-5 h-5 text-slate-600" />
                                </Link>

                                <div className="flex items-center space-x-4">
                                    <div className="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center">
                                        <span className="text-white font-bold text-2xl">
                                            {employee.name.charAt(0).toUpperCase()}
                                        </span>
                                    </div>
                                    <div>
                                        <h1 className="text-3xl font-bold text-slate-900">
                                            {employee.name}
                                        </h1>
                                        <p className="text-lg text-slate-600">
                                            NIP: {employee.employee_id}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Container Content */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Employee Info */}
                        <div className="bg-white rounded-lg border border-slate-200 p-6">
                            <h2 className="text-lg font-semibold text-slate-900 mb-4 flex items-center">
                                <UserIcon className="w-5 h-5 mr-2 text-green-600" />
                                Employee Information
                            </h2>

                            <div className="space-y-4">
                                <div>
                                    <dt className="text-sm font-medium text-slate-500">Department</dt>
                                    <dd className="text-sm text-slate-900 mt-1">
                                        {profile.department || 'Not assigned'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500">Position</dt>
                                    <dd className="text-sm text-slate-900 mt-1">
                                        {profile.position || 'Not assigned'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500">Hire Date</dt>
                                    <dd className="text-sm text-slate-900 mt-1">
                                        {profile.hire_date || 'Not available'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500">Status</dt>
                                    <dd className="text-sm text-slate-900 mt-1">
                                        <span className={`inline-flex items-center px-2 py-1 rounded text-xs font-medium ${
                                            profile.status === 'active'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800'
                                        }`}>
                                            {profile.status === 'active' ? 'Active' : 'Inactive'}
                                        </span>
                                    </dd>
                                </div>
                            </div>
                        </div>

                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-8">
                            {/* Background Check Section */}
                            <div className="bg-white rounded-lg border border-slate-200 p-6">
                                <div className="flex items-center justify-between mb-6">
                                    <h2 className="text-lg font-semibold text-slate-900 flex items-center">
                                        <IdentificationIcon className="w-5 h-5 mr-2 text-blue-600" />
                                        Background Check
                                    </h2>
                                    <button
                                        onClick={() => setShowAddBackgroundCheck(true)}
                                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
                                    >
                                        <PlusIcon className="w-4 h-4 mr-2 inline" />
                                        Upload Documents
                                    </button>
                                </div>

                                {background_check.date ? (
                                    <div className="space-y-4">
                                        <div className="flex items-center space-x-4">
                                            <span className="text-sm text-slate-500">Status:</span>
                                            {getStatusBadge(background_check.status || 'not_started')}
                                        </div>
                                        <div>
                                            <span className="text-sm text-slate-500">Date: </span>
                                            <span className="text-sm text-slate-900">{background_check.date}</span>
                                        </div>
                                        {background_check.files_count > 0 && (
                                            <div>
                                                <span className="text-sm text-slate-500">Files: </span>
                                                <span className="text-sm text-slate-900">{background_check.files_count} document(s)</span>
                                            </div>
                                        )}
                                        {background_check.notes && (
                                            <div>
                                                <span className="text-sm text-slate-500">Notes: </span>
                                                <span className="text-sm text-slate-700">{background_check.notes}</span>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <DocumentIcon className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                                        <p className="text-slate-500 text-sm">No background check data available</p>
                                    </div>
                                )}
                            </div>

                            {/* Certificates Section */}
                            <div className="bg-white rounded-lg border border-slate-200 p-6">
                                <div className="flex items-center justify-between mb-6">
                                    <h2 className="text-lg font-semibold text-slate-900 flex items-center">
                                        <DocumentIcon className="w-5 h-5 mr-2 text-purple-600" />
                                        Certificates ({certificates.length})
                                    </h2>
                                    <button
                                        onClick={() => setShowAddCertificate(true)}
                                        className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium"
                                    >
                                        <PlusIcon className="w-4 h-4 mr-2 inline" />
                                        Add Certificate
                                    </button>
                                </div>

                                {certificates.length > 0 ? (
                                    <div className="space-y-4">
                                        {certificates.map((certType, index) => (
                                            <div key={certType.type_id} className="border border-slate-200 rounded-lg p-4">
                                                <div className="flex items-center justify-between mb-3">
                                                    <h3 className="font-medium text-slate-900">
                                                        {certType.type_name}
                                                    </h3>
                                                    <span className="text-sm text-slate-500">
                                                        {certType.total_count} certificate(s)
                                                    </span>
                                                </div>

                                                {/* Current Certificate */}
                                                {certType.current && (
                                                    <div className="bg-green-50 border border-green-200 rounded p-3 mb-3">
                                                        <div className="flex items-center justify-between">
                                                            <div>
                                                                <div className="flex items-center space-x-2 mb-1">
                                                                    <span className="font-medium text-slate-900">
                                                                        {certType.current.certificate_number}
                                                                    </span>
                                                                    {getStatusBadge(certType.current.status)}
                                                                </div>
                                                                <p className="text-sm text-slate-600">
                                                                    {new Date(certType.current.issue_date).toLocaleDateString()} -
                                                                    {new Date(certType.current.expiry_date).toLocaleDateString()}
                                                                </p>
                                                                <p className="text-xs text-slate-500">
                                                                    Issuer: {certType.current.issuer}
                                                                </p>
                                                            </div>
                                                            <div className="flex items-center space-x-2">
                                                                {certType.current.certificate_files && certType.current.certificate_files.length > 0 && (
                                                                    <span className="text-xs text-slate-500">
                                                                        {certType.current.certificate_files.length} file(s)
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                )}

                                                {/* Certificate History */}
                                                {certType.history && certType.history.length > 0 && (
                                                    <div>
                                                        <h4 className="text-sm font-medium text-slate-700 mb-2 border-t pt-3">
                                                            History ({certType.history.length})
                                                        </h4>
                                                        <div className="space-y-2">
                                                            {certType.history.slice(0, 3).map((cert) => (
                                                                <div key={cert.id} className="bg-slate-50 border border-slate-200 rounded p-2">
                                                                    <div className="flex items-center justify-between">
                                                                        <div>
                                                                            <div className="flex items-center space-x-2">
                                                                                <span className="text-sm font-medium text-slate-700">
                                                                                    {cert.certificate_number}
                                                                                </span>
                                                                                {getStatusBadge(cert.status)}
                                                                            </div>
                                                                            <p className="text-xs text-slate-500 mt-1">
                                                                                {new Date(cert.issue_date).toLocaleDateString()} -
                                                                                {new Date(cert.expiry_date).toLocaleDateString()}
                                                                            </p>
                                                                        </div>
                                                                        {cert.certificate_files && cert.certificate_files.length > 0 && (
                                                                            <span className="text-xs text-slate-500">
                                                                                {cert.certificate_files.length} file(s)
                                                                            </span>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            ))}
                                                            {certType.history.length > 3 && (
                                                                <p className="text-xs text-slate-500 text-center py-2">
                                                                    ... and {certType.history.length - 3} more
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <DocumentIcon className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                                        <p className="text-slate-500 text-sm">No certificates available</p>
                                        <p className="text-slate-400 text-xs mt-1">
                                            Add the first certificate to get started
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Add Certificate Modal */}
                    {showAddCertificate && (
                        <AddCertificateModal
                            employee={employee}
                            certificateTypes={certificateTypes}
                            onClose={() => setShowAddCertificate(false)}
                        />
                    )}

                    {/* Add Background Check Modal */}
                    {showAddBackgroundCheck && (
                        <AddBackgroundCheckModal
                            employee={employee}
                            onClose={() => setShowAddBackgroundCheck(false)}
                        />
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Simple Add Certificate Modal Component
function AddCertificateModal({ employee, certificateTypes, onClose }) {
    const { data, setData, post, processing, errors } = useForm({
        certificate_type_id: '',
        certificate_number: '',
        issuer: '',
        issue_date: '',
        expiry_date: '',
        files: []
    });

    const submit = (e) => {
        e.preventDefault();

        const formData = new FormData();
        Object.keys(data).forEach(key => {
            if (key === 'files') {
                Array.from(data.files).forEach(file => {
                    formData.append('files[]', file);
                });
            } else {
                formData.append(key, data[key]);
            }
        });

        post(route('employee-containers.certificates.store', employee.id), {
            data: formData,
            onSuccess: () => onClose()
        });
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-md w-full p-6">
                <h3 className="text-lg font-medium text-slate-900 mb-4">
                    Add Certificate for {employee.name}
                </h3>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">
                            Certificate Type *
                        </label>
                        <select
                            className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            value={data.certificate_type_id}
                            onChange={(e) => setData('certificate_type_id', e.target.value)}
                            required
                        >
                            <option value="">Select certificate type</option>
                            {certificateTypes.map((type) => (
                                <option key={type.id} value={type.id}>
                                    {type.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">
                            Certificate Number *
                        </label>
                        <input
                            type="text"
                            className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            value={data.certificate_number}
                            onChange={(e) => setData('certificate_number', e.target.value)}
                            required
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">
                            Issuer *
                        </label>
                        <input
                            type="text"
                            className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            value={data.issuer}
                            onChange={(e) => setData('issuer', e.target.value)}
                            required
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">
                                Issue Date *
                            </label>
                            <input
                                type="date"
                                className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                value={data.issue_date}
                                onChange={(e) => setData('issue_date', e.target.value)}
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">
                                Expiry Date
                            </label>
                            <input
                                type="date"
                                className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                value={data.expiry_date}
                                onChange={(e) => setData('expiry_date', e.target.value)}
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">
                            Certificate Files (PDF/JPG)
                        </label>
                        <input
                            type="file"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png"
                            className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            onChange={(e) => setData('files', e.target.files)}
                        />
                    </div>

                    <div className="flex justify-end space-x-3 pt-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50"
                        >
                            {processing ? 'Saving...' : 'Save Certificate'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

// Simple Add Background Check Modal Component
function AddBackgroundCheckModal({ employee, onClose }) {
    const { data, setData, post, processing } = useForm({
        files: [],
        status: 'cleared',
        notes: ''
    });

    const submit = (e) => {
        e.preventDefault();

        const formData = new FormData();
        Array.from(data.files).forEach(file => {
            formData.append('files[]', file);
        });
        formData.append('status', data.status);
        formData.append('notes', data.notes);

        post(route('employee-containers.background-check.upload', employee.id), {
            data: formData,
            onSuccess: () => onClose()
        });
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-md w-full p-6">
                <h3 className="text-lg font-medium text-slate-900 mb-4">
                    Upload Background Check for {employee.name}
                </h3>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">
                            Files (PDF/JPG) *
                        </label>
                        <input
                            type="file"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png"
                            className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onChange={(e) => setData('files', e.target.files)}
                            required
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">
                            Status
                        </label>
                        <select
                            className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                        >
                            <option value="cleared">Cleared</option>
                            <option value="in_progress">In Progress</option>
                            <option value="pending_review">Pending Review</option>
                            <option value="requires_follow_up">Requires Follow Up</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">
                            Notes
                        </label>
                        <textarea
                            className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            rows="3"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            placeholder="Additional notes about background check..."
                        />
                    </div>

                    <div className="flex justify-end space-x-3 pt-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                        >
                            {processing ? 'Uploading...' : 'Upload Files'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
