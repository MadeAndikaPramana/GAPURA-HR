import { useState } from 'react';
import { router } from '@inertiajs/react';
import PropTypes from 'prop-types';
import FileUpload from './FileUpload';
import {
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    XCircleIcon,
    EyeIcon,
    TrashIcon,
    CalendarIcon,
    DocumentTextIcon,
    PencilIcon,
    CheckCircleIcon
} from '@heroicons/react/24/outline';

export default function BackgroundCheckSection({ employee, backgroundCheck, onFilePreview }) {
    const [isUploading, setIsUploading] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editForm, setEditForm] = useState({
        status: backgroundCheck?.status || 'not_started',
        notes: backgroundCheck?.notes || '',
        date: backgroundCheck?.date || ''
    });
    const [selectedFiles, setSelectedFiles] = useState([]);

    const getStatusConfig = (status) => {
        const configs = {
            cleared: {
                icon: ShieldCheckIcon,
                color: 'text-green-700',
                bgColor: 'bg-green-50',
                borderColor: 'border-green-200',
                label: 'Cleared'
            },
            in_progress: {
                icon: ClockIcon,
                color: 'text-yellow-700',
                bgColor: 'bg-yellow-50',
                borderColor: 'border-yellow-200',
                label: 'In Progress'
            },
            pending_review: {
                icon: ExclamationTriangleIcon,
                color: 'text-blue-700',
                bgColor: 'bg-blue-50',
                borderColor: 'border-blue-200',
                label: 'Pending Review'
            },
            requires_follow_up: {
                icon: ExclamationTriangleIcon,
                color: 'text-orange-700',
                bgColor: 'bg-orange-50',
                borderColor: 'border-orange-200',
                label: 'Requires Follow-up'
            },
            rejected: {
                icon: XCircleIcon,
                color: 'text-red-700',
                bgColor: 'bg-red-50',
                borderColor: 'border-red-200',
                label: 'Rejected'
            },
            expired: {
                icon: XCircleIcon,
                color: 'text-red-700',
                bgColor: 'bg-red-50',
                borderColor: 'border-red-200',
                label: 'Expired'
            },
            not_started: {
                icon: ClockIcon,
                color: 'text-gray-700',
                bgColor: 'bg-gray-50',
                borderColor: 'border-gray-200',
                label: 'Not Started'
            }
        };

        return configs[status] || configs.not_started;
    };

    const handleFileUpload = async (files) => {
        if (files.length === 0) return;

        setIsUploading(true);
        const formData = new FormData();

        files.forEach(file => {
            formData.append('files[]', file);
        });

        formData.append('status', editForm.status);
        formData.append('notes', editForm.notes);

        try {
            await router.post(
                route('employee-containers.background-check.upload', employee.id),
                formData,
                {
                    forceFormData: true,
                    onSuccess: () => {
                        setSelectedFiles([]);
                    },
                    onError: (errors) => {
                        console.error('Upload failed:', errors);
                    }
                }
            );
        } catch (error) {
            console.error('Upload error:', error);
        } finally {
            setIsUploading(false);
        }
    };

    const handleStatusUpdate = async () => {
        try {
            await router.put(
                route('employee-containers.background-check.update', employee.id),
                editForm,
                {
                    onSuccess: () => {
                        setIsEditing(false);
                    }
                }
            );
        } catch (error) {
            console.error('Update failed:', error);
        }
    };

    const handleFileDelete = async (fileIndex) => {
        if (confirm('Are you sure you want to delete this file?')) {
            try {
                await router.delete(
                    route('employee-containers.background-check.delete', [employee.id, fileIndex])
                );
            } catch (error) {
                console.error('Delete failed:', error);
            }
        }
    };

    const handleFileDownload = (fileIndex) => {
        window.open(
            route('employee-containers.background-check.download', [employee.id, fileIndex]),
            '_blank'
        );
    };

    const statusConfig = getStatusConfig(backgroundCheck?.status || 'not_started');
    const StatusIcon = statusConfig.icon;

    return (
        <div className="bg-white rounded-lg border border-gray-200">
            {/* Header */}
            <div className="p-4 border-b border-gray-200">
                <div className="flex items-center justify-between">
                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                        <ShieldCheckIcon className="w-5 h-5 mr-2" />
                        Background Check
                    </h3>
                    <button
                        onClick={() => setIsEditing(!isEditing)}
                        className="text-sm text-blue-600 hover:text-blue-800 flex items-center"
                    >
                        <PencilIcon className="w-4 h-4 mr-1" />
                        {isEditing ? 'Cancel' : 'Edit'}
                    </button>
                </div>
            </div>

            {/* Status Display */}
            <div className="p-4 border-b border-gray-200">
                <div className={`inline-flex items-center px-3 py-2 rounded-full text-sm font-medium border ${statusConfig.color} ${statusConfig.bgColor} ${statusConfig.borderColor}`}>
                    <StatusIcon className="w-4 h-4 mr-2" />
                    {statusConfig.label}
                </div>

                {backgroundCheck?.date && (
                    <div className="flex items-center mt-2 text-sm text-gray-600">
                        <CalendarIcon className="w-4 h-4 mr-1" />
                        Last updated: {backgroundCheck.date}
                    </div>
                )}
            </div>

            {/* Edit Form */}
            {isEditing && (
                <div className="p-4 bg-gray-50 border-b border-gray-200">
                    <div className="space-y-4">
                        {/* Status Selection */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Status
                            </label>
                            <select
                                value={editForm.status}
                                onChange={(e) => setEditForm({...editForm, status: e.target.value})}
                                className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            >
                                <option value="not_started">Not Started</option>
                                <option value="in_progress">In Progress</option>
                                <option value="pending_review">Pending Review</option>
                                <option value="cleared">Cleared</option>
                                <option value="requires_follow_up">Requires Follow-up</option>
                                <option value="rejected">Rejected</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>

                        {/* Date */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Date
                            </label>
                            <input
                                type="date"
                                value={editForm.date}
                                onChange={(e) => setEditForm({...editForm, date: e.target.value})}
                                className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            />
                        </div>

                        {/* Notes */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Notes
                            </label>
                            <textarea
                                rows={3}
                                value={editForm.notes}
                                onChange={(e) => setEditForm({...editForm, notes: e.target.value})}
                                placeholder="Add notes about the background check..."
                                className="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            />
                        </div>

                        {/* Action Buttons */}
                        <div className="flex justify-end space-x-2 pt-2">
                            <button
                                onClick={() => setIsEditing(false)}
                                className="px-3 py-1 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleStatusUpdate}
                                className="px-3 py-1 text-sm border border-transparent rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Update
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Notes Display */}
            {!isEditing && backgroundCheck?.notes && (
                <div className="p-4 border-b border-gray-200">
                    <h4 className="text-sm font-medium text-gray-700 mb-2">Notes</h4>
                    <p className="text-sm text-gray-600 whitespace-pre-wrap">
                        {backgroundCheck.notes}
                    </p>
                </div>
            )}

            {/* Files Section */}
            <div className="p-4">
                <h4 className="text-sm font-medium text-gray-700 mb-3">
                    Files ({backgroundCheck?.files_count || 0})
                </h4>

                {/* Existing Files */}
                {backgroundCheck?.files && backgroundCheck.files.length > 0 && (
                    <div className="space-y-2 mb-4">
                        {backgroundCheck.files.map((file, index) => (
                            <div
                                key={index}
                                className="flex items-center justify-between p-3 bg-gray-50 rounded-md border border-gray-200"
                            >
                                <div className="flex items-center space-x-3">
                                    <DocumentTextIcon className="w-5 h-5 text-gray-400" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">
                                            {file.original_name}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            Uploaded: {file.uploaded_at}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <button
                                        onClick={() => onFilePreview(file)}
                                        className="p-1 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        title="Preview file"
                                    >
                                        <EyeIcon className="w-4 h-4 text-gray-600" />
                                    </button>
                                    <button
                                        onClick={() => handleFileDownload(index)}
                                        className="text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        Download
                                    </button>
                                    <button
                                        onClick={() => handleFileDelete(index)}
                                        className="p-1 rounded-full hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500"
                                        title="Delete file"
                                    >
                                        <TrashIcon className="w-4 h-4 text-red-600" />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* File Upload */}
                <FileUpload
                    onFileSelect={setSelectedFiles}
                    accept=".pdf,.jpg,.jpeg,.png"
                    maxFiles={5}
                    maxSize={5 * 1024 * 1024}
                    label="Add Background Check Files"
                    description="Upload background check documents"
                />

                {/* Upload Button */}
                {selectedFiles.length > 0 && (
                    <div className="mt-3 flex justify-end">
                        <button
                            onClick={() => handleFileUpload(selectedFiles)}
                            disabled={isUploading}
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {isUploading ? (
                                <>
                                    <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Uploading...
                                </>
                            ) : (
                                <>
                                    <CheckCircleIcon className="w-4 h-4 mr-2" />
                                    Upload Files ({selectedFiles.length})
                                </>
                            )}
                        </button>
                    </div>
                )}

                {/* Empty State */}
                {(!backgroundCheck?.files || backgroundCheck.files.length === 0) && selectedFiles.length === 0 && (
                    <div className="text-center py-6 text-gray-500 text-sm">
                        <DocumentTextIcon className="w-8 h-8 mx-auto mb-2 text-gray-300" />
                        No background check files uploaded yet.
                    </div>
                )}
            </div>
        </div>
    );
}

BackgroundCheckSection.propTypes = {
    employee: PropTypes.object.isRequired,
    backgroundCheck: PropTypes.shape({
        status: PropTypes.string,
        date: PropTypes.string,
        notes: PropTypes.string,
        files: PropTypes.array,
        files_count: PropTypes.number,
        status_label: PropTypes.string
    }),
    onFilePreview: PropTypes.func.isRequired
};