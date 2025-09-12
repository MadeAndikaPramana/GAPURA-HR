// components/FileUpload/FilePreview.jsx
import { useState, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import {
    XMarkIcon,
    ArrowDownTrayIcon,
    MagnifyingGlassMinusIcon,
    MagnifyingGlassPlusIcon,
    ArrowsPointingOutIcon,
    ArrowsPointingInIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ExclamationTriangleIcon,
    DocumentTextIcon,
    PhotoIcon
} from '@heroicons/react/24/outline';
import { formatFileSize, getFileDisplay, canPreviewFile } from '../../utils/fileValidation';

// PDF Viewer Component
const PDFViewer = ({ file, onClose }) => {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [pdfUrl, setPdfUrl] = useState(null);
    const [scale, setScale] = useState(1.0);
    const iframeRef = useRef(null);

    useEffect(() => {
        loadPDF();
        return () => {
            if (pdfUrl) {
                URL.revokeObjectURL(pdfUrl);
            }
        };
    }, [file]);

    const loadPDF = async () => {
        setLoading(true);
        setError(null);

        try {
            let url;
            if (file instanceof File) {
                url = URL.createObjectURL(file);
            } else if (file.path && file.download_url) {
                url = file.download_url;
            } else {
                throw new Error('Invalid file source');
            }

            setPdfUrl(url);
            setLoading(false);
        } catch (err) {
            console.error('Error loading PDF:', err);
            setError('Unable to load PDF file');
            setLoading(false);
        }
    };

    const handleZoomIn = () => {
        setScale(prev => Math.min(prev + 0.25, 3.0));
    };

    const handleZoomOut = () => {
        setScale(prev => Math.max(prev - 0.25, 0.5));
    };

    const handleResetZoom = () => {
        setScale(1.0);
    };

    const handleDownload = () => {
        if (file instanceof File) {
            const url = URL.createObjectURL(file);
            const a = document.createElement('a');
            a.href = url;
            a.download = file.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } else if (file.download_url) {
            window.open(file.download_url, '_blank');
        }
    };

    return (
        <div className="flex flex-col h-full">
            {/* PDF Controls */}
            <div className="flex items-center justify-between p-2 bg-gray-100 border-b">
                <div className="flex items-center space-x-2">
                    <button
                        onClick={handleZoomOut}
                        disabled={scale <= 0.5}
                        className="p-1 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Zoom Out"
                    >
                        <MagnifyingGlassMinusIcon className="w-4 h-4" />
                    </button>
                    
                    <span className="text-sm text-gray-600 min-w-[60px] text-center">
                        {Math.round(scale * 100)}%
                    </span>
                    
                    <button
                        onClick={handleZoomIn}
                        disabled={scale >= 3.0}
                        className="p-1 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Zoom In"
                    >
                        <MagnifyingGlassPlusIcon className="w-4 h-4" />
                    </button>
                    
                    <button
                        onClick={handleResetZoom}
                        className="px-2 py-1 text-xs bg-white border rounded hover:bg-gray-50"
                    >
                        Reset
                    </button>
                </div>
                
                <button
                    onClick={handleDownload}
                    className="flex items-center px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    <ArrowDownTrayIcon className="w-3 h-3 mr-1" />
                    Download
                </button>
            </div>
            
            {/* PDF Content */}
            <div className="flex-1 overflow-auto">
                {loading ? (
                    <div className="flex items-center justify-center h-full">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        <span className="ml-3 text-gray-600">Loading PDF...</span>
                    </div>
                ) : error ? (
                    <div className="flex flex-col items-center justify-center h-full text-gray-500 p-8">
                        <ExclamationTriangleIcon className="w-16 h-16 mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">
                            {error}
                        </h3>
                        <button
                            onClick={handleDownload}
                            className="mt-3 text-blue-600 hover:text-blue-800 text-sm underline"
                        >
                            Download file instead
                        </button>
                    </div>
                ) : (
                    <div style={{ transform: `scale(${scale})`, transformOrigin: 'top left' }}>
                        <iframe
                            ref={iframeRef}
                            src={pdfUrl}
                            title={file.name || 'PDF Preview'}
                            className="w-full h-screen border-0"
                            style={{ minHeight: '600px' }}
                        />
                    </div>
                )}
            </div>
        </div>
    );
};

// Image Lightbox Component
const ImageLightbox = ({ file, files = [], currentIndex = 0, onClose, onNavigate }) => {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [imageUrl, setImageUrl] = useState(null);
    const [scale, setScale] = useState(1.0);
    const [position, setPosition] = useState({ x: 0, y: 0 });
    const [isDragging, setIsDragging] = useState(false);
    const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
    const [isFullscreen, setIsFullscreen] = useState(false);
    
    const imageRef = useRef(null);
    const containerRef = useRef(null);

    useEffect(() => {
        loadImage();
        return () => {
            if (imageUrl) {
                URL.revokeObjectURL(imageUrl);
            }
        };
    }, [file]);

    const loadImage = async () => {
        setLoading(true);
        setError(null);

        try {
            let url;
            if (file instanceof File) {
                url = URL.createObjectURL(file);
            } else if (file.preview_url || file.download_url) {
                url = file.preview_url || file.download_url;
            } else {
                throw new Error('Invalid image source');
            }

            // Test if image loads
            const img = new Image();
            img.onload = () => {
                setImageUrl(url);
                setLoading(false);
            };
            img.onerror = () => {
                setError('Failed to load image');
                setLoading(false);
                if (file instanceof File) {
                    URL.revokeObjectURL(url);
                }
            };
            img.src = url;
        } catch (err) {
            console.error('Error loading image:', err);
            setError('Unable to load image');
            setLoading(false);
        }
    };

    const handleZoomIn = () => {
        setScale(prev => Math.min(prev * 1.25, 5.0));
    };

    const handleZoomOut = () => {
        setScale(prev => Math.max(prev / 1.25, 0.25));
    };

    const handleResetZoom = () => {
        setScale(1.0);
        setPosition({ x: 0, y: 0 });
    };

    const handleMouseDown = (e) => {
        if (scale > 1) {
            setIsDragging(true);
            setDragStart({
                x: e.clientX - position.x,
                y: e.clientY - position.y
            });
        }
    };

    const handleMouseMove = (e) => {
        if (isDragging && scale > 1) {
            setPosition({
                x: e.clientX - dragStart.x,
                y: e.clientY - dragStart.y
            });
        }
    };

    const handleMouseUp = () => {
        setIsDragging(false);
    };

    const handleKeyDown = (e) => {
        if (e.key === 'ArrowLeft' && onNavigate && currentIndex > 0) {
            onNavigate(currentIndex - 1);
        } else if (e.key === 'ArrowRight' && onNavigate && currentIndex < files.length - 1) {
            onNavigate(currentIndex + 1);
        } else if (e.key === 'Escape') {
            onClose();
        }
    };

    const toggleFullscreen = () => {
        if (!document.fullscreenElement) {
            containerRef.current?.requestFullscreen();
            setIsFullscreen(true);
        } else {
            document.exitFullscreen();
            setIsFullscreen(false);
        }
    };

    useEffect(() => {
        const handleFullscreenChange = () => {
            setIsFullscreen(!!document.fullscreenElement);
        };

        document.addEventListener('fullscreenchange', handleFullscreenChange);
        return () => document.removeEventListener('fullscreenchange', handleFullscreenChange);
    }, []);

    const handleDownload = () => {
        if (file instanceof File) {
            const url = URL.createObjectURL(file);
            const a = document.createElement('a');
            a.href = url;
            a.download = file.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } else if (file.download_url) {
            window.open(file.download_url, '_blank');
        }
    };

    const showNavigation = files.length > 1 && onNavigate;

    return (
        <div
            ref={containerRef}
            className="relative w-full h-full bg-black"
            onKeyDown={handleKeyDown}
            tabIndex={0}
        >
            {/* Top Controls */}
            <div className="absolute top-0 left-0 right-0 z-10 bg-black bg-opacity-50 text-white p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <h3 className="text-lg font-medium truncate">
                            {file.name || file.original_name || 'Image Preview'}
                        </h3>
                        {showNavigation && (
                            <span className="text-sm text-gray-300">
                                {currentIndex + 1} of {files.length}
                            </span>
                        )}
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <button
                            onClick={handleZoomOut}
                            disabled={scale <= 0.25}
                            className="p-2 rounded hover:bg-white hover:bg-opacity-20 disabled:opacity-50"
                            title="Zoom Out"
                        >
                            <MagnifyingGlassMinusIcon className="w-5 h-5" />
                        </button>
                        
                        <span className="text-sm min-w-[60px] text-center">
                            {Math.round(scale * 100)}%
                        </span>
                        
                        <button
                            onClick={handleZoomIn}
                            disabled={scale >= 5.0}
                            className="p-2 rounded hover:bg-white hover:bg-opacity-20 disabled:opacity-50"
                            title="Zoom In"
                        >
                            <MagnifyingGlassPlusIcon className="w-5 h-5" />
                        </button>
                        
                        <button
                            onClick={handleResetZoom}
                            className="px-3 py-2 text-sm bg-white bg-opacity-20 rounded hover:bg-opacity-30"
                        >
                            Fit
                        </button>
                        
                        <button
                            onClick={toggleFullscreen}
                            className="p-2 rounded hover:bg-white hover:bg-opacity-20"
                            title={isFullscreen ? "Exit Fullscreen" : "Enter Fullscreen"}
                        >
                            {isFullscreen ? (
                                <ArrowsPointingInIcon className="w-5 h-5" />
                            ) : (
                                <ArrowsPointingOutIcon className="w-5 h-5" />
                            )}
                        </button>
                        
                        <button
                            onClick={handleDownload}
                            className="p-2 rounded hover:bg-white hover:bg-opacity-20"
                            title="Download"
                        >
                            <ArrowDownTrayIcon className="w-5 h-5" />
                        </button>
                        
                        <button
                            onClick={onClose}
                            className="p-2 rounded hover:bg-white hover:bg-opacity-20"
                            title="Close"
                        >
                            <XMarkIcon className="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>

            {/* Navigation Arrows */}
            {showNavigation && (
                <>
                    <button
                        onClick={() => onNavigate(currentIndex - 1)}
                        disabled={currentIndex === 0}
                        className="absolute left-4 top-1/2 transform -translate-y-1/2 z-10 p-3 bg-black bg-opacity-50 text-white rounded-full hover:bg-opacity-75 disabled:opacity-30 disabled:cursor-not-allowed"
                    >
                        <ChevronLeftIcon className="w-6 h-6" />
                    </button>
                    
                    <button
                        onClick={() => onNavigate(currentIndex + 1)}
                        disabled={currentIndex === files.length - 1}
                        className="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 p-3 bg-black bg-opacity-50 text-white rounded-full hover:bg-opacity-75 disabled:opacity-30 disabled:cursor-not-allowed"
                    >
                        <ChevronRightIcon className="w-6 h-6" />
                    </button>
                </>
            )}

            {/* Image Content */}
            <div className="absolute inset-0 flex items-center justify-center">
                {loading ? (
                    <div className="flex flex-col items-center text-white">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div>
                        <span className="mt-3">Loading image...</span>
                    </div>
                ) : error ? (
                    <div className="flex flex-col items-center text-white p-8">
                        <ExclamationTriangleIcon className="w-16 h-16 mb-4" />
                        <h3 className="text-lg font-medium mb-2">{error}</h3>
                        <button
                            onClick={handleDownload}
                            className="mt-3 text-blue-400 hover:text-blue-300 underline"
                        >
                            Download file instead
                        </button>
                    </div>
                ) : (
                    <div
                        className={`${scale > 1 ? 'cursor-move' : 'cursor-zoom-in'}`}
                        onMouseDown={handleMouseDown}
                        onMouseMove={handleMouseMove}
                        onMouseUp={handleMouseUp}
                        onMouseLeave={handleMouseUp}
                        onClick={scale === 1 ? handleZoomIn : undefined}
                    >
                        <img
                            ref={imageRef}
                            src={imageUrl}
                            alt={file.name || 'Preview'}
                            className="max-w-none select-none"
                            style={{
                                transform: `scale(${scale}) translate(${position.x / scale}px, ${position.y / scale}px)`,
                                transition: isDragging ? 'none' : 'transform 0.2s ease-out'
                            }}
                            draggable={false}
                        />
                    </div>
                )}
            </div>
        </div>
    );
};

// Main File Preview Component
const FilePreview = ({ 
    file, 
    files = [], 
    currentIndex = 0, 
    onClose, 
    onNavigate,
    className = "" 
}) => {
    if (!file) return null;

    const fileDisplay = getFileDisplay(file);
    const fileName = file.name || file.original_name || 'Unknown file';
    
    if (!canPreviewFile(file)) {
        return (
            <div className={`fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 ${className}`}>
                <div className="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-medium text-gray-900">File Preview</h3>
                        <button
                            onClick={onClose}
                            className="text-gray-400 hover:text-gray-600"
                        >
                            <XMarkIcon className="w-6 h-6" />
                        </button>
                    </div>
                    
                    <div className="text-center py-8">
                        <div className="text-4xl mb-4">{fileDisplay.icon}</div>
                        <h4 className="text-lg font-medium text-gray-900 mb-2">
                            Preview Not Available
                        </h4>
                        <p className="text-gray-600 mb-4">
                            "{fileName}" cannot be previewed in the browser.
                        </p>
                        <button
                            onClick={() => {
                                // Handle download
                                if (file instanceof File) {
                                    const url = URL.createObjectURL(file);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = file.name;
                                    document.body.appendChild(a);
                                    a.click();
                                    document.body.removeChild(a);
                                    URL.revokeObjectURL(url);
                                } else if (file.download_url) {
                                    window.open(file.download_url, '_blank');
                                }
                            }}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                            <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                            Download File
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    const isPDF = fileDisplay.type === 'PDF' || file.type === 'application/pdf' || fileName.toLowerCase().endsWith('.pdf');
    const isImage = fileDisplay.type === 'IMAGE' || file.type?.startsWith('image/');

    return (
        <div className={`fixed inset-0 bg-black bg-opacity-75 z-50 ${className}`}>
            <div className="w-full h-full">
                {isPDF ? (
                    <div className="bg-white h-full">
                        <div className="flex items-center justify-between p-4 border-b">
                            <h3 className="text-lg font-medium text-gray-900 truncate">
                                {fileName}
                            </h3>
                            <button
                                onClick={onClose}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                <XMarkIcon className="w-6 h-6" />
                            </button>
                        </div>
                        <div className="h-full">
                            <PDFViewer file={file} onClose={onClose} />
                        </div>
                    </div>
                ) : isImage ? (
                    <ImageLightbox
                        file={file}
                        files={files}
                        currentIndex={currentIndex}
                        onClose={onClose}
                        onNavigate={onNavigate}
                    />
                ) : null}
            </div>
        </div>
    );
};

PDFViewer.propTypes = {
    file: PropTypes.object.isRequired,
    onClose: PropTypes.func.isRequired
};

ImageLightbox.propTypes = {
    file: PropTypes.object.isRequired,
    files: PropTypes.array,
    currentIndex: PropTypes.number,
    onClose: PropTypes.func.isRequired,
    onNavigate: PropTypes.func
};

FilePreview.propTypes = {
    file: PropTypes.object,
    files: PropTypes.array,
    currentIndex: PropTypes.number,
    onClose: PropTypes.func.isRequired,
    onNavigate: PropTypes.func,
    className: PropTypes.string
};

export default FilePreview;