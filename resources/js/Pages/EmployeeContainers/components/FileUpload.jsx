import { useState, useRef } from 'react';
import PropTypes from 'prop-types';
import {
    CloudArrowUpIcon,
    DocumentIcon,
    XMarkIcon,
    ExclamationTriangleIcon
} from '@heroicons/react/24/outline';

export default function FileUpload({ 
    onFileSelect, 
    accept = ".pdf,.jpg,.jpeg,.png", 
    maxFiles = 5, 
    maxSize = 5 * 1024 * 1024, // 5MB
    label = "Upload Files",
    description = "Drag and drop files here, or click to browse",
    className = ""
}) {
    const [dragOver, setDragOver] = useState(false);
    const [selectedFiles, setSelectedFiles] = useState([]);
    const [errors, setErrors] = useState([]);
    const fileInputRef = useRef(null);

    const validateFile = (file) => {
        const errors = [];
        
        // Check file size
        if (file.size > maxSize) {
            errors.push(`File "${file.name}" is too large. Maximum size is ${formatFileSize(maxSize)}.`);
        }

        // Check file type
        const acceptedTypes = accept.split(',').map(type => type.trim());
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        const mimeTypeValid = acceptedTypes.some(type => {
            if (type.startsWith('.')) {
                return type === fileExtension;
            }
            return file.type.includes(type.split('/')[0]);
        });

        if (!mimeTypeValid) {
            errors.push(`File "${file.name}" has an invalid format. Accepted formats: ${accept}`);
        }

        return errors;
    };

    const handleFileSelect = (files) => {
        const fileArray = Array.from(files);
        const allFiles = [...selectedFiles, ...fileArray];
        
        // Check max files limit
        if (allFiles.length > maxFiles) {
            setErrors([`Maximum ${maxFiles} files allowed. Please remove some files first.`]);
            return;
        }

        // Validate each file
        const validationErrors = [];
        const validFiles = [];

        fileArray.forEach(file => {
            const fileErrors = validateFile(file);
            if (fileErrors.length > 0) {
                validationErrors.push(...fileErrors);
            } else {
                validFiles.push(file);
            }
        });

        setErrors(validationErrors);
        
        if (validFiles.length > 0) {
            const newSelectedFiles = [...selectedFiles, ...validFiles];
            setSelectedFiles(newSelectedFiles);
            onFileSelect(newSelectedFiles);
        }
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        setDragOver(true);
    };

    const handleDragLeave = (e) => {
        e.preventDefault();
        setDragOver(false);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setDragOver(false);
        
        const files = e.dataTransfer.files;
        handleFileSelect(files);
    };

    const handleFileInputChange = (e) => {
        const files = e.target.files;
        handleFileSelect(files);
    };

    const handleRemoveFile = (index) => {
        const newFiles = selectedFiles.filter((_, i) => i !== index);
        setSelectedFiles(newFiles);
        onFileSelect(newFiles);
        
        // Clear errors if removing files resolves them
        if (newFiles.length <= maxFiles) {
            setErrors(errors.filter(error => !error.includes('Maximum')));
        }
    };

    const handleBrowseClick = () => {
        fileInputRef.current?.click();
    };

    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const getFileIcon = (fileName) => {
        const extension = fileName.split('.').pop().toLowerCase();
        if (['pdf'].includes(extension)) {
            return '📄';
        } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
            return '🖼️';
        }
        return '📎';
    };

    return (
        <div className={`w-full ${className}`}>
            {/* Upload Area */}
            <div
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
                onClick={handleBrowseClick}
                className={`
                    relative border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-all duration-200
                    ${dragOver 
                        ? 'border-blue-400 bg-blue-50' 
                        : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50'
                    }
                    ${errors.length > 0 ? 'border-red-300 bg-red-50' : ''}
                `}
            >
                <input
                    ref={fileInputRef}
                    type="file"
                    multiple
                    accept={accept}
                    onChange={handleFileInputChange}
                    className="hidden"
                />

                <div className="space-y-2">
                    <CloudArrowUpIcon className={`mx-auto h-12 w-12 ${dragOver ? 'text-blue-400' : 'text-gray-400'}`} />
                    <div className="text-sm font-medium text-gray-900">
                        {label}
                    </div>
                    <div className="text-xs text-gray-600">
                        {description}
                    </div>
                    <div className="text-xs text-gray-500">
                        Max {maxFiles} files • {formatFileSize(maxSize)} per file • {accept.replace(/\./g, '').toUpperCase()}
                    </div>
                </div>
            </div>

            {/* Error Messages */}
            {errors.length > 0 && (
                <div className="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
                    <div className="flex">
                        <ExclamationTriangleIcon className="h-5 w-5 text-red-400 mt-0.5 mr-2 flex-shrink-0" />
                        <div className="text-sm text-red-700">
                            <ul className="space-y-1">
                                {errors.map((error, index) => (
                                    <li key={index}>{error}</li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            )}

            {/* Selected Files List */}
            {selectedFiles.length > 0 && (
                <div className="mt-4">
                    <h4 className="text-sm font-medium text-gray-900 mb-2">
                        Selected Files ({selectedFiles.length}/{maxFiles})
                    </h4>
                    <div className="space-y-2">
                        {selectedFiles.map((file, index) => (
                            <div
                                key={`${file.name}-${index}`}
                                className="flex items-center justify-between p-3 bg-gray-50 rounded-md border border-gray-200"
                            >
                                <div className="flex items-center space-x-3">
                                    <span className="text-lg">
                                        {getFileIcon(file.name)}
                                    </span>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-900 truncate">
                                            {file.name}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {formatFileSize(file.size)}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        handleRemoveFile(index);
                                    }}
                                    className="ml-2 p-1 rounded-full hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    title="Remove file"
                                >
                                    <XMarkIcon className="h-4 w-4 text-gray-500" />
                                </button>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

FileUpload.propTypes = {
    onFileSelect: PropTypes.func.isRequired,
    accept: PropTypes.string,
    maxFiles: PropTypes.number,
    maxSize: PropTypes.number,
    label: PropTypes.string,
    description: PropTypes.string,
    className: PropTypes.string
};