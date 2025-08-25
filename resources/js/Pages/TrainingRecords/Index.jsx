import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    PencilIcon,
    UserIcon,
    BuildingOffice2Icon, // ← FIXED: Correct icon name
    ChartBarIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    EyeIcon,
    DocumentCheckIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, employees, departments, filters, stats }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    const handleSearch = () => {
        router.get(route('training-records.index'), {
            search: searchTerm,
            department: selectedDepartment,
            status: selectedStatus
        }, { preserveState: true, preserveScroll: true });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedDepartment('');
        setSelectedStatus('');
        router.get(route('training-records.index'), {}, { preserveState: true });
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    const getStatusBadge = (status) => {
        const variants = {
            compliant: 'bg-green-100 text-green-800 border-green-200',
            expiring_soon: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            expired: 'bg-red-100 text-red-800 border-red-200'
        };

        const icons = {
            compliant: <CheckCircleIcon className="w-3 h-3" />,
            expiring_soon: <ExclamationTriangleIcon className="w-3 h-3" />,
            expired: <XCircleIcon className="w-3 h-3" />
        };

        const labels = {
            compliant: 'Active',
            expiring_soon: 'Expiring',
            expired: 'Expired'
        };

        return (
            <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border ${variants[status] || 'bg-gray-100 text-gray-800 border-gray-200'}`}>
                {icons[status]}
                {labels[status] || 'Unknown'}
            </span>
        );
    };

    const renderTrainingTypes = (trainingSummary) => {
        if (!trainingSummary || trainingSummary.length === 0) {
            return (
                <div className="text-gray-500 text-sm italic">
                    No training records
                </div>
            );
        }

        return (
            <div className="space-y-1">
                {trainingSummary.slice(0, 2).map((training, index) => (
                    <div key={index} className="flex items-center justify-between">
                        <span className="text-sm text-gray-700 truncate max-w-[200px]" title={training.training_type.name}>
                            {training.training_type.name}
                        </span>
                        {getStatusBadge(training.status)}
                    </div>
                ))}
                {trainingSummary.length > 2 && (
                    <div className="text-xs text-gray-500 font-medium">
                        +{trainingSummary.length - 2} more training types
                    </div>
                )}
            </div>
        );
    };

    const getComplianceColor = (stats) => {
        const rate = stats.total > 0 ? (stats.active / stats.total * 100) : 0;
        if (rate >= 90) return 'text-green-600';
        if (rate >= 70) return 'text-yellow-600';
        return 'text-red-600';
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Training Records Management" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="bg-white shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6 border-b border-gray-200">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h2 className="text-2xl font-bold text-gray-900">Training Records Management</h2>
                                    <p className="mt-1 text-sm text-gray-600">
                                        Kelola data sertifikat dan pelatihan karyawan GAPURA
                                    </p>
                                </div>
                                <div className="flex space-x-3">
                                    <Link
                                        href={route('training-records.create')}
                                        className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <DocumentCheckIcon className="w-4 h-4 mr-2" />
                                        Add Training Record
                                    </Link>
                                </div>
                            </div>
                        </div>

                        {/* Statistics Cards */}
                        <div className="p-6 bg-gray-50">
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div className="bg-white p-4 rounded-lg border border-gray-200">
                                    <div className="flex items-center">
                                        <UserIcon className="w-8 h-8 text-blue-500" />
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-600">Total Employees</p>
                                            <p className="text-2xl font-bold text-gray-900">{stats.total_employees}</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-white p-4 rounded-lg border border-gray-200">
                                    <div className="flex items-center">
                                        <CheckCircleIcon className="w-8 h-8 text-green-500" />
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-600">Active Certificates</p>
                                            <p className="text-2xl font-bold text-green-600">{stats.active_certificates}</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-white p-4 rounded-lg border border-gray-200">
                                    <div className="flex items-center">
                                        <ExclamationTriangleIcon className="w-8 h-8 text-yellow-500" />
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-600">Expiring Soon</p>
                                            <p className="text-2xl font-bold text-yellow-600">{stats.expiring_certificates}</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-white p-4 rounded-lg border border-gray-200">
                                    <div className="flex items-center">
                                        <XCircleIcon className="w-8 h-8 text-red-500" />
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-600">Expired</p>
                                            <p className="text-2xl font-bold text-red-600">{stats.expired_certificates}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Search Employee
                                    </label>
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                        <input
                                            type="text"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            onKeyPress={handleKeyPress}
                                            placeholder="Name or Employee ID..."
                                            className="pl-10 w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Department
                                    </label>
                                    <select
                                        value={selectedDepartment}
                                        onChange={(e) => setSelectedDepartment(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">All Departments</option>
                                        {departments.map(dept => (
                                            <option key={dept.id} value={dept.id}>{dept.name}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Training Status
                                    </label>
                                    <select
                                        value={selectedStatus}
                                        onChange={(e) => setSelectedStatus(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">All Status</option>
                                        <option value="active">Has Active Training</option>
                                        <option value="expiring_soon">Has Expiring Training</option>
                                        <option value="expired">Has Expired Training</option>
                                        <option value="no_training">No Training Records</option>
                                    </select>
                                </div>

                                <div className="flex items-end space-x-2">
                                    <button
                                        onClick={handleSearch}
                                        className="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200"
                                    >
                                        <FunnelIcon className="w-4 h-4 mr-2 inline" />
                                        Filter
                                    </button>
                                    <button
                                        onClick={clearFilters}
                                        className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Employees Table */}
                    <div className="bg-white shadow-sm sm:rounded-lg">
                        <div className="overflow-hidden">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Employee
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Department
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Training Types
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Compliance
                                        </th>
                                        <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {employees.data.length === 0 ? (
                                        <tr>
                                            <td colSpan="5" className="px-6 py-12 text-center">
                                                <UserIcon className="mx-auto w-12 h-12 text-gray-400" />
                                                <p className="mt-2 text-sm text-gray-500">No employees found</p>
                                                <p className="text-xs text-gray-400">Try adjusting your filters</p>
                                            </td>
                                        </tr>
                                    ) : (
                                        employees.data.map((employee) => (
                                            <tr key={employee.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <div className="flex-shrink-0 w-10 h-10">
                                                            <div className="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                                <UserIcon className="w-6 h-6 text-gray-600" />
                                                            </div>
                                                        </div>
                                                        <div className="ml-4">
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {employee.name}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                ID: {employee.employee_id}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center text-sm text-gray-900">
                                                        <BuildingOffice2Icon className="w-4 h-4 mr-2 text-gray-400" />
                                                        {employee.department?.name || 'No Department'}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="max-w-xs">
                                                        {renderTrainingTypes(employee.training_summary)}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center space-x-4">
                                                        <div className="text-sm">
                                                            <span className={`font-bold ${getComplianceColor(employee.training_stats)}`}>
                                                                {employee.training_stats.total}
                                                            </span>
                                                            <span className="text-gray-500 ml-1">total</span>
                                                        </div>
                                                        {employee.training_stats.active > 0 && (
                                                            <div className="text-sm">
                                                                <span className="font-medium text-green-600">
                                                                    {employee.training_stats.active}
                                                                </span>
                                                                <span className="text-gray-500 ml-1">active</span>
                                                            </div>
                                                        )}
                                                        {employee.training_stats.expired > 0 && (
                                                            <div className="text-sm">
                                                                <span className="font-medium text-red-600">
                                                                    {employee.training_stats.expired}
                                                                </span>
                                                                <span className="text-gray-500 ml-1">expired</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                                    <div className="flex items-center justify-center space-x-2">
                                                        <Link
                                                            href={route('employees.show', employee.id)}
                                                            className="text-gray-400 hover:text-gray-600"
                                                            title="View Employee Details"
                                                        >
                                                            <EyeIcon className="w-4 h-4" />
                                                        </Link>
                                                        <Link
                                                            href={route('training-records.edit-employee', employee.id)}
                                                            className="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
                                                        >
                                                            <PencilIcon className="w-4 h-4 mr-1" />
                                                            Manage Training
                                                        </Link>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {employees.links && (
                            <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
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
                                            Showing <span className="font-medium">{employees.from || 0}</span> to{' '}
                                            <span className="font-medium">{employees.to || 0}</span> of{' '}
                                            <span className="font-medium">{employees.total || 0}</span> employees
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
                                                            : link.url
                                                            ? 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                            : 'bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed'
                                                    } ${
                                                        index === 0 ? 'rounded-l-md' : ''
                                                    } ${
                                                        index === employees.links.length - 1 ? 'rounded-r-md' : ''
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ))}
                                        </nav>
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
