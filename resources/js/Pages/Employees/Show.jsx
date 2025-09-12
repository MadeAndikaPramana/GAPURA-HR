// resources/js/Pages/Employees/Show.jsx - Fixed Syntax Error

import { useState, useRef } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeftIcon,
    PencilIcon,
    TrashIcon,
    FolderIcon,
    DocumentCheckIcon,
    AcademicCapIcon,
    PlusIcon,
    CloudArrowUpIcon,
    DocumentIcon,
    CalendarIcon,
    BuildingOfficeIcon,
    UserIcon,
    PhoneIcon,
    EnvelopeIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    XMarkIcon
} from '@heroicons/react/24/outline';
import {
    FolderIcon as FolderIconSolid,
    DocumentCheckIcon as DocumentCheckIconSolid,
    AcademicCapIcon as AcademicCapIconSolid
} from '@heroicons/react/24/solid';

export default function Show({
    auth,
    employee,
    certificates = [],
    certificateTypes = [],
    backgroundCheck = null
}) {
    const [activeTab, setActiveTab] = useState('profile');
    const [showEditModal, setShowEditModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showUploadModal, setShowUploadModal] = useState(false);
    const [uploadType, setUploadType] = useState('certificate'); // 'certificate' or 'background-check'
    const fileInputRef = useRef(null);

    // Edit Employee Form
    const { data: editData, setData: setEditData, put, processing, errors } = useForm({
        name: employee.name || '',
        nip: employee.nip || employee.employee_id || '',
        position: employee.position || '',
        department_id: employee.department_id || '',
        phone: employee.phone || '',
        email: employee.email || '',
        hire_date: employee.hire_date || '',
    });

    // Upload Form
    const { data: uploadData, setData: setUploadData, post, processing: uploading, errors: uploadErrors, reset } = useForm({
        files: [],
        certificate_type_id: '',
        certificate_number: '',
        issue_date: '',
        expiry_date: '',
        notes: ''
    });

    // Handle file upload
    const handleFileUpload = (files) => {
        setUploadData('files', Array.from(files));
    };

    // Handle delete employee
    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this employee?')) {
            router.delete(route('employees.destroy', employee.id));
        }
    };

    // Handle edit submit
    const handleEditSubmit = (e) => {
        e.preventDefault();
        put(route('employees.update', employee.id), {
            onSuccess: () => setShowEditModal(false)
        });
    };

    // Get status badge
    const getStatusBadge = (status) => {
        const statusConfig = {
            active: { icon: CheckCircleIcon, bg: 'bg-green-100', text: 'text-green-800', label: 'Active' },
            inactive: { icon: XCircleIcon, bg: 'bg-red-100', text: 'text-red-800', label: 'Inactive' },
            pending_review: { icon: ExclamationTriangleIcon, bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Pending' },
            expired: { icon: XCircleIcon, bg: 'bg-red-100', text: 'text-red-800', label: 'Expired' },
            cleared: { icon: CheckCircleIcon, bg: 'bg-green-100', text: 'text-green-800', label: 'Cleared' },
        };

        const config = statusConfig[status] || statusConfig.pending_review;
        const IconComponent = config.icon;

        return (
            <div className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${config.bg} ${config.text}`}>
                <IconComponent className="w-4 h-4 mr-2" />
                {config.label}
            </div>
        );
    };

    // Profile Tab Content
    const ProfileTab = () => (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Employee Photo & Basic Info */}
            <div className="lg:col-span-1">
                <div className="bg-white rounded-xl border border-slate-200 p-6 text-center">
                    <div className="w-32 h-32 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span className="text-white font-bold text-4xl">
                            {employee.name.charAt(0).toUpperCase()}
                        </span>
                    </div>
                    <h3 className="text-2xl font-bold text-slate-900 mb-2">{employee.name}</h3>
                    <p className="text-slate-600 mb-4">NIP: {employee.nip || employee.employee_id || 'N/A'}</p>

                    {/* Action Buttons */}
                    <div className="flex space-x-2 justify-center">
                        <button
                            onClick={() => setShowEditModal(true)}
                            className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit
                        </button>
                        <button
                            onClick={() => setShowDeleteModal(true)}
                            className="flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                        >
                            <TrashIcon className="w-4 h-4 mr-2" />
                            Delete
                        </button>
                    </div>
                </div>

                {/* Status Information */}
                <div className="bg-white rounded-xl border border-slate-200 p-6 mt-6">
                    <h4 className="text-lg font-semibold text-slate-900 mb-4">Status</h4>
                    <div className="space-y-3">
                        <div>
                            <div className="text-sm text-slate-500 mb-1">Employee Status</div>
                            {getStatusBadge(employee.status)}
                        </div>
                        <div>
                            <div className="text-sm text-slate-500 mb-1">Background Check</div>
                            {getStatusBadge(employee.background_check_status || 'pending_review')}
                        </div>
                    </div>
                </div>
            </div>

            {/* Detailed Information */}
            <div className="lg:col-span-2 space-y-6">
                {/* Personal Information */}
                <div className="bg-white rounded-xl border border-slate-200 p-6">
                    <h4 className="text-lg font-semibold text-slate-900 mb-4">Personal Information</h4>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="flex items-center">
                            <UserIcon className="w-5 h-5 text-slate-400 mr-3" />
                            <div>
                                <div className="text-sm text-slate-500">Full Name</div>
                                <div className="font-medium text-slate-900">{employee.name}</div>
                            </div>
                        </div>
                        <div className="flex items-center">
                            <DocumentIcon className="w-5 h-5 text-slate-400 mr-3" />
                            <div>
                                <div className="text-sm text-slate-500">Employee ID</div>
                                <div className="font-medium text-slate-900">{employee.nip || employee.employee_id || 'N/A'}</div>
                            </div>
                        </div>
                        <div className="flex items-center">
                            <PhoneIcon className="w-5 h-5 text-slate-400 mr-3" />
                            <div>
                                <div className="text-sm text-slate-500">Phone</div>
                                <div className="font-medium text-slate-900">{employee.phone || 'Not provided'}</div>
                            </div>
                        </div>
                        <div className="flex items-center">
                            <EnvelopeIcon className="w-5 h-5 text-slate-400 mr-3" />
                            <div>
                                <div className="text-sm text-slate-500">Email</div>
                                <div className="font-medium text-slate-900">{employee.email || 'Not provided'}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Work Information */}
                <div className="bg-white rounded-xl border border-slate-200 p-6">
                    <h4 className="text-lg font-semibold text-slate-900 mb-4">Work Information</h4>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="flex items-center">
                            <BuildingOfficeIcon className="w-5 h-5 text-slate-400 mr-3" />
                            <div>
                                <div className="text-sm text-slate-500">Department</div>
                                <div className="font-medium text-slate-900">{employee.department?.name || 'Not assigned'}</div>
                            </div>
                        </div>
                        <div className="flex items-center">
                            <UserIcon className="w-5 h-5 text-slate-400 mr-3" />
                            <div>
                                <div className="text-sm text-slate-500">Position</div>
                                <div className="font-medium text-slate-900">{employee.position || 'Not specified'}</div>
                            </div>
                        </div>
                        <div className="flex items-center">
                            <CalendarIcon className="w-5 h-5 text-slate-400 mr-3" />
                            <div>
                                <div className="text-sm text-slate-500">Hire Date</div>
                                <div className="font-medium text-slate-900">
                                    {employee.hire_date ? new Date(employee.hire_date).toLocaleDateString() : 'Not specified'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`${employee.name} - Employee Details`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8">
                        <div className="flex items-center mb-4">
                            <Link
                                href={route('employees.index')}
                                className="flex items-center text-slate-600 hover:text-slate-900 mr-4"
                            >
                                <ArrowLeftIcon className="w-5 h-5 mr-2" />
                                Back to Employees
                            </Link>
                        </div>
                        <h1 className="text-3xl font-bold text-slate-900">Employee Details</h1>
                    </div>

                    {/* Navigation Tabs */}
                    <div className="border-b border-slate-200 mb-8">
                        <nav className="-mb-px flex space-x-8">
                            <button
                                onClick={() => setActiveTab('profile')}
                                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                    activeTab === 'profile'
                                        ? 'border-green-500 text-green-600'
                                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                }`}
                            >
                                <UserIcon className="w-5 h-5 inline mr-2" />
                                Profile
                            </button>
                            <button
                                onClick={() => setActiveTab('certificates')}
                                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                    activeTab === 'certificates'
                                        ? 'border-green-500 text-green-600'
                                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                }`}
                            >
                                <AcademicCapIcon className="w-5 h-5 inline mr-2" />
                                Certificates ({certificates.length})
                            </button>
                            <button
                                onClick={() => setActiveTab('background-check')}
                                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                    activeTab === 'background-check'
                                        ? 'border-green-500 text-green-600'
                                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                }`}
                            >
                                <DocumentCheckIcon className="w-5 h-5 inline mr-2" />
                                Background Check
                            </button>
                        </nav>
                    </div>

                    {/* Tab Content */}
                    <div className="mb-8">
                        {activeTab === 'profile' && <ProfileTab />}
                        {activeTab === 'certificates' && (
                            <div className="bg-white rounded-xl border border-slate-200 p-6">
                                <div className="flex items-center justify-between mb-6">
                                    <h3 className="text-lg font-semibold text-slate-900">Certificates</h3>
                                    <button
                                        onClick={() => {
                                            setUploadType('certificate');
                                            setShowUploadModal(true);
                                        }}
                                        className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                                    >
                                        <PlusIcon className="w-4 h-4 mr-2" />
                                        Add Certificate
                                    </button>
                                </div>
                                {certificates.length > 0 ? (
                                    <div className="space-y-4">
                                        {certificates.map((cert) => (
                                            <div key={cert.id} className="border border-slate-200 rounded-lg p-4">
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <h4 className="font-medium text-slate-900">{cert.certificate_type?.name}</h4>
                                                        <p className="text-sm text-slate-600">Number: {cert.certificate_number}</p>
                                                        <p className="text-sm text-slate-600">Expires: {cert.expiry_date}</p>
                                                    </div>
                                                    {getStatusBadge(cert.status)}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-12">
                                        <AcademicCapIcon className="w-12 h-12 text-slate-400 mx-auto mb-4" />
                                        <h3 className="text-lg font-medium text-slate-900 mb-2">No Certificates</h3>
                                        <p className="text-slate-600">Add certificates for this employee.</p>
                                    </div>
                                )}
                            </div>
                        )}
                        {activeTab === 'background-check' && (
                            <div className="bg-white rounded-xl border border-slate-200 p-6">
                                <div className="flex items-center justify-between mb-6">
                                    <h3 className="text-lg font-semibold text-slate-900">Background Check</h3>
                                    <button
                                        onClick={() => {
                                            setUploadType('background-check');
                                            setShowUploadModal(true);
                                        }}
                                        className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                                    >
                                        <PlusIcon className="w-4 h-4 mr-2" />
                                        Upload Documents
                                    </button>
                                </div>
                                {backgroundCheck ? (
                                    <div className="space-y-4">
                                        <div className="border border-slate-200 rounded-lg p-4">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <h4 className="font-medium text-slate-900">Background Check Status</h4>
                                                    <p className="text-sm text-slate-600">
                                                        Date: {employee.background_check_date ?
                                                            new Date(employee.background_check_date).toLocaleDateString() :
                                                            'Not specified'
                                                        }
                                                    </p>
                                                </div>
                                                {getStatusBadge(employee.background_check_status || 'pending_review')}
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center py-12">
                                        <DocumentCheckIcon className="w-12 h-12 text-slate-400 mx-auto mb-4" />
                                        <h3 className="text-lg font-medium text-slate-900 mb-2">No Background Check</h3>
                                        <p className="text-slate-600">Upload background check documents for this employee.</p>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Delete Confirmation Modal */}
            {showDeleteModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div className="mt-3 text-center">
                            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                                <TrashIcon className="h-6 w-6 text-red-600" />
                            </div>
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mt-4">Delete Employee</h3>
                            <div className="mt-2 px-7 py-3">
                                <p className="text-sm text-gray-500">
                                    Are you sure you want to delete this employee? This action cannot be undone.
                                </p>
                            </div>
                            <div className="items-center px-4 py-3">
                                <button
                                    onClick={handleDelete}
                                    className="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300"
                                >
                                    Delete
                                </button>
                                <button
                                    onClick={() => setShowDeleteModal(false)}
                                    className="ml-3 px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
