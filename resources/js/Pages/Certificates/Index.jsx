import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    DocumentArrowDownIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    ShieldCheckIcon,
    DocumentTextIcon,
    CalendarIcon,
    UserIcon,
    BuildingOfficeIcon
} from '@heroicons/react/24/outline';
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/react/20/solid';

export default function CertificateIndex({
    certificates,
    departments,
    trainingTypes,
    analytics,
    filters
}) {
    const [selectedCertificates, setSelectedCertificates] = useState([]);
    const [showFilters, setShowFilters] = useState(false);
    const [localFilters, setLocalFilters] = useState(filters);

    // Status color mapping
    const getStatusColor = (status) => {
        switch (status) {
            case 'active': return 'text-green-700 bg-green-50 ring-green-600/20';
            case 'expired': return 'text-red-700 bg-red-50 ring-red-600/20';
            case 'expiring_soon': return 'text-yellow-700 bg-yellow-50 ring-yellow-600/20';
            case 'expiring': return 'text-orange-700 bg-orange-50 ring-orange-600/20';
            default: return 'text-gray-700 bg-gray-50 ring-gray-600/20';
        }
    };

    const getStatusIcon = (status) => {
        switch (status) {
            case 'active': return <CheckCircleIcon className="h-4 w-4" />;
            case 'expired': return <XCircleIcon className="h-4 w-4" />;
            case 'expiring_soon': return <ExclamationTriangleIcon className="h-4 w-4" />;
            case 'expiring': return <ClockIcon className="h-4 w-4" />;
            default: return <DocumentTextIcon className="h-4 w-4" />;
        }
    };

    const handleSearch = (e) => {
        const search = e.target.value;
        setLocalFilters(prev => ({ ...prev, search }));

        // Debounced search
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(() => {
            router.get(route('certificates.index'), { ...localFilters, search }, {
                preserveState: true,
                preserveScroll: true
            });
        }, 300);
    };

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);

        router.get(route('certificates.index'), newFilters, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const handleSort = (field) => {
        const direction = localFilters.sort === field && localFilters.direction === 'asc' ? 'desc' : 'asc';
        handleFilterChange('sort', field);
        handleFilterChange('direction', direction);
    };

    const handleSelectAll = (e) => {
        if (e.target.checked) {
            setSelectedCertificates(certificates.data.map(cert => cert.id));
        } else {
            setSelectedCertificates([]);
        }
    };

    const handleSelectCertificate = (certificateId) => {
        setSelectedCertificates(prev =>
            prev.includes(certificateId)
                ? prev.filter(id => id !== certificateId)
                : [...prev, certificateId]
        );
    };

    const handleBulkAction = (action) => {
        if (selectedCertificates.length === 0) {
            alert('Please select certificates first');
            return;
        }

        if (action === 'delete' && !confirm('Are you sure you want to delete selected certificates?')) {
            return;
        }

        router.post(route('certificates.bulk-action'), {
            action,
            certificate_ids: selectedCertificates
        }, {
            onSuccess: () => setSelectedCertificates([])
        });
    };

    const SortButton = ({ field, children }) => (
        <button
            onClick={() => handleSort(field)}
            className="group inline-flex items-center space-x-1 text-left hover:text-gray-900"
        >
            <span>{children}</span>
            {localFilters.sort === field ? (
                localFilters.direction === 'asc' ?
                    <ChevronUpIcon className="h-4 w-4" /> :
                    <ChevronDownIcon className="h-4 w-4" />
            ) : (
                <ChevronUpIcon className="h-4 w-4 opacity-0 group-hover:opacity-50" />
            )}
        </button>
    );

    return (
        <AuthenticatedLayout>
            <Head title="Certificate Management" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="md:flex md:items-center md:justify-between mb-6">
                        <div className="min-w-0 flex-1">
                            <h2 className="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                                Certificate Management
                            </h2>
                            <p className="mt-1 text-sm text-gray-500">
                                Manage and track all employee certificates
                            </p>
                        </div>
                        <div className="mt-4 flex md:ml-4 md:mt-0">
                            <Link
                                href={route('certificates.create')}
                                className="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
                            >
                                <DocumentTextIcon className="-ml-0.5 mr-1.5 h-5 w-5" />
                                New Certificate
                            </Link>
                        </div>
                    </div>

                    {/* Analytics Cards */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <DocumentTextIcon className="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Certificates
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {analytics.total.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <CheckCircleIcon className="h-6 w-6 text-green-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Active Certificates
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {analytics.active.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ExclamationTriangleIcon className="h-6 w-6 text-yellow-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Expiring Soon
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {analytics.expiring_soon.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ShieldCheckIcon className="h-6 w-6 text-blue-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Compliance Rate
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {analytics.compliance_rate}%
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search and Filters */}
                    <div className="bg-white shadow rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex flex-col sm:flex-row gap-4">
                                {/* Search */}
                                <div className="flex-1">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            type="text"
                                            placeholder="Search certificates..."
                                            defaultValue={localFilters.search || ''}
                                            onChange={handleSearch}
                                            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                </div>

                                {/* Filter Toggle */}
                                <button
                                    onClick={() => setShowFilters(!showFilters)}
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <FunnelIcon className="h-5 w-5 mr-2" />
                                    Filters
                                </button>

                                {/* Export */}
                                <button
                                    onClick={() => handleBulkAction('export')}
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <DocumentArrowDownIcon className="h-5 w-5 mr-2" />
                                    Export
                                </button>
                            </div>

                            {/* Expandable Filters */}
                            {showFilters && (
                                <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Status</label>
                                        <select
                                            value={localFilters.status || ''}
                                            onChange={(e) => handleFilterChange('status', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        >
                                            <option value="">All Statuses</option>
                                            <option value="active">Active</option>
                                            <option value="expired">Expired</option>
                                            <option value="expiring_soon">Expiring Soon</option>
                                            <option value="expiring">Expiring</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Department</label>
                                        <select
                                            value={localFilters.department || ''}
                                            onChange={(e) => handleFilterChange('department', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        >
                                            <option value="">All Departments</option>
                                            {departments.map((dept) => (
                                                <option key={dept.id} value={dept.id}>
                                                    {dept.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Training Type</label>
                                        <select
                                            value={localFilters.training_type || ''}
                                            onChange={(e) => handleFilterChange('training_type', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        >
                                            <option value="">All Training Types</option>
                                            {trainingTypes.map((type) => (
                                                <option key={type.id} value={type.id}>
                                                    {type.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Verified</label>
                                        <select
                                            value={localFilters.verified || ''}
                                            onChange={(e) => handleFilterChange('verified', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        >
                                            <option value="">All</option>
                                            <option value="1">Verified</option>
                                            <option value="0">Unverified</option>
                                        </select>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Bulk Actions */}
                    {selectedCertificates.length > 0 && (
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center">
                                    <span className="text-sm font-medium text-blue-800">
                                        {selectedCertificates.length} certificate(s) selected
                                    </span>
                                </div>
                                <div className="flex space-x-2">
                                    <button
                                        onClick={() => handleBulkAction('verify')}
                                        className="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200"
                                    >
                                        Verify
                                    </button>
                                    <button
                                        onClick={() => handleBulkAction('export')}
                                        className="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200"
                                    >
                                        Export
                                    </button>
                                    <button
                                        onClick={() => handleBulkAction('delete')}
                                        className="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Certificate Table */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-md">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left">
                                            <input
                                                type="checkbox"
                                                checked={selectedCertificates.length === certificates.data.length}
                                                onChange={handleSelectAll}
                                                className="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                            />
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <SortButton field="certificate_number">Certificate</SortButton>
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Employee
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Training
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <SortButton field="issue_date">Issue Date</SortButton>
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <SortButton field="expiry_date">Expiry Date</SortButton>
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Verified
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {certificates.data.map((certificate) => (
                                        <tr key={certificate.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedCertificates.includes(certificate.id)}
                                                    onChange={() => handleSelectCertificate(certificate.id)}
                                                    className="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                                />
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <DocumentTextIcon className="h-5 w-5 text-gray-400 mr-2" />
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {certificate.certificate_number}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {certificate.issued_by}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <UserIcon className="h-5 w-5 text-gray-400 mr-2" />
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {certificate.training_record.employee.name}
                                                        </div>
                                                        <div className="text-sm text-gray-500 flex items-center">
                                                            <BuildingOfficeIcon className="h-4 w-4 mr-1" />
                                                            {certificate.training_record.employee.department.name}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">
                                                    {certificate.training_record.training_type.name}
                                                </div>
                                                {certificate.training_record.training_type.category && (
                                                    <span
                                                        className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                        style={{
                                                            backgroundColor: certificate.training_record.training_type.category.color_code + '20',
                                                            color: certificate.training_record.training_type.category.color_code
                                                        }}
                                                    >
                                                        {certificate.training_record.training_type.category.name}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div className="flex items-center">
                                                    <CalendarIcon className="h-4 w-4 text-gray-400 mr-1" />
                                                    {new Date(certificate.issue_date).toLocaleDateString()}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {certificate.expiry_date ? (
                                                    <div className="flex items-center">
                                                        <CalendarIcon className="h-4 w-4 text-gray-400 mr-1" />
                                                        {new Date(certificate.expiry_date).toLocaleDateString()}
                                                        {certificate.days_until_expiry !== null && (
                                                            <span className={`ml-2 text-xs ${
                                                                certificate.days_until_expiry < 0 ? 'text-red-600' :
                                                                certificate.days_until_expiry <= 30 ? 'text-yellow-600' :
                                                                'text-gray-600'
                                                            }`}>
                                                                ({certificate.days_until_expiry < 0 ?
                                                                    `${Math.abs(certificate.days_until_expiry)} days ago` :
                                                                    `${certificate.days_until_expiry} days left`
                                                                })
                                                            </span>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-500">No expiry</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset ${getStatusColor(certificate.status)}`}>
                                                    {getStatusIcon(certificate.status)}
                                                    <span className="ml-1 capitalize">{certificate.status.replace('_', ' ')}</span>
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {certificate.is_verified ? (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-green-700 bg-green-50 ring-1 ring-inset ring-green-600/20">
                                                        <CheckCircleIcon className="h-3 w-3 mr-1" />
                                                        Verified
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-gray-700 bg-gray-50 ring-1 ring-inset ring-gray-600/20">
                                                        <XCircleIcon className="h-3 w-3 mr-1" />
                                                        Unverified
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div className="flex items-center justify-end space-x-2">
                                                    <Link
                                                        href={route('certificates.show', certificate.id)}
                                                        className="text-green-600 hover:text-green-900"
                                                        title="View"
                                                    >
                                                        <EyeIcon className="h-4 w-4" />
                                                    </Link>
                                                    <Link
                                                        href={route('certificates.edit', certificate.id)}
                                                        className="text-blue-600 hover:text-blue-900"
                                                        title="Edit"
                                                    >
                                                        <PencilIcon className="h-4 w-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => {
                                                            if (confirm('Are you sure you want to delete this certificate?')) {
                                                                router.delete(route('certificates.destroy', certificate.id));
                                                            }
                                                        }}
                                                        className="text-red-600 hover:text-red-900"
                                                        title="Delete"
                                                    >
                                                        <TrashIcon className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {certificates.links && (
                            <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex-1 flex justify-between sm:hidden">
                                        {certificates.prev_page_url && (
                                            <Link
                                                href={certificates.prev_page_url}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Previous
                                            </Link>
                                        )}
                                        {certificates.next_page_url && (
                                            <Link
                                                href={certificates.next_page_url}
                                                className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Next
                                            </Link>
                                        )}
                                    </div>
                                    <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-gray-700">
                                                Showing <span className="font-medium">{certificates.from}</span> to{' '}
                                                <span className="font-medium">{certificates.to}</span> of{' '}
                                                <span className="font-medium">{certificates.total}</span> results
                                            </p>
                                        </div>
                                        {/* Pagination component would go here */}
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
