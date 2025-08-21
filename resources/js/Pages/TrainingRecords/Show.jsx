// resources/js/Pages/TrainingRecords/Show.jsx

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    ArrowLeftIcon,
    ClipboardDocumentListIcon,
    UserIcon,
    AcademicCapIcon,
    CalendarDaysIcon,
    DocumentIcon,
    BuildingOfficeIcon,
    PencilIcon,
    TrashIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    PrinterIcon,
    ShareIcon,
    EyeIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, trainingRecord, relatedRecords }) {
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    const formatDateTime = (dateString) => {
        return new Date(dateString).toLocaleString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getDaysUntilExpiry = (expiryDate) => {
        const today = new Date();
        const expiry = new Date(expiryDate);
        const diffTime = expiry - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays < 0) {
            return {
                text: `Expired ${Math.abs(diffDays)} days ago`,
                color: 'text-red-600',
                bgColor: 'bg-red-50',
                borderColor: 'border-red-200'
            };
        } else if (diffDays === 0) {
            return {
                text: 'Expires today',
                color: 'text-red-600',
                bgColor: 'bg-red-50',
                borderColor: 'border-red-200'
            };
        } else if (diffDays <= 30) {
            return {
                text: `${diffDays} days remaining`,
                color: 'text-yellow-600',
                bgColor: 'bg-yellow-50',
                borderColor: 'border-yellow-200'
            };
        } else {
            return {
                text: `${diffDays} days remaining`,
                color: 'text-green-600',
                bgColor: 'bg-green-50',
                borderColor: 'border-green-200'
            };
        }
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            active: {
                color: 'bg-green-100 text-green-800',
                icon: CheckCircleIcon,
                text: 'Active'
            },
            expiring_soon: {
                color: 'bg-yellow-100 text-yellow-800',
                icon: ExclamationTriangleIcon,
                text: 'Expiring Soon'
            },
            expired: {
                color: 'bg-red-100 text-red-800',
                icon: XCircleIcon,
                text: 'Expired'
            }
        };

        const config = statusConfig[status] || statusConfig.active;
        const IconComponent = config.icon;

        return (
            <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${config.color}`}>
                <IconComponent className="w-4 h-4 mr-2" />
                {config.text}
            </span>
        );
    };

    const handleDelete = () => {
        router.delete(route('training-records.destroy', trainingRecord.id), {
            onSuccess: () => {
                // Redirect handled by controller
            }
        });
    };

    const printCertificate = () => {
        window.print();
    };

    const expiryInfo = getDaysUntilExpiry(trainingRecord.expiry_date);

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
                                Training Record Details
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                {trainingRecord.training_type.name} • {trainingRecord.certificate_number}
                            </p>
                        </div>
                    </div>
                    <div className="flex space-x-2">
                        <button
                            onClick={printCertificate}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <PrinterIcon className="w-4 h-4 mr-2" />
                            Print
                        </button>
                        <Link
                            href={route('training-records.edit', trainingRecord.id)}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit
                        </Link>
                        <button
                            onClick={() => setShowDeleteModal(true)}
                            className="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            <TrashIcon className="w-4 h-4 mr-2" />
                            Delete
                        </button>
                    </div>
                </div>
            }
        >
            <Head title={`Training Record - ${trainingRecord.certificate_number}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Certificate Status Alert */}
                            <div className={`rounded-md p-4 ${expiryInfo.bgColor} ${expiryInfo.borderColor} border`}>
                                <div className="flex">
                                    <div className="flex-shrink-0">
                                        {trainingRecord.status === 'expired' ? (
                                            <XCircleIcon className={`h-5 w-5 ${expiryInfo.color}`} />
                                        ) : trainingRecord.status === 'expiring_soon' ? (
                                            <ExclamationTriangleIcon className={`h-5 w-5 ${expiryInfo.color}`} />
                                        ) : (
                                            <CheckCircleIcon className={`h-5 w-5 ${expiryInfo.color}`} />
                                        )}
                                    </div>
                                    <div className="ml-3">
                                        <h3 className={`text-sm font-medium ${expiryInfo.color}`}>
                                            Certificate Status: {trainingRecord.status.replace('_', ' ').toUpperCase()}
                                        </h3>
                                        <div className={`mt-2 text-sm ${expiryInfo.color}`}>
                                            <p>{expiryInfo.text} (expires {formatDate(trainingRecord.expiry_date)})</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Training Record Details */}
                            <div className="bg-white shadow rounded-lg">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                        <ClipboardDocumentListIcon className="w-5 h-5 mr-2" />
                                        Training Record Information
                                    </h3>
                                </div>
                                <div className="px-6 py-4 space-y-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <h4 className="text-sm font-medium text-gray-700 mb-4 flex items-center">
                                                <UserIcon className="w-4 h-4 mr-2" />
                                                Employee Information
                                            </h4>
                                            <div className="space-y-3">
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Employee Name</label>
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

                                        <div>
                                            <h4 className="text-sm font-medium text-gray-700 mb-4 flex items-center">
                                                <AcademicCapIcon className="w-4 h-4 mr-2" />
                                                Training Information
                                            </h4>
                                            <div className="space-y-3">
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Training Type</label>
                                                    <div className="flex items-center space-x-2">
                                                        <p className="text-sm text-gray-900">{trainingRecord.training_type?.name}</p>
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                            {trainingRecord.training_type?.code}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Category</label>
                                                    <p className="text-sm text-gray-900">{trainingRecord.training_type?.category}</p>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Validity Period</label>
                                                    <p className="text-sm text-gray-900">
                                                        {trainingRecord.training_type?.validity_months} months
                                                    </p>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Training Provider</label>
                                                    <p className="text-sm text-gray-900">{trainingRecord.issuer}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="border-t border-gray-200 pt-6">
                                        <h4 className="text-sm font-medium text-gray-700 mb-4 flex items-center">
                                            <DocumentIcon className="w-4 h-4 mr-2" />
                                            Certificate Details
                                        </h4>
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                            <div>
                                                <label className="text-sm font-medium text-gray-700">Certificate Number</label>
                                                <p className="text-sm text-gray-900 font-mono bg-gray-50 px-2 py-1 rounded">
                                                    {trainingRecord.certificate_number}
                                                </p>
                                            </div>
                                            <div>
                                                <label className="text-sm font-medium text-gray-700">Issue Date</label>
                                                <p className="text-sm text-gray-900">{formatDate(trainingRecord.issue_date)}</p>
                                            </div>
                                            <div>
                                                <label className="text-sm font-medium text-gray-700">Expiry Date</label>
                                                <p className="text-sm text-gray-900">{formatDate(trainingRecord.expiry_date)}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {trainingRecord.notes && (
                                        <div className="border-t border-gray-200 pt-6">
                                            <h4 className="text-sm font-medium text-gray-700 mb-2">Additional Notes</h4>
                                            <p className="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                                                {trainingRecord.notes}
                                            </p>
                                        </div>
                                    )}

                                    <div className="border-t border-gray-200 pt-6">
                                        <h4 className="text-sm font-medium text-gray-700 mb-2">Record Metadata</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                                            <div>
                                                <span className="font-medium">Created:</span> {formatDateTime(trainingRecord.created_at)}
                                            </div>
                                            <div>
                                                <span className="font-medium">Last Updated:</span> {formatDateTime(trainingRecord.updated_at)}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Quick Actions */}
                            <div className="bg-white shadow rounded-lg">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900">Quick Actions</h3>
                                </div>
                                <div className="px-6 py-4 space-y-3">
                                    <Link
                                        href={route('employees.show', trainingRecord.employee.id)}
                                        className="w-full flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        <EyeIcon className="w-4 h-4 mr-2" />
                                        View Employee Profile
                                    </Link>
                                    <Link
                                        href={route('training-records.create', { employee_id: trainingRecord.employee.id })}
                                        className="w-full flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        <ClipboardDocumentListIcon className="w-4 h-4 mr-2" />
                                        Add Another Training
                                    </Link>
                                </div>
                            </div>

                            {/* Status Summary */}
                            <div className="bg-white shadow rounded-lg">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900">Status Summary</h3>
                                </div>
                                <div className="px-6 py-4 space-y-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-600">Current Status</span>
                                        {getStatusBadge(trainingRecord.status)}
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-600">Days Until Expiry</span>
                                        <span className={`text-sm font-medium ${expiryInfo.color}`}>
                                            {expiryInfo.text.split(' ')[0]} days
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-600">Certificate Age</span>
                                        <span className="text-sm text-gray-900">
                                            {Math.floor((new Date() - new Date(trainingRecord.issue_date)) / (1000 * 60 * 60 * 24))} days
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Related Training Records */}
                            {relatedRecords && relatedRecords.length > 0 && (
                                <div className="bg-white shadow rounded-lg">
                                    <div className="px-6 py-4 border-b border-gray-200">
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Other Trainings ({relatedRecords.length})
                                        </h3>
                                        <p className="text-sm text-gray-600">
                                            Other training records for {trainingRecord.employee.name}
                                        </p>
                                    </div>
                                    <div className="px-6 py-4">
                                        <div className="space-y-3">
                                            {relatedRecords.slice(0, 5).map((record) => (
                                                <Link
                                                    key={record.id}
                                                    href={route('training-records.show', record.id)}
                                                    className="block p-3 bg-gray-50 rounded-md hover:bg-gray-100 transition-colors"
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <p className="text-sm font-medium text-gray-900">
                                                                {record.training_type.name}
                                                            </p>
                                                            <p className="text-xs text-gray-500">
                                                                {record.certificate_number}
                                                            </p>
                                                        </div>
                                                        {getStatusBadge(record.status)}
                                                    </div>
                                                </Link>
                                            ))}
                                        </div>
                                        {relatedRecords.length > 5 && (
                                            <div className="mt-3 text-center">
                                                <Link
                                                    href={route('training-records.index', { employee_id: trainingRecord.employee.id })}
                                                    className="text-sm text-green-600 hover:text-green-900"
                                                >
                                                    View all {relatedRecords.length} records
                                                </Link>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Delete Confirmation Modal */}
            {showDeleteModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div className="mt-3 text-center">
                            <TrashIcon className="mx-auto h-16 w-16 text-red-500" />
                            <h3 className="text-lg font-medium text-gray-900 mt-4">Delete Training Record</h3>
                            <div className="mt-2 px-7 py-3">
                                <p className="text-sm text-gray-500">
                                    Are you sure you want to delete this training record? This action cannot be undone.
                                </p>
                                <div className="bg-gray-50 border border-gray-200 rounded-md p-3 mt-3">
                                    <p className="text-sm text-gray-700">
                                        <strong>Employee:</strong> {trainingRecord.employee.name}<br />
                                        <strong>Training:</strong> {trainingRecord.training_type.name}<br />
                                        <strong>Certificate:</strong> {trainingRecord.certificate_number}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center justify-center px-4 py-3 space-x-2">
                                <button
                                    onClick={() => setShowDeleteModal(false)}
                                    className="px-4 py-2 bg-white text-gray-500 border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    Cancel
                                </button>
                                <button
                                    onClick={handleDelete}
                                    className="px-4 py-2 bg-red-600 text-white border border-transparent rounded-md text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                >
                                    Delete Record
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
