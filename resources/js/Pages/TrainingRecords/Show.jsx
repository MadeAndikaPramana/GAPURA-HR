// resources/js/Pages/TrainingRecords/Show.jsx
// View Training Record Details

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    UserIcon,
    AcademicCapIcon,
    DocumentTextIcon,
    CalendarIcon,
    CurrencyDollarIcon,
    MapPinIcon,
    UserGroupIcon,
    ClipboardDocumentListIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowLeftIcon,
    PencilIcon,
    BuildingOfficeIcon,
    ClockIcon,
    StarIcon,
    ChatBubbleBottomCenterTextIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, trainingRecord, relatedRecords }) {
    const getStatusBadge = (status) => {
        const statusConfig = {
            'active': {
                icon: CheckCircleIcon,
                classes: 'bg-green-100 text-green-800 border-green-200',
                label: 'Active'
            },
            'expiring_soon': {
                icon: ExclamationTriangleIcon,
                classes: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                label: 'Expiring Soon'
            },
            'expired': {
                icon: XCircleIcon,
                classes: 'bg-red-100 text-red-800 border-red-200',
                label: 'Expired'
            },
            'completed': {
                icon: CheckCircleIcon,
                classes: 'bg-blue-100 text-blue-800 border-blue-200',
                label: 'Completed'
            }
        };

        const config = statusConfig[status] || statusConfig['completed'];
        const Icon = config.icon;

        return (
            <div className={`inline-flex items-center px-4 py-2 rounded-full text-sm font-medium border ${config.classes}`}>
                <Icon className="w-4 h-4 mr-2" />
                {config.label}
            </div>
        );
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatShortDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('id-ID');
    };

    const formatCurrency = (amount) => {
        if (!amount) return 'N/A';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const calculateDaysUntilExpiry = (expiryDate) => {
        if (!expiryDate) return null;
        const today = new Date();
        const expiry = new Date(expiryDate);
        const diffTime = expiry - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    };

    const daysUntilExpiry = calculateDaysUntilExpiry(trainingRecord.expiry_date);

    const getExpiryStatus = () => {
        if (!daysUntilExpiry) return null;

        if (daysUntilExpiry <= 0) {
            return {
                text: `Expired ${Math.abs(daysUntilExpiry)} days ago`,
                classes: 'text-red-600 bg-red-50 border-red-200'
            };
        } else if (daysUntilExpiry <= 30) {
            return {
                text: `Expires in ${daysUntilExpiry} days`,
                classes: 'text-yellow-600 bg-yellow-50 border-yellow-200'
            };
        } else {
            return {
                text: `Expires in ${daysUntilExpiry} days`,
                classes: 'text-green-600 bg-green-50 border-green-200'
            };
        }
    };

    const expiryStatus = getExpiryStatus();

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('training-records.index')}
                            className="text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Training Record Details
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Certificate #{trainingRecord.certificate_number}
                            </p>
                        </div>
                    </div>
                    <div className="flex space-x-3">
                        <Link
                            href={route('training-records.edit', trainingRecord.id)}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit Record
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Training Record - ${trainingRecord.certificate_number}`} />

            <div className="py-12">
                <div className="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">

                    {/* Status Banner */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-4">
                                    {getStatusBadge(trainingRecord.status)}
                                    {expiryStatus && (
                                        <div className={`px-3 py-1 rounded-lg border text-sm font-medium ${expiryStatus.classes}`}>
                                            {expiryStatus.text}
                                        </div>
                                    )}
                                </div>
                                <div className="text-right">
                                    <div className="text-sm text-gray-500">Created</div>
                                    <div className="text-sm font-medium text-gray-900">
                                        {formatShortDate(trainingRecord.created_at)}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">

                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">

                            {/* Certificate Information */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <DocumentTextIcon className="w-5 h-5 mr-2 text-blue-600" />
                                        Certificate Information
                                    </h3>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Certificate Number</dt>
                                            <dd className="mt-1 text-sm text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded">
                                                {trainingRecord.certificate_number}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Issuer</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{trainingRecord.issuer}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Training Type</dt>
                                            <dd className="mt-1">
                                                <div className="flex items-center text-sm text-gray-900">
                                                    <AcademicCapIcon className="w-4 h-4 mr-2 text-gray-400" />
                                                    {trainingRecord.training_type?.name || 'N/A'}
                                                </div>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Validity Period</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {trainingRecord.training_type?.validity_months ?
                                                    `${trainingRecord.training_type.validity_months} months` :
                                                    'N/A'
                                                }
                                            </dd>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Dates */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <CalendarIcon className="w-5 h-5 mr-2 text-purple-600" />
                                        Important Dates
                                    </h3>

                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Issue Date</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{formatDate(trainingRecord.issue_date)}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Completion Date</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {trainingRecord.completion_date ? formatDate(trainingRecord.completion_date) : 'N/A'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Expiry Date</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {trainingRecord.expiry_date ? (
                                                    <div className="space-y-1">
                                                        <div>{formatDate(trainingRecord.expiry_date)}</div>
                                                        {expiryStatus && (
                                                            <div className={`text-xs px-2 py-1 rounded ${expiryStatus.classes}`}>
                                                                {expiryStatus.text}
                                                            </div>
                                                        )}
                                                    </div>
                                                ) : 'N/A'}
                                            </dd>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Additional Details */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <UserGroupIcon className="w-5 h-5 mr-2 text-indigo-600" />
                                        Training Details
                                    </h3>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {trainingRecord.score && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Score</dt>
                                                <dd className="mt-1 flex items-center">
                                                    <StarIcon className="w-4 h-4 mr-1 text-yellow-500" />
                                                    <span className="text-sm text-gray-900 font-medium">
                                                        {trainingRecord.score}%
                                                    </span>
                                                </dd>
                                            </div>
                                        )}

                                        {trainingRecord.training_hours && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Training Hours</dt>
                                                <dd className="mt-1 flex items-center text-sm text-gray-900">
                                                    <ClockIcon className="w-4 h-4 mr-1 text-gray-400" />
                                                    {trainingRecord.training_hours} hours
                                                </dd>
                                            </div>
                                        )}

                                        {trainingRecord.cost && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Cost</dt>
                                                <dd className="mt-1 flex items-center text-sm text-gray-900">
                                                    <CurrencyDollarIcon className="w-4 h-4 mr-1 text-gray-400" />
                                                    {formatCurrency(trainingRecord.cost)}
                                                </dd>
                                            </div>
                                        )}

                                        {trainingRecord.location && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">Location</dt>
                                                <dd className="mt-1 flex items-center text-sm text-gray-900">
                                                    <MapPinIcon className="w-4 h-4 mr-1 text-gray-400" />
                                                    {trainingRecord.location}
                                                </dd>
                                            </div>
                                        )}

                                        {trainingRecord.instructor_name && (
                                            <div className="md:col-span-2">
                                                <dt className="text-sm font-medium text-gray-500">Instructor</dt>
                                                <dd className="mt-1 flex items-center text-sm text-gray-900">
                                                    <UserIcon className="w-4 h-4 mr-1 text-gray-400" />
                                                    {trainingRecord.instructor_name}
                                                </dd>
                                            </div>
                                        )}
                                    </div>

                                    {trainingRecord.notes && (
                                        <div className="mt-6 pt-6 border-t border-gray-200">
                                            <dt className="text-sm font-medium text-gray-500 flex items-center">
                                                <ChatBubbleBottomCenterTextIcon className="w-4 h-4 mr-1" />
                                                Notes
                                            </dt>
                                            <dd className="mt-2 text-sm text-gray-900 bg-gray-50 p-4 rounded-lg">
                                                {trainingRecord.notes}
                                            </dd>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">

                            {/* Employee Information */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                        <UserIcon className="w-5 h-5 mr-2 text-green-600" />
                                        Employee
                                    </h3>

                                    <div className="space-y-4">
                                        <div className="flex items-center space-x-4">
                                            <div className="flex-shrink-0">
                                                <div className="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                                    <UserIcon className="h-6 w-6 text-green-600" />
                                                </div>
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-gray-900">
                                                    {trainingRecord.employee?.name}
                                                </p>
                                                <p className="text-sm text-gray-500">
                                                    ID: {trainingRecord.employee?.employee_id}
                                                </p>
                                            </div>
                                        </div>

                                        {trainingRecord.employee?.department && (
                                            <div className="pt-2 border-t border-gray-200">
                                                <div className="flex items-center text-sm text-gray-600">
                                                    <BuildingOfficeIcon className="w-4 h-4 mr-2" />
                                                    {trainingRecord.employee.department.name}
                                                </div>
                                            </div>
                                        )}

                                        <div className="pt-2">
                                            <Link
                                                href={route('employees.show', trainingRecord.employee?.id)}
                                                className="text-sm text-blue-600 hover:text-blue-500 font-medium"
                                            >
                                                View Employee Profile →
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Related Records */}
                            {relatedRecords && relatedRecords.length > 0 && (
                                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                    <div className="p-6">
                                        <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                            <ClipboardDocumentListIcon className="w-5 h-5 mr-2 text-gray-600" />
                                            Other Records
                                        </h3>

                                        <div className="space-y-3">
                                            {relatedRecords.slice(0, 5).map((record) => (
                                                <Link
                                                    key={record.id}
                                                    href={route('training-records.show', record.id)}
                                                    className="block p-3 rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-colors"
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-gray-900 truncate">
                                                                {record.training_type?.name}
                                                            </p>
                                                            <p className="text-xs text-gray-500">
                                                                {record.certificate_number}
                                                            </p>
                                                        </div>
                                                        <div className="text-xs text-gray-500">
                                                            {formatShortDate(record.issue_date)}
                                                        </div>
                                                    </div>
                                                </Link>
                                            ))}

                                            {relatedRecords.length > 5 && (
                                                <div className="text-center pt-2">
                                                    <Link
                                                        href={route('training-records.index', { employee: trainingRecord.employee?.id })}
                                                        className="text-sm text-blue-600 hover:text-blue-500 font-medium"
                                                    >
                                                        View all {relatedRecords.length} records →
                                                    </Link>
                                                </div>
                                            )}
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
