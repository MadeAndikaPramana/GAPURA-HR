// resources/js/Pages/Employees/Container.jsx
// Employee Container View - Digital Folder Interface

import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon, UserIcon, IdentificationIcon,
    PlusIcon, DocumentIcon, EyeIcon, DownloadIcon,
    ExclamationTriangleIcon, CheckCircleIcon,
    ClockIcon, XCircleIcon, FolderIcon
} from '@heroicons/react/24/outline';

export default function Container({ auth, employee, containerData }) {
    const [activeTab, setActiveTab] = useState('certificates');
    const [showAddCertificate, setShowAddCertificate] = useState(false);

    const { profile, background_check, certificates, statistics } = containerData;

    // Status badge helpers
    const getStatusBadge = (status) => {
        const configs = {
            active: { bg: 'bg-green-100', text: 'text-green-800', icon: CheckCircleIcon, label: 'ACTIVE' },
            expired: { bg: 'bg-red-100', text: 'text-red-800', icon: XCircleIcon, label: 'EXPIRED' },
            expiring_soon: { bg: 'bg-yellow-100', text: 'text-yellow-800', icon: ClockIcon, label: 'EXPIRING SOON' },
            pending: { bg: 'bg-blue-100', text: 'text-blue-800', icon: ClockIcon, label: 'PENDING' },
            completed: { bg: 'bg-green-100', text: 'text-green-800', icon: CheckCircleIcon, label: 'COMPLETED' }
        };

        const config = configs[status] || configs.active;
        const Icon = config.icon;

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.bg} ${config.text}`}>
                <Icon className="w-3 h-3 mr-1" />
                {config.label}
            </span>
        );
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Employee Container - ${employee.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <Link
                                    href={route('employees.index')}
                                    className="btn-secondary"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                    Back to Employees
                                </Link>
                                <div>
                                    <h1 className="text-3xl font-bold text-slate-900 flex items-center">
                                        <div className="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                                            <span className="text-white font-bold text-lg">
                                                {employee.name.charAt(0).toUpperCase()}
                                            </span>
                                        </div>
                                        {employee.name}
                                    </h1>
                                    <p className="text-lg text-slate-600 mt-1">
                                        {profile.position} • {profile.department}
                                    </p>
                                </div>
                            </div>

                            {/* Container Statistics */}
                            <div className="bg-white rounded-lg border border-slate-200 p-4">
                                <div className="grid grid-cols-4 gap-4 text-center">
                                    <div>
                                        <div className="text-2xl font-bold text-green-600">{statistics.active}</div>
                                        <div className="text-xs text-slate-500">Active</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-yellow-600">{statistics.expiring_soon}</div>
                                        <div className="text-xs text-slate-500">Expiring</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-red-600">{statistics.expired}</div>
                                        <div className="text-xs text-slate-500">Expired</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-blue-600">{statistics.compliance_rate}%</div>
                                        <div className="text-xs text-slate-500">Compliance</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        {/* Sidebar - Employee Profile */}
                        <div className="lg:col-span-1">
                            <div className="card mb-6">
                                <div className="card-header">
                                    <h3 className="text-lg font-medium text-slate-900 flex items-center">
                                        <UserIcon className="w-5 h-5 mr-2 text-green-600" />
                                        Profile Information
                                    </h3>
                                </div>
                                <div className="card-body space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-slate-500">NIP</dt>
                                        <dd className="text-sm font-mono bg-green-100 text-green-800 px-2 py-1 rounded mt-1">
                                            {profile.employee_id}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-slate-500">Department</dt>
                                        <dd className="text-sm text-slate-900 mt-1">{profile.department}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-slate-500">Position</dt>
                                        <dd className="text-sm text-slate-900 mt-1">{profile.position}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-slate-500">Hire Date</dt>
                                        <dd className="text-sm text-slate-900 mt-1">{profile.hire_date}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-slate-500">Email</dt>
                                        <dd className="text-sm text-slate-900 mt-1">{profile.email}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-slate-500">Phone</dt>
                                        <dd className="text-sm text-slate-900 mt-1">{profile.phone}</dd>
                                    </div>
                                </div>
                            </div>

                            {/* Background Check Section */}
                            <div className="card">
                                <div className="card-header">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-lg font-medium text-slate-900 flex items-center">
                                            <IdentificationIcon className="w-5 h-5 mr-2 text-blue-600" />
                                            Background Check
                                        </h3>
                                        <button className="btn-sm btn-primary">
                                            <PlusIcon className="w-4 h-4 mr-1" />
                                            Upload
                                        </button>
                                    </div>
                                </div>
                                <div className="card-body">
                                    <div className="space-y-3">
                                        <div>
                                            <dt className="text-sm font-medium text-slate-500">Status</dt>
                                            <dd className="mt-1">
                                                {getStatusBadge(background_check.status || 'not_started')}
                                            </dd>
                                        </div>
                                        {background_check.date && (
                                            <div>
                                                <dt className="text-sm font-medium text-slate-500">Date</dt>
                                                <dd className="text-sm text-slate-900 mt-1">{background_check.date}</dd>
                                            </div>
                                        )}
                                        {background_check.files_count > 0 && (
                                            <div>
                                                <dt className="text-sm font-medium text-slate-500">Files</dt>
                                                <dd className="text-sm text-slate-900 mt-1">
                                                    {background_check.files_count} document(s)
                                                    <div className="mt-2 space-y-1">
                                                        {background_check.files.map((file, index) => (
                                                            <div key={index} className="flex items-center justify-between text-xs bg-slate-50 p-2 rounded">
                                                                <span className="flex items-center">
                                                                    <DocumentIcon className="w-4 h-4 mr-1 text-slate-500" />
                                                                    {file.original_name}
                                                                </span>
                                                                <button className="text-blue-600 hover:text-blue-800">
                                                                    <DownloadIcon className="w-4 h-4" />
                                                                </button>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </dd>
                                            </div>
                                        )}
                                        {background_check.notes && (
                                            <div>
                                                <dt className="text-sm font-medium text-slate-500">Notes</dt>
                                                <dd className="text-sm text-slate-700 mt-1">{background_check.notes}</dd>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Main Content - Certificates */}
                        <div className="lg:col-span-3">
                            <div className="card">
                                <div className="card-header">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-lg font-medium text-slate-900 flex items-center">
                                            <FolderIcon className="w-5 h-5 mr-2 text-purple-600" />
                                            Certificates ({statistics.total})
                                        </h3>
                                        <button
                                            onClick={() => setShowAddCertificate(true)}
                                            className="btn-primary"
                                        >
                                            <PlusIcon className="w-4 h-4 mr-2" />
                                            Add Certificate
                                        </button>
                                    </div>
                                </div>

                                <div className="card-body">
                                    {certificates.length > 0 ? (
                                        <div className="space-y-6">
                                            {certificates.map((certType, index) => (
                                                <div key={certType.type_id} className="border border-slate-200 rounded-lg p-4">
                                                    {/* Certificate Type Header */}
                                                    <div className="flex items-center justify-between mb-4">
                                                        <div>
                                                            <h4 className="text-lg font-medium text-slate-900">
                                                                {certType.type_name}
                                                            </h4>
                                                            <p className="text-sm text-slate-500">
                                                                {certType.total_count} certificate(s) •
                                                                <span className="ml-1">
                                                                    {certType.status_summary.active} active,
                                                                    {certType.status_summary.expired} expired
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>

                                                    {/* Current Certificate */}
                                                    {certType.current && (
                                                        <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-3">
                                                            <div className="flex items-center justify-between">
                                                                <div>
                                                                    <div className="flex items-center space-x-3">
                                                                        <span className="font-medium text-slate-900">
                                                                            {certType.current.certificate_number}
                                                                        </span>
                                                                        {getStatusBadge(certType.current.status)}
                                                                    </div>
                                                                    <p className="text-sm text-slate-600 mt-1">
                                                                        Issued: {new Date(certType.current.issue_date).toLocaleDateString()} •
                                                                        Expires: {new Date(certType.current.expiry_date).toLocaleDateString()}
                                                                    </p>
                                                                    <p className="text-sm text-slate-500">
                                                                        Issuer: {certType.current.issuer}
                                                                    </p>
                                                                </div>
                                                                <div className="flex items-center space-x-2">
                                                                    <button className="btn-sm btn-secondary">
                                                                        <EyeIcon className="w-4 h-4 mr-1" />
                                                                        View
                                                                    </button>
                                                                    {certType.current.certificate_files && certType.current.certificate_files.length > 0 && (
                                                                        <button className="btn-sm btn-secondary">
                                                                            <DownloadIcon className="w-4 h-4 mr-1" />
                                                                            Files ({certType.current.certificate_files.length})
                                                                        </button>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    )}

                                                    {/* Certificate History */}
                                                    {certType.history && certType.history.length > 0 && (
                                                        <div className="space-y-2">
                                                            <h5 className="text-sm font-medium text-slate-700 border-t pt-3">
                                                                History ({certType.history.length})
                                                            </h5>
                                                            {certType.history.map((cert) => (
                                                                <div key={cert.id} className="bg-slate-50 border border-slate-200 rounded p-3">
                                                                    <div className="flex items-center justify-between">
                                                                        <div>
                                                                            <div className="flex items-center space-x-3">
                                                                                <span className="text-sm font-medium text-slate-700">
                                                                                    {cert.certificate_number}
                                                                                </span>
                                                                                {getStatusBadge(cert.status)}
                                                                            </div>
                                                                            <p className="text-xs text-slate-500 mt-1">
                                                                                {new Date(cert.issue_date).toLocaleDateString()} -
                                                                                {new Date(cert.expiry_date).toLocaleDateString()} •
                                                                                {cert.issuer}
                                                                            </p>
                                                                        </div>
                                                                        <div className="flex items-center space-x-1">
                                                                            <button className="btn-xs btn-secondary">
                                                                                <EyeIcon className="w-3 h-3" />
                                                                            </button>
                                                                            {cert.certificate_files && cert.certificate_files.length > 0 && (
                                                                                <button className="btn-xs btn-secondary">
                                                                                    <DownloadIcon className="w-3 h-3" />
                                                                                </button>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center py-8">
                                            <FolderIcon className="w-16 h-16 text-slate-300 mx-auto mb-4" />
                                            <h4 className="text-lg font-medium text-slate-900 mb-2">No certificates yet</h4>
                                            <p className="text-slate-500 mb-4">
                                                This employee's certificate container is empty. Add their first certificate to get started.
                                            </p>
                                            <button
                                                onClick={() => setShowAddCertificate(true)}
                                                className="btn-primary"
                                            >
                                                <PlusIcon className="w-4 h-4 mr-2" />
                                                Add First Certificate
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Add Certificate Modal */}
                    {showAddCertificate && (
                        <AddCertificateModal
                            employee={employee}
                            onClose={() => setShowAddCertificate(false)}
                        />
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Add Certificate Modal Component
function AddCertificateModal({ employee, onClose }) {
    const { data, setData, post, processing, errors } = useForm({
        certificate_type_id: '',
        certificate_number: '',
        issuer: '',
        training_provider: '',
        issue_date: '',
        expiry_date: '',
        completion_date: '',
        training_date: '',
        files: [],
        notes: ''
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

        post(route('employee-certificates.store', { employee: employee.id }), {
            data: formData,
            onSuccess: () => {
                onClose();
            }
        });
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div className="p-6">
                    <div className="flex items-center justify-between mb-6">
                        <h3 className="text-lg font-medium text-slate-900">
                            Add Certificate for {employee.name}
                        </h3>
                        <button
                            onClick={onClose}
                            className="text-slate-400 hover:text-slate-500"
                        >
                            <XCircleIcon className="w-6 h-6" />
                        </button>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        {/* Form fields would go here */}
                        <div className="grid grid-cols-2 gap-4">
                            {/* Certificate Type, Number, etc. */}
                        </div>

                        {/* File Upload Section */}
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">
                                Certificate Files (PDF/JPG)
                            </label>
                            <div className="border-2 border-dashed border-slate-300 rounded-lg p-6 text-center">
                                <DocumentIcon className="w-12 h-12 text-slate-400 mx-auto mb-4" />
                                <p className="text-slate-600">Drag & drop files here or click to browse</p>
                                <input
                                    type="file"
                                    multiple
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onChange={(e) => setData('files', e.target.files)}
                                    className="hidden"
                                />
                            </div>
                        </div>

                        <div className="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                onClick={onClose}
                                className="btn-secondary"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="btn-primary"
                            >
                                {processing ? 'Saving...' : 'Save Certificate'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
