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

    const { data, setData, post, processing, errors } = useForm({
        employee_id: '',
        training_type_id: '',
        certificate_number: '',
        issuer: '',
        issue_date: '',
        expiry_date: '',
        notes: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('training-records.store'));
    };

    // Auto-calculate expiry date when training type or issue date changes
    useEffect(() => {
        if (data.training_type_id && data.issue_date && selectedTrainingType) {
            const issueDate = new Date(data.issue_date);
            const expiryDate = new Date(issueDate);
            expiryDate.setMonth(expiryDate.getMonth() + selectedTrainingType.validity_months);

            setData('expiry_date', expiryDate.toISOString().split('T')[0]);
        }
    }, [data.training_type_id, data.issue_date, selectedTrainingType]);

    // Auto-generate certificate number
    const generateCertificateNumber = () => {
        if (!selectedTrainingType) {
            alert('Please select a training type first');
            return;
        }

        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');

        const certificateNumber = `${selectedTrainingType.code}-${year}${month}-${random}`;
        setData('certificate_number', certificateNumber);
    };

    const handleEmployeeChange = (employeeId) => {
        setData('employee_id', employeeId);
        const employee = employees.find(emp => emp.id == employeeId);
        setSelectedEmployee(employee);
    };

    const handleTrainingTypeChange = (trainingTypeId) => {
        setData('training_type_id', trainingTypeId);
        const trainingType = trainingTypes.find(type => type.id == trainingTypeId);
        setSelectedTrainingType(trainingType);
    };

    const getSelectedEmployee = () => {
        return employees.find(emp => emp.id == data.employee_id);
    };

    const formatDate = (dateString) => {
        if (!dateString) return '';
        return new Date(dateString).toLocaleDateString('id-ID');
    };

    const getValidityDescription = (months) => {
        if (!months) return '';
        if (months === 12) return '1 year validity';
        if (months < 12) return `${months} months validity`;
        return `${Math.floor(months / 12)} years ${months % 12} months validity`;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
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
                            <p className="text-sm text-gray-600 mt-1">
                                Create a new training record and certificate for an employee
                            </p>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title="Add Training Record" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6">
                        {/* Employee Selection */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <UserIcon className="w-5 h-5 mr-2" />
                                    Employee Information
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Select the employee for this training record
                                </p>
                            </div>
                            <div className="px-6 py-4 space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Select Employee *
                                    </label>
                                    <select
                                        value={data.employee_id}
                                        onChange={(e) => handleEmployeeChange(e.target.value)}
                                        className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                            errors.employee_id ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        required
                                    >
                                        <option value="">Choose an employee...</option>
                                        {employees.map((employee) => (
                                            <option key={employee.id} value={employee.id}>
                                                {employee.name} ({employee.employee_id}) - {employee.department?.name || 'No Department'}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.employee_id && (
                                        <p className="mt-2 text-sm text-red-600">{errors.employee_id}</p>
                                    )}
                                </div>

                                {/* Employee Preview */}
                                {selectedEmployee && (
                                    <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                                        <h4 className="text-sm font-medium text-blue-800 mb-2">Selected Employee</h4>
                                        <div className="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span className="font-medium text-blue-700">Name:</span>
                                                <div className="text-blue-900">{selectedEmployee.name}</div>
                                            </div>
                                            <div>
                                                <span className="font-medium text-blue-700">Employee ID:</span>
                                                <div className="text-blue-900">{selectedEmployee.employee_id}</div>
                                            </div>
                                            <div>
                                                <span className="font-medium text-blue-700">Department:</span>
                                                <div className="text-blue-900">{selectedEmployee.department?.name || 'No Department'}</div>
                                            </div>
                                            <div>
                                                <span className="font-medium text-blue-700">Position:</span>
                                                <div className="text-blue-900">{selectedEmployee.position || 'No Position'}</div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Training Information */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <AcademicCapIcon className="w-5 h-5 mr-2" />
                                    Training Details
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Specify the type of training and related information
                                </p>
                            </div>
                            <div className="px-6 py-4 space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Training Type */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Training Type *
                                        </label>
                                        <select
                                            value={data.training_type_id}
                                            onChange={(e) => handleTrainingTypeChange(e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.training_type_id ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            required
                                        >
                                            <option value="">Choose a training type...</option>
                                            {trainingTypes.map((type) => (
                                                <option key={type.id} value={type.id}>
                                                    {type.name} ({type.code}) - {getValidityDescription(type.validity_months)}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.training_type_id && (
                                            <p className="mt-2 text-sm text-red-600">{errors.training_type_id}</p>
                                        )}
                                    </div>

                                    {/* Issuer */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Training Provider / Issuer *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.issuer}
                                            onChange={(e) => setData('issuer', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.issuer ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            placeholder="e.g., GAPURA Training Dept, External Provider"
                                            required
                                        />
                                        {errors.issuer && (
                                            <p className="mt-2 text-sm text-red-600">{errors.issuer}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Training Type Preview */}
                                {selectedTrainingType && (
                                    <div className="bg-green-50 border border-green-200 rounded-md p-4">
                                        <h4 className="text-sm font-medium text-green-800 mb-2">Selected Training Type</h4>
                                        <div className="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span className="font-medium text-green-700">Training:</span>
                                                <div className="text-green-900">{selectedTrainingType.name}</div>
                                            </div>
                                            <div>
                                                <span className="font-medium text-green-700">Code:</span>
                                                <div className="text-green-900">{selectedTrainingType.code}</div>
                                            </div>
                                            <div>
                                                <span className="font-medium text-green-700">Category:</span>
                                                <div className="text-green-900">{selectedTrainingType.category}</div>
                                            </div>
                                            <div>
                                                <span className="font-medium text-green-700">Validity:</span>
                                                <div className="text-green-900">{getValidityDescription(selectedTrainingType.validity_months)}</div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Certificate Information */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <DocumentIcon className="w-5 h-5 mr-2" />
                                    Certificate Details
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Certificate number, dates, and validity information
                                </p>
                            </div>
                            <div className="px-6 py-4 space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Certificate Number */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Certificate Number *
                                        </label>
                                        <div className="flex space-x-2">
                                            <input
                                                type="text"
                                                value={data.certificate_number}
                                                onChange={(e) => setData('certificate_number', e.target.value)}
                                                className={`flex-1 border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                    errors.certificate_number ? 'border-red-300' : 'border-gray-300'
                                                }`}
                                                placeholder="Enter or generate certificate number"
                                                required
                                            />
                                            <button
                                                type="button"
                                                onClick={generateCertificateNumber}
                                                className="px-3 py-2 text-sm bg-green-100 border border-green-300 rounded-md hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-green-500"
                                            >
                                                Generate
                                            </button>
                                        </div>
                                        {errors.certificate_number && (
                                            <p className="mt-2 text-sm text-red-600">{errors.certificate_number}</p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500">
                                            Unique certificate identifier (auto-generated based on training type)
                                        </p>
                                    </div>

                                    {/* Issue Date */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Issue Date *
                                        </label>
                                        <input
                                            type="date"
                                            value={data.issue_date}
                                            onChange={(e) => setData('issue_date', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.issue_date ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            required
                                        />
                                        {errors.issue_date && (
                                            <p className="mt-2 text-sm text-red-600">{errors.issue_date}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Expiry Date */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Expiry Date *
                                        </label>
                                        <input
                                            type="date"
                                            value={data.expiry_date}
                                            onChange={(e) => setData('expiry_date', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.expiry_date ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            required
                                        />
                                        {errors.expiry_date && (
                                            <p className="mt-2 text-sm text-red-600">{errors.expiry_date}</p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500">
                                            Auto-calculated based on training type validity period
                                        </p>
                                    </div>

                                    {/* Validity Status */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Certificate Status
                                        </label>
                                        <div className="bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                                            {data.expiry_date ? (
                                                <div className="flex items-center">
                                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                                    <span className="text-sm text-gray-700">
                                                        Valid until {formatDate(data.expiry_date)}
                                                    </span>
                                                </div>
                                            ) : (
                                                <span className="text-sm text-gray-500">Set issue date to calculate validity</span>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {/* Notes */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Additional Notes
                                    </label>
                                    <textarea
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        rows={4}
                                        className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                            errors.notes ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        placeholder="Additional notes about this training record..."
                                    />
                                    {errors.notes && (
                                        <p className="mt-2 text-sm text-red-600">{errors.notes}</p>
                                    )}
                                </div>

                                {/* Preview */}
                                {data.employee_id && data.training_type_id && data.issue_date && data.expiry_date && (
                                    <div className="bg-gray-50 border border-gray-200 rounded-md p-4">
                                        <h4 className="text-sm font-medium text-gray-900 mb-3 flex items-center">
                                            <DocumentIcon className="w-4 h-4 mr-2" />
                                            Training Record Preview
                                        </h4>
                                        <div className="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span className="font-medium text-gray-700">Employee:</span>
                                                <div className="text-gray-900">{getSelectedEmployee()?.name}</div>
                                                <div className="text-xs text-gray-500">{getSelectedEmployee()?.employee_id}</div>
                                            </div>
                                            <div>
                                                <span className="font-medium text-gray-700">Training:</span>
                                                <div className="text-gray-900">{selectedTrainingType?.name}</div>
                                                <div className="text-xs text-gray-500">{selectedTrainingType?.code}</div>
                                            </div>
                                            <div>
                                                <span className="font-medium text-gray-700">Validity Period:</span>
                                                <div className="text-gray-900">{formatDate(data.issue_date)} - {formatDate(data.expiry_date)}</div>
                                                <div className="text-xs text-gray-500">{getValidityDescription(selectedTrainingType?.validity_months)}</div>
                                            </div>
                                            <div>
                                                <span className="font-medium text-gray-700">Issuer:</span>
                                                <div className="text-gray-900">{data.issuer}</div>
                                                <div className="text-xs text-gray-500">Certificate: {data.certificate_number}</div>
                                            </div>
                                        </div>
                                    </div>
                                )}
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
                                                    <PlusIcon className="w-4 h-4 mr-2" />
                                                    Create Training Record
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
