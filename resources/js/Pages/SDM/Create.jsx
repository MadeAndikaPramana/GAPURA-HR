// resources/js/Pages/SDM/Create.jsx
// Create Employee Form - Clean SDM Structure

import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    UserPlusIcon,
    ArrowLeftIcon,
    UserIcon,
    EnvelopeIcon,
    PhoneIcon,
    BuildingOfficeIcon,
    BriefcaseIcon,
    CalendarIcon
} from '@heroicons/react/24/outline';

export default function Create({ auth, departments }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        employee_id: '',
        name: '',
        email: '',
        phone: '',
        department_id: '',
        position: '',
        hire_date: '',
        status: 'active'
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('sdm.store'), {
            onSuccess: () => reset()
        });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Add New Employee - SDM" />

            <div className="py-6">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Header */}
                    <div className="mb-8">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900 flex items-center">
                                    <UserPlusIcon className="w-8 h-8 mr-3 text-blue-600" />
                                    Add New Employee
                                </h1>
                                <p className="mt-2 text-gray-600">
                                    Create a new employee profile with automatic container setup
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

                    {/* Form */}
                    <div className="bg-white rounded-lg border border-gray-200 p-8">
                        <form onSubmit={handleSubmit} className="space-y-6">

                            {/* Basic Information Section */}
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <UserIcon className="w-5 h-5 mr-2 text-blue-600" />
                                    Basic Information
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Employee ID */}
                                    <div>
                                        <label className="form-label">
                                            Employee ID <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={data.employee_id}
                                            onChange={(e) => setData('employee_id', e.target.value.toUpperCase())}
                                            className="form-input"
                                            placeholder="e.g., EMP001"
                                            required
                                        />
                                        {errors.employee_id && (
                                            <div className="form-error">{errors.employee_id}</div>
                                        )}
                                    </div>

                                    {/* Full Name */}
                                    <div>
                                        <label className="form-label">
                                            Full Name <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            className="form-input"
                                            placeholder="e.g., John Doe"
                                            required
                                        />
                                        {errors.name && (
                                            <div className="form-error">{errors.name}</div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Contact Information Section */}
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <EnvelopeIcon className="w-5 h-5 mr-2 text-green-600" />
                                    Contact Information
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Email */}
                                    <div>
                                        <label className="form-label">Email Address</label>
                                        <input
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className="form-input"
                                            placeholder="john.doe@company.com"
                                        />
                                        {errors.email && (
                                            <div className="form-error">{errors.email}</div>
                                        )}
                                    </div>

                                    {/* Phone */}
                                    <div>
                                        <label className="form-label">Phone Number</label>
                                        <input
                                            type="tel"
                                            value={data.phone}
                                            onChange={(e) => setData('phone', e.target.value)}
                                            className="form-input"
                                            placeholder="081234567890"
                                        />
                                        {errors.phone && (
                                            <div className="form-error">{errors.phone}</div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Work Information Section */}
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <BriefcaseIcon className="w-5 h-5 mr-2 text-purple-600" />
                                    Work Information
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Department */}
                                    <div>
                                        <label className="form-label">Department</label>
                                        <select
                                            value={data.department_id}
                                            onChange={(e) => setData('department_id', e.target.value)}
                                            className="form-input"
                                        >
                                            <option value="">Select Department</option>
                                            {departments?.map(dept => (
                                                <option key={dept.id} value={dept.id}>
                                                    {dept.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.department_id && (
                                            <div className="form-error">{errors.department_id}</div>
                                        )}
                                    </div>

                                    {/* Position */}
                                    <div>
                                        <label className="form-label">Position</label>
                                        <input
                                            type="text"
                                            value={data.position}
                                            onChange={(e) => setData('position', e.target.value)}
                                            className="form-input"
                                            placeholder="e.g., Software Engineer"
                                        />
                                        {errors.position && (
                                            <div className="form-error">{errors.position}</div>
                                        )}
                                    </div>

                                    {/* Hire Date */}
                                    <div>
                                        <label className="form-label">Hire Date</label>
                                        <input
                                            type="date"
                                            value={data.hire_date}
                                            onChange={(e) => setData('hire_date', e.target.value)}
                                            className="form-input"
                                        />
                                        {errors.hire_date && (
                                            <div className="form-error">{errors.hire_date}</div>
                                        )}
                                    </div>

                                    {/* Status */}
                                    <div>
                                        <label className="form-label">Status</label>
                                        <select
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className="form-input"
                                        >
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        {errors.status && (
                                            <div className="form-error">{errors.status}</div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Container Information */}
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div className="flex items-start">
                                    <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                        <BuildingOfficeIcon className="w-5 h-5 text-blue-600" />
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-semibold text-blue-900 mb-1">
                                            Digital Container Setup
                                        </h4>
                                        <p className="text-sm text-blue-700">
                                            A digital container (employee folder) will be automatically created for this employee.
                                            This container will store certificates, background checks, and other documents.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Form Actions */}
                            <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                                <a
                                    href={route('sdm.index')}
                                    className="btn-secondary"
                                >
                                    Cancel
                                </a>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {processing ? (
                                        <>
                                            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                            Creating...
                                        </>
                                    ) : (
                                        <>
                                            <UserPlusIcon className="w-4 h-4 mr-2" />
                                            Create Employee
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Additional Help */}
                    <div className="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h4 className="text-sm font-semibold text-gray-900 mb-2">
                            💡 Next Steps After Creating Employee
                        </h4>
                        <ul className="text-sm text-gray-600 space-y-1">
                            <li>• Access the employee's digital container to upload documents</li>
                            <li>• Add training certificates and background check documents</li>
                            <li>• Update employee information as needed</li>
                            <li>• Use bulk import for adding multiple employees at once</li>
                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
