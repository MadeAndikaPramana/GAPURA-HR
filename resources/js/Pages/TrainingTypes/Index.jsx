// resources/js/Pages/TrainingTypes/Index.jsx - Cleaned Index

import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function TrainingTypesIndex({
    auth,
    certificateTypes,
    filters = {},
    stats = {}
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    // Apply filters
    const applyFilters = () => {
        router.get(route('training-types.index'), {
            search: search,
            status: statusFilter
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    // Clear filters
    const clearFilters = () => {
        setSearch('');
        setStatusFilter('');
        router.get(route('training-types.index'));
    };

    // Handle delete
    const handleDelete = (type) => {
        if (confirm(`Are you sure you want to delete "${type.name}"?`)) {
            router.delete(route('training-types.destroy', type.id));
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Training Types" />

            <div className="min-h-screen bg-gray-50 py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Header */}
                    <div className="bg-white rounded-lg shadow-sm border p-6 mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900">Training Types</h1>
                                <p className="text-gray-600 mt-1">
                                    Manage certificate types and see who has each certification
                                </p>
                            </div>

                            <Link
                                href={route('training-types.create')}
                                className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                            >
                                Add Training Type
                            </Link>
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div className="bg-white p-4 rounded-lg shadow-sm border">
                            <div className="text-2xl font-bold text-gray-900">{stats.total_types || 0}</div>
                            <div className="text-sm text-gray-500">Total Types</div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow-sm border">
                            <div className="text-2xl font-bold text-green-600">{stats.active_types || 0}</div>
                            <div className="text-sm text-gray-500">Active</div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow-sm border">
                            <div className="text-2xl font-bold text-blue-600">{stats.recurrent_types || 0}</div>
                            <div className="text-sm text-gray-500">Recurrent</div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow-sm border">
                            <div className="text-2xl font-bold text-purple-600">{stats.types_with_certificates || 0}</div>
                            <div className="text-sm text-gray-500">With Certificates</div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow-sm border p-4 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">

                            {/* Search */}
                            <div className="md:col-span-2">
                                <input
                                    type="text"
                                    placeholder="Search training types..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && applyFilters()}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            {/* Status Filter */}
                            <div>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            {/* Filter Actions */}
                            <div className="flex space-x-2">
                                <button
                                    onClick={applyFilters}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                                >
                                    Apply
                                </button>
                                <button
                                    onClick={clearFilters}
                                    className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Training Types List */}
                    <div className="bg-white rounded-lg shadow-sm border">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h2 className="text-lg font-semibold text-gray-900">
                                Training Types ({certificateTypes?.data?.length || 0})
                            </h2>
                        </div>

                        {/* Table */}
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Code
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Validity
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Certificates
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Employees
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {certificateTypes?.data?.length > 0 ? (
                                        certificateTypes.data.map((type) => (
                                            <tr key={type.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4">
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {type.name}
                                                        </div>
                                                        {type.description && (
                                                            <div className="text-xs text-gray-400 mt-1 max-w-xs">
                                                                {type.description}
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="text-sm text-gray-900">
                                                        {type.code || '-'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="text-sm text-gray-900">
                                                        {type.validity_months ? `${type.validity_months} months` : 'No expiration'}
                                                    </div>
                                                    {type.warning_days && (
                                                        <div className="text-xs text-gray-500">
                                                            Warning: {type.warning_days} days
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex flex-col space-y-1">
                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                            type.is_active
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-gray-100 text-gray-800'
                                                        }`}>
                                                            {type.is_active ? 'Active' : 'Inactive'}
                                                        </span>
                                                        {type.is_recurrent && (
                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                Recurrent
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="text-sm text-gray-900">
                                                        <div>Total: {type.container_stats?.total_certificates || 0}</div>
                                                        <div className="text-xs text-gray-500 space-x-2">
                                                            <span className="text-green-600">
                                                                {type.container_stats?.active_certificates || 0} Active
                                                            </span>
                                                            {(type.container_stats?.expired_certificates || 0) > 0 && (
                                                                <span className="text-red-600">
                                                                    {type.container_stats.expired_certificates} Expired
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {type.container_stats?.unique_employees || 0}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <div className="flex justify-end space-x-2">
                                                        {/* View Container */}
                                                        <Link
                                                            href={route('training-types.container', type.id)}
                                                            className="text-blue-600 hover:text-blue-900 text-sm font-medium"
                                                        >
                                                            View Container
                                                        </Link>

                                                        {/* Edit */}
                                                        <Link
                                                            href={route('training-types.edit', type.id)}
                                                            className="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                                        >
                                                            Edit
                                                        </Link>

                                                        {/* Delete (only if no certificates) */}
                                                        {(type.container_stats?.total_certificates || 0) === 0 && (
                                                            <button
                                                                onClick={() => handleDelete(type)}
                                                                className="text-red-600 hover:text-red-900 text-sm font-medium"
                                                            >
                                                                Delete
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="7" className="px-6 py-12 text-center">
                                                <div className="text-gray-500">
                                                    <div className="text-lg font-medium">No training types found</div>
                                                    <div className="text-sm mt-1">
                                                        {(search || statusFilter) ? (
                                                            'Try adjusting your filters.'
                                                        ) : (
                                                            <>
                                                                Get started by{' '}
                                                                <Link
                                                                    href={route('training-types.create')}
                                                                    className="text-blue-600 hover:text-blue-800 font-medium"
                                                                >
                                                                    creating your first training type
                                                                </Link>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Simple Pagination */}
                        {certificateTypes?.links && certificateTypes.links.length > 3 && (
                            <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex-1 flex justify-between sm:hidden">
                                        {certificateTypes.links[0].url ? (
                                            <Link
                                                href={certificateTypes.links[0].url}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Previous
                                            </Link>
                                        ) : (
                                            <span className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-white">
                                                Previous
                                            </span>
                                        )}
                                        {certificateTypes.links[certificateTypes.links.length - 1].url ? (
                                            <Link
                                                href={certificateTypes.links[certificateTypes.links.length - 1].url}
                                                className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Next
                                            </Link>
                                        ) : (
                                            <span className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-white">
                                                Next
                                            </span>
                                        )}
                                    </div>
                                    <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-gray-700">
                                                Showing page {certificateTypes.links.find(l => l.active)?.label || 1}
                                            </p>
                                        </div>
                                        <div>
                                            <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                                {certificateTypes.links.map((link, index) => {
                                                    if (index === 0) {
                                                        return link.url ? (
                                                            <Link
                                                                key={index}
                                                                href={link.url}
                                                                className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                                            >
                                                                Previous
                                                            </Link>
                                                        ) : (
                                                            <span
                                                                key={index}
                                                                className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-300"
                                                            >
                                                                Previous
                                                            </span>
                                                        );
                                                    }

                                                    if (index === certificateTypes.links.length - 1) {
                                                        return link.url ? (
                                                            <Link
                                                                key={index}
                                                                href={link.url}
                                                                className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                                            >
                                                                Next
                                                            </Link>
                                                        ) : (
                                                            <span
                                                                key={index}
                                                                className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-300"
                                                            >
                                                                Next
                                                            </span>
                                                        );
                                                    }

                                                    return link.url ? (
                                                        <Link
                                                            key={index}
                                                            href={link.url}
                                                            className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                                link.active
                                                                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                            }`}
                                                        >
                                                            {link.label}
                                                        </Link>
                                                    ) : (
                                                        <span
                                                            key={index}
                                                            className="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                                        >
                                                            {link.label}
                                                        </span>
                                                    );
                                                })}
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
