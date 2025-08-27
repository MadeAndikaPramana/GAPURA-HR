import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    ArrowDownTrayIcon,
    AdjustmentsHorizontalIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
    StarIcon,
    BuildingOfficeIcon,
    PhoneIcon,
    EnvelopeIcon,
    GlobeAltIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, providers, filters, stats, filterOptions }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [selectedRating, setSelectedRating] = useState(filters.rating || '');
    const [selectedAccreditation, setSelectedAccreditation] = useState(filters.accreditation || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('training-providers.index'), {
            search: searchTerm,
            status: selectedStatus,
            rating: selectedRating,
            accreditation: selectedAccreditation
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedStatus('');
        setSelectedRating('');
        setSelectedAccreditation('');
        router.get(route('training-providers.index'));
    };

    const toggleStatus = (provider) => {
        if (confirm(`Are you sure you want to ${provider.is_active ? 'deactivate' : 'activate'} ${provider.name}?`)) {
            router.post(route('training-providers.toggle-status', provider.id));
        }
    };

    const deleteProvider = (provider) => {
        const confirmMessage = `⚠️ HAPUS Training Provider "${provider.name}"?\n\n` +
                              `📊 INFO PROVIDER:\n` +
                              `• Total Trainings: ${provider.training_records_count || 0}\n` +
                              `• Completed: ${provider.completed_trainings || 0}\n` +
                              `• Rating: ${provider.rating ? provider.rating + '/5' : 'No rating'}\n\n` +
                              `⚠️ PERINGATAN:\n` +
                              `• Tindakan ini TIDAK DAPAT dibatalkan\n` +
                              `• Provider hanya bisa dihapus jika TIDAK ada training records terkait\n\n` +
                              `💡 ALTERNATIF: Klik toggle status untuk non-aktifkan saja\n\n` +
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

    const getRatingStars = (rating) => {
        const stars = [];
        const fullStars = Math.floor(rating || 0);
        const hasHalfStar = (rating || 0) % 1 !== 0;

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

    const getAccreditationStatus = (provider) => {
        if (!provider.accreditation_expiry) {
            return { status: 'none', color: 'text-gray-500', text: 'No Accreditation' };
        }

        const expiryDate = new Date(provider.accreditation_expiry);
        const now = new Date();
        const daysUntilExpiry = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));

        if (daysUntilExpiry < 0) {
            return { status: 'expired', color: 'text-red-600', text: 'Expired' };
        } else if (daysUntilExpiry <= 30) {
            return { status: 'expiring', color: 'text-orange-600', text: `Expires in ${daysUntilExpiry} days` };
        } else if (daysUntilExpiry <= 90) {
            return { status: 'warning', color: 'text-yellow-600', text: `Expires in ${daysUntilExpiry} days` };
        } else {
            return { status: 'valid', color: 'text-green-600', text: 'Valid' };
        }
    };

    const getRatingText = (ratingFilter) => {
        const map = {
            'excellent': 'Excellent (4.5+)',
            'good': 'Good (3.5-4.4)',
            'average': 'Average (2.5-3.4)',
            'poor': 'Poor (0-2.4)'
        };
        return map[ratingFilter] || ratingFilter;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Training Providers
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Manage training providers and their performance
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Link
                            href={route('training-providers.create')}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <PlusIcon className="w-4 h-4 mr-2" />
                            Add Provider
                        </Link>
                        <button className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                            Export Data
                        </button>
                    </div>
                </div>
            }
        >
            <Head title="Training Providers" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <BuildingOfficeIcon className="h-8 w-8 text-blue-600" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Total Providers
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {stats.total}
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
                                            Active Providers
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {stats.active}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <ShieldCheckIcon className="h-8 w-8 text-purple-600" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Accredited
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {stats.with_accreditation}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <ExclamationTriangleIcon className="h-8 w-8 text-orange-600" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">
                                            Expiring Soon
                                        </dt>
                                        <dd className="text-lg font-medium text-gray-900">
                                            {stats.accreditation_expiring}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters and Search */}
                    <div className="bg-white shadow rounded-lg mb-8">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <AdjustmentsHorizontalIcon className="w-5 h-5 mr-2" />
                                    Filters & Search
                                </h3>
                                {(searchTerm || selectedStatus !== '' || selectedRating || selectedAccreditation) && (
                                    <button
                                        onClick={clearFilters}
                                        className="text-sm text-gray-500 hover:text-gray-700"
                                    >
                                        Clear All Filters
                                    </button>
                                )}
                            </div>
                        </div>

                        <form onSubmit={handleSearch} className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                                {/* Search */}
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Search Providers
                                    </label>
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                        <input
                                            type="text"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            placeholder="Search by name, contact, email..."
                                            className="pl-10 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        />
                                    </div>
                                </div>

                                {/* Status Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Status
                                    </label>
                                    <select
                                        value={selectedStatus}
                                        onChange={(e) => setSelectedStatus(e.target.value)}
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                    >
                                        <option value="">All Status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>

                                {/* Rating Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Rating
                                    </label>
                                    <select
                                        value={selectedRating}
                                        onChange={(e) => setSelectedRating(e.target.value)}
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                    >
                                        <option value="">All Ratings</option>
                                        {filterOptions.rating_ranges?.map(rating => (
                                            <option key={rating} value={rating}>
                                                {getRatingText(rating)}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Accreditation Filter */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Accreditation
                                    </label>
                                    <select
                                        value={selectedAccreditation}
                                        onChange={(e) => setSelectedAccreditation(e.target.value)}
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                    >
                                        <option value="">All</option>
                                        <option value="valid">Valid</option>
                                        <option value="expiring">Expiring (90 days)</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                            </div>

                            <div className="mt-4 flex justify-end">
                                <button
                                    type="submit"
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <MagnifyingGlassIcon className="w-4 h-4 mr-2" />
                                    Search
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Providers Table */}
                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Provider
                                    </th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Contact
                                    </th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Performance
                                    </th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Accreditation
                                    </th>
                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {providers.data?.map((provider) => {
                                    const accreditationStatus = getAccreditationStatus(provider);

                                    return (
                                        <tr key={provider.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center">
                                                    <div className="flex-shrink-0">
                                                        <div className="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                            <BuildingOfficeIcon className="h-5 w-5 text-gray-600" />
                                                        </div>
                                                    </div>
                                                    <div className="ml-4">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {provider.name}
                                                        </div>
                                                        {provider.code && (
                                                            <div className="text-sm text-gray-500">
                                                                Code: {provider.code}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div className="space-y-1">
                                                    {provider.contact_person && (
                                                        <div className="flex items-center">
                                                            <span className="text-gray-600">{provider.contact_person}</span>
                                                        </div>
                                                    )}
                                                    {provider.phone && (
                                                        <div className="flex items-center">
                                                            <PhoneIcon className="h-3 w-3 text-gray-400 mr-1" />
                                                            <span className="text-gray-600">{provider.phone}</span>
                                                        </div>
                                                    )}
                                                    {provider.email && (
                                                        <div className="flex items-center">
                                                            <EnvelopeIcon className="h-3 w-3 text-gray-400 mr-1" />
                                                            <span className="text-gray-600">{provider.email}</span>
                                                        </div>
                                                    )}
                                                    {provider.website && (
                                                        <div className="flex items-center">
                                                            <GlobeAltIcon className="h-3 w-3 text-gray-400 mr-1" />
                                                            <a
                                                                href={provider.website}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="text-green-600 hover:text-green-900"
                                                            >
                                                                Website
                                                            </a>
                                                        </div>
                                                    )}
                                                </div>
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="space-y-1">
                                                    {provider.rating && (
                                                        <div className="flex items-center">
                                                            <div className="flex mr-2">
                                                                {getRatingStars(provider.rating)}
                                                            </div>
                                                            <span className="text-sm text-gray-600">
                                                                ({provider.rating}/5)
                                                            </span>
                                                        </div>
                                                    )}
                                                    <div className="text-sm text-gray-500">
                                                        {provider.completed_trainings || 0} trainings completed
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {provider.recent_trainings || 0} recent (6mo)
                                                    </div>
                                                </div>
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="space-y-1">
                                                    {provider.accreditation_number && (
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {provider.accreditation_number}
                                                        </div>
                                                    )}
                                                    <div className={`text-sm ${accreditationStatus.color}`}>
                                                        {accreditationStatus.text}
                                                    </div>
                                                </div>
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                    provider.is_active
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {provider.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div className="flex items-center justify-end space-x-2">
                                                    <Link
                                                        href={route('training-providers.show', provider.id)}
                                                        className="text-green-600 hover:text-green-900"
                                                        title="View"
                                                    >
                                                        <EyeIcon className="w-4 h-4" />
                                                    </Link>
                                                    <Link
                                                        href={route('training-providers.edit', provider.id)}
                                                        className="text-blue-600 hover:text-blue-900"
                                                        title="Edit"
                                                    >
                                                        <PencilIcon className="w-4 h-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => toggleStatus(provider)}
                                                        className={`${provider.is_active ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900'}`}
                                                        title={provider.is_active ? 'Deactivate' : 'Activate'}
                                                    >
                                                        {provider.is_active ? <XCircleIcon className="w-4 h-4" /> : <CheckCircleIcon className="w-4 h-4" />}
                                                    </button>
                                                    <button
                                                        onClick={() => deleteProvider(provider)}
                                                        className="text-red-600 hover:text-red-900"
                                                        title="Delete"
                                                    >
                                                        <TrashIcon className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {providers.links && (
                            <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div className="flex-1 flex justify-between sm:hidden">
                                    {providers.links.prev && (
                                        <Link
                                            href={providers.links.prev}
                                            className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Previous
                                        </Link>
                                    )}
                                    {providers.links.next && (
                                        <Link
                                            href={providers.links.next}
                                            className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Next
                                        </Link>
                                    )}
                                </div>
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Showing <span className="font-medium">{providers.from || 0}</span> to{' '}
                                            <span className="font-medium">{providers.to || 0}</span> of{' '}
                                            <span className="font-medium">{providers.total || 0}</span> results
                                        </p>
                                    </div>
                                    <div>
                                        <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            {providers.links?.map((link, index) => {
                                                if (link.url === null) return null;

                                                return (
                                                    <Link
                                                        key={index}
                                                        href={link.url}
                                                        className={`relative inline-flex items-center px-2 py-2 border text-sm font-medium ${
                                                            link.active
                                                                ? 'z-10 bg-green-50 border-green-500 text-green-600'
                                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                        } ${index === 0 ? 'rounded-l-md' : ''} ${index === providers.links.length - 1 ? 'rounded-r-md' : ''}`}
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                );
                                            })}
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Empty State */}
                    {providers.data?.length === 0 && (
                        <div className="bg-white shadow rounded-lg p-12 text-center">
                            <BuildingOfficeIcon className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-2 text-sm font-medium text-gray-900">No training providers found</h3>
                            <p className="mt-1 text-sm text-gray-500">
                                Get started by creating a new training provider.
                            </p>
                            <div className="mt-6">
                                <Link
                                    href={route('training-providers.create')}
                                    className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <PlusIcon className="w-4 h-4 mr-2" />
                                    Add Provider
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
