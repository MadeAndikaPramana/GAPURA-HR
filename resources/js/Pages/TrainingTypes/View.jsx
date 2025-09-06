// resources/js/Pages/TrainingTypes/Container.jsx - Simple Container View

import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function TrainingTypeContainer({
    auth,
    certificateType,
    containerData = {},
    departments = [],
    breadcrumb = []
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

    // Get status badge styling
    const getStatusBadge = (status) => {
        const badges = {
            active: 'bg-green-100 text-green-800',
            expired: 'bg-red-100 text-red-800',
            expiring_soon: 'bg-yellow-100 text-yellow-800',
            pending: 'bg-gray-100 text-gray-800'
        };
        return badges[status] || badges.pending;
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`${certificateType.name} - Who Has This Certificate`} />

            <div className="min-h-screen bg-gray-50 py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Header */}
                    <div className="bg-white rounded-lg shadow-sm border p-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <Link
                                    href={route('training-types.index')}
                                    className="text-gray-600 hover:text-gray-900 font-medium"
                                >
                                    ← Back to Training Types
                                </Link>

                                <div>
                                    <h1 className="text-3xl font-bold text-gray-900">
                                        {certificateType.name}
                                    </h1>
                                    <p className="text-gray-600 mt-1">
                                        {certificateType.code} • {certificateType.category || 'No Category'}
                                    </p>
                                    {certificateType.description && (
                                        <p className="text-sm text-gray-500 mt-2 max-w-2xl">
                                            {certificateType.description}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Training Type Info */}
                            <div className="text-right">
                                <div className="flex items-center space-x-2">
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
                    <div className="grid grid-cols-2 md:grid-cols-6 gap-4">
                        <div className="bg-white rounded-lg border p-4">
                            <div className="text-2xl font-bold text-gray-900">{stats.unique_employees}</div>
                            <div className="text-sm text-gray-500">Employees</div>
                        </div>

                        <div className="bg-white rounded-lg border p-4">
                            <div className="text-2xl font-bold text-green-600">{stats.active_certificates}</div>
                            <div className="text-sm text-gray-500">Active</div>
                        </div>

                        <div className="bg-white rounded-lg border p-4">
                            <div className="text-2xl font-bold text-yellow-600">{stats.expiring_soon_certificates}</div>
                            <div className="text-sm text-gray-500">Expiring</div>
                        </div>

                        <div className="bg-white rounded-lg border p-4">
                            <div className="text-2xl font-bold text-red-600">{stats.expired_certificates}</div>
                            <div className="text-sm text-gray-500">Expired</div>
                        </div>

                        <div className="bg-white rounded-lg border p-4">
                            <div className="text-2xl font-bold text-gray-900">{stats.total_certificates}</div>
                            <div className="text-sm text-gray-500">Total Certs</div>
                        </div>

                        {stats.compliance_rate !== null && (
                            <div className="bg-white rounded-lg border p-4">
                                <div className={`text-2xl font-bold ${
                                    stats.compliance_rate >= 80 ? 'text-green-600' :
                                    stats.compliance_rate >= 60 ? 'text-yellow-600' : 'text-red-600'
                                }`}>
                                    {stats.compliance_rate}%
                                </div>
                                <div className="text-sm text-gray-500">Compliance</div>
                            </div>
                        )}
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow-sm border p-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">

                            {/* Search */}
                            <div>
                                <input
                                    type="text"
                                    placeholder="Search employees..."
                                    value={filters.search}
                                    onChange={(e) => setFilters({...filters, search: e.target.value})}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            {/* Status Filter */}
                            <div>
                                <select
                                    value={filters.status}
                                    onChange={(e) => setFilters({...filters, status: e.target.value})}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="expiring_soon">Expiring Soon</option>
                                    <option value="expired">Expired</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>

                            {/* Department Filter */}
                            <div>
                                <select
                                    value={filters.department_id}
                                    onChange={(e) => setFilters({...filters, department_id: e.target.value})}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">All Departments</option>
                                    {departments.map(dept => (
                                        <option key={dept.id} value={dept.id}>{dept.name}</option>
                                    ))}
                                </select>
                            </div>

                            {/* Clear Filters */}
                            <div>
                                {(filters.search || filters.status || filters.department_id) && (
                                    <button
                                        onClick={() => setFilters({status: '', department_id: '', search: ''})}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                                    >
                                        Clear Filters
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Employees List */}
                    <div className="bg-white rounded-lg shadow-sm border">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h2 className="text-lg font-semibold text-gray-900">
                                Employees with this Certificate ({filteredEmployees.length})
                            </h2>
                        </div>

                        {filteredEmployees.length === 0 ? (
                            <div className="p-8 text-center text-gray-500">
                                <div className="text-lg font-medium">No employees found with this certificate type.</div>
                                {(filters.search || filters.status || filters.department_id) && (
                                    <p className="text-sm mt-2">Try adjusting your filters.</p>
                                )}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
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
                                                Latest Certificate
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                History
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {filteredEmployees.map((employeeData) => {
                                            const employee = employeeData.employee;
                                            const latestCert = employeeData.latest_certificate;
                                            const history = employeeData.certificates_history;

                                            return (
                                                <tr key={employee.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4">
                                                        <div>
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {employee.name}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                {employee.employee_id} • {employee.position}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <div className="text-sm text-gray-900">
                                                            {employee.department || 'No Department'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        {latestCert ? (
                                                            <div className="text-sm">
                                                                <div className="text-gray-900">
                                                                    Issued: {latestCert.issue_date}
                                                                </div>
                                                                {latestCert.expiry_date && (
                                                                    <div className="text-gray-500">
                                                                        Expires: {latestCert.expiry_date}
                                                                    </div>
                                                                )}
                                                                {latestCert.issuer && (
                                                                    <div className="text-gray-500">
                                                                        by {latestCert.issuer}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-gray-500">No certificate data</span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        {latestCert ? (
                                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusBadge(latestCert.status)}`}>
                                                                {latestCert.status.replace('_', ' ')}
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                                No Certificate
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <div className="text-sm">
                                                            <div className="text-gray-900">
                                                                {history.total_count} Total
                                                            </div>
                                                            <div className="text-xs text-gray-500 space-x-2">
                                                                <span className="text-green-600">{history.active_count} Active</span>
                                                                {history.expired_count > 0 && (
                                                                    <span className="text-red-600">{history.expired_count} Expired</span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 text-right">
                                                        <Link
                                                            href={employeeData.container_link}
                                                            className="text-blue-600 hover:text-blue-900 text-sm font-medium"
                                                        >
                                                            View Container
                                                        </Link>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
