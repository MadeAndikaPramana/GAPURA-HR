import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon,
    UserIcon,
    IdentificationIcon,
    AcademicCapIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    EyeIcon,
    UsersIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    MagnifyingGlassIcon,
    ArrowPathIcon
} from '@heroicons/react/24/outline';

export default function Container({
    auth,
    certificateType,
    containerData = {},
    departments = []
}) {
    const [filters, setFilters] = useState({
        status: '',
        department_id: '',
        search: ''
    });

    // Get statistics
    const stats = containerData.statistics || {
        total_certificates: 0,
        active_certificates: 0,
        expired_certificates: 0,
        expiring_soon_certificates: 0,
        unique_employees: 0,
        compliance_rate: null
    };

    // Get employees list
    const employees = containerData.employees || [];

    // Filter employees based on current filters
    const filteredEmployees = employees.filter(employeeData => {
        const employee = employeeData.employee;
        const latestCert = employeeData.latest_certificate;

        // Status filter
        if (filters.status && (!latestCert || latestCert.status !== filters.status)) {
            return false;
        }

        // Department filter
        if (filters.department_id && employee.department_id !== parseInt(filters.department_id)) {
            return false;
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

    // Get status badge configuration
    const getStatusBadge = (status) => {
        const configs = {
            active: {
                bg: 'bg-green-100',
                text: 'text-green-800',
                icon: CheckCircleIcon,
                label: 'ACTIVE'
            },
            expired: {
                bg: 'bg-red-100',
                text: 'text-red-800',
                icon: XCircleIcon,
                label: 'EXPIRED'
            },
            expiring_soon: {
                bg: 'bg-yellow-100',
                text: 'text-yellow-800',
                icon: ClockIcon,
                label: 'EXPIRING SOON'
            },
            pending: {
                bg: 'bg-blue-100',
                text: 'text-blue-800',
                icon: ClockIcon,
                label: 'PENDING'
            }
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

    // Handle filter updates
    const updateFilters = (key, value) => {
        setFilters(prev => ({
            ...prev,
            [key]: value
        }));
    };

    const resetFilters = () => {
        setFilters({
            status: '',
            department_id: '',
            search: ''
        });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`${certificateType.name} - Certificates`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8">
                        {/* Breadcrumb */}
                        <nav className="flex mb-4" aria-label="Breadcrumb">
                            <ol className="inline-flex items-center space-x-1 md:space-x-3">
                                <li className="inline-flex items-center">
                                    <Link
                                        href={route('training-types.index')}
                                        className="inline-flex items-center text-sm font-medium text-slate-700 hover:text-green-600"
                                    >
                                        <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                        Training Types
                                    </Link>
                                </li>
                                <li>
                                    <div className="flex items-center">
                                        <span className="mx-2.5 text-slate-400">/</span>
                                        <span className="text-sm font-medium text-slate-500">
                                            {certificateType.name}
                                        </span>
                                    </div>
                                </li>
                            </ol>
                        </nav>

                        {/* Title Section */}
                        <div className="lg:flex lg:items-center lg:justify-between">
                            <div className="flex-1 min-w-0">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mr-4">
                                        <AcademicCapIcon className="w-8 h-8 text-white" />
                                    </div>
                                    <div>
                                        <h2 className="text-3xl font-bold leading-7 text-slate-900 sm:text-4xl">
                                            {certificateType.name}
                                        </h2>
                                        <p className="mt-1 text-lg text-slate-600">
                                            {certificateType.category && (
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-slate-100 text-slate-800">
                                                    {certificateType.category}
                                                </span>
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        {/* Total Employees */}
                        <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <UsersIcon className="w-8 h-8 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-slate-600">Total Employees</p>
                                    <p className="text-2xl font-bold text-slate-900">{stats.unique_employees}</p>
                                </div>
                            </div>
                        </div>

                        {/* Active Certificates */}
                        <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <CheckCircleIcon className="w-8 h-8 text-green-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-slate-600">Active Certificates</p>
                                    <p className="text-2xl font-bold text-slate-900">{stats.active_certificates}</p>
                                </div>
                            </div>
                        </div>

                        {/* Expired Certificates */}
                        <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <XCircleIcon className="w-8 h-8 text-red-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-slate-600">Expired Certificates</p>
                                    <p className="text-2xl font-bold text-slate-900">{stats.expired_certificates}</p>
                                </div>
                            </div>
                        </div>

                        {/* Expiring Soon */}
                        <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <ClockIcon className="w-8 h-8 text-yellow-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-slate-600">Expiring Soon</p>
                                    <p className="text-2xl font-bold text-slate-900">{stats.expiring_soon_certificates}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            {/* Search */}
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-2">
                                    Search Employee
                                </label>
                                <div className="relative">
                                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" />
                                    <input
                                        type="text"
                                        value={filters.search}
                                        onChange={(e) => updateFilters('search', e.target.value)}
                                        placeholder="Search by name or ID..."
                                        className="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                            </div>

                            {/* Status Filter */}
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-2">
                                    Certificate Status
                                </label>
                                <select
                                    value={filters.status}
                                    onChange={(e) => updateFilters('status', e.target.value)}
                                    className="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="expired">Expired</option>
                                    <option value="expiring_soon">Expiring Soon</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>

                            {/* Department Filter */}
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-2">
                                    Department
                                </label>
                                <select
                                    value={filters.department_id}
                                    onChange={(e) => updateFilters('department_id', e.target.value)}
                                    className="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                >
                                    <option value="">All Departments</option>
                                    {departments.map(dept => (
                                        <option key={dept.id} value={dept.id}>
                                            {dept.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Action Button */}
                            <div className="flex items-end">
                                <button
                                    onClick={resetFilters}
                                    className="w-full px-4 py-2.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors"
                                >
                                    <ArrowPathIcon className="w-5 h-5 mx-auto" />
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Results Info */}
                    <div className="flex items-center justify-between mb-6">
                        <div className="text-sm text-slate-600">
                            Showing {filteredEmployees.length} of {employees.length} employees
                        </div>
                    </div>

                    {/* Employee List */}
                    <div className="bg-white shadow-sm rounded-lg overflow-hidden">
                        {filteredEmployees.length > 0 ? (
                            <div className="divide-y divide-slate-200">
                                {filteredEmployees.map((employeeData, index) => {
                                    const employee = employeeData.employee;
                                    const latestCert = employeeData.latest_certificate;
                                    const history = employeeData.certificates_history || {};

                                    return (
                                        <div key={employee.id} className="p-6 hover:bg-slate-50 transition-colors">
                                            <div className="flex items-center justify-between">
                                                {/* Employee Info */}
                                                <div className="flex items-center space-x-4">
                                                    <div className="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-green-500 to-blue-600 rounded-full flex items-center justify-center">
                                                        <UserIcon className="w-6 h-6 text-white" />
                                                    </div>
                                                    <div>
                                                        <h3 className="text-lg font-semibold text-slate-900">
                                                            {employee.name}
                                                        </h3>
                                                        <div className="flex items-center space-x-4 text-sm text-slate-600">
                                                            <div className="flex items-center">
                                                                <IdentificationIcon className="w-4 h-4 mr-1" />
                                                                {employee.employee_id}
                                                            </div>
                                                            <div className="flex items-center">
                                                                <BuildingOfficeIcon className="w-4 h-4 mr-1" />
                                                                {employee.department}
                                                            </div>
                                                            {employee.position && (
                                                                <div className="flex items-center">
                                                                    <UserIcon className="w-4 h-4 mr-1" />
                                                                    {employee.position}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Certificate Status & Actions */}
                                                <div className="flex items-center space-x-4">
                                                    {/* Certificate Info */}
                                                    <div className="text-right">
                                                        {latestCert ? (
                                                            <div className="space-y-1">
                                                                {getStatusBadge(latestCert.status)}
                                                                {latestCert.expiry_date && (
                                                                    <div className="text-xs text-slate-500">
                                                                        <CalendarIcon className="w-3 h-3 inline mr-1" />
                                                                        Expires: {new Date(latestCert.expiry_date).toLocaleDateString()}
                                                                    </div>
                                                                )}
                                                                <div className="text-xs text-slate-500">
                                                                    Total: {history.total_count || 0} certificates
                                                                    {history.active_count > 0 && (
                                                                        <span className="ml-2 text-green-600">{history.active_count} Active</span>
                                                                    )}
                                                                    {history.expired_count > 0 && (
                                                                        <span className="ml-2 text-red-600">{history.expired_count} Expired</span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-slate-500">No certificates</span>
                                                        )}
                                                    </div>

                                                    {/* Action Button */}
                                                    <Link
                                                        href={route('employee-containers.show', employee.id)}
                                                        className="inline-flex items-center px-3 py-1 border border-slate-300 shadow-sm text-sm leading-4 font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 transition-colors"
                                                    >
                                                        <EyeIcon className="w-4 h-4 mr-1" />
                                                        View Container
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="text-center py-16">
                                <div className="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <UsersIcon className="w-12 h-12 text-slate-400" />
                                </div>
                                <h3 className="text-xl font-medium text-slate-900 mb-2">
                                    No employees found
                                </h3>
                                <p className="text-slate-600 max-w-md mx-auto">
                                    {filters.search || filters.status || filters.department_id ? (
                                        'No employees match your current filters. Try adjusting your search criteria.'
                                    ) : (
                                        'No employees have certificates for this training type yet.'
                                    )}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
