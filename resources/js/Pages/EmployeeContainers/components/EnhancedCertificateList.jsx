// Pages/EmployeeContainers/components/EnhancedCertificateList.jsx
import { useState } from 'react';
import { router } from '@inertiajs/react';
import PropTypes from 'prop-types';
import {
    AcademicCapIcon,
    CalendarIcon,
    DocumentTextIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    ArrowDownTrayIcon,
    PlusIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    CloudArrowUpIcon,
    DocumentPlusIcon,
    ArrowPathIcon
} from '@heroicons/react/24/outline';
import { 
    ResponsiveFileUpload, 
    FileVersioning, 
    BulkUpload, 
    FilePreview 
} from '../../../components/FileUpload';

// Enhanced Certificate Card Component
function EnhancedCertificateCard({ 
    certificate, 
    certificateType,
    employeeId,
    onEdit, 
    onDelete, 
    onFilePreview, 
    onUploadComplete,
    isLatest = false, 
    showHistory = false,
    showUpload = false 
}) {
    const [expanded, setExpanded] = useState(false);
    const [showFileUpload, setShowFileUpload] = useState(showUpload);
    const [showVersionHistory, setShowVersionHistory] = useState(false);

    const getStatusConfig = (status) => {
        const configs = {
            active: {
                icon: CheckCircleIcon,
                color: 'text-green-700',
                bgColor: 'bg-green-50',
                borderColor: 'border-green-200',
                label: 'Active'
            },
            expired: {
                icon: XCircleIcon,
                color: 'text-red-700',
                bgColor: 'bg-red-50',
                borderColor: 'border-red-200',
                label: 'Expired'
            },
            expiring_soon: {
                icon: ExclamationTriangleIcon,
                color: 'text-yellow-700',
                bgColor: 'bg-yellow-50',
                borderColor: 'border-yellow-200',
                label: 'Expiring Soon'
            },
            pending: {
                icon: ClockIcon,
                color: 'text-blue-700',
                bgColor: 'bg-blue-50',
                borderColor: 'border-blue-200',
                label: 'Pending'
            }
        };
        return configs[status] || configs.pending;
    };

    const statusConfig = getStatusConfig(certificate.certificate_status);
    const StatusIcon = statusConfig.icon;

    const handleFileUploadComplete = (result) => {
        setShowFileUpload(false);
        if (onUploadComplete) {
            onUploadComplete(result);
        }
        // Refresh page data
        router.reload({ only: ['employee'] });
    };

    const handleVersionUpdate = (result) => {
        if (onUploadComplete) {
            onUploadComplete(result);
        }
        // Refresh page data
        router.reload({ only: ['employee'] });
    };

    const handleFileDownload = (fileIndex) => {
        const downloadUrl = route('employee-containers.certificates.download', {
            employee: employeeId,
            certificate: certificate.id,
            fileIndex: fileIndex
        });
        window.open(downloadUrl, '_blank');
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'Not specified';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const getDaysUntilExpiry = () => {
        if (!certificate.expiry_date) return null;
        const today = new Date();
        const expiryDate = new Date(certificate.expiry_date);
        const diffTime = expiryDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    };

    const daysUntilExpiry = getDaysUntilExpiry();

    return (
        <div className={`bg-white rounded-lg border-2 transition-all duration-200 hover:shadow-md ${
            isLatest ? statusConfig.borderColor : 'border-gray-200'
        } ${isLatest ? statusConfig.bgColor : ''}`}>
            <div className="p-4">
                {/* Header */}
                <div className="flex items-start justify-between mb-3">
                    <div className="flex-1">
                        <div className="flex items-center space-x-2 mb-2">
                            <div className={`p-1.5 rounded-full ${statusConfig.bgColor}`}>
                                <StatusIcon className={`w-4 h-4 ${statusConfig.color}`} />
                            </div>
                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${statusConfig.color} ${statusConfig.bgColor} border ${statusConfig.borderColor}`}>
                                {statusConfig.label}
                            </span>
                            {isLatest && (
                                <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200">
                                    Current
                                </span>
                            )}
                            {certificate.version_number && (
                                <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-gray-600 bg-gray-100 border border-gray-200">
                                    v{certificate.version_number}
                                </span>
                            )}
                        </div>
                        
                        {/* Date Information */}
                        <div className="space-y-1 text-sm text-gray-600">
                            <div className="flex items-center space-x-4">
                                <div className="flex items-center">
                                    <CalendarIcon className="w-4 h-4 mr-1" />
                                    <span>Issued: {formatDate(certificate.issue_date)}</span>
                                </div>
                                <div className="flex items-center">
                                    <CalendarIcon className="w-4 h-4 mr-1" />
                                    <span className={`${
                                        daysUntilExpiry !== null && daysUntilExpiry <= 30 ? 'text-red-600 font-medium' : ''
                                    }`}>
                                        Expires: {formatDate(certificate.expiry_date)}
                                        {daysUntilExpiry !== null && (
                                            <span className={`ml-1 ${daysUntilExpiry <= 30 ? 'text-red-600' : 'text-gray-500'}`}>
                                                ({daysUntilExpiry > 0 ? `${daysUntilExpiry} days left` : 'Expired'})
                                            </span>
                                        )}
                                    </span>
                                </div>
                            </div>
                            {certificate.uploaded_at && (
                                <div className="flex items-center text-xs text-gray-500">
                                    <ClockIcon className="w-3 h-3 mr-1" />
                                    <span>Uploaded: {formatDate(certificate.uploaded_at)}</span>
                                    {certificate.uploaded_by && (
                                        <span className="ml-2">by {certificate.uploaded_by}</span>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                    
                    {/* Action Buttons */}
                    <div className="flex items-center space-x-1 ml-4">
                        {isLatest && (
                            <>
                                <button
                                    onClick={() => setShowFileUpload(!showFileUpload)}
                                    className="p-2 text-gray-400 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
                                    title="Upload new file"
                                >
                                    <CloudArrowUpIcon className="w-4 h-4" />
                                </button>
                                
                                <button
                                    onClick={() => setShowVersionHistory(!showVersionHistory)}
                                    className="p-2 text-gray-400 hover:text-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-500 rounded-lg"
                                    title="View version history"
                                >
                                    <ArrowPathIcon className="w-4 h-4" />
                                </button>
                            </>
                        )}
                        
                        <button
                            onClick={() => setExpanded(!expanded)}
                            className="p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
                            title={expanded ? "Collapse details" : "Expand details"}
                        >
                            {expanded ? (
                                <ChevronDownIcon className="w-4 h-4" />
                            ) : (
                                <ChevronRightIcon className="w-4 h-4" />
                            )}
                        </button>
                        
                        <button
                            onClick={() => onEdit(certificate)}
                            className="p-2 text-gray-400 hover:text-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 rounded-lg"
                            title="Edit certificate"
                        >
                            <PencilIcon className="w-4 h-4" />
                        </button>
                        
                        <button
                            onClick={() => onDelete(certificate)}
                            className="p-2 text-gray-400 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 rounded-lg"
                            title="Delete certificate"
                        >
                            <TrashIcon className="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {/* File Upload Section */}
                {showFileUpload && isLatest && (
                    <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div className="flex items-center justify-between mb-3">
                            <h4 className="text-sm font-medium text-gray-900">Upload New Certificate File</h4>
                            <button
                                onClick={() => setShowFileUpload(false)}
                                className="text-gray-400 hover:text-gray-600"
                                title="Close upload"
                            >
                                <XMarkIcon className="w-4 h-4" />
                            </button>
                        </div>
                        
                        <ResponsiveFileUpload
                            uploadUrl={route('employee-containers.certificates.store', employeeId)}
                            onUploadComplete={handleFileUploadComplete}
                            certificateType={certificateType?.code || 'default'}
                            additionalData={{
                                certificate_id: certificate.id,
                                certificate_type_id: certificate.certificate_type_id,
                                issue_date: certificate.issue_date,
                                expiry_date: certificate.expiry_date,
                                replace_existing: true
                            }}
                            maxFiles={1}
                            accept=".pdf,.jpg,.jpeg,.png"
                            enablePreview={true}
                        />
                    </div>
                )}

                {/* Version History Section */}
                {showVersionHistory && isLatest && (
                    <div className="mt-4 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                        <div className="flex items-center justify-between mb-3">
                            <h4 className="text-sm font-medium text-gray-900">Version History</h4>
                            <button
                                onClick={() => setShowVersionHistory(false)}
                                className="text-gray-400 hover:text-gray-600"
                                title="Close version history"
                            >
                                <XMarkIcon className="w-4 h-4" />
                            </button>
                        </div>
                        
                        <FileVersioning
                            employeeId={employeeId}
                            certificateTypeId={certificate.certificate_type_id}
                            certificateType={certificateType}
                            onVersionUpdate={handleVersionUpdate}
                        />
                    </div>
                )}

                {/* Expanded Details */}
                {expanded && (
                    <div className="mt-4 pt-3 border-t border-gray-200">
                        {/* Notes */}
                        {certificate.notes && (
                            <div className="mb-4">
                                <h5 className="text-sm font-medium text-gray-700 mb-1">Notes</h5>
                                <p className="text-sm text-gray-900 whitespace-pre-wrap">{certificate.notes}</p>
                            </div>
                        )}

                        {/* Files */}
                        {certificate.certificate_files && certificate.certificate_files.length > 0 && (
                            <div className="mb-4">
                                <h5 className="text-sm font-medium text-gray-700 mb-2">
                                    Files ({certificate.certificate_files.length})
                                </h5>
                                <div className="space-y-2">
                                    {certificate.certificate_files.map((file, index) => (
                                        <div
                                            key={index}
                                            className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors"
                                        >
                                            <div className="flex items-center space-x-3">
                                                <DocumentTextIcon className="w-5 h-5 text-gray-400" />
                                                <div>
                                                    <span className="text-sm font-medium text-gray-900 truncate block">
                                                        {file.original_name}
                                                    </span>
                                                    {file.file_size && (
                                                        <span className="text-xs text-gray-500">
                                                            {(file.file_size / 1024).toFixed(1)} KB
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <button
                                                    onClick={() => onFilePreview(file)}
                                                    className="p-2 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                                                    title="Preview file"
                                                >
                                                    <EyeIcon className="w-4 h-4 text-gray-600" />
                                                </button>
                                                <button
                                                    onClick={() => handleFileDownload(index)}
                                                    className="p-2 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors"
                                                    title="Download file"
                                                >
                                                    <ArrowDownTrayIcon className="w-4 h-4 text-gray-600" />
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Certificate Details */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            {certificate.certificate_number && (
                                <div>
                                    <span className="font-medium text-gray-700">Certificate Number:</span>
                                    <p className="text-gray-900">{certificate.certificate_number}</p>
                                </div>
                            )}
                            {certificate.issuing_authority && (
                                <div>
                                    <span className="font-medium text-gray-700">Issuing Authority:</span>
                                    <p className="text-gray-900">{certificate.issuing_authority}</p>
                                </div>
                            )}
                            {certificate.training_location && (
                                <div>
                                    <span className="font-medium text-gray-700">Training Location:</span>
                                    <p className="text-gray-900">{certificate.training_location}</p>
                                </div>
                            )}
                            {certificate.training_duration && (
                                <div>
                                    <span className="font-medium text-gray-700">Duration:</span>
                                    <p className="text-gray-900">{certificate.training_duration} hours</p>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

// Enhanced Certificate Type Section Component
function EnhancedCertificateTypeSection({ 
    typeName, 
    typeId,
    certificates, 
    certificateType,
    employeeId,
    onEdit, 
    onDelete, 
    onFilePreview,
    onUploadComplete,
    onAddNew,
    isRecurrent = false 
}) {
    const [showHistory, setShowHistory] = useState(false);
    const [showBulkUpload, setShowBulkUpload] = useState(false);
    
    // Sort certificates by issue date (newest first)
    const sortedCertificates = [...certificates].sort((a, b) => 
        new Date(b.issue_date || 0) - new Date(a.issue_date || 0)
    );

    const latestCertificate = sortedCertificates[0];
    const historicalCertificates = sortedCertificates.slice(1);

    const handleBulkUploadComplete = (result) => {
        setShowBulkUpload(false);
        if (onUploadComplete) {
            onUploadComplete(result);
        }
    };

    return (
        <div className="mb-8">
            {/* Section Header */}
            <div className="flex items-center justify-between mb-4">
                <div className="flex items-center space-x-2">
                    <AcademicCapIcon className="w-5 h-5 text-blue-600" />
                    <h3 className="text-lg font-medium text-gray-900">{typeName}</h3>
                    {isRecurrent && (
                        <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-purple-700 bg-purple-100 border border-purple-200">
                            Recurrent
                        </span>
                    )}
                    <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-gray-600 bg-gray-100 border border-gray-200">
                        {certificates.length} certificate{certificates.length !== 1 ? 's' : ''}
                    </span>
                </div>

                <div className="flex items-center space-x-2">
                    {historicalCertificates.length > 0 && (
                        <button
                            onClick={() => setShowHistory(!showHistory)}
                            className="text-sm text-blue-600 hover:text-blue-800 flex items-center"
                        >
                            {showHistory ? (
                                <>
                                    <ChevronDownIcon className="w-4 h-4 mr-1" />
                                    Hide History
                                </>
                            ) : (
                                <>
                                    <ChevronRightIcon className="w-4 h-4 mr-1" />
                                    Show History ({historicalCertificates.length})
                                </>
                            )}
                        </button>
                    )}
                    
                    <button
                        onClick={() => setShowBulkUpload(!showBulkUpload)}
                        className="text-sm text-green-600 hover:text-green-800 flex items-center"
                    >
                        <CloudArrowUpIcon className="w-4 h-4 mr-1" />
                        Bulk Upload
                    </button>
                    
                    <button
                        onClick={() => onAddNew(typeId)}
                        className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <PlusIcon className="w-4 h-4 mr-1" />
                        Add New
                    </button>
                </div>
            </div>

            {/* Bulk Upload Section */}
            {showBulkUpload && (
                <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div className="flex items-center justify-between mb-3">
                        <h4 className="text-sm font-medium text-gray-900">Bulk Upload - {typeName}</h4>
                        <button
                            onClick={() => setShowBulkUpload(false)}
                            className="text-gray-400 hover:text-gray-600"
                        >
                            <XMarkIcon className="w-4 h-4" />
                        </button>
                    </div>
                    
                    <BulkUpload
                        certificateTypes={[certificateType]}
                        employeeId={employeeId}
                        onUploadComplete={handleBulkUploadComplete}
                        onCancel={() => setShowBulkUpload(false)}
                    />
                </div>
            )}

            {/* Latest Certificate */}
            {latestCertificate && (
                <div className="mb-4">
                    <EnhancedCertificateCard
                        certificate={latestCertificate}
                        certificateType={certificateType}
                        employeeId={employeeId}
                        onEdit={onEdit}
                        onDelete={onDelete}
                        onFilePreview={onFilePreview}
                        onUploadComplete={onUploadComplete}
                        isLatest={true}
                        showHistory={false}
                    />
                </div>
            )}

            {/* Historical Certificates */}
            {showHistory && historicalCertificates.length > 0 && (
                <div className="space-y-3">
                    <h4 className="text-sm font-medium text-gray-700 border-b border-gray-200 pb-2">
                        Previous Versions
                    </h4>
                    {historicalCertificates.map((certificate) => (
                        <EnhancedCertificateCard
                            key={certificate.id}
                            certificate={certificate}
                            certificateType={certificateType}
                            employeeId={employeeId}
                            onEdit={onEdit}
                            onDelete={onDelete}
                            onFilePreview={onFilePreview}
                            onUploadComplete={onUploadComplete}
                            isLatest={false}
                            showHistory={true}
                        />
                    ))}
                </div>
            )}

            {/* Empty State */}
            {certificates.length === 0 && (
                <div className="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <DocumentPlusIcon className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                    <p className="text-sm text-gray-600 mb-3">
                        No {typeName.toLowerCase()} certificates found
                    </p>
                    <button
                        onClick={() => onAddNew(typeId)}
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Add First Certificate
                    </button>
                </div>
            )}
        </div>
    );
}

// Main Enhanced Certificate List Component
export default function EnhancedCertificateList({
    employee,
    certificateTypes,
    onEdit,
    onDelete,
    onAddNew,
    onFilePreview,
    onUploadComplete
}) {
    const [previewFile, setPreviewFile] = useState(null);

    // Group certificates by type
    const groupedCertificates = employee.certificates?.reduce((acc, certificate) => {
        const typeId = certificate.certificate_type_id;
        if (!acc[typeId]) {
            acc[typeId] = [];
        }
        acc[typeId].push(certificate);
        return acc;
    }, {}) || {};

    const handleFilePreview = (file) => {
        setPreviewFile(file);
        if (onFilePreview) {
            onFilePreview(file);
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-900">
                    Certificates ({employee.certificates?.length || 0})
                </h2>
                
                <div className="flex space-x-2">
                    <button
                        onClick={() => onAddNew()}
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Add Certificate
                    </button>
                </div>
            </div>

            {/* Certificate Type Sections */}
            <div className="space-y-8">
                {certificateTypes.map((certificateType) => {
                    const certificates = groupedCertificates[certificateType.id] || [];
                    
                    return (
                        <EnhancedCertificateTypeSection
                            key={certificateType.id}
                            typeName={certificateType.name}
                            typeId={certificateType.id}
                            certificates={certificates}
                            certificateType={certificateType}
                            employeeId={employee.id}
                            onEdit={onEdit}
                            onDelete={onDelete}
                            onFilePreview={handleFilePreview}
                            onUploadComplete={onUploadComplete}
                            onAddNew={onAddNew}
                            isRecurrent={certificateType.is_recurrent || false}
                        />
                    );
                })}
            </div>

            {/* Empty State */}
            {(!employee.certificates || employee.certificates.length === 0) && (
                <div className="text-center py-12">
                    <AcademicCapIcon className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                        No certificates found
                    </h3>
                    <p className="text-gray-600 mb-6">
                        Get started by adding the first certificate for {employee.name}
                    </p>
                    <button
                        onClick={() => onAddNew()}
                        className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <PlusIcon className="w-5 h-5 mr-2" />
                        Add First Certificate
                    </button>
                </div>
            )}

            {/* File Preview Modal */}
            {previewFile && (
                <FilePreview
                    file={previewFile}
                    onClose={() => setPreviewFile(null)}
                />
            )}
        </div>
    );
}

EnhancedCertificateCard.propTypes = {
    certificate: PropTypes.object.isRequired,
    certificateType: PropTypes.object,
    employeeId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    onEdit: PropTypes.func.isRequired,
    onDelete: PropTypes.func.isRequired,
    onFilePreview: PropTypes.func.isRequired,
    onUploadComplete: PropTypes.func,
    isLatest: PropTypes.bool,
    showHistory: PropTypes.bool,
    showUpload: PropTypes.bool
};

EnhancedCertificateTypeSection.propTypes = {
    typeName: PropTypes.string.isRequired,
    typeId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    certificates: PropTypes.array.isRequired,
    certificateType: PropTypes.object,
    employeeId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    onEdit: PropTypes.func.isRequired,
    onDelete: PropTypes.func.isRequired,
    onFilePreview: PropTypes.func.isRequired,
    onUploadComplete: PropTypes.func,
    onAddNew: PropTypes.func.isRequired,
    isRecurrent: PropTypes.bool
};

EnhancedCertificateList.propTypes = {
    employee: PropTypes.object.isRequired,
    certificateTypes: PropTypes.array.isRequired,
    onEdit: PropTypes.func.isRequired,
    onDelete: PropTypes.func.isRequired,
    onAddNew: PropTypes.func.isRequired,
    onFilePreview: PropTypes.func.isRequired,
    onUploadComplete: PropTypes.func
};