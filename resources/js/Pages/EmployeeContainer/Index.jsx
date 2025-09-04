// resources/js/Pages/EmployeeContainers/Index.jsx
// Employee Container System - Main Index Page

import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    FolderIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowPathIcon,
    ChartBarIcon,
    DocumentIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    ClockIcon,
    UsersIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, employees, departments, certificateTypes, overallStats, filters = {} }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [selectedContainerStatus, setSelectedContainerStatus] = useState(filters.container_status || '');

    const handleSearch = () => {
        const params = {
            search: searchTerm || undefined,
            department: selectedDepartment || undefined,
            status: selectedStatus || undefined,
            container_status: selectedContainerStatus || undefined,
        };

        Object.keys(params).forEach(key => {
            if (!params[key]) delete params[key];
        });

        router.get(route('employee-containers.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        setSearchTerm('');
        setSelectedDepartment('');
        setSelectedStatus('');
        setSelectedContainerStatus('');
        router.get(route('employee-containers.index'));
    };

    // Get container status display
    const getContainerStatus = (employee) => {
        const stats = employee.container_stats || {};

        if (stats.expired > 0) {
            return {
                icon: XCircleIcon,
                text: `${stats.expired} Expired`,
                color: 'text-red-600',
                bg: 'bg-red-50',
                border: 'border-red-200'
            };
        }

        if (stats.expiring_soon > 0) {
            return {
                icon: ExclamationTriangleIcon,
                text: `${stats.expiring_soon} Expiring Soon`,
                color: 'text-yellow-600',
                bg: 'bg-yellow-50',
                border: 'border-yellow-200'
            };
        }

        if (stats.active > 0) {
            return {
                icon: CheckCircleIcon,
                text: `${stats.active} Active Certs`,
                color: 'text-green-600',
                bg: 'bg-green-50',
                border: 'border-green-200'
            };
        }

        return {
            icon: FolderIcon,
            text: 'Empty Container',
            color: 'text-slate-500',
            bg: 'bg-slate-50',
            border: 'border-slate-200'
        };
    };

    const getBackgroundCheckBadge = (status) => {
        const configs = {
            cleared: { bg: 'bg-green-100', text: 'text-green-800', label: '✓ Cleared' },
            in_progress: { bg: 'bg-blue-100', text: 'text-blue-800', label: '⏳ In Progress' },
            expired: { bg: 'bg-red-100', text: 'text-red-800', label: '❌ Expired' },
            not_started: { bg: 'bg-slate-100', text: 'text-slate-800', label: '⚪ Not Started' }
        };

        const config = configs[status] || configs.not_started;

        return (
            <span className={`inline-flex items-center px-2 py-1 rounded text-xs font-medium ${config.bg} ${config.text}`}>
                {config.label}
            </span>
        );
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            active: { bg: 'bg-green-100', text: 'text-green-800', label: 'Active' },
            inactive: { bg: 'bg-red-100', text: 'text-red-800', label: 'Inactive' }
        };

        const config = statusConfig[status] || statusConfig.active;

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.bg} ${config.text}`}>
                {config.label}
            </span>
        );
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Employee Containers - Digital Folder System" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-3xl font-bold text-slate-900 flex items-center">
                                    <FolderIcon className="w-8 h-8 text-green-600 mr-3" />
                                    Employee Containers
                                </h1>
                                <p className="mt-2 text-sm text-slate-600">
                                    Digital employee folders with organized certificates and background check data
                                </p>
                            </div>
                            <div className="bg-white rounded-lg border border-slate-200 p-4">
                                <div className="grid grid-cols-2 gap-4 text-center">
                                    <div>
                                        <div className="text-2xl font-bold text-green-600">{overallStats.employees_with_certificates}</div>
                                        <div className="text-xs text-slate-500">With Certificates</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-blue-600">{overallStats.total_certificates}</div>
                                        <div className="text-xs text-slate-500">Total Certificates</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Overall Statistics */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div className="bg-white p-4 rounded-lg border border-slate-200">
                            <div className="flex items-center">
                                <UsersIcon className="w-8 h-8 text-slate-600 mr-3" />
                                <div>
                                    <p className="text-2xl font-bold text-slate-900">{overallStats.total_employees}</p>
                                    <p className="text-sm text-slate-600">Total Employees</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-green-50 p-4 rounded-lg border border-green-200">
                            <div className="flex items-center">
                                <CheckCircleIcon className="w-8 h-8 text-green-600 mr-3" />
                                <div>
                                    <p className="text-2xl font-bold text-green-900">{overallStats.certificates_by_status.active}</p>
                                    <p className="text-sm text-green-700">Active Certificates</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            <div className="flex items-center">
                                <ExclamationTriangleIcon className="w-8 h-8 text-yellow-600 mr-3" />
                                <div>
                                    <p className="text-2xl font-bold text-yellow-900">{overallStats.certificates_by_status.expiring_soon}</p>
                                    <p className="text-sm text-yellow-700">Expiring Soon</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-red-50 p-4 rounded-lg border border-red-200">
                            <div className="flex items-center">
                                <XCircleIcon className="w-8 h-8 text-red-600 mr-3" />
                                <div>
                                    <p className="text-2xl font-bold text-red-900">{overallStats.certificates_by_status.expired}</p>
                                    <p className="text-sm text-red-700">Expired Certificates</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search and Filter */}
                    <div className="card mb-6">
                        <div className="card-body">
                            <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                                <div className="md:col-span-2">
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <input
                                            type="text"
                                            placeholder="Search by name, NIP, position..."
                                            className="pl-10 input-field"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                        />
                                    </div>
                                </div>

                                <div>
                                    <select
                                        className="input-field"
                                        value={selectedDepartment}
                                        onChange={(e) => setSelectedDepartment(e.target.value)}
                                    >
                                        <option value="">All Departments</option>
                                        {departments?.map((dept) => (
                                            <option key={dept.id} value={dept.id}>
                                                {dept.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <select
                                        className="input-field"
                                        value={selectedContainerStatus}
                                        onChange={(e) => setSelectedContainerStatus(e.target.value)}
                                    >
                                        <option value="">All Container Status</option>
                                        <option value="with_certificates">With Certificates</option>
                                        <option value="no_certificates">Empty Containers</option>
                                        <option value="expired_certificates">Has Expired</option>
                                        <option value="expiring_soon">Expiring Soon</option>
                                    </select>
                                </div>

                                <div>
                                    <select
                                        className="input-field"
                                        value={selectedStatus}
                                        onChange={(e) => setSelectedStatus(e.target.value)}
                                    >
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div className="flex items-center justify-between mt-4">
                                <div className="flex items-center space-x-3">
                                    <button
                                        onClick={handleSearch}
                                        className="btn-primary"
                                    >
                                        <FunnelIcon className="w-4 h-4 mr-2" />
                                        Filter
                                    </button>
                                    <button
                                        onClick={resetFilters}
                                        className="btn-secondary"
                                    >
                                        <ArrowPathIcon className="w-4 h-4 mr-2" />
                                        Reset
                                    </button>
                                </div>
                                <div className="text-sm text-slate-600">
                                    Total: {employees?.total || 0} employee containers
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Employee Containers Table */}
                    <div className="card">
                        <div className="overflow-x-auto">
                            <table className="table w-full">
                                <thead>
                                    <tr className="bg-slate-50 border-b border-slate-200">
                                        <th className="table-header">Employee</th>
                                        <th className="table-header">Department & Position</th>
                                        <th className="table-header">Container Status</th>
                                        <th className="table-header">Background Check</th>
                                        <th className="table-header">Status</th>
                                        <th className="table-header">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-slate-200">
                                    {employees?.data?.length > 0 ? (
                                        employees.data.map((employee) => {
                                            const containerStatus = getContainerStatus(employee);
                                            const StatusIcon = containerStatus.icon;

                                            return (
                                                <tr key={employee.id} className="hover:bg-slate-50 transition-colors">
                                                    <td className="table-cell">
                                                        <div className="flex items-center">
                                                            <div className="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center mr-3">
                                                                <span className="text-white font-medium text-sm">
                                                                    {employee.name.charAt(0).toUpperCase()}
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <div className="font-medium text-slate-900">
                                                                    {employee.name}
                                                                </div>
                                                                <div className="text-sm text-slate-500">
                                                                    NIP: {employee.employee_id}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="table-cell">
                                                        <div>
                                                            <div className="text-sm font-medium text-slate-900">
                                                                {employee.department?.name || 'No Department'}
                                                            </div>
                                                            <div className="text-sm text-slate-500">
                                                                {employee.position || 'No Position'}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="table-cell">
                                                        <div className={`inline-flex items-center px-3 py-1.5 rounded-lg border ${containerStatus.bg} ${containerStatus.color} ${containerStatus.border}`}>
                                                            <StatusIcon className="w-4 h-4 mr-2" />
                                                            <span className="text-sm font-medium">
                                                                {containerStatus.text}
                                                            </span>
                                                        </div>
                                                        {employee.container_stats.total > 0 && (
                                                            <div className="text-xs text-slate-500 mt-1">
                                                                Total: {employee.container_stats.total} certificates
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="table-cell">
                                                        {employee.container_stats.has_background_check ? (
                                                            getBackgroundCheckBadge(employee.container_stats.background_check_status)
                                                        ) : (
                                                            <span className="text-sm text-slate-400">No data</span>
                                                        )}
                                                    </td>
                                                    <td className="table-cell">
                                                        {getStatusBadge(employee.status)}
                                                    </td>
                                                    <td className="table-cell">
                                                        <div className="flex items-center space-x-2">
                                                            <Link
                                                                href={route('employee-containers.show', employee.id)}
                                                                className="btn-sm btn-primary"
                                                                title="Open Container"
                                                            >
                                                                <FolderIcon className="w-4 h-4 mr-1" />
                                                                Open Container
                                                            </Link>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td colSpan="6" className="table-cell text-center py-8">
                                                <div className="flex flex-col items-center">
                                                    <FolderIcon className="w-16 h-16 text-slate-300 mb-4" />
                                                    <h3 className="text-lg font-medium text-slate-900 mb-2">
                                                        No employee containers found
                                                    </h3>
                                                    <p className="text-slate-500 mb-4">
                                                        Adjust your search criteria or filters to find employee containers.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {employees?.links && (
                            <div className="px-6 py-4 border-t border-slate-200">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-slate-700">
                                        Showing {employees.from || 0} to {employees.to || 0} of {employees.total || 0} containers
                                    </div>
                                    {/* Pagination links would go here */}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Container System Info */}
                    <div className="mt-6 bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-6">
                        <div className="flex items-start">
                            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                <FolderIcon className="w-6 h-6 text-green-600" />
                            </div>
                            <div className="flex-1">
                                <h3 className="text-lg font-semibold text-slate-900 mb-2">
                                    🗂️ Employee Container System
                                </h3>
                                <p className="text-slate-700 mb-3">
                                    Each employee has a digital container (folder) containing all their certificates and background check documents.
                                    Click <strong>"Open Container"</strong> to access organized employee data in one place.
                                </p>
                                <div className="flex items-center space-x-6 text-sm">
                                    <div className="flex items-center">
                                        <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                        <span className="text-slate-600">Active Certificates</span>
                                    </div>
                                    <div className="flex items-center">
                                        <ExclamationTriangleIcon className="w-4 h-4 text-yellow-500 mr-2" />
                                        <span className="text-slate-600">Expiring Soon</span>
                                    </div>
                                    <div className="flex items-center">
                                        <XCircleIcon className="w-4 h-4 text-red-500 mr-2" />
                                        <span className="text-slate-600">Expired</span>
                                    </div>
                                    <div className="flex items-center">
                                        <FolderIcon className="w-4 h-4 text-slate-400 mr-2" />
                                        <span className="text-slate-600">Empty Container</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
