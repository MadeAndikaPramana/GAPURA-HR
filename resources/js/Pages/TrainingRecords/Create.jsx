// resources/js/Pages/TrainingRecords/Create.jsx

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import {
    ArrowLeftIcon,
    ClipboardDocumentListIcon,
    UserIcon,
    AcademicCapIcon,
    CalendarDaysIcon,
    DocumentIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    PlusIcon
} from '@heroicons/react/24/outline';

export default function Create({ auth, employees, trainingTypes }) {
    const [selectedEmployee, setSelectedEmployee] = useState(null);
    const [selectedTrainingType, setSelectedTrainingType] = useState(null);

    // PERBAIKAN: Form data sesuai dengan field yang diperlukan controller
    const { data, setData, post, processing, errors } = useForm({
        employee_id: '',
        training_type_id: '',
        certificate_number: '',  // Optional - will auto-generate if empty
        issuer: '',
        issue_date: '',
        notes: '',
        // HAPUS: expiry_date - akan di-calculate otomatis
    });

    // PERBAIKAN: Submit handler dengan error handling
    const submit = (e) => {
        e.preventDefault();

        // Prevent double submission
        if (processing) {
            console.log('Form is already processing...');
            return;
        }

        // Validasi client-side
        if (!data.employee_id || !data.training_type_id || !data.issuer || !data.issue_date) {
            alert('Please fill in all required fields');
            return;
        }

        console.log('Submitting form with data:', data);

        post(route('training-records.store'), {
            onStart: () => {
                console.log('Form submission started');
            },
            onSuccess: (page) => {
                console.log('Form submission successful');
            },
            onError: (errors) => {
                console.log('Form submission errors:', errors);
            },
            onFinish: () => {
                console.log('Form submission finished');
            }
        });
    };

    // Update selected employee when employee_id changes
    useEffect(() => {
        if (data.employee_id) {
            const employee = employees.find(e => e.id == data.employee_id);
            setSelectedEmployee(employee);
        } else {
            setSelectedEmployee(null);
        }
    }, [data.employee_id, employees]);

    // Update selected training type when training_type_id changes
    useEffect(() => {
        if (data.training_type_id) {
            const type = trainingTypes.find(t => t.id == data.training_type_id);
            setSelectedTrainingType(type);
        } else {
            setSelectedTrainingType(null);
        }
    }, [data.training_type_id, trainingTypes]);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center space-x-4">
                    <Link
                        href={route('training-records.index')}
                        className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        <ArrowLeftIcon className="w-4 h-4 mr-2" />
                        Back to Training Records
                    </Link>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Add New Training Record
                        </h2>
                        <p className="text-sm text-gray-600">
                            Create a new training record for an employee
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Add Training Record" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-8">

                        {/* Basic Information */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 flex items-center">
                                    <ClipboardDocumentListIcon className="w-5 h-5 mr-2 text-green-600" />
                                    Basic Training Information
                                </h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Select employee and training type for this record
                                </p>
                            </div>
                            <div className="px-6 py-4 space-y-6">
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    {/* Employee Selection */}
                                    <div>
                                        <label htmlFor="employee_id" className="block text-sm font-medium text-gray-700">
                                            Employee *
                                        </label>
                                        <select
                                            id="employee_id"
                                            value={data.employee_id}
                                            onChange={(e) => setData('employee_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            required
                                        >
                                            <option value="">Select Employee</option>
                                            {employees.map((employee) => (
                                                <option key={employee.id} value={employee.id}>
                                                    {employee.name} ({employee.employee_id})
                                                </option>
                                            ))}
                                        </select>
                                        {errors.employee_id && <div className="mt-2 text-sm text-red-600">{errors.employee_id}</div>}
                                    </div>

                                    {/* Training Type Selection */}
                                    <div>
                                        <label htmlFor="training_type_id" className="block text-sm font-medium text-gray-700">
                                            Training Type *
                                        </label>
                                        <select
                                            id="training_type_id"
                                            value={data.training_type_id}
                                            onChange={(e) => setData('training_type_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            required
                                        >
                                            <option value="">Select Training Type</option>
                                            {trainingTypes.map((type) => (
                                                <option key={type.id} value={type.id}>
                                                    {type.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.training_type_id && <div className="mt-2 text-sm text-red-600">{errors.training_type_id}</div>}
                                    </div>
                                </div>

                                {/* Training Type Info */}
                                {selectedTrainingType && (
                                    <div className="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                        <div className="flex items-start">
                                            <div className="flex-shrink-0">
                                                <AcademicCapIcon className="w-5 h-5 text-blue-600" />
                                            </div>
                                            <div className="ml-3">
                                                <h4 className="text-sm font-medium text-blue-900">
                                                    Training Information
                                                </h4>
                                                <div className="mt-2 text-sm text-blue-700">
                                                    <p><strong>Validity:</strong> {selectedTrainingType.validity_months} months</p>
                                                    <p><strong>Certificate will expire:</strong> {
                                                        data.issue_date ?
                                                        new Date(new Date(data.issue_date).setMonth(new Date(data.issue_date).getMonth() + selectedTrainingType.validity_months)).toLocaleDateString()
                                                        : 'Select issue date first'
                                                    }</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Certificate Details */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 flex items-center">
                                    <DocumentIcon className="w-5 h-5 mr-2 text-green-600" />
                                    Certificate Details
                                </h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Certificate information and issuance details
                                </p>
                            </div>
                            <div className="px-6 py-4 space-y-6">
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    {/* Certificate Number */}
                                    <div>
                                        <label htmlFor="certificate_number" className="block text-sm font-medium text-gray-700">
                                            Certificate Number
                                        </label>
                                        <input
                                            type="text"
                                            id="certificate_number"
                                            value={data.certificate_number}
                                            onChange={(e) => setData('certificate_number', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            placeholder="Leave empty to auto-generate"
                                        />
                                        {errors.certificate_number && <div className="mt-2 text-sm text-red-600">{errors.certificate_number}</div>}
                                        <p className="mt-1 text-xs text-gray-500">
                                            If left empty, certificate number will be auto-generated
                                        </p>
                                    </div>

                                    {/* Issuer */}
                                    <div>
                                        <label htmlFor="issuer" className="block text-sm font-medium text-gray-700">
                                            Issued By *
                                        </label>
                                        <input
                                            type="text"
                                            id="issuer"
                                            value={data.issuer}
                                            onChange={(e) => setData('issuer', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                            placeholder="e.g., GAPURA SAFETY DEPT"
                                            required
                                        />
                                        {errors.issuer && <div className="mt-2 text-sm text-red-600">{errors.issuer}</div>}
                                    </div>
                                </div>

                                {/* Issue Date */}
                                <div>
                                    <label htmlFor="issue_date" className="block text-sm font-medium text-gray-700">
                                        Issue Date *
                                    </label>
                                    <input
                                        type="date"
                                        id="issue_date"
                                        value={data.issue_date}
                                        onChange={(e) => setData('issue_date', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        max={new Date().toISOString().split('T')[0]}
                                        required
                                    />
                                    {errors.issue_date && <div className="mt-2 text-sm text-red-600">{errors.issue_date}</div>}
                                </div>

                                {/* Notes */}
                                <div>
                                    <label htmlFor="notes" className="block text-sm font-medium text-gray-700">
                                        Notes
                                    </label>
                                    <textarea
                                        id="notes"
                                        rows={3}
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        placeholder="Additional notes about this training..."
                                    />
                                    {errors.notes && <div className="mt-2 text-sm text-red-600">{errors.notes}</div>}
                                </div>
                            </div>
                        </div>

                        {/* Form Actions */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-gray-600">
                                        * Required fields
                                    </div>
                                    <div className="flex space-x-3">
                                        <Link
                                            href={route('training-records.index')}
                                            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            Cancel
                                        </Link>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
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
                                                    <CheckCircleIcon className="w-4 h-4 mr-2" />
                                                    Create Training Record
                                                </>
                                            )}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Debug Info in Development */}
                        {process.env.NODE_ENV === 'development' && (
                            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <h4 className="text-sm font-medium text-yellow-800">Debug Information</h4>
                                <pre className="text-xs text-yellow-700 mt-2">
                                    {JSON.stringify(data, null, 2)}
                                </pre>
                            </div>
                        )}

                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
