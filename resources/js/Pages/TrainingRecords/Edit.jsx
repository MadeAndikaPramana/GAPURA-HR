import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    PlusIcon,
    TrashIcon,
    CalendarIcon,
    UserIcon,
    BuildingOffice2Icon,
    AcademicCapIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon
} from '@heroicons/react/24/outline';

export default function EditEmployee({ auth, employee, trainingTypes, can_edit }) {
    const [trainings, setTrainings] = useState(employee.training_records || []);
    const [newTraining, setNewTraining] = useState({
        training_type_id: '',
        certificate_number: '',
        issuer: '',
        issue_date: '',
        expiry_date: '',
        score: '',
        training_hours: '',
        notes: ''
    });
    const [showAddForm, setShowAddForm] = useState(false);
    const [errors, setErrors] = useState({});

    const { processing } = useForm();

    const handleAddNew = () => {
        if (!can_edit) {
            alert('You do not have permission to edit training records.');
            return;
        }
        setShowAddForm(true);
    };

    const handleCancelAdd = () => {
        setShowAddForm(false);
        setNewTraining({
            training_type_id: '',
            certificate_number: '',
            issuer: '',
            issue_date: '',
            expiry_date: '',
            score: '',
            training_hours: '',
            notes: ''
        });
        setErrors({});
    };

    const handleSaveNew = () => {
        const formData = new FormData();
        formData.append('action', 'create');
        Object.keys(newTraining).forEach(key => {
            if (newTraining[key]) {
                formData.append(key, newTraining[key]);
            }
        });

        router.post(route('training-records.update-employee-training', employee.id), formData, {
            onSuccess: () => {
                setShowAddForm(false);
                setNewTraining({
                    training_type_id: '',
                    certificate_number: '',
                    issuer: '',
                    issue_date: '',
                    expiry_date: '',
                    score: '',
                    training_hours: '',
                    notes: ''
                });
                setErrors({});
            },
            onError: (errors) => {
                setErrors(errors);
            }
        });
    };

    const handleEdit = (recordId) => {
        router.get(route('training-records.edit', recordId));
    };

    const handleDelete = (recordId) => {
        if (!can_edit) {
            alert('You do not have permission to delete training records.');
            return;
        }

        if (confirm('Are you sure you want to delete this training record?')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('record_id', recordId);

            router.post(route('training-records.update-employee-training', employee.id), formData, {
                onSuccess: () => {
                    // Records will be refreshed automatically
                }
            });
        }
    };

    const getStatusBadge = (status) => {
        const variants = {
            compliant: 'bg-green-100 text-green-800 border-green-200',
            expiring_soon: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            expired: 'bg-red-100 text-red-800 border-red-200'
        };

        const icons = {
            compliant: <CheckCircleIcon className="w-3 h-3" />,
            expiring_soon: <ExclamationTriangleIcon className="w-3 h-3" />,
            expired: <XCircleIcon className="w-3 h-3" />
        };

        const labels = {
            compliant: 'Active',
            expiring_soon: 'Expiring Soon',
            expired: 'Expired'
        };

        return (
            <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border ${variants[status] || 'bg-gray-100 text-gray-800 border-gray-200'}`}>
                {icons[status]}
                {labels[status] || 'Unknown'}
            </span>
        );
    };

    // Auto-calculate expiry date when training type or issue date changes
    const handleNewTrainingChange = (field, value) => {
        const updatedTraining = { ...newTraining, [field]: value };

        if ((field === 'training_type_id' || field === 'issue_date') &&
            updatedTraining.training_type_id && updatedTraining.issue_date) {
            const trainingType = trainingTypes.find(t => t.id == updatedTraining.training_type_id);
            if (trainingType && trainingType.validity_months) {
                const issueDate = new Date(updatedTraining.issue_date);
                const expiryDate = new Date(issueDate);
                expiryDate.setMonth(expiryDate.getMonth() + trainingType.validity_months);
                updatedTraining.expiry_date = expiryDate.toISOString().split('T')[0];
            }
        }

        setNewTraining(updatedTraining);
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Manage Training - ${employee.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Header */}
                    <div className="bg-white shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <Link
                                    href={route('training-records.index')}
                                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                                >
                                    <ArrowLeftIcon className="w-4 h-4 mr-1" />
                                    Back to Training Records
                                </Link>

                                {can_edit && (
                                    <button
                                        onClick={handleAddNew}
                                        disabled={showAddForm}
                                        className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                    >
                                        <PlusIcon className="w-4 h-4 mr-2" />
                                        Add Training Record
                                    </button>
                                )}
                            </div>

                            {/* Employee Info */}
                            <div className="bg-gray-50 rounded-lg p-4">
                                <div className="flex items-center space-x-4">
                                    <div className="flex-shrink-0">
                                        <div className="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                                            <UserIcon className="w-8 h-8 text-gray-600" />
                                        </div>
                                    </div>
                                    <div className="flex-1">
                                        <h1 className="text-xl font-bold text-gray-900">{employee.name}</h1>
                                        <div className="flex items-center space-x-6 mt-2 text-sm text-gray-600">
                                            <span className="flex items-center">
                                                <UserIcon className="w-4 h-4 mr-1" />
                                                ID: {employee.employee_id}
                                            </span>
                                            <span className="flex items-center">
                                                <BuildingOffice2Icon className="w-4 h-4 mr-1" />
                                                {employee.department?.name || 'No Department'}
                                            </span>
                                            <span className="flex items-center">
                                                <AcademicCapIcon className="w-4 h-4 mr-1" />
                                                {trainings.length} Training Records
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Add New Training Form */}
                    {showAddForm && (
                        <div className="bg-white shadow-sm sm:rounded-lg mb-6">
                            <div className="p-6 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">Add New Training Record</h3>
                            </div>
                            <div className="p-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Training Type *
                                        </label>
                                        <select
                                            value={newTraining.training_type_id}
                                            onChange={(e) => handleNewTrainingChange('training_type_id', e.target.value)}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        >
                                            <option value="">Select Training Type</option>
                                            {trainingTypes.map(type => (
                                                <option key={type.id} value={type.id}>
                                                    {type.name} ({type.category})
                                                </option>
                                            ))}
                                        </select>
                                        {errors.training_type_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.training_type_id}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Certificate Number
                                        </label>
                                        <input
                                            type="text"
                                            value={newTraining.certificate_number}
                                            onChange={(e) => handleNewTrainingChange('certificate_number', e.target.value)}
                                            placeholder="Auto-generated if empty"
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Issuer *
                                        </label>
                                        <input
                                            type="text"
                                            value={newTraining.issuer}
                                            onChange={(e) => handleNewTrainingChange('issuer', e.target.value)}
                                            placeholder="Training provider"
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        />
                                        {errors.issuer && (
                                            <p className="mt-1 text-sm text-red-600">{errors.issuer}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Issue Date *
                                        </label>
                                        <input
                                            type="date"
                                            value={newTraining.issue_date}
                                            onChange={(e) => handleNewTrainingChange('issue_date', e.target.value)}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        />
                                        {errors.issue_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.issue_date}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Expiry Date *
                                        </label>
                                        <input
                                            type="date"
                                            value={newTraining.expiry_date}
                                            onChange={(e) => handleNewTrainingChange('expiry_date', e.target.value)}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        />
                                        {errors.expiry_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.expiry_date}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Score (0-100)
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            value={newTraining.score}
                                            onChange={(e) => handleNewTrainingChange('score', e.target.value)}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>

                                    <div className="md:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Notes
                                        </label>
                                        <textarea
                                            rows={2}
                                            value={newTraining.notes}
                                            onChange={(e) => handleNewTrainingChange('notes', e.target.value)}
                                            placeholder="Additional notes..."
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                        />
                                    </div>
                                </div>

                                <div className="flex justify-end space-x-3 mt-6">
                                    <button
                                        onClick={handleCancelAdd}
                                        className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        onClick={handleSaveNew}
                                        disabled={processing || !newTraining.training_type_id || !newTraining.issuer}
                                        className="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                                    >
                                        {processing ? 'Saving...' : 'Save Training Record'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Training Records List */}
                    <div className="bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 border-b border-gray-200">
                            <h3 className="text-lg font-medium text-gray-900">Training Records</h3>
                        </div>

                        {trainings.length === 0 ? (
                            <div className="text-center py-12">
                                <AcademicCapIcon className="mx-auto w-12 h-12 text-gray-400" />
                                <p className="mt-2 text-sm text-gray-500">No training records found</p>
                                {can_edit && (
                                    <p className="text-xs text-gray-400">Click "Add Training Record" to create the first record</p>
                                )}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Training Type
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Certificate
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Issuer
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Dates
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {trainings.map((training) => (
                                            <tr key={training.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {training.training_type?.name || 'Unknown Training'}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {training.training_type?.category || 'No Category'}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-mono text-gray-900">
                                                        {training.certificate_number || 'No Certificate'}
                                                    </div>
                                                    {training.score && (
                                                        <div className="text-sm text-gray-500">
                                                            Score: {training.score}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm text-gray-900">
                                                        {training.issuer}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm text-gray-900">
                                                        <div className="flex items-center">
                                                            <CalendarIcon className="w-4 h-4 mr-1 text-gray-400" />
                                                            {formatDate(training.issue_date)}
                                                        </div>
                                                        <div className="text-xs text-gray-500 mt-1">
                                                            Expires: {formatDate(training.expiry_date)}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {getStatusBadge(training.compliance_status)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-center">
                                                    <div className="flex items-center justify-center space-x-2">
                                                        <button
                                                            onClick={() => handleEdit(training.id)}
                                                            className="text-blue-600 hover:text-blue-900"
                                                            title="Edit Training Record"
                                                        >
                                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </button>
                                                        {can_edit && (
                                                            <button
                                                                onClick={() => handleDelete(training.id)}
                                                                className="text-red-600 hover:text-red-900"
                                                                title="Delete Training Record"
                                                            >
                                                                <TrashIcon className="w-4 h-4" />
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
