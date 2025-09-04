// resources/js/Pages/Employees/Index.jsx - Container Data View with Icon/List Toggle

import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowPathIcon,
    Squares2X2Icon,
    ListBulletIcon,
    FolderIcon,
    UserIcon,
    BuildingOfficeIcon
} from '@heroicons/react/24/outline';
import {
    Squares2X2Icon as Squares2X2IconSolid,
    ListBulletIcon as ListBulletIconSolid
} from '@heroicons/react/24/solid';

export default function Index({ auth, employees, departments, filters = {} }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || '');
    const [viewMode, setViewMode] = useState('grid'); // 'grid' or 'list'

    const handleSearch = () => {
        const params = {
            search: searchTerm || undefined,
            department: selectedDepartment || undefined,
        };

        Object.keys(params).forEach(key => {
            if (!params[key]) delete params[key];
        });

        router.get(route('employees.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        setSearchTerm('');
        setSelectedDepartment('');
        router.get(route('employees.index'));
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    // Grid View Component
    const GridView = ({ employees }) => (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {employees?.data?.map((employee) => (
                <Link
                    key={employee.id}
                    href={route('employee-containers.show', employee.id)}
                    className="group bg-white rounded-xl border border-slate-200 p-6 hover:shadow-lg hover:border-green-300 transition-all duration-200 cursor-pointer"
                >
                    {/* Employee Avatar */}
                    <div className="flex justify-center mb-4">
                        <div className="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                            <span className="text-white font-bold text-xl">
                                {employee.name.charAt(0).toUpperCase()}
                            </span>
                        </div>
                    </div>

                    {/* Employee Info */}
                    <div className="text-center">
                        <h3 className="font-semibold text-slate-900 text-lg mb-1 group-hover:text-green-700 transition-colors">
                            {employee.name}
                        </h3>
                        <p className="text-sm text-slate-500 mb-2">
                            NIP: {employee.nip || employee.employee_id || 'N/A'}
                        </p>
                        <p className="text-sm text-slate-600 mb-3">
                            {employee.position || 'No Position'}
                        </p>

                        {/* Department Badge */}
                        {employee.department && (
                            <div className="inline-flex items-center px-3 py-1 bg-slate-100 rounded-full text-xs font-medium text-slate-700">
                                <BuildingOfficeIcon className="w-3 h-3 mr-1" />
                                {employee.department.name}
                            </div>
                        )}
                    </div>

                    {/* Container Indicator */}
                    <div className="mt-4 pt-4 border-t border-slate-100">
                        <div className="flex items-center justify-center text-xs text-slate-500">
                            <FolderIcon className="w-4 h-4 mr-1 group-hover:text-green-600 transition-colors" />
                            Container Data
                        </div>
                    </div>
                </Link>
            )) || []}
        </div>
    );

    // List View Component
    const ListView = ({ employees }) => (
        <div className="bg-white shadow rounded-lg overflow-hidden">
            <div className="min-w-full divide-y divide-slate-200">
                <table className="min-w-full divide-y divide-slate-200">
                    <thead className="bg-slate-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Employee
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Position
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                Department
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-slate-200">
                        {employees?.data?.map((employee) => (
                            <tr key={employee.id} className="hover:bg-slate-50 cursor-pointer">
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <Link
                                        href={route('employee-containers.show', employee.id)}
                                        className="flex items-center group"
                                    >
                                        <div className="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mr-4 group-hover:scale-105 transition-transform duration-200">
                                            <span className="text-white font-medium text-sm">
                                                {employee.name.charAt(0).toUpperCase()}
                                            </span>
                                        </div>
                                        <div>
                                            <div className="text-sm font-medium text-slate-900 group-hover:text-green-700 transition-colors">
                                                {employee.name}
                                            </div>
                                            <div className="text-sm text-slate-500">
                                                NIP: {employee.nip || employee.employee_id || 'N/A'}
                                            </div>
                                        </div>
                                    </Link>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <Link
                                        href={route('employee-containers.show', employee.id)}
                                        className="text-sm text-slate-900 hover:text-green-700 transition-colors"
                                    >
                                        {employee.position || 'No Position'}
                                    </Link>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <Link
                                        href={route('employee-containers.show', employee.id)}
                                        className="text-sm text-slate-900 hover:text-green-700 transition-colors"
                                    >
                                        {employee.department?.name || 'No Department'}
                                    </Link>
                                </td>
                            </tr>
                        )) || []}
                    </tbody>
                </table>
            </div>
        </div>
    );

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Container Data" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="md:flex md:items-center md:justify-between mb-8">
                        <div className="flex-1 min-w-0">
                            <h2 className="text-3xl font-bold leading-7 text-slate-900 sm:text-4xl">
                                Container Data
                            </h2>
                            <p className="mt-2 text-lg text-slate-600">
                                Digital employee folders with certificates and background check data
                            </p>
                        </div>

                        {/* View Toggle */}
                        <div className="mt-4 md:mt-0 md:ml-4">
                            <div className="flex items-center bg-slate-100 rounded-lg p-1">
                                <button
                                    onClick={() => setViewMode('grid')}
                                    className={`p-2 rounded-md transition-colors ${
                                        viewMode === 'grid'
                                            ? 'bg-white text-green-600 shadow-sm'
                                            : 'text-slate-500 hover:text-slate-700'
                                    }`}
                                    title="Grid View"
                                >
                                    {viewMode === 'grid' ? (
                                        <Squares2X2IconSolid className="w-5 h-5" />
                                    ) : (
                                        <Squares2X2Icon className="w-5 h-5" />
                                    )}
                                </button>
                                <button
                                    onClick={() => setViewMode('list')}
                                    className={`p-2 rounded-md transition-colors ${
                                        viewMode === 'list'
                                            ? 'bg-white text-green-600 shadow-sm'
                                            : 'text-slate-500 hover:text-slate-700'
                                    }`}
                                    title="List View"
                                >
                                    {viewMode === 'list' ? (
                                        <ListBulletIconSolid className="w-5 h-5" />
                                    ) : (
                                        <ListBulletIcon className="w-5 h-5" />
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white shadow-sm rounded-lg mb-8 border border-slate-200">
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="md:col-span-1">
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Search Employee
                                    </label>
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-5 h-5" />
                                        <input
                                            type="text"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            onKeyPress={handleKeyPress}
                                            placeholder="Name, NIP, position..."
                                            className="pl-10 pr-4 py-2.5 w-full border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                        />
                                    </div>
                                </div>

                                <div className="md:col-span-1">
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Department
                                    </label>
                                    <select
                                        value={selectedDepartment}
                                        onChange={(e) => setSelectedDepartment(e.target.value)}
                                        className="w-full py-2.5 px-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                    >
                                        <option value="">All Departments</option>
                                        {departments?.map((dept) => (
                                            <option key={dept.id} value={dept.id}>
                                                {dept.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="md:col-span-1 flex items-end space-x-3">
                                    <button
                                        onClick={handleSearch}
                                        className="flex-1 bg-green-600 text-white px-4 py-2.5 rounded-lg hover:bg-green-700 transition-colors font-medium"
                                    >
                                        <FunnelIcon className="w-5 h-5 mr-2 inline" />
                                        Filter
                                    </button>
                                    <button
                                        onClick={resetFilters}
                                        className="px-4 py-2.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors"
                                        title="Reset Filters"
                                    >
                                        <ArrowPathIcon className="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Results Info */}
                    <div className="flex items-center justify-between mb-6">
                        <div className="text-sm text-slate-600">
                            Showing {employees?.from || 0} to {employees?.to || 0} of {employees?.total || 0} containers
                        </div>
                        <div className="text-sm text-slate-500">
                            {viewMode === 'grid' ? 'Grid View' : 'List View'}
                        </div>
                    </div>

                    {/* Content */}
                    {employees?.data?.length > 0 ? (
                        <>
                            {viewMode === 'grid' ? (
                                <GridView employees={employees} />
                            ) : (
                                <ListView employees={employees} />
                            )}
                        </>
                    ) : (
                        <div className="text-center py-16">
                            <div className="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <FolderIcon className="w-12 h-12 text-slate-400" />
                            </div>
                            <h3 className="text-xl font-medium text-slate-900 mb-2">
                                No containers found
                            </h3>
                            <p className="text-slate-600 mb-6 max-w-md mx-auto">
                                No employee containers match your current search criteria. Try adjusting your filters or search terms.
                            </p>
                            <button
                                onClick={resetFilters}
                                className="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors font-medium"
                            >
                                Clear All Filters
                            </button>
                        </div>
                    )}

                    {/* Pagination */}
                    {employees?.links && employees?.data?.length > 0 && (
                        <div className="mt-8 flex items-center justify-center">
                            <div className="bg-white px-4 py-3 border border-slate-200 rounded-lg">
                                <div className="text-sm text-slate-700">
                                    Page {employees.current_page || 1} of {employees.last_page || 1}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
