// resources/js/Pages/Employees/Container.jsx
// Employee Container View - Digital Folder Interface (FIXED)

import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon,
    UserIcon,
    IdentificationIcon,
    PlusIcon,
    DocumentIcon,
    EyeIcon,
    ArrowDownTrayIcon, // ✅ FIXED: Changed from DownloadIcon
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ClockIcon,
    XCircleIcon,
    FolderIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    DocumentTextIcon
} from '@heroicons/react/24/outline';

export default function Container({ auth, employee = {}, statistics = {}, profile = {} }) {
    const [activeTab, setActiveTab] = useState('certificates');
    const [showAddCertificate, setShowAddCertificate] = useState(false);

    // Early return if employee data is not available
    if (!employee || !employee.id) {
        return (
            <AuthenticatedLayout user={auth.user}>
                <Head title="Employee Container" />
                <div className="py-6">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center py-12">
                            <ExclamationTriangleIcon className="mx-auto h-12 w-12 text-red-500" />
                            <h3 className="mt-2 text-lg font-medium text-gray-900">Employee Not Found</h3>
                            <p className="mt-1 text-sm text-gray-500">
                                The requested employee container could not be loaded.
                            </p>
                            <div className="mt-6">
                                <Link
                                    href={route('employee-containers.index')}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                    Back to Containers
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

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
            <Head title={`Employee Container - ${employee.name || 'Unknown'}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <Link
                                    href={route('employee-containers.index')}
                                    className="btn-secondary"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                    Back to Containers
                                </Link>
                                <div>
                                    <h1 className="text-3xl font-bold text-slate-900 flex items-center">
                                        <div className="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                                            <span className="text-white font-bold text-lg">
                                                {employee.name ? employee.name.charAt(0).toUpperCase() : 'U'}
                                            </span>
                                        </div>
                                        {employee.name || 'Unknown Employee'}
                                    </h1>
                                    <p className="text-lg text-slate-600 mt-1">
                                        {profile?.position || 'No Position'} • {profile?.department || 'No Department'}
                                    </p>
                                </div>
                            </div>

                            {/* Container Statistics */}
                            <div className="bg-white rounded-lg border border-slate-200 p-4">
                                <div className="grid grid-cols-4 gap-4 text-center">
                                    <div>
                                        <div className="text-2xl font-bold text-green-600">{statistics?.active || 0}</div>
                                        <div className="text-xs text-slate-500">Active</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-yellow-600">{statistics?.expiring_soon || 0}</div>
                                        <div className="text-xs text-slate-500">Expiring</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-red-600">{statistics?.expired || 0}</div>
                                        <div className="text-xs text-slate-500">Expired</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-blue-600">{statistics?.total || 0}</div>
                                        <div className="text-xs text-slate-500">Total</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Employee Profile Card */}
                    <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="flex items-center">
                                <IdentificationIcon className="w-5 h-5 text-slate-400 mr-3" />
                                <div>
                                    <p className="text-sm font-medium text-slate-700">Employee ID</p>
                                    <p className="text-slate-900">{employee.employee_id || employee.nip || 'N/A'}</p>
                                </div>
                            </div>
                            <div className="flex items-center">
                                <BuildingOfficeIcon className="w-5 h-5 text-slate-400 mr-3" />
                                <div>
                                    <p className="text-sm font-medium text-slate-700">Department</p>
                                    <p className="text-slate-900">{profile?.department || 'No Department'}</p>
                                </div>
                            </div>
                            <div className="flex items-center">
                                <CalendarIcon className="w-5 h-5 text-slate-400 mr-3" />
                                <div>
                                    <p className="text-sm font-medium text-slate-700">Hire Date</p>
                                    <p className="text-slate-900">{profile?.hire_date || 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Container Tabs */}
                    <div className="bg-white rounded-lg shadow-sm border border-slate-200">
                        {/* Tab Navigation */}
                        <div className="border-b border-slate-200">
                            <nav className="flex space-x-8 px-6" aria-label="Tabs">
                                <button
                                    onClick={() => setActiveTab('certificates')}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === 'certificates'
                                            ? 'border-green-500 text-green-600'
                                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                    }`}
                                >
                                    <DocumentTextIcon className="w-5 h-5 inline mr-2" />
                                    Certificates ({statistics?.total || 0})
                                </button>
                                <button
                                    onClick={() => setActiveTab('background-check')}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === 'background-check'
                                            ? 'border-green-500 text-green-600'
                                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                    }`}
                                >
                                    <DocumentIcon className="w-5 h-5 inline mr-2" />
                                    Background Check
                                </button>
                            </nav>
                        </div>

                        {/* Tab Content */}
                        <div className="p-6">
                            {activeTab === 'certificates' && (
                                <div>
                                    {/* Add Certificate Button */}
                                    <div className="flex justify-between items-center mb-6">
                                        <h3 className="text-lg font-semibold text-slate-900">Training Certificates</h3>
                                        <button
                                            onClick={() => setShowAddCertificate(true)}
                                            className="btn-primary"
                                        >
                                            <PlusIcon className="w-4 h-4 mr-2" />
                                            Add Certificate
                                        </button>
                                    </div>

                                    {/* Certificates List */}
                                    {employee.employee_certificates?.length > 0 ? (
                                        <div className="space-y-4">
                                            {employee.employee_certificates.map((certificate) => (
                                                <div
                                                    key={certificate.id}
                                                    className="border border-slate-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex-1">
                                                            <div className="flex items-center">
                                                                <h4 className="text-lg font-medium text-slate-900 mr-3">
                                                                    {certificate.certificate_type?.name || 'Unknown Certificate'}
                                                                </h4>
                                                                {getStatusBadge(certificate.status)}
                                                            </div>
                                                            <p className="text-sm text-slate-500 mt-1">
                                                                Certificate #: {certificate.certificate_number || 'N/A'}
                                                            </p>
                                                            <p className="text-sm text-slate-500">
                                                                Issued: {certificate.issue_date || 'N/A'}
                                                                {certificate.expiry_date && ` • Expires: ${certificate.expiry_date}`}
                                                            </p>
                                                        </div>
                                                        <div className="flex items-center space-x-2">
                                                            <button className="btn-secondary">
                                                                <EyeIcon className="w-4 h-4" />
                                                            </button>
                                                            <button className="btn-secondary">
                                                                <ArrowDownTrayIcon className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center py-12">
                                            <DocumentTextIcon className="mx-auto h-12 w-12 text-slate-400" />
                                            <h3 className="mt-2 text-sm font-medium text-slate-900">No certificates</h3>
                                            <p className="mt-1 text-sm text-slate-500">
                                                Get started by adding a training certificate.
                                            </p>
                                            <div className="mt-6">
                                                <button
                                                    onClick={() => setShowAddCertificate(true)}
                                                    className="btn-primary"
                                                >
                                                    <PlusIcon className="w-4 h-4 mr-2" />
                                                    Add First Certificate
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {activeTab === 'background-check' && (
                                <div>
                                    <div className="flex justify-between items-center mb-6">
                                        <h3 className="text-lg font-semibold text-slate-900">Background Check Documents</h3>
                                        <button className="btn-primary">
                                            <PlusIcon className="w-4 h-4 mr-2" />
                                            Upload Documents
                                        </button>
                                    </div>

                                    {/* Background Check Status */}
                                    <div className="bg-slate-50 rounded-lg p-6 text-center">
                                        {statistics?.has_background_check ? (
                                            <div>
                                                <CheckCircleIcon className="mx-auto h-12 w-12 text-green-500" />
                                                <h3 className="mt-2 text-sm font-medium text-slate-900">Background Check Complete</h3>
                                                <p className="mt-1 text-sm text-slate-500">
                                                    Background check documents are on file.
                                                </p>
                                            </div>
                                        ) : (
                                            <div>
                                                <ExclamationTriangleIcon className="mx-auto h-12 w-12 text-yellow-500" />
                                                <h3 className="mt-2 text-sm font-medium text-slate-900">No Background Check</h3>
                                                <p className="mt-1 text-sm text-slate-500">
                                                    No background check documents have been uploaded yet.
                                                </p>
                                                <div className="mt-6">
                                                    <button className="btn-primary">
                                                        <PlusIcon className="w-4 h-4 mr-2" />
                                                        Upload Background Check
                                                    </button>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
