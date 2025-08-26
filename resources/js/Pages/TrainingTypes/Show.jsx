import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft, Edit, Trash2, Users, Clock, DollarSign,
    Award, AlertTriangle, CheckCircle, BookOpen, Building2,
    Calendar, User, FileText
} from 'lucide-react';

const ShowTrainingType = ({
    auth,
    trainingType,
    complianceRate = 0,
    stats = {},
    departmentBreakdown = []
}) => {
    const handleEdit = () => {
        router.get(route('training-types.edit', trainingType.id));
    };

    const handleDelete = () => {
        if (confirm(`Are you sure you want to delete "${trainingType.name}"? This action cannot be undone.`)) {
            router.delete(route('training-types.destroy', trainingType.id), {
                onSuccess: () => {
                    router.get(route('training-types.index'));
                }
            });
        }
    };

    const getRiskBadgeColor = (rate) => {
        if (rate >= 90) return 'bg-green-100 text-green-800';
        if (rate >= 75) return 'bg-yellow-100 text-yellow-800';
        if (rate >= 60) return 'bg-orange-100 text-orange-800';
        return 'bg-red-100 text-red-800';
    };

    const getComplianceIcon = (rate) => {
        if (rate >= 90) return <CheckCircle className="h-5 w-5 text-green-500" />;
        if (rate >= 60) return <AlertTriangle className="h-5 w-5 text-yellow-500" />;
        return <AlertTriangle className="h-5 w-5 text-red-500" />;
    };

    // Mock data if not provided
    const mockStats = {
        total_certificates: 24,
        active_certificates: 8,
        expiring_certificates: 0,
        expired_certificates: 16
    };
    const currentStats = Object.keys(stats).length > 0 ? stats : mockStats;
    const currentComplianceRate = complianceRate || 33.3;

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={trainingType.name} />

            <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <Link
                                href={route('training-types.index')}
                                className="flex items-center text-sm text-gray-500 hover:text-gray-700"
                            >
                                <ArrowLeft className="h-4 w-4 mr-1" />
                                Back to Training Types
                            </Link>
                        </div>
                        <div className="flex items-center space-x-3">
                            <button
                                onClick={handleEdit}
                                className="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                            >
                                <Edit className="h-4 w-4" />
                                <span>Edit</span>
                            </button>
                            <button
                                onClick={handleDelete}
                                className="flex items-center space-x-2 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                            >
                                <Trash2 className="h-4 w-4" />
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                </div>

                {/* Training Type Header */}
                <div className="bg-white shadow rounded-lg mb-6">
                    <div className="px-6 py-6">
                        <div className="flex items-start justify-between">
                            <div className="flex items-start space-x-4">
                                <div className="p-3 bg-blue-100 rounded-lg">
                                    <BookOpen className="h-8 w-8 text-blue-600" />
                                </div>
                                <div>
                                    <h1 className="text-2xl font-bold text-gray-900">
                                        {trainingType.name}
                                    </h1>
                                    <div className="flex items-center space-x-4 mt-2">
                                        <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                            {trainingType.category}
                                        </span>
                                        <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            Code: {trainingType.code}
                                        </span>
                                        {trainingType.is_mandatory && (
                                            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                                Mandatory
                                            </span>
                                        )}
                                        {trainingType.is_active ? (
                                            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                                Inactive
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <div className="flex items-center space-x-2">
                                {getComplianceIcon(currentComplianceRate)}
                                <div className="text-right">
                                    <div className="text-2xl font-bold text-gray-900">
                                        {currentComplianceRate}%
                                    </div>
                                    <div className="text-sm text-gray-500">Compliance Rate</div>
                                </div>
                            </div>
                        </div>

                        {trainingType.description && (
                            <div className="mt-4">
                                <p className="text-gray-600">{trainingType.description}</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div className="bg-white rounded-lg shadow p-6">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <Award className="h-8 w-8 text-blue-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">Total Certificates</p>
                                <p className="text-2xl font-semibold text-gray-900">
                                    {currentStats.total_certificates}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg shadow p-6">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <CheckCircle className="h-8 w-8 text-green-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">Active</p>
                                <p className="text-2xl font-semibold text-green-600">
                                    {currentStats.active_certificates}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg shadow p-6">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <Clock className="h-8 w-8 text-yellow-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">Expiring Soon</p>
                                <p className="text-2xl font-semibold text-yellow-600">
                                    {currentStats.expiring_certificates}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg shadow p-6">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <AlertTriangle className="h-8 w-8 text-red-600" />
                            </div>
                            <div className="ml-4">
                                <p className="text-sm font-medium text-gray-500">Expired</p>
                                <p className="text-2xl font-semibold text-red-600">
                                    {currentStats.expired_certificates}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Details Sections */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Training Details */}
                    <div className="lg:col-span-2">
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">Training Details</h3>
                            </div>
                            <div className="px-6 py-6">
                                <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 flex items-center">
                                            <Clock className="h-4 w-4 mr-2" />
                                            Validity Period
                                        </dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {trainingType.validity_period_months || trainingType.validity_months || 12} months
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 flex items-center">
                                            <AlertTriangle className="h-4 w-4 mr-2" />
                                            Warning Period
                                        </dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {trainingType.warning_period_days || 30} days before expiry
                                        </dd>
                                    </div>

                                    {trainingType.estimated_cost && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 flex items-center">
                                                <DollarSign className="h-4 w-4 mr-2" />
                                                Estimated Cost
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                Rp {Number(trainingType.estimated_cost).toLocaleString()}
                                            </dd>
                                        </div>
                                    )}

                                    {trainingType.estimated_duration_hours && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 flex items-center">
                                                <Clock className="h-4 w-4 mr-2" />
                                                Duration
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {trainingType.estimated_duration_hours} hours
                                            </dd>
                                        </div>
                                    )}

                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 flex items-center">
                                            <User className="h-4 w-4 mr-2" />
                                            Created
                                        </dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {new Date(trainingType.created_at).toLocaleDateString()}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 flex items-center">
                                            <Calendar className="h-4 w-4 mr-2" />
                                            Last Updated
                                        </dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {new Date(trainingType.updated_at).toLocaleDateString()}
                                        </dd>
                                    </div>
                                </dl>

                                {trainingType.requirements && (
                                    <div className="mt-6">
                                        <dt className="text-sm font-medium text-gray-500 mb-2">
                                            Requirements
                                        </dt>
                                        <dd className="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                                            {trainingType.requirements}
                                        </dd>
                                    </div>
                                )}

                                {trainingType.learning_objectives && (
                                    <div className="mt-6">
                                        <dt className="text-sm font-medium text-gray-500 mb-2">
                                            Learning Objectives
                                        </dt>
                                        <dd className="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                                            {trainingType.learning_objectives}
                                        </dd>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Recent Training Records */}
                    <div>
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">Recent Training Records</h3>
                            </div>
                            <div className="px-6 py-6">
                                {trainingType.training_records && trainingType.training_records.length > 0 ? (
                                    <div className="space-y-4">
                                        {trainingType.training_records.slice(0, 5).map((record) => (
                                            <div key={record.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">
                                                        {record.employee?.name}
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        {record.employee?.department?.name}
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <div className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                                        record.status === 'active' ? 'bg-green-100 text-green-800' :
                                                        record.status === 'expiring_soon' ? 'bg-yellow-100 text-yellow-800' :
                                                        record.status === 'expired' ? 'bg-red-100 text-red-800' :
                                                        'bg-gray-100 text-gray-800'
                                                    }`}>
                                                        {record.status}
                                                    </div>
                                                    <p className="text-xs text-gray-500 mt-1">
                                                        {new Date(record.completion_date).toLocaleDateString()}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                        <div className="text-center">
                                            <Link
                                                href={route('training-records.index', { training_type: trainingType.id })}
                                                className="text-sm text-blue-600 hover:text-blue-800"
                                            >
                                                View all training records →
                                            </Link>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center py-6">
                                        <FileText className="mx-auto h-12 w-12 text-gray-400" />
                                        <p className="text-sm text-gray-500 mt-2">
                                            No training records yet
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default ShowTrainingType;
