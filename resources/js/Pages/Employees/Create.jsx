// resources/js/Pages/Employees/Create.jsx

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    UserPlusIcon,
    UserIcon,
    BuildingOfficeIcon,
    IdentificationIcon,
    CalendarDaysIcon,
    DocumentTextIcon
} from '@heroicons/react/24/outline';

export default function Create({ auth, departments }) {
    const { data, setData, post, processing, errors } = useForm({
        employee_id: '',
        name: '',
        department_id: '',
        position: '',
        status: 'active',
        hire_date: '',
        background_check_date: '',
        background_check_status: '',
        background_check_notes: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('employees.store'));
    };

    const generateEmployeeId = () => {
        const timestamp = Date.now().toString().slice(-6);
        const random = Math.floor(Math.random() * 100).toString().padStart(2, '0');
        const generated = `GAP${timestamp}${random}`;
        setData('employee_id', generated);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('employees.index')}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-2" />
                            Back to Employees
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Tambah Karyawan Baru
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Tambahkan data karyawan baru ke sistem training GAPURA
                            </p>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title="Tambah Karyawan" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6">
                        {/* Basic Information */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <UserIcon className="w-5 h-5 mr-2" />
                                    Informasi Dasar
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Data identitas dan informasi dasar karyawan
                                </p>
                            </div>
                            <div className="px-6 py-4 space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Employee ID */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Employee ID / NIP *
                                        </label>
                                        <div className="flex space-x-2">
                                            <input
                                                type="text"
                                                value={data.employee_id}
                                                onChange={(e) => setData('employee_id', e.target.value)}
                                                className={`flex-1 border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                    errors.employee_id ? 'border-red-300' : 'border-gray-300'
                                                }`}
                                                placeholder="GAP001 or auto-generate"
                                                required
                                            />
                                            <button
                                                type="button"
                                                onClick={generateEmployeeId}
                                                className="px-3 py-2 text-sm bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500"
                                            >
                                                Generate
                                            </button>
                                        </div>
                                        {errors.employee_id && (
                                            <p className="mt-2 text-sm text-red-600">{errors.employee_id}</p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500">
                                            Unique identifier untuk karyawan (contoh: GAP001, EMP123)
                                        </p>
                                    </div>

                                    {/* Full Name */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Nama Lengkap *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.name ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            placeholder="Nama lengkap karyawan"
                                            required
                                        />
                                        {errors.name && (
                                            <p className="mt-2 text-sm text-red-600">{errors.name}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Department */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Department
                                        </label>
                                        <select
                                            value={data.department_id}
                                            onChange={(e) => setData('department_id', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.department_id ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                        >
                                            <option value="">Pilih Department</option>
                                            {departments.map((dept) => (
                                                <option key={dept.id} value={dept.id}>
                                                    {dept.name} ({dept.code})
                                                </option>
                                            ))}
                                        </select>
                                        {errors.department_id && (
                                            <p className="mt-2 text-sm text-red-600">{errors.department_id}</p>
                                        )}
                                    </div>

                                    {/* Position */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Jabatan / Position
                                        </label>
                                        <input
                                            type="text"
                                            value={data.position}
                                            onChange={(e) => setData('position', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.position ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            placeholder="Manager, Officer, Staff, etc."
                                        />
                                        {errors.position && (
                                            <p className="mt-2 text-sm text-red-600">{errors.position}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Status */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Status *
                                        </label>
                                        <select
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.status ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            required
                                        >
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        {errors.status && (
                                            <p className="mt-2 text-sm text-red-600">{errors.status}</p>
                                        )}
                                    </div>

                                    {/* Hire Date */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Tanggal Bergabung
                                        </label>
                                        <input
                                            type="date"
                                            value={data.hire_date}
                                            onChange={(e) => setData('hire_date', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.hire_date ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.hire_date && (
                                            <p className="mt-2 text-sm text-red-600">{errors.hire_date}</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Background Check Information */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <DocumentTextIcon className="w-5 h-5 mr-2" />
                                    Background Check
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Informasi pemeriksaan latar belakang karyawan (opsional)
                                </p>
                            </div>
                            <div className="px-6 py-4 space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Background Check Date */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Tanggal Background Check
                                        </label>
                                        <input
                                            type="date"
                                            value={data.background_check_date}
                                            onChange={(e) => setData('background_check_date', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.background_check_date ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.background_check_date && (
                                            <p className="mt-2 text-sm text-red-600">{errors.background_check_date}</p>
                                        )}
                                    </div>

                                    {/* Background Check Status */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Status Background Check
                                        </label>
                                        <select
                                            value={data.background_check_status}
                                            onChange={(e) => setData('background_check_status', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.background_check_status ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                        >
                                            <option value="">Pilih Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="cleared">Cleared</option>
                                            <option value="failed">Failed</option>
                                        </select>
                                        {errors.background_check_status && (
                                            <p className="mt-2 text-sm text-red-600">{errors.background_check_status}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Background Check Notes */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Catatan Background Check
                                    </label>
                                    <textarea
                                        value={data.background_check_notes}
                                        onChange={(e) => setData('background_check_notes', e.target.value)}
                                        rows={4}
                                        className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                            errors.background_check_notes ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        placeholder="Catatan tambahan mengenai background check..."
                                    />
                                    {errors.background_check_notes && (
                                        <p className="mt-2 text-sm text-red-600">{errors.background_check_notes}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Form Actions */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-gray-600">
                                        * Field yang wajib diisi
                                    </div>
                                    <div className="flex space-x-3">
                                        <Link
                                            href={route('employees.index')}
                                            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            Cancel
                                        </Link>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                                        >
                                            {processing ? (
                                                <>
                                                    <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Creating...
                                                </>
                                            ) : (
                                                <>
                                                    <UserPlusIcon className="w-4 h-4 mr-2" />
                                                    Tambah Karyawan
                                                </>
                                            )}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
