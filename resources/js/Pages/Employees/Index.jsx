// resources/js/Pages/Employees/Index.jsx
// PERBAIKAN: Menambahkan fallback untuk stats dan error handling

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowDownTrayIcon,
    UserIcon,
    BuildingOfficeIcon,
    ClipboardDocumentListIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, employees, departments, filters, stats, flash }) {
    // PERBAIKAN: Default stats fallback untuk mencegah undefined error
    const safeStats = stats || {
        total: 0,
        active: 0,
        inactive: 0,
        by_department: [],
        departments_count: 0,
        training_records_count: 0,
        active_training_records: 0,
        expired_training_records: 0,
        expiring_soon_records: 0,
        compliance_rate: 0
    };

    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters?.department || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [deleteLoading, setDeleteLoading] = useState(null);

    const handleSearch = () => {
        router.get(route('employees.index'), {
            search: searchTerm,
            department: selectedDepartment,
            status: selectedStatus,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedDepartment('');
        setSelectedStatus('');
        router.get(route('employees.index'));
    };

    const exportEmployees = () => {
        const params = new URLSearchParams({
            search: searchTerm,
            department: selectedDepartment,
            status: selectedStatus,
        });

        window.location.href = route('import-export.employees.export') + '?' + params.toString();
    };

    // PERBAIKAN: Improved delete function dengan better error handling
    const deleteEmployee = async (id, employeeName) => {
        // Debug log
        console.log('Delete employee clicked:', { id, employeeName });

        // Improved confirmation dialog
        const confirmMessage = `Apakah Anda yakin ingin menghapus karyawan "${employeeName}"?\n\nTindakan ini tidak dapat dibatalkan.`;

        if (!window.confirm(confirmMessage)) {
            console.log('Delete cancelled by user');
            return;
        }

        try {
            setDeleteLoading(id);
            console.log('Sending delete request to:', route('employees.destroy', id));

            // Delete request dengan error handling
            router.delete(route('employees.destroy', id), {
                preserveScroll: true,
                onStart: () => {
                    console.log('Delete request started');
                },
                onSuccess: (page) => {
                    console.log('Delete successful:', page);
                    setDeleteLoading(null);
                },
                onError: (errors) => {
                    console.error('Delete failed:', errors);
                    setDeleteLoading(null);

                    // Show detailed error
                    const errorMessage = typeof errors === 'object'
                        ? Object.values(errors).join(', ')
                        : errors || 'Terjadi kesalahan tidak diketahui';

                    alert(`Gagal menghapus karyawan: ${errorMessage}`);
                },
                onFinish: () => {
                    console.log('Delete request finished');
                    setDeleteLoading(null);
                }
            });

        } catch (error) {
            console.error('Unexpected error during delete:', error);
            setDeleteLoading(null);
            alert('Terjadi kesalahan unexpected. Silakan coba lagi.');
        }
    };

    const getStatusBadge = (status) => {
        if (status === 'active') {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <CheckCircleIcon className="w-3 h-3 mr-1" />
                    Active
                </span>
            );
        } else {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <XCircleIcon className="w-3 h-3 mr-1" />
                    Inactive
                </span>
            );
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('id-ID');
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Data Karyawan
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Kelola data kepegawaian sistem training GAPURA
                        </p>
                    </div>
                    <div className="flex space-x-2">
                        <button
                            onClick={exportEmployees}
                            className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                            Export Data
                        </button>
                        <Link
                            href={route('employees.create')}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        >
                            <PlusIcon className="w-4 h-4 mr-2" />
                            Tambah Karyawan
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Data Karyawan" />

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

                    {/* Statistics Cards - PERBAIKAN: Gunakan safeStats */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-green-50 rounded-lg p-6 border border-green-200">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-green-500 text-white">
                                    <UserIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-green-600">Total Karyawan</p>
                                    <p className="text-2xl font-bold text-green-900">{safeStats.total}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-blue-50 rounded-lg p-6 border border-blue-200">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-blue-500 text-white">
                                    <BuildingOfficeIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-blue-600">Departemen</p>
                                    <p className="text-2xl font-bold text-blue-900">{safeStats.departments_count}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-yellow-50 rounded-lg p-6 border border-yellow-200">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-yellow-500 text-white">
                                    <ExclamationTriangleIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-yellow-600">Training Records</p>
                                    <p className="text-2xl font-bold text-yellow-900">{safeStats.training_records_count}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-purple-50 rounded-lg p-6 border border-purple-200">
                            <div className="flex items-center">
                                <div className="p-3 rounded-full bg-purple-500 text-white">
                                    <ClipboardDocumentListIcon className="w-6 h-6" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-purple-600">Compliance Rate</p>
                                    <p className="text-2xl font-bold text-purple-900">{safeStats.compliance_rate}%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white shadow rounded-lg mb-6">
                        <div className="px-6 py-4">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Search
                                    </label>
                                    <div className="relative">
                                        <input
                                            type="text"
                                            placeholder="Cari nama, ID, posisi..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                        />
                                        <MagnifyingGlassIcon className="w-5 h-5 text-gray-400 absolute left-3 top-2.5" />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Departemen
                                    </label>
                                    <select
                                        value={selectedDepartment}
                                        onChange={(e) => setSelectedDepartment(e.target.value)}
                                        className="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">Semua Departemen</option>
                                        {departments?.map((dept) => (
                                            <option key={dept.id} value={dept.id}>
                                                {dept.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Status
                                    </label>
                                    <select
                                        value={selectedStatus}
                                        onChange={(e) => setSelectedStatus(e.target.value)}
                                        className="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="">Semua Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <div className="flex items-end space-x-2">
                                    <button
                                        onClick={handleSearch}
                                        className="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                                    >
                                        <FunnelIcon className="w-4 h-4 inline mr-1" />
                                        Filter
                                    </button>
                                    <button
                                        onClick={clearFilters}
                                        className="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors"
                                    >
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Employee Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ID Karyawan
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nama
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Departemen
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Posisi
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {employees?.data?.length > 0 ? (
                                        employees.data.map((employee) => (
                                            <tr key={employee.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {employee.employee_id}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {employee.name}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {employee.department?.name || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {employee.position || 'N/A'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {getStatusBadge(employee.status)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div className="flex items-center space-x-3">
                                                        {/* View */}
                                                        <Link
                                                            href={route('employees.show', employee.id)}
                                                            className="text-blue-600 hover:text-blue-900"
                                                            title="View Details"
                                                        >
                                                            <EyeIcon className="w-4 h-4" />
                                                        </Link>

                                                        {/* Edit */}
                                                        <Link
                                                            href={route('employees.edit', employee.id)}
                                                            className="text-indigo-600 hover:text-indigo-900"
                                                            title="Edit"
                                                        >
                                                            <PencilIcon className="w-4 h-4" />
                                                        </Link>

                                                        {/* Delete Button - Improved */}
                                                        <button
                                                            onClick={() => deleteEmployee(employee.id, employee.name)}
                                                            disabled={deleteLoading === employee.id}
                                                            className={`${
                                                                deleteLoading === employee.id
                                                                    ? 'text-gray-400 cursor-not-allowed'
                                                                    : 'text-red-600 hover:text-red-900'
                                                            } transition-colors`}
                                                            title={deleteLoading === employee.id ? 'Deleting...' : 'Delete'}
                                                        >
                                                            {deleteLoading === employee.id ? (
                                                                <div className="w-4 h-4 border-2 border-gray-300 border-t-red-600 rounded-full animate-spin"></div>
                                                            ) : (
                                                                <TrashIcon className="w-4 h-4" />
                                                            )}
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-12 text-center text-gray-500">
                                                <UserIcon className="w-12 h-12 mx-auto text-gray-400 mb-4" />
                                                <p className="text-lg font-medium text-gray-900">Belum ada data karyawan</p>
                                                <p className="text-sm">Mulai dengan menambahkan karyawan pertama.</p>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {employees?.links && (
                            <div className="px-6 py-3 border-t border-gray-200">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-gray-500">
                                        Showing {employees.from || 0} to {employees.to || 0} of {employees.total || 0} results
                                    </div>
                                    <div className="flex space-x-1">
                                        {employees.links.map((link, index) => (
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
