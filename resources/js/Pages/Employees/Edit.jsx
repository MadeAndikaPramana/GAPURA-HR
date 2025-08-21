// resources/js/Pages/Employees/Edit.jsx

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    UserIcon,
    PencilIcon,
    BuildingOfficeIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    EyeIcon
} from '@heroicons/react/24/outline';

export default function Edit({ auth, employee, departments }) {
    const { data, setData, put, processing, errors, isDirty } = useForm({
        employee_id: employee.employee_id || '',
        name: employee.name || '',
        department_id: employee.department_id || '',
        position: employee.position || '',
        status: employee.status || 'active',
        hire_date: employee.hire_date || '',
        background_check_date: employee.background_check_date || '',
        background_check_status: employee.background_check_status || '',
        background_check_notes: employee.background_check_notes || '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('employees.update', employee.id));
    };

    const formatDate = (dateString) => {
        if (!dateString) return '';
        return new Date(dateString).toISOString().split('T')[0];
    };

    // Set formatted dates on component mount
    if (employee.hire_date && !data.hire_date) {
        setData('hire_date', formatDate(employee.hire_date));
    }
    if (employee.background_check_date && !data.background_check_date) {
        setData('background_check_date', formatDate(employee.background_check_date));
    }

    const hasChanges = isDirty;

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
                                Edit Karyawan: {employee.name}
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Update data karyawan {employee.employee_id}
                            </p>
                        </div>
                    </div>
                    <div className="flex space-x-2">
                        <Link
                            href={route('employees.show', employee.id)}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <EyeIcon className="w-4 h-4 mr-2" />
                            View Profile
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Edit ${employee.name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {/* Unsaved Changes Warning */}
                    {hasChanges && (
                        <div className="mb-6 bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <ExclamationTriangleIcon className="h-5 w-5 text-yellow-400" />
                                </div>
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-yellow-800">
                                        You have unsaved changes
                                    </h3>
                                    <div className="mt-2 text-sm text-yellow-700">
                                        <p>Don't forget to save your changes before leaving this page.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

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
                                        <input
                                            type="text"
                                            value={data.employee_id}
                                            onChange={(e) => setData('employee_id', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.employee_id ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            required
                                        />
                                        {errors.employee_id && (
                                            <p className="mt-2 text-sm text-red-600">{errors.employee_id}</p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500">
                                            Unique identifier untuk karyawan
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
                                        {employee.department && (
                                            <p className="mt-1 text-xs text-gray-500">
                                                Current: {employee.department.name}
                                            </p>
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
                                        {data.status !== employee.status && (
                                            <p className="mt-1 text-xs text-blue-600">
                                                Status will change from {employee.status} to {data.status}
                                            </p>
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
                                    Informasi pemeriksaan latar belakang karyawan
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

                        {/* Employee Statistics */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Employee Statistics
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Overview informasi karyawan ini
                                </p>
                            </div>
                            <div className="px-6 py-4">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-gray-900">
                                            {employee.training_records_count || 0}
                                        </div>
                                        <div className="text-sm text-gray-500">Training Records</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-green-600">
                                            {employee.created_at ?
                                                Math.floor((new Date() - new Date(employee.created_at)) / (1000 * 60 * 60 * 24))
                                                : 0
                                            }
                                        </div>
                                        <div className="text-sm text-gray-500">Days in System</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="text-2xl font-bold text-blue-600">
                                            {employee.updated_at ?
                                                Math.floor((new Date() - new Date(employee.updated_at)) / (1000 * 60 * 60 * 24))
                                                : 0
                                            }
                                        </div>
                                        <div className="text-sm text-gray-500">Days Since Last Update</div>
                                    </div>
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
                                            href={route('employees.show', employee.id)}
                                            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            Cancel
                                        </Link>
                                        <button
                                            type="submit"
                                            disabled={processing || !hasChanges}
                                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                                        >
                                            {processing ? (
                                                <>
                                                    <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Updating...
                                                </>
                                            ) : (
                                                <>
                                                    <PencilIcon className="w-4 h-4 mr-2" />
                                                    Update Employee
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
