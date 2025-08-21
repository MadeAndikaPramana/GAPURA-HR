// resources/js/Pages/ImportExport/Index.jsx

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowUpTrayIcon,
    ArrowDownTrayIcon,
    DocumentArrowUpIcon,
    DocumentArrowDownIcon,
    UsersIcon,
    ClipboardDocumentListIcon,
    DocumentIcon,
    ChartBarIcon,
    CloudArrowUpIcon,
    CloudArrowDownIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon
} from '@heroicons/react/24/outline';

export default function Index({ auth, recentImports, exportStats }) {
    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getStatusIcon = (status) => {
        switch (status) {
            case 'completed':
                return <CheckCircleIcon className="w-5 h-5 text-green-500" />;
            case 'processing':
                return <ClockIcon className="w-5 h-5 text-yellow-500" />;
            case 'failed':
                return <XCircleIcon className="w-5 h-5 text-red-500" />;
            default:
                return <ExclamationTriangleIcon className="w-5 h-5 text-gray-500" />;
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'completed':
                return 'bg-green-100 text-green-800';
            case 'processing':
                return 'bg-yellow-100 text-yellow-800';
            case 'failed':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Import/Export Data
                        </h2>
                        <p className="text-sm text-gray-600 mt-1">
                            Import/Export data dengan template dan batch operations
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Import/Export" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-blue-100 text-sm font-medium">Total Exports</p>
                                    <p className="text-3xl font-bold">{exportStats.total_exports_this_month}</p>
                                    <p className="text-blue-100 text-xs mt-1">This month</p>
                                </div>
                                <ArrowDownTrayIcon className="w-12 h-12 text-blue-200" />
                            </div>
                        </div>

                        <div className="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-green-100 text-sm font-medium">Records Exported</p>
                                    <p className="text-3xl font-bold">{exportStats.total_records_exported}</p>
                                    <p className="text-green-100 text-xs mt-1">All time</p>
                                </div>
                                <DocumentArrowDownIcon className="w-12 h-12 text-green-200" />
                            </div>
                        </div>

                        <div className="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-purple-100 text-sm font-medium">Avg Export Size</p>
                                    <p className="text-3xl font-bold">{exportStats.average_export_size}</p>
                                    <p className="text-purple-100 text-xs mt-1">Records</p>
                                </div>
                                <ChartBarIcon className="w-12 h-12 text-purple-200" />
                            </div>
                        </div>

                        <div className="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-orange-100 text-sm font-medium">Most Exported</p>
                                    <p className="text-lg font-bold capitalize">{exportStats.most_exported_type}</p>
                                    <p className="text-orange-100 text-xs mt-1">Data type</p>
                                </div>
                                <DocumentIcon className="w-12 h-12 text-orange-200" />
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* Import Section */}
                        <div className="space-y-6">
                            <div className="bg-white shadow rounded-lg">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                        <ArrowUpTrayIcon className="w-5 h-5 mr-2 text-blue-600" />
                                        Import Data
                                    </h3>
                                    <p className="text-sm text-gray-600 mt-1">
                                        Upload Excel/CSV files untuk import data ke sistem
                                    </p>
                                </div>
                                <div className="p-6 space-y-4">
                                    {/* Employee Import */}
                                    <div className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                                        <div className="flex items-center space-x-4">
                                            <div className="flex-shrink-0">
                                                <div className="p-2 bg-blue-100 rounded-lg">
                                                    <UsersIcon className="w-6 h-6 text-blue-600" />
                                                </div>
                                            </div>
                                            <div className="flex-1">
                                                <h4 className="text-sm font-medium text-gray-900">Employee Data</h4>
                                                <p className="text-sm text-gray-500">Import master data karyawan dari Excel</p>
                                            </div>
                                            <div className="flex space-x-2">
                                                <Link
                                                    href={route('import-export.templates.employees')}
                                                    className="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-md"
                                                >
                                                    Template
                                                </Link>
                                                <Link
                                                    href={route('import-export.employees.import')}
                                                    className="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md"
                                                >
                                                    Import
                                                </Link>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Training Records Import */}
                                    <div className="border border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors">
                                        <div className="flex items-center space-x-4">
                                            <div className="flex-shrink-0">
                                                <div className="p-2 bg-green-100 rounded-lg">
                                                    <ClipboardDocumentListIcon className="w-6 h-6 text-green-600" />
                                                </div>
                                            </div>
                                            <div className="flex-1">
                                                <h4 className="text-sm font-medium text-gray-900">Training Records</h4>
                                                <p className="text-sm text-gray-500">Import data training dan sertifikasi</p>
                                            </div>
                                            <div className="flex space-x-2">
                                                <Link
                                                    href={route('import-export.templates.training-records')}
                                                    className="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-md"
                                                >
                                                    Template
                                                </Link>
                                                <Link
                                                    href={route('import-export.training-records.import')}
                                                    className="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md"
                                                >
                                                    Import
                                                </Link>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Certificates Import */}
                                    <div className="border border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-colors">
                                        <div className="flex items-center space-x-4">
                                            <div className="flex-shrink-0">
                                                <div className="p-2 bg-purple-100 rounded-lg">
                                                    <DocumentIcon className="w-6 h-6 text-purple-600" />
                                                </div>
                                            </div>
                                            <div className="flex-1">
                                                <h4 className="text-sm font-medium text-gray-900">Certificates</h4>
                                                <p className="text-sm text-gray-500">Import data sertifikat detail</p>
                                            </div>
                                            <div className="flex space-x-2">
                                                <Link
                                                    href={route('import-export.templates.certificates')}
                                                    className="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-md"
                                                >
                                                    Template
                                                </Link>
                                                <Link
                                                    href={route('import-export.certificates.import')}
                                                    className="text-xs bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded-md"
                                                >
                                                    Import
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Export Section */}
                        <div className="space-y-6">
                            <div className="bg-white shadow rounded-lg">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                        <ArrowDownTrayIcon className="w-5 h-5 mr-2 text-green-600" />
                                        Export Data
                                    </h3>
                                    <p className="text-sm text-gray-600 mt-1">
                                        Download data dalam format Excel untuk analisis dan backup
                                    </p>
                                </div>
                                <div className="p-6 space-y-4">
                                    {/* Employee Export */}
                                    <div className="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-4">
                                                <div className="p-2 bg-blue-100 rounded-lg">
                                                    <UsersIcon className="w-6 h-6 text-blue-600" />
                                                </div>
                                                <div>
                                                    <h4 className="text-sm font-medium text-gray-900">Employee Data</h4>
                                                    <p className="text-sm text-gray-500">Export master data karyawan</p>
                                                </div>
                                            </div>
                                            <Link
                                                href={route('import-export.employees.export')}
                                                className="inline-flex items-center px-3 py-2 border border-blue-300 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            >
                                                <CloudArrowDownIcon className="w-4 h-4 mr-2" />
                                                Export
                                            </Link>
                                        </div>
                                    </div>

                                    {/* Training Records Export */}
                                    <div className="border border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-4">
                                                <div className="p-2 bg-green-100 rounded-lg">
                                                    <ClipboardDocumentListIcon className="w-6 h-6 text-green-600" />
                                                </div>
                                                <div>
                                                    <h4 className="text-sm font-medium text-gray-900">Training Records</h4>
                                                    <p className="text-sm text-gray-500">Export data training dan sertifikasi</p>
                                                </div>
                                            </div>
                                            <Link
                                                href={route('import-export.training-records.export')}
                                                className="inline-flex items-center px-3 py-2 border border-green-300 shadow-sm text-sm leading-4 font-medium rounded-md text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                            >
                                                <CloudArrowDownIcon className="w-4 h-4 mr-2" />
                                                Export
                                            </Link>
                                        </div>
                                    </div>

                                    {/* Certificates Export */}
                                    <div className="border border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-colors">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-4">
                                                <div className="p-2 bg-purple-100 rounded-lg">
                                                    <DocumentIcon className="w-6 h-6 text-purple-600" />
                                                </div>
                                                <div>
                                                    <h4 className="text-sm font-medium text-gray-900">Certificates</h4>
                                                    <p className="text-sm text-gray-500">Export data sertifikat detail</p>
                                                </div>
                                            </div>
                                            <Link
                                                href={route('import-export.certificates.export')}
                                                className="inline-flex items-center px-3 py-2 border border-purple-300 shadow-sm text-sm leading-4 font-medium rounded-md text-purple-700 bg-purple-50 hover:bg-purple-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                                            >
                                                <CloudArrowDownIcon className="w-4 h-4 mr-2" />
                                                Export
                                            </Link>
                                        </div>
                                    </div>

                                    {/* Compliance Report */}
                                    <div className="border border-gray-200 rounded-lg p-4 hover:border-orange-300 transition-colors">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-4">
                                                <div className="p-2 bg-orange-100 rounded-lg">
                                                    <ChartBarIcon className="w-6 h-6 text-orange-600" />
                                                </div>
                                                <div>
                                                    <h4 className="text-sm font-medium text-gray-900">Compliance Report</h4>
                                                    <p className="text-sm text-gray-500">Export comprehensive compliance report</p>
                                                </div>
                                            </div>
                                            <Link
                                                href={route('import-export.compliance-report.export')}
                                                className="inline-flex items-center px-3 py-2 border border-orange-300 shadow-sm text-sm leading-4 font-medium rounded-md text-orange-700 bg-orange-50 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                                            >
                                                <CloudArrowDownIcon className="w-4 h-4 mr-2" />
                                                Export
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Recent Import History */}
                    <div className="mt-8 bg-white shadow rounded-lg">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h3 className="text-lg font-medium text-gray-900">Recent Import History</h3>
                            <p className="text-sm text-gray-600 mt-1">
                                Track your recent data import activities
                            </p>
                        </div>
                        <div className="divide-y divide-gray-200">
                            {recentImports && recentImports.length > 0 ? (
                                recentImports.map((importRecord, index) => (
                                    <div key={index} className="px-6 py-4 hover:bg-gray-50">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-4">
                                                {getStatusIcon(importRecord.status)}
                                                <div>
                                                    <h4 className="text-sm font-medium text-gray-900">
                                                        {importRecord.data_type.replace('_', ' ').toUpperCase()} Import
                                                    </h4>
                                                    <p className="text-sm text-gray-500">
                                                        {importRecord.filename} • {formatDate(importRecord.imported_at)}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center space-x-4">
                                                <div className="text-right text-sm">
                                                    <p className="text-gray-900">
                                                        {importRecord.records_successful}/{importRecord.records_processed} records
                                                    </p>
                                                    {importRecord.records_failed > 0 && (
                                                        <p className="text-red-600">{importRecord.records_failed} failed</p>
                                                    )}
                                                </div>
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(importRecord.status)}`}>
                                                    {importRecord.status}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="px-6 py-8 text-center">
                                    <CloudArrowUpIcon className="w-12 h-12 mx-auto text-gray-400 mb-4" />
                                    <p className="text-lg font-medium text-gray-900">No import history</p>
                                    <p className="text-sm text-gray-500">Start by importing your first data file.</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
