// resources/js/Pages/SDM/Index.jsx
// SDM Employee Management - Main Index Page

import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    UsersIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowPathIcon,
    PlusIcon,
    DocumentArrowDownIcon,
    DocumentArrowUpIcon,
    EllipsisVerticalIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    BuildingOfficeIcon,
    CheckCircleIcon,
    XCircleIcon,
    FolderIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, employees, departments, statistics, filters = {} }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [selectedEmployees, setSelectedEmployees] = useState([]);
    const [showBulkActions, setShowBulkActions] = useState(false);

    const handleSearch = () => {
        const params = {
            search: searchTerm || undefined,
            department: selectedDepartment || undefined,
            status: selectedStatus || undefined,
        };

        Object.keys(params).forEach(key => {
            if (!params[key]) delete params[key];
        });

        router.get(route('sdm.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        setSearchTerm('');
        setSelectedDepartment('');
        setSelectedStatus('');
        router.get(route('sdm.index'));
    };

    const handleSelectAll = (checked) => {
        if (checked) {
            setSelectedEmployees(employees.data.map(emp => emp.id));
        } else {
            setSelectedEmployees([]);
        }
        setShowBulkActions(checked && employees.data.length > 0);
    };

    const handleSelectEmployee = (employeeId, checked) => {
        let newSelected;
        if (checked) {
            newSelected = [...selectedEmployees, employeeId];
        } else {
            newSelected = selectedEmployees.filter(id => id !== employeeId);
        }
        setSelectedEmployees(newSelected);
        setShowBulkActions(newSelected.length > 0);
    };

    const getStatusBadge = (status) => {
        const configs = {
            active: { bg: 'bg-green-100', text: 'text-green-800', icon: CheckCircleIcon, label: 'Active' },
            inactive: { bg: 'bg-red-100', text: 'text-red-800', icon: XCircleIcon, label: 'Inactive' }
        };

        const config = configs[status] || configs.active;
        const Icon = config.icon;

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.bg} ${config.text}`}>
                <Icon className="w-3 h-3 mr-1" />
                {config.label}
            </span>
        );
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="SDM - Employee Management" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Page Header */}
                    <div className="mb-8">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900 flex items-center">
                                    <UsersIcon className="w-8 h-8 mr-3 text-blue-600" />
                                    SDM - Employee Management
                                </h1>
                                <p className="mt-2 text-gray-600">
                                    Manage employee master data with Excel integration
                                </p>
                            </div>
                            <div className="flex items-center space-x-3">
                                <Link
                                    href={route('sdm.import')}
                                    className="btn-secondary"
                                >
                                    <DocumentArrowUpIcon className="w-4 h-4 mr-2" />
                                    Import Excel
                                </Link>
                                <button
                                    onClick={() => router.get(route('sdm.export'))}
                                    className="btn-secondary"
                                >
                                    <DocumentArrowDownIcon className="w-4 h-4 mr-2" />
                                    Export Excel
                                </button>
                                <Link
                                    href={route('sdm.create')}
                                    className="btn-primary"
                                >
                                    <PlusIcon className="w-4 h-4 mr-2" />
                                    Add Employee
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-lg border border-gray-200 p-6">
                            <div className="flex items-center">
                                <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <UsersIcon className="w-6 h-6 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Employees</p>
                                    <p className="text-2xl font-bold text-gray-900">{statistics?.total_employees || 0}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg border border-gray-200 p-6">
                            <div className="flex items-center">
                                <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <CheckCircleIcon className="w-6 h-6 text-green-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Active</p>
                                    <p className="text-2xl font-bold text-gray-900">{statistics?.active_employees || 0}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg border border-gray-200 p-6">
                            <div className="flex items-center">
                                <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                    <XCircleIcon className="w-6 h-6 text-red-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Inactive</p>
                                    <p className="text-2xl font-bold text-gray-900">{statistics?.inactive_employees || 0}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg border border-gray-200 p-6">
                            <div className="flex items-center">
                                <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <FolderIcon className="w-6 h-6 text-purple-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">With Containers</p>
                                    <p className="text-2xl font-bold text-gray-900">{statistics?.employees_with_containers || 0}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search and Filters */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6 mb-8">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Search Employee
                                </label>
                                <div className="relative">
                                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                    <input
                                        type="text"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        placeholder="Search by name, NIP, email..."
                                        className="pl-10 form-input w-full"
                                        onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Department
                                </label>
                                <select
                                    value={selectedDepartment}
                                    onChange={(e) => setSelectedDepartment(e.target.value)}
                                    className="form-input w-full"
                                >
                                    <option value="">All Departments</option>
                                    {departments?.map(dept => (
                                        <option key={dept.id} value={dept.id}>{dept.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Status
                                </label>
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => setSelectedStatus(e.target.value)}
                                    className="form-input w-full"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div className="flex items-end space-x-2">
                                <button
                                    onClick={handleSearch}
                                    className="btn-primary flex-1"
                                >
                                    <FunnelIcon className="w-4 h-4 mr-2" />
                                    Filter
                                </button>
                                <button
                                    onClick={resetFilters}
                                    className="btn-secondary"
                                >
                                    <ArrowPathIcon className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Bulk Actions Bar */}
                    {showBulkActions && (
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center">
                                    <span className="text-sm font-medium text-blue-900">
                                        {selectedEmployees.length} employees selected
                                    </span>
                                </div>
                                <div className="flex items-center space-x-3">
                                    <button className="text-sm text-blue-600 hover:text-blue-800">
                                        Activate
                                    </button>
                                    <button className="text-sm text-blue-600 hover:text-blue-800">
                                        Deactivate
                                    </button>
                                    <button className="text-sm text-blue-600 hover:text-blue-800">
                                        Export Selected
                                    </button>
                                    <button
                                        onClick={() => setShowBulkActions(false)}
                                        className="text-sm text-gray-500 hover:text-gray-700"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Employees Table */}
                    <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left">
                                            <input
                                                type="checkbox"
                                                onChange={(e) => handleSelectAll(e.target.checked)}
                                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Employee
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Department & Position
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Contact
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Certificates
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
                                    {employees?.data?.length > 0 ? employees.data.map(employee => (
                                        <tr key={employee.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedEmployees.includes(employee.id)}
                                                    onChange={(e) => handleSelectEmployee(employee.id, e.target.checked)}
                                                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                />
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center">
                                                    <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                        <span className="text-blue-600 font-semibold text-sm">
                                                            {employee.name.charAt(0).toUpperCase()}
                                                        </span>
                                                    </div>
                                                    <div className="ml-4">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {employee.name}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            NIP: {employee.employee_id}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div>
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {employee.department?.name || 'No Department'}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {employee.position || 'No Position'}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div>
                                                    <div className="text-sm text-gray-900">
                                                        {employee.email || 'No email'}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {employee.phone || 'No phone'}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center space-x-2">
                                                    <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {employee.total_certificates || 0} Total
                                                    </span>
                                                    {employee.active_certificates > 0 && (
                                                        <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            {employee.active_certificates} Active
                                                        </span>
                                                    )}
                                                    {employee.expired_certificates > 0 && (
                                                        <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                            {employee.expired_certificates} Expired
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                {getStatusBadge(employee.status)}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center space-x-2">
                                                    <Link
                                                        href={route('employee-containers.show', employee.id)}
                                                        className="text-blue-600 hover:text-blue-900"
                                                        title="View Container"
                                                    >
                                                        <FolderIcon className="w-4 h-4" />
                                                    </Link>
                                                    <Link
                                                        href={route('sdm.edit', employee.id)}
                                                        className="text-gray-600 hover:text-gray-900"
                                                        title="Edit Employee"
                                                    >
                                                        <PencilIcon className="w-4 h-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => {
                                                            if (confirm('Are you sure you want to delete this employee?')) {
                                                                router.delete(route('sdm.destroy', employee.id));
                                                            }
                                                        }}
                                                        className="text-red-600 hover:text-red-900"
                                                        title="Delete Employee"
                                                    >
                                                        <TrashIcon className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr>
                                            <td colSpan="7" className="px-6 py-12 text-center">
                                                <div className="flex flex-col items-center">
                                                    <UsersIcon className="w-12 h-12 text-gray-400 mb-4" />
                                                    <h3 className="text-lg font-medium text-gray-900 mb-2">No employees found</h3>
                                                    <p className="text-gray-500 mb-4">
                                                        {searchTerm || selectedDepartment || selectedStatus
                                                            ? 'Try adjusting your search criteria'
                                                            : 'Get started by adding your first employee'
                                                        }
                                                    </p>
                                                    {!searchTerm && !selectedDepartment && !selectedStatus && (
                                                        <Link
                                                            href={route('sdm.create')}
                                                            className="btn-primary"
                                                        >
                                                            <PlusIcon className="w-4 h-4 mr-2" />
                                                            Add First Employee
                                                        </Link>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {employees?.links && (
                            <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-gray-700">
                                        Showing {employees.from || 0} to {employees.to || 0} of {employees.total || 0} employees
                                    </div>
                                    {/* Add pagination component here */}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Quick Stats Footer */}
                    <div className="mt-8 bg-gradient-to-r from-blue-50 to-green-50 border border-blue-200 rounded-lg p-6">
                        <div className="flex items-start">
                            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                <UsersIcon className="w-6 h-6 text-blue-600" />
                            </div>
                            <div className="flex-1">
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                    👥 SDM Employee Management System
                                </h3>
                                <p className="text-gray-700 mb-3">
                                    Manage employee master data with Excel import/export functionality.
                                    Each employee automatically gets a digital container for certificates and documents.
                                </p>
                                <div className="flex items-center space-x-6 text-sm text-gray-600">
                                    <span>📊 Total: {statistics?.total_employees || 0} employees</span>
                                    <span>✅ Active: {statistics?.active_employees || 0}</span>
                                    <span>🗂️ With Containers: {statistics?.employees_with_containers || 0}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
