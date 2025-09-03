// resources/js/Pages/Employees/Index.jsx - Enhanced with Container View

import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    FolderIcon,
    ArrowPathIcon,
    DocumentTextIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, employees, departments, filters = {} }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');

    const handleSearch = () => {
        const params = {
            search: searchTerm || undefined,
            department: selectedDepartment || undefined,
            status: selectedStatus || undefined,
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
        setSelectedStatus('');
        router.get(route('employees.index'));
    };

    const deleteEmployee = (employee) => {
        if (confirm(`Apakah Anda yakin ingin menghapus data ${employee.name}?`)) {
            router.delete(route('employees.destroy', employee.id));
        }
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            active: { bg: 'bg-green-100', text: 'text-green-800', label: 'Aktif' },
            inactive: { bg: 'bg-red-100', text: 'text-red-800', label: 'Tidak Aktif' }
        };

        const config = statusConfig[status] || statusConfig.active;

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.bg} ${config.text}`}>
                {config.label}
            </span>
        );
    };

    // NEW: Get container status summary for employee
    const getContainerStatus = (employee) => {
        const stats = employee.certificate_statistics || {};
        const total = stats.total || 0;
        const expired = stats.expired || 0;
        const expiring_soon = stats.expiring_soon || 0;

        if (expired > 0) {
            return {
                icon: '🔴',
                text: `${expired} Expired`,
                color: 'text-red-600',
                bg: 'bg-red-50'
            };
        }

        if (expiring_soon > 0) {
            return {
                icon: '🟡',
                text: `${expiring_soon} Expiring`,
                color: 'text-yellow-600',
                bg: 'bg-yellow-50'
            };
        }

        if (total > 0) {
            return {
                icon: '🟢',
                text: `${total} Certificates`,
                color: 'text-green-600',
                bg: 'bg-green-50'
            };
        }

        return {
            icon: '📁',
            text: 'Empty Container',
            color: 'text-slate-500',
            bg: 'bg-slate-50'
        };
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Data Karyawan" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header with enhanced title */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-slate-900 flex items-center">
                                    <FolderIcon className="w-8 h-8 text-green-600 mr-3" />
                                    Employee Containers
                                </h1>
                                <p className="mt-2 text-sm text-slate-600">
                                    Digital employee folders with certificates and background check data
                                </p>
                            </div>
                            <Link
                                href={route('employees.create')}
                                className="btn-primary"
                            >
                                <PlusIcon className="w-4 h-4 mr-2" />
                                Tambah Karyawan
                            </Link>
                        </div>
                    </div>

                    {/* Search and Filter */}
                    <div className="card mb-6">
                        <div className="card-body">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div className="md:col-span-2">
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <input
                                            type="text"
                                            placeholder="Cari karyawan (nama, NIP, jabatan)..."
                                            className="pl-10 input-field"
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                        />
                                    </div>
                                </div>

                                <div>
                                    <select
                                        className="input-field"
                                        value={selectedDepartment}
                                        onChange={(e) => setSelectedDepartment(e.target.value)}
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
                                    <select
                                        className="input-field"
                                        value={selectedStatus}
                                        onChange={(e) => setSelectedStatus(e.target.value)}
                                    >
                                        <option value="">Semua Status</option>
                                        <option value="active">Aktif</option>
                                        <option value="inactive">Tidak Aktif</option>
                                    </select>
                                </div>
                            </div>

                            <div className="flex items-center justify-between mt-4">
                                <div className="flex items-center space-x-3">
                                    <button
                                        onClick={handleSearch}
                                        className="btn-primary"
                                    >
                                        <FunnelIcon className="w-4 h-4 mr-2" />
                                        Filter
                                    </button>
                                    <button
                                        onClick={resetFilters}
                                        className="btn-secondary"
                                    >
                                        <ArrowPathIcon className="w-4 h-4 mr-2" />
                                        Reset
                                    </button>
                                </div>
                                <div className="text-sm text-slate-600">
                                    Total: {employees?.total || 0} karyawan
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Enhanced Employee Table with Container Status */}
                    <div className="card">
                        <div className="overflow-x-auto">
                            <table className="table w-full">
                                <thead>
                                    <tr className="bg-slate-50 border-b border-slate-200">
                                        <th className="table-header">NIP</th>
                                        <th className="table-header">Nama Karyawan</th>
                                        <th className="table-header">Jabatan</th>
                                        <th className="table-header">Departemen</th>
                                        <th className="table-header">Container Status</th>
                                        <th className="table-header">Status</th>
                                        <th className="table-header">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-slate-200">
                                    {employees?.data?.length > 0 ? (
                                        employees.data.map((employee) => {
                                            const containerStatus = getContainerStatus(employee);

                                            return (
                                                <tr key={employee.id} className="hover:bg-slate-50 transition-colors">
                                                    <td className="table-cell">
                                                        <span className="font-medium text-slate-700">
                                                            {employee.employee_id}
                                                        </span>
                                                    </td>
                                                    <td className="table-cell">
                                                        <div className="flex items-center">
                                                            <div className="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center mr-3">
                                                                <span className="text-white font-medium text-sm">
                                                                    {employee.name.charAt(0).toUpperCase()}
                                                                </span>
                                                            </div>
                                                            <span className="font-medium text-slate-900">
                                                                {employee.name}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td className="table-cell">
                                                        <span className="text-slate-600">
                                                            {employee.position || 'Belum diisi'}
                                                        </span>
                                                    </td>
                                                    <td className="table-cell">
                                                        <span className="text-slate-600">
                                                            {employee.department?.name || 'Tidak ada'}
                                                        </span>
                                                    </td>
                                                    {/* NEW: Container Status Column */}
                                                    <td className="table-cell">
                                                        <div className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${containerStatus.bg} ${containerStatus.color}`}>
                                                            <span className="mr-1">{containerStatus.icon}</span>
                                                            {containerStatus.text}
                                                        </div>
                                                    </td>
                                                    <td className="table-cell">
                                                        {getStatusBadge(employee.status)}
                                                    </td>
                                                    <td className="table-cell">
                                                        <div className="flex items-center space-x-2">
                                                            {/* NEW: Container View Button (Primary Action) */}
                                                            <Link
                                                                href={route('employees.container', employee.id)}
                                                                className="btn-xs btn-primary"
                                                                title="View Employee Container"
                                                            >
                                                                <FolderIcon className="w-4 h-4 mr-1" />
                                                                Container
                                                            </Link>

                                                            {/* Traditional actions moved to secondary */}
                                                            <div className="flex items-center space-x-1">
                                                                <Link
                                                                    href={route('employees.show', employee.id)}
                                                                    className="btn-xs btn-secondary"
                                                                    title="View Details"
                                                                >
                                                                    <EyeIcon className="w-4 h-4" />
                                                                </Link>
                                                                <Link
                                                                    href={route('employees.edit', employee.id)}
                                                                    className="btn-xs btn-secondary"
                                                                    title="Edit"
                                                                >
                                                                    <PencilIcon className="w-4 h-4" />
                                                                </Link>
                                                                <button
                                                                    onClick={() => deleteEmployee(employee)}
                                                                    className="btn-xs btn-danger"
                                                                    title="Delete"
                                                                >
                                                                    <TrashIcon className="w-4 h-4" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td colSpan="7" className="table-cell text-center py-8">
                                                <div className="flex flex-col items-center">
                                                    <FolderIcon className="w-16 h-16 text-slate-300 mb-4" />
                                                    <h3 className="text-lg font-medium text-slate-900 mb-2">
                                                        Tidak ada data karyawan
                                                    </h3>
                                                    <p className="text-slate-500 mb-4">
                                                        Mulai dengan menambahkan karyawan pertama atau ubah filter pencarian.
                                                    </p>
                                                    <Link
                                                        href={route('employees.create')}
                                                        className="btn-primary"
                                                    >
                                                        <PlusIcon className="w-4 h-4 mr-2" />
                                                        Tambah Karyawan
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {employees?.links && (
                            <div className="px-6 py-4 border-t border-slate-200">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-slate-700">
                                        Menampilkan {employees.from || 0} sampai {employees.to || 0} dari {employees.total || 0} hasil
                                    </div>
                                    {/* Pagination links would go here */}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Container System Info Card */}
                    <div className="mt-6 bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-6">
                        <div className="flex items-start">
                            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                <FolderIcon className="w-6 h-6 text-green-600" />
                            </div>
                            <div className="flex-1">
                                <h3 className="text-lg font-semibold text-slate-900 mb-2">
                                    🗂️ Employee Container System
                                </h3>
                                <p className="text-slate-700 mb-3">
                                    Setiap karyawan memiliki digital folder yang berisi certificate dan background check data.
                                    Click <strong>"Container"</strong> untuk melihat semua dokumen dalam satu tempat yang terorganisir.
                                </p>
                                <div className="flex items-center space-x-6 text-sm">
                                    <div className="flex items-center">
                                        <span className="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                                        <span className="text-slate-600">Active Certificates</span>
                                    </div>
                                    <div className="flex items-center">
                                        <span className="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
                                        <span className="text-slate-600">Expiring Soon</span>
                                    </div>
                                    <div className="flex items-center">
                                        <span className="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                                        <span className="text-slate-600">Expired</span>
                                    </div>
                                    <div className="flex items-center">
                                        <span className="w-3 h-3 bg-slate-400 rounded-full mr-2"></span>
                                        <span className="text-slate-600">Empty Container</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
