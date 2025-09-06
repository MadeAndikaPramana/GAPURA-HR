// resources/js/Pages/TrainingTypes/Edit.jsx
import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ArrowLeftIcon, DocumentTextIcon } from '@heroicons/react/24/outline';

export default function EditTrainingType({ auth, certificateType, existingCategories }) {
    const { data, setData, put, processing, errors } = useForm({
        name: certificateType.name || '',
        code: certificateType.code || '',
        category: certificateType.category || '',
        validity_months: certificateType.validity_months || '',
        warning_days: certificateType.warning_days || '',
        is_mandatory: certificateType.is_mandatory || false,
        is_recurrent: certificateType.is_recurrent || false,
        description: certificateType.description || '',
        requirements: certificateType.requirements || '',
        learning_objectives: certificateType.learning_objectives || '',
        estimated_cost: certificateType.estimated_cost || '',
        estimated_duration_hours: certificateType.estimated_duration_hours || '',
        is_active: certificateType.is_active !== undefined ? certificateType.is_active : true
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('training-types.update', certificateType.id));
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Edit ${certificateType.name}`} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                    {/* Header */}
                    <div className="bg-white rounded-lg shadow border border-slate-200 p-6">
                        <div className="flex items-center space-x-4">
                            <a
                                href={route('training-types.index')}
                                className="btn-secondary"
                            >
                                <ArrowLeftIcon className="w-4 h-4 mr-2" />
                                Back to Training Types
                            </a>

                            <div>
                                <h1 className="text-3xl font-bold text-slate-900 flex items-center">
                                    <DocumentTextIcon className="w-8 h-8 text-blue-600 mr-3" />
                                    Edit Training Type
                                </h1>
                                <p className="text-slate-600 mt-1">
                                    Update {certificateType.name} certificate type
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Form - Same structure as Create but with pre-filled data */}
                    <div className="bg-white rounded-lg shadow border border-slate-200 p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Copy the same form structure as Create.jsx but with different submit handler */}
                            {/* ... same form fields ... */}

                            {/* Submit Button */}
                            <div className="flex items-center justify-end space-x-4 pt-6 border-t border-slate-200">
                                <a
                                    href={route('training-types.index')}
                                    className="btn-secondary"
                                >
                                    Cancel
                                </a>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="btn-primary"
                                >
                                    {processing ? 'Updating...' : 'Update Training Type'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
