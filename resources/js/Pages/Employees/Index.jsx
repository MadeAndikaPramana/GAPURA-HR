// resources/js/Pages/Employees/Index.jsx

import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    UsersIcon,
    ArrowPathIcon
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

        // Remove empty params
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

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Data Karyawan SDM" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-slate-900">Data Karyawan SDM</h1>
                                <p className="mt-2 text-sm text-slate-600">
                                    Kelola data karyawan: NIP, Nama, dan Jabatan
                                </p>
                            </div>
                            <div className="mt-4 sm:mt-0">
                                <Link
                                    href={route('employees.create')}
                                    className="btn-primary"
                                >
                                    <PlusIcon className="w-4 h-4 mr-2" />
                                    Tambah Karyawan
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Search and Filters */}
                    <div className="card mb-6">
                        <div className="card-body">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                {/* Search */}
                                <div className="md:col-span-2">
                                    <label className="form-label">
                                        <MagnifyingGlassIcon className="w-4 h-4 inline mr-2" />
                                        Cari Karyawan
                                    </label>
                                    <input
                                        type="text"
                                        className="form-input w-full"
                                        placeholder="Cari NIK, NIP, nama, atau jabatan..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                    />
                                </div>

                                {/* Department Filter */}
                                <div>
                                    <label className="form-label">Departemen</label>
                                    <select
                                        className="form-input w-full"
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

                                {/* Status Filter */}
                                <div>
                                    <label className="form-label">Status</label>
                                    <select
                                        className="form-input w-full"
                                        value={selectedStatus}
                                        onChange={(e) => setSelectedStatus(e.target.value)}
                                    >
                                        <option value="">Semua Status</option>
                                        <option value="active">Aktif</option>
                                        <option value="inactive">Tidak Aktif</option>
                                    </select>
                                </div>
                            </div>

                            {/* Filter Actions */}
                            <div className="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                                <div className="flex space-x-3">
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

                    {/* Employee Table */}
                    <div className="card">
                        <div className="overflow-x-auto">
                            <table className="table w-full">
                                <thead>
                                    <tr className="bg-slate-50 border-b border-slate-200">
                                        <th className="table-header">NIK</th>
                                        <th className="table-header">NIP</th>
                                        <th className="table-header">Nama Karyawan</th>
                                        <th className="table-header">Jabatan</th>
                                        <th className="table-header">Departemen</th>
                                        <th className="table-header">Status</th>
                                        <th className="table-header">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-slate-200">
                                    {employees?.data?.length > 0 ? (
                                        employees.data.map((employee) => (
                                            <tr key={employee.id} className="hover:bg-slate-50 transition-colors">
                                                <td className="table-cell">
                                                    <span className="font-medium text-slate-900">
                                                        {employee.nik || '-'}
                                                    </span>
                                                </td>
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
                                                    <span className="text-slate-700">
                                                        {employee.position || '-'}
                                                    </span>
                                                </td>
                                                <td className="table-cell">
                                                    <span className="text-slate-600">
                                                        {employee.department?.name || 'Tidak ada'}
                                                    </span>
                                                </td>
                                                <td className="table-cell">
                                                    {getStatusBadge(employee.status)}
                                                </td>
                                                <td className="table-cell">
                                                    <div className="flex items-center space-x-2">
                                                        <Link
                                                            href={route('employees.show', employee.id)}
                                                            className="p-1 text-slate-400 hover:text-blue-600 transition-colors"
                                                            title="Lihat Detail"
                                                        >
                                                            <EyeIcon className="w-4 h-4" />
                                                        </Link>
                                                        <Link
                                                            href={route('employees.edit', employee.id)}
                                                            className="p-1 text-slate-400 hover:text-green-600 transition-colors"
                                                            title="Edit"
                                                        >
                                                            <PencilIcon className="w-4 h-4" />
                                                        </Link>
                                                        <button
                                                            onClick={() => deleteEmployee(employee)}
                                                            className="p-1 text-slate-400 hover:text-red-600 transition-colors"
                                                            title="Hapus"
                                                        >
                                                            <TrashIcon className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="7" className="table-cell text-center py-12">
                                                <div className="flex flex-col items-center">
                                                    <UsersIcon className="w-12 h-12 text-slate-400 mb-4" />
                                                    <h3 className="text-lg font-medium text-slate-900 mb-2">
                                                        Belum ada data karyawan
                                                    </h3>
                                                    <p className="text-slate-600 mb-4">
                                                        Mulai dengan menambahkan karyawan baru ke sistem.
                                                    </p>
                                                    <Link
                                                        href={route('employees.create')}
                                                        className="btn-primary"
                                                    >
                                                        <PlusIcon className="w-4 h-4 mr-2" />
                                                        Tambah Karyawan Pertama
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
                            <div className="px-6 py-4 border-t border-slate-200 bg-slate-50">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-slate-600">
                                        Menampilkan {employees.from || 0} - {employees.to || 0} dari {employees.total || 0} karyawan
                                    </div>
                                    {/* Pagination links would go here */}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
