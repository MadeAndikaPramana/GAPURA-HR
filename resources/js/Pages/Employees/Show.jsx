// resources/js/Pages/EmployeeContainers/Show.jsx - Comprehensive Container View

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
        notes: '',
        background_check_status: 'pending_review',
        background_check_notes: '',
    });

    const tabs = [
        {
            id: 'profile',
            name: 'Profile',
            icon: UserIcon,
            iconSolid: UserIcon,
            count: null
        },
        {
            id: 'background-check',
            name: 'Background Check',
            icon: DocumentCheckIcon,
            iconSolid: DocumentCheckIconSolid,
            count: backgroundCheck?.files?.length || 0
        },
        {
            id: 'certificates',
            name: 'Certificates',
            icon: AcademicCapIcon,
            iconSolid: AcademicCapIconSolid,
            count: certificates.length || 0
        },
    ];

    const handleEditSubmit = (e) => {
        e.preventDefault();
        put(route('employees.update', employee.id), {
            onSuccess: () => {
                setShowEditModal(false);
            }
        });
    };

    const handleDelete = () => {
        router.delete(route('employees.destroy', employee.id), {
            onSuccess: () => {
                router.visit(route('employees.index'));
            }
        });
    };

    const handleFileUpload = (e) => {
        e.preventDefault();

        if (uploadType === 'certificate') {
            post(route('employee-containers.certificates.store', employee.id), {
                onSuccess: () => {
                    setShowUploadModal(false);
                    reset();
                }
            });
        } else {
            post(route('employee-containers.background-check.upload', employee.id), {
                onSuccess: () => {
                    setShowUploadModal(false);
                    reset();
                }
            });
        }
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            active: { icon: CheckCircleIcon, bg: 'bg-green-100', text: 'text-green-800', label: 'Active' },
            pending_review: { icon: ExclamationTriangleIcon, bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Pending Review' },
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
                                    {employee.hire_date ? new Date(employee.hire_date).toLocaleDateString() : 'Not provided'}
                                </div>
                            </div>
                        </div>
                        <div className="flex items-center">
                            <CheckCircleIcon className="w-5 h-5 text-slate-400 mr-3" />
                            <div>
                                <div className="text-sm text-slate-500">Status</div>
                                <div className="font-medium text-slate-900">
                                    {getStatusBadge(employee.status || 'active')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );

    // Background Check Tab Content
    const BackgroundCheckTab = () => (
        <div className="space-y-6">
            {/* Header with Upload Button */}
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-xl font-semibold text-slate-900">Background Check</h3>
                    <p className="text-slate-600 mt-1">Manage background check documents and status</p>
                </div>
                <button
                    onClick={() => {
                        setUploadType('background-check');
                        setShowUploadModal(true);
                    }}
                    className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                >
                    <PlusIcon className="w-4 h-4 mr-2" />
                    Upload Documents
                </button>
            </div>

            {/* Background Check Status */}
            <div className="bg-white rounded-xl border border-slate-200 p-6">
                <div className="flex items-center justify-between mb-4">
                    <h4 className="text-lg font-semibold text-slate-900">Status & Information</h4>
                    {backgroundCheck?.status && getStatusBadge(backgroundCheck.status)}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div className="text-sm text-slate-500 mb-1">Last Updated</div>
                        <div className="font-medium text-slate-900">
                            {backgroundCheck?.updated_at ? new Date(backgroundCheck.updated_at).toLocaleDateString() : 'Not updated'}
                        </div>
                    </div>
                    <div>
                        <div className="text-sm text-slate-500 mb-1">Files Count</div>
                        <div className="font-medium text-slate-900">{backgroundCheck?.files?.length || 0} files</div>
                    </div>
                </div>

                {backgroundCheck?.notes && (
                    <div className="mt-4">
                        <div className="text-sm text-slate-500 mb-1">Notes</div>
                        <div className="text-slate-900 bg-slate-50 p-3 rounded-lg">{backgroundCheck.notes}</div>
                    </div>
                )}
            </div>

            {/* Files */}
            <div className="bg-white rounded-xl border border-slate-200 p-6">
                <h4 className="text-lg font-semibold text-slate-900 mb-4">Documents</h4>
                {backgroundCheck?.files?.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {backgroundCheck.files.map((file, index) => (
                            <div key={index} className="border border-slate-200 rounded-lg p-4 hover:bg-slate-50 transition-colors">
                                <div className="flex items-center justify-between mb-2">
                                    <DocumentIcon className="w-8 h-8 text-blue-600" />
                                    <button className="text-slate-400 hover:text-slate-600">
                                        <EyeIcon className="w-4 h-4" />
                                    </button>
                                </div>
                                <div className="text-sm font-medium text-slate-900 truncate">{file.name}</div>
                                <div className="text-xs text-slate-500 mt-1">{file.size || 'Unknown size'}</div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-8">
                        <DocumentCheckIcon className="w-16 h-16 text-slate-300 mx-auto mb-4" />
                        <h5 className="text-lg font-medium text-slate-900 mb-2">No documents uploaded</h5>
                        <p className="text-slate-600 mb-4">Upload background check documents to get started.</p>
                        <button
                            onClick={() => {
                                setUploadType('background-check');
                                setShowUploadModal(true);
                            }}
                            className="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors"
                        >
                            Upload First Document
                        </button>
                    </div>
                )}
            </div>
        </div>
    );

    // Certificates Tab Content
    const CertificatesTab = () => (
        <div className="space-y-6">
            {/* Header with Add Button */}
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-xl font-semibold text-slate-900">Certificates</h3>
                    <p className="text-slate-600 mt-1">Training certificates and qualifications</p>
                </div>
                <button
                    onClick={() => {
                        setUploadType('certificate');
                        setShowUploadModal(true);
                    }}
                    className="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                >
                    <PlusIcon className="w-4 h-4 mr-2" />
                    Add Certificate
                </button>
            </div>

            {/* Certificates Grid */}
            {certificates?.length > 0 ? (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {certificates.map((certificate) => (
                        <div key={certificate.id} className="bg-white rounded-xl border border-slate-200 p-6">
                            <div className="flex items-start justify-between mb-4">
                                <div className="flex items-center">
                                    <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                        <AcademicCapIcon className="w-6 h-6 text-green-600" />
                                    </div>
                                    <div>
                                        <h5 className="text-lg font-semibold text-slate-900">{certificate.type?.name || 'Unknown Certificate'}</h5>
                                        <p className="text-sm text-slate-600">#{certificate.certificate_number}</p>
                                    </div>
                                </div>
                                {getStatusBadge(certificate.status)}
                            </div>

                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <div className="text-slate-500">Issue Date</div>
                                    <div className="font-medium text-slate-900">
                                        {certificate.issue_date ? new Date(certificate.issue_date).toLocaleDateString() : 'N/A'}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-slate-500">Expiry Date</div>
                                    <div className="font-medium text-slate-900">
                                        {certificate.expiry_date ? new Date(certificate.expiry_date).toLocaleDateString() : 'N/A'}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-slate-500">Issuer</div>
                                    <div className="font-medium text-slate-900">{certificate.issuer || 'N/A'}</div>
                                </div>
                                <div>
                                    <div className="text-slate-500">Files</div>
                                    <div className="font-medium text-slate-900">{certificate.files?.length || 0} files</div>
                                </div>
                            </div>

                            {certificate.notes && (
                                <div className="mt-4">
                                    <div className="text-sm text-slate-500">Notes</div>
                                    <div className="text-sm text-slate-900 mt-1">{certificate.notes}</div>
                                </div>
                            )}

                            <div className="flex items-center justify-end mt-4 space-x-2">
                                <button className="text-slate-600 hover:text-slate-800 transition-colors">
                                    <EyeIcon className="w-4 h-4" />
                                </button>
                                <button className="text-blue-600 hover:text-blue-800 transition-colors">
                                    <PencilIcon className="w-4 h-4" />
                                </button>
                                <button className="text-red-600 hover:text-red-800 transition-colors">
                                    <TrashIcon className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="text-center py-12">
                    <AcademicCapIcon className="w-20 h-20 text-slate-300 mx-auto mb-6" />
                    <h5 className="text-xl font-medium text-slate-900 mb-2">No certificates yet</h5>
                    <p className="text-slate-600 mb-6">Add training certificates and qualifications to build the employee's profile.</p>
                    <button
                        onClick={() => {
                            setUploadType('certificate');
                            setShowUploadModal(true);
                        }}
                        className="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition-colors font-medium"
                    >
                        Add First Certificate
                    </button>
                </div>
            )}
        </div>
    );

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`${employee.name} - Container`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8">
                        <div className="flex items-center space-x-4 mb-6">
                            <Link
                                href={route('employees.index')}
                                className="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 transition-colors"
                            >
                                <ArrowLeftIcon className="w-5 h-5 text-slate-600" />
                            </Link>
                            <div className="flex items-center space-x-4">
                                <div className="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center">
                                    <span className="text-white font-bold text-2xl">
                                        {employee.name.charAt(0).toUpperCase()}
                                    </span>
                                </div>
                                <div>
                                    <h1 className="text-3xl font-bold text-slate-900">{employee.name}</h1>
                                    <p className="text-lg text-slate-600">
                                        {employee.position || 'Employee'} • {employee.department?.name || 'No Department'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Tabs Navigation */}
                        <div className="border-b border-slate-200">
                            <nav className="-mb-px flex space-x-8">
                                {tabs.map((tab) => {
                                    const IconComponent = activeTab === tab.id ? tab.iconSolid : tab.icon;
                                    return (
                                        <button
                                            key={tab.id}
                                            onClick={() => setActiveTab(tab.id)}
                                            className={`flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors ${
                                                activeTab === tab.id
                                                    ? 'border-green-500 text-green-600'
                                                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                            }`}
                                        >
                                            <IconComponent className="w-5 h-5 mr-2" />
                                            {tab.name}
                                            {tab.count !== null && (
                                                <span className={`ml-2 px-2 py-0.5 rounded-full text-xs ${
                                                    activeTab === tab.id
                                                        ? 'bg-green-100 text-green-600'
                                                        : 'bg-slate-100 text-slate-600'
                                                }`}>
                                                    {tab.count}
                                                </span>
                                            )}
                                        </button>
                                    );
                                })}
                            </nav>
                        </div>
                    </div>

                    {/* Tab Content */}
                    <div className="mt-8">
                        {activeTab === 'profile' && <ProfileTab />}
                        {activeTab === 'background-check' && <BackgroundCheckTab />}
                        {activeTab === 'certificates' && <CertificatesTab />}
                    </div>
                </div>
            </div>

            {/* Edit Modal */}
            {showEditModal && (
                <div className="fixed inset-0 bg-slate-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                        <div className="mt-3">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-slate-900">Edit Employee</h3>
                                <button
                                    onClick={() => setShowEditModal(false)}
                                    className="text-slate-400 hover:text-slate-600"
                                >
                                    <XMarkIcon className="w-6 h-6" />
                                </button>
                            </div>
                            <form onSubmit={handleEditSubmit} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">Name</label>
                                        <input
                                            type="text"
                                            value={editData.name}
                                            onChange={(e) => setEditData('name', e.target.value)}
                                            className="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">NIP</label>
                                        <input
                                            type="text"
                                            value={editData.nip}
                                            onChange={(e) => setEditData('nip', e.target.value)}
                                            className="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">Position</label>
                                        <input
                                            type="text"
                                            value={editData.position}
                                            onChange={(e) => setEditData('position', e.target.value)}
                                            className="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                                        <input
                                            type="text"
                                            value={editData.phone}
                                            onChange={(e) => setEditData('phone', e.target.value)}
                                            className="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                        <input
                                            type="email"
                                            value={editData.email}
                                            onChange={(e) => setEditData('email', e.target.value)}
                                            className="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">Hire Date</label>
                                        <input
                                            type="date"
                                            value={editData.hire_date}
                                            onChange={(e) => setEditData('hire_date', e.target.value)}
                                            className="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                        />
                                    </div>
                                </div>
                                <div className="flex justify-end space-x-3 mt-6">
                                    <button
                                        type="button"
                                        onClick={() => setShowEditModal(false)}
                                        className="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
                                    >
                                        {processing ? 'Saving...' : 'Save Changes'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* Delete Confirmation Modal */}
            {showDeleteModal && (
                <div className="fixed inset-0 bg-slate-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div className="mt-3 text-center">
                            <Trash
