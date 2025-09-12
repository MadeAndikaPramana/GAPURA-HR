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
    ChevronRightIcon
} from '@heroicons/react/24/outline';

// Certificate Card Component
function CertificateCard({ certificate, onEdit, onDelete, onFilePreview, isLatest = false, showHistory = false }) {
    const [expanded, setExpanded] = useState(false);

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

    const statusConfig = getStatusConfig(certificate.status);
    const StatusIcon = statusConfig.icon;

    const formatDate = (dateString) => {
        if (!dateString) return 'Not set';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const handleFileDownload = (fileIndex) => {
        window.open(
            route('employee-containers.certificates.download', [certificate.employee_id, certificate.id, fileIndex]),
            '_blank'
        );
    };

    return (
        <div className={`bg-white border rounded-lg ${isLatest ? 'ring-2 ring-blue-200 border-blue-300' : 'border-gray-200'} hover:shadow-md transition-all duration-200`}>
            {/* Card Header */}
            <div className="p-4 border-b border-gray-200">
                <div className="flex items-start justify-between">
                    <div className="flex-1">
                        <div className="flex items-center space-x-2 mb-2">
                            <h4 className="text-sm font-medium text-gray-900">
                                {certificate.certificate_type?.name || 'Unknown Certificate'}
                            </h4>
                            {isLatest && (
                                <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-blue-700 bg-blue-100 border border-blue-200">
                                    Latest
                                </span>
                            )}
                        </div>
                        
                        <div className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border ${statusConfig.color} ${statusConfig.bgColor} ${statusConfig.borderColor}`}>
                            <StatusIcon className="w-3 h-3 mr-1" />
                            {statusConfig.label}
                        </div>
                    </div>

                    <div className="flex items-center space-x-1">
                        <button
                            onClick={() => onEdit(certificate)}
                            className="p-1 rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            title="Edit certificate"
                        >
                            <PencilIcon className="w-4 h-4 text-gray-600" />
                        </button>
                        <button
                            onClick={() => onDelete(certificate)}
                            className="p-1 rounded-full hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500"
                            title="Delete certificate"
                        >
                            <TrashIcon className="w-4 h-4 text-red-600" />
                        </button>
                    </div>
                </div>
            </div>

            {/* Certificate Details */}
            <div className="p-4">
                <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span className="text-gray-600">Certificate No:</span>
                        <p className="font-medium text-gray-900">
                            {certificate.certificate_number || 'Not provided'}
                        </p>
                    </div>
                    <div>
                        <span className="text-gray-600">Issuer:</span>
                        <p className="font-medium text-gray-900">
                            {certificate.issuer || 'Not provided'}
                        </p>
                    </div>
                    <div>
                        <span className="text-gray-600">Issue Date:</span>
                        <p className="font-medium text-gray-900 flex items-center">
                            <CalendarIcon className="w-4 h-4 mr-1 text-gray-400" />
                            {formatDate(certificate.issue_date)}
                        </p>
                    </div>
                    <div>
                        <span className="text-gray-600">Expiry Date:</span>
                        <p className="font-medium text-gray-900 flex items-center">
                            <CalendarIcon className="w-4 h-4 mr-1 text-gray-400" />
                            {formatDate(certificate.expiry_date)}
                        </p>
                    </div>
                </div>

                {/* Show More/Less Button */}
                {(certificate.training_provider || certificate.score || certificate.training_hours || certificate.notes) && (
                    <button
                        onClick={() => setExpanded(!expanded)}
                        className="mt-3 flex items-center text-sm text-blue-600 hover:text-blue-800"
                    >
                        {expanded ? (
                            <>
                                <ChevronDownIcon className="w-4 h-4 mr-1" />
                                Show Less
                            </>
                        ) : (
                            <>
                                <ChevronRightIcon className="w-4 h-4 mr-1" />
                                Show More Details
                            </>
                        )}
                    </button>
                )}

                {/* Expanded Details */}
                {expanded && (
                    <div className="mt-3 pt-3 border-t border-gray-200 space-y-3">
                        {certificate.training_provider && (
                            <div>
                                <span className="text-xs text-gray-600">Training Provider:</span>
                                <p className="text-sm font-medium text-gray-900">{certificate.training_provider}</p>
                            </div>
                        )}
                        
                        <div className="grid grid-cols-2 gap-4">
                            {certificate.score && (
                                <div>
                                    <span className="text-xs text-gray-600">Score:</span>
                                    <p className="text-sm font-medium text-gray-900">{certificate.score}%</p>
                                </div>
                            )}
                            {certificate.training_hours && (
                                <div>
                                    <span className="text-xs text-gray-600">Training Hours:</span>
                                    <p className="text-sm font-medium text-gray-900">{certificate.training_hours}h</p>
                                </div>
                            )}
                        </div>

                        {certificate.notes && (
                            <div>
                                <span className="text-xs text-gray-600">Notes:</span>
                                <p className="text-sm text-gray-900 whitespace-pre-wrap">{certificate.notes}</p>
                            </div>
                        )}
                    </div>
                )}

                {/* Files */}
                {certificate.certificate_files && certificate.certificate_files.length > 0 && (
                    <div className="mt-4 pt-3 border-t border-gray-200">
                        <h5 className="text-xs font-medium text-gray-700 mb-2">
                            Files ({certificate.certificate_files.length})
                        </h5>
                        <div className="space-y-2">
                            {certificate.certificate_files.map((file, index) => (
                                <div
                                    key={index}
                                    className="flex items-center justify-between p-2 bg-gray-50 rounded border border-gray-200"
                                >
                                    <div className="flex items-center space-x-2">
                                        <DocumentTextIcon className="w-4 h-4 text-gray-400" />
                                        <span className="text-sm text-gray-900 truncate">
                                            {file.original_name}
                                        </span>
                                    </div>
                                    <div className="flex items-center space-x-1">
                                        <button
                                            onClick={() => onFilePreview(file)}
                                            className="p-1 rounded hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            title="Preview file"
                                        >
                                            <EyeIcon className="w-3 h-3 text-gray-600" />
                                        </button>
                                        <button
                                            onClick={() => handleFileDownload(index)}
                                            className="p-1 rounded hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            title="Download file"
                                        >
                                            <ArrowDownTrayIcon className="w-3 h-3 text-gray-600" />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

CertificateCard.propTypes = {
    certificate: PropTypes.object.isRequired,
    onEdit: PropTypes.func.isRequired,
    onDelete: PropTypes.func.isRequired,
    onFilePreview: PropTypes.func.isRequired,
    isLatest: PropTypes.bool,
    showHistory: PropTypes.bool
};

// Certificate Type Section Component
function CertificateTypeSection({ 
    typeName, 
    certificates, 
    onEdit, 
    onDelete, 
    onFilePreview, 
    isRecurrent = false 
}) {
    const [showHistory, setShowHistory] = useState(false);
    
    // Sort certificates by issue date (newest first)
    const sortedCertificates = [...certificates].sort((a, b) => 
        new Date(b.issue_date || 0) - new Date(a.issue_date || 0)
    );

    const latestCertificate = sortedCertificates[0];
    const historicalCertificates = sortedCertificates.slice(1);

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
                        {certificates.length} certificates
                    </span>
                </div>

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
                                View History ({historicalCertificates.length})
                            </>
                        )}
                    </button>
                )}
            </div>

            {/* Latest Certificate */}
            {latestCertificate && (
                <div className="mb-4">
                    <CertificateCard
                        certificate={latestCertificate}
                        onEdit={onEdit}
                        onDelete={onDelete}
                        onFilePreview={onFilePreview}
                        isLatest={certificates.length > 1}
                    />
                </div>
            )}

            {/* Historical Certificates */}
            {showHistory && historicalCertificates.length > 0 && (
                <div className="space-y-4 pl-4 border-l-2 border-gray-200">
                    <h4 className="text-sm font-medium text-gray-700 mb-3">Previous Certificates</h4>
                    {historicalCertificates.map((certificate) => (
                        <CertificateCard
                            key={certificate.id}
                            certificate={certificate}
                            onEdit={onEdit}
                            onDelete={onDelete}
                            onFilePreview={onFilePreview}
                            showHistory={true}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

CertificateTypeSection.propTypes = {
    typeName: PropTypes.string.isRequired,
    certificates: PropTypes.array.isRequired,
    onEdit: PropTypes.func.isRequired,
    onDelete: PropTypes.func.isRequired,
    onFilePreview: PropTypes.func.isRequired,
    isRecurrent: PropTypes.bool
};

// Main Certificate List Component
export default function CertificateList({ employee, certificates, onFilePreview, onAddCertificate }) {
    const handleEdit = (certificate) => {
        // This would open an edit modal or navigate to edit page
        console.log('Edit certificate:', certificate);
    };

    const handleDelete = async (certificate) => {
        if (confirm('Are you sure you want to delete this certificate? This action cannot be undone.')) {
            try {
                await router.delete(
                    route('employee-containers.certificates.destroy', [employee.id, certificate.id])
                );
            } catch (error) {
                console.error('Delete failed:', error);
            }
        }
    };

    // Group certificates by type
    const certificatesByType = certificates || {};

    return (
        <div className="bg-white rounded-lg border border-gray-200">
            {/* Header */}
            <div className="p-4 border-b border-gray-200">
                <div className="flex items-center justify-between">
                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                        <AcademicCapIcon className="w-5 h-5 mr-2" />
                        Certificates
                    </h3>
                    <button
                        onClick={onAddCertificate}
                        className="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Add Certificate
                    </button>
                </div>
            </div>

            {/* Certificate Content */}
            <div className="p-4">
                {Object.keys(certificatesByType).length > 0 ? (
                    <div>
                        {Object.entries(certificatesByType).map(([typeName, typeCertificates]) => (
                            <CertificateTypeSection
                                key={typeName}
                                typeName={typeName}
                                certificates={typeCertificates}
                                onEdit={handleEdit}
                                onDelete={handleDelete}
                                onFilePreview={onFilePreview}
                                isRecurrent={typeCertificates.length > 1} // Assume recurrent if multiple certificates
                            />
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-8">
                        <AcademicCapIcon className="mx-auto h-12 w-12 text-gray-300" />
                        <h3 className="mt-2 text-sm font-medium text-gray-900">No certificates</h3>
                        <p className="mt-1 text-sm text-gray-500">
                            No certificates have been added to this employee container yet.
                        </p>
                        <div className="mt-6">
                            <button
                                onClick={onAddCertificate}
                                className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <PlusIcon className="w-4 h-4 mr-2" />
                                Add First Certificate
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

CertificateList.propTypes = {
    employee: PropTypes.object.isRequired,
    certificates: PropTypes.object,
    onFilePreview: PropTypes.func.isRequired,
    onAddCertificate: PropTypes.func.isRequired
};