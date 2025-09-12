import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PropTypes from 'prop-types';
import {
    UserIcon,
    FolderOpenIcon,
    DocumentTextIcon,
    ShieldCheckIcon,
    CalendarDaysIcon,
    BuildingOfficeIcon,
    ChevronRightIcon,
    PlusIcon,
    ArrowLeftIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon
} from '@heroicons/react/24/outline';

// Import components
import BackgroundCheckSection from './components/BackgroundCheckSection';
import CertificateList from './components/CertificateList';
import FilePreviewModal from './components/FilePreviewModal';

// Employee Header Component
function EmployeeHeader({ employee, container, onAddCertificate }) {
    const getStatusBadge = (status, count) => {
        if (count === 0) return null;
        
        const statusConfig = {
            active: { icon: CheckCircleIcon, color: 'green' },
            expired: { icon: XCircleIcon, color: 'red' },
            expiring_soon: { icon: ExclamationTriangleIcon, color: 'yellow' },
            pending: { icon: ClockIcon, color: 'blue' }
        };

        const config = statusConfig[status];
        if (!config) return null;

        const Icon = config.icon;
        const colorClasses = {
            green: 'text-green-700 bg-green-50 border-green-200',
            red: 'text-red-700 bg-red-50 border-red-200',
            yellow: 'text-yellow-700 bg-yellow-50 border-yellow-200',
            blue: 'text-blue-700 bg-blue-50 border-blue-200'
        };

        return (
            <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border ${colorClasses[config.color]}`}>
                <Icon className="w-4 h-4 mr-2" />
                {count} {status.replace('_', ' ')}
            </span>
        );
    };

    const stats = container?.statistics || {};
    
    return (
        <div className="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div className="flex items-start justify-between">
                {/* Employee Info */}
                <div className="flex items-start space-x-4">
                    {/* Avatar */}
                    <div className="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                        {employee.profile_photo_path ? (
                            <img 
                                src={employee.profile_photo_path} 
                                alt={employee.name}
                                className="w-16 h-16 rounded-full object-cover"
                            />
                        ) : (
                            <UserIcon className="w-8 h-8 text-blue-600" />
                        )}
                    </div>

                    {/* Details */}
                    <div className="flex-1">
                        <h1 className="text-2xl font-bold text-gray-900 mb-2">
                            {employee.name}
                        </h1>
                        
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600">
                            <div className="flex items-center">
                                <span className="font-medium mr-2">NIP:</span>
                                <span>{employee.employee_id || 'Not set'}</span>
                            </div>
                            <div className="flex items-center">
                                <BuildingOfficeIcon className="w-4 h-4 mr-2" />
                                <span>{employee.department?.name || 'No Department'}</span>
                            </div>
                            <div className="flex items-center">
                                <span className="font-medium mr-2">Position:</span>
                                <span>{employee.position || 'Not set'}</span>
                            </div>
                            <div className="flex items-center">
                                <CalendarDaysIcon className="w-4 h-4 mr-2" />
                                <span>
                                    {employee.hire_date ? new Date(employee.hire_date).toLocaleDateString() : 'Not set'}
                                </span>
                            </div>
                        </div>

                        {/* Status Badges */}
                        <div className="flex flex-wrap gap-2 mt-4">
                            {getStatusBadge('active', stats.active)}
                            {getStatusBadge('expiring_soon', stats.expiring_soon)}
                            {getStatusBadge('expired', stats.expired)}
                            {getStatusBadge('pending', stats.pending)}
                        </div>
                    </div>
                </div>

                {/* Action Button */}
                <div className="flex-shrink-0">
                    <button
                        onClick={onAddCertificate}
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Add Certificate
                    </button>
                </div>
            </div>

            {/* Statistics Summary */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-200">
                <div className="text-center">
                    <div className="text-2xl font-semibold text-gray-900">
                        {stats.total || 0}
                    </div>
                    <div className="text-sm text-gray-600">Total Certificates</div>
                </div>
                <div className="text-center">
                    <div className="text-2xl font-semibold text-green-600">
                        {stats.active || 0}
                    </div>
                    <div className="text-sm text-gray-600">Active</div>
                </div>
                <div className="text-center">
                    <div className="text-2xl font-semibold text-yellow-600">
                        {stats.expiring_soon || 0}
                    </div>
                    <div className="text-sm text-gray-600">Expiring Soon</div>
                </div>
                <div className="text-center">
                    <div className="text-2xl font-semibold text-red-600">
                        {stats.expired || 0}
                    </div>
                    <div className="text-sm text-gray-600">Expired</div>
                </div>
            </div>
        </div>
    );
}

EmployeeHeader.propTypes = {
    employee: PropTypes.object.isRequired,
    container: PropTypes.object,
    onAddCertificate: PropTypes.func.isRequired
};

// Add Certificate Modal
function AddCertificateModal({ isOpen, onClose, employee, certificateTypes, onSubmit }) {
    const [loading, setLoading] = useState(false);
    const [formData, setFormData] = useState({
        certificate_type_id: '',
        certificate_number: '',
        issuer: '',
        training_provider: '',
        issue_date: '',
        expiry_date: '',
        completion_date: '',
        training_date: '',
        training_hours: '',
        cost: '',
        score: '',
        location: '',
        instructor_name: '',
        notes: '',
        files: []
    });

    if (!isOpen) return null;

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);

        const form = new FormData();
        Object.keys(formData).forEach(key => {
            if (key === 'files') {
                formData.files.forEach(file => form.append('files[]', file));
            } else if (formData[key]) {
                form.append(key, formData[key]);
            }
        });

        try {
            await router.post(route('employee-containers.certificates.store', employee.id), form, {
                forceFormData: true,
                onSuccess: () => {
                    onClose();
                    setFormData({
                        certificate_type_id: '',
                        certificate_number: '',
                        issuer: '',
                        training_provider: '',
                        issue_date: '',
                        expiry_date: '',
                        completion_date: '',
                        training_date: '',
                        training_hours: '',
                        cost: '',
                        score: '',
                        location: '',
                        instructor_name: '',
                        notes: '',
                        files: []
                    });
                }
            });
        } catch (error) {
            console.error('Error adding certificate:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div className="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-medium text-gray-900">
                        Add New Certificate
                    </h3>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <XCircleIcon className="w-6 h-6" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {/* Certificate Type */}
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Certificate Type *
                            </label>
                            <select
                                value={formData.certificate_type_id}
                                onChange={(e) => setFormData({...formData, certificate_type_id: e.target.value})}
                                required
                                className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Select Certificate Type</option>
                                {certificateTypes.map(type => (
                                    <option key={type.id} value={type.id}>
                                        {type.name} ({type.code})
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Certificate Number */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Certificate Number
                            </label>
                            <input
                                type="text"
                                value={formData.certificate_number}
                                onChange={(e) => setFormData({...formData, certificate_number: e.target.value})}
                                className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        {/* Issuer */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Issuer
                            </label>
                            <input
                                type="text"
                                value={formData.issuer}
                                onChange={(e) => setFormData({...formData, issuer: e.target.value})}
                                className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        {/* Issue Date */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Issue Date *
                            </label>
                            <input
                                type="date"
                                value={formData.issue_date}
                                onChange={(e) => setFormData({...formData, issue_date: e.target.value})}
                                required
                                className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        {/* Expiry Date */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Expiry Date
                            </label>
                            <input
                                type="date"
                                value={formData.expiry_date}
                                onChange={(e) => setFormData({...formData, expiry_date: e.target.value})}
                                className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    {/* Notes */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Notes
                        </label>
                        <textarea
                            rows={3}
                            value={formData.notes}
                            onChange={(e) => setFormData({...formData, notes: e.target.value})}
                            className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    {/* File Upload */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Certificate Files (Max 3 files, 5MB each)
                        </label>
                        <input
                            type="file"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png"
                            onChange={(e) => setFormData({...formData, files: Array.from(e.target.files)})}
                            className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p className="mt-1 text-xs text-gray-500">
                            Accepted formats: PDF, JPG, PNG. Maximum 5MB per file.
                        </p>
                    </div>

                    {/* Form Actions */}
                    <div className="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={loading}
                            className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {loading ? 'Adding...' : 'Add Certificate'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

AddCertificateModal.propTypes = {
    isOpen: PropTypes.bool.isRequired,
    onClose: PropTypes.func.isRequired,
    employee: PropTypes.object.isRequired,
    certificateTypes: PropTypes.array.isRequired,
    onSubmit: PropTypes.func.isRequired
};

// Main Component
export default function Show({ auth, employee, container, certificateTypes = [], recentActivity = [], breadcrumbs = [] }) {
    const [previewFile, setPreviewFile] = useState(null);
    const [showAddCertificate, setShowAddCertificate] = useState(false);

    const handleFilePreview = (file) => {
        setPreviewFile(file);
    };

    const handleClosePreview = () => {
        setPreviewFile(null);
    };

    const handleAddCertificate = () => {
        setShowAddCertificate(true);
    };

    const handleCloseAddCertificate = () => {
        setShowAddCertificate(false);
    };

    const handleCertificateSubmit = (data) => {
        // This will be handled by the modal component
        console.log('Certificate submitted:', data);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    {/* Breadcrumb */}
                    <div className="flex items-center space-x-2 text-sm">
                        <Link 
                            href={route('employee-containers.index')}
                            className="flex items-center text-gray-500 hover:text-gray-700"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-1" />
                            Containers
                        </Link>
                        <ChevronRightIcon className="w-4 h-4 text-gray-400" />
                        <span className="text-gray-900 font-medium">
                            {employee.name}
                        </span>
                    </div>

                    {/* Header Actions */}
                    <div className="flex items-center space-x-3">
                        <Link
                            href={route('employee-containers.export', employee.id)}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Export Container
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`${employee.name} - Container`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Employee Header */}
                    <EmployeeHeader 
                        employee={employee} 
                        container={container}
                        onAddCertificate={handleAddCertificate}
                    />

                    {/* Main Content Grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        {/* Left Column - Background Check */}
                        <div className="lg:col-span-1">
                            <BackgroundCheckSection 
                                employee={employee}
                                backgroundCheck={container?.background_check}
                                onFilePreview={handleFilePreview}
                            />
                        </div>

                        {/* Right Column - Certificates */}
                        <div className="lg:col-span-2">
                            <CertificateList 
                                employee={employee}
                                certificates={container?.certificates}
                                onFilePreview={handleFilePreview}
                                onAddCertificate={handleAddCertificate}
                            />
                        </div>
                    </div>

                    {/* Recent Activity */}
                    {recentActivity && recentActivity.length > 0 && (
                        <div className="mt-8">
                            <div className="bg-white rounded-lg border border-gray-200 p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">
                                    Recent Activity
                                </h3>
                                <div className="flow-root">
                                    <ul className="-mb-8">
                                        {recentActivity.map((activity, index) => (
                                            <li key={activity.id} className={index !== recentActivity.length - 1 ? 'pb-8' : ''}>
                                                <div className="relative">
                                                    {index !== recentActivity.length - 1 && (
                                                        <span className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" />
                                                    )}
                                                    <div className="relative flex items-start space-x-3">
                                                        <div className="relative">
                                                            <div className="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                                <DocumentTextIcon className="h-4 w-4 text-blue-600" />
                                                            </div>
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <div>
                                                                <p className="text-sm text-gray-900">
                                                                    {activity.description}
                                                                </p>
                                                                <p className="mt-1 text-xs text-gray-500">
                                                                    {activity.date}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* File Preview Modal */}
            {previewFile && (
                <FilePreviewModal 
                    file={previewFile}
                    onClose={handleClosePreview}
                />
            )}

            {/* Add Certificate Modal */}
            <AddCertificateModal
                isOpen={showAddCertificate}
                onClose={handleCloseAddCertificate}
                employee={employee}
                certificateTypes={certificateTypes}
                onSubmit={handleCertificateSubmit}
            />
        </AuthenticatedLayout>
    );
}

// PropTypes
Show.propTypes = {
    auth: PropTypes.shape({
        user: PropTypes.object.isRequired
    }).isRequired,
    employee: PropTypes.object.isRequired,
    container: PropTypes.object,
    certificateTypes: PropTypes.array,
    recentActivity: PropTypes.array,
    breadcrumbs: PropTypes.array
};