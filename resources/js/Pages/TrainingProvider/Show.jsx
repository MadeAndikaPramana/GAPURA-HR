import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    BuildingOfficeIcon,
    UserIcon,
    EnvelopeIcon,
    PhoneIcon,
    GlobeAltIcon,
    ShieldCheckIcon,
    DocumentTextIcon,
    CalendarDaysIcon,
    StarIcon,
    ChartBarIcon,
    ClipboardDocumentListIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    TrophyIcon,
    TrendingUpIcon,
    TrendingDownIcon,
    MinusIcon,
    ArrowDownTrayIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, provider, stats, performance, recentTrainings, trainingsByType, trainingTrend }) {
    const [activeTab, setActiveTab] = useState('overview');

    const handleDelete = () => {
        const confirmMessage = `⚠️ HAPUS Training Provider "${provider.name}"?\n\n` +
                              `📊 PROVIDER STATISTICS:\n` +
                              `• Total Trainings: ${stats.total_trainings || 0}\n` +
                              `• Completed Trainings: ${stats.completed_trainings || 0}\n` +
                              `• Active Certificates: ${stats.active_certificates || 0}\n` +
                              `• Unique Employees: ${stats.unique_employees || 0}\n\n` +
                              `⚠️ PERINGATAN:\n` +
                              `• Tindakan ini TIDAK DAPAT dibatalkan\n` +
                              `• Provider hanya bisa dihapus jika TIDAK ada training records terkait\n\n` +
                              `💡 ALTERNATIF: Non-aktifkan provider ini untuk menjaga data historis\n\n` +
                              `Lanjutkan hapus?`;

        if (confirm(confirmMessage)) {
            router.delete(route('training-providers.destroy', provider.id), {
                onError: (errors) => {
                    let errorMessage = 'Terjadi kesalahan tidak diketahui';

                    if (typeof errors === 'object' && errors.message) {
                        errorMessage = errors.message;
                    } else if (typeof errors === 'string') {
                        errorMessage = errors;
                    } else if (typeof errors === 'object') {
                        errorMessage = Object.values(errors).join('\n');
                    }

                    alert(errorMessage);
                }
            });
        }
    };

    const toggleStatus = () => {
        if (confirm(`Are you sure you want to ${provider.is_active ? 'deactivate' : 'activate'} ${provider.name}?`)) {
            router.post(route('training-providers.toggle-status', provider.id));
        }
    };

    const getRatingStars = (rating) => {
        const stars = [];
        const ratingValue = parseFloat(rating) || 0;
        const fullStars = Math.floor(ratingValue);
        const hasHalfStar = ratingValue % 1 !== 0;

        for (let i = 0; i < 5; i++) {
            if (i < fullStars) {
                stars.push(
                    <StarIcon key={i} className="w-4 h-4 text-yellow-400 fill-current" />
                );
            } else if (i === fullStars && hasHalfStar) {
                stars.push(
                    <StarIcon key={i} className="w-4 h-4 text-yellow-400 fill-current opacity-50" />
                );
            } else {
                stars.push(
                    <StarIcon key={i} className="w-4 h-4 text-gray-300" />
                );
            }
        }

        return stars;
    };

    const getAccreditationStatus = () => {
        if (!provider.accreditation_expiry) {
            return { status: 'none', color: 'text-gray-500', text: 'No Accreditation', bgColor: 'bg-gray-100' };
        }

        const expiryDate = new Date(provider.accreditation_expiry);
        const now = new Date();
        const daysUntilExpiry = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));

        if (daysUntilExpiry < 0) {
            return {
                status: 'expired',
                color: 'text-red-600',
                text: `Expired ${Math.abs(daysUntilExpiry)} days ago`,
                bgColor: 'bg-red-100'
            };
        } else if (daysUntilExpiry <= 30) {
            return {
                status: 'expiring',
                color: 'text-orange-600',
                text: `Expires in ${daysUntilExpiry} days`,
                bgColor: 'bg-orange-100'
            };
        } else if (daysUntilExpiry <= 90) {
            return {
                status: 'warning',
                color: 'text-yellow-600',
                text: `Expires in ${daysUntilExpiry} days`,
                bgColor: 'bg-yellow-100'
            };
        } else {
            return {
                status: 'valid',
                color: 'text-green-600',
                text: 'Valid',
                bgColor: 'bg-green-100'
            };
        }
    };

    const getPerformanceTrendIcon = (trend) => {
        switch (trend) {
            case 'improving':
                return <TrendingUpIcon className="w-5 h-5 text-green-500" />;
            case 'declining':
                return <TrendingDownIcon className="w-5 h-5 text-red-500" />;
            case 'stable':
                return <MinusIcon className="w-5 h-5 text-gray-500" />;
            default:
                return <ChartBarIcon className="w-5 h-5 text-gray-400" />;
        }
    };

    const accreditationStatus = getAccreditationStatus();

    const tabs = [
        { id: 'overview', name: 'Overview', icon: ChartBarIcon },
        { id: 'trainings', name: 'Training Records', icon: ClipboardDocumentListIcon },
        { id: 'analytics', name: 'Analytics', icon: TrendingUpIcon }
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('training-providers.index')}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-2" />
                            Back to Training Providers
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {provider.name}
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Training Provider Details & Performance Analytics
                            </p>
                        </div>
                    </div>

                    <div className="flex space-x-2">
                        <button
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                            Export Report
                        </button>
                        <Link
                            href={route('training-providers.edit', provider.id)}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit
                        </Link>
                        <button
                            onClick={toggleStatus}
                            className={`inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 ${
                                provider.is_active
                                    ? 'bg-orange-600 hover:bg-orange-700 focus:bg-orange-700 active:bg-orange-900 focus:ring-orange-500'
                                    : 'bg-green-600 hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:ring-green-500'
                            }`}
                        >
                            {provider.is_active ? <XCircleIcon className="w-4 h-4 mr-2" /> : <CheckCircleIcon className="w-4 h-4 mr-2" />}
                            {provider.is_active ? 'Deactivate' : 'Activate'}
                        </button>
                        <button
                            onClick={handleDelete}
                            className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <TrashIcon className="w-4 h-4 mr-2" />
                            Delete
                        </button>
                    </div>
                </div>
            }
        >
            <Head title={`Provider - ${provider.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Provider Status Alerts */}
                    {!provider.is_active && (
                        <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div className="flex">
                                <ExclamationTriangleIcon className="h-5 w-5 text-red-400" />
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-red-800">
                                        Provider is Currently Inactive
                                    </h3>
                                    <p className="mt-1 text-sm text-red-700">
                                        This provider will not appear in training assignment options while inactive.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {accreditationStatus.status === 'expired' && (
                        <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div className="flex">
                                <ExclamationTriangleIcon className="h-5 w-5 text-red-400" />
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-red-800">
                                        Accreditation Expired
                                    </h3>
                                    <p className="mt-1 text-sm text-red-700">
                                        {accreditationStatus.text}. Please update accreditation information.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {accreditationStatus.status === 'expiring' && (
                        <div className="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div className="flex">
                                <ExclamationTriangleIcon className="h-5 w-5 text-yellow-400" />
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-yellow-800">
                                        Accreditation Expiring Soon
                                    </h3>
                                    <p className="mt-1 text-sm text-yellow-700">
                                        {accreditationStatus.text}. Consider renewal process.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Provider Overview Card */}
                    <div className="bg-white shadow rounded-lg mb-8">
                        <div className="px-6 py-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-6">
                                    <div className="flex-shrink-0">
                                        <div className="h-16 w-16 rounded-full bg-green-100 flex items-center justify-center">
                                            <BuildingOfficeIcon className="h-8 w-8 text-green-600" />
                                        </div>
                                    </div>
                                    <div>
                                        <h3 className="text-2xl font-bold text-gray-900">{provider.name}</h3>
                                        {provider.code && (
                                            <p className="text-sm text-gray-500">Code: {provider.code}</p>
                                        )}
                                        <div className="flex items-center space-x-4 mt-2">
                                            {provider.rating && (
                                                <div className="flex items-center">
                                                    <div className="flex mr-2">
                                                        {getRatingStars(provider.rating)}
                                                    </div>
                                                    <span className="text-sm text-gray-600">
                                                        ({provider.rating}/5.0)
                                                    </span>
                                                </div>
                                            )}
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                provider.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800'
                                            }`}>
                                                {provider.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${accreditationStatus.bgColor} ${accreditationStatus.color}`}>
                                                <ShieldCheckIcon className="w-3 h-3 mr-1" />
                                                {accreditationStatus.text}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {/* Performance Summary */}
                                <div className="text-right">
                                    <div className="flex items-center justify-end space-x-4">
                                        <div className="text-center">
                                            <div className="text-2xl font-bold text-green-600">{stats.completed_trainings || 0}</div>
                                            <div className="text-xs text-gray-500">Trainings Completed</div>
                                        </div>
                                        <div className="text-center">
                                            <div className="text-2xl font-bold text-blue-600">{stats.unique_employees || 0}</div>
                                            <div className="text-xs text-gray-500">Employees Trained</div>
                                        </div>
                                        <div className="text-center">
                                            <div className="flex items-center justify-center">
                                                <span className="text-2xl font-bold text-gray-900">{performance?.completion_rate || 0}%</span>
                                                {getPerformanceTrendIcon(performance?.recent_performance_trend)}
                                            </div>
                                            <div className="text-xs text-gray-500">Completion Rate</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <ClipboardDocumentListIcon className="h-8 w-8 text-blue-600" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Total Trainings
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {stats.total_trainings || 0}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <CheckCircleIcon className="h-8 w-8 text-green-600" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Active Certificates
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {stats.active_certificates || 0}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <ExclamationTriangleIcon className="h-8 w-8 text-yellow-600" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Expiring Soon
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {stats.expiring_certificates || 0}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <XCircleIcon className="h-8 w-8 text-red-600" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Expired
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {stats.expired_certificates || 0}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Tab Navigation */}
                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8 px-6">
                                {tabs.map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => setActiveTab(tab.id)}
                                        className={`py-4 px-1 border-b-2 font-medium text-sm flex items-center ${
                                            activeTab === tab.id
                                                ? 'border-green-500 text-green-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }`}
                                    >
                                        <tab.icon className="w-5 h-5 mr-2" />
                                        {tab.name}
                                    </button>
                                ))}
                            </nav>
                        </div>

                        {/* Tab Content */}
                        <div className="p-6">
                            {activeTab === 'overview' && (
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    {/* Provider Information */}
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                            <BuildingOfficeIcon className="w-5 h-5 mr-2 text-green-600" />
                                            Provider Information
                                        </h3>
                                        <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                                            {provider.contact_person && (
                                                <div className="flex items-center">
                                                    <UserIcon className="w-4 h-4 text-gray-400 mr-3" />
                                                    <span className="text-sm text-gray-900">{provider.contact_person}</span>
                                                </div>
                                            )}
                                            {provider.email && (
                                                <div className="flex items-center">
                                                    <EnvelopeIcon className="w-4 h-4 text-gray-400 mr-3" />
                                                    <a href={`mailto:${provider.email}`} className="text-sm text-green-600 hover:text-green-900">
                                                        {provider.email}
                                                    </a>
                                                </div>
                                            )}
                                            {provider.phone && (
                                                <div className="flex items-center">
                                                    <PhoneIcon className="w-4 h-4 text-gray-400 mr-3" />
                                                    <a href={`tel:${provider.phone}`} className="text-sm text-green-600 hover:text-green-900">
                                                        {provider.phone}
                                                    </a>
                                                </div>
                                            )}
                                            {provider.website && (
                                                <div className="flex items-center">
                                                    <GlobeAltIcon className="w-4 h-4 text-gray-400 mr-3" />
                                                    <a href={provider.website} target="_blank" rel="noopener noreferrer" className="text-sm text-green-600 hover:text-green-900">
                                                        Visit Website
                                                    </a>
                                                </div>
                                            )}
                                            {provider.address && (
                                                <div className="flex items-start">
                                                    <BuildingOfficeIcon className="w-4 h-4 text-gray-400 mr-3 mt-0.5" />
                                                    <span className="text-sm text-gray-900">{provider.address}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Accreditation & Contract Info */}
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                            <ShieldCheckIcon className="w-5 h-5 mr-2 text-green-600" />
                                            Accreditation & Contract
                                        </h3>
                                        <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                                            {provider.accreditation_number && (
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm font-medium text-gray-700">Accreditation:</span>
                                                    <span className="text-sm text-gray-900">{provider.accreditation_number}</span>
                                                </div>
                                            )}
                                            {provider.accreditation_expiry && (
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm font-medium text-gray-700">Expires:</span>
                                                    <span className={`text-sm ${accreditationStatus.color}`}>
                                                        {new Date(provider.accreditation_expiry).toLocaleDateString()}
                                                    </span>
                                                </div>
                                            )}
                                            {provider.contract_start_date && (
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm font-medium text-gray-700">Contract Start:</span>
                                                    <span className="text-sm text-gray-900">
                                                        {new Date(provider.contract_start_date).toLocaleDateString()}
                                                    </span>
                                                </div>
                                            )}
                                            {provider.contract_end_date && (
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm font-medium text-gray-700">Contract End:</span>
                                                    <span className="text-sm text-gray-900">
                                                        {new Date(provider.contract_end_date).toLocaleDateString()}
                                                    </span>
                                                </div>
                                            )}
                                        </div>

                                        {provider.notes && (
                                            <div className="mt-6">
                                                <h4 className="text-md font-medium text-gray-900 mb-2">Notes</h4>
                                                <div className="bg-gray-50 rounded-lg p-4">
                                                    <p className="text-sm text-gray-700">{provider.notes}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {activeTab === 'trainings' && (
                                <div>
                                    <div className="flex items-center justify-between mb-6">
                                        <h3 className="text-lg font-medium text-gray-900">Recent Training Records</h3>
                                        <span className="text-sm text-gray-500">
                                            Last {recentTrainings?.length || 0} training records
                                        </span>
                                    </div>

                                    {recentTrainings && recentTrainings.length > 0 ? (
                                        <div className="space-y-4">
                                            {recentTrainings.map((record) => (
                                                <div key={record.id} className="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center space-x-4">
                                                            <div className="flex-shrink-0">
                                                                <div className="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                                    <UserIcon className="h-5 w-5 text-blue-600" />
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {record.employee?.name || 'Unknown Employee'}
                                                                </div>
                                                                <div className="text-sm text-gray-500">
                                                                    {record.training_type?.name || 'Unknown Training Type'}
                                                                </div>
                                                                <div className="text-xs text-gray-400">
                                                                    Certificate: {record.certificate_number || 'N/A'}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="text-right">
                                                            <div className="text-sm text-gray-900">
                                                                Completed: {record.completion_date ? new Date(record.completion_date).toLocaleDateString() : 'N/A'}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                Expires: {record.expiry_date ? new Date(record.expiry_date).toLocaleDateString() : 'No expiry'}
                                                            </div>
                                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                                record.compliance_status === 'compliant'
                                                                    ? 'bg-green-100 text-green-800'
                                                                    : record.compliance_status === 'expiring_soon'
                                                                    ? 'bg-yellow-100 text-yellow-800'
                                                                    : 'bg-red-100 text-red-800'
                                                            }`}>
                                                                {record.compliance_status === 'compliant' ? 'Active' :
                                                                 record.compliance_status === 'expiring_soon' ? 'Expiring' : 'Expired'}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center py-8">
                                            <ClipboardDocumentListIcon className="mx-auto h-12 w-12 text-gray-400" />
                                            <h3 className="mt-2 text-sm font-medium text-gray-900">No training records</h3>
                                            <p className="mt-1 text-sm text-gray-500">
                                                This provider hasn't delivered any trainings yet.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            )}

                            {activeTab === 'analytics' && (
                                <div className="space-y-8">
                                    {/* Performance Metrics */}
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                            <TrophyIcon className="w-5 h-5 mr-2 text-green-600" />
                                            Performance Metrics
                                        </h3>
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <p className="text-sm font-medium text-green-800">Completion Rate</p>
                                                        <p className="text-2xl font-bold text-green-900">{performance?.completion_rate || 0}%</p>
                                                    </div>
                                                    <CheckCircleIcon className="h-8 w-8 text-green-600" />
                                                </div>
                                            </div>

                                            {performance?.average_score && (
                                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                                    <div className="flex items-center justify-between">
                                                        <div>
                                                            <p className="text-sm font-medium text-blue-800">Average Score</p>
                                                            <p className="text-2xl font-bold text-blue-900">{performance.average_score}/100</p>
                                                        </div>
                                                        <StarIcon className="h-8 w-8 text-blue-600 fill-current" />
                                                    </div>
                                                </div>
                                            )}

                                            <div className="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <p className="text-sm font-medium text-purple-800">Training Types</p>
                                                        <p className="text-2xl font-bold text-purple-900">{stats.unique_training_types || 0}</p>
                                                    </div>
                                                    <DocumentTextIcon className="h-8 w-8 text-purple-600" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Training by Type Breakdown */}
                                    {trainingsByType && Object.keys(trainingsByType).length > 0 && (
                                        <div>
                                            <h3 className="text-lg font-medium text-gray-900 mb-4">Training by Type</h3>
                                            <div className="space-y-4">
                                                {Object.entries(trainingsByType).map(([typeName, data]) => (
                                                    <div key={typeName} className="border border-gray-200 rounded-lg p-4">
                                                        <div className="flex items-center justify-between mb-2">
                                                            <h4 className="text-md font-medium text-gray-900">{typeName}</h4>
                                                            <span className="text-sm text-gray-500">{data.total} total</span>
                                                        </div>
                                                        <div className="grid grid-cols-3 gap-4 text-sm">
                                                            <div className="text-center">
                                                                <div className="text-lg font-semibold text-green-600">{data.active}</div>
                                                                <div className="text-xs text-gray-500">Active</div>
                                                            </div>
                                                            <div className="text-center">
                                                                <div className="text-lg font-semibold text-blue-600">{data.completed}</div>
                                                                <div className="text-xs text-gray-500">Completed</div>
                                                            </div>
                                                            <div className="text-center">
                                                                <div className="text-lg font-semibold text-gray-600">{data.total}</div>
                                                                <div className="text-xs text-gray-500">Total</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Training Trend */}
                                    {trainingTrend && trainingTrend.length > 0 && (
                                        <div>
                                            <h3 className="text-lg font-medium text-gray-900 mb-4">Training Trend (Last 12 Months)</h3>
                                            <div className="bg-gray-50 rounded-lg p-4">
                                                <div className="grid grid-cols-6 gap-2 text-center">
                                                    {trainingTrend.slice(-6).map((month) => (
                                                        <div key={month.month} className="text-center">
                                                            <div className="text-lg font-semibold text-gray-900">{month.count}</div>
                                                            <div className="text-xs text-gray-500">{month.month}</div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
