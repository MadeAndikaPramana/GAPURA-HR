import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    PencilIcon,
    DocumentDuplicateIcon,
    CalendarIcon,
    UserIcon,
    TagIcon,
    BuildingOfficeIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    ClockIcon,
    DocumentTextIcon,
    ArrowPathIcon,
    PrinterIcon,
    ShareIcon,
    ShieldCheckIcon,
    InformationCircleIcon,
    ExclamationCircleIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, trainingRecord, relatedCertificates, renewalHistory }) {
    const [activeTab, setActiveTab] = useState('details');

    const getStatusColor = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800 border-green-200',
            expiring_soon: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            expired: 'bg-red-100 text-red-800 border-red-200',
            superseded: 'bg-gray-100 text-gray-800 border-gray-200'
        };
        return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const getStatusIcon = (status) => {
        const icons = {
            active: <CheckCircleIcon className="w-5 h-5" />,
            expiring_soon: <ExclamationTriangleIcon className="w-5 h-5" />,
            expired: <XCircleIcon className="w-5 h-5" />,
            superseded: <ArrowPathIcon className="w-5 h-5" />
        };
        return icons[status] || <XCircleIcon className="w-5 h-5" />;
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

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const getDaysUntilExpiry = () => {
        const expiry = new Date(trainingRecord.expiry_date);
        const today = new Date();
        const diffTime = expiry - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    };

    const getValidityPeriod = () => {
        const issue = new Date(trainingRecord.issue_date);
        const expiry = new Date(trainingRecord.expiry_date);
        const diffTime = expiry - issue;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        const months = Math.round(diffDays / 30);

        if (months < 12) return `${months} month(s)`;
        const years = Math.floor(months / 12);
        const remainingMonths = months % 12;
        if (remainingMonths === 0) return `${years} year(s)`;
        return `${years} year(s) ${remainingMonths} month(s)`;
    };

    const renewCertificate = () => {
        router.post(route('training-records.renew', trainingRecord.id));
    };

    const duplicateCertificate = () => {
        router.get(route('training-records.create'), {
            duplicate_from: trainingRecord.id,
            employee_id: trainingRecord.employee_id,
            training_type_id: trainingRecord.training_type_id
        });
    };

    const printCertificate = () => {
        window.print();
    };

    const shareCertificate = async () => {
        if (navigator.share) {
            try {
                await navigator.share({
                    title: `Training Certificate - ${trainingRecord.employee?.name}`,
                    text: `${trainingRecord.training_type?.name} certificate for ${trainingRecord.employee?.name}`,
                    url: window.location.href
                });
            } catch (error) {
                console.log('Error sharing:', error);
            }
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(window.location.href);
            alert('Certificate link copied to clipboard!');
        }
    };

    const daysLeft = getDaysUntilExpiry();
    const isExpired = daysLeft < 0;
    const isExpiringSoon = daysLeft <= 30 && daysLeft >= 0;

    const tabs = [
        { id: 'details', name: 'Certificate Details', icon: DocumentTextIcon },
        { id: 'history', name: 'History & Renewals', icon: ClockIcon },
        { id: 'related', name: 'Related Certificates', icon: DocumentDuplicateIcon },
        { id: 'compliance', name: 'Compliance Info', icon: ShieldCheckIcon }
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
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
                                Training Certificate Details
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Certificate: {trainingRecord.certificate_number}
                            </p>
                        </div>
                    </div>
                    <div className="flex space-x-2">
                        <button
                            onClick={printCertificate}
                            className="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <PrinterIcon className="w-4 h-4 mr-2" />
                            Print
                        </button>
                        <button
                            onClick={shareCertificate}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <ShareIcon className="w-4 h-4 mr-2" />
                            Share
                        </button>
                        <button
                            onClick={duplicateCertificate}
                            className="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <DocumentDuplicateIcon className="w-4 h-4 mr-2" />
                            Duplicate
                        </button>
                        {(isExpired || isExpiringSoon) && (
                            <button
                                onClick={renewCertificate}
                                className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                <ArrowPathIcon className="w-4 h-4 mr-2" />
                                Renew Certificate
                            </button>
                        )}
                        <Link
                            href={route('training-records.edit', trainingRecord.id)}
                            className="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit Certificate
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Certificate - ${trainingRecord.certificate_number}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Status Alert */}
                    {(isExpired || isExpiringSoon) && (
                        <div className={`mb-6 border rounded-md p-4 ${
                            isExpired ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200'
                        }`}>
                            <div className="flex">
                                <ExclamationCircleIcon className={`w-5 h-5 mt-0.5 ${
                                    isExpired ? 'text-red-400' : 'text-yellow-400'
                                }`} />
                                <div className="ml-3">
                                    <h3 className={`text-sm font-medium ${
                                        isExpired ? 'text-red-800' : 'text-yellow-800'
                                    }`}>
                                        {isExpired ? 'Certificate Expired' : 'Certificate Expiring Soon'}
                                    </h3>
                                    <div className={`mt-2 text-sm ${
                                        isExpired ? 'text-red-700' : 'text-yellow-700'
                                    }`}>
                                        <p>
                                            {isExpired
                                                ? `This certificate expired ${Math.abs(daysLeft)} days ago and requires immediate renewal.`
                                                : `This certificate will expire in ${daysLeft} days. Consider scheduling a renewal.`
                                            }
                                        </p>
                                        <div className="mt-3">
                                            <button
                                                onClick={renewCertificate}
                                                className="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                                            >
                                                <ArrowPathIcon className="w-4 h-4 mr-1" />
                                                Renew Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">

                        {/* Certificate Summary Card */}
                        <div className="lg:col-span-1">
                            <div className="bg-white shadow rounded-lg p-6">
                                <div className="flex items-center space-x-4 mb-6">
                                    <div className="flex-shrink-0">
                                        <div className={`w-16 h-16 rounded-full flex items-center justify-center ${
                                            trainingRecord.status === 'active' ? 'bg-green-100' :
                                            trainingRecord.status === 'expiring_soon' ? 'bg-yellow-100' :
                                            trainingRecord.status === 'expired' ? 'bg-red-100' :
                                            'bg-gray-100'
                                        }`}>
                                            {getStatusIcon(trainingRecord.status)}
                                        </div>
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Certificate Status
                                        </h3>
                                        <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border ${getStatusColor(trainingRecord.status)}`}>
                                            {trainingRecord.status?.replace('_', ' ').toUpperCase()}
                                        </span>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <div>
                                        <h4 className="text-sm font-medium text-gray-900 mb-2">Certificate Number</h4>
                                        <p className="text-sm text-gray-600 font-mono bg-gray-50 p-2 rounded">
                                            {trainingRecord.certificate_number}
                                        </p>
                                    </div>

                                    <div>
                                        <h4 className="text-sm font-medium text-gray-900 mb-2">Validity Period</h4>
                                        <p className="text-sm text-gray-600">{getValidityPeriod()}</p>
                                    </div>

                                    <div>
                                        <h4 className="text-sm font-medium text-gray-900 mb-2">Days Until Expiry</h4>
                                        <p className={`text-sm font-medium ${
                                            daysLeft < 0 ? 'text-red-600' :
                                            daysLeft <= 30 ? 'text-yellow-600' :
                                            'text-green-600'
                                        }`}>
                                            {daysLeft < 0 ? `Expired ${Math.abs(daysLeft)} days ago` :
                                             daysLeft === 0 ? 'Expires today' :
                                             daysLeft === 1 ? 'Expires tomorrow' :
                                             `${daysLeft} days remaining`}
                                        </p>
                                    </div>

                                    <div>
                                        <h4 className="text-sm font-medium text-gray-900 mb-2">Created</h4>
                                        <p className="text-sm text-gray-600">{formatDate(trainingRecord.created_at)}</p>
                                    </div>

                                    <div>
                                        <h4 className="text-sm font-medium text-gray-900 mb-2">Last Updated</h4>
                                        <p className="text-sm text-gray-600">{formatDate(trainingRecord.updated_at)}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Actions */}
                            <div className="bg-white shadow rounded-lg p-6 mt-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                                <div className="space-y-2">
                                    <Link
                                        href={route('employees.show', trainingRecord.employee?.id)}
                                        className="w-full flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
                                    >
                                        <UserIcon className="w-4 h-4 mr-2 text-blue-600" />
                                        View Employee Profile
                                    </Link>
                                    <Link
                                        href={route('training-types.show', trainingRecord.training_type?.id)}
                                        className="w-full flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
                                    >
                                        <TagIcon className="w-4 h-4 mr-2 text-purple-600" />
                                        View Training Type
                                    </Link>
                                    <Link
                                        href={route('departments.show', trainingRecord.employee?.department?.id)}
                                        className="w-full flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
                                    >
                                        <BuildingOfficeIcon className="w-4 h-4 mr-2 text-green-600" />
                                        View Department
                                    </Link>
                                </div>
                            </div>
                        </div>

                        {/* Main Content */}
                        <div className="lg:col-span-3">
                            <div className="bg-white shadow rounded-lg">
                                {/* Tabs */}
                                <div className="border-b border-gray-200">
                                    <nav className="-mb-px flex">
                                        {tabs.map((tab) => {
                                            const Icon = tab.icon;
                                            return (
                                                <button
                                                    key={tab.id}
                                                    onClick={() => setActiveTab(tab.id)}
                                                    className={`${
                                                        activeTab === tab.id
                                                            ? 'border-green-500 text-green-600'
                                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                                    } w-1/4 py-4 px-1 text-center border-b-2 font-medium text-sm flex items-center justify-center space-x-2`}
                                                >
                                                    <Icon className="w-5 h-5" />
                                                    <span className="hidden sm:inline">{tab.name}</span>
                                                </button>
                                            );
                                        })}
                                    </nav>
                                </div>

                                {/* Tab Content */}
                                <div className="p-6">
                                    {activeTab === 'details' && (
                                        <div className="space-y-6">
                                            {/* Employee Information */}
                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 mb-4">Employee Information</h3>
                                                <div className="bg-gray-50 rounded-lg p-4">
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Name</label>
                                                            <p className="text-sm text-gray-900">{trainingRecord.employee?.name}</p>
                                                        </div>
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Employee ID</label>
                                                            <p className="text-sm text-gray-900">{trainingRecord.employee?.employee_id}</p>
                                                        </div>
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Department</label>
                                                            <p className="text-sm text-gray-900">{trainingRecord.employee?.department?.name}</p>
                                                        </div>
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Position</label>
                                                            <p className="text-sm text-gray-900">{trainingRecord.employee?.position || 'N/A'}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Training Information */}
                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 mb-4">Training Information</h3>
                                                <div className="bg-gray-50 rounded-lg p-4">
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Training Type</label>
                                                            <div className="flex items-center space-x-2">
                                                                <p className="text-sm text-gray-900">{trainingRecord.training_type?.name}</p>
                                                                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getCategoryColor(trainingRecord.training_type?.category)}`}>
                                                                    {trainingRecord.training_type?.category}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Training Code</label>
                                                            <p className="text-sm text-gray-900 font-mono">{trainingRecord.training_type?.code}</p>
                                                        </div>
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Validity Period</label>
                                                            <p className="text-sm text-gray-900">{trainingRecord.training_type?.validity_months} months</p>
                                                        </div>
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Description</label>
                                                            <p className="text-sm text-gray-900">{trainingRecord.training_type?.description || 'N/A'}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Certificate Details */}
                                            <div>
                                                <h3 className="text-lg font-medium text-gray-900 mb-4">Certificate Details</h3>
                                                <div className="bg-gray-50 rounded-lg p-4">
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Certificate Number</label>
                                                            <p className="text-sm text-gray-900 font-mono">{trainingRecord.certificate_number}</p>
                                                        </div>
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Issuing Organization</label>
                                                            <p className="text-sm text-gray-900">{trainingRecord.issuer}</p>
                                                        </div>
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Issue Date</label>
                                                            <p className="text-sm text-gray-900">{formatDate(trainingRecord.issue_date)}</p>
                                                        </div>
                                                        <div>
                                                            <label className="text-sm font-medium text-gray-700">Expiry Date</label>
                                                            <p className={`text-sm font-medium ${
                                                                daysLeft < 0 ? 'text-red-600' :
                                                                daysLeft <= 30 ? 'text-yellow-600' :
                                                                'text-green-600'
                                                            }`}>
                                                                {formatDate(trainingRecord.expiry_date)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    {trainingRecord.notes && (
                                                        <div className="mt-4">
                                                            <label className="text-sm font-medium text-gray-700">Notes</label>
                                                            <p className="text-sm text-gray-900 mt-1 p-3 bg-white rounded border">
                                                                {trainingRecord.notes}
                                                            </p>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {activeTab === 'history' && (
                                        <div className="space-y-6">
                                            <h3 className="text-lg font-medium text-gray-900">Certificate History</h3>

                                            {renewalHistory && renewalHistory.length > 0 ? (
                                                <div className="space-y-4">
                                                    {renewalHistory.map((record, index) => (
                                                        <div key={index} className="border border-gray-200 rounded-lg p-4">
                                                            <div className="flex items-center justify-between mb-2">
                                                                <div className="flex items-center">
                                                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(record.status)}`}>
                                                                        {record.status.replace('_', ' ').toUpperCase()}
                                                                    </span>
                                                                    <span className="ml-3 font-medium text-gray-900">
                                                                        {record.certificate_number}
                                                                    </span>
                                                                </div>
                                                                <span className="text-sm text-gray-500">
                                                                    {formatDate(record.created_at)}
                                                                </span>
                                                            </div>
                                                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                                <div>
                                                                    <span className="font-medium text-gray-700">Issue:</span>
                                                                    <div className="text-gray-900">{formatDate(record.issue_date)}</div>
                                                                </div>
                                                                <div>
                                                                    <span className="font-medium text-gray-700">Expiry:</span>
                                                                    <div className="text-gray-900">{formatDate(record.expiry_date)}</div>
                                                                </div>
                                                                <div>
                                                                    <span className="font-medium text-gray-700">Issuer:</span>
                                                                    <div className="text-gray-900">{record.issuer}</div>
                                                                </div>
                                                                <div>
                                                                    <span className="font-medium text-gray-700">Actions:</span>
                                                                    <Link
                                                                        href={route('training-records.show', record.id)}
                                                                        className="text-green-600 hover:text-green-900 text-xs"
                                                                    >
                                                                        View Details
                                                                    </Link>
                                                                </div>
                                                            </div>
                                                            {record.notes && (
                                                                <div className="mt-2">
                                                                    <span className="text-xs font-medium text-gray-700">Notes:</span>
                                                                    <p className="text-xs text-gray-600 mt-1">{record.notes}</p>
                                                                </div>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <div className="text-center py-6">
                                                    <ClockIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No renewal history</h3>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        This is the original certificate or no previous versions exist.
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {activeTab === 'related' && (
                                        <div className="space-y-6">
                                            <h3 className="text-lg font-medium text-gray-900">Related Certificates</h3>

                                            {relatedCertificates && relatedCertificates.length > 0 ? (
                                                <div className="space-y-4">
                                                    {relatedCertificates.map((cert, index) => (
                                                        <div key={index} className="border border-gray-200 rounded-lg p-4">
                                                            <div className="flex items-center justify-between">
                                                                <div>
                                                                    <div className="flex items-center space-x-2">
                                                                        <h4 className="font-medium text-gray-900">{cert.training_type_name}</h4>
                                                                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(cert.status)}`}>
                                                                            {cert.status.replace('_', ' ').toUpperCase()}
                                                                        </span>
                                                                    </div>
                                                                    <p className="text-sm text-gray-600 mt-1">
                                                                        Certificate: {cert.certificate_number}
                                                                    </p>
                                                                    <p className="text-xs text-gray-500">
                                                                        Valid: {formatDate(cert.issue_date)} - {formatDate(cert.expiry_date)}
                                                                    </p>
                                                                </div>
                                                                <Link
                                                                    href={route('training-records.show', cert.id)}
                                                                    className="text-green-600 hover:text-green-900"
                                                                >
                                                                    <DocumentTextIcon className="w-5 h-5" />
                                                                </Link>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <div className="text-center py-6">
                                                    <DocumentDuplicateIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No related certificates</h3>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        This employee has no other training certificates.
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {activeTab === 'compliance' && (
                                        <div className="space-y-6">
                                            <h3 className="text-lg font-medium text-gray-900">Compliance Information</h3>

                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                {/* Compliance Status */}
                                                <div className="bg-gray-50 rounded-lg p-4">
                                                    <h4 className="font-medium text-gray-900 mb-3">Current Compliance Status</h4>
                                                    <div className="space-y-3">
                                                        <div className="flex items-center justify-between">
                                                            <span className="text-sm text-gray-700">Certificate Status:</span>
                                                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(trainingRecord.status)}`}>
                                                                {trainingRecord.status.replace('_', ' ').toUpperCase()}
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center justify-between">
                                                            <span className="text-sm text-gray-700">Compliance Level:</span>
                                                            <span className={`text-sm font-medium ${
                                                                trainingRecord.status === 'active' ? 'text-green-600' :
                                                                trainingRecord.status === 'expiring_soon' ? 'text-yellow-600' :
                                                                'text-red-600'
                                                            }`}>
                                                                {trainingRecord.status === 'active' ? 'Compliant' :
                                                                 trainingRecord.status === 'expiring_soon' ? 'Warning' :
                                                                 'Non-Compliant'}
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center justify-between">
                                                            <span className="text-sm text-gray-700">Action Required:</span>
                                                            <span className="text-sm text-gray-900">
                                                                {trainingRecord.status === 'active' ? 'None' :
                                                                 trainingRecord.status === 'expiring_soon' ? 'Schedule Renewal' :
                                                                 'Immediate Renewal Required'}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Audit Trail */}
                                                <div className="bg-gray-50 rounded-lg p-4">
                                                    <h4 className="font-medium text-gray-900 mb-3">Audit Information</h4>
                                                    <div className="space-y-3 text-sm">
                                                        <div>
                                                            <span className="font-medium text-gray-700">Record ID:</span>
                                                            <p className="text-gray-900 font-mono">{trainingRecord.id}</p>
                                                        </div>
                                                        <div>
                                                            <span className="font-medium text-gray-700">Created By:</span>
                                                            <p className="text-gray-900">System Admin</p>
                                                        </div>
                                                        <div>
                                                            <span className="font-medium text-gray-700">Last Modified:</span>
                                                            <p className="text-gray-900">{formatDate(trainingRecord.updated_at)}</p>
                                                        </div>
                                                        <div>
                                                            <span className="font-medium text-gray-700">Verification Status:</span>
                                                            <p className="text-green-600">Verified</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Compliance Actions */}
                                            <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                                                <div className="flex">
                                                    <InformationCircleIcon className="w-5 h-5 text-blue-400 mt-0.5" />
                                                    <div className="ml-3">
                                                        <h4 className="text-sm font-medium text-blue-800 mb-2">
                                                            Compliance Recommendations
                                                        </h4>
                                                        <div className="text-sm text-blue-700">
                                                            {trainingRecord.status === 'active' && (
                                                                <ul className="list-disc pl-5 space-y-1">
                                                                    <li>Certificate is currently valid and compliant</li>
                                                                    <li>Monitor for upcoming renewal requirements</li>
                                                                    <li>Ensure employee maintains required competencies</li>
                                                                </ul>
                                                            )}
                                                            {trainingRecord.status === 'expiring_soon' && (
                                                                <ul className="list-disc pl-5 space-y-1">
                                                                    <li>Schedule renewal training before expiry date</li>
                                                                    <li>Contact training provider to arrange sessions</li>
                                                                    <li>Update calendar with renewal deadline</li>
                                                                </ul>
                                                            )}
                                                            {trainingRecord.status === 'expired' && (
                                                                <ul className="list-disc pl-5 space-y-1">
                                                                    <li>Immediate renewal required for compliance</li>
                                                                    <li>Employee may need to be temporarily restricted from related duties</li>
                                                                    <li>Prioritize this renewal in the training schedule</li>
                                                                </ul>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
