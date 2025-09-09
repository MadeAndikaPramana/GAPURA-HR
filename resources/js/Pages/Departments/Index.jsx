// resources/js/Pages/Departments/Index.jsx
// Departments Management Page

import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    BuildingOfficeIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    UsersIcon,
    ChartBarIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, departments, stats, filters = {} }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    const handleSearch = () => {
        const params = {
            search: searchTerm || undefined,
        };

        Object.keys(params).forEach(key => {
            if (!params[key]) delete params[key];
        });

        router.get(route('departments.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        setSearchTerm('');
        router.get(route('departments.index'));
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Departments" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="md:flex md:items-center md:justify-between mb-8">
                        <div className="flex-1 min-w-0">
                            <h2 className="text-3xl font-bold leading-7 text-slate-900 sm:text-4xl flex items-center">
                                <BuildingOfficeIcon className="w-8 h-8 mr-3 text-blue-600" />
                                Departments
                            </h2>
                            <p className="mt-2 text-lg text-slate-600">
                                Manage organizational departments and their employees
                            </p>
                        </div>

                        <div className="mt-4 md:mt-0 md:ml-4">
                            <Link
                                href={route('departments.create')}
                                className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <PlusIcon className="w-4 h-4 mr-2" />
                                Add Department
                            </Link>
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <BuildingOfficeIcon className="h-6 w-6 text-blue-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Departments
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats?.total_departments || 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <UsersIcon className="h-6 w-6 text-green-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Employees
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats?.total_employees || 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ChartBarIcon className="h-6 w-6 text-purple-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Avg per Dept
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats?.average_employees_per_department || 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <BuildingOfficeIcon className="h-6 w-6 text-yellow-600" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                With Employees
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats?.departments_with_employees || 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Search */}
                    <div className="bg-white shadow rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex flex-col lg:flex-row lg:items-center space-y-4 lg:space-y-0 lg:space-x-4">
                                <div className="flex-1">
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                                        <input
                                            type="text"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            onKeyPress={handleKeyPress}
                                            placeholder="Search departments by name or code..."
                                            className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                </div>

                                <div className="flex space-x-2">
                                    <button
                                        onClick={handleSearch}
                                        className="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors font-medium"
                                    >
                                        Search
                                    </button>

                                    {searchTerm && (
                                        <button
                                            onClick={resetFilters}
                                            className="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors"
                                        >
                                            Clear
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Departments Table */}
                    <div className="bg-white shadow rounded-lg">
                        <div className="min-w-full divide-y divide-gray-200">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Department
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Code
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Employees
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {departments?.data?.map((department) => (
                                        <tr key={department.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <BuildingOfficeIcon className="h-5 w-5 text-gray-400 mr-3" />
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {department.name}
                                                        </div>
                                                        {department.description && (
                                                            <div className="text-sm text-gray-500">
                                                                {department.description}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    {department.code}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div className="flex items-center">
                                                    <UsersIcon className="h-4 w-4 text-gray-400 mr-1" />
                                                    {department.employees_count || 0} employees
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div className="flex items-center justify-end space-x-2">
                                                    <Link
                                                        href={route('departments.edit', department.id)}
                                                        className="text-indigo-600 hover:text-indigo-900"
                                                        title="Edit"
                                                    >
                                                        <PencilIcon className="w-4 h-4" />
                                                    </Link>

                                                    <button
                                                        onClick={() => {
                                                            if (confirm(`Are you sure you want to delete "${department.name}"?`)) {
                                                                router.delete(route('departments.destroy', department.id));
                                                            }
                                                        }}
                                                        className="text-red-600 hover:text-red-900"
                                                        title="Delete"
                                                    >
                                                        <TrashIcon className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    )) || []}
                                </tbody>
                            </table>
                        </div>

                        {/* Empty State */}
                        {(!departments?.data || departments.data.length === 0) && (
                            <div className="text-center py-12">
                                <BuildingOfficeIcon className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No departments found</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Get started by creating a new department.
                                </p>
                                <div className="mt-6">
                                    <Link
                                        href={route('departments.create')}
                                        className="btn-primary"
                                    >
                                        <PlusIcon className="w-4 h-4 mr-2" />
                                        Add First Department
                                    </Link>
                                </div>
                            </div>
                        )}

                        {/* Pagination */}
                        {departments?.links && departments?.data?.length > 0 && (
                            <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div className="flex-1 flex justify-between sm:hidden">
                                    {departments.prev_page_url && (
                                        <Link
                                            href={departments.prev_page_url}
                                            className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Previous
                                        </Link>
                                    )}
                                    {departments.next_page_url && (
                                        <Link
                                            href={departments.next_page_url}
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
                                            <span className="font-medium">{departments.from}</span>
                                            {' '}to{' '}
                                            <span className="font-medium">{departments.to}</span>
                                            {' '}of{' '}
                                            <span className="font-medium">{departments.total}</span>
                                            {' '}results
                                        </p>
                                    </div>
                                    <div>
                                        <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            {departments.links?.map((link, index) => (
                                                <Link
                                                    key={index}
                                                    href={link.url}
                                                    className={`relative inline-flex items-center px-2 py-2 border text-sm font-medium ${
                                                        link.active
                                                            ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    } ${
                                                        index === 0 ? 'rounded-l-md' : ''
                                                    } ${
                                                        index === departments.links.length - 1 ? 'rounded-r-md' : ''
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
