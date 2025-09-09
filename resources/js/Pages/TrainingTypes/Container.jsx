// resources/js/Pages/TrainingTypes/Container.jsx
// "Certificate jenis ini dimiliki siapa saja" view - FIXED

import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    UserGroupIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    XCircleIcon,
    ArrowLeftIcon,
    EyeIcon,
    BuildingOfficeIcon,
    IdentificationIcon
} from '@heroicons/react/24/outline';

export default function TrainingTypeContainer({
    auth,
    certificateType,
    containerData = {},
    departments = [], // ✅ FIXED: Default empty array
    breadcrumb = []
}) {
    const [filters, setFilters] = useState({
        status: '',
        department_id: '',
        search: ''
    });

    // Get statistics safely
    const stats = containerData.statistics || {
        total_certificates: 0,
        active_certificates: 0,
        expired_certificates: 0,
        expiring_soon_certificates: 0,
        unique_employees: 0,
        compliance_rate: null
    };

    // Get employees list safely
    const allEmployees = containerData.employees || [];

    // Filter employees based on current filters
    const filteredEmployees = allEmployees.filter(employeeData => {
        const employee = employeeData.employee;
        const latestCert = employeeData.latest_certificate;

        // Status filter
        if (filters.status && (!latestCert || latestCert.status !== filters.status)) {
            return false;
        }

        // Department filter (match by department name)
        if (filters.department_id) {
            const selectedDept = departments.find(d => d.id == filters.department_id);
            if (selectedDept && employee.department !== selectedDept.name) {
                return false;
            }
        }

        // Search filter
        if (filters.search) {
            const searchLower = filters.search.toLowerCase();
            const nameMatch = employee.name.toLowerCase().includes(searchLower);
            const idMatch = employee.employee_id && employee.employee_id.toLowerCase().includes(searchLower);
            if (!nameMatch && !idMatch) {
                return false;
            }
        }

        return true;
    });

    // Handle filter changes
    const handleFilterChange = (key, value) => {
        setFilters(prev => ({
            ...prev,
            [key]: value
        }));
    };

    // Get status badge styling
    const getStatusBadge = (status) => {
        const badges = {
            active: 'bg-green-100 text-green-800 border-green-200',
            expired: 'bg-red-100 text-red-800 border-red-200',
            expiring_soon: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            pending: 'bg-gray-100 text-gray-800 border-gray-200'
        };
        return badges[status] || badges.pending;
    };

    // Get status icon
    const getStatusIcon = (status) => {
        switch (status) {
            case 'active':
                return <CheckCircleIcon className="w-4 h-4" />;
            case 'expired':
                return <XCircleIcon className="w-4 h-4" />;
            case 'expiring_soon':
                return <ExclamationTriangleIcon className="w-4 h-4" />;
            default:
                return <ClockIcon className="w-4 h-4" />;
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Training Type Container - ${certificateType.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <Link
                                    href={route('training-types.index')}
                                    className="inline-flex items-center px-3 py-2 border border-slate-300 shadow-sm text-sm leading-4 font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                    Back to Training Types
                                </Link>
                                <div>
                                    <h1 className="text-3xl font-bold text-slate-900 flex items-center">
                                        <DocumentTextIcon className="w-8 h-8 text-blue-600 mr-3" />
                                        {certificateType.name}
                                    </h1>
                                    <p className="text-lg text-slate-600 mt-1">
                                        Who has this certificate type?
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <div className="bg-white p-4 rounded-lg shadow border border-slate-200">
                            <div className="flex items-center">
                                <UserGroupIcon className="w-6 h-6 text-blue-600 mr-2" />
                                <div>
                                    <p className="text-sm font-medium text-slate-600">Unique Employees</p>
                                    <p className="text-2xl font-bold text-slate-900">{stats.unique_employees}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-4 rounded-lg shadow border border-slate-200">
                            <div className="flex items-center">
                                <CheckCircleIcon className="w-6 h-6 text-green-600 mr-2" />
                                <div>
                                    <p className="text-sm font-medium text-slate-600">Active</p>
                                    <p className="text-2xl font-bold text-green-600">{stats.active_certificates}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-4 rounded-lg shadow border border-slate-200">
                            <div className="flex items-center">
                                <ExclamationTriangleIcon className="w-6 h-6 text-yellow-600 mr-2" />
                                <div>
                                    <p className="text-sm font-medium text-slate-600">Expiring Soon</p>
                                    <p className="text-2xl font-bold text-yellow-600">{stats.expiring_soon_certificates}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-4 rounded-lg shadow border border-slate-200">
                            <div className="flex items-center">
                                <XCircleIcon className="w-6 h-6 text-red-600 mr-2" />
                                <div>
                                    <p className="text-sm font-medium text-slate-600">Expired</p>
                                    <p className="text-2xl font-bold text-red-600">{stats.expired_certificates}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-4 rounded-lg shadow border border-slate-200">
                            <div className="flex items-center">
                                <DocumentTextIcon className="w-6 h-6 text-purple-600 mr-2" />
                                <div>
                                    <p className="text-sm font-medium text-slate-600">Total Certificates</p>
                                    <p className="text-2xl font-bold text-purple-600">{stats.total_certificates}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow border border-slate-200 p-4 mb-6">
                        <div className="flex flex-col lg:flex-row lg:items-center space-y-4 lg:space-y-0 lg:space-x-4">
                            {/* Search */}
                            <div className="flex-1">
                                <div className="relative">
                                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400" />
                                    <input
                                        type="text"
                                        value={filters.search}
                                        onChange={(e) => handleFilterChange('search', e.target.value)}
                                        placeholder="Search employees by name or ID..."
                                        className="pl-10 pr-4 py-2 w-full border border-slate-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                            </div>

                            {/* Status Filter */}
                            <select
                                value={filters.status}
                                onChange={(e) => handleFilterChange('status', e.target.value)}
                                className="border border-slate-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="expiring_soon">Expiring Soon</option>
                                <option value="expired">Expired</option>
                                <option value="pending">Pending</option>
                            </select>

                            {/* Department Filter */}
                            <select
                                value={filters.department_id}
                                onChange={(e) => handleFilterChange('department_id', e.target.value)}
                                className="border border-slate-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">All Departments</option>
                                {departments.map(dept => (
                                    <option key={dept.id} value={dept.id}>{dept.name}</option>
                                ))}
                            </select>

                            {/* Clear Filters */}
                            {(filters.search || filters.status || filters.department_id) && (
                                <button
                                    onClick={() => setFilters({status: '', department_id: '', search: ''})}
                                    className="text-slate-500 hover:text-slate-700 text-sm px-3 py-2 border border-slate-300 rounded-md"
                                >
                                    Clear Filters
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Employees List */}
                    <div className="bg-white rounded-lg shadow border border-slate-200">
                        <div className="px-6 py-4 border-b border-slate-200">
                            <h2 className="text-lg font-semibold text-slate-900">
                                Employees with this Certificate ({filteredEmployees.length})
                            </h2>
                        </div>

                        {filteredEmployees.length === 0 ? (
                            <div className="p-8 text-center text-slate-500">
                                <UserGroupIcon className="w-12 h-12 text-slate-300 mx-auto mb-4" />
                                <p>No employees found with this certificate type.</p>
                                {(filters.search || filters.status || filters.department_id) && (
                                    <p className="text-sm mt-2">Try adjusting your filters.</p>
                                )}
                            </div>
                        ) : (
                            <div className="divide-y divide-slate-200">
                                {filteredEmployees.map((employeeData) => {
                                    const employee = employeeData.employee;
                                    const latestCert = employeeData.latest_certificate;

                                    return (
                                        <div key={employee.id} className="p-6 hover:bg-slate-50 transition-colors">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center space-x-4">
                                                    {/* Employee Avatar */}
                                                    <div className="w-12 h-12 bg-slate-600 rounded-full flex items-center justify-center">
                                                        <span className="text-white font-bold text-lg">
                                                            {employee.name.charAt(0).toUpperCase()}
                                                        </span>
                                                    </div>

                                                    {/* Employee Info */}
                                                    <div>
                                                        <h3 className="text-lg font-semibold text-slate-900">
                                                            {employee.name}
                                                        </h3>
                                                        <div className="flex items-center space-x-4 text-sm text-slate-500">
                                                            <div className="flex items-center">
                                                                <IdentificationIcon className="w-4 h-4 mr-1" />
                                                                {employee.employee_id || 'N/A'}
                                                            </div>
                                                            <div className="flex items-center">
                                                                <BuildingOfficeIcon className="w-4 h-4 mr-1" />
                                                                {employee.department}
                                                            </div>
                                                            <span>{employee.position}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="flex items-center space-x-6">
                                                    {/* Certificate Info */}
                                                    {latestCert ? (
                                                        <div className="text-right">
                                                            <div className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${getStatusBadge(latestCert.status)}`}>
                                                                {getStatusIcon(latestCert.status)}
                                                                <span className="ml-1 uppercase">{latestCert.status.replace('_', ' ')}</span>
                                                            </div>
                                                            <p className="text-sm text-slate-500 mt-1">
                                                                Cert #: {latestCert.certificate_number}
                                                            </p>
                                                            <p className="text-sm text-slate-500">
                                                                {latestCert.issue_date} - {latestCert.expiry_date || 'No expiry'}
                                                            </p>
                                                        </div>
                                                    ) : (
                                                        <div className="text-right">
                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                                                <ClockIcon className="w-4 h-4 mr-1" />
                                                                NO CERTIFICATE
                                                            </span>
                                                        </div>
                                                    )}

                                                    {/* Actions */}
                                                    <div className="flex items-center space-x-2">
                                                        <Link
                                                            href={route('employee-containers.show', employee.id)}
                                                            className="inline-flex items-center px-3 py-1 border border-slate-300 shadow-sm text-sm leading-4 font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50"
                                                        >
                                                            <EyeIcon className="w-4 h-4 mr-1" />
                                                            View Container
                                                        </Link>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
