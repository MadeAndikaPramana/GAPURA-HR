// resources/js/Pages/SDM/Import.jsx
// Excel Import Page - Clean SDM Structure

import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    DocumentArrowUpIcon,
    DocumentArrowDownIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    InformationCircleIcon,
    ArrowLeftIcon,
    CloudArrowUpIcon,
    DocumentIcon
} from '@heroicons/react/24/outline';

export default function Import({ auth, departments, importHistory = [] }) {
    const { data, setData, post, processing, errors, progress } = useForm({
        excel_file: null,
        update_existing: false,
        create_departments: false
    });

    const [dragActive, setDragActive] = useState(false);
    const [fileInfo, setFileInfo] = useState(null);

    const handleFileSelect = (file) => {
        setData('excel_file', file);
        setFileInfo({
            name: file.name,
            size: (file.size / 1024 / 1024).toFixed(2) + ' MB',
            type: file.type
        });
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        setDragActive(true);
    };

    const handleDragLeave = (e) => {
        e.preventDefault();
        setDragActive(false);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setDragActive(false);

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            if (file.type.includes('spreadsheet') || file.name.endsWith('.xlsx') || file.name.endsWith('.xls')) {
                handleFileSelect(file);
            } else {
                alert('Please upload a valid Excel file (.xlsx or .xls)');
            }
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('sdm.import.process'));
    };

    const downloadTemplate = () => {
        window.open(route('sdm.download-template'), '_blank');
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Import Employees from Excel - SDM" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Header */}
                    <div className="mb-8">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900 flex items-center">
                                    <DocumentArrowUpIcon className="w-8 h-8 mr-3 text-blue-600" />
                                    Import Employees from Excel
                                </h1>
                                <p className="mt-2 text-gray-600">
                                    Upload employee data from Excel file with automatic container creation
                                </p>
                            </div>
                            <a
                                href={route('sdm.index')}
                                className="btn-secondary"
                            >
                                <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                Back to SDM
                            </a>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">

                        {/* Left Column - Import Form */}
                        <div className="space-y-6">

                            {/* Step 1: Download Template */}
                            <div className="bg-white rounded-lg border border-gray-200 p-6">
                                <div className="flex items-start">
                                    <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-4 mt-1">
                                        <span className="text-blue-600 font-bold text-sm">1</span>
                                    </div>
                                    <div className="flex-1">
                                        <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                            Download Excel Template
                                        </h3>
                                        <p className="text-gray-600 mb-4">
                                            Start with our pre-formatted template to ensure data compatibility
                                        </p>
                                        <button
                                            onClick={downloadTemplate}
                                            className="btn-secondary"
                                        >
                                            <DocumentArrowDownIcon className="w-4 h-4 mr-2" />
                                            Download Template
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* Step 2: Upload File */}
                            <div className="bg-white rounded-lg border border-gray-200 p-6">
                                <div className="flex items-start">
                                    <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-4 mt-1">
                                        <span className="text-blue-600 font-bold text-sm">2</span>
                                    </div>
                                    <div className="flex-1">
                                        <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                            Upload Your Excel File
                                        </h3>

                                        <form onSubmit={handleSubmit} className="space-y-4">
                                            {/* File Upload Area */}
                                            <div
                                                className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
                                                    dragActive
                                                        ? 'border-blue-400 bg-blue-50'
                                                        : 'border-gray-300 hover:border-gray-400'
                                                }`}
                                                onDragOver={handleDragOver}
                                                onDragLeave={handleDragLeave}
                                                onDrop={handleDrop}
                                            >
                                                {fileInfo ? (
                                                    <div className="space-y-2">
                                                        <DocumentIcon className="w-12 h-12 text-green-600 mx-auto" />
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {fileInfo.name}
                                                        </div>
                                                        <div className="text-xs text-gray-500">
                                                            {fileInfo.size} • {fileInfo.type}
                                                        </div>
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                setFileInfo(null);
                                                                setData('excel_file', null);
                                                            }}
                                                            className="text-sm text-red-600 hover:text-red-800"
                                                        >
                                                            Remove file
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <div className="space-y-2">
                                                        <CloudArrowUpIcon className="w-12 h-12 text-gray-400 mx-auto" />
                                                        <div className="text-sm font-medium text-gray-900">
                                                            Drop your Excel file here, or click to browse
                                                        </div>
                                                        <div className="text-xs text-gray-500">
                                                            Supports .xlsx and .xls files up to 10MB
                                                        </div>
                                                        <input
                                                            type="file"
                                                            accept=".xlsx,.xls"
                                                            onChange={(e) => {
                                                                const file = e.target.files[0];
                                                                if (file) handleFileSelect(file);
                                                            }}
                                                            className="hidden"
                                                            id="file-upload"
                                                        />
                                                        <label
                                                            htmlFor="file-upload"
                                                            className="btn-primary cursor-pointer"
                                                        >
                                                            Choose File
                                                        </label>
                                                    </div>
                                                )}
                                            </div>

                                            {errors.excel_file && (
                                                <div className="text-red-600 text-sm">
                                                    {errors.excel_file}
                                                </div>
                                            )}

                                            {/* Import Options */}
                                            <div className="space-y-3">
                                                <div className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        id="update_existing"
                                                        checked={data.update_existing}
                                                        onChange={(e) => setData('update_existing', e.target.checked)}
                                                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                    />
                                                    <label htmlFor="update_existing" className="ml-2 text-sm text-gray-700">
                                                        Update existing employees (based on Employee ID)
                                                    </label>
                                                </div>

                                                <div className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        id="create_departments"
                                                        checked={data.create_departments}
                                                        onChange={(e) => setData('create_departments', e.target.checked)}
                                                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                    />
                                                    <label htmlFor="create_departments" className="ml-2 text-sm text-gray-700">
                                                        Auto-create new departments if not found
                                                    </label>
                                                </div>
                                            </div>

                                            {/* Submit Button */}
                                            <button
                                                type="submit"
                                                disabled={!data.excel_file || processing}
                                                className="btn-primary w-full disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                {processing ? (
                                                    <>
                                                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                                        Importing... {progress && `${progress.percentage}%`}
                                                    </>
                                                ) : (
                                                    <>
                                                        <DocumentArrowUpIcon className="w-4 h-4 mr-2" />
                                                        Import Employees
                                                    </>
                                                )}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {/* Import Guidelines */}
                            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div className="flex">
                                    <ExclamationTriangleIcon className="w-5 h-5 text-yellow-400 mr-3 mt-0.5" />
                                    <div>
                                        <h4 className="text-sm font-semibold text-yellow-800 mb-2">
                                            Import Guidelines
                                        </h4>
                                        <ul className="text-sm text-yellow-700 space-y-1">
                                            <li>• Employee ID must be unique for each employee</li>
                                            <li>• Employee Name is required</li>
                                            <li>• Email addresses must be unique if provided</li>
                                            <li>• Department codes should match existing departments</li>
                                            <li>• Digital containers are auto-created for each employee</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Right Column - Info & History */}
                        <div className="space-y-6">

                            {/* Expected Format */}
                            <div className="bg-white rounded-lg border border-gray-200 p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <InformationCircleIcon className="w-5 h-5 text-blue-600 mr-2" />
                                    Expected Excel Format
                                </h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-3 py-2 text-left font-medium text-gray-700">Column</th>
                                                <th className="px-3 py-2 text-left font-medium text-gray-700">Required</th>
                                                <th className="px-3 py-2 text-left font-medium text-gray-700">Example</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200">
                                            <tr>
                                                <td className="px-3 py-2 font-mono">employee_id</td>
                                                <td className="px-3 py-2 text-red-600">Yes</td>
                                                <td className="px-3 py-2">EMP001</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono">name</td>
                                                <td className="px-3 py-2 text-red-600">Yes</td>
                                                <td className="px-3 py-2">John Doe</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono">email</td>
                                                <td className="px-3 py-2 text-gray-500">No</td>
                                                <td className="px-3 py-2">john@company.com</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono">phone</td>
                                                <td className="px-3 py-2 text-gray-500">No</td>
                                                <td className="px-3 py-2">081234567890</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono">department</td>
                                                <td className="px-3 py-2 text-gray-500">No</td>
                                                <td className="px-3 py-2">IT</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono">position</td>
                                                <td className="px-3 py-2 text-gray-500">No</td>
                                                <td className="px-3 py-2">Software Engineer</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono">hire_date</td>
                                                <td className="px-3 py-2 text-gray-500">No</td>
                                                <td className="px-3 py-2">2024-01-15</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono">status</td>
                                                <td className="px-3 py-2 text-gray-500">No</td>
                                                <td className="px-3 py-2">active</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Import History */}
                            {importHistory.length > 0 && (
                                <div className="bg-white rounded-lg border border-gray-200 p-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                        Recent Imports
                                    </h3>
                                    <div className="space-y-3">
                                        {importHistory.map((import_record, index) => (
                                            <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div>
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {import_record.file}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {import_record.date}
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="text-sm">
                                                        <span className="text-green-600">+{import_record.created}</span>
                                                        {import_record.updated > 0 && (
                                                            <span className="text-blue-600 ml-2">~{import_record.updated}</span>
                                                        )}
                                                        {import_record.errors > 0 && (
                                                            <span className="text-red-600 ml-2">!{import_record.errors}</span>
                                                        )}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {import_record.created + import_record.updated} processed
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Success Tips */}
                            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div className="flex">
                                    <CheckCircleIcon className="w-5 h-5 text-green-400 mr-3 mt-0.5" />
                                    <div>
                                        <h4 className="text-sm font-semibold text-green-800 mb-2">
                                            Pro Tips for Successful Import
                                        </h4>
                                        <ul className="text-sm text-green-700 space-y-1">
                                            <li>• Use the template to avoid formatting issues</li>
                                            <li>• Validate data before uploading</li>
                                            <li>• Employee containers are created automatically</li>
                                            <li>• You can import certificates separately later</li>
                                            <li>• Backup your data before large imports</li>
                                        </ul>
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
