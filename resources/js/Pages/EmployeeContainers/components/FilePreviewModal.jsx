import { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import {
    XMarkIcon,
    ArrowDownTrayIcon,
    EyeIcon,
    DocumentTextIcon,
    PhotoIcon,
    ExclamationTriangleIcon
} from '@heroicons/react/24/outline';

export default function FilePreviewModal({ file, onClose }) {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [previewUrl, setPreviewUrl] = useState(null);

    useEffect(() => {
        if (file) {
            loadFilePreview();
        }
    }, [file]);

    const loadFilePreview = async () => {
        setLoading(true);
        setError(null);

        try {
            // If it's a File object (from file input), create object URL
            if (file instanceof File) {
                const url = URL.createObjectURL(file);
                setPreviewUrl(url);
                setLoading(false);
                return;
            }

            // If it's a server file with path, we need to handle it differently
            if (file.path) {
                // For server files, we'll show download option instead of direct preview
                // since we can't directly access private storage files in browser
                setPreviewUrl(null);
                setLoading(false);
                return;
            }

            throw new Error('Invalid file format');

        } catch (err) {
            console.error('Error loading file preview:', err);
            setError('Unable to preview this file');
            setLoading(false);
        }
    };

    const getFileExtension = (filename) => {
        return filename?.split('.').pop()?.toLowerCase() || '';
    };

    const getFileType = (filename) => {
        const extension = getFileExtension(filename);
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
            return 'image';
        } else if (extension === 'pdf') {
            return 'pdf';
        }
        return 'document';
    };

    const handleDownload = () => {
        if (file instanceof File) {
            // For File objects, create a download
            const url = URL.createObjectURL(file);
            const a = document.createElement('a');
            a.href = url;
            a.download = file.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } else if (file.path && file.download_url) {
            // For server files, use the download URL
            window.open(file.download_url, '_blank');
        }
    };

    const formatFileSize = (bytes) => {
        if (!bytes) return 'Unknown size';
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    if (!file) return null;

    const fileType = getFileType(file.name || file.original_name);
    const fileName = file.name || file.original_name || 'Unknown file';
    const fileSize = file.size || null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-4xl max-h-[90vh] w-full flex flex-col">
                
                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b border-gray-200">
                    <div className="flex items-center space-x-3">
                        <div className="p-2 bg-blue-100 rounded-lg">
                            {fileType === 'image' ? (
                                <PhotoIcon className="w-5 h-5 text-blue-600" />
                            ) : (
                                <DocumentTextIcon className="w-5 h-5 text-blue-600" />
                            )}
                        </div>
                        <div>
                            <h3 className="text-lg font-medium text-gray-900 truncate">
                                {fileName}
                            </h3>
                            {fileSize && (
                                <p className="text-sm text-gray-500">
                                    {formatFileSize(fileSize)}
                                </p>
                            )}
                        </div>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <button
                            onClick={handleDownload}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                            Download
                        </button>
                        <button
                            onClick={onClose}
                            className="p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
                        >
                            <XMarkIcon className="w-6 h-6" />
                        </button>
                    </div>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-hidden">
                    {loading ? (
                        <div className="flex items-center justify-center h-64">
                            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                            <span className="ml-3 text-gray-600">Loading preview...</span>
                        </div>
                    ) : error ? (
                        <div className="flex flex-col items-center justify-center h-64 text-gray-500">
                            <ExclamationTriangleIcon className="w-12 h-12 mb-3" />
                            <p className="text-sm">{error}</p>
                            <button
                                onClick={handleDownload}
                                className="mt-3 text-blue-600 hover:text-blue-800 text-sm underline"
                            >
                                Download file instead
                            </button>
                        </div>
                    ) : (
                        <div className="h-full overflow-auto">
                            {fileType === 'image' && previewUrl ? (
                                <div className="flex items-center justify-center p-4 h-full">
                                    <img
                                        src={previewUrl}
                                        alt={fileName}
                                        className="max-w-full max-h-full object-contain rounded-lg shadow-lg"
                                        onError={() => setError('Unable to load image')}
                                    />
                                </div>
                            ) : fileType === 'pdf' && previewUrl ? (
                                <div className="h-full">
                                    <iframe
                                        src={previewUrl}
                                        title={fileName}
                                        className="w-full h-full border-0"
                                        onError={() => setError('Unable to load PDF')}
                                    />
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center h-64 text-gray-500 p-8">
                                    <DocumentTextIcon className="w-16 h-16 mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                                        Preview not available
                                    </h3>
                                    <p className="text-sm text-center mb-4">
                                        This file type cannot be previewed in the browser. 
                                        You can download it to view on your device.
                                    </p>
                                    <button
                                        onClick={handleDownload}
                                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                                        Download File
                                    </button>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="p-4 border-t border-gray-200 bg-gray-50">
                    <div className="flex items-center justify-between text-sm text-gray-600">
                        <div className="flex items-center space-x-4">
                            <span>Type: {getFileExtension(fileName).toUpperCase()}</span>
                            {fileSize && <span>Size: {formatFileSize(fileSize)}</span>}
                            {file.uploaded_at && (
                                <span>Uploaded: {new Date(file.uploaded_at).toLocaleDateString()}</span>
                            )}
                        </div>
                        
                        <div className="flex items-center space-x-2">
                            <EyeIcon className="w-4 h-4" />
                            <span>Preview Mode</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

FilePreviewModal.propTypes = {
    file: PropTypes.oneOfType([
        PropTypes.instanceOf(File),
        PropTypes.shape({
            name: PropTypes.string,
            original_name: PropTypes.string,
            path: PropTypes.string,
            size: PropTypes.number,
            uploaded_at: PropTypes.string,
            download_url: PropTypes.string
        })
    ]),
    onClose: PropTypes.func.isRequired
};