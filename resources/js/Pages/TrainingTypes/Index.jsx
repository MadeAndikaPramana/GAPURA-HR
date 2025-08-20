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
    XCircleIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, trainingTypes, filters, categories, stats }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('training-types.index'), {
            search: searchTerm,
            category: selectedCategory,
            status: selectedStatus
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedCategory('');
        setSelectedStatus('');
        router.get(route('training-types.index'));
    };

    const toggleStatus = (trainingType) => {
        if (confirm(`Are you sure you want to ${trainingType.is_active ? 'deactivate' : 'activate'} this training type?`)) {
            router.post(route('training-types.toggle-status', trainingType.id));
        }
    };

    const deleteTrainingType = (trainingType) => {
        if (confirm('Are you sure you want to delete this training type? This action cannot be undone.')) {
            router.delete(route('training-types.destroy', trainingType.id));
        }
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

    const getStatusColor = (isActive) => {
        return isActive
            ? 'bg-green-100 text-green-800'
            : 'bg-red-100 text-red-800';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Training Types Management
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Master jenis pelatihan sistem training GAPURA
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Link
                            href={route('training-types.create')}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <PlusIcon className="w-4 h-4 mr-2" />
                            Tambah Training Type
                        </Link>
                        <Link
                            href={route('training-types.export')}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                            Export Data
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Training Types" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-blue-100 text-blue-600">
                                    <AdjustmentsHorizontalIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Training Types</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.total}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-green-100 text-green-600">
                                    <CheckCircleIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Active Types</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.active}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-purple-100 text-purple-600">
                                    <EyeIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Categories</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.by_category.length}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-orange-100 text-orange-600">
                                    <XCircleIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Inactive Types</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.total - stats.active}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white shadow rounded-lg p-6 mb-6">
                        <form onSubmit={handleSearch} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-1">
                                        Search
                                    </label>
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                        <input
                                            type="text"
                                            id="search"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            placeholder="Cari nama, kode, atau kategori..."
                                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label htmlFor="category" className="block text-sm font-medium text-gray-700 mb-1">
                                        Category
                                    </label>
                                    <select
                                        id="category"
                                        value={selectedCategory}
                                        onChange={(e) => setSelectedCategory(e.target.value)}
                                        className="w-full py-2 px-3 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">All Categories</option>
                                        {categories.map(category => (
                                            <option key={category} value={category}>
                                                {category.charAt(0).toUpperCase() + category.slice(1)}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-1">
                                        Status
                                    </label>
                                    <select
                                        id="status"
                                        value={selectedStatus}
                                        onChange={(e) => setSelectedStatus(e.target.value)}
                                        className="w-full py-2 px-3 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <div className="flex items-end space-x-2">
                                    <button
                                        type="submit"
                                        className="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                    >
                                        Filter
                                    </button>
                                    <button
                                        type="button"
                                        onClick={clearFilters}
                                        className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {/* Training Types Table */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Training Type
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Category
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Validity
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Statistics
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {trainingTypes.data.map((trainingType) => (
                                    <tr key={trainingType.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {trainingType.name}
                                                </div>
                                                {trainingType.code && (
                                                    <div className="text-sm text-gray-500">
                                                        Code: {trainingType.code}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getCategoryColor(trainingType.category)}`}>
                                                {trainingType.category.charAt(0).toUpperCase() + trainingType.category.slice(1)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {trainingType.validity_months} months
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm text-gray-900">
                                                <div>Total: {trainingType.statistics?.total_certificates || 0}</div>
                                                <div className="text-xs text-gray-500">
                                                    Active: {trainingType.statistics?.active_count || 0} |
                                                    Expiring: {trainingType.statistics?.expiring_count || 0} |
                                                    Expired: {trainingType.statistics?.expired_count || 0}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(trainingType.is_active)}`}>
                                                {trainingType.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div className="flex items-center space-x-2">
                                                <Link
                                                    href={route('training-types.show', trainingType.id)}
                                                    className="text-green-600 hover:text-green-900"
                                                    title="View"
                                                >
                                                    <EyeIcon className="w-4 h-4" />
                                                </Link>
                                                <Link
                                                    href={route('training-types.edit', trainingType.id)}
                                                    className="text-blue-600 hover:text-blue-900"
                                                    title="Edit"
                                                >
                                                    <PencilIcon className="w-4 h-4" />
                                                </Link>
                                                <button
                                                    onClick={() => toggleStatus(trainingType)}
                                                    className={`${trainingType.is_active ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900'}`}
                                                    title={trainingType.is_active ? 'Deactivate' : 'Activate'}
                                                >
                                                    {trainingType.is_active ? <XCircleIcon className="w-4 h-4" /> : <CheckCircleIcon className="w-4 h-4" />}
                                                </button>
                                                <button
                                                    onClick={() => deleteTrainingType(trainingType)}
                                                    className="text-red-600 hover:text-red-900"
                                                    title="Delete"
                                                >
                                                    <TrashIcon className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {trainingTypes.links && (
                            <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div className="flex-1 flex justify-between sm:hidden">
                                    {trainingTypes.links.prev && (
                                        <Link
                                            href={trainingTypes.links.prev}
                                            className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Previous
                                        </Link>
                                    )}
                                    {trainingTypes.links.next && (
                                        <Link
                                            href={trainingTypes.links.next}
                                            className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Next
                                        </Link>
                                    )}
                                </div>
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Showing <span className="font-medium">{trainingTypes.from}</span> to{' '}
                                            <span className="font-medium">{trainingTypes.to}</span> of{' '}
                                            <span className="font-medium">{trainingTypes.total}</span> results
                                        </p>
                                    </div>
                                    {/* Pagination links would go here */}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Empty State */}
                    {trainingTypes.data.length === 0 && (
                        <div className="bg-white shadow rounded-lg p-6 text-center">
                            <AdjustmentsHorizontalIcon className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-2 text-sm font-medium text-gray-900">No training types found</h3>
                            <p className="mt-1 text-sm text-gray-500">
                                Get started by creating a new training type.
                            </p>
                            <div className="mt-6">
                                <Link
                                    href={route('training-types.create')}
                                    className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <PlusIcon className="-ml-1 mr-2 h-5 w-5" />
                                    New Training Type
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
