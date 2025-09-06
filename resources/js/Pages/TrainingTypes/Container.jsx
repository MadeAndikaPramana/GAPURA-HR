// resources/js/Pages/TrainingTypes/Container.jsx
// "Certificate jenis ini dimiliki siapa saja" view

import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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
    EyeIcon
} from '@heroicons/react/24/outline';

export default function TrainingTypeContainer({
    auth,
    certificateType,
    containerData,
    departments,
    breadcrumb
}) {
    const [employees, setEmployees] = useState(containerData.employees || []);
    const [loading, setLoading] = useState(false);
    const [filters, setFilters] = useState({
        status: '',
        department_id: '',
        search: ''
    });

    // Statistics from container data
    const stats = containerData.statistics;

    // Load employees with filters
    const loadEmployees = async () => {
        setLoading(true);
        try {
            const response = await fetch(route('training-types.employees-list', certificateType.id) +
                '?' + new URLSearchParams(filters));
            const data = await response.json();
            setEmployees(data.employees);
        } catch (error) {
            console.error('Failed to load employees:', error);
        } finally {
            setLoading(false);
        }
    };

    // Handle filter changes
    const handleFilterChange = (key, value) => {
        setFilters(prev => ({
            ...prev,
            [key]: value
        }));
    };

    // Apply filters
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            loadEmployees();
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [filters]);

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
            <Head title={`${certificateType.name} - Who Has This Certificate`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Header */}
                    <div className="bg-white rounded-lg shadow border border-slate-200 p-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <Link
                                    href={route('training-types.index')}
                                    className="btn-secondary"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                    Back to Training Types
                                </Link>

                                <div>
                                    <h1 className="text-3xl font-bold text-slate-900 flex items-center">
                                        <div className="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                                            <DocumentTextIcon className="w-6 h-6 text-white" />
                                        </div>
                                        {certificateType.name}
                                    </h1>
                                    <p className="text-lg text-slate-600 mt-1">
                                        {certificateType.code} • {certificateType.category || 'No Category'}
                                    </p>
                                    {certificateType.description && (
                                        <p className="text-sm text-slate-500 mt-2 max-w-2xl">
                                            {certificateType.description}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Training Type Info */}
                            <div className="text-right">
                                <div className="flex items-center space-x-4 text-sm">
                                    {certificateType.is_mandatory && (
                                        <span className="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium">
                                            Mandatory
                                        </span>
                                    )}
                                    {certificateType.is_recurrent && (
                                        <span className="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                            Recurrent
                                        </span>
                                    )}
                                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                        certificateType.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800'
                                    }`}>
                                        {certificateType.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div className="bg-white rounded-lg border border-slate-200 p-4">
                            <div className="flex items-center">
                                <UserGroupIcon className="w-8 h-8 text-blue-600 mr-3" />
                                <div>
                                    <div className="text-2xl font-bold text-slate-900">{stats.unique_employees}</div>
                                    <div className="text-xs text-slate-500">Employees</div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg border border-slate-200 p-4">
                            <div className="flex items-center">
                                <CheckCircleIcon className="w-8 h-8 text-green-600 mr-3" />
                                <div>
                                    <div className="text-2xl font-bold text-green-600">{stats.active_certificates}</div>
                                    <div className="text-xs text-slate-500">Active</div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg border border-slate-200 p-4">
                            <div className="flex items-center">
                                <ExclamationTriangleIcon className="w-8 h-8 text-yellow-600 mr-3" />
                                <div>
                                    <div className="text-2xl font-bold text-yellow-600">{stats.expiring_soon_certificates}</div>
                                    <div className="text-xs text-slate-500">Expiring</div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg border border-slate-200 p-4">
                            <div className="flex items-center">
                                <XCircleIcon className="w-8 h-8 text-red-600 mr-3" />
                                <div>
                                    <div className="text-2xl font-bold text-red-600">{stats.expired_certificates}</div>
                                    <div className="text-xs text-slate-500">Expired</div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg border border-slate-200 p-4">
                            <div className="flex items-center">
                                <DocumentTextIcon className="w-8 h-8 text-slate-600 mr-3" />
                                <div>
                                    <div className="text-2xl font-bold text-slate-900">{stats.total_certificates}</div>
                                    <div className="text-xs text-slate-500">Total Certs</div>
                                </div>
                            </div>
                        </div>

                        {stats.compliance_rate !== null && (
                            <div className="bg-white rounded-lg border border-slate-200 p-4">
                                <div className="flex items-center">
                                    <div className={`w-8 h-8 rounded-full flex items-center justify-center mr-3 ${
                                        stats.compliance_rate >= 80 ? 'bg-green-100' :
                                        stats.compliance_rate >= 60 ? 'bg-yellow-100' : 'bg-red-100'
                                    }`}>
                                        <span className={`text-sm font-bold ${
                                            stats.compliance_rate >= 80 ? 'text-green-600' :
                                            stats.compliance_rate >= 60 ? 'text-yellow-600' : 'text-red-600'
                                        }`}>
                                            %
                                        </span>
                                    </div>
                                    <div>
                                        <div className={`text-2xl font-bold ${
                                            stats.compliance_rate >= 80 ? 'text-green-600' :
                                            stats.compliance_rate >= 60 ? 'text-yellow-600' : 'text-red-600'
                                        }`}>
                                            {stats.compliance_rate}%
                                        </div>
                                        <div className="text-xs text-slate-500">Compliance</div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow border border-slate-200 p-4">
                        <div className="flex items-center space-x-4">
                            <FunnelIcon className="w-5 h-5 text-slate-400" />

                            {/* Search */}
                            <div className="flex-1 max-w-md">
                                <div className="relative">
                                    <MagnifyingGlassIcon className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
                                    <input
                                        type="text"
                                        placeholder="Search employees..."
                                        value={filters.search}
                                        onChange={(e) => handleFilterChange('search', e.target.value)}
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
                                    className="text-slate-500 hover:text-slate-700 text-sm"
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
                                Employees with this Certificate ({employees.length})
                            </h2>
                        </div>

                        {loading ? (
                            <div className="p-8 text-center">
                                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                <p className="mt-2 text-slate-500">Loading employees...</p>
                            </div>
                        ) : employees.length === 0 ? (
                            <div className="p-8 text-center text-slate-500">
                                <UserGroupIcon className="w-12 h-12 text-slate-300 mx-auto mb-4" />
                                <p>No employees found with this certificate type.</p>
                                {(filters.search || filters.status || filters.department_id) && (
                                    <p className="text-sm mt-2">Try adjusting your filters.</p>
                                )}
                            </div>
                        ) : (
                            <div className="divide-y divide-slate-200">
                                {employees.map((employeeData) => {
                                    const employee = employeeData.employee;
                                    const latestCert = employeeData.latest_certificate;
                                    const history = employeeData.certificates_history;

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
                                                        <p className="text-sm text-slate-500">
                                                            {employee.employee_id} • {employee.position} • {employee.department}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="flex items-center space-x-6">
                                                    {/* Certificate Info */}
                                                    {latestCert ? (
                                                        <div className="text-right">
                                                            <div className="flex items-center space-x-2 mb-1">
                                                                <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border ${getStatusBadge(latestCert.status)}`}>
                                                                    {getStatusIcon(latestCert.status)}
                                                                    <span className="ml-1 capitalize">{latestCert.status.replace('_', ' ')}</span>
                                                                </span>
                                                            </div>
                                                            <p className="text-sm text-slate-600">
                                                                Issued: {latestCert.issue_date}
                                                                {latestCert.expiry_date && (
                                                                    <span> • Expires: {latestCert.expiry_date}</span>
                                                                )}
                                                            </p>
                                                            {latestCert.issuer && (
                                                                <p className="text-xs text-slate-500">
                                                                    by {latestCert.issuer}
                                                                </p>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <div className="text-right">
                                                            <span className="text-sm text-slate-500">No certificate data</span>
                                                        </div>
                                                    )}

                                                    {/* History Summary */}
                                                    <div className="text-center">
                                                        <div className="text-sm font-medium text-slate-900">
                                                            {history.total_count} Total
                                                        </div>
                                                        <div className="text-xs text-slate-500 space-x-2">
                                                            <span className="text-green-600">{history.active_count} Active</span>
                                                            {history.expired_count > 0 && (
                                                                <span className="text-red-600">{history.expired_count} Expired</span>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* View Container Button */}
                                                    <Link
                                                        href={employeeData.container_link}
                                                        className="btn-secondary"
                                                    >
                                                        <EyeIcon className="w-4 h-4 mr-2" />
                                                        View Container
                                                    </Link>
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
