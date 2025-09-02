// resources/js/Pages/Employees/Show.jsx
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    UserIcon,
    DocumentTextIcon,
    ShieldCheckIcon,
    ClockIcon,
    PlusIcon,
    DocumentArrowUpIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowLeftIcon,
    PhoneIcon,
    EnvelopeIcon,
    HomeIcon,
    CalendarIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, employee }) {
    const [activeTab, setActiveTab] = useState('profile');

    const tabs = [
        { id: 'profile', name: 'Profile', icon: UserIcon },
        { id: 'certificates', name: 'Certificates', icon: DocumentTextIcon },
        { id: 'background_check', name: 'Background Check', icon: ShieldCheckIcon },
        { id: 'history', name: 'History', icon: ClockIcon }
    ];

    const getStatusColor = (status) => {
        const colors = {
            active: 'green',
            expiring_soon: 'yellow',
            expired: 'red',
            pending: 'gray',
            completed: 'blue'
        };
        return colors[status] || 'gray';
    };

    const getBackgroundCheckColor = (status) => {
        const colors = {
            cleared: 'green',
            in_progress: 'blue',
            pending_review: 'yellow',
            requires_follow_up: 'orange',
            expired: 'red',
            rejected: 'red',
            not_started: 'gray'
        };
        return colors[status] || 'gray';
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'Not specified';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const formatDateTime = (dateString) => {
        if (!dateString) return 'Not specified';
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('employees.index')}
                            className="text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Employee Container: {employee.name}
                        </h2>
                    </div>
                </div>
            }
        >
            <Head title={`${employee.name} - Employee Container`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Container Header - Digital Folder Look */}
                    <div className="bg-white shadow-lg rounded-lg mb-6 overflow-hidden">
                        <div className="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 text-white">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-6">
                                    <div className="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <span className="text-white font-bold text-2xl">
                                            {employee.name.charAt(0)}
                                        </span>
                                    </div>
                                    <div>
                                        <h1 className="text-3xl font-bold">{employee.name}</h1>
                                        <p className="text-blue-100 text-lg">{employee.employee_id}</p>
                                        <p className="text-blue-200">{employee.position}</p>
                                        <p className="text-blue-200">{employee.department?.name}</p>
                                    </div>
                                </div>

                                <div className="flex flex-col sm:flex-row gap-3">
                                    <button className="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg flex items-center space-x-2 backdrop-blur-sm transition-colors">
                                        <PlusIcon className="w-4 h-4" />
                                        <span>Add Certificate</span>
                                    </button>
                                    <button className="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg flex items-center space-x-2 backdrop-blur-sm transition-colors">
                                        <DocumentArrowUpIcon className="w-4 h-4" />
                                        <span>Upload Documents</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Quick Stats Bar */}
                        <div className="bg-gray-50 px-6 py-4 border-b">
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-green-600">
                                        {employee.compliance?.active_certificates || 0}
                                    </div>
                                    <div className="text-sm text-gray-500">Active Certificates</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-yellow-600">
                                        {employee.compliance?.expiring_soon_certificates || 0}
                                    </div>
                                    <div className="text-sm text-gray-500">Expiring Soon</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-red-600">
                                        {employee.compliance?.expired_certificates || 0}
                                    </div>
                                    <div className="text-sm text-gray-500">Expired</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-blue-600">
                                        {employee.compliance?.compliance_rate || 100}%
                                    </div>
                                    <div className="text-sm text-gray-500">Compliance</div>
                                </div>
                            </div>
                        </div>

                        {/* Container Tabs */}
                        <div className="px-6">
                            <nav className="flex space-x-8">
                                {tabs.map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => setActiveTab(tab.id)}
                                        className={`py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2 transition-colors ${
                                            activeTab === tab.id
                                                ? 'border-blue-500 text-blue-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }`}
                                    >
                                        <tab.icon className="w-4 h-4" />
                                        <span>{tab.name}</span>
                                    </button>
                                ))}
                            </nav>
                        </div>
                    </div>

                    {/* Container Content */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        {/* Profile Tab */}
                        {activeTab === 'profile' && (
                            <>
                                {/* Employee Information */}
                                <div className="lg:col-span-2">
                                    <div className="bg-white shadow rounded-lg">
                                        <div className="px-6 py-4 border-b border-gray-200">
                                            <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                                <UserIcon className="w-5 h-5 mr-2" />
                                                Employee Information
                                            </h3>
                                        </div>
                                        <div className="px-6 py-4">
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Employee ID</label>
                                                    <p className="text-sm text-gray-900 mt-1 font-mono bg-gray-50 px-3 py-2 rounded">
                                                        {employee.employee_id}
                                                    </p>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Full Name</label>
                                                    <p className="text-sm text-gray-900 mt-1 bg-gray-50 px-3 py-2 rounded">
                                                        {employee.name}
                                                    </p>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Department</label>
                                                    <p className="text-sm text-gray-900 mt-1 bg-gray-50 px-3 py-2 rounded">
                                                        {employee.department?.name || 'Not assigned'}
                                                    </p>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Position</label>
                                                    <p className="text-sm text-gray-900 mt-1 bg-gray-50 px-3 py-2 rounded">
                                                        {employee.position}
                                                    </p>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Status</label>
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1 ${
                                                        employee.status === 'active'
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-gray-100 text-gray-800'
                                                    }`}>
                                                        {employee.status}
                                                    </span>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Hire Date</label>
                                                    <p className="text-sm text-gray-900 mt-1 bg-gray-50 px-3 py-2 rounded flex items-center">
                                                        <CalendarIcon className="w-4 h-4 mr-2 text-gray-400" />
                                                        {formatDate(employee.hire_date)}
                                                    </p>
                                                </div>
                                            </div>

                                            {employee.notes && (
                                                <div className="mt-6">
                                                    <label className="text-sm font-medium text-gray-700">Notes</label>
                                                    <p className="text-sm text-gray-900 mt-1 bg-gray-50 px-3 py-2 rounded">
                                                        {employee.notes}
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {/* Contact Information */}
                                <div>
                                    <div className="bg-white shadow rounded-lg mb-6">
                                        <div className="px-6 py-4 border-b border-gray-200">
                                            <h3 className="text-lg font-medium text-gray-900">Contact Information</h3>
                                        </div>
                                        <div className="px-6 py-4 space-y-4">
                                            {employee.email && (
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Email</label>
                                                    <p className="text-sm text-gray-900 flex items-center mt-1">
                                                        <EnvelopeIcon className="w-4 h-4 mr-2 text-gray-400" />
                                                        <a href={`mailto:${employee.email}`} className="text-blue-600 hover:text-blue-800">
                                                            {employee.email}
                                                        </a>
                                                    </p>
                                                </div>
                                            )}
                                            {employee.phone && (
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Phone</label>
                                                    <p className="text-sm text-gray-900 flex items-center mt-1">
                                                        <PhoneIcon className="w-4 h-4 mr-2 text-gray-400" />
                                                        <a href={`tel:${employee.phone}`} className="text-blue-600 hover:text-blue-800">
                                                            {employee.phone}
                                                        </a>
                                                    </p>
                                                </div>
                                            )}
                                            {employee.address && (
                                                <div>
                                                    <label className="text-sm font-medium text-gray-700">Address</label>
                                                    <p className="text-sm text-gray-900 flex items-start mt-1">
                                                        <HomeIcon className="w-4 h-4 mr-2 text-gray-400 mt-0.5" />
                                                        <span>{employee.address}</span>
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Emergency Contact */}
                                    {(employee.emergency_contact_name || employee.emergency_contact_phone) && (
                                        <div className="bg-white shadow rounded-lg">
                                            <div className="px-6 py-4 border-b border-gray-200">
                                                <h3 className="text-lg font-medium text-gray-900">Emergency Contact</h3>
                                            </div>
                                            <div className="px-6 py-4 space-y-4">
                                                {employee.emergency_contact_name && (
                                                    <div>
                                                        <label className="text-sm font-medium text-gray-700">Name</label>
                                                        <p className="text-sm text-gray-900">{employee.emergency_contact_name}</p>
                                                    </div>
                                                )}
                                                {employee.emergency_contact_phone && (
                                                    <div>
                                                        <label className="text-sm font-medium text-gray-700">Phone</label>
                                                        <p className="text-sm text-gray-900 flex items-center">
                                                            <PhoneIcon className="w-4 h-4 mr-2 text-gray-400" />
                                                            <a href={`tel:${employee.emergency_contact_phone}`} className="text-blue-600 hover:text-blue-800">
                                                                {employee.emergency_contact_phone}
                                                            </a>
                                                        </p>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </>
                        )}

                        {/* Certificates Tab */}
                        {activeTab === 'certificates' && (
                            <div className="lg:col-span-3">
                                <div className="bg-white shadow rounded-lg">
                                    <div className="px-6 py-4 border-b border-gray-200">
                                        <div className="flex items-center justify-between">
                                            <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                                <DocumentTextIcon className="w-5 h-5 mr-2" />
                                                Employee Certificates
                                            </h3>
                                            <button className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                                                <PlusIcon className="w-4 h-4" />
                                                <span>Add Certificate</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div className="px-6 py-4">
                                        {employee.employee_certificates && employee.employee_certificates.length > 0 ? (
                                            <div className="space-y-4">
                                                {employee.employee_certificates.map((certificate) => (
                                                    <div key={certificate.id} className="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                                        <div className="flex items-center justify-between">
                                                            <div>
                                                                <h4 className="font-medium text-gray-900">
                                                                    {certificate.certificate_type?.name || 'Unknown Certificate'}
                                                                </h4>
                                                                <p className="text-sm text-gray-600">
                                                                    {certificate.certificate_number}
                                                                </p>
                                                                <p className="text-sm text-gray-500">
                                                                    Issued: {formatDate(certificate.issue_date)} |
                                                                    Expires: {formatDate(certificate.expiry_date) || 'No expiry'}
                                                                </p>
                                                            </div>
                                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${getStatusColor(certificate.status)}-100 text-${getStatusColor(certificate.status)}-800`}>
                                                                {certificate.status?.replace('_', ' ')}
                                                            </span>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="text-center py-8">
                                                <DocumentTextIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                <h3 className="mt-2 text-sm font-medium text-gray-900">No certificates</h3>
                                                <p className="mt-1 text-sm text-gray-500">
                                                    This employee doesn't have any certificates yet.
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Background Check Tab */}
                        {activeTab === 'background_check' && (
                            <div className="lg:col-span-3">
                                <div className="bg-white shadow rounded-lg">
                                    <div className="px-6 py-4 border-b border-gray-200">
                                        <div className="flex items-center justify-between">
                                            <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                                <ShieldCheckIcon className="w-5 h-5 mr-2" />
                                                Background Check
                                            </h3>
                                            <button className="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                                                <DocumentArrowUpIcon className="w-4 h-4" />
                                                <span>Upload Documents</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div className="px-6 py-4">
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                            <div>
                                                <label className="text-sm font-medium text-gray-700">Status</label>
                                                <div className="flex items-center mt-1">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${getBackgroundCheckColor(employee.background_check_status)}-100 text-${getBackgroundCheckColor(employee.background_check_status)}-800`}>
                                                        {employee.background_check_status?.replace('_', ' ') || 'Not specified'}
                                                    </span>
                                                </div>
                                            </div>
                                            <div>
                                                <label className="text-sm font-medium text-gray-700">Date</label>
                                                <p className="text-sm text-gray-900 mt-1">
                                                    {formatDate(employee.background_check_date)}
                                                </p>
                                            </div>
                                            <div>
                                                <label className="text-sm font-medium text-gray-700">Files</label>
                                                <p className="text-sm text-gray-900 mt-1">
                                                    {employee.background_check_files?.length || 0} files uploaded
                                                </p>
                                            </div>
                                        </div>

                                        {employee.background_check_notes && (
                                            <div className="mt-6">
                                                <label className="text-sm font-medium text-gray-700">Notes</label>
                                                <p className="text-sm text-gray-900 mt-1 bg-gray-50 p-3 rounded-md">
                                                    {employee.background_check_notes}
                                                </p>
                                            </div>
                                        )}

                                        {employee.background_check_files && employee.background_check_files.length > 0 && (
                                            <div className="mt-6">
                                                <label className="text-sm font-medium text-gray-700">Uploaded Files</label>
                                                <div className="mt-2 space-y-2">
                                                    {employee.background_check_files.map((file, index) => (
                                                        <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                                            <span className="text-sm text-gray-900">{file.original_name}</span>
                                                            <button className="text-blue-500 hover:text-blue-600 text-sm transition-colors">
                                                                Download
                                                            </button>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* History Tab */}
                        {activeTab === 'history' && (
                            <div className="lg:col-span-3">
                                <div className="bg-white shadow rounded-lg">
                                    <div className="px-6 py-4 border-b border-gray-200">
                                        <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                            <ClockIcon className="w-5 h-5 mr-2" />
                                            Activity History
                                        </h3>
                                    </div>
                                    <div className="px-6 py-4">
                                        <div className="text-center py-8">
                                            <ClockIcon className="mx-auto h-12 w-12 text-gray-400" />
                                            <h3 className="mt-2 text-sm font-medium text-gray-900">No activity history</h3>
                                            <p className="mt-1 text-sm text-gray-500">
                                                Activity history will appear here once actions are performed.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Action Buttons */}
                    <div className="mt-6 flex justify-end space-x-3">
                        <Link
                            href={route('employees.edit', employee.id)}
                            className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors"
                        >
                            Edit Employee
                        </Link>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
