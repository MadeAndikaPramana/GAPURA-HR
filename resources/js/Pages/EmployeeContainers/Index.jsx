import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Pagination';
import PropTypes from 'prop-types';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowPathIcon,
    UserIcon,
    FolderIcon,
    DocumentTextIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ClockIcon,
    XCircleIcon,
    BuildingOfficeIcon,
    EyeIcon,
    ArrowDownTrayIcon
} from '@heroicons/react/24/outline';

// Container Card Component
function EmployeeContainerCard({ container, onView }) {
    const getStatusBadge = (status) => {
        const statusMap = {
            active: { icon: CheckCircleIcon, class: 'text-green-600 bg-green-50 border-green-200', label: 'Active' },
            expired: { icon: XCircleIcon, class: 'text-red-600 bg-red-50 border-red-200', label: 'Has Expired' },
            expiring_soon: { icon: ExclamationTriangleIcon, class: 'text-yellow-600 bg-yellow-50 border-yellow-200', label: 'Expiring Soon' },
            no_certificates: { icon: ClockIcon, class: 'text-gray-600 bg-gray-50 border-gray-200', label: 'No Certificates' }
        };

        const config = statusMap[status.toLowerCase()] || statusMap.no_certificates;
        const Icon = config.icon;

        return (
            <span className={`inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border ${config.class}`}>
                <Icon className="w-3 h-3 mr-1" />
                {config.label}
            </span>
        );
    };

    const getBackgroundCheckBadge = (bgCheck) => {
        if (bgCheck.status === 'cleared') {
            return (
                <span className="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium text-green-700 bg-green-50 border border-green-200">
                    <ShieldCheckIcon className="w-3 h-3 mr-1" />
                    BG Cleared
                </span>
            );
        } else if (bgCheck.status === 'in_progress') {
            return (
                <span className="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium text-yellow-700 bg-yellow-50 border border-yellow-200">
                    <ClockIcon className="w-3 h-3 mr-1" />
                    BG Pending
                </span>
            );
        } else {
            return (
                <span className="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium text-red-700 bg-red-50 border border-red-200">
                    <ExclamationTriangleIcon className="w-3 h-3 mr-1" />
                    No BG Check
                </span>
            );
        }
    };

    return (
        <div 
            className="group bg-white rounded-lg border border-gray-200 hover:border-blue-300 hover:shadow-lg transition-all duration-200 cursor-pointer"
            onClick={() => onView(container.id)}
        >
            {/* Card Header */}
            <div className="p-4 border-b border-gray-100">
                <div className="flex items-center space-x-3">
                    {/* Employee Avatar */}
                    <div className="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                        {container.profile_photo_path ? (
                            <img 
                                src={container.profile_photo_path} 
                                alt={container.name}
                                className="w-12 h-12 rounded-full object-cover"
                            />
                        ) : (
                            <UserIcon className="w-6 h-6 text-blue-600" />
                        )}
                    </div>
                    
                    {/* Employee Info */}
                    <div className="flex-1 min-w-0">
                        <h3 className="text-sm font-medium text-gray-900 truncate group-hover:text-blue-600 transition-colors">
                            {container.name}
                        </h3>
                        <p className="text-xs text-gray-500 mt-1">
                            NIP: {container.employee_id}
                        </p>
                        <div className="flex items-center mt-1 text-xs text-gray-500">
                            <BuildingOfficeIcon className="w-3 h-3 mr-1" />
                            {container.department || 'No Department'}
                        </div>
                    </div>
                    
                    {/* Quick Actions */}
                    <div className="opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                            onClick={(e) => {
                                e.stopPropagation();
                                onView(container.id);
                            }}
                            className="p-1 rounded-full hover:bg-gray-100 transition-colors"
                            title="View Container"
                        >
                            <EyeIcon className="w-4 h-4 text-gray-600" />
                        </button>
                    </div>
                </div>
            </div>

            {/* Card Body - Statistics */}
            <div className="p-4">
                {/* Container Status */}
                <div className="flex items-center justify-between mb-3">
                    <span className="text-xs font-medium text-gray-700">Status</span>
                    {getStatusBadge(container.container_status?.label || 'no_certificates')}
                </div>

                {/* Background Check */}
                <div className="flex items-center justify-between mb-3">
                    <span className="text-xs font-medium text-gray-700">Background Check</span>
                    {getBackgroundCheckBadge(container.background_check_status)}
                </div>

                {/* Certificate Stats */}
                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <span className="text-xs text-gray-600 flex items-center">
                            <FolderIcon className="w-3 h-3 mr-1" />
                            Total Certificates
                        </span>
                        <span className="text-xs font-medium text-gray-900">
                            {container.certificates?.total || 0}
                        </span>
                    </div>
                    
                    {container.certificates?.active > 0 && (
                        <div className="flex items-center justify-between">
                            <span className="text-xs text-green-600">Active</span>
                            <span className="text-xs font-medium text-green-600">
                                {container.certificates.active}
                            </span>
                        </div>
                    )}
                    
                    {container.certificates?.expiring_soon > 0 && (
                        <div className="flex items-center justify-between">
                            <span className="text-xs text-yellow-600">Expiring Soon</span>
                            <span className="text-xs font-medium text-yellow-600">
                                {container.certificates.expiring_soon}
                            </span>
                        </div>
                    )}
                    
                    {container.certificates?.expired > 0 && (
                        <div className="flex items-center justify-between">
                            <span className="text-xs text-red-600">Expired</span>
                            <span className="text-xs font-medium text-red-600">
                                {container.certificates.expired}
                            </span>
                        </div>
                    )}
                </div>
            </div>

            {/* Card Footer */}
            <div className="px-4 py-3 bg-gray-50 border-t border-gray-100">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-2 text-xs text-gray-500">
                        <DocumentTextIcon className="w-3 h-3" />
                        <span>
                            {container.background_check_files_count || 0} files
                        </span>
                    </div>
                    <span className="text-xs text-gray-400">
                        Updated {container.last_updated}
                    </span>
                </div>
            </div>
        </div>
    );
}

EmployeeContainerCard.propTypes = {
    container: PropTypes.shape({
        id: PropTypes.number.isRequired,
        name: PropTypes.string.isRequired,
        employee_id: PropTypes.string.isRequired,
        department: PropTypes.string,
        profile_photo_path: PropTypes.string,
        container_status: PropTypes.object,
        background_check_status: PropTypes.object,
        certificates: PropTypes.object,
        background_check_files_count: PropTypes.number,
        last_updated: PropTypes.string
    }).isRequired,
    onView: PropTypes.func.isRequired
};

// Statistics Card Component
function StatisticsCard({ title, value, subtitle, icon: Icon, color = 'blue' }) {
    const colorClasses = {
        blue: 'text-blue-600 bg-blue-50',
        green: 'text-green-600 bg-green-50',
        yellow: 'text-yellow-600 bg-yellow-50',
        red: 'text-red-600 bg-red-50'
    };

    return (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
            <div className="flex items-center">
                <div className={`p-3 rounded-lg ${colorClasses[color]}`}>
                    <Icon className="w-6 h-6" />
                </div>
                <div className="ml-4">
                    <p className="text-2xl font-semibold text-gray-900">{value}</p>
                    <p className="text-sm font-medium text-gray-600">{title}</p>
                    {subtitle && <p className="text-xs text-gray-500 mt-1">{subtitle}</p>}
                </div>
            </div>
        </div>
    );
}

StatisticsCard.propTypes = {
    title: PropTypes.string.isRequired,
    value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    subtitle: PropTypes.string,
    icon: PropTypes.elementType.isRequired,
    color: PropTypes.oneOf(['blue', 'green', 'yellow', 'red'])
};

// Main Component
export default function Index({ auth, containers, statistics, departments = [], filters = {} }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department_id || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [sortBy, setSortBy] = useState(filters.sort_by || 'name');
    const [sortDirection, setSortDirection] = useState(filters.sort_direction || 'asc');
    const [loading, setLoading] = useState(false);
    const [showFilters, setShowFilters] = useState(false);

    // Handle search and filters
    const handleSearch = () => {
        setLoading(true);
        
        const params = {
            search: searchTerm || undefined,
            department_id: selectedDepartment || undefined,
            status: selectedStatus || undefined,
            sort_by: sortBy,
            sort_direction: sortDirection
        };

        // Remove undefined values
        Object.keys(params).forEach(key => {
            if (!params[key]) delete params[key];
        });

        router.get(route('employee-containers.index'), params, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setLoading(false)
        });
    };

    // Handle sort change
    const handleSort = (field) => {
        const newDirection = field === sortBy && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortBy(field);
        setSortDirection(newDirection);
        
        // Auto-apply sort
        setTimeout(() => {
            handleSearch();
        }, 100);
    };

    // Clear all filters
    const clearFilters = () => {
        setSearchTerm('');
        setSelectedDepartment('');
        setSelectedStatus('');
        setSortBy('name');
        setSortDirection('asc');
        
        router.get(route('employee-containers.index'), {}, {
            preserveState: true,
            onFinish: () => setLoading(false)
        });
    };

    // View container handler
    const handleViewContainer = (containerId) => {
        router.visit(route('employee-containers.show', containerId));
    };

    // Apply filters when Enter is pressed
    useEffect(() => {
        const handleKeyPress = (e) => {
            if (e.key === 'Enter' && e.target.matches('input[type="text"]')) {
                handleSearch();
            }
        };

        document.addEventListener('keypress', handleKeyPress);
        return () => document.removeEventListener('keypress', handleKeyPress);
    }, [searchTerm, selectedDepartment, selectedStatus, sortBy, sortDirection]);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Employee Containers
                    </h2>
                    <div className="flex items-center space-x-3">
                        <button
                            onClick={() => setShowFilters(!showFilters)}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <FunnelIcon className="w-4 h-4 mr-2" />
                            Filters
                        </button>
                        
                        <Link
                            href={route('employee-containers.export', filters)}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                            Export
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Employee Containers" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Statistics Overview */}
                    {statistics && (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <StatisticsCard
                                title="Total Employees"
                                value={statistics.employees?.total || 0}
                                subtitle={`${statistics.employees?.active || 0} active`}
                                icon={UserIcon}
                                color="blue"
                            />
                            <StatisticsCard
                                title="Active Certificates"
                                value={statistics.certificates?.active || 0}
                                subtitle={`${statistics.certificates?.total || 0} total`}
                                icon={CheckCircleIcon}
                                color="green"
                            />
                            <StatisticsCard
                                title="Expiring Soon"
                                value={statistics.certificates?.expiring_soon || 0}
                                subtitle="Need attention"
                                icon={ExclamationTriangleIcon}
                                color="yellow"
                            />
                            <StatisticsCard
                                title="Expired"
                                value={statistics.certificates?.expired || 0}
                                subtitle="Require renewal"
                                icon={XCircleIcon}
                                color="red"
                            />
                        </div>
                    )}

                    {/* Search and Filters */}
                    <div className="bg-white rounded-lg border border-gray-200 mb-6">
                        <div className="p-4 border-b border-gray-200">
                            <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                                {/* Search Bar */}
                                <div className="flex-1 max-w-lg">
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                        <input
                                            type="text"
                                            placeholder="Search by name, NIP, email, or position..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        />
                                    </div>
                                </div>
                                
                                {/* Action Buttons */}
                                <div className="flex items-center space-x-3">
                                    <button
                                        onClick={handleSearch}
                                        disabled={loading}
                                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {loading ? (
                                            <ArrowPathIcon className="w-4 h-4 mr-2 animate-spin" />
                                        ) : (
                                            <MagnifyingGlassIcon className="w-4 h-4 mr-2" />
                                        )}
                                        Search
                                    </button>
                                    
                                    {(searchTerm || selectedDepartment || selectedStatus) && (
                                        <button
                                            onClick={clearFilters}
                                            className="text-sm text-gray-600 hover:text-gray-900 underline"
                                        >
                                            Clear filters
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Advanced Filters */}
                        {showFilters && (
                            <div className="p-4 bg-gray-50 border-t border-gray-200">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    {/* Department Filter */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Department
                                        </label>
                                        <select
                                            value={selectedDepartment}
                                            onChange={(e) => setSelectedDepartment(e.target.value)}
                                            className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        >
                                            <option value="">All Departments</option>
                                            {departments.map((dept) => (
                                                <option key={dept.id} value={dept.id}>
                                                    {dept.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    {/* Status Filter */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Status
                                        </label>
                                        <select
                                            value={selectedStatus}
                                            onChange={(e) => setSelectedStatus(e.target.value)}
                                            className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        >
                                            <option value="">All Statuses</option>
                                            <option value="active">Has Active Certificates</option>
                                            <option value="expiring_soon">Has Expiring Certificates</option>
                                            <option value="has_expired">Has Expired Certificates</option>
                                            <option value="no_certificates">No Certificates</option>
                                            <option value="no_background_check">No Background Check</option>
                                        </select>
                                    </div>

                                    {/* Sort By */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Sort By
                                        </label>
                                        <select
                                            value={`${sortBy}-${sortDirection}`}
                                            onChange={(e) => {
                                                const [field, direction] = e.target.value.split('-');
                                                setSortBy(field);
                                                setSortDirection(direction);
                                            }}
                                            className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        >
                                            <option value="name-asc">Name (A-Z)</option>
                                            <option value="name-desc">Name (Z-A)</option>
                                            <option value="department-asc">Department (A-Z)</option>
                                            <option value="department-desc">Department (Z-A)</option>
                                            <option value="certificates_count-desc">Most Certificates</option>
                                            <option value="certificates_count-asc">Least Certificates</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Results Summary */}
                    {containers?.data && (
                        <div className="flex items-center justify-between mb-4">
                            <p className="text-sm text-gray-700">
                                Showing <span className="font-medium">{containers.from || 0}</span> to{' '}
                                <span className="font-medium">{containers.to || 0}</span> of{' '}
                                <span className="font-medium">{containers.total || 0}</span> employee containers
                            </p>
                            
                            {loading && (
                                <div className="flex items-center text-sm text-gray-500">
                                    <ArrowPathIcon className="w-4 h-4 mr-2 animate-spin" />
                                    Loading...
                                </div>
                            )}
                        </div>
                    )}

                    {/* Container Grid */}
                    {containers?.data?.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6 gap-6 mb-8">
                            {containers.data.map((container) => (
                                <EmployeeContainerCard
                                    key={container.id}
                                    container={container}
                                    onView={handleViewContainer}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <FolderIcon className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-2 text-sm font-medium text-gray-900">No containers found</h3>
                            <p className="mt-1 text-sm text-gray-500">
                                {searchTerm || selectedDepartment || selectedStatus
                                    ? 'Try adjusting your search criteria.'
                                    : 'No employee containers have been created yet.'}
                            </p>
                            {(searchTerm || selectedDepartment || selectedStatus) && (
                                <div className="mt-6">
                                    <button
                                        onClick={clearFilters}
                                        className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        Clear filters
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Pagination */}
                    {containers?.links && containers.links.length > 3 && (
                        <div className="mt-8">
                            <Pagination links={containers.links} />
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// PropTypes
Index.propTypes = {
    auth: PropTypes.shape({
        user: PropTypes.object.isRequired
    }).isRequired,
    containers: PropTypes.shape({
        data: PropTypes.array,
        links: PropTypes.array,
        from: PropTypes.number,
        to: PropTypes.number,
        total: PropTypes.number
    }),
    statistics: PropTypes.shape({
        employees: PropTypes.object,
        certificates: PropTypes.object,
        background_checks: PropTypes.object
    }),
    departments: PropTypes.array,
    filters: PropTypes.object
};