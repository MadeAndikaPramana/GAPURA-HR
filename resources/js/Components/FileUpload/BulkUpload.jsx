// components/FileUpload/BulkUpload.jsx
import { useState, useRef, useCallback } from 'react';
import PropTypes from 'prop-types';
import {
    CloudArrowUpIcon,
    DocumentPlusIcon,
    FolderPlusIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XMarkIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    ArrowUpOnSquareIcon
} from '@heroicons/react/24/outline';
import { useFileUpload } from '../../hooks/useFileUpload';
import { validateFiles, CERTIFICATE_VALIDATION_RULES } from '../../utils/fileValidation';
import UploadProgress from './UploadProgress';
import FilePreview from './FilePreview';

const CertificateTypeSelector = ({ certificateTypes, selectedTypes, onSelectionChange, disabled = false }) => {
    const [expandedGroups, setExpandedGroups] = useState({});

    const groupedTypes = certificateTypes.reduce((acc, type) => {
        const category = type.category || 'Other';
        if (!acc[category]) {
            acc[category] = [];
        }
        acc[category].push(type);
        return acc;
    }, {});

    const toggleGroup = (category) => {
        setExpandedGroups(prev => ({
            ...prev,
            [category]: !prev[category]
        }));
    };

    const isTypeSelected = (typeId) => {
        return selectedTypes.includes(typeId);
    };

    const toggleType = (typeId) => {
        if (disabled) return;
        
        const newSelection = isTypeSelected(typeId)
            ? selectedTypes.filter(id => id !== typeId)
            : [...selectedTypes, typeId];
        
        onSelectionChange(newSelection);
    };

    return (
        <div className="bg-white rounded-lg border border-gray-200">
            <div className="p-4 border-b border-gray-200">
                <h3 className="text-lg font-medium text-gray-900">Select Certificate Types</h3>
                <p className="text-sm text-gray-600 mt-1">
                    Choose which certificate types to upload files for
                </p>
            </div>
            
            <div className="max-h-96 overflow-y-auto">
                {Object.entries(groupedTypes).map(([category, types]) => (
                    <div key={category} className="border-b border-gray-100 last:border-b-0">
                        <button
                            onClick={() => toggleGroup(category)}
                            className="w-full px-4 py-3 text-left flex items-center justify-between hover:bg-gray-50"
                        >
                            <div className="flex items-center">
                                {expandedGroups[category] ? (
                                    <ChevronDownIcon className="w-4 h-4 mr-2 text-gray-500" />
                                ) : (
                                    <ChevronRightIcon className="w-4 h-4 mr-2 text-gray-500" />
                                )}
                                <span className="font-medium text-gray-900">{category}</span>
                                <span className="ml-2 px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">
                                    {types.length}
                                </span>
                            </div>
                            <div className="text-sm text-gray-500">
                                {types.filter(type => isTypeSelected(type.id)).length} selected
                            </div>
                        </button>
                        
                        {expandedGroups[category] && (
                            <div className="bg-gray-50 px-6 py-2">
                                {types.map(type => (
                                    <label
                                        key={type.id}
                                        className={`flex items-center py-2 cursor-pointer ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={isTypeSelected(type.id)}
                                            onChange={() => toggleType(type.id)}
                                            disabled={disabled}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <div className="ml-3 flex-1">
                                            <div className="text-sm font-medium text-gray-900">
                                                {type.name}
                                            </div>
                                            {type.description && (
                                                <div className="text-xs text-gray-500">
                                                    {type.description}
                                                </div>
                                            )}
                                            <div className="flex items-center mt-1 text-xs text-gray-500 space-x-3">
                                                <span>Max files: {type.max_files || 5}</span>
                                                {type.validity_period && (
                                                    <span>Valid for: {type.validity_period} months</span>
                                                )}
                                                {type.is_mandatory && (
                                                    <span className="px-1.5 py-0.5 bg-red-100 text-red-700 rounded">
                                                        Required
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </label>
                                ))}
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
};

const FileAssignment = ({ files, certificateTypes, selectedTypes, assignments, onAssignmentChange }) => {
    const [draggedFile, setDraggedFile] = useState(null);
    
    const getTypeById = (typeId) => {
        return certificateTypes.find(type => type.id === typeId);
    };

    const assignFile = (fileId, typeId) => {
        onAssignmentChange({
            ...assignments,
            [fileId]: typeId
        });
    };

    const unassignFile = (fileId) => {
        const newAssignments = { ...assignments };
        delete newAssignments[fileId];
        onAssignmentChange(newAssignments);
    };

    const handleDragStart = (e, file) => {
        setDraggedFile(file);
        e.dataTransfer.effectAllowed = 'move';
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    };

    const handleDrop = (e, typeId) => {
        e.preventDefault();
        if (draggedFile) {
            assignFile(draggedFile.id, typeId);
            setDraggedFile(null);
        }
    };

    const unassignedFiles = files.filter(file => !assignments[file.id]);
    
    return (
        <div className="space-y-6">
            {/* Unassigned Files */}
            {unassignedFiles.length > 0 && (
                <div className="bg-yellow-50 rounded-lg border border-yellow-200 p-4">
                    <h4 className="text-sm font-medium text-yellow-900 mb-2">
                        Unassigned Files ({unassignedFiles.length})
                    </h4>
                    <p className="text-xs text-yellow-700 mb-3">
                        Drag these files to certificate types below, or files will be uploaded to the first selected type
                    </p>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        {unassignedFiles.map(file => (
                            <div
                                key={file.id}
                                draggable
                                onDragStart={(e) => handleDragStart(e, file)}
                                className="flex items-center p-2 bg-white border border-yellow-300 rounded-md cursor-move hover:shadow-sm"
                            >
                                <div className="text-lg mr-2">
                                    {file.type?.startsWith('image/') ? '🖼️' : '📄'}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <div className="text-sm font-medium text-gray-900 truncate">
                                        {file.name}
                                    </div>
                                    <div className="text-xs text-gray-500">
                                        {(file.size / 1024).toFixed(1)} KB
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Certificate Type Assignments */}
            <div className="space-y-4">
                {selectedTypes.map(typeId => {
                    const type = getTypeById(typeId);
                    const assignedFiles = files.filter(file => assignments[file.id] === typeId);
                    
                    return (
                        <div
                            key={typeId}
                            className="bg-white rounded-lg border border-gray-200"
                            onDragOver={handleDragOver}
                            onDrop={(e) => handleDrop(e, typeId)}
                        >
                            <div className="p-4 border-b border-gray-200">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center">
                                        <FolderPlusIcon className="w-5 h-5 text-blue-600 mr-2" />
                                        <div>
                                            <h4 className="text-sm font-medium text-gray-900">
                                                {type?.name || 'Unknown Type'}
                                            </h4>
                                            <div className="text-xs text-gray-500">
                                                {assignedFiles.length} / {type?.max_files || 5} files
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {type?.is_mandatory && (
                                        <span className="px-2 py-1 text-xs bg-red-100 text-red-700 rounded">
                                            Required
                                        </span>
                                    )}
                                </div>
                            </div>
                            
                            <div className="p-4">
                                {assignedFiles.length > 0 ? (
                                    <div className="space-y-2">
                                        {assignedFiles.map(file => (
                                            <div
                                                key={file.id}
                                                className="flex items-center justify-between p-3 bg-gray-50 rounded-md border border-gray-200"
                                            >
                                                <div className="flex items-center">
                                                    <div className="text-lg mr-3">
                                                        {file.type?.startsWith('image/') ? '🖼️' : '📄'}
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <div className="text-sm font-medium text-gray-900 truncate">
                                                            {file.name}
                                                        </div>
                                                        <div className="text-xs text-gray-500">
                                                            {(file.size / 1024).toFixed(1)} KB • {file.type}
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <button
                                                    onClick={() => unassignFile(file.id)}
                                                    className="ml-2 p-1 text-gray-400 hover:text-red-600 focus:outline-none"
                                                    title="Remove from this certificate type"
                                                >
                                                    <XMarkIcon className="w-4 h-4" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                        <FolderPlusIcon className="w-8 h-8 text-gray-400 mx-auto mb-2" />
                                        <p className="text-sm text-gray-500">
                                            Drop files here for {type?.name}
                                        </p>
                                        <p className="text-xs text-gray-400 mt-1">
                                            Or assign files manually below
                                        </p>
                                    </div>
                                )}
                                
                                {/* Quick assign dropdown for mobile */}
                                {unassignedFiles.length > 0 && (
                                    <div className="mt-3 sm:hidden">
                                        <select
                                            onChange={(e) => {
                                                if (e.target.value) {
                                                    const fileId = e.target.value;
                                                    assignFile(fileId, typeId);
                                                    e.target.value = '';
                                                }
                                            }}
                                            className="block w-full text-xs border-gray-300 rounded-md"
                                        >
                                            <option value="">Assign file...</option>
                                            {unassignedFiles.map(file => (
                                                <option key={file.id} value={file.id}>
                                                    {file.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

const BulkUpload = ({ 
    certificateTypes = [], 
    employeeId,
    onUploadComplete,
    onCancel,
    className = "" 
}) => {
    const [step, setStep] = useState(1); // 1: Select types, 2: Upload files, 3: Assign files, 4: Upload progress
    const [selectedTypes, setSelectedTypes] = useState([]);
    const [assignments, setAssignments] = useState({});
    const [previewFile, setPreviewFile] = useState(null);
    
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
        fileInputRef
    } = useFileUpload({
        maxSize: 10 * 1024 * 1024, // 10MB
        maxFiles: 50,
        autoUpload: false
    });

    const handleTypeSelection = (types) => {
        setSelectedTypes(types);
    };

    const handleNextStep = () => {
        if (step === 1 && selectedTypes.length === 0) {
            return;
        }
        setStep(step + 1);
    };

    const handlePrevStep = () => {
        setStep(Math.max(1, step - 1));
    };

    const handleFileUpload = (uploadedFiles) => {
        if (step === 2) {
            setStep(3); // Move to assignment step
        }
    };

    const handleAssignmentChange = (newAssignments) => {
        setAssignments(newAssignments);
    };

    const handleStartUpload = async () => {
        setStep(4);
        clearErrors();

        try {
            const uploadPromises = selectedTypes.map(async (typeId) => {
                const assignedFiles = files.filter(file => assignments[file.id] === typeId);
                const unassignedFiles = files.filter(file => !assignments[file.id]);
                
                // If first type, include unassigned files
                const filesToUpload = typeId === selectedTypes[0] 
                    ? [...assignedFiles, ...unassignedFiles]
                    : assignedFiles;

                if (filesToUpload.length === 0) return;

                const uploadUrl = `/employee-containers/${employeeId}/certificates`;
                const additionalData = {
                    certificate_type_id: typeId,
                    bulk_upload: true
                };

                return uploadFiles(filesToUpload, uploadUrl, { additionalData });
            });

            const results = await Promise.all(uploadPromises);
            const totalSuccessful = results.reduce((sum, result) => sum + (result?.successful || 0), 0);
            
            if (onUploadComplete) {
                onUploadComplete({
                    successful: totalSuccessful,
                    total: files.length,
                    certificateTypes: selectedTypes
                });
            }
        } catch (error) {
            console.error('Bulk upload error:', error);
        }
    };

    const handleCancel = () => {
        clearFiles();
        setStep(1);
        setSelectedTypes([]);
        setAssignments({});
        if (onCancel) {
            onCancel();
        }
    };

    const renderStepIndicator = () => (
        <div className="flex items-center justify-center mb-8">
            {[1, 2, 3, 4].map((stepNum) => (
                <div key={stepNum} className="flex items-center">
                    <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium ${
                        step === stepNum 
                            ? 'bg-blue-600 text-white' 
                            : step > stepNum 
                            ? 'bg-green-600 text-white'
                            : 'bg-gray-300 text-gray-600'
                    }`}>
                        {step > stepNum ? (
                            <CheckCircleIcon className="w-5 h-5" />
                        ) : (
                            stepNum
                        )}
                    </div>
                    {stepNum < 4 && (
                        <div className={`w-16 h-1 mx-2 ${
                            step > stepNum ? 'bg-green-600' : 'bg-gray-300'
                        }`} />
                    )}
                </div>
            ))}
        </div>
    );

    return (
        <div className={`max-w-6xl mx-auto ${className}`}>
            {renderStepIndicator()}

            {/* Step 1: Select Certificate Types */}
            {step === 1 && (
                <div>
                    <div className="text-center mb-6">
                        <h2 className="text-2xl font-bold text-gray-900">Bulk Certificate Upload</h2>
                        <p className="text-gray-600 mt-2">
                            Upload multiple certificates for different types in one go
                        </p>
                    </div>

                    <CertificateTypeSelector
                        certificateTypes={certificateTypes}
                        selectedTypes={selectedTypes}
                        onSelectionChange={handleTypeSelection}
                    />

                    <div className="flex justify-between mt-6">
                        <button
                            onClick={handleCancel}
                            className="px-4 py-2 text-gray-600 hover:text-gray-800"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleNextStep}
                            disabled={selectedTypes.length === 0}
                            className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Continue ({selectedTypes.length} types selected)
                        </button>
                    </div>
                </div>
            )}

            {/* Step 2: Upload Files */}
            {step === 2 && (
                <div>
                    <div className="text-center mb-6">
                        <h2 className="text-2xl font-bold text-gray-900">Upload Files</h2>
                        <p className="text-gray-600 mt-2">
                            Add files for the selected certificate types
                        </p>
                    </div>

                    {/* Drop Zone */}
                    <div
                        {...dragHandlers}
                        onClick={() => fileInputRef.current?.click()}
                        className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer ${
                            dragOver 
                                ? 'border-blue-400 bg-blue-50' 
                                : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50'
                        }`}
                    >
                        <input
                            ref={fileInputRef}
                            type="file"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png"
                            onChange={(e) => handleFiles(e.target.files)}
                            className="hidden"
                        />
                        
                        <CloudArrowUpIcon className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">
                            Upload Certificate Files
                        </h3>
                        <p className="text-gray-600 mb-4">
                            Drag and drop files here, or click to browse
                        </p>
                        <p className="text-sm text-gray-500">
                            PDF, JPG, PNG files up to 10MB each
                        </p>
                    </div>

                    {/* Error Messages */}
                    {errors.length > 0 && (
                        <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
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

                    {/* Selected Files */}
                    {files.length > 0 && (
                        <div className="mt-6">
                            <h4 className="text-lg font-medium text-gray-900 mb-4">
                                Selected Files ({files.length})
                            </h4>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                {files.map(file => (
                                    <div key={file.id} className="relative group">
                                        <div className="flex items-center p-3 bg-white border border-gray-200 rounded-md hover:shadow-sm">
                                            <div className="text-2xl mr-3">
                                                {file.type?.startsWith('image/') ? '🖼️' : '📄'}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="text-sm font-medium text-gray-900 truncate">
                                                    {file.name}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    {(file.size / 1024).toFixed(1)} KB
                                                </div>
                                            </div>
                                            <div className="flex space-x-1">
                                                <button
                                                    onClick={() => setPreviewFile(file)}
                                                    className="p-1 text-gray-400 hover:text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity"
                                                    title="Preview"
                                                >
                                                    👁️
                                                </button>
                                                <button
                                                    onClick={() => removeFile(file.id)}
                                                    className="p-1 text-gray-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity"
                                                    title="Remove"
                                                >
                                                    <XMarkIcon className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="flex justify-between mt-6">
                        <button
                            onClick={handlePrevStep}
                            className="px-4 py-2 text-gray-600 hover:text-gray-800"
                        >
                            Back
                        </button>
                        <button
                            onClick={handleNextStep}
                            disabled={files.length === 0}
                            className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Continue ({files.length} files)
                        </button>
                    </div>
                </div>
            )}

            {/* Step 3: Assign Files */}
            {step === 3 && (
                <div>
                    <div className="text-center mb-6">
                        <h2 className="text-2xl font-bold text-gray-900">Assign Files</h2>
                        <p className="text-gray-600 mt-2">
                            Organize your files by certificate type
                        </p>
                    </div>

                    <FileAssignment
                        files={files}
                        certificateTypes={certificateTypes}
                        selectedTypes={selectedTypes}
                        assignments={assignments}
                        onAssignmentChange={handleAssignmentChange}
                    />

                    <div className="flex justify-between mt-6">
                        <button
                            onClick={handlePrevStep}
                            className="px-4 py-2 text-gray-600 hover:text-gray-800"
                        >
                            Back
                        </button>
                        <button
                            onClick={handleStartUpload}
                            className="flex items-center px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                        >
                            <ArrowUpOnSquareIcon className="w-4 h-4 mr-2" />
                            Start Upload
                        </button>
                    </div>
                </div>
            )}

            {/* Step 4: Upload Progress */}
            {step === 4 && (
                <div>
                    <div className="text-center mb-6">
                        <h2 className="text-2xl font-bold text-gray-900">Uploading Files</h2>
                        <p className="text-gray-600 mt-2">
                            Please wait while your files are being uploaded
                        </p>
                    </div>

                    <UploadProgress
                        files={files}
                        uploadProgress={uploadProgress}
                        onRetry={(file) => {
                            // Handle retry logic
                        }}
                        onRemove={removeFile}
                    />

                    {!uploading && (
                        <div className="flex justify-center mt-6">
                            <button
                                onClick={handleCancel}
                                className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                            >
                                Done
                            </button>
                        </div>
                    )}
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
};

CertificateTypeSelector.propTypes = {
    certificateTypes: PropTypes.array.isRequired,
    selectedTypes: PropTypes.array.isRequired,
    onSelectionChange: PropTypes.func.isRequired,
    disabled: PropTypes.bool
};

FileAssignment.propTypes = {
    files: PropTypes.array.isRequired,
    certificateTypes: PropTypes.array.isRequired,
    selectedTypes: PropTypes.array.isRequired,
    assignments: PropTypes.object.isRequired,
    onAssignmentChange: PropTypes.func.isRequired
};

BulkUpload.propTypes = {
    certificateTypes: PropTypes.array,
    employeeId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    onUploadComplete: PropTypes.func,
    onCancel: PropTypes.func,
    className: PropTypes.string
};

export default BulkUpload;