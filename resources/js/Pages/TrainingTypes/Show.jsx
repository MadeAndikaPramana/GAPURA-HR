// resources/js/Pages/TrainingTypes/Show.jsx
import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    TagIcon,
    ClipboardDocumentListIcon,
    UserGroupIcon,
    ChartBarIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, trainingType, stats, recordsByStatus, recentRecords, employeesByDepartment }) {

    // ⭐ FUNCTION DELETE YANG BENAR
    const handleDelete = () => {
    // Konfirmasi yang lebih informatif
    const confirmMessage = `⚠️ PERINGATAN: Hapus Training Type "${trainingType.name}"?\n\n` +
                          `Tindakan ini akan:\n` +
                          `• Menghapus training type secara PERMANEN\n` +
                          `• TIDAK DAPAT dibatalkan\n\n` +
                          `📋 PERSYARATAN:\n` +
                          `Training type hanya bisa dihapus jika TIDAK ada karyawan yang memiliki training record dengan type ini.\n\n` +
                          `Lanjutkan hapus?`;

    if (confirm(confirmMessage)) {
        console.log('Sending DELETE request to:', route('training-types.destroy', trainingType.id));

        router.delete(route('training-types.destroy', trainingType.id), {
            onStart: () => {
                console.log('Delete request started');
            },
            onSuccess: (response) => {
                console.log('Delete successful:', response);
                // Success akan otomatis redirect ke index dengan flash message
            },
            onError: (errors) => {
                console.error('Delete failed:', errors);

                // Handle error response dengan pesan yang lebih informatif
                let errorMessage = 'Terjadi kesalahan tidak diketahui';

                // Handle different types of error responses
                if (typeof errors === 'object') {
                    if (errors.message) {
                        // Direct message from backend
                        errorMessage = errors.message;
                    } else if (errors.errors && typeof errors.errors === 'object') {
                        // Laravel validation errors
                        errorMessage = Object.values(errors.errors).flat().join('\n');
                    } else if (Object.keys(errors).length > 0) {
                        // Generic object errors
                        errorMessage = Object.values(errors).join('\n');
                    }
                } else if (typeof errors === 'string') {
                    errorMessage = errors;
                }

                // Show alert dengan styling yang lebih baik
                alert(errorMessage);
            },
            onFinish: () => {
                console.log('Delete request finished');
            }
        });
    }
    ;
};

    const getCategoryColor = (category) => {
        const colors = {
            safety: 'bg-red-100 text-red-800',
            operational: 'bg-blue-100 text-blue-800',
            security: 'bg-purple-100 text-purple-800',
            technical: 'bg-green-100 text-green-800'
        };
        return colors[category] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('training-types.index')}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-2" />
                            Back to Training Types
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {trainingType.name}
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Training Type Details & Statistics
                            </p>
                        </div>
                    </div>

                    {/* ⭐ HEADER BUTTONS DENGAN DELETE YANG BENAR */}
                    <div className="flex space-x-3">
                        <Link
                            href={route('training-types.edit', trainingType.id)}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit
                        </Link>
                        <button
                            onClick={handleDelete}
                            className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <TrashIcon className="w-4 h-4 mr-2" />
                            Delete
                        </button>
                    </div>
                </div>
            }
        >
            <Head title={`Training Type - ${trainingType.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">

                        {/* Training Type Information Card */}
                        <div className="lg:col-span-1">
                            <div className="bg-white shadow rounded-lg p-6">
                                <div className="flex items-center space-x-4 mb-6">
                                    <div className="flex-shrink-0">
                                        <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                            <TagIcon className="w-8 h-8 text-green-600" />
                                        </div>
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900">
                                            {trainingType.name}
                                        </h3>
                                        {trainingType.code && (
                                            <p className="text-sm text-gray-500">
                                                Code: {trainingType.code}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Category</dt>
                                        <dd className="mt-1">
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getCategoryColor(trainingType.category)}`}>
                                                {trainingType.category}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Validity Period</dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {trainingType.validity_months} months
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Status</dt>
                                        <dd className="mt-1">
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${trainingType.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                                {trainingType.is_active ? <CheckCircleIcon className="w-3 h-3 mr-1" /> : <XCircleIcon className="w-3 h-3 mr-1" />}
                                                {trainingType.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </dd>
                                    </div>
                                    {trainingType.description && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Description</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {trainingType.description}
                                            </dd>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Statistics Cards */}
                        <div className="lg:col-span-3">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                                <div className="bg-white rounded-lg shadow p-6">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0">
                                            <ClipboardDocumentListIcon className="h-8 w-8 text-blue-600" />
                                        </div>
                                        <div className="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt className="text-sm font-medium text-gray-500 truncate">
                                                    Total Certificates
                                                </dt>
                                                <dd className="text-lg font-medium text-gray-900">
                                                    {stats?.total_certificates || 0}
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <div className="bg-white rounded-lg shadow p-6">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0">
                                            <CheckCircleIcon className="h-8 w-8 text-green-600" />
                                        </div>
                                        <div className="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt className="text-sm font-medium text-gray-500 truncate">
                                                    Active
                                                </dt>
                                                <dd className="text-lg font-medium text-gray-900">
                                                    {stats?.active_certificates || 0}
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <div className="bg-white rounded-lg shadow p-6">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0">
                                            <ExclamationTriangleIcon className="h-8 w-8 text-yellow-600" />
                                        </div>
                                        <div className="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt className="text-sm font-medium text-gray-500 truncate">
                                                    Expiring Soon
                                                </dt>
                                                <dd className="text-lg font-medium text-gray-900">
                                                    {stats?.expiring_certificates || 0}
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <div className="bg-white rounded-lg shadow p-6">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0">
                                            <XCircleIcon className="h-8 w-8 text-red-600" />
                                        </div>
                                        <div className="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt className="text-sm font-medium text-gray-500 truncate">
                                                    Expired
                                                </dt>
                                                <dd className="text-lg font-medium text-gray-900">
                                                    {stats?.expired_certificates || 0}
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Compliance Rate */}
                            <div className="bg-white shadow rounded-lg p-6 mb-8">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-medium text-gray-900">Compliance Rate</h3>
                                    <div className="flex items-center space-x-2">
                                        <span className="text-2xl font-bold text-gray-900">
                                            {stats?.compliance_rate || '0'}%
                                        </span>
                                        {(stats?.compliance_rate || 0) >= 90 && (
                                            <CheckCircleIcon className="w-6 h-6 text-green-500" />
                                        )}
                                    </div>
                                </div>
                                <div className="mt-4">
                                    <div className="bg-gray-200 rounded-full h-2">
                                        <div
                                            className={`h-2 rounded-full ${(stats?.compliance_rate || 0) >= 90 ? 'bg-green-500' : (stats?.compliance_rate || 0) >= 70 ? 'bg-yellow-500' : 'bg-red-500'}`}
                                            style={{ width: `${stats?.compliance_rate || 0}%` }}
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            {/* Recent Training Records */}
                            <div className="bg-white shadow rounded-lg">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900">Recent Training Records</h3>
                                </div>
                                <div className="overflow-hidden">
                                    {recentRecords && recentRecords.length > 0 ? (
                                        <div className="divide-y divide-gray-200">
                                            {recentRecords.map((record) => (
                                                <div key={record.id} className="px-6 py-4 hover:bg-gray-50">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center">
                                                            <div className="flex-shrink-0">
                                                                <div className="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                                    <UserGroupIcon className="h-5 w-5 text-gray-600" />
                                                                </div>
                                                            </div>
                                                            <div className="ml-4">
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {record.employee?.name || 'Unknown Employee'}
                                                                </div>
                                                                <div className="text-sm text-gray-500">
                                                                    {record.employee?.department?.name || 'No Department'} • {record.certificate_number}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="text-right">
                                                            <div className="text-sm text-gray-900">
                                                                Expires: {record.expiry_date ? new Date(record.expiry_date).toLocaleDateString() : 'N/A'}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                {record.issuer}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="px-6 py-12 text-center">
                                            <ClipboardDocumentListIcon className="h-12 w-12 mx-auto text-gray-400" />
                                            <h3 className="mt-2 text-sm font-medium text-gray-900">No training records</h3>
                                            <p className="mt-1 text-sm text-gray-500">
                                                No employees have completed this training type yet.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
