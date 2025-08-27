import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    MagnifyingGlassIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    EyeIcon,
    ShieldCheckIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, certificates, filters, filterOptions, stats }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    const statusColors = {
        active: 'bg-green-100 text-green-800',
        expiring_soon: 'bg-yellow-100 text-yellow-800',
        expired: 'bg-red-100 text-red-800',
        revoked: 'bg-red-100 text-red-800',
        suspended: 'bg-gray-100 text-gray-800'
    };

    const handleSearch = () => {
        router.get(route('certificates.index'), {
            search: searchTerm,
            status: selectedStatus
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const formatDate = (date) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Certificates
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Manage and verify digital certificates
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Certificates" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-blue-100 rounded-lg">
                                    <DocumentTextIcon className="w-6 h-6 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Certificates</p>
                                    <p className="text-2xl font-semibold text-gray-900">{stats?.total || 0}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-green-100 rounded-lg">
                                    <CheckCircleIcon className="w-6 h-6 text-green-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Active</p>
                                    <p className="text-2xl font-semibold text-gray-900">{stats?.active || 0}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-yellow-100 rounded-lg">
                                    <ExclamationTriangleIcon className="w-6 h-6 text-yellow-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Expiring Soon</p>
                                    <p className="text-2xl font-semibold text-gray-900">{stats?.expiring_soon || 0}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-blue-100 rounded-lg">
                                    <ShieldCheckIcon className="w-6 h-6 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Verified</p>
                                    <p className="text-2xl font-semibold text-gray-900">{stats?.verified || 0}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search and Filters */}
                    <div className="bg-white rounded-lg shadow p-6">
                        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                            <div className="flex-1 lg:max-w-lg">
                                <div className="relative">
                                    <input
                                        type="text"
                                        placeholder="Search certificates..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                        className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md"
                                    />
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center">
                                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center space-x-3">
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => setSelectedStatus(e.target.value)}
                                    className="border-gray-300 rounded-md"
                                >
                                    <option value="">All Status</option>
                                    {filterOptions?.statuses?.map(status => (
                                        <option key={status} value={status}>
                                            {status.replace('_', ' ').toUpperCase()}
                                        </option>
                                    ))}
                                </select>
                                <button
                                    onClick={handleSearch}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                >
                                    Search
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Certificates Table */}
                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Certificate Number
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Employee
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Training Type
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Issue Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Expiry Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Verified
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {certificates?.data?.length > 0 ? (
                                        certificates.data.map((certificate) => (
                                            <tr key={certificate.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-mono text-gray-900">
                                                        {certificate.certificate_number}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {certificate.verification_code}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {certificate.employee?.name}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {certificate.employee?.employee_id}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm text-gray-900">
                                                        {certificate.training_type?.name}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {formatDate(certificate.issue_date)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {formatDate(certificate.expiry_date)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColors[certificate.status] || 'bg-gray-100 text-gray-800'}`}>
                                                        {certificate.status?.replace('_', ' ').toUpperCase()}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {certificate.is_verified ? (
                                                        <CheckCircleIcon className="w-5 h-5 text-green-500" />
                                                    ) : (
                                                        <XCircleIcon className="w-5 h-5 text-gray-300" />
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <Link
                                                        href={route('certificates.show', certificate.id)}
                                                        className="text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        <EyeIcon className="w-4 h-4" />
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="8" className="px-6 py-12 text-center text-gray-500">
                                                <DocumentTextIcon className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                                <p>No certificates found.</p>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
