// hooks/useFileUpload.js
import { useState, useCallback, useRef } from 'react';
import { router } from '@inertiajs/react';

const DEFAULT_CONFIG = {
    maxSize: 5 * 1024 * 1024, // 5MB
    maxFiles: 5,
    acceptedTypes: ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'],
    acceptedExtensions: ['.pdf', '.jpg', '.jpeg', '.png'],
    enableDragDrop: true,
    enablePreview: true,
    autoUpload: false
};

export const useFileUpload = (config = {}) => {
    const settings = { ...DEFAULT_CONFIG, ...config };
    
    const [files, setFiles] = useState([]);
    const [uploadQueue, setUploadQueue] = useState([]);
    const [uploading, setUploading] = useState(false);
    const [uploadProgress, setUploadProgress] = useState({});
    const [errors, setErrors] = useState([]);
    const [dragOver, setDragOver] = useState(false);
    
    const fileInputRef = useRef(null);
    const abortControllerRef = useRef(null);

    // File validation
    const validateFile = useCallback((file) => {
        const errors = [];
        
        // Size validation
        if (file.size > settings.maxSize) {
            errors.push({
                type: 'size',
                message: `File "${file.name}" exceeds maximum size of ${formatFileSize(settings.maxSize)}`,
                file: file.name
            });
        }

        // Type validation
        const isValidType = settings.acceptedTypes.some(type => file.type === type);
        const fileExt = '.' + file.name.split('.').pop().toLowerCase();
        const isValidExtension = settings.acceptedExtensions.includes(fileExt);
        
        if (!isValidType && !isValidExtension) {
            errors.push({
                type: 'format',
                message: `File "${file.name}" has unsupported format. Accepted: ${settings.acceptedExtensions.join(', ')}`,
                file: file.name
            });
        }

        // Name validation
        if (!/^[a-zA-Z0-9\-_. ]+$/.test(file.name)) {
            errors.push({
                type: 'name',
                message: `File "${file.name}" contains invalid characters`,
                file: file.name
            });
        }

        return errors;
    }, [settings.maxSize, settings.acceptedTypes, settings.acceptedExtensions]);

    // Handle file selection
    const handleFiles = useCallback((fileList, options = {}) => {
        const newFiles = Array.from(fileList);
        const { replace = false } = options;
        
        // Validate file count
        const currentCount = replace ? 0 : files.length;
        if (currentCount + newFiles.length > settings.maxFiles) {
            setErrors(prev => [...prev, {
                type: 'count',
                message: `Cannot add ${newFiles.length} files. Maximum ${settings.maxFiles} files allowed.`,
                file: null
            }]);
            return false;
        }

        // Validate each file
        const validationErrors = [];
        const validFiles = [];

        newFiles.forEach(file => {
            const fileErrors = validateFile(file);
            if (fileErrors.length > 0) {
                validationErrors.push(...fileErrors);
            } else {
                // Add metadata to file
                const enhancedFile = Object.assign(file, {
                    id: generateFileId(),
                    preview: null,
                    status: 'pending',
                    uploadProgress: 0
                });
                validFiles.push(enhancedFile);
            }
        });

        // Update errors
        if (validationErrors.length > 0) {
            setErrors(prev => [...prev, ...validationErrors]);
        }

        // Update files
        if (validFiles.length > 0) {
            setFiles(prev => replace ? validFiles : [...prev, ...validFiles]);
            
            // Generate previews for images
            validFiles.forEach(file => {
                if (file.type.startsWith('image/')) {
                    generatePreview(file);
                }
            });

            // Auto upload if enabled
            if (settings.autoUpload) {
                uploadFiles(validFiles);
            }
        }

        return validFiles.length > 0;
    }, [files.length, settings.maxFiles, validateFile, settings.autoUpload]);

    // Generate file preview
    const generatePreview = useCallback((file) => {
        if (!settings.enablePreview || !file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            setFiles(prev => prev.map(f => 
                f.id === file.id ? { ...f, preview: e.target.result } : f
            ));
        };
        reader.readAsDataURL(file);
    }, [settings.enablePreview]);

    // Upload files
    const uploadFiles = useCallback(async (filesToUpload = files, uploadUrl, options = {}) => {
        if (!uploadUrl) {
            console.error('Upload URL is required');
            return false;
        }

        setUploading(true);
        setErrors([]);
        
        // Create abort controller
        abortControllerRef.current = new AbortController();
        
        const uploadPromises = filesToUpload.map(file => uploadSingleFile(file, uploadUrl, options));
        
        try {
            const results = await Promise.allSettled(uploadPromises);
            const successful = results.filter(r => r.status === 'fulfilled').length;
            const failed = results.filter(r => r.status === 'rejected');
            
            if (failed.length > 0) {
                const uploadErrors = failed.map((result, index) => ({
                    type: 'upload',
                    message: `Failed to upload "${filesToUpload[index].name}": ${result.reason?.message || 'Unknown error'}`,
                    file: filesToUpload[index].name
                }));
                setErrors(prev => [...prev, ...uploadErrors]);
            }

            return { successful, failed: failed.length };
        } catch (error) {
            console.error('Upload error:', error);
            setErrors(prev => [...prev, {
                type: 'upload',
                message: 'Upload failed: ' + error.message,
                file: null
            }]);
            return { successful: 0, failed: filesToUpload.length };
        } finally {
            setUploading(false);
            setUploadProgress({});
        }
    }, [files]);

    // Upload single file with progress
    const uploadSingleFile = useCallback(async (file, uploadUrl, options = {}) => {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', file);
            
            // Add additional form data
            if (options.additionalData) {
                Object.keys(options.additionalData).forEach(key => {
                    formData.append(key, options.additionalData[key]);
                });
            }

            const xhr = new XMLHttpRequest();
            
            // Handle progress
            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) {
                    const progress = Math.round((event.loaded / event.total) * 100);
                    setUploadProgress(prev => ({
                        ...prev,
                        [file.id]: progress
                    }));
                    
                    setFiles(prev => prev.map(f => 
                        f.id === file.id ? { ...f, uploadProgress: progress } : f
                    ));
                }
            };

            // Handle completion
            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        setFiles(prev => prev.map(f => 
                            f.id === file.id ? { ...f, status: 'uploaded', serverResponse: response } : f
                        ));
                        resolve(response);
                    } catch (e) {
                        reject(new Error('Invalid server response'));
                    }
                } else {
                    setFiles(prev => prev.map(f => 
                        f.id === file.id ? { ...f, status: 'error' } : f
                    ));
                    reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                }
            };

            // Handle errors
            xhr.onerror = () => {
                setFiles(prev => prev.map(f => 
                    f.id === file.id ? { ...f, status: 'error' } : f
                ));
                reject(new Error('Network error'));
            };

            // Handle abort
            xhr.onabort = () => {
                setFiles(prev => prev.map(f => 
                    f.id === file.id ? { ...f, status: 'cancelled' } : f
                ));
                reject(new Error('Upload cancelled'));
            };

            // Setup abort signal
            if (abortControllerRef.current) {
                abortControllerRef.current.signal.addEventListener('abort', () => {
                    xhr.abort();
                });
            }

            // Start upload
            setFiles(prev => prev.map(f => 
                f.id === file.id ? { ...f, status: 'uploading' } : f
            ));
            
            xhr.open('POST', uploadUrl);
            
            // Add CSRF token for Laravel
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (token) {
                xhr.setRequestHeader('X-CSRF-TOKEN', token);
            }
            
            xhr.send(formData);
        });
    }, []);

    // Remove file
    const removeFile = useCallback((fileId) => {
        setFiles(prev => prev.filter(f => f.id !== fileId));
        setUploadProgress(prev => {
            const newProgress = { ...prev };
            delete newProgress[fileId];
            return newProgress;
        });
        setErrors(prev => prev.filter(error => !error.file || 
            !files.find(f => f.id === fileId)?.name?.includes(error.file)
        ));
    }, [files]);

    // Clear all files
    const clearFiles = useCallback(() => {
        setFiles([]);
        setUploadProgress({});
        setErrors([]);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }, []);

    // Cancel upload
    const cancelUpload = useCallback(() => {
        if (abortControllerRef.current) {
            abortControllerRef.current.abort();
        }
        setUploading(false);
        setUploadProgress({});
    }, []);

    // Drag and drop handlers
    const dragHandlers = settings.enableDragDrop ? {
        onDragEnter: (e) => {
            e.preventDefault();
            e.stopPropagation();
            setDragOver(true);
        },
        onDragOver: (e) => {
            e.preventDefault();
            e.stopPropagation();
            setDragOver(true);
        },
        onDragLeave: (e) => {
            e.preventDefault();
            e.stopPropagation();
            setDragOver(false);
        },
        onDrop: (e) => {
            e.preventDefault();
            e.stopPropagation();
            setDragOver(false);
            
            const droppedFiles = e.dataTransfer.files;
            if (droppedFiles.length > 0) {
                handleFiles(droppedFiles);
            }
        }
    } : {};

    // Clear errors
    const clearErrors = useCallback(() => {
        setErrors([]);
    }, []);

    // Get files by status
    const getFilesByStatus = useCallback((status) => {
        return files.filter(file => file.status === status);
    }, [files]);

    return {
        // State
        files,
        uploading,
        uploadProgress,
        errors,
        dragOver,
        
        // Actions
        handleFiles,
        uploadFiles,
        removeFile,
        clearFiles,
        cancelUpload,
        clearErrors,
        
        // Utilities
        getFilesByStatus,
        validateFile,
        
        // Drag and drop
        dragHandlers,
        
        // Refs
        fileInputRef,
        
        // Config
        settings
    };
};

// Utility functions
const generateFileId = () => {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

export default useFileUpload;