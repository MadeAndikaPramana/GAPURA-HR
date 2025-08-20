import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    DocumentIcon,
    InformationCircleIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    UsersIcon,
    TagIcon,
    BuildingOfficeIcon,
    ClipboardDocumentListIcon
} from '@heroicons/react/24/outline';

export default function Templates({ auth }) {
    const [activeTab, setActiveTab] = useState('download');

    const templates = [
        {
            id: 'employees',
            name: 'Employee Import Template',
            description: 'Template for importing employee data with department assignments',
            route: 'system.templates.employees',
            icon: UsersIcon,
            color: 'blue',
            fields: [
                'employee_id', 'name', 'department_id', 'position',
                'status', 'background_check_date', 'background_check_notes'
            ],
            example: {
                employee_id: 'GAP001',
                name: 'John Doe',
                department_id: '1',
                position: 'Safety Officer',
                status: 'active',
                background_check_date: '2024-01-15',
                background_check_notes: 'Cleared'
            },
            validations: [
                'employee_id must be unique',
                'name is required',
                'department_id must exist in departments table',
                'status must be either "active" or "inactive"',
                'background_check_date must be valid date format (YYYY-MM-DD)'
            ]
        },
        {
            id: 'training-records',
            name: 'Training Records Import Template',
            description: 'Template for importing training certificates and records',
            route: 'system.templates.training-records',
            icon: ClipboardDocumentListIcon,
            color: 'green',
            fields: [
                'employee_id', 'training_type', 'certificate_number',
                'issuer', 'issue_date', 'expiry_date', 'notes'
            ],
            example: {
                employee_id: 'GAP001',
                training_type: 'Fire Safety Training',
                certificate_number: 'FIRE-001-2024',
                issuer: 'GAPURA SAFETY DEPT',
                issue_date: '2024-01-15',
                expiry_date: '2025-01-15',
                notes: 'Completed with certification'
            },
            validations: [
                'employee_id must exist in employees table',
                'training_type must exist in training_types table',
                'certificate_number must be unique',
                'issuer is required',
                'issue_date and expiry_date must be valid dates',
                'expiry_date must be after issue_date'
            ]
        },
        {
            id: 'training-types',
            name: 'Training Types Import Template',
            description: 'Template for importing training type definitions',
            route: 'system.templates.training-types',
            icon: TagIcon,
            color: 'purple',
            fields: [
                'name', 'code', 'validity_months', 'category',
                'description', 'is_active'
            ],
            example: {
                name: 'Fire Safety Training',
                code: 'FIRE',
                validity_months: '12',
                category: 'safety',
                description: 'Basic fire safety and emergency procedures',
                is_active: 'true'
            },
            validations: [
                'name must be unique',
                'code must be unique if provided',
                'validity_months must be between 1-120',
                'category must be: safety, operational, security, or technical',
                'is_active must be "true" or "false"'
            ]
        }
    ];

    const importSteps = [
        {
            step: 1,
            title: 'Download Template',
            description: 'Download the appropriate Excel template for your data type',
            icon: ArrowDownTrayIcon,
            color: 'blue'
        },
        {
            step: 2,
            title: 'Fill Data',
            description: 'Fill in your data following the format and validation rules',
            icon: DocumentIcon,
            color: 'yellow'
        },
        {
            step: 3,
            title: 'Validate Data',
            description: 'Check that all required fields are filled and formats are correct',
            icon: CheckCircleIcon,
            color: 'green'
        },
        {
            step: 4,
            title: 'Upload File',
            description: 'Go to the respective management page and upload your Excel file',
            icon: ArrowUpTrayIcon,
            color: 'purple'
        }
    ];

    const getColorClasses = (color) => {
        const colors = {
            blue: 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100',
            green: 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100',
            purple: 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100',
            yellow: 'bg-yellow-50 text-yellow-700 border-yellow-200 hover:bg-yellow-100'
        };
        return colors[color] || colors.blue;
    };

    const getIconColorClasses = (color) => {
        const colors = {
            blue: 'bg-blue-100 text-blue-600',
            green: 'bg-green-100 text-green-600',
            purple: 'bg-purple-100 text-purple-600',
            yellow: 'bg-yellow-100 text-yellow-600'
        };
        return colors[color] || colors.blue;
    };

    const downloadTemplate = (template) => {
        window.location.href = route(template.route);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Import/Export Templates
                    </h2>
                    <p className="text-sm text-gray-600 mt-1">
                        Download templates and learn how to import data into GAPURA Training System
                    </p>
                </div>
            }
        >
            <Head title="Import/Export Templates" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Quick Links */}
                    <div className="mb-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <div className="flex items-center mb-4">
                            <InformationCircleIcon className="w-6 h-6 text-blue-600 mr-2" />
                            <h3 className="text-lg font-medium text-blue-900">Quick Access</h3>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Link
                                href={route('employees.index')}
                                className="flex items-center p-3 bg-white border border-blue-200 rounded-md hover:bg-blue-50 transition-colors"
                            >
                                <UsersIcon className="w-5 h-5 text-blue-600 mr-3" />
                                <span className="text-blue-900 font-medium">Manage Employees</span>
                            </Link>
                            <Link
                                href={route('training-records.index')}
                                className="flex items-center p-3 bg-white border border-blue-200 rounded-md hover:bg-blue-50 transition-colors"
                            >
                                <ClipboardDocumentListIcon className="w-5 h-5 text-blue-600 mr-3" />
                                <span className="text-blue-900 font-medium">Training Records</span>
                            </Link>
                            <Link
                                href={route('training-types.index')}
                                className="flex items-center p-3 bg-white border border-blue-200 rounded-md hover:bg-blue-50 transition-colors"
                            >
                                <TagIcon className="w-5 h-5 text-blue-600 mr-3" />
                                <span className="text-blue-900 font-medium">Training Types</span>
                            </Link>
                        </div>
                    </div>

                    {/* Tabs */}
                    <div className="bg-white shadow rounded-lg">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex">
                                <button
                                    onClick={() => setActiveTab('download')}
                                    className={`${
                                        activeTab === 'download'
                                            ? 'border-green-500 text-green-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm flex items-center justify-center space-x-2`}
                                >
                                    <ArrowDownTrayIcon className="w-5 h-5" />
                                    <span>Download Templates</span>
                                </button>
                                <button
                                    onClick={() => setActiveTab('guide')}
                                    className={`${
                                        activeTab === 'guide'
                                            ? 'border-green-500 text-green-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm flex items-center justify-center space-x-2`}
                                >
                                    <InformationCircleIcon className="w-5 h-5" />
                                    <span>Import Guide</span>
                                </button>
                            </nav>
                        </div>

                        <div className="p-6">
                            {activeTab === 'download' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">
                                            Available Templates
                                        </h3>
                                        <p className="text-sm text-gray-600 mb-6">
                                            Download Excel templates for importing your data. Each template includes proper headers and example data.
                                        </p>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        {templates.map((template) => {
                                            const Icon = template.icon;
                                            return (
                                                <div
                                                    key={template.id}
                                                    className="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow"
                                                >
                                                    <div className="flex items-center mb-4">
                                                        <div className={`p-3 rounded-full ${getIconColorClasses(template.color)}`}>
                                                            <Icon className="w-6 h-6" />
                                                        </div>
                                                        <h4 className="ml-3 text-lg font-medium text-gray-900">
                                                            {template.name}
                                                        </h4>
                                                    </div>

                                                    <p className="text-sm text-gray-600 mb-4">
                                                        {template.description}
                                                    </p>

                                                    <div className="mb-4">
                                                        <h5 className="text-xs font-medium text-gray-700 mb-2">Required Fields:</h5>
                                                        <div className="flex flex-wrap gap-1">
                                                            {template.fields.slice(0, 4).map((field) => (
                                                                <span key={field} className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                                    {field}
                                                                </span>
                                                            ))}
                                                            {template.fields.length > 4 && (
                                                                <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                                    +{template.fields.length - 4} more
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>

                                                    <button
                                                        onClick={() => downloadTemplate(template)}
                                                        className={`w-full inline-flex items-center justify-center px-4 py-2 border rounded-md text-sm font-medium transition-colors ${getColorClasses(template.color)}`}
                                                    >
                                                        <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                                                        Download Template
                                                    </button>
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {/* Template Details */}
                                    <div className="mt-8 space-y-6">
                                        <h3 className="text-lg font-medium text-gray-900">Template Details</h3>

                                        {templates.map((template) => (
                                            <div key={template.id} className="border border-gray-200 rounded-lg">
                                                <div className="p-4 bg-gray-50 border-b border-gray-200">
                                                    <h4 className="font-medium text-gray-900">{template.name}</h4>
                                                </div>
                                                <div className="p-4 space-y-4">
                                                    <div>
                                                        <h5 className="text-sm font-medium text-gray-700 mb-2">Example Data:</h5>
                                                        <div className="bg-gray-50 rounded-md p-3 overflow-x-auto">
                                                            <pre className="text-xs text-gray-600">
                                                                {JSON.stringify(template.example, null, 2)}
                                                            </pre>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h5 className="text-sm font-medium text-gray-700 mb-2">Validation Rules:</h5>
                                                        <ul className="text-xs text-gray-600 space-y-1">
                                                            {template.validations.map((rule, index) => (
                                                                <li key={index} className="flex items-start">
                                                                    <ExclamationTriangleIcon className="w-3 h-3 text-yellow-500 mr-2 mt-0.5 flex-shrink-0" />
                                                                    {rule}
                                                                </li>
                                                            ))}
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {activeTab === 'guide' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900 mb-4">
                                            Step-by-Step Import Guide
                                        </h3>
                                        <p className="text-sm text-gray-600 mb-6">
                                            Follow these steps to successfully import your data into the GAPURA Training System.
                                        </p>
                                    </div>

                                    {/* Steps */}
                                    <div className="space-y-6">
                                        {importSteps.map((step, index) => {
                                            const Icon = step.icon;
                                            return (
                                                <div key={step.step} className="flex items-start">
                                                    <div className={`flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center ${getIconColorClasses(step.color)}`}>
                                                        <Icon className="w-6 h-6" />
                                                    </div>
                                                    <div className="ml-4 flex-1">
                                                        <h4 className="text-lg font-medium text-gray-900">
                                                            Step {step.step}: {step.title}
                                                        </h4>
                                                        <p className="text-sm text-gray-600 mt-1">
                                                            {step.description}
                                                        </p>
                                                    </div>
                                                    {index < importSteps.length - 1 && (
                                                        <div className="absolute left-6 mt-12 w-0.5 h-6 bg-gray-300"></div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {/* Best Practices */}
                                    <div className="mt-8 bg-green-50 border border-green-200 rounded-lg p-6">
                                        <div className="flex items-center mb-4">
                                            <CheckCircleIcon className="w-6 h-6 text-green-600 mr-2" />
                                            <h4 className="text-lg font-medium text-green-900">Best Practices</h4>
                                        </div>
                                        <ul className="text-sm text-green-800 space-y-2">
                                            <li className="flex items-start">
                                                <CheckCircleIcon className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                                                Always backup your existing data before importing
                                            </li>
                                            <li className="flex items-start">
                                                <CheckCircleIcon className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                                                Start with a small test file to verify the format
                                            </li>
                                            <li className="flex items-start">
                                                <CheckCircleIcon className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                                                Use consistent date formats (YYYY-MM-DD)
                                            </li>
                                            <li className="flex items-start">
                                                <CheckCircleIcon className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                                                Avoid special characters in IDs and codes
                                            </li>
                                            <li className="flex items-start">
                                                <CheckCircleIcon className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                                                Double-check required fields are filled
                                            </li>
                                        </ul>
                                    </div>

                                    {/* Common Issues */}
                                    <div className="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                                        <div className="flex items-center mb-4">
                                            <ExclamationTriangleIcon className="w-6 h-6 text-yellow-600 mr-2" />
                                            <h4 className="text-lg font-medium text-yellow-900">Common Issues</h4>
                                        </div>
                                        <ul className="text-sm text-yellow-800 space-y-2">
                                            <li className="flex items-start">
                                                <ExclamationTriangleIcon className="w-4 h-4 text-yellow-600 mr-2 mt-0.5 flex-shrink-0" />
                                                <span><strong>Duplicate IDs:</strong> Employee IDs and certificate numbers must be unique</span>
                                            </li>
                                            <li className="flex items-start">
                                                <ExclamationTriangleIcon className="w-4 h-4 text-yellow-600 mr-2 mt-0.5 flex-shrink-0" />
                                                <span><strong>Invalid Dates:</strong> Use YYYY-MM-DD format for all dates</span>
                                            </li>
                                            <li className="flex items-start">
                                                <ExclamationTriangleIcon className="w-4 h-4 text-yellow-600 mr-2 mt-0.5 flex-shrink-0" />
                                                <span><strong>Missing References:</strong> Department IDs and Training Types must exist before importing</span>
                                            </li>
                                            <li className="flex items-start">
                                                <ExclamationTriangleIcon className="w-4 h-4 text-yellow-600 mr-2 mt-0.5 flex-shrink-0" />
                                                <span><strong>File Format:</strong> Only .xlsx, .xls, and .csv files are supported</span>
                                            </li>
                                        </ul>
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
