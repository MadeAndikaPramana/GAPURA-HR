import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    FunnelIcon,
    DocumentArrowDownIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    CheckCircleIcon,
    XMarkIcon
} from '@heroicons/react/24/outline';

export default function CertificateIndex({
    auth,
    certificates,
    filters,
    filterOptions,
    stats
}) {
    const [selectedCertificates, setSelectedCertificates] = useState([]);
    const [showFilters, setShowFilters] = useState(false);

    const { data, setData, get, processing } = useForm({
        search: filters.search || '',
        status: filters.status || '',
        department_id: filters.department_id || '',
        training_type_id: filters.training_type_id || '',
        training_provider_id: filters.training_provider_id || '',
        date_from: filters.date_from || '',
        date_to: filters.date_to || '',
        sort_by: filters.sort_by || 'issue_date',
        sort_order: filters.sort_order || 'desc',
        per_page: 15
    });

    const handleSearch = (e) => {
        e.preventDefault();
        get(route('certificates.index'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleFilterChange = (key, value) => {
        setData(key, value);
        const newFilters = { ...data, [key]: value };
        router.get(route('certificates.index'), newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        router.get(route('certificates.index'), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSort = (column) => {
        const newSortOrder = data.sort_by === column && data.sort_order === 'desc' ? 'asc' : 'desc';
        handleFilterChange('sort_order', newSortOrder);
        handleFilterChange('sort_by', column);
    };

    const toggleSelectAll = () => {
        if (selectedCertificates.length === certificates.data.length) {
            setSelectedCertificates([]);
        } else {
            setSelectedCertificates(certificates.data.map(cert => cert.id));
        }
    };

    const toggleSelectCertificate = (id) => {
        setSelectedCertificates(prev =>
            prev.includes(id)
                ? prev.filter(certId => certId !== id)
                : [...prev, id]
        );
    };

    const getStatusBadge = (certificate) => {
        const { status, expiry_date, days_until_expiry } = certificate;

        if (status === 'expired' || (expiry_date && days_until_expiry < 0)) {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <ExclamationTriangleIcon className="w-3 h-3 mr-1" />
                    Expired
                </span>
            );
        }

        if (status === 'issued' && days_until_expiry <= 30 && days_until_expiry >= 0) {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <ClockIcon className="w-3 h-3 mr-1" />
                    Expiring Soon
                </span>
            );
        }

        if (status === 'issued') {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <CheckCircleIcon className="w-3 h-3 mr-1" />
                    Active
                </span>
            );
        }

        const statusConfig = {
            draft: { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Draft' },
            revoked: { bg: 'bg-red-100', text: 'text-red-800', label: 'Revoked' },
            renewed: { bg: 'bg-blue-100', text: 'text-blue-800', label: 'Renewed' }
        };

        const config = statusConfig[status] || { bg: 'bg-gray-100', text: 'text-gray-800', label: status };

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.bg} ${config.text}`}>
                {config.label}
            </span>
        );
    };

    const getVerificationBadge = (verificationStatus) => {
        const config = {
            verified: { bg: 'bg-green-100', text: 'text-green-800', label: 'Verified', icon: CheckCircleIcon },
            pending: { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Pending', icon: ClockIcon },
            invalid: { bg: 'bg-red-100', text: 'text-red-800', label: 'Invalid', icon: XMarkIcon },
            under_review: { bg: 'bg-blue-100', text: 'text-blue-800', label: 'Under Review', icon: ClockIcon }
        };

        const statusConfig = config[verificationStatus] || config.pending;
        const Icon = statusConfig.icon;

        return (
            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${statusConfig.bg} ${statusConfig.text}`}>
                <Icon className="w-3 h-3 mr-1" />
                {statusConfig.label}
            </span>
        );
    };

    const formatDate = (date) => {
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
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Certificate Management
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Kelola sertifikat karyawan dan tracking compliance
                        </p>
                    </div>
                    <Link
                        href={route('certificates.create')}
                        className="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Add Certificate
                    </Link>
                </div>
            }
        >
            <Head title="Certificates" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ShieldCheckIcon className="h-8 w-8 text-blue-500" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Certificates
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats.total.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <CheckCircleIcon className="h-8 w-8 text-green-500" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Active
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats.active.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ClockIcon className="h-8 w-8 text-yellow-500" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Expiring Soon
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats.expiring_soon.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm rounded-lg border">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ExclamationTriangleIcon className="h-8 w-8 text-red-500" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Expired
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats.expired.toLocaleString()}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                                <div className="mt-2">
                                    <div className="text-sm text-gray-600">
                                        Compliance Rate: {stats.compliance_rate}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters and Search */}
                    <div className="bg-white shadow-sm rounded-lg border">
                        <div className="p-6">
                            <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                                {/* Search Form */}
                                <form onSubmit={handleSearch} className="flex-1 max-w-lg">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            type="text"
                                            value={data.search}
                                            onChange={(e) => setData('search', e.target.value)}
                                            placeholder="Search certificates, employees, training types..."
                                            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                </form>

                                {/* Action Buttons */}
                                <div className="flex items-center space-x-3">
                                    <button
                                        onClick={() => setShowFilters(!showFilters)}
                                        className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        <FunnelIcon className="h-4 w-4 mr-2" />
                                        Filters
                                        {Object.values(filters).some(v => v) && (
                                            <span className="ml-2 bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                Active
                                            </span>
                                        )}
                                    </button>

                                    <button className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <DocumentArrowDownIcon className="h-4 w-4 mr-2" />
                                        Export
                                    </button>
                                </div>
                            </div>

                            {/* Filter Panel */}
                            {showFilters && (
                                <div className="mt-4 pt-4 border-t border-gray-200">
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Status</label>
                                            <select
                                                value={data.status}
                                                onChange={(e) => handleFilterChange('status', e.target.value)}
                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            >
                                                <option value="">All Status</option>
                                                <option value="active">Active</option>
                                                <option value="expired">Expired</option>
                                                <option value="expiring">Expiring Soon</option>
                                                <option value="draft">Draft</option>
                                                <option value="revoked">Revoked</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Department</label>
                                            <select
                                                value={data.department_id}
                                                onChange={(e) => handleFilterChange('department_id', e.target.value)}
                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            >
                                                <option value="">All Departments</option>
                                                {filterOptions.departments.map(dept => (
                                                    <option key={dept.id} value={dept.id}>
                                                        {dept.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Training Type</label>
                                            <select
                                                value={data.training_type_id}
                                                onChange={(e) => handleFilterChange('training_type_id', e.target.value)}
                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            >
                                                <option value="">All Training Types</option>
                                                {filterOptions.trainingTypes.map(type => (
                                                    <option key={type.id} value={type.id}>
                                                        {type.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Provider</label>
                                            <select
                                                value={data.training_provider_id}
                                                onChange={(e) => handleFilterChange('training_provider_id', e.target.value)}
                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            >
                                                <option value="">All Providers</option>
                                                {filterOptions.trainingProviders.map(provider => (
                                                    <option key={provider.id} value={provider.id}>
                                                        {provider.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>

                                    <div className="mt-4 flex items-center justify-between">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">Date From</label>
                                                <input
                                                    type="date"
                                                    value={data.date_from}
                                                    onChange={(e) => handleFilterChange('date_from', e.target.value)}
                                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">Date To</label>
                                                <input
                                                    type="date"
                                                    value={data.date_to}
                                                    onChange={(e) => handleFilterChange('date_to', e.target.value)}
                                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                />
                                            </div>
                                        </div>

                                        <button
                                            onClick={clearFilters}
                                            className="text-sm text-gray-600 hover:text-gray-900 underline"
                                        >
                                            Clear Filters
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Certificates Table */}
                    <div className="bg-white shadow-sm rounded-lg border overflow-hidden">

                        {/* Bulk Actions */}
                        {selectedCertificates.length > 0 && (
                            <div className="bg-gray-50 px-6 py-3 border-b">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-gray-700">
                                        {selectedCertificates.length} certificates selected
                                    </span>
                                    <div className="flex space-x-2">
                                        <button className="text-sm bg-white border border-gray-300 rounded-md px-3 py-2 hover:bg-gray-50">
                                            Export Selected
                                        </button>
                                        <button className="text-sm bg-white border border-gray-300 rounded-md px-3 py-2 hover:bg-gray-50 text-red-600">
                                            Delete Selected
                                        </button>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th scope="col" className="relative px-6 py-3">
                                            <input
                                                type="checkbox"
                                                className="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                                checked={certificates.data.length > 0 && selectedCertificates.length === certificates.data.length}
                                                onChange={toggleSelectAll}
                                            />
                                        </th>

                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            onClick={() => handleSort('certificate_number')}
                                        >
                                            Certificate Number
                                        </th>

                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Employee
                                        </th>

                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Training Type
                                        </th>

                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            onClick={() => handleSort('issue_date')}
                                        >
                                            Issue Date
                                        </th>

                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            onClick={() => handleSort('expiry_date')}
                                        >
                                            Expiry Date
                                        </th>

                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>

                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Verification
                                        </th>

                                        <th scope="col" className="relative px-6 py-3">
                                            <span className="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {certificates.data.map((certificate) => (
                                        <tr key={certificate.id} className="hover:bg-gray-50">
                                            <td className="relative px-6 py-4">
                                                <input
                                                    type="checkbox"
                                                    className="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                                    checked={selectedCertificates.includes(certificate.id)}
                                                    onChange={() => toggleSelectCertificate(certificate.id)}
                                                />
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">
                                                    {certificate.certificate_number}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {certificate.certificate_type}
                                                </div>
                                            </td>

                                            <td className="px-6 py-4">
                                                <div className="text-sm font-medium text-gray-900">
                                                    {certificate.employee.name}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {certificate.employee.nip} • {certificate.employee.department.name}
                                                </div>
                                            </td>

                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-900">
                                                    {certificate.training_type.name}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {certificate.training_type.category}
                                                </div>
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {formatDate(certificate.issue_date)}
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {certificate.expiry_date ? formatDate(certificate.expiry_date) : 'No Expiry'}
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {getStatusBadge(certificate)}
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {getVerificationBadge(certificate.verification_status)}
                                            </td>

                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div className="flex items-center space-x-2">
                                                    <Link
                                                        href={route('certificates.show', certificate.id)}
                                                        className="text-green-600 hover:text-green-900"
                                                        title="View Details"
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
                        {certificates.links && certificates.links.length > 3 && (
                            <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex-1 flex justify-between sm:hidden">
                                        {certificates.links[0].url && (
                                            <Link
                                                href={certificates.links[0].url}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Previous
                                            </Link>
                                        )}
                                        {certificates.links[certificates.links.length - 1].url && (
                                            <Link
                                                href={certificates.links[certificates.links.length - 1].url}
                                                className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Next
                                            </Link>
                                        )}
                                    </div>
                                    <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-gray-700">
                                                Showing{' '}
                                                <span className="font-medium">{certificates.from || 0}</span>
                                                {' '}to{' '}
                                                <span className="font-medium">{certificates.to || 0}</span>
                                                {' '}of{' '}
                                                <span className="font-medium">{certificates.total}</span>
                                                {' '}results
                                            </p>
                                        </div>
                                        <div>
                                            <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                                {certificates.links.map((link, index) => (
                                                    <Link
                                                        key={index}
                                                        href={link.url || '#'}
                                                        className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                            link.active
                                                                ? 'z-10 bg-green-50 border-green-500 text-green-600'
                                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                        } ${
                                                            index === 0 ? 'rounded-l-md' : ''
                                                        } ${
                                                            index === certificates.links.length - 1 ? 'rounded-r-md' : ''
                                                        } ${
                                                            !link.url ? 'cursor-not-allowed opacity-50' : ''
                                                        }`}
                                                        preserveState
                                                        preserveScroll
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                ))}
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Empty State */}
                        {certificates.data.length === 0 && (
                            <div className="text-center py-12">
                                <ShieldCheckIcon className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No certificates found</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    {filters.search || Object.values(filters).some(v => v)
                                        ? 'Try adjusting your search or filters.'
                                        : 'Get started by creating a new certificate.'
                                    }
                                </p>
                                {!(filters.search || Object.values(filters).some(v => v)) && (
                                    <div className="mt-6">
                                        <Link
                                            href={route('certificates.create')}
                                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            <PlusIcon className="w-4 h-4 mr-2" />
                                            Create Certificate
                                        </Link>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
