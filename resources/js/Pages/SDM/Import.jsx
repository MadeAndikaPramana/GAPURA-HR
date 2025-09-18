// resources/js/Pages/SDM/Import.jsx
// Excel Import Page - Clean SDM Structure

import React, { useState, useRef } from 'react';
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
    DocumentIcon,
    XMarkIcon
} from '@heroicons/react/24/outline';

export default function Import({ auth, departments = [], importHistory = [], flash }) {
    const { data, setData, post, processing, errors, progress, clearErrors } = useForm({
        excel_file: null,
        update_existing: false,
        create_departments: false,
        sync_mode: 'merge',
        soft_delete: true,
        dry_run: false,
        is_sync: false
    });

    const [dragActive, setDragActive] = useState(false);
    const [fileInfo, setFileInfo] = useState(null);
    const [validationErrors, setValidationErrors] = useState([]);
    const fileInputRef = useRef(null);

    // File validation function
    const validateFile = (file) => {
        const errors = [];
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.ms-excel', // .xls
            'application/excel',
            'application/x-excel',
            'application/x-msexcel'
        ];

        if (!file) {
            errors.push('No file selected');
            return errors;
        }

        if (file.size > maxSize) {
            errors.push(`File size (${(file.size / 1024 / 1024).toFixed(2)}MB) exceeds maximum limit of 10MB`);
        }

        const isValidType = allowedTypes.includes(file.type) ||
                           file.name.toLowerCase().endsWith('.xlsx') ||
                           file.name.toLowerCase().endsWith('.xls');

        if (!isValidType) {
            errors.push('Invalid file type. Please upload an Excel file (.xlsx or .xls)');
        }

        return errors;
    };

    const handleFileSelect = (file) => {
        const fileErrors = validateFile(file);

        if (fileErrors.length > 0) {
            setValidationErrors(fileErrors);
            setFileInfo(null);
            setData('excel_file', null);
            return;
        }

        setValidationErrors([]);
        clearErrors('excel_file');
        setData('excel_file', file);
        setFileInfo({
            name: file.name,
            size: (file.size / 1024 / 1024).toFixed(2) + ' MB',
            type: file.type || 'Excel File',
            lastModified: new Date(file.lastModified).toLocaleDateString()
        });
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(true);
    };

    const handleDragLeave = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    };

    const removeFile = () => {
        setFileInfo(null);
        setData('excel_file', null);
        setValidationErrors([]);
        clearErrors('excel_file');
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!data.excel_file) {
            setValidationErrors(['Please select a file first']);
            return;
        }

        const fileErrors = validateFile(data.excel_file);
        if (fileErrors.length > 0) {
            setValidationErrors(fileErrors);
            return;
        }

        setValidationErrors([]);

        const routeName = data.is_sync ? 'sdm.upload-sync' : 'sdm.import.process';

        post(route(routeName), {
            forceFormData: true,
            onSuccess: () => {
                // Reset form on success
                removeFile();
                setData({
                    excel_file: null,
                    update_existing: false,
                    create_departments: false,
                    sync_mode: 'merge',
                    soft_delete: true,
                    dry_run: false,
                    is_sync: false
                });
            },
            onError: (errors) => {
                console.error('Import errors:', errors);
            }
        });
    };

    const downloadTemplate = () => {
        window.open(route('sdm.download-template'), '_blank');
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Import Employees from Excel - SDM" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Flash Messages */}
                    {flash?.success && (
                        <div className="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                            <div className="flex">
                                <CheckCircleIcon className="w-5 h-5 text-green-400 mr-3 mt-0.5" />
                                <div className="text-sm text-green-800">{flash.success}</div>
                            </div>
                        </div>
                    )}

                    {flash?.error && (
                        <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div className="flex">
                                <ExclamationTriangleIcon className="w-5 h-5 text-red-400 mr-3 mt-0.5" />
                                <div className="text-sm text-red-800">{flash.error}</div>
                            </div>
                        </div>
                    )}

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
                                className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
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
                                    <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-4 mt-1 flex-shrink-0">
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
                                            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
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
                                    <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-4 mt-1 flex-shrink-0">
                                        <span className="text-blue-600 font-bold text-sm">2</span>
                                    </div>
                                    <div className="flex-1">
                                        <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                            Upload Your Excel File
                                        </h3>

                                        <form onSubmit={handleSubmit} className="space-y-4">
                                            {/* File Upload Area */}
                                            <div
                                                className={`border-2 border-dashed rounded-lg p-8 text-center transition-all duration-200 ${
                                                    dragActive
                                                        ? 'border-blue-400 bg-blue-50 scale-105'
                                                        : fileInfo
                                                        ? 'border-green-300 bg-green-50'
                                                        : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50'
                                                }`}
                                                onDragOver={handleDragOver}
                                                onDragLeave={handleDragLeave}
                                                onDrop={handleDrop}
                                            >
                                                {fileInfo ? (
                                                    <div className="space-y-3">
                                                        <DocumentIcon className="w-12 h-12 text-green-600 mx-auto" />
                                                        <div>
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {fileInfo.name}
                                                            </div>
                                                            <div className="text-xs text-gray-500 mt-1">
                                                                {fileInfo.size} • {fileInfo.type}
                                                            </div>
                                                            <div className="text-xs text-gray-500">
                                                                Modified: {fileInfo.lastModified}
                                                            </div>
                                                        </div>
                                                        <button
                                                            type="button"
                                                            onClick={removeFile}
                                                            className="inline-flex items-center text-sm text-red-600 hover:text-red-800 focus:outline-none"
                                                        >
                                                            <XMarkIcon className="w-4 h-4 mr-1" />
                                                            Remove file
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <div className="space-y-3">
                                                        <CloudArrowUpIcon className={`w-12 h-12 mx-auto ${dragActive ? 'text-blue-500' : 'text-gray-400'}`} />
                                                        <div className="space-y-2">
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {dragActive ? 'Drop your Excel file here' : 'Drop your Excel file here, or click to browse'}
                                                            </div>
                                                            <div className="text-xs text-gray-500">
                                                                Supports .xlsx and .xls files up to 10MB
                                                            </div>
                                                        </div>
                                                        <input
                                                            ref={fileInputRef}
                                                            type="file"
                                                            accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                                                            onChange={(e) => {
                                                                const file = e.target.files[0];
                                                                if (file) handleFileSelect(file);
                                                            }}
                                                            className="hidden"
                                                            id="file-upload"
                                                        />
                                                        <label
                                                            htmlFor="file-upload"
                                                            className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer"
                                                        >
                                                            Choose File
                                                        </label>
                                                    </div>
                                                )}
                                            </div>

                                            {/* Validation Errors */}
                                            {(validationErrors.length > 0 || errors.excel_file) && (
                                                <div className="bg-red-50 border border-red-200 rounded-lg p-3">
                                                    <div className="flex">
                                                        <ExclamationTriangleIcon className="w-5 h-5 text-red-400 mr-2 flex-shrink-0" />
                                                        <div className="text-sm text-red-800">
                                                            {validationErrors.length > 0 ? (
                                                                <ul className="space-y-1">
                                                                    {validationErrors.map((error, index) => (
                                                                        <li key={index}>• {error}</li>
                                                                    ))}
                                                                </ul>
                                                            ) : (
                                                                errors.excel_file
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {/* Import Mode Selection */}
                                            <div className="p-4 bg-gray-50 rounded-lg">
                                                <h4 className="text-sm font-semibold text-gray-900 mb-3">Import Mode</h4>
                                                <div className="space-y-3">
                                                    <div className="flex items-start">
                                                        <input
                                                            type="radio"
                                                            id="mode_regular"
                                                            name="import_mode"
                                                            checked={!data.is_sync}
                                                            onChange={() => setData('is_sync', false)}
                                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 mt-0.5"
                                                        />
                                                        <label htmlFor="mode_regular" className="ml-3 text-sm">
                                                            <span className="font-medium text-gray-900">Regular Import</span>
                                                            <span className="block text-gray-500 mt-1">Add new employees, optionally update existing ones</span>
                                                        </label>
                                                    </div>
                                                    <div className="flex items-start">
                                                        <input
                                                            type="radio"
                                                            id="mode_sync"
                                                            name="import_mode"
                                                            checked={data.is_sync}
                                                            onChange={() => setData('is_sync', true)}
                                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 mt-0.5"
                                                        />
                                                        <label htmlFor="mode_sync" className="ml-3 text-sm">
                                                            <span className="font-medium text-gray-900">Synchronization Mode</span>
                                                            <span className="block text-gray-500 mt-1">Replace/merge data to match exactly what's in the file</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Import Options - Only show for regular import */}
                                            {!data.is_sync && (
                                                <div className="space-y-3 p-4 bg-blue-50 rounded-lg">
                                                    <h4 className="text-sm font-semibold text-gray-900 mb-2">Import Options</h4>
                                                    <div className="space-y-2">
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
                                                </div>
                                            )}

                                            {/* Submit Button */}
                                            <button
                                                type="submit"
                                                disabled={processing || !data.excel_file || validationErrors.length > 0}
                                                className="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                {processing ? (
                                                    <>
                                                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                                        {data.is_sync ? 'Synchronizing' : 'Importing'}...
                                                        {progress && ` ${progress.percentage}%`}
                                                    </>
                                                ) : (
                                                    <>
                                                        <DocumentArrowUpIcon className="w-4 h-4 mr-2" />
                                                        {data.is_sync ? 'Synchronize Data' : 'Import Employees'}
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
                                    <ExclamationTriangleIcon className="w-5 h-5 text-yellow-400 mr-3 mt-0.5 flex-shrink-0" />
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
                                                <th className="px-3 py-2 text-left font-medium text-gray-700 border-b">Column</th>
                                                <th className="px-3 py-2 text-left font-medium text-gray-700 border-b">Required</th>
                                                <th className="px-3 py-2 text-left font-medium text-gray-700 border-b">Example</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200">
                                            <tr>
                                                <td className="px-3 py-2 font-mono text-xs">employee_id</td>
                                                <td className="px-3 py-2 text-red-600 font-semibold">Yes</td>
                                                <td className="px-3 py-2">EMP001</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono text-xs">name</td>
                                                <td className="px-3 py-2 text-red-600 font-semibold">Yes</td>
                                                <td className="px-3 py-2">John Doe</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono text-xs">email</td>
                                                <td className="px-3 py-2 text-gray-500">No</td>
                                                <td className="px-3 py-2">john@company.com</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono text-xs">phone</td>
                                                <td className="px-3 py-2 text-gray-500">No</td>
                                                <td className="px-3 py-2">081234567890</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono text-xs">department</td>
                                                <td className="px-3 py-2 text-gray-500">No</td>
                                                <td className="px-3 py-2">IT</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono text-xs">position</td>
                                                <td className="px-3 py-2 text-gray-500">No</td>
                                                <td className="px-3 py-2">Software Engineer</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono text-xs">hire_date</td>
                                                <td className="px-3 py-2 text-gray-500">No</td>
                                                <td className="px-3 py-2">2024-01-15</td>
                                            </tr>
                                            <tr>
                                                <td className="px-3 py-2 font-mono text-xs">status</td>
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
                                    <div className="space-y-3 max-h-64 overflow-y-auto">
                                        {importHistory.map((importRecord, index) => (
                                            <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div className="flex-1 min-w-0">
                                                    <div className="text-sm font-medium text-gray-900 truncate">
                                                        {importRecord.file}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {importRecord.date}
                                                    </div>
                                                </div>
                                                <div className="text-right ml-4">
                                                    <div className="text-sm space-x-2">
                                                        <span className="text-green-600 font-medium">+{importRecord.created}</span>
                                                        {importRecord.updated > 0 && (
                                                            <span className="text-blue-600 font-medium">~{importRecord.updated}</span>
                                                        )}
                                                        {importRecord.errors > 0 && (
                                                            <span className="text-red-600 font-medium">!{importRecord.errors}</span>
                                                        )}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {importRecord.created + importRecord.updated} processed
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
                                    <CheckCircleIcon className="w-5 h-5 text-green-400 mr-3 mt-0.5 flex-shrink-0" />
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

                            {/* Available Departments */}
                            {departments.length > 0 && (
                                <div className="bg-white rounded-lg border border-gray-200 p-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                        Available Departments
                                    </h3>
                                    <div className="grid grid-cols-1 gap-2 max-h-32 overflow-y-auto">
                                        {departments.map((dept, index) => (
                                            <div key={index} className="text-sm text-gray-700 bg-gray-50 px-3 py-1 rounded">
                                                {dept.name} {dept.code && `(${dept.code})`}
                                            </div>
                                        ))}
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
