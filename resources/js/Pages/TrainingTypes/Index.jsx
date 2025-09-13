import { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import MemoizedCard from '@/Components/Performance/MemoizedCard';
import OptimizedPagination from '@/Components/Performance/OptimizedPagination';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowPathIcon,
    AcademicCapIcon,
    UserIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    PlusIcon,
    FolderIcon
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

    // Memoized calculations for better performance
    const processedCertificateTypes = useMemo(() => {
        if (!certificateTypes?.data) return [];

        return certificateTypes.data.map(type => {
            const stats = type.container_stats || {};
            const certificateStats = {
                employees: stats.unique_employees || 0,
                active: stats.active_certificates || 0,
                expired: stats.expired_certificates || 0,
                expiring: stats.expiring_soon_certificates || 0
            };

            // Determine status based on certificate state
            let status = 'inactive';
            if (certificateStats.employees === 0) {
                status = 'inactive';
            } else if (certificateStats.expired > 0) {
                status = 'critical';
            } else if (certificateStats.expiring > 0) {
                status = 'warning';
            } else if (certificateStats.active > 0) {
                status = 'active';
            }

            return {
                ...type,
                status,
                certificateStats,
                href: route('training-types.container', type.id)
            };
        });
    }, [certificateTypes]);

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

                    {/* Optimized Grid Content */}
                    {processedCertificateTypes.length > 0 ? (
                        <>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                {processedCertificateTypes.map((type) => (
                                    <MemoizedCard
                                        key={type.id}
                                        title={type.name}
                                        subtitle={type.category}
                                        description={type.description}
                                        href={type.href}
                                        icon={<AcademicCapIcon />}
                                        status={type.status}
                                        stats={{
                                            employees: type.certificateStats.employees,
                                            active: type.certificateStats.active,
                                            expired: type.certificateStats.expired,
                                            expiring: type.certificateStats.expiring,
                                        }}
                                        metadata={{
                                            mandatory: type.is_mandatory ? 'Yes' : 'No',
                                            validity: type.validity_months ? `${type.validity_months} months` : 'N/A',
                                            code: type.code || 'N/A',
                                        }}
                                        badge={
                                            type.is_mandatory 
                                                ? { text: 'Mandatory', status: 'active' }
                                                : type.is_active 
                                                ? { text: 'Active', status: 'active' }
                                                : { text: 'Inactive', status: 'inactive' }
                                        }
                                        className="hover:border-green-300"
                                    />
                                ))}
                            </div>

                            {/* Optimized Pagination */}
                            <div className="mt-8">
                                <OptimizedPagination 
                                    data={certificateTypes}
                                    preserveState={true}
                                    preserveScroll={true}
                                    showStats={true}
                                    maxVisiblePages={7}
                                    prefetchPages={true}
                                />
                            </div>
                        </>
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
