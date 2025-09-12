// components/FileUpload/ResponsiveFileUpload.jsx
import { useState, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import {
    CloudArrowUpIcon,
    DocumentPlusIcon,
    XMarkIcon,
    EyeIcon,
    ArrowUpOnSquareIcon,
    CameraIcon,
    PhotoIcon,
    DevicePhoneMobileIcon
} from '@heroicons/react/24/outline';
import { useFileUpload } from '../../hooks/useFileUpload';
import { validateFiles, formatFileSize, getFileDisplay, canPreviewFile } from '../../utils/fileValidation';
import UploadProgress from './UploadProgress';
import FilePreview from './FilePreview';

const MobileFileCapture = ({ onFileCapture, accept, disabled }) => {
    const fileInputRef = useRef(null);
    const cameraInputRef = useRef(null);

    const handleCameraCapture = () => {
        cameraInputRef.current?.click();
    };

    const handleFileSelect = () => {
        fileInputRef.current?.click();
    };

    const handleFileChange = (e, isCamera = false) => {
        const files = Array.from(e.target.files);
        if (files.length > 0) {
            onFileCapture(files, isCamera);
        }
        // Reset input
        e.target.value = '';
    };

    return (
        <div className="grid grid-cols-2 gap-3">
            {/* Camera Capture */}
            <button
                onClick={handleCameraCapture}
                disabled={disabled}
                className="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                <input
                    ref={cameraInputRef}
                    type="file"
                    accept="image/*"
                    capture="environment"
                    onChange={(e) => handleFileChange(e, true)}
                    className="hidden"
                />
                <CameraIcon className="w-8 h-8 text-gray-400 mb-2" />
                <span className="text-sm font-medium text-gray-900">Camera</span>
                <span className="text-xs text-gray-500">Take Photo</span>
            </button>

            {/* File Browser */}
            <button
                onClick={handleFileSelect}
                disabled={disabled}
                className="flex flex-col items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                <input
                    ref={fileInputRef}
                    type="file"
                    accept={accept}
                    multiple
                    onChange={(e) => handleFileChange(e, false)}
                    className="hidden"
                />
                <PhotoIcon className="w-8 h-8 text-gray-400 mb-2" />
                <span className="text-sm font-medium text-gray-900">Gallery</span>
                <span className="text-xs text-gray-500">Choose Files</span>
            </button>
        </div>
    );
};

const ResponsiveDropZone = ({ 
    onFiles, 
    dragHandlers, 
    dragOver, 
    accept, 
    maxSize, 
    maxFiles, 
    disabled,
    isMobile 
}) => {
    const fileInputRef = useRef(null);

    const handleClick = () => {
        if (!isMobile) {
            fileInputRef.current?.click();
        }
    };

    const handleFileCapture = (files, isCamera) => {
        onFiles(files);
    };

    return (
        <div
            {...(!isMobile ? dragHandlers : {})}
            onClick={!isMobile ? handleClick : undefined}
            className={`relative border-2 border-dashed rounded-lg transition-all duration-200 ${
                dragOver 
                    ? 'border-blue-400 bg-blue-50' 
                    : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50'
            } ${
                disabled ? 'opacity-50 cursor-not-allowed' : !isMobile ? 'cursor-pointer' : ''
            } ${
                isMobile ? 'p-4' : 'p-6'
            }`}
        >
            {!isMobile && (
                <input
                    ref={fileInputRef}
                    type="file"
                    multiple
                    accept={accept}
                    onChange={(e) => onFiles(Array.from(e.target.files))}
                    className="hidden"
                    disabled={disabled}
                />
            )}

            <div className="text-center">
                {isMobile ? (
                    <div className="space-y-4">
                        <div className="flex items-center justify-center">
                            <DevicePhoneMobileIcon className="w-12 h-12 text-gray-400" />
                        </div>
                        <div>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">
                                Upload Files
                            </h3>
                            <p className="text-sm text-gray-600 mb-4">
                                Take photos or choose files from your device
                            </p>
                        </div>
                        <MobileFileCapture
                            onFileCapture={handleFileCapture}
                            accept={accept}
                            disabled={disabled}
                        />
                        <div className="text-xs text-gray-500">
                            Max {maxFiles} files • {formatFileSize(maxSize)} per file
                        </div>
                    </div>
                ) : (
                    <div className="space-y-3">
                        <CloudArrowUpIcon className={`mx-auto h-16 w-16 ${
                            dragOver ? 'text-blue-400' : 'text-gray-400'
                        } transition-colors`} />
                        <div>
                            <h3 className="text-lg font-medium text-gray-900 mb-1">
                                Upload Files
                            </h3>
                            <p className="text-sm text-gray-600 mb-2">
                                Drag and drop files here, or click to browse
                            </p>
                            <div className="text-xs text-gray-500">
                                Max {maxFiles} files • {formatFileSize(maxSize)} per file
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

const FileList = ({ 
    files, 
    onRemove, 
    onPreview, 
    showPreview = true, 
    isMobile = false,
    uploadProgress = {}
}) => {
    if (files.length === 0) return null;

    return (
        <div className="space-y-3">
            <h4 className="text-sm font-medium text-gray-900">
                Selected Files ({files.length})
            </h4>
            
            <div className={`grid gap-3 ${
                isMobile ? 'grid-cols-1' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'
            }`}>
                {files.map((file) => {
                    const fileDisplay = getFileDisplay(file);
                    const progress = uploadProgress[file.id] || 0;
                    const status = file.status || 'pending';
                    
                    return (
                        <div
                            key={file.id}
                            className="relative group bg-white border border-gray-200 rounded-lg hover:shadow-sm transition-shadow"
                        >
                            <div className="p-3">
                                <div className="flex items-start space-x-3">
                                    {/* File Preview/Icon */}
                                    <div className="flex-shrink-0">
                                        {showPreview && file.preview ? (
                                            <div className="relative">
                                                <img 
                                                    src={file.preview} 
                                                    alt="Preview" 
                                                    className="w-12 h-12 object-cover rounded-lg border cursor-pointer"
                                                    onClick={() => onPreview && onPreview(file)}
                                                />
                                                {canPreviewFile(file) && (
                                                    <div className="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-20 rounded-lg transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                        <EyeIcon className="w-4 h-4 text-white" />
                                                    </div>
                                                )}
                                            </div>
                                        ) : (
                                            <div 
                                                className={`w-12 h-12 rounded-lg flex items-center justify-center bg-${fileDisplay.color}-100 ${
                                                    canPreviewFile(file) && onPreview ? 'cursor-pointer hover:bg-opacity-80' : ''
                                                }`}
                                                onClick={() => canPreviewFile(file) && onPreview && onPreview(file)}
                                            >
                                                <span className="text-xl">{fileDisplay.icon}</span>
                                            </div>
                                        )}
                                    </div>
                                    
                                    {/* File Info */}
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-gray-900 truncate">
                                                    {file.name}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    {formatFileSize(file.size)}
                                                </p>
                                                {status === 'uploading' && (
                                                    <div className="mt-1">
                                                        <div className="w-full bg-gray-200 rounded-full h-1.5">
                                                            <div 
                                                                className="bg-blue-500 h-1.5 rounded-full transition-all duration-300"
                                                                style={{ width: `${progress}%` }}
                                                            />
                                                        </div>
                                                        <span className="text-xs text-blue-600 mt-0.5">
                                                            {progress}%
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                            
                                            {/* Remove Button */}
                                            {onRemove && status !== 'uploading' && (
                                                <button
                                                    onClick={() => onRemove(file.id)}
                                                    className="ml-2 p-1 text-gray-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-red-500 rounded"
                                                    title="Remove file"
                                                >
                                                    <XMarkIcon className="w-4 h-4" />
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Status Indicator */}
                            {status !== 'pending' && (
                                <div className={`absolute top-2 right-2 w-3 h-3 rounded-full ${
                                    status === 'uploaded' ? 'bg-green-500' :
                                    status === 'uploading' ? 'bg-blue-500 animate-pulse' :
                                    status === 'error' ? 'bg-red-500' : 'bg-gray-400'
                                }`} />
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

const ResponsiveFileUpload = ({
    onFileSelect,
    onUploadComplete,
    accept = ".pdf,.jpg,.jpeg,.png",
    maxFiles = 5,
    maxSize = 5 * 1024 * 1024,
    certificateType = 'default',
    existingFiles = [],
    enablePreview = true,
    enableBulkUpload = false,
    uploadUrl,
    additionalData = {},
    disabled = false,
    className = ""
}) => {
    const [isMobile, setIsMobile] = useState(false);
    const [previewFile, setPreviewFile] = useState(null);
    const [previewFiles, setPreviewFiles] = useState([]);
    const [currentPreviewIndex, setCurrentPreviewIndex] = useState(0);

    const {
        files,
        uploading,
        uploadProgress,
        errors,
        dragOver,
        handleFiles,
        uploadFiles,
        removeFile,
        clearFiles,
        clearErrors,
        dragHandlers,
        settings
    } = useFileUpload({
        maxSize,
        maxFiles,
        acceptedTypes: accept.split(',').map(type => {
            const mimeTypes = {
                '.pdf': 'application/pdf',
                '.jpg': 'image/jpeg',
                '.jpeg': 'image/jpeg',
                '.png': 'image/png',
                '.gif': 'image/gif'
            };
            return mimeTypes[type.trim()] || type.trim();
        }).filter(Boolean),
        autoUpload: false
    });

    // Detect mobile device
    useEffect(() => {
        const checkIsMobile = () => {
            const userAgent = navigator.userAgent;
            const mobileRegex = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;
            const isTouchDevice = 'ontouchstart' in window;
            const hasSmallScreen = window.innerWidth <= 768;
            
            setIsMobile(mobileRegex.test(userAgent) || (isTouchDevice && hasSmallScreen));
        };

        checkIsMobile();
        window.addEventListener('resize', checkIsMobile);
        return () => window.removeEventListener('resize', checkIsMobile);
    }, []);

    // Update parent component when files change
    useEffect(() => {
        if (onFileSelect) {
            onFileSelect(files);
        }
    }, [files, onFileSelect]);

    const handleFilesSelected = (selectedFiles) => {
        const validation = validateFiles(selectedFiles, certificateType, [...existingFiles, ...files]);
        
        if (validation.validFiles.length > 0) {
            handleFiles(validation.validFiles);
        }
    };

    const handleUpload = async () => {
        if (!uploadUrl || files.length === 0) return;

        try {
            const result = await uploadFiles(files, uploadUrl, { additionalData });
            
            if (onUploadComplete) {
                onUploadComplete(result);
            }
        } catch (error) {
            console.error('Upload error:', error);
        }
    };

    const handlePreview = (file) => {
        if (!enablePreview || !canPreviewFile(file)) return;
        
        const previewableFiles = files.filter(f => canPreviewFile(f));
        const currentIndex = previewableFiles.findIndex(f => f.id === file.id);
        
        setPreviewFiles(previewableFiles);
        setCurrentPreviewIndex(Math.max(0, currentIndex));
        setPreviewFile(file);
    };

    const handlePreviewNavigation = (newIndex) => {
        if (newIndex >= 0 && newIndex < previewFiles.length) {
            setCurrentPreviewIndex(newIndex);
            setPreviewFile(previewFiles[newIndex]);
        }
    };

    const handleClosePreview = () => {
        setPreviewFile(null);
        setPreviewFiles([]);
        setCurrentPreviewIndex(0);
    };

    return (
        <div className={`w-full ${className}`}>
            {/* Upload Area */}
            <ResponsiveDropZone
                onFiles={handleFilesSelected}
                dragHandlers={dragHandlers}
                dragOver={dragOver}
                accept={accept}
                maxSize={maxSize}
                maxFiles={maxFiles}
                disabled={disabled || uploading}
                isMobile={isMobile}
            />

            {/* Error Messages */}
            {errors.length > 0 && (
                <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div className="flex items-start">
                        <XMarkIcon className="w-5 h-5 text-red-400 mt-0.5 mr-2 flex-shrink-0" />
                        <div className="flex-1">
                            <h4 className="text-sm font-medium text-red-900">Upload Issues</h4>
                            <ul className="mt-2 text-sm text-red-700 space-y-1">
                                {errors.map((error, index) => (
                                    <li key={index}>{error.message}</li>
                                ))}
                            </ul>
                            <button
                                onClick={clearErrors}
                                className="mt-2 text-xs text-red-600 hover:text-red-800 underline"
                            >
                                Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* File List */}
            <div className="mt-4">
                <FileList
                    files={files}
                    onRemove={!uploading ? removeFile : null}
                    onPreview={handlePreview}
                    showPreview={enablePreview}
                    isMobile={isMobile}
                    uploadProgress={uploadProgress}
                />
            </div>

            {/* Upload Progress */}
            {uploading && (
                <div className="mt-4">
                    <UploadProgress
                        files={files}
                        uploadProgress={uploadProgress}
                        showOverallProgress={!isMobile}
                    />
                </div>
            )}

            {/* Action Buttons */}
            {files.length > 0 && uploadUrl && (
                <div className={`mt-4 flex ${
                    isMobile ? 'flex-col space-y-2' : 'justify-between items-center'
                }`}>
                    <div className={`text-sm text-gray-600 ${isMobile ? 'text-center' : ''}`}>
                        {files.length} file{files.length !== 1 ? 's' : ''} selected
                    </div>
                    
                    <div className={`flex space-x-3 ${isMobile ? 'justify-center' : ''}`}>
                        <button
                            onClick={clearFiles}
                            disabled={uploading}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Clear All
                        </button>
                        
                        <button
                            onClick={handleUpload}
                            disabled={uploading || files.length === 0}
                            className="flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ArrowUpOnSquareIcon className="w-4 h-4 mr-2" />
                            {uploading ? 'Uploading...' : 'Upload Files'}
                        </button>
                    </div>
                </div>
            )}

            {/* File Preview Modal */}
            {previewFile && (
                <FilePreview
                    file={previewFile}
                    files={previewFiles}
                    currentIndex={currentPreviewIndex}
                    onClose={handleClosePreview}
                    onNavigate={previewFiles.length > 1 ? handlePreviewNavigation : null}
                />
            )}
        </div>
    );
};

MobileFileCapture.propTypes = {
    onFileCapture: PropTypes.func.isRequired,
    accept: PropTypes.string.isRequired,
    disabled: PropTypes.bool
};

ResponsiveDropZone.propTypes = {
    onFiles: PropTypes.func.isRequired,
    dragHandlers: PropTypes.object,
    dragOver: PropTypes.bool,
    accept: PropTypes.string.isRequired,
    maxSize: PropTypes.number.isRequired,
    maxFiles: PropTypes.number.isRequired,
    disabled: PropTypes.bool,
    isMobile: PropTypes.bool
};

FileList.propTypes = {
    files: PropTypes.array.isRequired,
    onRemove: PropTypes.func,
    onPreview: PropTypes.func,
    showPreview: PropTypes.bool,
    isMobile: PropTypes.bool,
    uploadProgress: PropTypes.object
};

ResponsiveFileUpload.propTypes = {
    onFileSelect: PropTypes.func,
    onUploadComplete: PropTypes.func,
    accept: PropTypes.string,
    maxFiles: PropTypes.number,
    maxSize: PropTypes.number,
    certificateType: PropTypes.string,
    existingFiles: PropTypes.array,
    enablePreview: PropTypes.bool,
    enableBulkUpload: PropTypes.bool,
    uploadUrl: PropTypes.string,
    additionalData: PropTypes.object,
    disabled: PropTypes.bool,
    className: PropTypes.string
};

export default ResponsiveFileUpload;