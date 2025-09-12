// utils/fileValidation.js

// File type definitions
export const FILE_TYPES = {
    PDF: {
        extensions: ['.pdf'],
        mimeTypes: ['application/pdf'],
        maxSize: 10 * 1024 * 1024, // 10MB
        icon: '📄',
        color: 'red'
    },
    IMAGE: {
        extensions: ['.jpg', '.jpeg', '.png', '.gif', '.webp'],
        mimeTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        maxSize: 5 * 1024 * 1024, // 5MB
        icon: '🖼️',
        color: 'blue'
    },
    DOCUMENT: {
        extensions: ['.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx'],
        mimeTypes: [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ],
        maxSize: 20 * 1024 * 1024, // 20MB
        icon: '📝',
        color: 'green'
    }
};

// Certificate type specific validation rules
export const CERTIFICATE_VALIDATION_RULES = {
    'background-check': {
        allowedTypes: [FILE_TYPES.PDF, FILE_TYPES.IMAGE],
        maxFiles: 10,
        required: false,
        description: 'Background check documents (PDF or images)'
    },
    'medical': {
        allowedTypes: [FILE_TYPES.PDF, FILE_TYPES.IMAGE],
        maxFiles: 3,
        required: true,
        description: 'Medical certificates (PDF or high-quality images)'
    },
    'training': {
        allowedTypes: [FILE_TYPES.PDF],
        maxFiles: 5,
        required: true,
        description: 'Training certificates (PDF only)'
    },
    'license': {
        allowedTypes: [FILE_TYPES.PDF, FILE_TYPES.IMAGE],
        maxFiles: 2,
        required: true,
        description: 'Professional licenses (PDF or clear images)'
    },
    'default': {
        allowedTypes: [FILE_TYPES.PDF, FILE_TYPES.IMAGE],
        maxFiles: 5,
        required: false,
        description: 'Certificate documents'
    }
};

// Error types and messages
export const ERROR_TYPES = {
    FILE_SIZE: 'FILE_SIZE',
    FILE_TYPE: 'FILE_TYPE',
    FILE_NAME: 'FILE_NAME',
    FILE_COUNT: 'FILE_COUNT',
    FILE_CORRUPTED: 'FILE_CORRUPTED',
    UPLOAD_FAILED: 'UPLOAD_FAILED',
    NETWORK_ERROR: 'NETWORK_ERROR',
    SERVER_ERROR: 'SERVER_ERROR'
};

export const ERROR_MESSAGES = {
    [ERROR_TYPES.FILE_SIZE]: (fileName, maxSize, actualSize) => 
        `"${fileName}" is too large (${formatFileSize(actualSize)}). Maximum size allowed is ${formatFileSize(maxSize)}.`,
    
    [ERROR_TYPES.FILE_TYPE]: (fileName, allowedTypes) => 
        `"${fileName}" has an unsupported file format. Allowed formats: ${allowedTypes.join(', ')}.`,
    
    [ERROR_TYPES.FILE_NAME]: (fileName) => 
        `"${fileName}" contains invalid characters. Use only letters, numbers, hyphens, underscores, and spaces.`,
    
    [ERROR_TYPES.FILE_COUNT]: (currentCount, maxCount) => 
        `Too many files selected. You can upload up to ${maxCount} files (currently have ${currentCount}).`,
    
    [ERROR_TYPES.FILE_CORRUPTED]: (fileName) => 
        `"${fileName}" appears to be corrupted or unreadable.`,
    
    [ERROR_TYPES.UPLOAD_FAILED]: (fileName, reason) => 
        `Failed to upload "${fileName}". ${reason || 'Please try again.'}`,
    
    [ERROR_TYPES.NETWORK_ERROR]: () => 
        'Network error occurred. Please check your connection and try again.',
    
    [ERROR_TYPES.SERVER_ERROR]: (code) => 
        `Server error (${code}). Please contact support if this continues.`
};

// Main validation function
export const validateFile = (file, certificateType = 'default', existingFiles = []) => {
    const errors = [];
    const rules = CERTIFICATE_VALIDATION_RULES[certificateType] || CERTIFICATE_VALIDATION_RULES.default;
    
    // Get allowed file types for this certificate type
    const allowedTypes = rules.allowedTypes.flatMap(type => type.extensions);
    const allowedMimeTypes = rules.allowedTypes.flatMap(type => type.mimeTypes);
    
    // 1. File name validation
    if (!isValidFileName(file.name)) {
        errors.push({
            type: ERROR_TYPES.FILE_NAME,
            message: ERROR_MESSAGES[ERROR_TYPES.FILE_NAME](file.name),
            severity: 'error'
        });
    }
    
    // 2. File type validation
    const fileExt = '.' + file.name.split('.').pop().toLowerCase();
    const isValidExtension = allowedTypes.includes(fileExt);
    const isValidMimeType = allowedMimeTypes.includes(file.type);
    
    if (!isValidExtension && !isValidMimeType) {
        errors.push({
            type: ERROR_TYPES.FILE_TYPE,
            message: ERROR_MESSAGES[ERROR_TYPES.FILE_TYPE](file.name, allowedTypes),
            severity: 'error'
        });
    }
    
    // 3. File size validation
    const fileType = getFileType(file);
    if (fileType && file.size > fileType.maxSize) {
        errors.push({
            type: ERROR_TYPES.FILE_SIZE,
            message: ERROR_MESSAGES[ERROR_TYPES.FILE_SIZE](file.name, fileType.maxSize, file.size),
            severity: 'error'
        });
    }
    
    // 4. File count validation
    if (existingFiles.length >= rules.maxFiles) {
        errors.push({
            type: ERROR_TYPES.FILE_COUNT,
            message: ERROR_MESSAGES[ERROR_TYPES.FILE_COUNT](existingFiles.length, rules.maxFiles),
            severity: 'warning'
        });
    }
    
    // 5. Duplicate file name check
    const isDuplicate = existingFiles.some(existingFile => 
        existingFile.name === file.name || 
        (existingFile.original_name && existingFile.original_name === file.name)
    );
    
    if (isDuplicate) {
        errors.push({
            type: ERROR_TYPES.FILE_NAME,
            message: `A file named "${file.name}" already exists. Please rename the file or use the replace option.`,
            severity: 'warning'
        });
    }
    
    return errors;
};

// Batch validation for multiple files
export const validateFiles = (files, certificateType = 'default', existingFiles = []) => {
    const allErrors = [];
    const validFiles = [];
    const rules = CERTIFICATE_VALIDATION_RULES[certificateType] || CERTIFICATE_VALIDATION_RULES.default;
    
    // Check total file count first
    const totalCount = existingFiles.length + files.length;
    if (totalCount > rules.maxFiles) {
        allErrors.push({
            type: ERROR_TYPES.FILE_COUNT,
            message: ERROR_MESSAGES[ERROR_TYPES.FILE_COUNT](totalCount, rules.maxFiles),
            severity: 'error',
            global: true
        });
        return { errors: allErrors, validFiles: [] };
    }
    
    files.forEach((file, index) => {
        const fileErrors = validateFile(file, certificateType, [...existingFiles, ...validFiles]);
        
        if (fileErrors.some(error => error.severity === 'error')) {
            allErrors.push(...fileErrors.map(error => ({ ...error, fileIndex: index, fileName: file.name })));
        } else {
            validFiles.push(file);
            // Add warnings to errors but still include file as valid
            allErrors.push(...fileErrors.map(error => ({ ...error, fileIndex: index, fileName: file.name })));
        }
    });
    
    return { errors: allErrors, validFiles };
};

// File type detection
export const getFileType = (file) => {
    const extension = '.' + file.name.split('.').pop().toLowerCase();
    const mimeType = file.type;
    
    for (const [typeName, typeConfig] of Object.entries(FILE_TYPES)) {
        if (typeConfig.extensions.includes(extension) || typeConfig.mimeTypes.includes(mimeType)) {
            return { ...typeConfig, name: typeName };
        }
    }
    
    return null;
};

// File name validation
export const isValidFileName = (fileName) => {
    // Allow letters, numbers, spaces, hyphens, underscores, and dots
    // But not consecutive dots or dots at start/end
    const invalidChars = /[<>:"/\\|?*]/;
    const consecutiveDots = /\.{2,}/;
    const dotsAtEnds = /^\.|\.$/;
    
    if (invalidChars.test(fileName) || consecutiveDots.test(fileName) || dotsAtEnds.test(fileName)) {
        return false;
    }
    
    // Check length
    if (fileName.length > 255 || fileName.length < 1) {
        return false;
    }
    
    // Check for reserved names (Windows)
    const reservedNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
    const nameWithoutExt = fileName.split('.')[0].toUpperCase();
    
    if (reservedNames.includes(nameWithoutExt)) {
        return false;
    }
    
    return true;
};

// Sanitize file name
export const sanitizeFileName = (fileName) => {
    // Replace invalid characters with underscores
    let sanitized = fileName.replace(/[<>:"/\\|?*]/g, '_');
    
    // Remove consecutive dots
    sanitized = sanitized.replace(/\.{2,}/g, '.');
    
    // Remove dots at start and end
    sanitized = sanitized.replace(/^\.|\.$/g, '');
    
    // Limit length
    if (sanitized.length > 255) {
        const extension = sanitized.split('.').pop();
        const nameWithoutExt = sanitized.substring(0, sanitized.lastIndexOf('.'));
        const maxNameLength = 255 - extension.length - 1; // -1 for the dot
        sanitized = nameWithoutExt.substring(0, maxNameLength) + '.' + extension;
    }
    
    // Ensure it's not empty
    if (sanitized.length === 0) {
        sanitized = 'file';
    }
    
    return sanitized;
};

// Format file size
export const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Get file icon and color
export const getFileDisplay = (file) => {
    const fileType = getFileType(file);
    if (fileType) {
        return {
            icon: fileType.icon,
            color: fileType.color,
            type: fileType.name
        };
    }
    
    return {
        icon: '📎',
        color: 'gray',
        type: 'UNKNOWN'
    };
};

// Check if file can be previewed
export const canPreviewFile = (file) => {
    const fileType = getFileType(file);
    if (!fileType) return false;
    
    return fileType.name === 'IMAGE' || fileType.name === 'PDF';
};

// Generate file thumbnail (for images)
export const generateThumbnail = (file, maxWidth = 150, maxHeight = 150, quality = 0.7) => {
    return new Promise((resolve, reject) => {
        if (!file.type.startsWith('image/')) {
            reject(new Error('File is not an image'));
            return;
        }
        
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        
        img.onload = () => {
            // Calculate new dimensions
            let { width, height } = img;
            
            if (width > height) {
                if (width > maxWidth) {
                    height = (height * maxWidth) / width;
                    width = maxWidth;
                }
            } else {
                if (height > maxHeight) {
                    width = (width * maxHeight) / height;
                    height = maxHeight;
                }
            }
            
            canvas.width = width;
            canvas.height = height;
            
            // Draw and convert to blob
            ctx.drawImage(img, 0, 0, width, height);
            canvas.toBlob(
                (blob) => {
                    if (blob) {
                        resolve(URL.createObjectURL(blob));
                    } else {
                        reject(new Error('Failed to generate thumbnail'));
                    }
                },
                'image/jpeg',
                quality
            );
        };
        
        img.onerror = () => {
            reject(new Error('Failed to load image'));
        };
        
        img.src = URL.createObjectURL(file);
    });
};

// Detect file corruption (basic checks)
export const checkFileIntegrity = async (file) => {
    return new Promise((resolve) => {
        const reader = new FileReader();
        
        reader.onload = () => {
            try {
                const arrayBuffer = reader.result;
                const uint8Array = new Uint8Array(arrayBuffer);
                
                // Basic file signature checks
                const isValidFile = checkFileSignature(uint8Array, file.type, file.name);
                resolve(isValidFile);
            } catch (error) {
                resolve(false);
            }
        };
        
        reader.onerror = () => resolve(false);
        
        // Read first few bytes for signature check
        const slice = file.slice(0, 8);
        reader.readAsArrayBuffer(slice);
    });
};

// Check file signature (magic numbers)
const checkFileSignature = (bytes, mimeType, fileName) => {
    const extension = fileName.split('.').pop().toLowerCase();
    
    // PDF signature: %PDF
    if (extension === 'pdf' || mimeType === 'application/pdf') {
        return bytes[0] === 0x25 && bytes[1] === 0x50 && bytes[2] === 0x44 && bytes[3] === 0x46;
    }
    
    // JPEG signature: FF D8 FF
    if (['jpg', 'jpeg'].includes(extension) || mimeType === 'image/jpeg') {
        return bytes[0] === 0xFF && bytes[1] === 0xD8 && bytes[2] === 0xFF;
    }
    
    // PNG signature: 89 50 4E 47 0D 0A 1A 0A
    if (extension === 'png' || mimeType === 'image/png') {
        return bytes[0] === 0x89 && bytes[1] === 0x50 && bytes[2] === 0x4E && bytes[3] === 0x47;
    }
    
    // For other files, assume valid (more comprehensive checks would require full file reading)
    return true;
};

export default {
    FILE_TYPES,
    CERTIFICATE_VALIDATION_RULES,
    ERROR_TYPES,
    ERROR_MESSAGES,
    validateFile,
    validateFiles,
    getFileType,
    isValidFileName,
    sanitizeFileName,
    formatFileSize,
    getFileDisplay,
    canPreviewFile,
    generateThumbnail,
    checkFileIntegrity
};