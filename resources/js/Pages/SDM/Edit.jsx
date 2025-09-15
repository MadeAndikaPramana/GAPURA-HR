// resources/js/Pages/SDM/Edit.jsx
// SDM Employee Edit Form

import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon,
    UserIcon,
    CheckIcon
} from '@heroicons/react/24/outline';

export default function Edit({ auth, employee, departments }) {
    const { data, setData, put, processing, errors } = useForm({
        employee_id: employee.employee_id || '',
        name: employee.name || '',
        email: employee.email || '',
        phone: employee.phone || '',
        department_id: employee.department_id || '',
        position: employee.position || '',
        hire_date: employee.hire_date || '',
        status: employee.status || 'active',
        background_check_status: employee.background_check_status || 'not_started',
        background_check_date: employee.background_check_date || '',
        background_check_notes: employee.background_check_notes || ''
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('sdm.update', employee.id), {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Edit Employee - ${employee.name}`} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <Link
                                    href={route('sdm.index')}
                                    className="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 mb-2"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-1" />
                                    Back to Employee List
                                </Link>
                                <h1 className="text-2xl font-bold text-gray-900">Edit Employee</h1>
                                <p className="text-sm text-gray-600">Update employee information</p>
                            </div>
                            <div className="flex items-center space-x-2">
                                <UserIcon className="w-8 h-8 text-gray-400" />
                                <div>
                                    <p className="text-sm font-medium text-gray-900">{employee.name}</p>
                                    <p className="text-xs text-gray-500">{employee.employee_id}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Edit Form */}
                    <div className="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl">
                        <form onSubmit={handleSubmit} className="p-6 space-y-6">

                            {/* Basic Information */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    {/* Employee ID */}
                                    <div>
                                        <label htmlFor="employee_id" className="block text-sm font-medium text-gray-700 mb-2">
                                            Employee ID (NIP) *
                                        </label>
                                        <input
                                            type="text"
                                            id="employee_id"
                                            value={data.employee_id}
                                            onChange={e => setData('employee_id', e.target.value)}
                                            className={`block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 ${
                                                errors.employee_id ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-300'
                                            }`}
                                            placeholder="e.g., MPGA-GSE-001"
                                        />
                                        {errors.employee_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.employee_id}</p>
                                        )}
                                    </div>

                                    {/* Full Name */}
                                    <div>
                                        <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
                                            Full Name *
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            value={data.name}
                                            onChange={e => setData('name', e.target.value)}
                                            className={`block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 ${
                                                errors.name ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-300'
                                            }`}
                                            placeholder="Full Name"
                                        />
                                        {errors.name && (
                                            <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                        )}
                                    </div>

                                    {/* Email */}
                                    <div>
                                        <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                                            Email Address
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            value={data.email}
                                            onChange={e => setData('email', e.target.value)}
                                            className={`block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 ${
                                                errors.email ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-300'
                                            }`}
                                            placeholder="employee@gapura.com"
                                        />
                                        {errors.email && (
                                            <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                        )}
                                    </div>

                                    {/* Phone */}
                                    <div>
                                        <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-2">
                                            Phone Number
                                        </label>
                                        <input
                                            type="tel"
                                            id="phone"
                                            value={data.phone}
                                            onChange={e => setData('phone', e.target.value)}
                                            className={`block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 ${
                                                errors.phone ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-300'
                                            }`}
                                            placeholder="+62 8xx xxxx xxxx"
                                        />
                                        {errors.phone && (
                                            <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Employment Information */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Employment Information</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    {/* Department */}
                                    <div>
                                        <label htmlFor="department_id" className="block text-sm font-medium text-gray-700 mb-2">
                                            Department
                                        </label>
                                        <select
                                            id="department_id"
                                            value={data.department_id}
                                            onChange={e => setData('department_id', e.target.value)}
                                            className={`block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 ${
                                                errors.department_id ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-300'
                                            }`}
                                        >
                                            <option value="">Select Department</option>
                                            {departments?.map(dept => (
                                                <option key={dept.id} value={dept.id}>
                                                    {dept.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.department_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.department_id}</p>
                                        )}
                                    </div>

                                    {/* Position */}
                                    <div>
                                        <label htmlFor="position" className="block text-sm font-medium text-gray-700 mb-2">
                                            Position
                                        </label>
                                        <input
                                            type="text"
                                            id="position"
                                            value={data.position}
                                            onChange={e => setData('position', e.target.value)}
                                            className={`block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 ${
                                                errors.position ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-300'
                                            }`}
                                            placeholder="Job Title"
                                        />
                                        {errors.position && (
                                            <p className="mt-1 text-sm text-red-600">{errors.position}</p>
                                        )}
                                    </div>

                                    {/* Hire Date */}
                                    <div>
                                        <label htmlFor="hire_date" className="block text-sm font-medium text-gray-700 mb-2">
                                            Hire Date
                                        </label>
                                        <input
                                            type="date"
                                            id="hire_date"
                                            value={data.hire_date}
                                            onChange={e => setData('hire_date', e.target.value)}
                                            className={`block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 ${
                                                errors.hire_date ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-300'
                                            }`}
                                        />
                                        {errors.hire_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.hire_date}</p>
                                        )}
                                    </div>

                                    {/* Status */}
                                    <div>
                                        <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-2">
                                            Status *
                                        </label>
                                        <select
                                            id="status"
                                            value={data.status}
                                            onChange={e => setData('status', e.target.value)}
                                            className={`block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 ${
                                                errors.status ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-300'
                                            }`}
                                        >
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        {errors.status && (
                                            <p className="mt-1 text-sm text-red-600">{errors.status}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Background Check Information */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Background Check Information</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">

                                    {/* Background Check Status */}
                                    <div>
                                        <label htmlFor="background_check_status" className="block text-sm font-medium text-gray-700 mb-2">
                                            Background Check Status
                                        </label>
                                        <select
                                            id="background_check_status"
                                            value={data.background_check_status}
                                            onChange={e => setData('background_check_status', e.target.value)}
                                            className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6"
                                        >
                                            <option value="not_started">Not Started</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="expired">Expired</option>
                                        </select>
                                    </div>

                                    {/* Background Check Date */}
                                    <div>
                                        <label htmlFor="background_check_date" className="block text-sm font-medium text-gray-700 mb-2">
                                            Background Check Date
                                        </label>
                                        <input
                                            type="date"
                                            id="background_check_date"
                                            value={data.background_check_date}
                                            onChange={e => setData('background_check_date', e.target.value)}
                                            className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6"
                                        />
                                    </div>

                                    {/* Background Check Notes */}
                                    <div className="md:col-span-2">
                                        <label htmlFor="background_check_notes" className="block text-sm font-medium text-gray-700 mb-2">
                                            Background Check Notes
                                        </label>
                                        <textarea
                                            id="background_check_notes"
                                            rows={3}
                                            value={data.background_check_notes}
                                            onChange={e => setData('background_check_notes', e.target.value)}
                                            className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6"
                                            placeholder="Additional notes about the background check..."
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Form Actions */}
                            <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                                <Link
                                    href={route('sdm.index')}
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                                >
                                    <CheckIcon className="w-4 h-4 mr-2" />
                                    {processing ? 'Updating...' : 'Update Employee'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}