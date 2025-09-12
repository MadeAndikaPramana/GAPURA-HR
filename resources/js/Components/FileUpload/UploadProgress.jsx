// components/FileUpload/UploadProgress.jsx
import { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import {
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    ClockIcon,
    ArrowPathIcon
} from '@heroicons/react/24/outline';
import { formatFileSize, getFileDisplay } from '../../utils/fileValidation';

const STATUS_CONFIG = {
    pending: {
        label: 'Waiting',
        icon: ClockIcon,
        color: 'gray',
        bgColor: 'bg-gray-100',
        textColor: 'text-gray-600',
        iconColor: 'text-gray-400'
    },
    uploading: {
        label: 'Uploading',
        icon: ArrowPathIcon,
        color: 'blue',
        bgColor: 'bg-blue-50',
        textColor: 'text-blue-900',
        iconColor: 'text-blue-500'
    },
    uploaded: {
        label: 'Completed',
        icon: CheckCircleIcon,
        color: 'green',
        bgColor: 'bg-green-50',
        textColor: 'text-green-900',
        iconColor: 'text-green-500'
    },
    error: {
        label: 'Failed',
        icon: XCircleIcon,
        color: 'red',
        bgColor: 'bg-red-50',
        textColor: 'text-red-900',
        iconColor: 'text-red-500'
    },
    cancelled: {
        label: 'Cancelled',
        icon: ExclamationTriangleIcon,
        color: 'yellow',
        bgColor: 'bg-yellow-50',
        textColor: 'text-yellow-900',
        iconColor: 'text-yellow-500'
    }
};

const ProgressBar = ({ progress, status, animated = true }) => {
    const isIndeterminate = status === 'uploading' && progress === 0;
    
    return (
        <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
            <div
                className={`h-full transition-all duration-300 ${
                    status === 'uploaded' 
                        ? 'bg-green-500' 
                        : status === 'error' 
                        ? 'bg-red-500'
                        : status === 'cancelled'
                        ? 'bg-yellow-500'
                        : 'bg-blue-500'
                } ${
                    isIndeterminate && animated 
                        ? 'animate-pulse' 
                        : ''
                }`}
                style={{ 
                    width: `${isIndeterminate ? 100 : progress}%`,
                    opacity: isIndeterminate ? 0.6 : 1
                }}
            />
        </div>
    );
};

const FileUploadItem = ({ 
    file, 
    progress = 0, 
    status = 'pending', 
    error = null, 
    onRetry = null, 
    onRemove = null,
    showPreview = true 
}) => {
    const statusConfig = STATUS_CONFIG[status] || STATUS_CONFIG.pending;
    const StatusIcon = statusConfig.icon;
    const fileDisplay = getFileDisplay(file);
    
    const [estimatedTime, setEstimatedTime] = useState(null);
    const [uploadSpeed, setUploadSpeed] = useState(null);
    
    useEffect(() => {
        if (status === 'uploading' && progress > 0 && progress < 100) {
            // Calculate estimated time and speed
            const startTime = file.uploadStartTime || Date.now();
            const elapsedTime = (Date.now() - startTime) / 1000; // seconds
            const uploadedBytes = (file.size * progress) / 100;
            const speed = uploadedBytes / elapsedTime; // bytes per second
            const remainingBytes = file.size - uploadedBytes;
            const timeRemaining = remainingBytes / speed; // seconds
            
            setUploadSpeed(speed);
            setEstimatedTime(timeRemaining);
        } else {
            setEstimatedTime(null);
            setUploadSpeed(null);
        }
    }, [progress, status, file.size, file.uploadStartTime]);

    const formatTimeRemaining = (seconds) => {
        if (seconds < 60) return `${Math.round(seconds)}s remaining`;
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.round(seconds % 60);
        return `${minutes}m ${remainingSeconds}s remaining`;
    };

    const formatSpeed = (bytesPerSecond) => {
        if (bytesPerSecond > 1024 * 1024) {
            return `${(bytesPerSecond / (1024 * 1024)).toFixed(1)} MB/s`;
        } else if (bytesPerSecond > 1024) {
            return `${(bytesPerSecond / 1024).toFixed(1)} KB/s`;
        }
        return `${bytesPerSecond.toFixed(0)} B/s`;
    };

    return (
        <div className={`rounded-lg border transition-all duration-200 ${
            statusConfig.bgColor
        } ${
            status === 'error' ? 'border-red-200' : 
            status === 'uploaded' ? 'border-green-200' : 
            status === 'uploading' ? 'border-blue-200' : 
            'border-gray-200'
        }`}>
            <div className="p-4">
                <div className="flex items-start space-x-3">
                    {/* File Icon/Preview */}
                    <div className="flex-shrink-0">
                        {showPreview && file.preview ? (
                            <img 
                                src={file.preview} 
                                alt="Preview" 
                                className="w-12 h-12 object-cover rounded-lg border"
                            />
                        ) : (
                            <div className={`w-12 h-12 rounded-lg flex items-center justify-center bg-${fileDisplay.color}-100`}>
                                <span className="text-2xl">{fileDisplay.icon}</span>
                            </div>
                        )}
                    </div>
                    
                    {/* File Info */}
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center justify-between">
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-medium text-gray-900 truncate">
                                    {file.name}
                                </p>
                                <p className="text-xs text-gray-500">
                                    {formatFileSize(file.size)} • {fileDisplay.type}
                                </p>
                            </div>
                            
                            {/* Status Icon */}
                            <div className="flex items-center ml-2">
                                <div className={`p-1.5 rounded-full ${statusConfig.bgColor}`}>
                                    <StatusIcon className={`w-4 h-4 ${statusConfig.iconColor} ${
                                        status === 'uploading' ? 'animate-spin' : ''
                                    }`} />
                                </div>
                            </div>
                        </div>
                        
                        {/* Progress Bar */}
                        {(status === 'uploading' || (status === 'uploaded' && progress === 100)) && (
                            <div className="mt-2">
                                <ProgressBar progress={progress} status={status} />
                                
                                {/* Progress Details */}
                                <div className="flex justify-between items-center mt-1">
                                    <span className={`text-xs ${statusConfig.textColor}`}>
                                        {status === 'uploading' ? `${progress}%` : statusConfig.label}
                                    </span>
                                    
                                    {status === 'uploading' && uploadSpeed && (
                                        <span className="text-xs text-gray-500">
                                            {formatSpeed(uploadSpeed)}
                                        </span>
                                    )}
                                </div>
                                
                                {/* Time Remaining */}
                                {status === 'uploading' && estimatedTime && estimatedTime > 0 && (
                                    <p className="text-xs text-gray-500 mt-1">
                                        {formatTimeRemaining(estimatedTime)}
                                    </p>
                                )}
                            </div>
                        )}
                        
                        {/* Error Message */}
                        {status === 'error' && error && (
                            <div className="mt-2 p-2 bg-red-50 border border-red-200 rounded-md">
                                <p className="text-xs text-red-700">{error}</p>
                            </div>
                        )}
                        
                        {/* Success Message */}
                        {status === 'uploaded' && (
                            <div className="mt-2 flex items-center">
                                <CheckCircleIcon className="w-4 h-4 text-green-500 mr-1" />
                                <span className="text-xs text-green-700">Upload completed successfully</span>
                            </div>
                        )}
                    </div>
                </div>
                
                {/* Action Buttons */}
                {(status === 'error' || status === 'cancelled') && (
                    <div className="mt-3 flex space-x-2">
                        {onRetry && (
                            <button
                                onClick={() => onRetry(file)}
                                className="text-xs px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                Retry
                            </button>
                        )}
                        {onRemove && (
                            <button
                                onClick={() => onRemove(file)}
                                className="text-xs px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                            >
                                Remove
                            </button>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
};

const UploadProgress = ({ 
    files = [], 
    uploadProgress = {}, 
    onRetry = null, 
    onRemove = null,
    showOverallProgress = true,
    showPreview = true,
    className = ""
}) => {
    const [overallProgress, setOverallProgress] = useState(0);
    const [overallStatus, setOverallStatus] = useState('pending');
    
    useEffect(() => {
        if (files.length === 0) {
            setOverallProgress(0);
            setOverallStatus('pending');
            return;
        }
        
        const fileProgresses = files.map(file => {
            const progress = uploadProgress[file.id] || 0;
            const status = file.status || 'pending';
            
            if (status === 'uploaded') return 100;
            if (status === 'error' || status === 'cancelled') return 0;
            return progress;
        });
        
        const totalProgress = fileProgresses.reduce((sum, progress) => sum + progress, 0);
        const avgProgress = totalProgress / files.length;
        
        setOverallProgress(Math.round(avgProgress));
        
        // Determine overall status
        const uploadedCount = files.filter(f => f.status === 'uploaded').length;
        const errorCount = files.filter(f => f.status === 'error').length;
        const uploadingCount = files.filter(f => f.status === 'uploading').length;
        
        if (uploadedCount === files.length) {
            setOverallStatus('uploaded');
        } else if (errorCount > 0 && uploadingCount === 0) {
            setOverallStatus('error');
        } else if (uploadingCount > 0) {
            setOverallStatus('uploading');
        } else {
            setOverallStatus('pending');
        }
    }, [files, uploadProgress]);

    if (files.length === 0) {
        return null;
    }

    const statusCounts = files.reduce((acc, file) => {
        const status = file.status || 'pending';
        acc[status] = (acc[status] || 0) + 1;
        return acc;
    }, {});

    return (
        <div className={`space-y-4 ${className}`}>
            {/* Overall Progress */}
            {showOverallProgress && (
                <div className="bg-white rounded-lg border border-gray-200 p-4">
                    <div className="flex items-center justify-between mb-2">
                        <h4 className="text-sm font-medium text-gray-900">
                            Upload Progress ({statusCounts.uploaded || 0}/{files.length} completed)
                        </h4>
                        <span className="text-sm text-gray-600">
                            {overallProgress}%
                        </span>
                    </div>
                    
                    <ProgressBar progress={overallProgress} status={overallStatus} />
                    
                    {/* Status Summary */}
                    <div className="flex items-center justify-between mt-3 text-xs text-gray-500">
                        <div className="flex space-x-4">
                            {statusCounts.uploading > 0 && (
                                <span className="flex items-center">
                                    <div className="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse" />
                                    {statusCounts.uploading} uploading
                                </span>
                            )}
                            {statusCounts.uploaded > 0 && (
                                <span className="flex items-center">
                                    <div className="w-2 h-2 bg-green-500 rounded-full mr-1" />
                                    {statusCounts.uploaded} completed
                                </span>
                            )}
                            {statusCounts.error > 0 && (
                                <span className="flex items-center">
                                    <div className="w-2 h-2 bg-red-500 rounded-full mr-1" />
                                    {statusCounts.error} failed
                                </span>
                            )}
                            {statusCounts.pending > 0 && (
                                <span className="flex items-center">
                                    <div className="w-2 h-2 bg-gray-400 rounded-full mr-1" />
                                    {statusCounts.pending} waiting
                                </span>
                            )}
                        </div>
                    </div>
                </div>
            )}
            
            {/* Individual File Progress */}
            <div className="space-y-2">
                {files.map((file) => (
                    <FileUploadItem
                        key={file.id}
                        file={file}
                        progress={uploadProgress[file.id] || 0}
                        status={file.status || 'pending'}
                        error={file.error}
                        onRetry={onRetry}
                        onRemove={onRemove}
                        showPreview={showPreview}
                    />
                ))}
            </div>
        </div>
    );
};

ProgressBar.propTypes = {
    progress: PropTypes.number.isRequired,
    status: PropTypes.string.isRequired,
    animated: PropTypes.bool
};

FileUploadItem.propTypes = {
    file: PropTypes.object.isRequired,
    progress: PropTypes.number,
    status: PropTypes.string,
    error: PropTypes.string,
    onRetry: PropTypes.func,
    onRemove: PropTypes.func,
    showPreview: PropTypes.bool
};

UploadProgress.propTypes = {
    files: PropTypes.array,
    uploadProgress: PropTypes.object,
    onRetry: PropTypes.func,
    onRemove: PropTypes.func,
    showOverallProgress: PropTypes.bool,
    showPreview: PropTypes.bool,
    className: PropTypes.string
};

export default UploadProgress;