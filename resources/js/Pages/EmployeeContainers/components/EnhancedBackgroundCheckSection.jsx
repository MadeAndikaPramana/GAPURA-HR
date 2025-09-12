// Pages/EmployeeContainers/components/EnhancedBackgroundCheckSection.jsx
import { useState } from 'react';
import { router } from '@inertiajs/react';
import PropTypes from 'prop-types';
import {
    ShieldCheckIcon,
    DocumentTextIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    TrashIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    PlusIcon,
    XMarkIcon,
    CloudArrowUpIcon
} from '@heroicons/react/24/outline';
import { ResponsiveFileUpload, FilePreview } from '../../../components/FileUpload';

const BackgroundCheckStatusBadge = ({ status }) => {
    const statusConfig = {
        completed: {
            icon: CheckCircleIcon,
            color: 'text-green-700',
            bgColor: 'bg-green-50',
            borderColor: 'border-green-200',
            label: 'Completed'
        },
        pending: {
            icon: ClockIcon,
            color: 'text-yellow-700',
            bgColor: 'bg-yellow-50',
            borderColor: 'border-yellow-200',
            label: 'Pending'
        },
        incomplete: {
            icon: ExclamationTriangleIcon,
            color: 'text-red-700',
            bgColor: 'bg-red-50',
            borderColor: 'border-red-200',
            label: 'Incomplete'
        },
        'in-progress': {
            icon: ClockIcon,
            color: 'text-blue-700',
            bgColor: 'bg-blue-50',
            borderColor: 'border-blue-200',
            label: 'In Progress'
        }
    };

    const config = statusConfig[status] || statusConfig.incomplete;
    const StatusIcon = config.icon;

    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.color} ${config.bgColor} border ${config.borderColor}`}>
            <StatusIcon className={`w-3 h-3 mr-1 ${config.color}`} />
            {config.label}
        </span>
    );
};

const BackgroundCheckFileItem = ({ file, index, onPreview, onDownload, onDelete, canDelete = true }) => {
    const getFileIcon = (fileName) => {
        const extension = fileName.split('.').pop().toLowerCase();
        if (['pdf'].includes(extension)) {
            return '📄';
        } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
            return '🖼️';
        }
        return '📎';
    };

    const formatFileSize = (bytes) => {
        if (!bytes) return 'Unknown size';
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    return (
        <div className="group flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg hover:shadow-sm transition-all">
            <div className="flex items-center space-x-3 flex-1 min-w-0">
                <div className="flex-shrink-0 text-2xl">
                    {getFileIcon(file.original_name || file.name)}
                </div>
                <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 truncate">
                        {file.original_name || file.name}
                    </p>
                    <div className="text-xs text-gray-500 space-x-2">
                        {file.file_size && <span>{formatFileSize(file.file_size)}</span>}
                        {file.uploaded_at && (
                            <span>Uploaded: {new Date(file.uploaded_at).toLocaleDateString()}</span>
                        )}
                        {file.uploaded_by && <span>by {file.uploaded_by}</span>}
                    </div>
                </div>
            </div>

            <div className="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <button
                    onClick={() => onPreview(file)}
                    className="p-2 text-gray-400 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
                    title="Preview file"
                >
                    <EyeIcon className="w-4 h-4" />
                </button>
                
                <button
                    onClick={() => onDownload(index)}
                    className="p-2 text-gray-400 hover:text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 rounded-lg"
                    title="Download file"
                >
                    <ArrowDownTrayIcon className="w-4 h-4" />
                </button>
                
                {canDelete && (
                    <button
                        onClick={() => onDelete(index)}
                        className="p-2 text-gray-400 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 rounded-lg"
                        title="Delete file"
                    >
                        <TrashIcon className="w-4 h-4" />
                    </button>
                )}
            </div>
        </div>
    );
};

const BackgroundCheckForm = ({ employee, onUpdate, onCancel }) => {
    const [formData, setFormData] = useState({
        status: employee.background_check_status || 'incomplete',
        completion_date: employee.background_check_completion_date || '',
        expiry_date: employee.background_check_expiry_date || '',
        authority: employee.background_check_authority || '',
        reference_number: employee.background_check_reference_number || '',
        notes: employee.background_check_notes || ''
    });
    const [saving, setSaving] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);

        try {
            router.put(route('employee-containers.background-check.update', employee.id), formData, {
                onSuccess: () => {
                    onUpdate?.();
                },
                onError: (errors) => {
                    console.error('Update failed:', errors);
                },
                onFinish: () => {
                    setSaving(false);
                }
            });
        } catch (error) {
            console.error('Background check update error:', error);
            setSaving(false);
        }
    };

    const handleChange = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Status
                    </label>
                    <select
                        value={formData.status}
                        onChange={(e) => handleChange('status', e.target.value)}
                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        required
                    >
                        <option value="incomplete">Incomplete</option>
                        <option value="pending">Pending</option>
                        <option value="in-progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Authority/Agency
                    </label>
                    <input
                        type="text"
                        value={formData.authority}
                        onChange={(e) => handleChange('authority', e.target.value)}
                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g., Indonesian National Police"
                    />
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Completion Date
                    </label>
                    <input
                        type="date"
                        value={formData.completion_date}
                        onChange={(e) => handleChange('completion_date', e.target.value)}
                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Expiry Date
                    </label>
                    <input
                        type="date"
                        value={formData.expiry_date}
                        onChange={(e) => handleChange('expiry_date', e.target.value)}
                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Reference Number
                    </label>
                    <input
                        type="text"
                        value={formData.reference_number}
                        onChange={(e) => handleChange('reference_number', e.target.value)}
                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Background check reference or certificate number"
                    />
                </div>

                <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Notes
                    </label>
                    <textarea
                        value={formData.notes}
                        onChange={(e) => handleChange('notes', e.target.value)}
                        rows={3}
                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Additional notes about the background check..."
                    />
                </div>
            </div>

            <div className="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                <button
                    type="button"
                    onClick={onCancel}
                    disabled={saving}
                    className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    disabled={saving}
                    className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {saving ? 'Saving...' : 'Save Changes'}
                </button>
            </div>
        </form>
    );
};

export default function EnhancedBackgroundCheckSection({ 
    employee, 
    onFilePreview, 
    onUpdate 
}) {
    const [showForm, setShowForm] = useState(false);
    const [showUpload, setShowUpload] = useState(false);
    const [previewFile, setPreviewFile] = useState(null);
    const [uploading, setUploading] = useState(false);

    const handleFileDownload = (fileIndex) => {
        const downloadUrl = route('employee-containers.background-check.download', {
            employee: employee.id,
            fileIndex: fileIndex
        });
        window.open(downloadUrl, '_blank');
    };

    const handleFileDelete = (fileIndex) => {
        if (confirm('Are you sure you want to delete this file?')) {
            router.delete(route('employee-containers.background-check.delete', {
                employee: employee.id,
                fileIndex: fileIndex
            }), {
                onSuccess: () => {
                    // Page will reload automatically
                },
                onError: (errors) => {
                    console.error('Delete failed:', errors);
                    alert('Failed to delete file. Please try again.');
                }
            });
        }
    };

    const handleFilePreview = (file) => {
        setPreviewFile(file);
        onFilePreview?.(file);
    };

    const handleFileUploadComplete = (result) => {
        setShowUpload(false);
        setUploading(false);
        
        // Refresh the page data
        router.reload({ only: ['employee'] });
        
        onUpdate?.();
    };

    const handleFormUpdate = () => {
        setShowForm(false);
        onUpdate?.();
    };

    const getStatusDescription = (status) => {
        const descriptions = {
            completed: 'Background check has been completed and all requirements are met.',
            pending: 'Background check has been initiated and is awaiting results.',
            'in-progress': 'Background check is currently being processed.',
            incomplete: 'Background check requirements have not been met or are missing.'
        };
        return descriptions[status] || descriptions.incomplete;
    };

    const backgroundCheckFiles = employee.background_check_files || [];
    const hasFiles = backgroundCheckFiles.length > 0;
    const status = employee.background_check_status || 'incomplete';

    return (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200">
            {/* Header */}
            <div className="px-6 py-4 border-b border-gray-200">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                        <div className="p-2 bg-purple-100 rounded-lg">
                            <ShieldCheckIcon className="w-5 h-5 text-purple-600" />
                        </div>
                        <div>
                            <h3 className="text-lg font-medium text-gray-900">
                                Background Check
                            </h3>
                            <p className="text-sm text-gray-600">
                                Security clearance and background verification
                            </p>
                        </div>
                    </div>
                    
                    <div className="flex items-center space-x-3">
                        <BackgroundCheckStatusBadge status={status} />
                        
                        <div className="flex items-center space-x-2">
                            <button
                                onClick={() => setShowUpload(!showUpload)}
                                className="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <CloudArrowUpIcon className="w-4 h-4 mr-1" />
                                Upload Files
                            </button>
                            
                            <button
                                onClick={() => setShowForm(!showForm)}
                                className="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                            >
                                <PencilIcon className="w-4 h-4 mr-1" />
                                {showForm ? 'Cancel' : 'Edit Details'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div className="p-6">
                {/* Status Description */}
                <div className="mb-6 p-4 bg-gray-50 rounded-lg">
                    <p className="text-sm text-gray-700">
                        {getStatusDescription(status)}
                    </p>
                </div>

                {/* Form Section */}
                {showForm && (
                    <div className="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                        <div className="flex items-center justify-between mb-4">
                            <h4 className="text-sm font-medium text-gray-900">Edit Background Check Details</h4>
                            <button
                                onClick={() => setShowForm(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <XMarkIcon className="w-4 h-4" />
                            </button>
                        </div>
                        
                        <BackgroundCheckForm
                            employee={employee}
                            onUpdate={handleFormUpdate}
                            onCancel={() => setShowForm(false)}
                        />
                    </div>
                )}

                {/* File Upload Section */}
                {showUpload && (
                    <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div className="flex items-center justify-between mb-4">
                            <h4 className="text-sm font-medium text-gray-900">Upload Background Check Documents</h4>
                            <button
                                onClick={() => setShowUpload(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <XMarkIcon className="w-4 h-4" />
                            </button>
                        </div>
                        
                        <ResponsiveFileUpload
                            uploadUrl={route('employee-containers.background-check.upload', employee.id)}
                            onUploadComplete={handleFileUploadComplete}
                            certificateType="background-check"
                            existingFiles={backgroundCheckFiles}
                            maxFiles={10}
                            accept=".pdf,.jpg,.jpeg,.png"
                            enablePreview={true}
                            additionalData={{
                                employee_id: employee.id,
                                document_type: 'background_check'
                            }}
                        />
                    </div>
                )}

                {/* Background Check Details */}
                {(employee.background_check_authority || employee.background_check_reference_number || employee.background_check_completion_date || employee.background_check_expiry_date) && (
                    <div className="mb-6">
                        <h4 className="text-sm font-medium text-gray-900 mb-3">Details</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            {employee.background_check_authority && (
                                <div>
                                    <span className="font-medium text-gray-700">Authority:</span>
                                    <p className="text-gray-900 mt-1">{employee.background_check_authority}</p>
                                </div>
                            )}
                            {employee.background_check_reference_number && (
                                <div>
                                    <span className="font-medium text-gray-700">Reference Number:</span>
                                    <p className="text-gray-900 mt-1">{employee.background_check_reference_number}</p>
                                </div>
                            )}
                            {employee.background_check_completion_date && (
                                <div>
                                    <span className="font-medium text-gray-700">Completed:</span>
                                    <p className="text-gray-900 mt-1">
                                        {new Date(employee.background_check_completion_date).toLocaleDateString()}
                                    </p>
                                </div>
                            )}
                            {employee.background_check_expiry_date && (
                                <div>
                                    <span className="font-medium text-gray-700">Expires:</span>
                                    <p className="text-gray-900 mt-1">
                                        {new Date(employee.background_check_expiry_date).toLocaleDateString()}
                                    </p>
                                </div>
                            )}
                        </div>
                        
                        {employee.background_check_notes && (
                            <div className="mt-4">
                                <span className="font-medium text-gray-700">Notes:</span>
                                <p className="text-gray-900 mt-1 whitespace-pre-wrap">{employee.background_check_notes}</p>
                            </div>
                        )}
                    </div>
                )}

                {/* Files Section */}
                <div>
                    <div className="flex items-center justify-between mb-4">
                        <h4 className="text-sm font-medium text-gray-900">
                            Documents ({backgroundCheckFiles.length})
                        </h4>
                        
                        {!showUpload && (
                            <button
                                onClick={() => setShowUpload(true)}
                                className="text-sm text-blue-600 hover:text-blue-800 flex items-center"
                            >
                                <PlusIcon className="w-4 h-4 mr-1" />
                                Add Files
                            </button>
                        )}
                    </div>
                    
                    {hasFiles ? (
                        <div className="space-y-3">
                            {backgroundCheckFiles.map((file, index) => (
                                <BackgroundCheckFileItem
                                    key={index}
                                    file={file}
                                    index={index}
                                    onPreview={handleFilePreview}
                                    onDownload={handleFileDownload}
                                    onDelete={handleFileDelete}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <DocumentTextIcon className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                            <p className="text-sm text-gray-600 mb-3">
                                No background check documents uploaded
                            </p>
                            <button
                                onClick={() => setShowUpload(true)}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <PlusIcon className="w-4 h-4 mr-2" />
                                Upload Documents
                            </button>
                        </div>
                    )}
                </div>
            </div>

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

BackgroundCheckStatusBadge.propTypes = {
    status: PropTypes.string.isRequired
};

BackgroundCheckFileItem.propTypes = {
    file: PropTypes.object.isRequired,
    index: PropTypes.number.isRequired,
    onPreview: PropTypes.func.isRequired,
    onDownload: PropTypes.func.isRequired,
    onDelete: PropTypes.func.isRequired,
    canDelete: PropTypes.bool
};

BackgroundCheckForm.propTypes = {
    employee: PropTypes.object.isRequired,
    onUpdate: PropTypes.func,
    onCancel: PropTypes.func.isRequired
};

EnhancedBackgroundCheckSection.propTypes = {
    employee: PropTypes.object.isRequired,
    onFilePreview: PropTypes.func,
    onUpdate: PropTypes.func
};