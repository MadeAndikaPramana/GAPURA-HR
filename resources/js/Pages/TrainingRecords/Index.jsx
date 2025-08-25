// resources/js/Pages/TrainingRecords/Index.jsx - COMPLETELY REWRITTEN

import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    UserIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    ArrowDownTrayIcon,
    FunnelIcon,
    XMarkIcon,
    ClipboardDocumentListIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    AcademicCapIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, employees, employeeList, trainingTypes, departments, filters, stats }) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [selectedEmployee, setSelectedEmployee] = useState(filters?.employee || '');
    const [selectedTrainingType, setSelectedTrainingType] = useState(filters?.training_type || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters?.department || '');

    // State for expandable rows
    const [expandedEmployees, setExpandedEmployees] = useState(new Set());
    const [employeeCertificates, setEmployeeCertificates] = useState({});
    const [loadingCertificates, setLoadingCertificates] = useState({});

    const handleSearch = () => {
        router.get(route('training-records.index'), {
            search: searchTerm,
            status: selectedStatus,
            employee: selectedEmployee,
            training_type: selectedTrainingType,
            department: selectedDepartment,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedStatus('');
        setSelectedEmployee('');
        setSelectedTrainingType('');
        setSelectedDepartment('');
        router.get(route('training-records.index'));
    };

    const handleFilterChange = (filterType, value) => {
        const newFilters = {
            search: searchTerm,
            status: selectedStatus,
            employee: selectedEmployee,
            training_type: selectedTrainingType,
            department: selectedDepartment,
        };

        newFilters[filterType] = value;

        router.get(route('training-records.index'), newFilters, {
            preserveState: true,
            replace: true,
        });
    };

    // Toggle expand/collapse employee certificates
    const toggleEmployeeCertificates = async (employeeId) => {
        const newExpanded = new Set(expandedEmployees);

        if (newExpanded.has(employeeId)) {
            newExpanded.delete(employeeId);
            setExpandedEmployees(newExpanded);
        } else {
            newExpanded.add(employeeId);
            setExpandedEmployees(newExpanded);

            // Load certificates if not already loaded
            if (!employeeCertificates[employeeId]) {
                setLoadingCertificates(prev => ({ ...prev, [employeeId]: true }));

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
                    setLoadingCertificates(prev => ({ ...prev, [employeeId]: false }));
                }
            }
        }
    };

    const exportRecords = () => {
        const params = new URLSearchParams({
            search: searchTerm,
            status: selectedStatus,
            employee: selectedEmployee,
            training_type: selectedTrainingType,
            department: selectedDepartment,
        });

        window.location.href = route('import-export.training-records.export') + '?' + params.toString();
    };

    const getStatusBadge = (status, count) => {
        if (count === 0) return null;

        let statusClass = '';
        let statusText = '';

        switch (status) {
            case 'active':
                statusClass = 'bg-green-100 text-green-800';
                statusText = `${count} Active`;
                break;
            case 'expiring':
                statusClass = 'bg-yellow-100 text-yellow-800';
                statusText = `${count} Expiring`;
                break;
            case 'expired':
                statusClass = 'bg-red-100 text-red-800';
                statusText = `${count} Expired`;
                break;
            default:
                statusClass = 'bg-gray-100 text-gray-800';
                statusText = `${count}`;
        }

        return (
            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass} mr-1 mb-1`}>
                {statusText}
            </span>
        );
    };

    const getCertificateStatusBadge = (cert) => {
        let statusClass = '';
        let statusText = '';

        switch (cert.status) {
            case 'compliant':
                statusClass = 'bg-green-100 text-green-800';
                statusText = 'Active';
                break;
            case 'expiring_soon':
                statusClass = 'bg-yellow-100 text-yellow-800';
                statusText = 'Expiring Soon';
                break;
            case 'expired':
                statusClass = 'bg-red-100 text-red-800';
                statusText = 'Expired';
                break;
            default:
                statusClass = 'bg-gray-100 text-gray-800';
                statusText = 'Unknown';
        }

        return (
            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass}`}>
                {statusText}
            </span>
        );
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Training Records" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Header */}
                    <div className="mb-8">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Training Records</h1>
                                <p className="text-sm text-gray-600 mt-1">
                                    Employee training certificates and compliance overview
                                </p>
                            </div>
                            <div className="flex space-x-2">
                                <button
                                    onClick={exportRecords}
                                    className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                >
                                    <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                                    Export
                                </button>
                                <Link
                                    href={route('training-records.create')}
                                    className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    <PlusIcon className="w-4 h-4 mr-2" />
                                    Add Training
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-blue-50 rounded-lg p-6">
                            <div className="flex items-center">
                                <UserIcon className="w-8 h-8 text-blue-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-blue-600">Total Employees</p>
                                    <p className="text-2xl font-bold text-blue-900">{stats?.total_employees || 0}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-green-50 rounded-lg p-6">
                            <div className="flex items-center">
                                <CheckCircleIcon className="w-8 h-8 text-green-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-green-600">Active Certificates</p>
                                    <p className="text-2xl font-bold text-green-900">{stats?.compliant_certificates || 0}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-yellow-50 rounded-lg p-6">
                            <div className="flex items-center">
                                <ExclamationTriangleIcon className="w-8 h-8 text-yellow-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-yellow-600">Expiring Soon</p>
                                    <p className="text-2xl font-bold text-yellow-900">{stats?.expiring_certificates || 0}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-red-50 rounded-lg p-6">
                            <div className="flex items-center">
                                <XCircleIcon className="w-8 h-8 text-red-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-red-600">Expired</p>
                                    <p className="text-2xl font-bold text-red-900">{stats?.expired_certificates || 0}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white shadow rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <FunnelIcon className="w-5 h-5 mr-2" />
                                    Filters
                                </h3>
                                {(searchTerm || selectedStatus || selectedEmployee || selectedTrainingType || selectedDepartment) && (
                                    <button
                                        onClick={clearFilters}
                                        className="text-sm text-red-600 hover:text-red-700 flex items-center"
                                    >
                                        <XMarkIcon className="w-4 h-4 mr-1" />
                                        Clear Filters
                                    </button>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                                {/* Search */}
                                <div>
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

                                {/* Employee Filter */}
                                <div>
                                    <select
                                        value={selectedEmployee}
                                        onChange={(e) => {
                                            setSelectedEmployee(e.target.value);
                                            handleFilterChange('employee', e.target.value);
                                        }}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                    >
                                        <option value="">All Employees</option>
                                        {employeeList && employeeList.map((employee) => (
                                            <option key={employee.id} value={employee.id}>
                                                {employee.employee_id} - {employee.name}
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
                    </div>

                    {/* Employee Training Overview Table */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        {employees && employees.data && employees.data.length > 0 ? (
                            <>
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Employee
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Certificates Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Latest Training
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Total Certificates
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {employees.data.map((employee) => (
                                            <React.Fragment key={employee.id}>
                                                {/* Main Employee Row */}
                                                <tr className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <div className="flex-shrink-0 h-10 w-10">
                                                                <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                                    <span className="text-sm font-medium text-green-800">
                                                                        {employee.name?.charAt(0) || '?'}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div className="ml-4">
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {employee.name || 'Unknown'}
                                                                </div>
                                                                <div className="text-sm text-gray-500">
                                                                    {employee.employee_id || 'No ID'} • {employee.department?.name || 'No Department'}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex flex-wrap">
                                                            {getStatusBadge('active', employee.active_certificates_count)}
                                                            {getStatusBadge('expiring', employee.expiring_certificates_count)}
                                                            {getStatusBadge('expired', employee.expired_certificates_count)}
                                                            {employee.total_certificates_count === 0 && (
                                                                <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                                    No Certificates
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {employee.training_records && employee.training_records.length > 0 ? (
                                                            <div className="text-sm text-gray-900">
                                                                {employee.training_records[0].training_type?.name || 'Unknown'}
                                                                <div className="text-xs text-gray-500">
                                                                    {new Date(employee.training_records[0].created_at).toLocaleDateString()}
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-gray-500">No training records</span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <span className="text-sm font-medium text-gray-900">
                                                                {employee.total_certificates_count}
                                                            </span>
                                                            {employee.total_certificates_count > 0 && (
                                                                <button
                                                                    onClick={() => toggleEmployeeCertificates(employee.id)}
                                                                    className="ml-2 text-green-600 hover:text-green-700"
                                                                >
                                                                    {expandedEmployees.has(employee.id) ? (
                                                                        <ChevronDownIcon className="w-4 h-4" />
                                                                    ) : (
                                                                        <ChevronRightIcon className="w-4 h-4" />
                                                                    )}
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex items-center space-x-2">
                                                            <Link
                                                                href={route('employees.show', employee.id)}
                                                                className="text-green-600 hover:text-green-900"
                                                                title="View Employee"
                                                            >
                                                                <EyeIcon className="w-4 h-4" />
                                                            </Link>
                                                            <Link
                                                                href={route('training-records.create', { employee_id: employee.id })}
                                                                className="text-blue-600 hover:text-blue-900"
                                                                title="Add Training"
                                                            >
                                                                <PlusIcon className="w-4 h-4" />
                                                            </Link>
                                                        </div>
                                                    </td>
                                                </tr>

                                                {/* Expandable Certificates Row */}
                                                {expandedEmployees.has(employee.id) && (
                                                    <tr>
                                                        <td colSpan="5" className="px-6 py-4 bg-gray-50">
                                                            {loadingCertificates[employee.id] ? (
                                                                <div className="flex items-center justify-center py-4">
                                                                    <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-green-600"></div>
                                                                    <span className="ml-2 text-sm text-gray-600">Loading certificates...</span>
                                                                </div>
                                                            ) : employeeCertificates[employee.id] ? (
                                                                <div className="space-y-2">
                                                                    <h5 className="text-sm font-medium text-gray-800 mb-3 flex items-center">
                                                                        <AcademicCapIcon className="w-4 h-4 mr-2" />
                                                                        All Certificates ({employeeCertificates[employee.id].length})
                                                                    </h5>
                                                                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                                                        {employeeCertificates[employee.id].map((cert) => (
                                                                            <div key={cert.id} className="bg-white border border-gray-200 rounded-lg p-3">
                                                                                <div className="flex items-start justify-between">
                                                                                    <div className="flex-1">
                                                                                        <div className="text-sm font-medium text-gray-900 mb-1">
                                                                                            {cert.training_type}
                                                                                        </div>
                                                                                        <div className="text-xs text-gray-600 mb-2">
                                                                                            <strong>Certificate:</strong> {cert.certificate_number}
                                                                                        </div>
                                                                                        <div className="text-xs text-gray-600 mb-2">
                                                                                            <strong>Issued by:</strong> {cert.issuer}
                                                                                        </div>
                                                                                        <div className="text-xs text-gray-600 mb-2">
                                                                                            <strong>Valid:</strong> {cert.issue_date} - {cert.expiry_date || 'No expiry'}
                                                                                        </div>
                                                                                        {cert.days_until_expiry !== null && (
                                                                                            <div className="text-xs text-gray-600 mb-2">
                                                                                                <strong>Days until expiry:</strong> {cert.days_until_expiry}
                                                                                            </div>
                                                                                        )}
                                                                                    </div>
                                                                                    <div className="flex flex-col items-end space-y-2">
                                                                                        {getCertificateStatusBadge(cert)}
                                                                                        <div className="flex items-center space-x-1">
                                                                                            <Link
                                                                                                href={route('training-records.edit', cert.id)}
                                                                                                className="text-blue-600 hover:text-blue-700"
                                                                                                title="Edit"
                                                                                            >
                                                                                                <PencilIcon className="w-3 h-3" />
                                                                                            </Link>
                                                                                            <button
                                                                                                onClick={() => {
                                                                                                    if (confirm('Are you sure you want to delete this certificate?')) {
                                                                                                        router.delete(route('training-records.destroy', cert.id));
                                                                                                    }
                                                                                                }}
                                                                                                className="text-red-600 hover:text-red-700"
                                                                                                title="Delete"
                                                                                            >
                                                                                                <TrashIcon className="w-3 h-3" />
                                                                                            </button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        ))}
                                                                    </div>
                                                                </div>
                                                            ) : (
                                                                <div className="text-center py-4">
                                                                    <p className="text-sm text-gray-500">Failed to load certificates</p>
                                                                </div>
                                                            )}
                                                        </td>
                                                    </tr>
                                                )}
                                            </React.Fragment>
                                        ))}
                                    </tbody>
                                </table>

                                {/* Pagination */}
                                {employees.links && (
                                    <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                        <div className="flex items-center justify-between">
                                            <div className="flex-1 flex justify-between sm:hidden">
                                                {employees.prev_page_url && (
                                                    <Link
                                                        href={employees.prev_page_url}
                                                        className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                                    >
                                                        Previous
                                                    </Link>
                                                )}
                                                {employees.next_page_url && (
                                                    <Link
                                                        href={employees.next_page_url}
                                                        className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                                    >
                                                        Next
                                                    </Link>
                                                )}
                                            </div>
                                            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                                <div>
                                                    <p className="text-sm text-gray-700">
                                                        Showing <span className="font-medium">{employees.from}</span> to{' '}
                                                        <span className="font-medium">{employees.to}</span> of{' '}
                                                        <span className="font-medium">{employees.total}</span> employees
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
                            </>
                        ) : (
                            <div className="text-center py-12">
                                <ClipboardDocumentListIcon className="w-12 h-12 mx-auto text-gray-400 mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">No employees found</h3>
                                <p className="text-sm text-gray-500 mb-4">
                                    Try adjusting your search criteria or add some employees first.
                                </p>
                                <div className="flex items-center justify-center space-x-2">
                                    <Link
                                        href={route('employees.create')}
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <UserIcon className="w-4 h-4 mr-2" />
                                        Add Employee
                                    </Link>
                                    <Link
                                        href={route('training-records.create')}
                                        className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <PlusIcon className="w-4 h-4 mr-2" />
                                        Add Training
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
