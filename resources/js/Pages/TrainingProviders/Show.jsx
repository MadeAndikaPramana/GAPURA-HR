import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    PencilIcon,
    UsersIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    ChartBarIcon,
    ClipboardDocumentListIcon,
    CalendarIcon,
    UserPlusIcon,
    DocumentArrowDownIcon,
    EyeIcon,
    BuildingOfficeIcon,
    TrophyIcon,
    ArrowTrendingUpIcon,  // ✅ Fixed: Changed from TrendingUpIcon
    ArrowTrendingDownIcon, // ✅ Fixed: Changed from TrendingDownIcon
    MinusIcon,
    StarIcon,
    CurrencyDollarIcon,
    AcademicCapIcon,
    PhoneIcon,
    EnvelopeIcon,
    GlobeAltIcon,
    MapPinIcon
} from '@heroicons/react/24/outline';

export default function Show({
    auth,
    provider,
    stats,
    performance,
    recentTrainings,
    trainingsByType,
    trainingTrend
}) {
    const [activeTab, setActiveTab] = useState('overview');

    const getStatusColor = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            expiring_soon: 'bg-yellow-100 text-yellow-800',
            expired: 'bg-red-100 text-red-800',
            suspended: 'bg-gray-100 text-gray-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getRatingStars = (rating) => {
        const stars = [];
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 !== 0;

        for (let i = 0; i < fullStars; i++) {
            stars.push(
                <StarIcon key={i} className="h-4 w-4 text-yellow-400 fill-current" />
            );
        }

        if (hasHalfStar) {
            stars.push(
                <StarIcon key="half" className="h-4 w-4 text-yellow-400" />
            );
        }

        const remainingStars = 5 - Math.ceil(rating);
        for (let i = 0; i < remainingStars; i++) {
            stars.push(
                <StarIcon key={`empty-${i}`} className="h-4 w-4 text-gray-300" />
            );
        }

        return stars;
    };

    const getPerformanceTrendIcon = (trend) => {
        if (trend === 'up') {
            return <ArrowTrendingUpIcon className="h-4 w-4 text-green-500 ml-1" />;  // ✅ Fixed
        } else if (trend === 'down') {
            return <ArrowTrendingDownIcon className="h-4 w-4 text-red-500 ml-1" />; // ✅ Fixed
        } else {
            return <MinusIcon className="h-4 w-4 text-gray-500 ml-1" />;
        }
    };

    const toggleStatus = () => {
        router.post(route('training-providers.toggle-status', provider.id), {}, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const tabs = [
        { id: 'overview', name: 'Overview', icon: ChartBarIcon },
        { id: 'trainings', name: 'Training Records', icon: ClipboardDocumentListIcon },
        { id: 'analytics', name: 'Analytics', icon: ArrowTrendingUpIcon } // ✅ Fixed
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
                        <button className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <DocumentArrowDownIcon className="w-4 h-4 mr-2" />
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
                    </div>
                </div>
            }
        >
            <Head title={`Training Provider - ${provider.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                    {/* Provider Overview Card */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-8">
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                {/* Basic Info */}
                                <div className="lg:col-span-2 space-y-6">
                                    <div>
                                        <div className="flex items-start justify-between">
                                            <div>
                                                <h1 className="text-2xl font-bold text-gray-900">{provider.name}</h1>
                                                <p className="text-gray-600 mt-1">Code: {provider.code}</p>
                                                {provider.description && (
                                                    <p className="text-gray-700 mt-2">{provider.description}</p>
                                                )}
                                            </div>
                                            <div className={`px-3 py-1 rounded-full text-sm font-medium ${
                                                provider.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                            }`}>
                                                {provider.is_active ? 'Active' : 'Inactive'}
                                            </div>
                                        </div>
                                    </div>

                                    {/* Contact Information */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {provider.contact_person && (
                                            <div className="flex items-center space-x-3">
                                                <UsersIcon className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <p className="text-sm text-gray-500">Contact Person</p>
                                                    <p className="text-sm font-medium text-gray-900">{provider.contact_person}</p>
                                                </div>
                                            </div>
                                        )}

                                        {provider.phone && (
                                            <div className="flex items-center space-x-3">
                                                <PhoneIcon className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <p className="text-sm text-gray-500">Phone</p>
                                                    <p className="text-sm font-medium text-gray-900">{provider.phone}</p>
                                                </div>
                                            </div>
                                        )}

                                        {provider.email && (
                                            <div className="flex items-center space-x-3">
                                                <EnvelopeIcon className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <p className="text-sm text-gray-500">Email</p>
                                                    <p className="text-sm font-medium text-gray-900">{provider.email}</p>
                                                </div>
                                            </div>
                                        )}

                                        {provider.website && (
                                            <div className="flex items-center space-x-3">
                                                <GlobeAltIcon className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <p className="text-sm text-gray-500">Website</p>
                                                    <a
                                                        href={provider.website}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-sm font-medium text-blue-600 hover:text-blue-500"
                                                    >
                                                        {provider.website}
                                                    </a>
                                                </div>
                                            </div>
                                        )}

                                        {provider.address && (
                                            <div className="flex items-start space-x-3 md:col-span-2">
                                                <MapPinIcon className="h-5 w-5 text-gray-400 mt-0.5" />
                                                <div>
                                                    <p className="text-sm text-gray-500">Address</p>
                                                    <p className="text-sm font-medium text-gray-900">{provider.address}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Rating and Performance */}
                                <div className="bg-gray-50 rounded-lg p-6">
                                    <div className="space-y-6">
                                        {/* Rating */}
                                        <div>
                                            <div className="flex items-center justify-between mb-2">
                                                <h3 className="text-lg font-medium text-gray-900">Provider Rating</h3>
                                                <span className="text-2xl font-bold text-gray-900">{provider.rating || 0}</span>
                                            </div>
                                            <div className="flex items-center space-x-1">
                                                {getRatingStars(provider.rating || 0)}
                                            </div>
                                        </div>

                                        {/* Performance Metrics */}
                                        <div className="space-y-4">
                                            <div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-sm font-medium text-gray-600">Completion Rate</span>
                                                    <span className="text-lg font-semibold text-gray-900">{performance?.completion_rate || 0}%</span>
                                                    {getPerformanceTrendIcon(performance?.recent_performance_trend)}
                                                </div>
                                                <div className="text-xs text-gray-500">Completion Rate</div>
                                            </div>
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
                                    <StarIcon className="h-8 w-8 text-purple-600" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Average Score
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {performance?.average_score || 0}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Tab Navigation */}
                    <div className="bg-white shadow sm:rounded-lg">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8" aria-label="Tabs">
                                {tabs.map((tab) => {
                                    const Icon = tab.icon;
                                    return (
                                        <button
                                            key={tab.id}
                                            onClick={() => setActiveTab(tab.id)}
                                            className={`${
                                                activeTab === tab.id
                                                    ? 'border-blue-500 text-blue-600'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            } whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm flex items-center space-x-2`}
                                        >
                                            <Icon className="w-4 h-4" />
                                            <span>{tab.name}</span>
                                        </button>
                                    );
                                })}
                            </nav>
                        </div>

                        {/* Tab Content */}
                        <div className="p-6">
                            {activeTab === 'overview' && (
                                <div className="space-y-6">
                                    {/* Recent Training Records */}
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">Recent Training Records</h3>
                                        {recentTrainings && recentTrainings.length > 0 ? (
                                            <div className="bg-gray-50 rounded-lg overflow-hidden">
                                                <table className="min-w-full divide-y divide-gray-200">
                                                    <thead className="bg-gray-100">
                                                        <tr>
                                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                                Employee
                                                            </th>
                                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                                Training Type
                                                            </th>
                                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                                Completion Date
                                                            </th>
                                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                                Status
                                                            </th>
                                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                                Actions
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-gray-200">
                                                        {recentTrainings.map((training) => (
                                                            <tr key={training.id}>
                                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    {training.employee?.name}
                                                                </td>
                                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    {training.training_type?.name}
                                                                </td>
                                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                    {formatDate(training.completion_date)}
                                                                </td>
                                                                <td className="px-6 py-4 whitespace-nowrap">
                                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(training.status)}`}>
                                                                        {training.status?.replace('_', ' ').toUpperCase()}
                                                                    </span>
                                                                </td>
                                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                                    <Link
                                                                        href={route('training-records.show', training.id)}
                                                                        className="text-blue-600 hover:text-blue-900"
                                                                    >
                                                                        <EyeIcon className="w-4 h-4" />
                                                                    </Link>
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        ) : (
                                            <div className="text-center py-8">
                                                <ClipboardDocumentListIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                <h3 className="mt-2 text-sm font-medium text-gray-900">No recent trainings</h3>
                                                <p className="mt-1 text-sm text-gray-500">
                                                    This provider has no recent training records.
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {activeTab === 'trainings' && (
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">All Training Records</h3>
                                    <p className="text-gray-600">Complete list of training records from this provider will be displayed here.</p>
                                </div>
                            )}

                            {activeTab === 'analytics' && (
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">Provider Analytics</h3>
                                    <p className="text-gray-600">Detailed analytics and performance metrics will be displayed here.</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
