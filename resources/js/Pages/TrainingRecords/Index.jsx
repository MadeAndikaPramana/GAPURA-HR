// resources/js/Pages/TrainingRecords/Index.jsx
// Comprehensive Training Records Management

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    UserIcon,
    AcademicCapIcon,
    ClipboardDocumentListIcon,
    ExclamationTriangleIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
    CalendarIcon,
    BuildingOfficeIcon,
    ChevronDownIcon
} from '@heroicons/react/24/outline';

export default function Index({
    auth,
    trainingRecords,
    employees,
    trainingTypes,
    departments,
    filters,
    stats,
    flash
}) {
    // Safe stats dengan fallback
    const safeStats = stats || {
        total_certificates: 0,
        active_certificates: 0,
        expiring_certificates: 0,
        expired_certificates: 0
    };

    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [selectedEmployee, setSelectedEmployee] = useState(filters?.employee || '');
    const [selectedTrainingType, setSelectedTrainingType] = useState(filters?.training_type || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters?.department || '');
    const [selectedDateFrom, setSelectedDateFrom] = useState(filters?.date_from || '');
    const [selectedDateTo, setSelectedDateTo] = useState(filters?.date_to || '');
    const [deleteLoading, setDeleteLoading] = useState(null);
    const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);

    const handleSearch = () => {
        router.get(route('training-records.index'), {
            search: searchTerm,
            status: selectedStatus,
            employee: selectedEmployee,
            training_type: selectedTrainingType,
            department: selectedDepartment,
            date_from: selectedDateFrom,
            date_to: selectedDateTo,
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
        setSelectedDateFrom('');
        setSelectedDateTo('');
        router.get(route('training-records.index'));
    };

    const exportRecords = () => {
        const params = new URLSearchParams({
            search: searchTerm,
            status: selectedStatus,
            employee: selectedEmployee,
            training_type: selectedTrainingType,
            department: selectedDepartment,
            date_from: selectedDateFrom,
            date_to: selectedDateTo,
        });

        window.location.href = route('import-export.training-records.export') + '?' + params.toString();
    };

    const deleteRecord = async (id, certificateNumber) => {
        const confirmMessage = `Apakah Anda yakin ingin menghapus training record "${certificateNumber}"?\n\nTindakan ini tidak dapat dibatalkan.`;

        if (!window.confirm(confirmMessage)) {
            return;
        }

        try {
            setDeleteLoading(id);
            router.delete(route('training-records.destroy', id), {
                preserveScroll: true,
                onSuccess: () => setDeleteLoading(null),
                onError: (errors) => {
                    setDeleteLoading(null);
                    const errorMessage = typeof errors === 'object'
                        ? Object.values(errors).join(', ')
                        : errors || 'Terjadi kesalahan tidak diketahui';
                    alert(`Gagal menghapus training record: ${errorMessage}`);
                },
                onFinish: () => setDeleteLoading(null)
            });
        } catch (error) {
            console.error('Delete error:', error);
            setDeleteLoading(null);
        }
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            'active': {
                icon: CheckCircleIcon,
                classes: 'bg-green-100 text-green-800',
                label: 'Active'
            },
            'expiring_soon': {
                icon: ExclamationTriangleIcon,
                classes: 'bg-yellow-100 text-yellow-800',
                label: 'Expiring Soon'
            },
            'expired': {
                icon: XCircleIcon,
                classes: 'bg-red-100 text-red-800',
                label: 'Expired'
            },
            'completed': {
                icon: CheckCircleIcon,
                classes: 'bg-blue-100 text-blue-800',
                label: 'Completed'
            }
        };

        const config = statusConfig[status] || statusConfig['completed'];
        const Icon = config.icon;

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.classes}`}>
                <Icon className="w-3 h-3 mr-1" />
                {config.label}
            </span>
        );
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('id-ID');
    };

    const calculateDaysUntilExpiry = (expiryDate) => {
        if (!expiryDate) return null;
        const today = new Date();
        const expiry = new Date(expiryDate);
        const diffTime = expiry - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Training Records
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Kelola data sertifikat dan pelatihan karyawan GAPURA
                        </p>
                    </div>
                    <div className="flex space-x-2">
                        <button
                            onClick={exportRecords}
                            className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                            Export
                        </button>
                        <Link
                            href={route('import-export.training-records.import')}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            <ArrowUpTrayIcon className="w-4 h-4 mr-2" />
                            Import
                        </Link>
                        <Link
                            href={route('training-records.create')}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        >
                            <PlusIcon className="w-4 h-4 mr-2" />
                            Add Record
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Training Records" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Flash Messages */}
                    {flash?.success && (
                        <div className="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                            <div className="flex">
                                <CheckCircleIcon className="w-5 h-5 mr-2" />
                                {flash.success}
                            </div>
                        </div>
                    )}

                    {flash?.error && (
                        <div className="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <div className="flex">
                                <ExclamationTriangleIcon className="w-5 h-5 mr-2" />
                                {flash.error}
                            </div>
                        </div>
                    )}

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-blue-50 rounded-lg p-6 border border-blue-200">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-blue-500 text-white">
                                    <ClipboardDocumentListIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-blue-600">Total Certificates</p>
                                    <p className="text-2xl font-bold text-blue-900">{safeStats.total_certificates}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-green-50 rounded-lg p-6 border border-green-200">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-green-500 text-white">
                                    <CheckCircleIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-green-600">Active</p>
                                    <p className="text-2xl font-bold text-green-900">{safeStats.active_certificates}</p>
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
                                    <p className="text-2xl font-bold text-yellow-900">{safeStats.expiring_certificates}</p>
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
                                    <p className="text-2xl font-bold text-red-900">{safeStats.expired_certificates}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white shadow rounded-lg mb-6">
                        <div className="px-6 py-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                {/* Search */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Search
                                    </label>
                                    <div className="relative">
                                        <input
                                            type="text"
                                            placeholder="Certificate number, employee..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                        />
                                        <MagnifyingGlassIcon className="w-5 h-5 text-gray-400 absolute left-3 top-2.5" />
                                    </div>
                                </div>

                                {/* Status */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Status
                                    </label>
                                    <select
                                        value={selectedStatus}
                                        onChange={(e) => setSelectedStatus(e.target.value)}
                                        className="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="expiring_soon">Expiring Soon</option>
                                        <option value="expired">Expired</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>

                                {/* Department */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Department
                                    </label>
                                    <select
                                        value={selectedDepartment}
                                        onChange={(e) => setSelectedDepartment(e.target.value)}
                                        className="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">All Departments</option>
                                        {departments?.map((dept) => (
                                            <option key={dept.id} value={dept.id}>
                                                {dept.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Actions */}
                                <div className="flex items-end space-x-2">
                                    <button
                                        onClick={handleSearch}
                                        className="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                                    >
                                        <FunnelIcon className="w-4 h-4 inline mr-1" />
                                        Filter
                                    </button>
                                    <button
                                        onClick={() => setShowAdvancedFilters(!showAdvancedFilters)}
                                        className="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                                    >
                                        <ChevronDownIcon className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            {/* Advanced Filters */}
                            {showAdvancedFilters && (
                                <div className="mt-4 pt-4 border-t border-gray-200">
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Employee
                                            </label>
                                            <select
                                                value={selectedEmployee}
                                                onChange={(e) => setSelectedEmployee(e.target.value)}
                                                className="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                            >
                                                <option value="">All Employees</option>
                                                {employees?.map((emp) => (
                                                    <option key={emp.id} value={emp.id}>
                                                        {emp.name} ({emp.employee_id})
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Training Type
                                            </label>
                                            <select
                                                value={selectedTrainingType}
                                                onChange={(e) => setSelectedTrainingType(e.target.value)}
                                                className="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                            >
                                                <option value="">All Training Types</option>
                                                {trainingTypes?.map((type) => (
                                                    <option key={type.id} value={type.id}>
                                                        {type.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Date From
                                            </label>
                                            <input
                                                type="date"
                                                value={selectedDateFrom}
                                                onChange={(e) => setSelectedDateFrom(e.target.value)}
                                                className="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Date To
                                            </label>
                                            <input
                                                type="date"
                                                value={selectedDateTo}
                                                onChange={(e) => setSelectedDateTo(e.target.value)}
                                                className="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                            />
                                        </div>
                                    </div>

                                    <div className="mt-4 flex justify-end space-x-2">
                                        <button
                                            onClick={clearFilters}
                                            className="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors"
                                        >
                                            Clear All Filters
                                        </button>
                                        <button
                                            onClick={handleSearch}
                                            className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                                        >
                                            Apply Filters
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Training Records Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Certificate #
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Employee
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Training Type
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Issue Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Expiry Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {trainingRecords?.data?.length > 0 ? (
                                        trainingRecords.data.map((record) => {
                                            const daysUntilExpiry = calculateDaysUntilExpiry(record.expiry_date);
                                            return (
                                                <tr key={record.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {record.certificate_number}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <div className="flex-shrink-0 h-10 w-10">
                                                                <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                                    <UserIcon className="h-5 w-5 text-gray-500" />
                                                                </div>
                                                            </div>
                                                            <div className="ml-4">
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {record.employee?.name}
                                                                </div>
                                                                <div className="text-sm text-gray-500">
                                                                    {record.employee?.department?.name || 'No Department'}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <div className="flex items-center">
                                                            <AcademicCapIcon className="h-4 w-4 text-gray-400 mr-2" />
                                                            {record.training_type?.name || 'N/A'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <div className="flex items-center">
                                                            <CalendarIcon className="h-4 w-4 text-gray-400 mr-1" />
                                                            {formatDate(record.issue_date)}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <div className="flex items-center">
                                                            <CalendarIcon className="h-4 w-4 text-gray-400 mr-1" />
                                                            {formatDate(record.expiry_date)}
                                                            {daysUntilExpiry !== null && (
                                                                <span className={`ml-2 text-xs px-2 py-1 rounded-full ${
                                                                    daysUntilExpiry <= 0
                                                                        ? 'bg-red-100 text-red-700'
                                                                        : daysUntilExpiry <= 30
                                                                        ? 'bg-yellow-100 text-yellow-700'
                                                                        : 'bg-gray-100 text-gray-700'
                                                                }`}>
                                                                    {daysUntilExpiry <= 0 ? 'EXPIRED' : `${daysUntilExpiry}d`}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getStatusBadge(record.status)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div className="flex items-center space-x-3">
                                                            <Link
                                                                href={route('training-records.show', record.id)}
                                                                className="text-blue-600 hover:text-blue-900"
                                                                title="View Details"
                                                            >
                                                                <EyeIcon className="w-4 h-4" />
                                                            </Link>
                                                            <Link
                                                                href={route('training-records.edit', record.id)}
                                                                className="text-indigo-600 hover:text-indigo-900"
                                                                title="Edit"
                                                            >
                                                                <PencilIcon className="w-4 h-4" />
                                                            </Link>
                                                            <button
                                                                onClick={() => deleteRecord(record.id, record.certificate_number)}
                                                                disabled={deleteLoading === record.id}
                                                                className={`${
                                                                    deleteLoading === record.id
                                                                        ? 'text-gray-400 cursor-not-allowed'
                                                                        : 'text-red-600 hover:text-red-900'
                                                                } transition-colors`}
                                                                title="Delete"
                                                            >
                                                                {deleteLoading === record.id ? (
                                                                    <div className="w-4 h-4 border-2 border-gray-300 border-t-red-600 rounded-full animate-spin"></div>
                                                                ) : (
                                                                    <TrashIcon className="w-4 h-4" />
                                                                )}
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td colSpan="7" className="px-6 py-12 text-center text-gray-500">
                                                <ClipboardDocumentListIcon className="w-12 h-12 mx-auto text-gray-400 mb-4" />
                                                <p className="text-lg font-medium text-gray-900">No training records found</p>
                                                <p className="text-sm">Start by adding your first training record.</p>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {trainingRecords?.links && (
                            <div className="px-6 py-3 border-t border-gray-200">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-gray-500">
                                        Showing {trainingRecords.from || 0} to {trainingRecords.to || 0} of {trainingRecords.total || 0} results
                                    </div>
                                    <div className="flex space-x-1">
                                        {trainingRecords.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`px-3 py-1 text-sm rounded-md ${
                                                    link.active
                                                        ? 'bg-green-600 text-white'
                                                        : 'bg-white text-gray-500 border border-gray-300 hover:bg-gray-50'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                preserveState
                                            />
                                        ))}
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
