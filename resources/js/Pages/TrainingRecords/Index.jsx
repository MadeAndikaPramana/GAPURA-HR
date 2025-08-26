import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    AdjustmentsHorizontalIcon,
    ChartBarIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    ChevronRightIcon,
    ChevronDownIcon,
    BuildingOfficeIcon,
    UserGroupIcon,
    DocumentTextIcon
} from '@heroicons/react/24/outline';

export default function Index({
    auth,
    employees,
    trainingTypes,
    departments,
    trainingProviders, // ✅ Added providers
    filters,
    stats,
    flash // ✅ Flash messages for delete success/error
}) {
    // Filter states
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [selectedTrainingType, setSelectedTrainingType] = useState(filters.training_type || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || '');
    const [selectedProvider, setSelectedProvider] = useState(filters.provider || ''); // ✅ Provider filter

    // UI states
    const [expandedEmployee, setExpandedEmployee] = useState(null);
    const [employeeCertificates, setEmployeeCertificates] = useState({});
    const [loadingCertificates, setLoadingCertificates] = useState(false);
    const [deletingCertificate, setDeletingCertificate] = useState(null); // ✅ Loading state for delete

    // Filter change handler
    const handleFilterChange = (key, value) => {
        const newFilters = {
            ...filters,
            [key]: value,
            page: 1 // Reset to first page when filtering
        };

        // Remove empty filters
        Object.keys(newFilters).forEach(k => {
            if (!newFilters[k]) delete newFilters[k];
        });

        router.get(route('training-records.index'), newFilters, {
            preserveState: true,
            preserveScroll: true
        });
    };

    // Search handler
    const handleSearch = () => {
        handleFilterChange('search', searchTerm);
    };

    // Clear filters
    const clearFilters = () => {
        setSearchTerm('');
        setSelectedStatus('');
        setSelectedTrainingType('');
        setSelectedDepartment('');
        setSelectedProvider(''); // ✅ Clear provider filter

        router.get(route('training-records.index'), {}, {
            preserveState: true
        });
    };

    // Load employee certificates
    const loadEmployeeCertificates = async (employeeId) => {
        if (employeeCertificates[employeeId]) {
            return; // Already loaded
        }

        setLoadingCertificates(true);
        try {
            const response = await fetch(`/training-records/employee/${employeeId}/certificates`);
            const data = await response.json();

            setEmployeeCertificates(prev => ({
                ...prev,
                [employeeId]: data.certificates
            }));
        } catch (error) {
            console.error('Failed to load certificates:', error);
        } finally {
            setLoadingCertificates(false);
        }
    };

    // Toggle employee details
    const toggleEmployeeDetails = (employeeId) => {
        if (expandedEmployee === employeeId) {
            setExpandedEmployee(null);
        } else {
            setExpandedEmployee(employeeId);
            loadEmployeeCertificates(employeeId);
        }
    };

    // ✅ Delete training record with confirmation
    const deleteTrainingRecord = (certificateId, certificateNumber, employeeId) => {
        if (confirm(`Are you sure you want to delete training record "${certificateNumber}"? This action cannot be undone.`)) {
            setDeletingCertificate(certificateId);

            router.delete(route('training-records.destroy', certificateId), {
                onSuccess: () => {
                    // Refresh the certificates for this employee
                    setEmployeeCertificates(prev => {
                        const updated = { ...prev };
                        if (updated[employeeId]) {
                            updated[employeeId] = updated[employeeId].filter(cert => cert.id !== certificateId);
                        }
                        return updated;
                    });
                    setDeletingCertificate(null);
                },
                onError: (errors) => {
                    console.error('Delete failed:', errors);
                    setDeletingCertificate(null);
                    alert('Failed to delete training record. Please try again.');
                },
                onFinish: () => {
                    setDeletingCertificate(null);
                }
            });
        }
    };

    // Get status badge color
    const getStatusColor = (status) => {
        switch (status) {
            case 'compliant': return 'bg-green-100 text-green-800';
            case 'expiring_soon': return 'bg-yellow-100 text-yellow-800';
            case 'expired': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    // Get status icon
    const getStatusIcon = (status) => {
        switch (status) {
            case 'compliant': return <CheckCircleIcon className="w-4 h-4" />;
            case 'expiring_soon': return <ClockIcon className="w-4 h-4" />;
            case 'expired': return <XCircleIcon className="w-4 h-4" />;
            default: return <ExclamationTriangleIcon className="w-4 h-4" />;
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Training Records Management
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Manage employee training records and certificates
                        </p>
                    </div>
                    <Link
                        href={route('training-records.create')}
                                                                className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition ease-in-out duration-150"
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Add Training Record
                    </Link>
                </div>
            }
        >
            <Head title="Training Records" />

            {/* Statistics Cards */}
            {stats && (
                <div className="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <UserGroupIcon className="h-8 w-8 text-blue-500" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">Total Employees</dt>
                                        <dd className="text-lg font-medium text-gray-900">{stats.total_employees}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <DocumentTextIcon className="h-8 w-8 text-green-500" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">Total Certificates</dt>
                                        <dd className="text-lg font-medium text-gray-900">{stats.total_certificates}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <CheckCircleIcon className="h-8 w-8 text-green-500" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">Active</dt>
                                        <dd className="text-lg font-medium text-gray-900">{stats.compliant_certificates}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <ClockIcon className="h-8 w-8 text-yellow-500" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">Expiring Soon</dt>
                                        <dd className="text-lg font-medium text-gray-900">{stats.expiring_certificates}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="p-5">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <XCircleIcon className="h-8 w-8 text-red-500" />
                                </div>
                                <div className="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt className="text-sm font-medium text-gray-500 truncate">Expired</dt>
                                        <dd className="text-lg font-medium text-gray-900">{stats.expired_certificates}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white shadow rounded-lg p-6 mb-6">
                        <div className="flex items-center justify-between mb-4">
                            <div className="flex items-center">
                                <AdjustmentsHorizontalIcon className="w-5 h-5 text-gray-500 mr-2" />
                                <h3 className="text-lg font-medium text-gray-900">Filters</h3>
                            </div>
                            <button
                                onClick={clearFilters}
                                className="text-sm text-gray-500 hover:text-gray-700"
                            >
                                Clear all filters
                            </button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-4">
                            {/* Search */}
                            <div className="lg:col-span-2">
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <input
                                        type="text"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        placeholder="Search employees or certificates..."
                                        onKeyPress={(e) => {
                                            if (e.key === 'Enter') {
                                                handleSearch();
                                            }
                                        }}
                                    />
                                </div>
                            </div>

                            {/* Status Filter */}
                            <div>
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => {
                                        setSelectedStatus(e.target.value);
                                        handleFilterChange('status', e.target.value);
                                    }}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Has Active Certificates</option>
                                    <option value="expiring_soon">Has Expiring Certificates</option>
                                    <option value="expired">Has Expired Certificates</option>
                                </select>
                            </div>

                            {/* ✅ Provider Filter (replacing All Employee filter) */}
                            <div>
                                <select
                                    value={selectedProvider}
                                    onChange={(e) => {
                                        setSelectedProvider(e.target.value);
                                        handleFilterChange('provider', e.target.value);
                                    }}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Providers</option>
                                    {trainingProviders && trainingProviders.map((provider) => (
                                        <option key={provider.id} value={provider.id}>
                                            {provider.code ? `${provider.code} - ${provider.name}` : provider.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Training Type Filter */}
                            <div>
                                <select
                                    value={selectedTrainingType}
                                    onChange={(e) => {
                                        setSelectedTrainingType(e.target.value);
                                        handleFilterChange('training_type', e.target.value);
                                    }}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Training Types</option>
                                    {trainingTypes && trainingTypes.map((type) => (
                                        <option key={type.id} value={type.id}>
                                            {type.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Department Filter */}
                            <div>
                                <select
                                    value={selectedDepartment}
                                    onChange={(e) => {
                                        setSelectedDepartment(e.target.value);
                                        handleFilterChange('department', e.target.value);
                                    }}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Departments</option>
                                    {departments && departments.map((dept) => (
                                        <option key={dept.id} value={dept.id}>
                                            {dept.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="flex justify-start">
                            <button
                                onClick={handleSearch}
                                className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                <MagnifyingGlassIcon className="w-4 h-4 mr-2" />
                                Search
                            </button>
                        </div>
                    </div>

                    {/* Employee Training Overview Table */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        {employees && employees.data && employees.data.length > 0 ? (
                            <div className="min-w-full divide-y divide-gray-200">
                                <div className="bg-gray-50 px-6 py-3">
                                    <div className="grid grid-cols-12 gap-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div className="col-span-3">Employee</div>
                                        <div className="col-span-2">Department</div>
                                        <div className="col-span-2 text-center">Total Certificates</div>
                                        <div className="col-span-2 text-center">Active</div>
                                        <div className="col-span-1 text-center">Expiring</div>
                                        <div className="col-span-1 text-center">Expired</div>
                                        <div className="col-span-1 text-center">Actions</div>
                                    </div>
                                </div>
                                <div className="bg-white divide-y divide-gray-200">
                                    {employees.data.map((employee) => (
                                        <div key={employee.id}>
                                            <div className="px-6 py-4 hover:bg-gray-50">
                                                <div className="grid grid-cols-12 gap-4 items-center">
                                                    {/* Employee Info */}
                                                    <div className="col-span-3">
                                                        <div className="flex items-center">
                                                            <div className="flex-shrink-0 h-10 w-10">
                                                                <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                                    <span className="text-sm font-medium text-green-800">
                                                                        {employee.name ? employee.name.charAt(0).toUpperCase() : 'N'}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div className="ml-4">
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {employee.name || 'Unknown'}
                                                                </div>
                                                                <div className="text-sm text-gray-500">
                                                                    {employee.employee_id || 'No ID'}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {/* Department */}
                                                    <div className="col-span-2">
                                                        <div className="flex items-center">
                                                            <BuildingOfficeIcon className="w-4 h-4 text-gray-400 mr-2" />
                                                            <span className="text-sm text-gray-900">
                                                                {employee.department?.name || 'No Department'}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    {/* Statistics */}
                                                    <div className="col-span-2 text-center">
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                            {employee.total_certificates_count || 0}
                                                        </span>
                                                    </div>

                                                    <div className="col-span-2 text-center">
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            {employee.active_certificates_count || 0}
                                                        </span>
                                                    </div>

                                                    <div className="col-span-1 text-center">
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            {employee.expiring_certificates_count || 0}
                                                        </span>
                                                    </div>

                                                    <div className="col-span-1 text-center">
                                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                            {employee.expired_certificates_count || 0}
                                                        </span>
                                                    </div>

                                                    {/* Actions */}
                                                    <div className="col-span-1 text-center">
                                                        <button
                                                            onClick={() => toggleEmployeeDetails(employee.id)}
                                                            className="text-green-600 hover:text-green-900 mr-2"
                                                            title="View Details"
                                                        >
                                                            {expandedEmployee === employee.id ? (
                                                                <ChevronDownIcon className="w-5 h-5" />
                                                            ) : (
                                                                <ChevronRightIcon className="w-5 h-5" />
                                                            )}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Expanded Details */}
                                            {expandedEmployee === employee.id && (
                                                <div className="px-6 py-4 bg-gray-50">
                                                    <h4 className="text-sm font-medium text-gray-900 mb-3">
                                                        Training Certificates for {employee.name}
                                                    </h4>

                                                    {loadingCertificates ? (
                                                        <div className="text-center py-4">
                                                            <div className="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-green-600"></div>
                                                            <p className="mt-2 text-sm text-gray-500">Loading certificates...</p>
                                                        </div>
                                                    ) : employeeCertificates[employee.id]?.length > 0 ? (
                                                        <div className="overflow-x-auto">
                                                            <table className="min-w-full divide-y divide-gray-200">
                                                                <thead className="bg-gray-100">
                                                                    <tr>
                                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Certificate #</th>
                                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Training Type</th>
                                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Issue Date</th>
                                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                                        <th className="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody className="bg-white divide-y divide-gray-200">
                                                                    {employeeCertificates[employee.id].map((certificate) => (
                                                                        <tr key={certificate.id}>
                                                                            <td className="px-4 py-2 text-sm text-gray-900">{certificate.certificate_number}</td>
                                                                            <td className="px-4 py-2 text-sm text-gray-900">{certificate.training_type}</td>
                                                                            <td className="px-4 py-2 text-sm text-gray-900">{certificate.provider}</td>
                                                                            <td className="px-4 py-2 text-sm text-gray-900">{certificate.issue_date}</td>
                                                                            <td className="px-4 py-2 text-sm text-gray-900">{certificate.expiry_date || 'No Expiry'}</td>
                                                                            <td className="px-4 py-2">
                                                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(certificate.compliance_status)}`}>
                                                                                    {getStatusIcon(certificate.compliance_status)}
                                                                                    <span className="ml-1">{certificate.compliance_status}</span>
                                                                                </span>
                                                                            </td>
                                                                            <td className="px-4 py-2 text-center">
                                                                                <div className="flex items-center justify-center space-x-2">
                                                                                    {/* View Training Record Details */}
                                                                                    <Link
                                                                                        href={route('training-records.show', certificate.id)}
                                                                                        className="text-blue-600 hover:text-blue-900"
                                                                                        title="View Details"
                                                                                    >
                                                                                        <EyeIcon className="w-4 h-4" />
                                                                                    </Link>

                                                                                    {/* Edit Training Record */}
                                                                                    <Link
                                                                                        href={route('training-records.edit', certificate.id)}
                                                                                        className="text-green-600 hover:text-green-900"
                                                                                        title="Edit Training Record"
                                                                                    >
                                                                                        <PencilIcon className="w-4 h-4" />
                                                                                    </Link>

                                                                                    {/* Delete Training Record */}
                                                                                    <button
                                                                                        onClick={() => deleteTrainingRecord(certificate.id, certificate.certificate_number, employee.id)}
                                                                                        disabled={deletingCertificate === certificate.id}
                                                                                        className="text-red-600 hover:text-red-900 disabled:opacity-50 disabled:cursor-not-allowed"
                                                                                        title="Delete Training Record"
                                                                                    >
                                                                                        {deletingCertificate === certificate.id ? (
                                                                                            <div className="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin"></div>
                                                                                        ) : (
                                                                                            <TrashIcon className="w-4 h-4" />
                                                                                        )}
                                                                                    </button>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    ))}
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    ) : (
                                                        <div className="text-center py-4 text-gray-500">
                                                            No training certificates found for this employee.
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <ChartBarIcon className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No employees found</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Try adjusting your search or filter criteria.
                                </p>
                            </div>
                        )}

                        {/* Pagination */}
                        {employees && employees.links && (
                            <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex-1 flex justify-between sm:hidden">
                                        {employees.links.prev && (
                                            <Link
                                                href={employees.links.prev}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Previous
                                            </Link>
                                        )}
                                        {employees.links.next && (
                                            <Link
                                                href={employees.links.next}
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
                                                <span className="font-medium">{employees.from}</span>{' '}
                                                to{' '}
                                                <span className="font-medium">{employees.to}</span>{' '}
                                                of{' '}
                                                <span className="font-medium">{employees.total}</span>{' '}
                                                results
                                            </p>
                                        </div>
                                        <div>
                                            <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                                {employees.links.map((link, index) => (
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
                                                            index === employees.links.length - 1 ? 'rounded-r-md' : ''
                                                        } ${
                                                            !link.url ? 'cursor-not-allowed opacity-50' : ''
                                                        }`}
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                ))}
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
