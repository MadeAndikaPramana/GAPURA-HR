import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    DocumentTextIcon,
    CalendarIcon,
    UserIcon,
    BuildingOfficeIcon,
    ShieldCheckIcon,
    QrCodeIcon,
    ArrowDownTrayIcon,
    ShareIcon,
    PrinterIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    PencilIcon,
    ArrowLeftIcon,
    CurrencyDollarIcon,
    AcademicCapIcon,
    MapPinIcon,
    UserGroupIcon
} from '@heroicons/react/24/outline';

export default function CertificateShow({
    certificate,
    verificationUrl,
    downloadUrl,
    renewalRecommendationDate
}) {
    const [showQrCode, setShowQrCode] = useState(false);
    const [shareMenuOpen, setShareMenuOpen] = useState(false);

    // Status configuration
    const getStatusConfig = (status) => {
        switch (status) {
            case 'active':
                return {
                    color: 'text-green-700 bg-green-50 ring-green-600/20',
                    icon: <CheckCircleIcon className="h-5 w-5" />,
                    text: 'Active'
                };
            case 'expired':
                return {
                    color: 'text-red-700 bg-red-50 ring-red-600/20',
                    icon: <XCircleIcon className="h-5 w-5" />,
                    text: 'Expired'
                };
            case 'expiring_soon':
                return {
                    color: 'text-yellow-700 bg-yellow-50 ring-yellow-600/20',
                    icon: <ExclamationTriangleIcon className="h-5 w-5" />,
                    text: 'Expiring Soon'
                };
            case 'expiring':
                return {
                    color: 'text-orange-700 bg-orange-50 ring-orange-600/20',
                    icon: <ClockIcon className="h-5 w-5" />,
                    text: 'Expiring'
                };
            default:
                return {
                    color: 'text-gray-700 bg-gray-50 ring-gray-600/20',
                    icon: <DocumentTextIcon className="h-5 w-5" />,
                    text: 'Unknown'
                };
        }
    };

    const statusConfig = getStatusConfig(certificate.status);

    const handleShare = async () => {
        if (navigator.share) {
            try {
                await navigator.share({
                    title: `Certificate - ${certificate.training_record.training_type.name}`,
                    text: `${certificate.training_record.employee.name}'s certificate for ${certificate.training_record.training_type.name}`,
                    url: verificationUrl
                });
            } catch (err) {
                console.log('Error sharing:', err);
            }
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(verificationUrl);
            alert('Verification URL copied to clipboard!');
        }
    };

    const handlePrint = () => {
        window.print();
    };

    const handleDownload = () => {
        if (downloadUrl) {
            window.open(downloadUrl, '_blank');
        }
    };

    const getRenewalUrgency = () => {
        if (!certificate.expiry_date) return null;

        const daysUntilExpiry = certificate.days_until_expiry;
        if (daysUntilExpiry < 0) {
            return { level: 'critical', message: 'Certificate has expired - immediate action required' };
        } else if (daysUntilExpiry <= 7) {
            return { level: 'urgent', message: 'Certificate expires very soon - schedule renewal immediately' };
        } else if (daysUntilExpiry <= 30) {
            return { level: 'high', message: 'Certificate expires soon - plan renewal training' };
        } else if (daysUntilExpiry <= 90) {
            return { level: 'medium', message: 'Consider scheduling renewal training' };
        }
        return null;
    };

    const renewalUrgency = getRenewalUrgency();

    return (
        <AuthenticatedLayout>
            <Head title={`Certificate - ${certificate.certificate_number}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <Link
                                    href={route('certificates.index')}
                                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                                >
                                    <ArrowLeftIcon className="h-4 w-4 mr-1" />
                                    Back to Certificates
                                </Link>
                            </div>
                            <div className="flex items-center space-x-3">
                                {downloadUrl && (
                                    <button
                                        onClick={handleDownload}
                                        className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        <ArrowDownTrayIcon className="h-4 w-4 mr-2" />
                                        Download
                                    </button>
                                )}
                                <button
                                    onClick={handleShare}
                                    className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <ShareIcon className="h-4 w-4 mr-2" />
                                    Share
                                </button>
                                <button
                                    onClick={handlePrint}
                                    className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <PrinterIcon className="h-4 w-4 mr-2" />
                                    Print
                                </button>
                                <Link
                                    href={route('certificates.edit', certificate.id)}
                                    className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <PencilIcon className="h-4 w-4 mr-2" />
                                    Edit
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Renewal Alert */}
                    {renewalUrgency && (
                        <div className={`rounded-md p-4 mb-6 ${
                            renewalUrgency.level === 'critical' ? 'bg-red-50 border border-red-200' :
                            renewalUrgency.level === 'urgent' ? 'bg-orange-50 border border-orange-200' :
                            renewalUrgency.level === 'high' ? 'bg-yellow-50 border border-yellow-200' :
                            'bg-blue-50 border border-blue-200'
                        }`}>
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <ExclamationTriangleIcon className={`h-5 w-5 ${
                                        renewalUrgency.level === 'critical' ? 'text-red-400' :
                                        renewalUrgency.level === 'urgent' ? 'text-orange-400' :
                                        renewalUrgency.level === 'high' ? 'text-yellow-400' :
                                        'text-blue-400'
                                    }`} />
                                </div>
                                <div className="ml-3">
                                    <h3 className={`text-sm font-medium ${
                                        renewalUrgency.level === 'critical' ? 'text-red-800' :
                                        renewalUrgency.level === 'urgent' ? 'text-orange-800' :
                                        renewalUrgency.level === 'high' ? 'text-yellow-800' :
                                        'text-blue-800'
                                    }`}>
                                        Renewal Required
                                    </h3>
                                    <div className={`mt-1 text-sm ${
                                        renewalUrgency.level === 'critical' ? 'text-red-700' :
                                        renewalUrgency.level === 'urgent' ? 'text-orange-700' :
                                        renewalUrgency.level === 'high' ? 'text-yellow-700' :
                                        'text-blue-700'
                                    }`}>
                                        <p>{renewalUrgency.message}</p>
                                        {certificate.days_until_expiry !== null && (
                                            <p className="mt-1">
                                                {certificate.days_until_expiry < 0
                                                    ? `Expired ${Math.abs(certificate.days_until_expiry)} days ago`
                                                    : `${certificate.days_until_expiry} days remaining`
                                                }
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Certificate Information */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Certificate Overview */}
                            <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center">
                                            <DocumentTextIcon className="h-8 w-8 text-green-600 mr-3" />
                                            <div>
                                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                                    Certificate Details
                                                </h3>
                                                <p className="mt-1 max-w-2xl text-sm text-gray-500">
                                                    {certificate.certificate_number}
                                                </p>
                                            </div>
                                        </div>
                                        <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ring-1 ring-inset ${statusConfig.color}`}>
                                            {statusConfig.icon}
                                            <span className="ml-2">{statusConfig.text}</span>
                                        </span>
                                    </div>
                                </div>

                                <div className="px-4 py-5 sm:p-6">
                                    <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Certificate Number</dt>
                                            <dd className="mt-1 text-sm text-gray-900 font-mono">
                                                {certificate.certificate_number || 'N/A'}
                                            </dd>
                                        </div>

                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Issued By</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {certificate.issued_by}
                                            </dd>
                                        </div>

                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 flex items-center">
                                                <CalendarIcon className="h-4 w-4 mr-1" />
                                                Issue Date
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {new Date(certificate.issue_date).toLocaleDateString('en-GB', {
                                                    day: 'numeric',
                                                    month: 'long',
                                                    year: 'numeric'
                                                })}
                                            </dd>
                                        </div>

                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 flex items-center">
                                                <CalendarIcon className="h-4 w-4 mr-1" />
                                                Expiry Date
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {certificate.expiry_date ?
                                                    new Date(certificate.expiry_date).toLocaleDateString('en-GB', {
                                                        day: 'numeric',
                                                        month: 'long',
                                                        year: 'numeric'
                                                    }) :
                                                    'No expiry'
                                                }
                                            </dd>
                                        </div>

                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 flex items-center">
                                                <ShieldCheckIcon className="h-4 w-4 mr-1" />
                                                Verification Status
                                            </dt>
                                            <dd className="mt-1">
                                                {certificate.is_verified ? (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-green-700 bg-green-50 ring-1 ring-inset ring-green-600/20">
                                                        <CheckCircleIcon className="h-3 w-3 mr-1" />
                                                        Verified
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-gray-700 bg-gray-50 ring-1 ring-inset ring-gray-600/20">
                                                        <XCircleIcon className="h-3 w-3 mr-1" />
                                                        Unverified
                                                    </span>
                                                )}
                                            </dd>
                                        </div>

                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Verification Code</dt>
                                            <dd className="mt-1 text-sm text-gray-900 font-mono">
                                                {certificate.verification_code || 'N/A'}
                                            </dd>
                                        </div>

                                        {certificate.notes && (
                                            <div className="sm:col-span-2">
                                                <dt className="text-sm font-medium text-gray-500">Notes</dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    {certificate.notes}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>
                                </div>
                            </div>

                            {/* Employee Information */}
                            <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
                                    <div className="flex items-center">
                                        <UserIcon className="h-6 w-6 text-blue-600 mr-3" />
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                                            Employee Information
                                        </h3>
                                    </div>
                                </div>

                                <div className="px-4 py-5 sm:p-6">
                                    <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Employee Name</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                <Link
                                                    href={route('employees.show', certificate.training_record.employee.id)}
                                                    className="text-green-600 hover:text-green-900"
                                                >
                                                    {certificate.training_record.employee.name}
                                                </Link>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Employee ID</dt>
                                            <dd className="mt-1 text-sm text-gray-900 font-mono">
                                                {certificate.training_record.employee.employee_id}
                                            </dd>
                                        </div>

                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 flex items-center">
                                                <BuildingOfficeIcon className="h-4 w-4 mr-1" />
                                                Department
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                <Link
                                                    href={route('departments.show', certificate.training_record.employee.department.id)}
                                                    className="text-green-600 hover:text-green-900"
                                                >
                                                    {certificate.training_record.employee.department.name}
                                                </Link>
                                            </dd>
                                        </div>

                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Position</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {certificate.training_record.employee.position || 'N/A'}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            {/* Training Information */}
                            <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
                                    <div className="flex items-center">
                                        <AcademicCapIcon className="h-6 w-6 text-purple-600 mr-3" />
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                                            Training Information
                                        </h3>
                                    </div>
                                </div>

                                <div className="px-4 py-5 sm:p-6">
                                    <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Training Type</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                <Link
                                                    href={route('training-types.show', certificate.training_record.training_type.id)}
                                                    className="text-green-600 hover:text-green-900"
                                                >
                                                    {certificate.training_record.training_type.name}
                                                </Link>
                                            </dd>
                                        </div>

                                        {certificate.training_record.training_type.category && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Category</dt>
                                                <dd className="mt-1">
                                                    <span
                                                        className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                        style={{
                                                            backgroundColor: certificate.training_record.training_type.category.color_code + '20',
                                                            color: certificate.training_record.training_type.category.color_code
                                                        }}
                                                    >
                                                        {certificate.training_record.training_type.category.name}
                                                    </span>
                                                </dd>
                                            </div>
                                        )}

                                        {certificate.training_record.training_provider && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Training Provider</dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    <Link
                                                        href={route('training-providers.show', certificate.training_record.training_provider.id)}
                                                        className="text-green-600 hover:text-green-900"
                                                    >
                                                        {certificate.training_record.training_provider.name}
                                                    </Link>
                                                </dd>
                                            </div>
                                        )}

                                        {certificate.training_record.completion_date && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Completion Date</dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    {new Date(certificate.training_record.completion_date).toLocaleDateString('en-GB', {
                                                        day: 'numeric',
                                                        month: 'long',
                                                        year: 'numeric'
                                                    })}
                                                </dd>
                                            </div>
                                        )}

                                        {certificate.training_record.score && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Training Score</dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    <span className={`font-semibold ${
                                                        certificate.training_record.score >= 90 ? 'text-green-600' :
                                                        certificate.training_record.score >= 80 ? 'text-blue-600' :
                                                        certificate.training_record.score >= 70 ? 'text-yellow-600' :
                                                        'text-red-600'
                                                    }`}>
                                                        {certificate.training_record.score}%
                                                    </span>
                                                </dd>
                                            </div>
                                        )}

                                        {certificate.training_record.cost && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500 flex items-center">
                                                    <CurrencyDollarIcon className="h-4 w-4 mr-1" />
                                                    Training Cost
                                                </dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    Rp {certificate.training_record.cost.toLocaleString('id-ID')}
                                                </dd>
                                            </div>
                                        )}

                                        {certificate.training_record.location && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500 flex items-center">
                                                    <MapPinIcon className="h-4 w-4 mr-1" />
                                                    Location
                                                </dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    {certificate.training_record.location}
                                                </dd>
                                            </div>
                                        )}

                                        {certificate.training_record.instructor_name && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500 flex items-center">
                                                    <UserGroupIcon className="h-4 w-4 mr-1" />
                                                    Instructor
                                                </dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    {certificate.training_record.instructor_name}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>
                                </div>
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Quick Actions */}
                            <div className="bg-white shadow rounded-lg">
                                <div className="px-4 py-5 sm:p-6">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                        Quick Actions
                                    </h3>
                                    <div className="space-y-3">
                                        <button
                                            onClick={() => setShowQrCode(!showQrCode)}
                                            className="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            <QrCodeIcon className="h-4 w-4 mr-2" />
                                            {showQrCode ? 'Hide' : 'Show'} QR Code
                                        </button>

                                        <a
                                            href={verificationUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            <ShieldCheckIcon className="h-4 w-4 mr-2" />
                                            Verify Certificate
                                        </a>

                                        <Link
                                            href={route('training-records.show', certificate.training_record.id)}
                                            className="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            <DocumentTextIcon className="h-4 w-4 mr-2" />
                                            View Training Record
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            {/* QR Code Display */}
                            {showQrCode && certificate.qr_code_path && (
                                <div className="bg-white shadow rounded-lg">
                                    <div className="px-4 py-5 sm:p-6 text-center">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                            Verification QR Code
                                        </h3>
                                        <div className="flex justify-center mb-4">
                                            <img
                                                src={`/storage/${certificate.qr_code_path}`}
                                                alt="Certificate QR Code"
                                                className="w-32 h-32 border border-gray-200 rounded"
                                            />
                                        </div>
                                        <p className="text-sm text-gray-500">
                                            Scan this QR code to verify the certificate
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Verification Information */}
                            <div className="bg-white shadow rounded-lg">
                                <div className="px-4 py-5 sm:p-6">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                        Verification Details
                                    </h3>
                                    <dl className="space-y-3">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Verification URL</dt>
                                            <dd className="mt-1 text-xs text-gray-900 break-all font-mono">
                                                {verificationUrl}
                                            </dd>
                                        </div>

                                        {certificate.verified_by && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Verified By</dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    {certificate.verified_by.name}
                                                </dd>
                                            </div>
                                        )}

                                        {certificate.verification_date && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Verification Date</dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    {new Date(certificate.verification_date).toLocaleDateString('en-GB', {
                                                        day: 'numeric',
                                                        month: 'long',
                                                        year: 'numeric',
                                                        hour: '2-digit',
                                                        minute: '2-digit'
                                                    })}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>
                                </div>
                            </div>

                            {/* Renewal Information */}
                            {renewalRecommendationDate && (
                                <div className="bg-white shadow rounded-lg">
                                    <div className="px-4 py-5 sm:p-6">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                            Renewal Information
                                        </h3>
                                        <dl className="space-y-3">
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Recommended Renewal Date</dt>
                                                <dd className="mt-1 text-sm text-gray-900">
                                                    {new Date(renewalRecommendationDate).toLocaleDateString('en-GB', {
                                                        day: 'numeric',
                                                        month: 'long',
                                                        year: 'numeric'
                                                    })}
                                                </dd>
                                            </div>
                                        </dl>
                                        <div className="mt-4">
                                            <Link
                                                href={route('training-records.create-renewal', certificate.training_record.id)}
                                                className="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                method="post"
                                            >
                                                Schedule Renewal
                                            </Link>
                                        </div>
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
