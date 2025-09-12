// components/FileUpload/FileVersioning.jsx
import { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import {
    ArrowUpOnSquareIcon,
    ClockIcon,
    DocumentDuplicateIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XMarkIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    TagIcon,
    CalendarIcon
} from '@heroicons/react/24/outline';
import { formatFileSize, getFileDisplay } from '../../utils/fileValidation';
import { useFileUpload } from '../../hooks/useFileUpload';
import UploadProgress from './UploadProgress';
import FilePreview from './FilePreview';

const VersionHistoryItem = ({ version, isLatest, onPreview, onDownload, onReplace }) => {
    const fileDisplay = getFileDisplay({ name: version.original_filename, type: version.mime_type });
    const uploadDate = new Date(version.uploaded_at);
    const issueDate = version.issue_date ? new Date(version.issue_date) : null;
    const expiryDate = version.expiry_date ? new Date(version.expiry_date) : null;

    const getStatusBadge = () => {
        if (isLatest) {
            return (
                <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <CheckCircleIcon className="w-3 h-3 mr-1" />
                    Current
                </span>
            );
        }

        return (
            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                <ClockIcon className="w-3 h-3 mr-1" />
                Version {version.version_number}
            </span>
        );
    };

    const getValidityStatus = () => {
        if (!expiryDate) return null;
        
        const now = new Date();
        const daysUntilExpiry = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));
        
        if (daysUntilExpiry < 0) {
            return (
                <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    Expired
                </span>
            );
        } else if (daysUntilExpiry <= 30) {
            return (
                <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    Expires in {daysUntilExpiry} days
                </span>
            );
        }
        
        return (
            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Valid
            </span>
        );
    };

    return (
        <div className={`bg-white rounded-lg border-2 transition-all ${
            isLatest ? 'border-green-200 bg-green-50' : 'border-gray-200'
        }`}>
            <div className="p-4">
                <div className="flex items-start justify-between">
                    <div className="flex items-start space-x-3 flex-1">
                        {/* File Icon */}
                        <div className={`p-2 rounded-lg flex-shrink-0 ${
                            isLatest ? 'bg-green-100' : 'bg-gray-100'
                        }`}>
                            <span className="text-xl">{fileDisplay.icon}</span>
                        </div>
                        
                        {/* File Info */}
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center space-x-2 mb-1">
                                <h4 className="text-sm font-medium text-gray-900 truncate">
                                    {version.original_filename}
                                </h4>
                                {getStatusBadge()}
                                {getValidityStatus()}
                            </div>
                            
                            <div className="text-xs text-gray-600 space-y-1">
                                <div className="flex items-center space-x-4">
                                    <span>Size: {formatFileSize(version.file_size)}</span>
                                    <span>Type: {version.mime_type}</span>
                                    <span>Version: {version.version_number}</span>
                                </div>
                                
                                <div className="flex items-center space-x-4">
                                    <div className="flex items-center">
                                        <CalendarIcon className="w-3 h-3 mr-1" />
                                        Uploaded: {uploadDate.toLocaleDateString()}
                                    </div>
                                    {version.uploaded_by_name && (
                                        <span>By: {version.uploaded_by_name}</span>
                                    )}
                                </div>
                                
                                {issueDate && expiryDate && (
                                    <div className="flex items-center space-x-4">
                                        <span>Issue: {issueDate.toLocaleDateString()}</span>
                                        <span>Expires: {expiryDate.toLocaleDateString()}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                    
                    {/* Actions */}
                    <div className="flex items-center space-x-2 ml-4">
                        <button
                            onClick={() => onPreview(version)}
                            className="p-2 text-gray-400 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
                            title="Preview"
                        >
                            <EyeIcon className="w-4 h-4" />
                        </button>
                        
                        <button
                            onClick={() => onDownload(version)}
                            className="p-2 text-gray-400 hover:text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 rounded-lg"
                            title="Download"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4" />
                        </button>
                        
                        {isLatest && (
                            <button
                                onClick={() => onReplace(version)}
                                className="p-2 text-gray-400 hover:text-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 rounded-lg"
                                title="Replace with new version"
                            >
                                <ArrowUpOnSquareIcon className="w-4 h-4" />
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

const ReplaceFileModal = ({ 
    currentFile, 
    certificateType, 
    onReplace, 
    onCancel,
    employeeId 
}) => {
    const [issueDate, setIssueDate] = useState('');
    const [expiryDate, setExpiryDate] = useState('');
    const [notes, setNotes] = useState('');
    const [previewFile, setPreviewFile] = useState(null);
    
    const {
        files,
        uploading,
        uploadProgress,
        errors,
        handleFiles,
        uploadFiles,
        removeFile,
        clearErrors,
        dragHandlers,
        fileInputRef
    } = useFileUpload({
        maxFiles: 1,
        maxSize: 10 * 1024 * 1024,
        autoUpload: false
    });

    const handleSubmit = async () => {
        if (files.length === 0) return;

        clearErrors();
        
        try {
            const uploadUrl = `/employee-containers/${employeeId}/certificates/${currentFile.id}/replace`;
            const additionalData = {
                certificate_type_id: certificateType.id,
                issue_date: issueDate,
                expiry_date: expiryDate,
                notes: notes,
                replace_version: true
            };

            const result = await uploadFiles(files, uploadUrl, { additionalData });
            
            if (result.successful > 0) {
                onReplace(result);
            }
        } catch (error) {
            console.error('File replacement error:', error);
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex items-center justify-between p-6 border-b border-gray-200">
                    <div>
                        <h3 className="text-lg font-medium text-gray-900">
                            Replace Certificate File
                        </h3>
                        <p className="text-sm text-gray-600 mt-1">
                            Upload a new version of {currentFile.original_filename}
                        </p>
                    </div>
                    <button
                        onClick={onCancel}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <XMarkIcon className="w-6 h-6" />
                    </button>
                </div>

                {/* Content */}
                <div className="p-6">
                    {/* Current File Info */}
                    <div className="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h4 className="text-sm font-medium text-gray-900 mb-2">Current Version</h4>
                        <div className="text-xs text-gray-600 space-y-1">
                            <div>File: {currentFile.original_filename}</div>
                            <div>Version: {currentFile.version_number}</div>
                            <div>Size: {formatFileSize(currentFile.file_size)}</div>
                            <div>Uploaded: {new Date(currentFile.uploaded_at).toLocaleDateString()}</div>
                        </div>
                    </div>

                    {/* File Upload */}
                    <div className="mb-6">
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            New File
                        </label>
                        
                        {files.length === 0 ? (
                            <div
                                {...dragHandlers}
                                onClick={() => fileInputRef.current?.click()}
                                className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 hover:bg-gray-50 cursor-pointer transition-colors"
                            >
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onChange={(e) => handleFiles(e.target.files)}
                                    className="hidden"
                                />
                                
                                <DocumentDuplicateIcon className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                                <p className="text-sm text-gray-600 mb-1">
                                    Drop new file here or click to browse
                                </p>
                                <p className="text-xs text-gray-500">
                                    PDF, JPG, PNG up to 10MB
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {files.map(file => (
                                    <div key={file.id} className="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                                        <div className="flex items-center">
                                            <span className="text-2xl mr-3">
                                                {file.type?.startsWith('image/') ? '🖼️' : '📄'}
                                            </span>
                                            <div>
                                                <div className="text-sm font-medium text-gray-900">
                                                    {file.name}
                                                </div>
                                                <div className="text-xs text-gray-600">
                                                    {formatFileSize(file.size)}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <button
                                                onClick={() => setPreviewFile(file)}
                                                className="p-1 text-blue-600 hover:text-blue-800"
                                                title="Preview"
                                            >
                                                <EyeIcon className="w-4 h-4" />
                                            </button>
                                            <button
                                                onClick={() => removeFile(file.id)}
                                                className="p-1 text-red-600 hover:text-red-800"
                                                title="Remove"
                                            >
                                                <XMarkIcon className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Certificate Details */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Issue Date
                            </label>
                            <input
                                type="date"
                                value={issueDate}
                                onChange={(e) => setIssueDate(e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Expiry Date
                            </label>
                            <input
                                type="date"
                                value={expiryDate}
                                onChange={(e) => setExpiryDate(e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <div className="mb-6">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Notes (Optional)
                        </label>
                        <textarea
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            rows={3}
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Reason for replacement or additional notes..."
                        />
                    </div>

                    {/* Errors */}
                    {errors.length > 0 && (
                        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                            <div className="flex">
                                <ExclamationTriangleIcon className="w-5 h-5 text-red-400 mt-0.5 mr-2 flex-shrink-0" />
                                <div>
                                    <h4 className="text-sm font-medium text-red-900">Upload Errors</h4>
                                    <ul className="mt-2 text-sm text-red-700 space-y-1">
                                        {errors.map((error, index) => (
                                            <li key={index}>{error.message}</li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Upload Progress */}
                    {uploading && (
                        <div className="mb-6">
                            <UploadProgress
                                files={files}
                                uploadProgress={uploadProgress}
                                showOverallProgress={false}
                            />
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="flex items-center justify-between px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div className="text-sm text-gray-600">
                        This will create version {currentFile.version_number + 1}
                    </div>
                    
                    <div className="flex space-x-3">
                        <button
                            onClick={onCancel}
                            disabled={uploading}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleSubmit}
                            disabled={files.length === 0 || uploading || !issueDate || !expiryDate}
                            className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {uploading ? 'Uploading...' : 'Replace File'}
                        </button>
                    </div>
                </div>
            </div>

            {/* Preview Modal */}
            {previewFile && (
                <FilePreview
                    file={previewFile}
                    onClose={() => setPreviewFile(null)}
                />
            )}
        </div>
    );
};

const FileVersioning = ({ 
    employeeId, 
    certificateTypeId, 
    certificateType,
    onVersionUpdate,
    className = "" 
}) => {
    const [versions, setVersions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [showReplaceModal, setShowReplaceModal] = useState(false);
    const [currentFile, setCurrentFile] = useState(null);
    const [previewFile, setPreviewFile] = useState(null);

    useEffect(() => {
        fetchVersions();
    }, [employeeId, certificateTypeId]);

    const fetchVersions = async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/files/versions/${employeeId}/${certificateTypeId}`);
            
            if (!response.ok) {
                throw new Error('Failed to fetch file versions');
            }

            const data = await response.json();
            setVersions(data.versions || []);
        } catch (err) {
            console.error('Error fetching versions:', err);
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handlePreview = (version) => {
        setPreviewFile(version);
    };

    const handleDownload = (version) => {
        window.open(version.download_url, '_blank');
    };

    const handleReplace = (version) => {
        setCurrentFile(version);
        setShowReplaceModal(true);
    };

    const handleReplaceComplete = (result) => {
        setShowReplaceModal(false);
        setCurrentFile(null);
        fetchVersions(); // Refresh versions list
        
        if (onVersionUpdate) {
            onVersionUpdate(result);
        }
    };

    const handleReplaceCancel = () => {
        setShowReplaceModal(false);
        setCurrentFile(null);
    };

    if (loading) {
        return (
            <div className={`flex items-center justify-center py-8 ${className}`}>
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span className="ml-3 text-gray-600">Loading versions...</span>
            </div>
        );
    }

    if (error) {
        return (
            <div className={`text-center py-8 ${className}`}>
                <ExclamationTriangleIcon className="w-12 h-12 text-red-400 mx-auto mb-3" />
                <p className="text-sm text-red-600">{error}</p>
                <button
                    onClick={fetchVersions}
                    className="mt-3 text-blue-600 hover:text-blue-800 text-sm underline"
                >
                    Try again
                </button>
            </div>
        );
    }

    if (versions.length === 0) {
        return (
            <div className={`text-center py-8 ${className}`}>
                <DocumentDuplicateIcon className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                <p className="text-sm text-gray-600">No file versions found</p>
                <p className="text-xs text-gray-500 mt-1">
                    Upload a file to see version history
                </p>
            </div>
        );
    }

    const latestVersion = versions.find(v => v.is_latest);
    const olderVersions = versions.filter(v => !v.is_latest).sort((a, b) => b.version_number - a.version_number);

    return (
        <div className={className}>
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h3 className="text-lg font-medium text-gray-900">File Versions</h3>
                    <p className="text-sm text-gray-600">
                        {certificateType?.name} • {versions.length} version{versions.length !== 1 ? 's' : ''}
                    </p>
                </div>
                
                <div className="flex items-center space-x-2">
                    <TagIcon className="w-4 h-4 text-gray-400" />
                    <span className="text-sm text-gray-600">
                        Current: v{latestVersion?.version_number}
                    </span>
                </div>
            </div>

            <div className="space-y-4">
                {/* Current Version */}
                {latestVersion && (
                    <VersionHistoryItem
                        version={latestVersion}
                        isLatest={true}
                        onPreview={handlePreview}
                        onDownload={handleDownload}
                        onReplace={handleReplace}
                    />
                )}

                {/* Older Versions */}
                {olderVersions.length > 0 && (
                    <div>
                        <h4 className="text-sm font-medium text-gray-700 mb-3">Previous Versions</h4>
                        <div className="space-y-3">
                            {olderVersions.map(version => (
                                <VersionHistoryItem
                                    key={version.id}
                                    version={version}
                                    isLatest={false}
                                    onPreview={handlePreview}
                                    onDownload={handleDownload}
                                    onReplace={handleReplace}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Replace File Modal */}
            {showReplaceModal && currentFile && (
                <ReplaceFileModal
                    currentFile={currentFile}
                    certificateType={certificateType}
                    employeeId={employeeId}
                    onReplace={handleReplaceComplete}
                    onCancel={handleReplaceCancel}
                />
            )}

            {/* Preview Modal */}
            {previewFile && (
                <FilePreview
                    file={previewFile}
                    onClose={() => setPreviewFile(null)}
                />
            )}
        </div>
    );
};

VersionHistoryItem.propTypes = {
    version: PropTypes.object.isRequired,
    isLatest: PropTypes.bool.isRequired,
    onPreview: PropTypes.func.isRequired,
    onDownload: PropTypes.func.isRequired,
    onReplace: PropTypes.func.isRequired
};

ReplaceFileModal.propTypes = {
    currentFile: PropTypes.object.isRequired,
    certificateType: PropTypes.object.isRequired,
    employeeId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    onReplace: PropTypes.func.isRequired,
    onCancel: PropTypes.func.isRequired
};

FileVersioning.propTypes = {
    employeeId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    certificateTypeId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    certificateType: PropTypes.object,
    onVersionUpdate: PropTypes.func,
    className: PropTypes.string
};

export default FileVersioning;