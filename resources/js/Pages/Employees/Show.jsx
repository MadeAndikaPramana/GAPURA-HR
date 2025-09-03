// resources/js/Pages/Employees/Show.jsx

import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    UserIcon,
    IdentificationIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    CheckBadgeIcon,
    XMarkIcon,
    CalendarIcon,
    ExclamationTriangleIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, employee }) {
    const deleteEmployee = () => {
        if (confirm(`Apakah Anda yakin ingin menghapus data karyawan ${employee.name}?\n\nData yang dihapus tidak dapat dikembalikan.`)) {
            router.delete(route('employees.destroy', employee.id));
        }
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            active: {
                bg: 'bg-green-100',
                text: 'text-green-800',
                icon: CheckBadgeIcon,
                label: 'Aktif'
            },
            inactive: {
                bg: 'bg-red-100',
                text: 'text-red-800',
                icon: XMarkIcon,
                label: 'Tidak Aktif'
            }
        };

        const config = statusConfig[status] || statusConfig.active;
        const IconComponent = config.icon;

        return (
            <div className={`inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium ${config.bg} ${config.text}`}>
                <IconComponent className="w-4 h-4 mr-2" />
                {config.label}
            </div>
        );
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Detail Karyawan - ${employee.name}`} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between mb-4">
                            <div className="flex items-center space-x-4">
                                <Link
                                    href={route('employees.index')}
                                    className="btn-secondary"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                    Kembali ke Daftar
                                </Link>
                            </div>
                            <div className="flex items-center space-x-3">
                                <Link
                                    href={route('employees.edit', employee.id)}
                                    className="btn-secondary"
                                >
                                    <PencilIcon className="w-4 h-4 mr-2" />
                                    Edit Data
                                </Link>
                                <button
                                    onClick={deleteEmployee}
                                    className="btn-danger"
                                >
                                    <TrashIcon className="w-4 h-4 mr-2" />
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center">
                                <div className="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center mr-4">
                                    <span className="text-white font-bold text-lg">
                                        {employee.name.charAt(0).toUpperCase()}
                                    </span>
                                </div>
                                {employee.name}
                            </h1>
                            <p className="mt-2 text-sm text-slate-600">
                                Detail informasi karyawan dalam sistem SDM
                            </p>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Info */}
                        <div className="lg:col-span-2">
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="text-lg font-medium text-slate-900 flex items-center">
                                        <UserIcon className="w-5 h-5 mr-2 text-green-600" />
                                        Informasi Karyawan
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <dl className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {/* NIK */}
                                        <div>
                                            <dt className="text-sm font-medium text-slate-500 flex items-center mb-2">
                                                <IdentificationIcon className="w-4 h-4 mr-2" />
                                                NIK (Nomor Induk Kependudukan)
                                            </dt>
                                            <dd className="text-sm text-slate-900">
                                                {employee.nik ? (
                                                    <span className="font-mono bg-slate-100 px-2 py-1 rounded">
                                                        {employee.nik}
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-400 italic">Belum diisi</span>
                                                )}
                                            </dd>
                                        </div>

                                        {/* NIP */}
                                        <div>
                                            <dt className="text-sm font-medium text-slate-500 flex items-center mb-2">
                                                <IdentificationIcon className="w-4 h-4 mr-2" />
                                                NIP (Nomor Induk Pegawai)
                                            </dt>
                                            <dd className="text-sm text-slate-900">
                                                <span className="font-mono bg-green-100 text-green-800 px-2 py-1 rounded">
                                                    {employee.employee_id}
                                                </span>
                                            </dd>
                                        </div>

                                        {/* Nama */}
                                        <div>
                                            <dt className="text-sm font-medium text-slate-500 flex items-center mb-2">
                                                <UserIcon className="w-4 h-4 mr-2" />
                                                Nama Lengkap
                                            </dt>
                                            <dd className="text-lg font-semibold text-slate-900">
                                                {employee.name}
                                            </dd>
                                        </div>

                                        {/* Jabatan */}
                                        <div>
                                            <dt className="text-sm font-medium text-slate-500 flex items-center mb-2">
                                                <BriefcaseIcon className="w-4 h-4 mr-2" />
                                                Jabatan
                                            </dt>
                                            <dd className="text-sm text-slate-900">
                                                {employee.position ? (
                                                    <span className="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-medium">
                                                        {employee.position}
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-400 italic">Belum diisi</span>
                                                )}
                                            </dd>
                                        </div>

                                        {/* Departemen */}
                                        <div>
                                            <dt className="text-sm font-medium text-slate-500 flex items-center mb-2">
                                                <BuildingOfficeIcon className="w-4 h-4 mr-2" />
                                                Departemen
                                            </dt>
                                            <dd className="text-sm text-slate-900">
                                                {employee.department ? (
                                                    <div>
                                                        <div className="font-medium">{employee.department.name}</div>
                                                        <div className="text-slate-500 text-xs">
                                                            Kode: {employee.department.code}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-slate-400 italic">Tidak ada</span>
                                                )}
                                            </dd>
                                        </div>

                                        {/* Status */}
                                        <div>
                                            <dt className="text-sm font-medium text-slate-500 mb-2">
                                                Status Karyawan
                                            </dt>
                                            <dd>
                                                {getStatusBadge(employee.status)}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        {/* Side Info */}
                        <div className="space-y-6">
                            {/* Status Card */}
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="text-lg font-medium text-slate-900">
                                        Status Karyawan
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="text-center">
                                        {getStatusBadge(employee.status)}
                                        <p className="text-xs text-slate-500 mt-2">
                                            Status dapat diubah melalui menu edit
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Info */}
                            <div className="card">
                                <div className="card-header">
                                    <h3 className="text-lg font-medium text-slate-900">
                                        Informasi Tambahan
                                    </h3>
                                </div>
                                <div className="card-body">
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-slate-500">Data dibuat:</span>
                                            <span className="text-slate-900">
                                                {new Date(employee.created_at).toLocaleDateString('id-ID')}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="text-slate-500">Terakhir diupdate:</span>
                                            <span className="text-slate-900">
                                                {new Date(employee.updated_at).toLocaleDateString('id-ID')}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Action Guide */}
                            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div className="flex items-start">
                                    <CheckBadgeIcon className="w-5 h-5 text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <div>
                                        <h4 className="text-sm font-medium text-green-900">
                                            Data Lengkap
                                        </h4>
                                        <p className="text-sm text-green-700 mt-1">
                                            Informasi karyawan telah tersimpan dalam sistem.
                                            Anda dapat mengedit atau menghapus data melalui tombol di atas.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Validation Warnings */}
                            {(!employee.position) && (
                                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <div className="flex items-start">
                                        <ExclamationTriangleIcon className="w-5 h-5 text-yellow-500 mt-0.5 mr-3 flex-shrink-0" />
                                        <div>
                                            <h4 className="text-sm font-medium text-yellow-900">
                                                Data Belum Lengkap
                                            </h4>
                                            <div className="text-sm text-yellow-700 mt-1">
                                                <p>Jabatan belum diisi</p>
                                            </div>
                                        </div>
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
