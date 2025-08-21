// resources/js/Pages/TrainingRecords/Index.jsx

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowDownTrayIcon,
    ClipboardDocumentListIcon,
    UserIcon,
    CalendarDaysIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, trainingRecords, employees, trainingTypes, departments, filters, stats }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [selectedEmployee, setSelectedEmployee] = useState(filters.employee_id || '');
    const [selectedTrainingType, setSelectedTrainingType] = useState(filters.training_type_id || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department_id || '');

    const handleSearch = () => {
        router.get(route('training-records.index'), {
            search: searchTerm,
            status: selectedStatus,
            employee_id: selectedEmployee,
            training_type_id: selectedTrainingType,
            department_id: selectedDepartment,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedStatus('');
        setSelectedEmployee('');
        setSelectedTrainingType('');
        setSelectedDepartment('');
        router.get(route('training-records.index'));
    };

    const exportRecords = () => {
        const params = new URLSearchParams({
            search: searchTerm,
            status: selectedStatus,
            employee_id: selectedEmployee,
            training_type_id: selectedTrainingType,
            department_id: selectedDepartment,
        });

        window.location.href = route('import-export.training-records.export') + '?' + params.toString();
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            active: {
                color: 'bg-green-100 text-green-800',
                icon: CheckCircleIcon,
                text: 'Active'
            },
            expiring_soon: {
                color: 'bg-yellow-100 text-yellow-800',
                icon: ExclamationTriangleIcon,
                text: 'Expiring Soon'
            },
            expired: {
                color: 'bg-red-100 text-red-800',
                icon: XCircleIcon,
                text: 'Expired'
            }
        };

        const config = statusConfig[status] || statusConfig.active;
        const IconComponent = config.icon;

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.color}`}>
                <IconComponent className="w-3 h-3 mr-1" />
                {config.text}
            </span>
        );
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID');
    };

    const getDaysUntilExpiry = (expiryDate) => {
        const today = new Date();
        const expiry = new Date(expiryDate);
        const diffTime = expiry - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays < 0) {
            return `Expired ${Math.abs(diffDays)} days ago`;
        } else if (diffDays === 0) {
            return 'Expires today';
        } else {
            return `${diffDays} days remaining`;
        }
    };

    const deleteRecord = (id) => {
        if (confirm('Are you sure you want to delete this training record?')) {
            router.delete(route('training-records.destroy', id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Training Records Management
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Kelola data pelatihan dan sertifikasi karyawan
                        </p>
                    </div>
                    <div className="flex space-x-2">
                        <Link
                            href={route('employees.index')}
                            className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        >
                            <UserIcon className="w-4 h-4 mr-2" />
                            Data Karyawan
                        </Link>
                        <Link
                            href={route('training-records.create')}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        >
                            <PlusIcon className="w-4 h-4 mr-2" />
                            Add Training
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Training Records" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-blue-50 rounded-lg p-6 border border-blue-200">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-blue-500 text-white">
                                    <UserIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-blue-600">Total Karyawan</p>
                                    <p className="text-2xl font-bold text-blue-900">{stats.total_employees}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-green-50 rounded-lg p-6 border border-green-200">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-green-500 text-white">
                                    <CheckCircleIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-green-600">Active Certificates</p>
                                    <p className="text-2xl font-bold text-green-900">{stats.active_certificates}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-yellow-50 rounded-lg p-6 border border-yellow-200">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-yellow-500 text-white">
                                    <ExclamationTriangleIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-yellow-600">Expiring Soon</p>
                                    <p className="text-2xl font-bold text-yellow-900">{stats.expiring_soon}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-red-50 rounded-lg p-6 border border-red-200">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-red-500 text-white">
                                    <XCircleIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-red-600">Expired</p>
                                    <p className="text-2xl font-bold text-red-900">{stats.expired_certificates}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow p-6 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
                            {/* Search */}
                            <div className="md:col-span-2">
                                <div className="relative">
                                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        type="text"
                                        placeholder="Search records..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="pl-10 w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                        onKeyPress={(e) => {
                                            if (e.key === 'Enter') {
                                                handleSearch();
                                            }
                                        }}
                                    />
                                </div>
                            </div>

                            {/* Status Filter */}
                            <div>
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => setSelectedStatus(e.target.value)}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="expiring_soon">Expiring Soon</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>

                            {/* Employee Filter */}
                            <div>
                                <select
                                    value={selectedEmployee}
                                    onChange={(e) => setSelectedEmployee(e.target.value)}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Employees</option>
                                    {employees.map((employee) => (
                                        <option key={employee.id} value={employee.id}>
                                            {employee.name} ({employee.employee_id})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Training Type Filter */}
                            <div>
                                <select
                                    value={selectedTrainingType}
                                    onChange={(e) => setSelectedTrainingType(e.target.value)}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Training Types</option>
                                    {trainingTypes.map((type) => (
                                        <option key={type.id} value={type.id}>
                                            {type.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Department Filter */}
                            <div>
                                <select
                                    value={selectedDepartment}
                                    onChange={(e) => setSelectedDepartment(e.target.value)}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Departments</option>
                                    {departments.map((dept) => (
                                        <option key={dept.id} value={dept.id}>
                                            {dept.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="flex justify-between">
                            <div className="flex space-x-2">
                                <button
                                    onClick={handleSearch}
                                    className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                >
                                    <FunnelIcon className="w-4 h-4 mr-2" />
                                    Apply Filters
                                </button>
                                <button
                                    onClick={clearFilters}
                                    className="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                >
                                    Clear
                                </button>
                            </div>
                            <button
                                onClick={exportRecords}
                                className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                                Export Data
                            </button>
                        </div>
                    </div>

                    {/* Training Records Table */}
                    <div className="bg-white rounded-lg shadow overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h3 className="text-lg font-medium text-gray-900">
                                Training Records ({trainingRecords.total} total)
                            </h3>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Employee
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Training Type
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Certificate
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Valid Until
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {trainingRecords.data.length > 0 ? (
                                        trainingRecords.data.map((record) => (
                                            <tr key={record.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <div className="flex-shrink-0 h-10 w-10">
                                                            <div className="h-10 w-10 rounded-full bg-green-500 flex items-center justify-center">
                                                                <span className="text-sm font-medium text-white">
                                                                    {record.employee.name.charAt(0)}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div className="ml-4">
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {record.employee.name}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                {record.employee.employee_id} • {record.employee.department?.name}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {record.training_type.name}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {record.training_type.code}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm text-gray-900">
                                                        {record.certificate_number}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {record.issuer}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm text-gray-900">
                                                        {formatDate(record.expiry_date)}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {getDaysUntilExpiry(record.expiry_date)}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {getStatusBadge(record.status)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex items-center justify-end space-x-2">
                                                        <Link
                                                            href={route('training-records.show', record.id)}
                                                            className="text-green-600 hover:text-green-900"
                                                            title="View"
                                                        >
                                                            <EyeIcon className="w-4 h-4" />
                                                        </Link>
                                                        <Link
                                                            href={route('training-records.edit', record.id)}
                                                            className="text-blue-600 hover:text-blue-900"
                                                            title="Edit"
                                                        >
                                                            <PencilIcon className="w-4 h-4" />
                                                        </Link>
                                                        <button
                                                            onClick={() => deleteRecord(record.id)}
                                                            className="text-red-600 hover:text-red-900"
                                                            title="Delete"
                                                        >
                                                            <TrashIcon className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-4 text-center text-gray-500">
                                                <ClipboardDocumentListIcon className="w-12 h-12 mx-auto text-gray-400 mb-4" />
                                                <p className="text-lg font-medium text-gray-900">No training records found</p>
                                                <p className="text-sm">Get started by adding your first training record.</p>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {trainingRecords.links && (
                            <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div className="flex-1 flex justify-between sm:hidden">
                                    {trainingRecords.prev_page_url && (
                                        <Link
                                            href={trainingRecords.prev_page_url}
                                            className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Previous
                                        </Link>
                                    )}
                                    {trainingRecords.next_page_url && (
                                        <Link
                                            href={trainingRecords.next_page_url}
                                            className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                        >
                                            Next
                                        </Link>
                                    )}
                                </div>
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Showing <span className="font-medium">{trainingRecords.from}</span> to{' '}
                                            <span className="font-medium">{trainingRecords.to}</span> of{' '}
                                            <span className="font-medium">{trainingRecords.total}</span> results
                                        </p>
                                    </div>
                                    <div>
                                        <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            {trainingRecords.links.map((link, index) => (
                                                <Link
                                                    key={index}
                                                    href={link.url || '#'}
                                                    className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                        link.active
                                                            ? 'z-10 bg-green-50 border-green-500 text-green-600'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    } ${index === 0 ? 'rounded-l-md' : ''} ${
                                                        index === trainingRecords.links.length - 1 ? 'rounded-r-md' : ''
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
