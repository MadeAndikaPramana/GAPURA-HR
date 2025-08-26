// resources/js/Pages/TrainingTypes/Edit.jsx
import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import TrainingTypeForm from './Form';
import { Head, router } from '@inertiajs/react';

export default function Edit({ auth, trainingType, providers, categoryOptions }) {
    const handleSave = (formData) => {
        router.put(route('training-types.update', trainingType.id), formData, {
            onSuccess: () => {
                // Handle success
            }
        });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Edit ${trainingType.name}`} />
            <TrainingTypeForm
                trainingType={trainingType}
                providers={providers}
                categoryOptions={categoryOptions}
                onSave={handleSave}
                onCancel={() => router.get(route('training-types.index'))}
            />
        </AuthenticatedLayout>
    );
}
