// resources/js/Pages/TrainingRecords/Create.jsx
// Add New Training Record Form

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import {
    UserIcon,
    AcademicCapIcon,
    DocumentTextIcon,
    CalendarIcon,
    CurrencyDollarIcon,
    MapPinIcon,
    UserGroupIcon,
    ClipboardDocumentListIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ArrowLeftIcon
} from '@heroicons/react/24/outline';

export default function Create({ auth, employees, trainingTypes }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        employee_id: '',
        training_type_id: '',
        certificate_number: '',
        auto_generate_certificate: false,
        issuer: '',
        issue_date: new Date().toISOString().split('T')[0],
        completion_date: '',
        expiry_date: '',
        score: '',
        training_hours: '',
        cost: '',
        location: '',
        instructor_name: '',
        notes: ''
    });

    const [selectedTrainingType, setSelectedTrainingType] = useState(null);
    const [calculatedExpiryDate, setCalculatedExpiryDate] = useState('');
    const [certificatePreview, setCertificatePreview] = useState('');

    // Update training type selection
    useEffect(() => {
        if (data.training_type_id) {
            const trainingType = trainingTypes.find(t => t.id == data.training_type_id);
            setSelectedTrainingType(trainingType);

            // Auto-calculate expiry date if validity_months is available
            if (trainingType?.validity_months && data.issue_date) {
                const issueDate = new Date(data.issue_date);
                issueDate.setMonth(issueDate.getMonth() + trainingType.validity_months);
                const calculatedDate = issueDate.toISOString().split('T')[0];
                setCalculatedExpiryDate(calculatedDate);

                if (!data.expiry_date) {
                    setData('expiry_date', calculatedDate);
                }
            }
        }
    }, [data.training_type_id, data.issue_date]);

    // Generate certificate number preview
    useEffect(() => {
        if (data.auto_generate_certificate && data.employee_id && data.training_type_id) {
            const employee = employees.find(e => e.id == data.employee_id);
            const trainingType = trainingTypes.find(t => t.id == data.training_type_id);

            if (employee && trainingType) {
                // Generate preview format: DEPT-TRAINING-YYYYMM-XXX
                const year = new Date().getFullYear();
                const month = String(new Date().getMonth() + 1).padStart(2, '0');
                const preview = `DEPT-${trainingType.code || 'TRN'}-${year}${month}-XXX`;
                setCertificatePreview(preview);
            }
        } else {
            setCertificatePreview('');
        }
    }, [data.auto_generate_certificate, data.employee_id, data.training_type_id, employees, trainingTypes]);

    const submit = (e) => {
        e.preventDefault();
        post(route('training-records.store'));
    };

    const getSelectedEmployee = () => {
        return employees.find(e => e.id == data.employee_id);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('training-records.index')}
                            className="text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Add Training Record
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Tambahkan sertifikat pelatihan karyawan baru
                            </p>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title="Add Training Record" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="p-6 space-y-8">

                            {/* Basic Information */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <ClipboardDocumentListIcon className="w-5 h-5 mr-2 text-green-600" />
                                    Basic Information
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Employee Selection */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Employee *
                                        </label>
                                        <select
                                            value={data.employee_id}
                                            onChange={e => setData('employee_id', e.target.value)}
                                            className={`block w-full py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                errors.employee_id ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            required
                                        >
                                            <option value="">Select Employee</option>
                                            {employees?.map((employee) => (
                                                <option key={employee.id} value={employee.id}>
                                                    {employee.name} ({employee.employee_id})
                                                </option>
                                            ))}
                                        </select>
                                        {errors.employee_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.employee_id}</p>
                                        )}

                                        {/* Employee Info Preview */}
                                        {getSelectedEmployee() && (
                                            <div className="mt-2 p-3 bg-blue-50 rounded-md border border-blue-200">
                                                <div className="flex items-center text-sm text-blue-700">
                                                    <UserIcon className="w-4 h-4 mr-2" />
                                                    <span className="font-medium">{getSelectedEmployee().name}</span>
                                                    <span className="mx-2">•</span>
                                                    <span>ID: {getSelectedEmployee().employee_id}</span>
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {/* Training Type Selection */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Training Type *
                                        </label>
                                        <select
                                            value={data.training_type_id}
                                            onChange={e => setData('training_type_id', e.target.value)}
                                            className={`block w-full py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                errors.training_type_id ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            required
                                        >
                                            <option value="">Select Training Type</option>
                                            {trainingTypes?.map((type) => (
                                                <option key={type.id} value={type.id}>
                                                    {type.name} ({type.validity_months}m validity)
                                                </option>
                                            ))}
                                        </select>
                                        {errors.training_type_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.training_type_id}</p>
                                        )}

                                        {/* Training Type Info Preview */}
                                        {selectedTrainingType && (
                                            <div className="mt-2 p-3 bg-green-50 rounded-md border border-green-200">
                                                <div className="flex items-center text-sm text-green-700">
                                                    <AcademicCapIcon className="w-4 h-4 mr-2" />
                                                    <span className="font-medium">{selectedTrainingType.name}</span>
                                                    <span className="mx-2">•</span>
                                                    <span>Valid for {selectedTrainingType.validity_months} months</span>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Certificate Information */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <DocumentTextIcon className="w-5 h-5 mr-2 text-blue-600" />
                                    Certificate Information
                                </h3>

                                <div className="space-y-4">
                                    {/* Auto Generate Certificate Number */}
                                    <div className="flex items-center">
                                        <input
                                            type="checkbox"
                                            id="auto_generate_certificate"
                                            checked={data.auto_generate_certificate}
                                            onChange={e => {
                                                setData('auto_generate_certificate', e.target.checked);
                                                if (e.target.checked) {
                                                    setData('certificate_number', '');
                                                }
                                            }}
                                            className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="auto_generate_certificate" className="ml-2 block text-sm text-gray-900">
                                            Auto-generate certificate number
                                        </label>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {/* Certificate Number */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Certificate Number *
                                            </label>
                                            <input
                                                type="text"
                                                value={data.auto_generate_certificate ? certificatePreview : data.certificate_number}
                                                onChange={e => setData('certificate_number', e.target.value)}
                                                disabled={data.auto_generate_certificate}
                                                placeholder={data.auto_generate_certificate ? "Will be auto-generated" : "Enter certificate number"}
                                                className={`block w-full py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                    data.auto_generate_certificate
                                                        ? 'bg-gray-100 text-gray-500'
                                                        : errors.certificate_number
                                                        ? 'border-red-300'
                                                        : 'border-gray-300'
                                                }`}
                                                required={!data.auto_generate_certificate}
                                            />
                                            {errors.certificate_number && (
                                                <p className="mt-1 text-sm text-red-600">{errors.certificate_number}</p>
                                            )}
                                        </div>

                                        {/* Issuer */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Issuer *
                                            </label>
                                            <input
                                                type="text"
                                                value={data.issuer}
                                                onChange={e => setData('issuer', e.target.value)}
                                                placeholder="Training provider or issuing authority"
                                                className={`block w-full py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                    errors.issuer ? 'border-red-300' : 'border-gray-300'
                                                }`}
                                                required
                                            />
                                            {errors.issuer && (
                                                <p className="mt-1 text-sm text-red-600">{errors.issuer}</p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Dates */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <CalendarIcon className="w-5 h-5 mr-2 text-purple-600" />
                                    Important Dates
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    {/* Issue Date */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Issue Date *
                                        </label>
                                        <input
                                            type="date"
                                            value={data.issue_date}
                                            onChange={e => setData('issue_date', e.target.value)}
                                            className={`block w-full py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                errors.issue_date ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            required
                                        />
                                        {errors.issue_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.issue_date}</p>
                                        )}
                                    </div>

                                    {/* Completion Date */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Completion Date
                                        </label>
                                        <input
                                            type="date"
                                            value={data.completion_date}
                                            onChange={e => setData('completion_date', e.target.value)}
                                            className={`block w-full py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                errors.completion_date ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.completion_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.completion_date}</p>
                                        )}
                                    </div>

                                    {/* Expiry Date */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Expiry Date
                                            {calculatedExpiryDate && (
                                                <span className="text-xs text-green-600 ml-1">(Auto-calculated)</span>
                                            )}
                                        </label>
                                        <input
                                            type="date"
                                            value={data.expiry_date}
                                            onChange={e => setData('expiry_date', e.target.value)}
                                            className={`block w-full py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                errors.expiry_date ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.expiry_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.expiry_date}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Additional Information */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                    <UserGroupIcon className="w-5 h-5 mr-2 text-indigo-600" />
                                    Additional Information
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Score */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Score (0-100)
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            value={data.score}
                                            onChange={e => setData('score', e.target.value)}
                                            placeholder="e.g., 85.5"
                                            className={`block w-full py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                errors.score ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.score && (
                                            <p className="mt-1 text-sm text-red-600">{errors.score}</p>
                                        )}
                                    </div>

                                    {/* Training Hours */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Training Hours
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.5"
                                            value={data.training_hours}
                                            onChange={e => setData('training_hours', e.target.value)}
                                            placeholder="e.g., 16"
                                            className={`block w-full py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                errors.training_hours ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.training_hours && (
                                            <p className="mt-1 text-sm text-red-600">{errors.training_hours}</p>
                                        )}
                                    </div>

                                    {/* Cost */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Cost (IDR)
                                        </label>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <CurrencyDollarIcon className="h-4 w-4 text-gray-400" />
                                            </div>
                                            <input
                                                type="number"
                                                min="0"
                                                step="1000"
                                                value={data.cost}
                                                onChange={e => setData('cost', e.target.value)}
                                                placeholder="e.g., 2500000"
                                                className={`block w-full pl-10 py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                    errors.cost ? 'border-red-300' : 'border-gray-300'
                                                }`}
                                            />
                                        </div>
                                        {errors.cost && (
                                            <p className="mt-1 text-sm text-red-600">{errors.cost}</p>
                                        )}
                                    </div>

                                    {/* Location */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Location
                                        </label>
                                        <div className="relative">
                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <MapPinIcon className="h-4 w-4 text-gray-400" />
                                            </div>
                                            <input
                                                type="text"
                                                value={data.location}
                                                onChange={e => setData('location', e.target.value)}
                                                placeholder="Training location"
                                                className={`block w-full pl-10 py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                    errors.location ? 'border-red-300' : 'border-gray-300'
                                                }`}
                                            />
                                        </div>
                                        {errors.location && (
                                            <p className="mt-1 text-sm text-red-600">{errors.location}</p>
                                        )}
                                    </div>

                                    {/* Instructor Name */}
                                    <div className="md:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Instructor Name
                                        </label>
                                        <input
                                            type="text"
                                            value={data.instructor_name}
                                            onChange={e => setData('instructor_name', e.target.value)}
                                            placeholder="Name of the training instructor"
                                            className={`block w-full py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                errors.instructor_name ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.instructor_name && (
                                            <p className="mt-1 text-sm text-red-600">{errors.instructor_name}</p>
                                        )}
                                    </div>

                                    {/* Notes */}
                                    <div className="md:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Notes
                                        </label>
                                        <textarea
                                            rows={3}
                                            value={data.notes}
                                            onChange={e => setData('notes', e.target.value)}
                                            placeholder="Additional notes about the training..."
                                            className={`block w-full py-2 px-3 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 ${
                                                errors.notes ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.notes && (
                                            <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Form Actions */}
                            <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
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
                                            <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
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
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
