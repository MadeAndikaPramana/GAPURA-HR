import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    PrinterIcon,
    DocumentArrowDownIcon,
    ClipboardDocumentCheckIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ClockIcon,
    ShieldCheckIcon,
    BuildingOfficeIcon,
    UserIcon,
    CalendarIcon,
    AcademicCapIcon,
    StarIcon
} from '@heroicons/react/24/outline';

export default function CertificateShow({ auth, certificate, relatedCertificates }) {
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const { delete: destroy, processing: deleting } = useForm();

    const handleDelete = () => {
        destroy(route('certificates.destroy', certificate.id), {
            onSuccess: () => router.visit(route('certificates.index')),
        });
    };

    const getStatusBadge = (cert) => {
        const { status, expiry_date, days_until_expiry } = cert;

        if (status === 'expired' || (expiry_date && days_until_expiry < 0)) {
            return (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                    <ExclamationTriangleIcon className="w-4 h-4 mr-1" />
                    Expired
                </span>
            );
        }

        if (status === 'issued' && days_until_expiry <= 30 && days_until_expiry >= 0) {
            return (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                    <ClockIcon className="w-4 h-4 mr-1" />
                    Expiring Soon
                </span>
            );
        }

        if (status === 'issued') {
            return (
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <CheckCircleIcon className="w-4 h-4 mr-1" />
                    Active
                </span>
            );
        }

        const statusConfig = {
            draft: { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Draft', icon: ClockIcon },
            revoked: { bg: 'bg-red-100', text: 'text-red-800', label: 'Revoked', icon: ExclamationTriangleIcon },
            renewed: { bg: 'bg-blue-100', text: 'text-blue-800', label: 'Renewed', icon: CheckCircleIcon }
        };

        const config = statusConfig[status] || { bg: 'bg-gray-100', text: 'text-gray-800', label: status, icon: ClockIcon };
        const Icon = config.icon;

        return (
            <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${config.bg} ${config.text}`}>
                <Icon className="w-4 h-4 mr-1" />
                {config.label}
            </span>
        );
    };

    const getVerificationBadge = (verificationStatus) => {
        const config = {
            verified: { bg: 'bg-green-100', text: 'text-green-800', label: 'Verified', icon: CheckCircleIcon },
            pending: { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Pending', icon: ClockIcon },
            invalid: { bg: 'bg-red-100', text: 'text-red-800', label: 'Invalid', icon: ExclamationTriangleIcon },
            under_review: { bg: 'bg-blue-100', text: 'text-blue-800', label: 'Under Review', icon: ClockIcon }
        };

        const statusConfig = config[verificationStatus] || config.pending;
        const Icon = statusConfig.icon;

        return (
            <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusConfig.bg} ${statusConfig.text}`}>
                <Icon className="w-4 h-4 mr-1" />
                {statusConfig.label}
            </span>
        );
    };

    const formatDate = (date) => {
        return new Date(date).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    const formatDateTime = (datetime) => {
        return new Date(datetime).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('certificates.index')}
                            className="flex items-center text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeftIcon className="w-5 h-5 mr-2" />
                            Back to Certificates
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Certificate Details
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                {certificate.certificate_number}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <button className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <PrinterIcon className="w-4 h-4 mr-2" />
                            Print
                        </button>
                        <button className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <DocumentArrowDownIcon className="w-4 h-4 mr-2" />
                            Download PDF
                        </button>
                        <Link
                            href={route('certificates.edit', certificate.id)}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit
                        </Link>
                        <button
                            onClick={() => setShowDeleteModal(true)}
                            className="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50"
                        >
                            <TrashIcon className="w-4 h-4 mr-2" />
                            Delete
                        </button>
                    </div>
                </div>
            }
        >
            <Head title={`Certificate - ${certificate.certificate_number}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Main Certificate Info */}
                    <div className="bg-white shadow-sm rounded-lg border overflow-hidden mb-6">
                        <div className="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-3">
                                    <div className="flex-shrink-0">
                                        <ShieldCheckIcon className="h-10 w-10 text-white" />
                                    </div>
                                    <div>
                                        <h1 className="text-2xl font-bold text-white">
                                            {certificate.certificate_number}
                                        </h1>
                                        <p className="text-green-100 text-sm">
                                            Certificate of {certificate.certificate_type}
                                        </p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    {getStatusBadge(certificate)}
                                </div>
                            </div>
                        </div>

                        <div className="px-6 py-4">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">

                                {/* Employee Information */}
                                <div className="space-y-4">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                        <UserIcon className="w-5 h-5 mr-2" />
                                        Employee Information
                                    </h3>
                                    <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Name</dt>
                                            <dd className="text-sm text-gray-900">{certificate.employee.name}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Employee ID</dt>
                                            <dd className="text-sm text-gray-900">{certificate.employee.nip}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Department</dt>
                                            <dd className="text-sm text-gray-900">{certificate.employee.department.name}</dd>
                                        </div>
                                        {certificate.employee.position && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Position</dt>
                                                <dd className="text-sm text-gray-900">{certificate.employee.position}</dd>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Training Information */}
                                <div className="space-y-4">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                        <AcademicCapIcon className="w-5 h-5 mr-2" />
                                        Training Information
                                    </h3>
                                    <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Training Type</dt>
                                            <dd className="text-sm text-gray-900">{certificate.training_type.name}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Category</dt>
                                            <dd className="text-sm text-gray-900">{certificate.training_type.category}</dd>
                                        </div>
                                        {certificate.training_provider && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Provider</dt>
                                                <dd className="text-sm text-gray-900">{certificate.training_provider.name}</dd>
                                            </div>
                                        )}
                                        {certificate.training_record && (
                                            <>
                                                {certificate.training_record.training_date && (
                                                    <div>
                                                        <dt className="text-sm font-medium text-gray-500">Training Date</dt>
                                                        <dd className="text-sm text-gray-900">
                                                            {formatDate(certificate.training_record.training_date)}
                                                        </dd>
                                                    </div>
                                                )}
                                                {certificate.training_record.training_hours && (
                                                    <div>
                                                        <dt className="text-sm font-medium text-gray-500">Training Hours</dt>
                                                        <dd className="text-sm text-gray-900">
                                                            {certificate.training_record.training_hours} hours
                                                        </dd>
                                                    </div>
                                                )}
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        {/* Certificate Details */}
                        <div className="lg:col-span-2">
                            <div className="bg-white shadow-sm rounded-lg border">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                        <ClipboardDocumentCheckIcon className="w-5 h-5 mr-2" />
                                        Certificate Details
                                    </h3>
                                </div>
                                <div className="px-6 py-4">
                                    <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Issue Date</dt>
                                            <dd className="text-sm text-gray-900 flex items-center">
                                                <CalendarIcon className="w-4 h-4 mr-1" />
                                                {formatDate(certificate.issue_date)}
                                            </dd>
                                        </div>

                                        {certificate.expiry_date && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Expiry Date</dt>
                                                <dd className="text-sm text-gray-900 flex items-center">
                                                    <CalendarIcon className="w-4 h-4 mr-1" />
                                                    {formatDate(certificate.expiry_date)}
                                                </dd>
                                            </div>
                                        )}

                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Issuer</dt>
                                            <dd className="text-sm text-gray-900">{certificate.issuer_name}</dd>
                                        </div>

                                        {certificate.issuer_title && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Issuer Title</dt>
                                                <dd className="text-sm text-gray-900">{certificate.issuer_title}</dd>
                                            </div>
                                        )}

                                        {certificate.issuer_organization && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Organization</dt>
                                                <dd className="text-sm text-gray-900 flex items-center">
                                                    <BuildingOfficeIcon className="w-4 h-4 mr-1" />
                                                    {certificate.issuer_organization}
                                                </dd>
                                            </div>
                                        )}

                                        {certificate.score && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Score</dt>
                                                <dd className="text-sm text-gray-900 flex items-center">
                                                    <StarIcon className="w-4 h-4 mr-1" />
                                                    {certificate.score}%
                                                    {certificate.passing_score && (
                                                        <span className="text-gray-500 ml-1">
                                                            (Passing: {certificate.passing_score}%)
                                                        </span>
                                                    )}
                                                </dd>
                                            </div>
                                        )}

                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Verification Status</dt>
                                            <dd className="text-sm text-gray-900">
                                                {getVerificationBadge(certificate.verification_status)}
                                            </dd>
                                        </div>

                                        {certificate.verification_code && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Verification Code</dt>
                                                <dd className="text-sm text-gray-900 font-mono">
                                                    {certificate.verification_code}
                                                </dd>
                                            </div>
                                        )}

                                        {certificate.print_count > 0 && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Print Status</dt>
                                                <dd className="text-sm text-gray-900">
                                                    Printed {certificate.print_count} time{certificate.print_count !== 1 ? 's' : ''}
                                                    {certificate.printed_at && (
                                                        <span className="text-gray-500 block">
                                                            Last printed: {formatDateTime(certificate.printed_at)}
                                                        </span>
                                                    )}
                                                </dd>
                                            </div>
                                        )}

                                        {certificate.renewal_count > 0 && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Renewal Count</dt>
                                                <dd className="text-sm text-gray-900">
                                                    {certificate.renewal_count} renewal{certificate.renewal_count !== 1 ? 's' : ''}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>

                                    {certificate.achievements && (
                                        <div className="mt-6">
                                            <dt className="text-sm font-medium text-gray-500 mb-2">Achievements</dt>
                                            <dd className="text-sm text-gray-900 bg-gray-50 rounded-lg p-3">
                                                {certificate.achievements}
                                            </dd>
                                        </div>
                                    )}

                                    {certificate.remarks && (
                                        <div className="mt-4">
                                            <dt className="text-sm font-medium text-gray-500 mb-2">Remarks</dt>
                                            <dd className="text-sm text-gray-900 bg-gray-50 rounded-lg p-3">
                                                {certificate.remarks}
                                            </dd>
                                        </div>
                                    )}

                                    {certificate.notes && (
                                        <div className="mt-4">
                                            <dt className="text-sm font-medium text-gray-500 mb-2">Notes</dt>
                                            <dd className="text-sm text-gray-900 bg-gray-50 rounded-lg p-3">
                                                {certificate.notes}
                                            </dd>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Sidebar - Related Info */}
                        <div className="space-y-6">

                            {/* Compliance Status */}
                            <div className="bg-white shadow-sm rounded-lg border">
                                <div className="px-4 py-3 border-b border-gray-200">
                                    <h3 className="text-sm font-medium text-gray-900">Compliance Status</h3>
                                </div>
                                <div className="px-4 py-3">
                                    <div className="space-y-3">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-500">Required</span>
                                            <span className={`text-sm font-medium ${certificate.is_compliance_required ? 'text-red-600' : 'text-gray-900'}`}>
                                                {certificate.is_compliance_required ? 'Yes' : 'No'}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-500">Status</span>
                                            <span className={`text-sm font-medium capitalize ${
                                                certificate.compliance_status === 'compliant' ? 'text-green-600' :
                                                certificate.compliance_status === 'non_compliant' ? 'text-red-600' :
                                                'text-yellow-600'
                                            }`}>
                                                {certificate.compliance_status.replace('_', ' ')}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-gray-500">Renewable</span>
                                            <span className="text-sm font-medium">
                                                {certificate.is_renewable ? 'Yes' : 'No'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Related Certificates */}
                            {relatedCertificates.length > 0 && (
                                <div className="bg-white shadow-sm rounded-lg border">
                                    <div className="px-4 py-3 border-b border-gray-200">
                                        <h3 className="text-sm font-medium text-gray-900">Related Certificates</h3>
                                        <p className="text-xs text-gray-500 mt-1">
                                            Other certificates for same employee & training type
                                        </p>
                                    </div>
                                    <div className="px-4 py-3">
                                        <div className="space-y-3">
                                            {relatedCertificates.map((cert) => (
                                                <div key={cert.id} className="flex items-center justify-between">
                                                    <div>
                                                        <Link
                                                            href={route('certificates.show', cert.id)}
                                                            className="text-sm font-medium text-green-600 hover:text-green-900"
                                                        >
                                                            {cert.certificate_number}
                                                        </Link>
                                                        <div className="text-xs text-gray-500">
                                                            {formatDate(cert.issue_date)}
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        {getStatusBadge(cert)}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Audit Trail */}
                            <div className="bg-white shadow-sm rounded-lg border">
                                <div className="px-4 py-3 border-b border-gray-200">
                                    <h3 className="text-sm font-medium text-gray-900">Audit Trail</h3>
                                </div>
                                <div className="px-4 py-3">
                                    <div className="space-y-3 text-xs text-gray-500">
                                        <div>
                                            <span className="font-medium">Created:</span>
                                            <div>{formatDateTime(certificate.created_at)}</div>
                                            {certificate.created_by && (
                                                <div>by {certificate.created_by.name}</div>
                                            )}
                                        </div>

                                        {certificate.updated_at !== certificate.created_at && (
                                            <div>
                                                <span className="font-medium">Last Updated:</span>
                                                <div>{formatDateTime(certificate.updated_at)}</div>
                                                {certificate.updated_by && (
                                                    <div>by {certificate.updated_by.name}</div>
                                                )}
                                            </div>
                                        )}

                                        {certificate.issued_at && (
                                            <div>
                                                <span className="font-medium">Issued:</span>
                                                <div>{formatDateTime(certificate.issued_at)}</div>
                                            </div>
                                        )}

                                        {certificate.last_verified_at && (
                                            <div>
                                                <span className="font-medium">Last Verified:</span>
                                                <div>{formatDateTime(certificate.last_verified_at)}</div>
                                                {certificate.verified_by && (
                                                    <div>by {certificate.verified_by}</div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Delete Confirmation Modal */}
            {showDeleteModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div className="mt-3 text-center">
                            <ExclamationTriangleIcon className="mx-auto h-12 w-12 text-red-600" />
                            <h3 className="text-lg font-medium text-gray-900 mt-2">Delete Certificate</h3>
                            <div className="mt-2 px-7 py-3">
                                <p className="text-sm text-gray-500">
                                    Are you sure you want to delete this certificate? This action cannot be undone.
                                </p>
                                <p className="text-sm font-medium text-gray-900 mt-2">
                                    Certificate: {certificate.certificate_number}
                                </p>
                            </div>
                            <div className="flex items-center justify-center gap-3 mt-4">
                                <button
                                    onClick={() => setShowDeleteModal(false)}
                                    className="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300"
                                    disabled={deleting}
                                >
                                    Cancel
                                </button>
                                <button
                                    onClick={handleDelete}
                                    className="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300"
                                    disabled={deleting}
                                >
                                    {deleting ? 'Deleting...' : 'Delete'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
