// resources/js/Pages/Employees/Edit.jsx

import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import {
    ArrowLeftIcon,
    PencilIcon,
    IdentificationIcon,
    UserIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    ExclamationTriangleIcon
} from '@heroicons/react/24/outline';

export default function Edit({ auth, employee, departments }) {
    const { data, setData, put, processing, errors } = useForm({
        employee_id: employee.employee_id || '',
        name: employee.name || '',
        position: employee.position || '',
        department_id: employee.department_id || '',
        status: employee.status || 'active'
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('employees.update', employee.id));
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Edit Karyawan - ${employee.name}`} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center space-x-4 mb-4">
                            <Link
                                href={route('employees.index')}
                                className="btn-secondary"
                            >
                                <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                Kembali
                            </Link>
                            <Link
                                href={route('employees.show', employee.id)}
                                className="btn-secondary"
                            >
                                Lihat Detail
                            </Link>
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 flex items-center">
                                <PencilIcon className="w-8 h-8 text-green-600 mr-3" />
                                Edit Data Karyawan
                            </h1>
                            <p className="mt-2 text-sm text-slate-600">
                                Perbarui informasi karyawan: <strong>{employee.name}</strong>
                            </p>
                        </div>
                    </div>

                    {/* Current Data Info */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div className="flex items-start">
                            <ExclamationTriangleIcon className="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" />
                            <div>
                                <h4 className="text-sm font-medium text-blue-900">Data Saat Ini</h4>
                                <div className="text-sm text-blue-700 mt-1 space-y-1">
                                    <p><strong>NIP:</strong> {employee.employee_id}</p>
                                    <p><strong>Nama:</strong> {employee.name}</p>
                                    <p><strong>Jabatan:</strong> {employee.position || 'Belum diisi'}</p>
                                    <p><strong>Departemen:</strong> {employee.department?.name || 'Tidak ada'}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="card">
                        <div className="card-header">
                            <h3 className="text-lg font-medium text-slate-900">
                                Perbarui Informasi Karyawan
                            </h3>
                            <p className="text-sm text-slate-600 mt-1">
                                Ubah data karyawan sesuai kebutuhan
                            </p>
                        </div>

                        <form onSubmit={submit} className="card-body space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* NIP */}
                                <div>
                                    <InputLabel htmlFor="employee_id" value="NIP" className="flex items-center">
                                        <IdentificationIcon className="w-4 h-4 mr-2 text-slate-400" />
                                        Nomor Induk Pegawai (NIP) *
                                    </InputLabel>
                                    <TextInput
                                        id="employee_id"
                                        name="employee_id"
                                        value={data.employee_id}
                                        className="mt-1 block w-full"
                                        autoComplete="off"
                                        isFocused={true}
                                        onChange={(e) => setData('employee_id', e.target.value)}
                                        placeholder="Contoh: MPGA-001"
                                    />
                                    <InputError message={errors.employee_id} className="mt-2" />
                                    <p className="text-xs text-slate-500 mt-1">
                                        Nomor induk pegawai unik dalam sistem
                                    </p>
                                </div>

                                {/* Nama */}
                                <div>
                                    <InputLabel htmlFor="name" value="Nama Lengkap" className="flex items-center">
                                        <UserIcon className="w-4 h-4 mr-2 text-slate-400" />
                                        Nama Lengkap Karyawan *
                                    </InputLabel>
                                    <TextInput
                                        id="name"
                                        name="name"
                                        value={data.name}
                                        className="mt-1 block w-full"
                                        autoComplete="name"
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Contoh: Ahmad Suryanto"
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                    <p className="text-xs text-slate-500 mt-1">
                                        Nama lengkap sesuai identitas resmi
                                    </p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Jabatan */}
                                <div>
                                    <InputLabel htmlFor="position" value="Jabatan" className="flex items-center">
                                        <BriefcaseIcon className="w-4 h-4 mr-2 text-slate-400" />
                                        Jabatan *
                                    </InputLabel>
                                    <TextInput
                                        id="position"
                                        name="position"
                                        value={data.position}
                                        className="mt-1 block w-full"
                                        autoComplete="organization-title"
                                        onChange={(e) => setData('position', e.target.value)}
                                        placeholder="Contoh: Manager SDM"
                                    />
                                    <InputError message={errors.position} className="mt-2" />
                                    <p className="text-xs text-slate-500 mt-1">
                                        Posisi atau jabatan karyawan
                                    </p>
                                </div>

                                {/* Departemen */}
                                <div>
                                    <InputLabel htmlFor="department_id" value="Departemen" className="flex items-center">
                                        <BuildingOfficeIcon className="w-4 h-4 mr-2 text-slate-400" />
                                        Departemen
                                    </InputLabel>
                                    <select
                                        id="department_id"
                                        name="department_id"
                                        value={data.department_id}
                                        className="form-input mt-1 block w-full"
                                        onChange={(e) => setData('department_id', e.target.value)}
                                    >
                                        <option value="">-- Pilih Departemen --</option>
                                        {departments?.map((dept) => (
                                            <option
                                                key={dept.id}
                                                value={dept.id}
                                                selected={dept.id === employee.department_id}
                                            >
                                                {dept.name} ({dept.code})
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.department_id} className="mt-2" />
                                    <p className="text-xs text-slate-500 mt-1">
                                        Pilih departemen tempat karyawan bekerja
                                    </p>
                                </div>
                            </div>

                            {/* Status */}
                            <div>
                                <InputLabel htmlFor="status" value="Status Karyawan" />
                                <div className="mt-2 space-y-3">
                                    <label className="flex items-center">
                                        <input
                                            type="radio"
                                            name="status"
                                            value="active"
                                            checked={data.status === 'active'}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className="text-green-600 focus:ring-green-500 border-slate-300"
                                        />
                                        <span className="ml-3 flex items-center">
                                            <span className="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                            <span className="text-sm text-slate-700">Aktif</span>
                                        </span>
                                    </label>
                                    <label className="flex items-center">
                                        <input
                                            type="radio"
                                            name="status"
                                            value="inactive"
                                            checked={data.status === 'inactive'}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className="text-red-600 focus:ring-red-500 border-slate-300"
                                        />
                                        <span className="ml-3 flex items-center">
                                            <span className="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                            <span className="text-sm text-slate-700">Tidak Aktif</span>
                                        </span>
                                    </label>
                                </div>
                                <InputError message={errors.status} className="mt-2" />
                            </div>

                            {/* Form Actions */}
                            <div className="flex items-center justify-between pt-6 border-t border-slate-200">
                                <div className="text-sm text-slate-600">
                                    <span className="text-red-500">*</span> Wajib diisi
                                </div>
                                <div className="flex items-center space-x-4">
                                    <Link
                                        href={route('employees.index')}
                                        className="btn-secondary"
                                    >
                                        Batal
                                    </Link>
                                    <PrimaryButton disabled={processing}>
                                        {processing ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Menyimpan...
                                            </>
                                        ) : (
                                            <>
                                                <PencilIcon className="w-4 h-4 mr-2" />
                                                Perbarui Data
                                            </>
                                        )}
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
