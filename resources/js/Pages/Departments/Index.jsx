import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    ArrowDownTrayIcon,
    BuildingOfficeIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    UsersIcon,
    ClipboardDocumentListIcon,
    ChartBarIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, departments, filters, stats }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('departments.index'), {
            search: searchTerm
        }, {
            preserveState: true,
            preserveScroll: true
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        router.get(route('departments.index'));
    };

    const deleteDepartment = (department) => {
        if (confirm('Are you sure you want to delete this department? This action cannot be undone.')) {
            router.delete(route('departments.destroy', department.id));
        }
    };

    const getComplianceColor = (rate) => {
        if (rate >= 90) return 'text-green-600';
        if (rate >= 80) return 'text-yellow-600';
        if (rate >= 70) return 'text-orange-600';
        return 'text-red-600';
    };

    const getComplianceBadgeColor = (rate) => {
        if (rate >= 90) return 'bg-green-100 text-green-800';
        if (rate >= 80) return 'bg-yellow-100 text-yellow-800';
        if (rate >= 70) return 'bg-orange-100 text-orange-800';
        return 'bg-red-100 text-red-800';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Department Management
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Kelola departemen dan unit organisasi GAPURA
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Link
                            href={route('departments.create')}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <PlusIcon className="w-4 h-4 mr-2" />
                            Tambah Department
                        </Link>
                        <Link
                            href={route('departments.export')}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                            Export Data
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Departments" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-blue-100 text-blue-600">
                                    <BuildingOfficeIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Departments</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.total_departments}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-green-100 text-green-600">
                                    <UsersIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Employees</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.total_employees}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-purple-100 text-purple-600">
                                    <ChartBarIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Active Departments</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.departments_with_employees}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-orange-100 text-orange-600">
                                    <ClipboardDocumentListIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Avg Employees/Dept</p>
                                    <p className="text-2xl font-bold text-gray-900">{stats.average_employees_per_department}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search */}
                    <div className="bg-white shadow rounded-lg p-6 mb-6">
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <div className="flex-1">
                                <div className="relative">
                                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                                    <input
                                        type="text"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        placeholder="Cari nama, kode, atau deskripsi department..."
                                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                            >
                                Search
                            </button>
                            {searchTerm && (
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                >
                                    Clear
                                </button>
                            )}
                        </form>
                    </div>

                    {/* Departments Table */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Department
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employees
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Training Certificates
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Compliance Rate
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {departments.data.map((department) => (
                                    <tr key={department.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {department.name}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    Code: {department.code}
                                                </div>
                                                {department.description && (
                                                    <div className="text-xs text-gray-400 mt-1 max-w-xs truncate">
                                                        {department.description}
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm text-gray-900">
                                                <div className="flex items-center">
                                                    <UsersIcon className="w-4 h-4 text-gray-400 mr-1" />
                                                    <span className="font-medium">{department.employees_count}</span>
                                                    <span className="text-gray-500 ml-1">total</span>
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    {department.active_employees_count} active
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm text-gray-900">
                                                <div>Total: {department.training_stats?.total_certificates || 0}</div>
                                                <div className="text-xs text-gray-500">
                                                    <span className="text-green-600">{department.training_stats?.active_certificates || 0} active</span>
                                                    {(department.training_stats?.expiring_certificates || 0) > 0 && (
                                                        <span className="text-yellow-600 ml-2">{department.training_stats.expiring_certificates} expiring</span>
                                                    )}
                                                    {(department.training_stats?.expired_certificates || 0) > 0 && (
                                                        <span className="text-red-600 ml-2">{department.training_stats.expired_certificates} expired</span>
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center">
                                                <span className={`text-sm font-medium ${getComplianceColor(department.compliance_rate)}`}>
                                                    {department.compliance_rate}%
                                                </span>
                                                <div className="ml-2 w-16 bg-gray-200 rounded-full h-2">
                                                    <div
                                                        className={`h-2 rounded-full ${
                                                            department.compliance_rate >= 90 ? 'bg-green-600' :
                                                            department.compliance_rate >= 80 ? 'bg-yellow-600' :
                                                            department.compliance_rate >= 70 ? 'bg-orange-600' : 'bg-red-600'
                                                        }`}
                                                        style={{ width: `${department.compliance_rate}%` }}
                                                    ></div>
                                                </div>
                                            </div>
                                            <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getComplianceBadgeColor(department.compliance_rate)} mt-1`}>
                                                {department.compliance_rate >= 90 ? 'Excellent' :
                                                 department.compliance_rate >= 80 ? 'Good' :
                                                 department.compliance_rate >= 70 ? 'Fair' : 'Needs Attention'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div className="flex items-center space-x-2">
                                                <Link
                                                    href={route('departments.show', department.id)}
                                                    className="text-green-600 hover:text-green-900"
                                                    title="View Details"
                                                >
                                                    <EyeIcon className="w-4 h-4" />
                                                </Link>
                                                <Link
                                                    href={route('departments.edit', department.id)}
                                                    className="text-blue-600 hover:text-blue-900"
                                                    title="Edit"
                                                >
                                                    <PencilIcon className="w-4 h-4" />
                                                </Link>
                                                <button
                                                    onClick={() => deleteDepartment(department)}
                                                    className="text-red-600 hover:text-red-900"
                                                    title="Delete"
                                                    disabled={department.employees_count > 0}
                                                >
                                                    <TrashIcon className={`w-4 h-4 ${department.employees_count > 0 ? 'opacity-50 cursor-not-allowed' : ''}`} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {departments.links && (
                            <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div className="flex-1 flex justify-between sm:hidden">
                                    {departments.links.prev && (
                                        <Link
                                            href={departments.links.prev}
                                            className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Previous
                                        </Link>
                                    )}
                                    {departments.links.next && (
                                        <Link
                                            href={departments.links.next}
                                            className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Next
                                        </Link>
                                    )}
                                </div>
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Showing <span className="font-medium">{departments.from}</span> to{' '}
                                            <span className="font-medium">{departments.to}</span> of{' '}
                                            <span className="font-medium">{departments.total}</span> results
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Empty State */}
                    {departments.data.length === 0 && (
                        <div className="bg-white shadow rounded-lg p-6 text-center">
                            <BuildingOfficeIcon className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-2 text-sm font-medium text-gray-900">No departments found</h3>
                            <p className="mt-1 text-sm text-gray-500">
                                Get started by creating a new department.
                            </p>
                            <div className="mt-6">
                                <Link
                                    href={route('departments.create')}
                                    className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <PlusIcon className="-ml-1 mr-2 h-5 w-5" />
                                    New Department
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
