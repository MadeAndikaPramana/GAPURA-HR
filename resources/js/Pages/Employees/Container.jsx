// resources/js/Pages/Employees/Container.jsx
// Employee Container View - Digital Folder Interface (FIXED)

import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon,
    UserIcon,
    IdentificationIcon,
    PlusIcon,
    DocumentIcon,
    EyeIcon,
    ArrowDownTrayIcon, // ✅ FIXED: Changed from DownloadIcon
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ClockIcon,
    XCircleIcon,
    FolderIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    DocumentTextIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowPathIcon,
    ChevronUpIcon,
    ChevronDownIcon
} from '@heroicons/react/24/outline';

// Grid View Component for Employee Containers
function ContainerGridView({ auth, containers, statistics, departments, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters?.department_id || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [sortBy, setSortBy] = useState(filters?.sort_by || 'name');
    const [sortDirection, setSortDirection] = useState(filters?.sort_direction || 'asc');
    const [showFilters, setShowFilters] = useState(false);

    const handleSearch = () => {
        const params = {
            search: searchTerm || undefined,
            department_id: selectedDepartment || undefined,
            status: selectedStatus || undefined,
            sort_by: sortBy || undefined,
            sort_direction: sortDirection || undefined,
        };

        // Remove undefined values
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
        setSortBy('name');
        setSortDirection('asc');
        router.get(route('employee-containers.index'));
    };

    const handleSort = (field) => {
        const newDirection = sortBy === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortBy(field);
        setSortDirection(newDirection);

        const params = {
            search: searchTerm || undefined,
            department_id: selectedDepartment || undefined,
            status: selectedStatus || undefined,
            sort_by: field,
            sort_direction: newDirection,
        };

        Object.keys(params).forEach(key => {
            if (!params[key]) delete params[key];
        });

        router.get(route('employee-containers.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Employee Containers" />
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="md:flex md:items-center md:justify-between">
                        <div className="flex-1 min-w-0">
                            <h2 className="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                                Employee Containers
                            </h2>
                            <p className="mt-1 text-sm text-gray-500">
                                Digital employee file containers ({containers?.total || 0} total)
                            </p>
                        </div>
                        <div className="flex items-center space-x-3">
                            <button
                                onClick={() => setShowFilters(!showFilters)}
                                className={`inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ${
                                    showFilters ? 'bg-gray-100' : ''
                                }`}
                            >
                                <FunnelIcon className="w-4 h-4 mr-2" />
                                Filters
                            </button>
                        </div>
                    </div>

                    {/* Search and Filter Bar */}
                    <div className={`mt-6 transition-all duration-200 ${showFilters ? 'block' : 'hidden'}`}>
                        <div className="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                {/* Search */}
                                <div className="lg:col-span-2">
                                    <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-1">
                                        Search Employees
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            type="text"
                                            id="search"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Search by name, employee ID, position..."
                                        />
                                    </div>
                                </div>

                                {/* Department Filter */}
                                <div>
                                    <label htmlFor="department" className="block text-sm font-medium text-gray-700 mb-1">
                                        Department
                                    </label>
                                    <select
                                        id="department"
                                        value={selectedDepartment}
                                        onChange={(e) => setSelectedDepartment(e.target.value)}
                                        className="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">All Departments</option>
                                        {departments?.map(dept => (
                                            <option key={dept.id} value={dept.id}>
                                                {dept.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Status Filter */}
                                <div>
                                    <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-1">
                                        Status
                                    </label>
                                    <select
                                        id="status"
                                        value={selectedStatus}
                                        onChange={(e) => setSelectedStatus(e.target.value)}
                                        className="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">All Status</option>
                                        <option value="has_expired">Has Expired Certificates</option>
                                        <option value="expiring_soon">Certificates Expiring Soon</option>
                                        <option value="active">Has Active Certificates</option>
                                        <option value="no_certificates">No Certificates</option>
                                        <option value="no_background_check">No Background Check</option>
                                    </select>
                                </div>
                            </div>

                            {/* Action Buttons */}
                            <div className="mt-4 flex items-center justify-between">
                                <div className="flex items-center space-x-2">
                                    <button
                                        onClick={handleSearch}
                                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        <MagnifyingGlassIcon className="w-4 h-4 mr-2" />
                                        Search
                                    </button>
                                    <button
                                        onClick={resetFilters}
                                        className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        <ArrowPathIcon className="w-4 h-4 mr-2" />
                                        Reset
                                    </button>
                                </div>

                                <div className="text-sm text-gray-500">
                                    Showing {containers?.from || 0} - {containers?.to || 0} of {containers?.total || 0} results
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Quick Search (always visible) */}
                    {!showFilters && (
                        <div className="mt-6">
                            <div className="relative max-w-md">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Quick search employees..."
                                />
                            </div>
                        </div>
                    )}

                    {/* Sort Controls */}
                    <div className="mt-4 flex items-center space-x-4">
                        <span className="text-sm font-medium text-gray-700">Sort by:</span>
                        <div className="flex items-center space-x-2">
                            {[
                                { key: 'name', label: 'Name' },
                                { key: 'department', label: 'Department' },
                                { key: 'certificates_count', label: 'Certificates' },
                                { key: 'status', label: 'Status' }
                            ].map(option => (
                                <button
                                    key={option.key}
                                    onClick={() => handleSort(option.key)}
                                    className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
                                        sortBy === option.key
                                            ? 'bg-blue-100 text-blue-800'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    {option.label}
                                    {sortBy === option.key && (
                                        sortDirection === 'asc' ?
                                            <ChevronUpIcon className="w-4 h-4 ml-1" /> :
                                            <ChevronDownIcon className="w-4 h-4 ml-1" />
                                    )}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Statistics Cards */}
                    <div className="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <UserIcon className="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Employees
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {statistics?.employees?.total || 0}
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
                                        <CheckCircleIcon className="h-6 w-6 text-green-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Active Certificates
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {statistics?.certificates?.active || 0}
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
                                        <ClockIcon className="h-6 w-6 text-yellow-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Expiring Soon
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {statistics?.certificates?.expiring_soon || 0}
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
                                        <XCircleIcon className="h-6 w-6 text-red-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Expired
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {statistics?.certificates?.expired || 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Employee Container Grid */}
                    <div className="mt-8">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {containers?.data?.map((container) => (
                                <div key={container.id} className="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
                                    <div className="p-6">
                                        {/* Employee Info */}
                                        <div className="flex items-center">
                                            <div className="flex-shrink-0">
                                                <UserIcon className="h-10 w-10 text-gray-400" />
                                            </div>
                                            <div className="ml-4 flex-1">
                                                <p className="text-sm font-medium text-gray-900 truncate">
                                                    {container.name}
                                                </p>
                                                <p className="text-sm text-gray-500 truncate">
                                                    {container.employee_id}
                                                </p>
                                            </div>
                                        </div>

                                        {/* Department & Position */}
                                        <div className="mt-4">
                                            <p className="text-sm text-gray-500">
                                                {container.position}
                                            </p>
                                            <p className="text-sm text-gray-400">
                                                {container.department}
                                            </p>
                                        </div>

                                        {/* Container Status */}
                                        <div className="mt-4 flex items-center justify-between">
                                            <div className="flex items-center space-x-2">
                                                <span className="text-lg">
                                                    {container.container_status?.icon || '⚪'}
                                                </span>
                                                <span className="text-sm text-gray-600">
                                                    {container.container_status?.label || 'Unknown'}
                                                </span>
                                            </div>
                                        </div>


                                        {/* Actions */}
                                        <div className="mt-6">
                                            <Link
                                                href={container.container_url}
                                                className="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            >
                                                <FolderIcon className="w-4 h-4 mr-2" />
                                                Open Container
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Pagination */}
                        {containers?.links && (
                            <div className="mt-6">
                                <nav className="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                                    <div className="flex flex-1 justify-between sm:hidden">
                                        {containers.prev_page_url && (
                                            <Link
                                                href={containers.prev_page_url}
                                                className="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                            >
                                                Previous
                                            </Link>
                                        )}
                                        {containers.next_page_url && (
                                            <Link
                                                href={containers.next_page_url}
                                                className="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                            >
                                                Next
                                            </Link>
                                        )}
                                    </div>
                                    <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-gray-700">
                                                Showing{' '}
                                                <span className="font-medium">{containers.from || 0}</span>
                                                {' '}to{' '}
                                                <span className="font-medium">{containers.to || 0}</span>
                                                {' '}of{' '}
                                                <span className="font-medium">{containers.total || 0}</span>
                                                {' '}results
                                            </p>
                                        </div>
                                    </div>
                                </nav>
                            </div>
                        )}

                        {/* Empty State */}
                        {(!containers?.data || containers.data.length === 0) && (
                            <div className="text-center py-12">
                                <FolderIcon className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-2 text-sm font-medium text-gray-900">No employee containers</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    No employee containers found.
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

export default function Container({ auth, containers = null, employee = {}, statistics = {}, profile = {}, departments = [], filters = {} }) {
    const [activeTab, setActiveTab] = useState('certificates');
    const [showAddCertificate, setShowAddCertificate] = useState(false);

    // If containers prop exists, this is the grid view
    if (containers) {
        return <ContainerGridView
            auth={auth}
            containers={containers}
            statistics={statistics}
            departments={departments}
            filters={filters}
        />;
    }

    // Early return if employee data is not available for individual view
    if (!employee || !employee.id) {
        return (
            <AuthenticatedLayout user={auth.user}>
                <Head title="Employee Container" />
                <div className="py-6">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center py-12">
                            <ExclamationTriangleIcon className="mx-auto h-12 w-12 text-red-500" />
                            <h3 className="mt-2 text-lg font-medium text-gray-900">Employee Not Found</h3>
                            <p className="mt-1 text-sm text-gray-500">
                                The requested employee container could not be loaded.
                            </p>
                            <div className="mt-6">
                                <Link
                                    href={route('employee-containers.index')}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                    Back to Containers
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    // Status badge helpers
    const getStatusBadge = (status) => {
        const configs = {
            active: { bg: 'bg-green-100', text: 'text-green-800', icon: CheckCircleIcon, label: 'ACTIVE' },
            expired: { bg: 'bg-red-100', text: 'text-red-800', icon: XCircleIcon, label: 'EXPIRED' },
            expiring_soon: { bg: 'bg-yellow-100', text: 'text-yellow-800', icon: ClockIcon, label: 'EXPIRING SOON' },
            pending: { bg: 'bg-blue-100', text: 'text-blue-800', icon: ClockIcon, label: 'PENDING' },
            completed: { bg: 'bg-green-100', text: 'text-green-800', icon: CheckCircleIcon, label: 'COMPLETED' }
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
            <Head title={`Employee Container - ${employee.name || 'Unknown'}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                <Link
                                    href={route('employee-containers.index')}
                                    className="btn-secondary"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                    Back to Containers
                                </Link>
                                <div>
                                    <h1 className="text-3xl font-bold text-slate-900 flex items-center">
                                        <div className="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mr-4">
                                            <span className="text-white font-bold text-lg">
                                                {employee.name ? employee.name.charAt(0).toUpperCase() : 'U'}
                                            </span>
                                        </div>
                                        {employee.name || 'Unknown Employee'}
                                    </h1>
                                    <p className="text-lg text-slate-600 mt-1">
                                        {profile?.position || 'No Position'} • {profile?.department || 'No Department'}
                                    </p>
                                </div>
                            </div>

                            {/* Container Statistics */}
                            <div className="bg-white rounded-lg border border-slate-200 p-4">
                                <div className="grid grid-cols-4 gap-4 text-center">
                                    <div>
                                        <div className="text-2xl font-bold text-green-600">{statistics?.active || 0}</div>
                                        <div className="text-xs text-slate-500">Active</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-yellow-600">{statistics?.expiring_soon || 0}</div>
                                        <div className="text-xs text-slate-500">Expiring</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-red-600">{statistics?.expired || 0}</div>
                                        <div className="text-xs text-slate-500">Expired</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-blue-600">{statistics?.total || 0}</div>
                                        <div className="text-xs text-slate-500">Total</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Employee Profile Card */}
                    <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="flex items-center">
                                <IdentificationIcon className="w-5 h-5 text-slate-400 mr-3" />
                                <div>
                                    <p className="text-sm font-medium text-slate-700">Employee ID</p>
                                    <p className="text-slate-900">{employee.employee_id || employee.nip || 'N/A'}</p>
                                </div>
                            </div>
                            <div className="flex items-center">
                                <BuildingOfficeIcon className="w-5 h-5 text-slate-400 mr-3" />
                                <div>
                                    <p className="text-sm font-medium text-slate-700">Department</p>
                                    <p className="text-slate-900">{profile?.department || 'No Department'}</p>
                                </div>
                            </div>
                            <div className="flex items-center">
                                <CalendarIcon className="w-5 h-5 text-slate-400 mr-3" />
                                <div>
                                    <p className="text-sm font-medium text-slate-700">Hire Date</p>
                                    <p className="text-slate-900">{profile?.hire_date || 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Container Tabs */}
                    <div className="bg-white rounded-lg shadow-sm border border-slate-200">
                        {/* Tab Navigation */}
                        <div className="border-b border-slate-200">
                            <nav className="flex space-x-8 px-6" aria-label="Tabs">
                                <button
                                    onClick={() => setActiveTab('certificates')}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === 'certificates'
                                            ? 'border-green-500 text-green-600'
                                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                    }`}
                                >
                                    <DocumentTextIcon className="w-5 h-5 inline mr-2" />
                                    Certificates ({statistics?.total || 0})
                                </button>
                                <button
                                    onClick={() => setActiveTab('background-check')}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === 'background-check'
                                            ? 'border-green-500 text-green-600'
                                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                    }`}
                                >
                                    <DocumentIcon className="w-5 h-5 inline mr-2" />
                                    Background Check
                                </button>
                            </nav>
                        </div>

                        {/* Tab Content */}
                        <div className="p-6">
                            {activeTab === 'certificates' && (
                                <div>
                                    {/* Add Certificate Button */}
                                    <div className="flex justify-between items-center mb-6">
                                        <h3 className="text-lg font-semibold text-slate-900">Training Certificates</h3>
                                        <button
                                            onClick={() => setShowAddCertificate(true)}
                                            className="btn-primary"
                                        >
                                            <PlusIcon className="w-4 h-4 mr-2" />
                                            Add Certificate
                                        </button>
                                    </div>

                                    {/* Certificates List */}
                                    {employee.employee_certificates?.length > 0 ? (
                                        <div className="space-y-4">
                                            {employee.employee_certificates.map((certificate) => (
                                                <div
                                                    key={certificate.id}
                                                    className="border border-slate-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex-1">
                                                            <div className="flex items-center">
                                                                <h4 className="text-lg font-medium text-slate-900 mr-3">
                                                                    {certificate.certificate_type?.name || 'Unknown Certificate'}
                                                                </h4>
                                                                {getStatusBadge(certificate.status)}
                                                            </div>
                                                            <p className="text-sm text-slate-500 mt-1">
                                                                Certificate #: {certificate.certificate_number || 'N/A'}
                                                            </p>
                                                            <p className="text-sm text-slate-500">
                                                                Issued: {certificate.issue_date || 'N/A'}
                                                                {certificate.expiry_date && ` • Expires: ${certificate.expiry_date}`}
                                                            </p>
                                                        </div>
                                                        <div className="flex items-center space-x-2">
                                                            <button className="btn-secondary">
                                                                <EyeIcon className="w-4 h-4" />
                                                            </button>
                                                            <button className="btn-secondary">
                                                                <ArrowDownTrayIcon className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center py-12">
                                            <DocumentTextIcon className="mx-auto h-12 w-12 text-slate-400" />
                                            <h3 className="mt-2 text-sm font-medium text-slate-900">No certificates</h3>
                                            <p className="mt-1 text-sm text-slate-500">
                                                Get started by adding a training certificate.
                                            </p>
                                            <div className="mt-6">
                                                <button
                                                    onClick={() => setShowAddCertificate(true)}
                                                    className="btn-primary"
                                                >
                                                    <PlusIcon className="w-4 h-4 mr-2" />
                                                    Add First Certificate
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {activeTab === 'background-check' && (
                                <div>
                                    <div className="flex justify-between items-center mb-6">
                                        <h3 className="text-lg font-semibold text-slate-900">Background Check Documents</h3>
                                        <button className="btn-primary">
                                            <PlusIcon className="w-4 h-4 mr-2" />
                                            Upload Documents
                                        </button>
                                    </div>

                                    {/* Background Check Status */}
                                    <div className="bg-slate-50 rounded-lg p-6 text-center">
                                        {statistics?.has_background_check ? (
                                            <div>
                                                <CheckCircleIcon className="mx-auto h-12 w-12 text-green-500" />
                                                <h3 className="mt-2 text-sm font-medium text-slate-900">Background Check Complete</h3>
                                                <p className="mt-1 text-sm text-slate-500">
                                                    Background check documents are on file.
                                                </p>
                                            </div>
                                        ) : (
                                            <div>
                                                <ExclamationTriangleIcon className="mx-auto h-12 w-12 text-yellow-500" />
                                                <h3 className="mt-2 text-sm font-medium text-slate-900">No Background Check</h3>
                                                <p className="mt-1 text-sm text-slate-500">
                                                    No background check documents have been uploaded yet.
                                                </p>
                                                <div className="mt-6">
                                                    <button className="btn-primary">
                                                        <PlusIcon className="w-4 h-4 mr-2" />
                                                        Upload Background Check
                                                    </button>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
