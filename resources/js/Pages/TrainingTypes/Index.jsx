import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowPathIcon,
    AcademicCapIcon,
    UserIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    PlusIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, certificateTypes, filters = {} }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    const handleSearch = () => {
        const params = {
            search: searchTerm || undefined,
            status: selectedStatus || undefined,
        };

        Object.keys(params).forEach(key => {
            if (!params[key]) delete params[key];
        });

        router.get(route('training-types.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('');
        router.get(route('training-types.index'));
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    const getCertificateStats = (type) => {
        const stats = type.container_stats || {};
        return {
            total: stats.unique_employees || 0,
            active: stats.active_certificates || 0,
            expired: stats.expired_certificates || 0,
            expiring: stats.expiring_soon_certificates || 0
        };
    };

    const getStatusColor = (type) => {
        const stats = getCertificateStats(type);

        if (stats.total === 0) {
            return 'bg-gray-400';
        }

        if (stats.expired > 0) {
            return 'bg-red-500';
        }

        if (stats.expiring > 0) {
            return 'bg-yellow-500';
        }

        if (stats.active > 0) {
            return 'bg-green-500';
        }

        return 'bg-gray-400';
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Training Types" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="md:flex md:items-center md:justify-between mb-8">
                        <div className="flex-1 min-w-0">
                            <h2 className="text-3xl font-bold leading-7 text-slate-900 sm:text-4xl">
                                Training Types
                            </h2>
                            <p className="mt-2 text-lg text-slate-600">
                                Certificate types and who holds them
                            </p>
                        </div>

                        <div className="mt-4 md:mt-0 md:ml-4">
                            <Link
                                href={route('training-types.create')}
                                className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition-colors"
                            >
                                <PlusIcon className="w-4 h-4 mr-2" />
                                Add Training Type
                            </Link>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {/* Search */}
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-2">
                                    Search
                                </label>
                                <div className="relative">
                                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" />
                                    <input
                                        type="text"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        onKeyPress={handleKeyPress}
                                        placeholder="Search training types..."
                                        className="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                            </div>

                            {/* Status Filter */}
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-2">
                                    Status
                                </label>
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => setSelectedStatus(e.target.value)}
                                    className="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="mandatory">Mandatory</option>
                                </select>
                            </div>

                            {/* Action Buttons */}
                            <div className="flex items-end space-x-3">
                                <button
                                    onClick={handleSearch}
                                    className="flex-1 bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 transition-colors font-medium"
                                >
                                    <FunnelIcon className="w-5 h-5 mr-2 inline" />
                                    Filter
                                </button>
                                <button
                                    onClick={resetFilters}
                                    className="px-4 py-2.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors"
                                    title="Reset Filters"
                                >
                                    <ArrowPathIcon className="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Results Info */}
                    <div className="flex items-center justify-between mb-6">
                        <div className="text-sm text-slate-600">
                            Showing {certificateTypes?.from || 0} to {certificateTypes?.to || 0} of {certificateTypes?.total || 0} training types
                        </div>
                    </div>

                    {/* Grid Content */}
                    {certificateTypes?.data?.length > 0 ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            {certificateTypes.data.map((type) => {
                                const stats = getCertificateStats(type);
                                const statusColor = getStatusColor(type);

                                return (
                                    <Link
                                        key={type.id}
                                        href={route('training-types.container', type.id)}
                                        className="group bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-lg hover:border-green-300 transition-all duration-200 overflow-hidden"
                                    >
                                        <div className="p-6">
                                            {/* Header with Icon */}
                                            <div className="flex items-start justify-between mb-4">
                                                <div className="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                                    <AcademicCapIcon className="w-6 h-6 text-white" />
                                                </div>

                                                {/* Status Dot */}
                                                <div className={`w-3 h-3 rounded-full ${statusColor}`}></div>
                                            </div>

                                            {/* Training Type Info */}
                                            <div className="mb-6">
                                                <h3 className="font-semibold text-slate-900 text-lg mb-2 group-hover:text-green-700 transition-colors line-clamp-2">
                                                    {type.name}
                                                </h3>
                                                {type.category && (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                                        {type.category}
                                                    </span>
                                                )}
                                            </div>

                                            {/* Certificate Statistics */}
                                            <div className="space-y-4">
                                                {/* Employee Count */}
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center text-sm text-slate-600">
                                                        <UserIcon className="w-4 h-4 mr-2" />
                                                        Employees
                                                    </div>
                                                    <span className="font-semibold text-slate-900">
                                                        {stats.total}
                                                    </span>
                                                </div>

                                                {/* Certificate Status Grid */}
                                                {stats.total > 0 && (
                                                    <div className="grid grid-cols-3 gap-2 text-xs">
                                                        <div className="text-center">
                                                            <div className="flex items-center justify-center w-6 h-6 bg-green-100 rounded-full mx-auto mb-1">
                                                                <CheckCircleIcon className="w-3 h-3 text-green-600" />
                                                            </div>
                                                            <div className="font-medium text-green-700">{stats.active}</div>
                                                            <div className="text-green-600">Active</div>
                                                        </div>
                                                        {stats.expiring > 0 && (
                                                            <div className="text-center">
                                                                <div className="flex items-center justify-center w-6 h-6 bg-yellow-100 rounded-full mx-auto mb-1">
                                                                    <ClockIcon className="w-3 h-3 text-yellow-600" />
                                                                </div>
                                                                <div className="font-medium text-yellow-700">{stats.expiring}</div>
                                                                <div className="text-yellow-600">Expiring</div>
                                                            </div>
                                                        )}
                                                        {stats.expired > 0 && (
                                                            <div className="text-center">
                                                                <div className="flex items-center justify-center w-6 h-6 bg-red-100 rounded-full mx-auto mb-1">
                                                                    <XCircleIcon className="w-3 h-3 text-red-600" />
                                                                </div>
                                                                <div className="font-medium text-red-700">{stats.expired}</div>
                                                                <div className="text-red-600">Expired</div>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </Link>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <FolderIcon className="w-12 h-12 text-slate-400" />
                            </div>
                            <h3 className="text-xl font-medium text-slate-900 mb-2">
                                No training types found
                            </h3>
                            <p className="text-slate-600 mb-6 max-w-md mx-auto">
                                {searchTerm || selectedStatus ? (
                                    'No training types match your current search criteria. Try adjusting your filters.'
                                ) : (
                                    'Get started by creating your first training type to manage certificate requirements.'
                                )}
                            </p>
                            <Link
                                href={route('training-types.create')}
                                className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition-colors"
                            >
                                <PlusIcon className="w-4 h-4 mr-2" />
                                Create First Training Type
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
