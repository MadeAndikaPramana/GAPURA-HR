// components/ContainerManagement/BulkOperations.jsx
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import PropTypes from 'prop-types';
import {
    CheckIcon,
    XMarkIcon,
    ArrowDownTrayIcon,
    PencilSquareIcon,
    TrashIcon,
    DocumentArrowUpIcon,
    UserGroupIcon,
    AcademicCapIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    ClipboardDocumentListIcon,
    CalendarIcon,
    TagIcon,
    Cog6ToothIcon
} from '@heroicons/react/24/outline';

const BulkActionSelector = ({ selectedCount, onActionSelect, availableActions }) => {
    const [isOpen, setIsOpen] = useState(false);

    const actionIcons = {
        'update_status': PencilSquareIcon,
        'update_expiry_dates': CalendarIcon,
        'assign_tags': TagIcon,
        'export_data': ArrowDownTrayIcon,
        'bulk_upload': DocumentArrowUpIcon,
        'delete_certificates': TrashIcon,
        'send_notifications': InformationCircleIcon,
        'generate_report': ClipboardDocumentListIcon
    };

    const actionColors = {
        'update_status': 'text-blue-600 hover:bg-blue-50',
        'update_expiry_dates': 'text-purple-600 hover:bg-purple-50',
        'assign_tags': 'text-green-600 hover:bg-green-50',
        'export_data': 'text-indigo-600 hover:bg-indigo-50',
        'bulk_upload': 'text-yellow-600 hover:bg-yellow-50',
        'delete_certificates': 'text-red-600 hover:bg-red-50',
        'send_notifications': 'text-blue-600 hover:bg-blue-50',
        'generate_report': 'text-gray-600 hover:bg-gray-50'
    };

    if (selectedCount === 0) return null;

    return (
        <div className="relative">
            <div className="bg-white border border-gray-300 rounded-lg shadow-sm p-3">
                <div className="flex items-center justify-between">
                    <div className="flex items-center text-sm text-gray-700">
                        <CheckIcon className="w-4 h-4 mr-2 text-blue-600" />
                        {selectedCount} item{selectedCount !== 1 ? 's' : ''} selected
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <button
                            onClick={() => setIsOpen(!isOpen)}
                            className="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <Cog6ToothIcon className="w-4 h-4 mr-1" />
                            Actions
                        </button>
                    </div>
                </div>

                {isOpen && (
                    <div className="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        <div className="py-1">
                            {availableActions.map((action) => {
                                const Icon = actionIcons[action.key] || Cog6ToothIcon;
                                const colorClass = actionColors[action.key] || 'text-gray-600 hover:bg-gray-50';
                                
                                return (
                                    <button
                                        key={action.key}
                                        onClick={() => {
                                            onActionSelect(action);
                                            setIsOpen(false);
                                        }}
                                        disabled={action.disabled}
                                        className={`w-full flex items-center px-4 py-2 text-sm ${colorClass} disabled:opacity-50 disabled:cursor-not-allowed`}
                                    >
                                        <Icon className="w-4 h-4 mr-3" />
                                        {action.label}
                                        {action.description && (
                                            <span className="ml-auto text-xs text-gray-500">
                                                {action.description}
                                            </span>
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

const BulkStatusUpdateModal = ({ selectedItems, onConfirm, onCancel }) => {
    const [newStatus, setNewStatus] = useState('');
    const [notes, setNotes] = useState('');
    const [processing, setProcessing] = useState(false);

    const statusOptions = [
        { value: 'active', label: 'Active', color: 'green' },
        { value: 'expired', label: 'Expired', color: 'red' },
        { value: 'pending', label: 'Pending Review', color: 'yellow' },
        { value: 'suspended', label: 'Suspended', color: 'red' }
    ];

    const handleSubmit = async () => {
        if (!newStatus) return;
        
        setProcessing(true);
        try {
            await onConfirm({
                action: 'update_status',
                data: { status: newStatus, notes },
                items: selectedItems
            });
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div className="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 className="text-lg font-medium text-gray-900">
                        Update Status for {selectedItems.length} Items
                    </h3>
                    <button
                        onClick={onCancel}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <XMarkIcon className="w-6 h-6" />
                    </button>
                </div>

                <div className="p-6">
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                New Status
                            </label>
                            <div className="space-y-2">
                                {statusOptions.map((option) => (
                                    <label key={option.value} className="flex items-center">
                                        <input
                                            type="radio"
                                            name="status"
                                            value={option.value}
                                            checked={newStatus === option.value}
                                            onChange={(e) => setNewStatus(e.target.value)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">{option.label}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Notes (Optional)
                            </label>
                            <textarea
                                value={notes}
                                onChange={(e) => setNotes(e.target.value)}
                                rows={3}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Reason for status change..."
                            />
                        </div>

                        <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div className="flex">
                                <ExclamationTriangleIcon className="h-5 w-5 text-yellow-400 mt-0.5 mr-2 flex-shrink-0" />
                                <div className="text-sm text-yellow-700">
                                    This action will update the status for all {selectedItems.length} selected items.
                                    This action cannot be undone.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-200">
                    <button
                        onClick={onCancel}
                        disabled={processing}
                        className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleSubmit}
                        disabled={!newStatus || processing}
                        className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {processing ? 'Updating...' : `Update ${selectedItems.length} Items`}
                    </button>
                </div>
            </div>
        </div>
    );
};

const BulkExpiryUpdateModal = ({ selectedItems, onConfirm, onCancel }) => {
    const [updateType, setUpdateType] = useState('extend'); // extend, set_date, based_on_issue
    const [extensionMonths, setExtensionMonths] = useState(12);
    const [newExpiryDate, setNewExpiryDate] = useState('');
    const [processing, setProcessing] = useState(false);

    const handleSubmit = async () => {
        setProcessing(true);
        try {
            const data = {
                update_type: updateType,
                ...(updateType === 'extend' && { extension_months: extensionMonths }),
                ...(updateType === 'set_date' && { new_expiry_date: newExpiryDate })
            };

            await onConfirm({
                action: 'update_expiry_dates',
                data,
                items: selectedItems
            });
        } finally {
            setProcessing(false);
        }
    };

    const canSubmit = () => {
        if (updateType === 'extend') return extensionMonths > 0;
        if (updateType === 'set_date') return newExpiryDate !== '';
        return true;
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div className="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 className="text-lg font-medium text-gray-900">
                        Update Expiry Dates for {selectedItems.length} Items
                    </h3>
                    <button
                        onClick={onCancel}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <XMarkIcon className="w-6 h-6" />
                    </button>
                </div>

                <div className="p-6">
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-3">
                                Update Method
                            </label>
                            <div className="space-y-3">
                                <label className="flex items-start">
                                    <input
                                        type="radio"
                                        name="updateType"
                                        value="extend"
                                        checked={updateType === 'extend'}
                                        onChange={(e) => setUpdateType(e.target.value)}
                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 mt-0.5"
                                    />
                                    <div className="ml-2">
                                        <span className="text-sm font-medium text-gray-700">Extend by months</span>
                                        <p className="text-xs text-gray-500">Add months to current expiry date</p>
                                    </div>
                                </label>

                                <label className="flex items-start">
                                    <input
                                        type="radio"
                                        name="updateType"
                                        value="set_date"
                                        checked={updateType === 'set_date'}
                                        onChange={(e) => setUpdateType(e.target.value)}
                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 mt-0.5"
                                    />
                                    <div className="ml-2">
                                        <span className="text-sm font-medium text-gray-700">Set specific date</span>
                                        <p className="text-xs text-gray-500">Set same expiry date for all items</p>
                                    </div>
                                </label>

                                <label className="flex items-start">
                                    <input
                                        type="radio"
                                        name="updateType"
                                        value="based_on_issue"
                                        checked={updateType === 'based_on_issue'}
                                        onChange={(e) => setUpdateType(e.target.value)}
                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 mt-0.5"
                                    />
                                    <div className="ml-2">
                                        <span className="text-sm font-medium text-gray-700">Based on certificate validity</span>
                                        <p className="text-xs text-gray-500">Calculate from issue date + certificate validity period</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {updateType === 'extend' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Extension Period (Months)
                                </label>
                                <input
                                    type="number"
                                    value={extensionMonths}
                                    onChange={(e) => setExtensionMonths(parseInt(e.target.value))}
                                    min="1"
                                    max="60"
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        )}

                        {updateType === 'set_date' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    New Expiry Date
                                </label>
                                <input
                                    type="date"
                                    value={newExpiryDate}
                                    onChange={(e) => setNewExpiryDate(e.target.value)}
                                    min={new Date().toISOString().split('T')[0]}
                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        )}

                        <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                            <div className="flex">
                                <InformationCircleIcon className="h-5 w-5 text-blue-400 mt-0.5 mr-2 flex-shrink-0" />
                                <div className="text-sm text-blue-700">
                                    This will update expiry dates for {selectedItems.length} certificates.
                                    Notifications will be recalculated based on new dates.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-200">
                    <button
                        onClick={onCancel}
                        disabled={processing}
                        className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleSubmit}
                        disabled={!canSubmit() || processing}
                        className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {processing ? 'Updating...' : `Update ${selectedItems.length} Items`}
                    </button>
                </div>
            </div>
        </div>
    );
};

const BulkTagsModal = ({ selectedItems, availableTags, onConfirm, onCancel }) => {
    const [selectedTags, setSelectedTags] = useState([]);
    const [newTag, setNewTag] = useState('');
    const [tagAction, setTagAction] = useState('add'); // add, remove, replace
    const [processing, setProcessing] = useState(false);

    const handleTagToggle = (tagId) => {
        setSelectedTags(prev => 
            prev.includes(tagId) 
                ? prev.filter(id => id !== tagId)
                : [...prev, tagId]
        );
    };

    const handleAddNewTag = () => {
        if (newTag.trim()) {
            // This would typically create a new tag via API
            setNewTag('');
        }
    };

    const handleSubmit = async () => {
        if (selectedTags.length === 0) return;
        
        setProcessing(true);
        try {
            await onConfirm({
                action: 'assign_tags',
                data: { 
                    tag_ids: selectedTags,
                    tag_action: tagAction,
                    new_tag: newTag.trim() || null
                },
                items: selectedItems
            });
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[80vh] overflow-y-auto">
                <div className="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 className="text-lg font-medium text-gray-900">
                        Manage Tags for {selectedItems.length} Items
                    </h3>
                    <button
                        onClick={onCancel}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <XMarkIcon className="w-6 h-6" />
                    </button>
                </div>

                <div className="p-6">
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Tag Action
                            </label>
                            <div className="space-y-2">
                                {[
                                    { value: 'add', label: 'Add tags to selected items' },
                                    { value: 'remove', label: 'Remove tags from selected items' },
                                    { value: 'replace', label: 'Replace all tags with selected ones' }
                                ].map((option) => (
                                    <label key={option.value} className="flex items-center">
                                        <input
                                            type="radio"
                                            name="tagAction"
                                            value={option.value}
                                            checked={tagAction === option.value}
                                            onChange={(e) => setTagAction(e.target.value)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">{option.label}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Create New Tag
                            </label>
                            <div className="flex space-x-2">
                                <input
                                    type="text"
                                    value={newTag}
                                    onChange={(e) => setNewTag(e.target.value)}
                                    placeholder="Enter new tag name..."
                                    className="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                                />
                                <button
                                    onClick={handleAddNewTag}
                                    disabled={!newTag.trim()}
                                    className="px-3 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 disabled:opacity-50"
                                >
                                    Add
                                </button>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Available Tags
                            </label>
                            <div className="max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2">
                                {availableTags.length === 0 ? (
                                    <p className="text-sm text-gray-500 text-center py-4">
                                        No tags available. Create a new tag above.
                                    </p>
                                ) : (
                                    <div className="space-y-1">
                                        {availableTags.map((tag) => (
                                            <label key={tag.id} className="flex items-center p-2 hover:bg-gray-50 rounded">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedTags.includes(tag.id)}
                                                    onChange={() => handleTagToggle(tag.id)}
                                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                />
                                                <span 
                                                    className="ml-2 px-2 py-1 text-xs rounded-full"
                                                    style={{ backgroundColor: tag.color || '#e5e7eb', color: tag.text_color || '#374151' }}
                                                >
                                                    {tag.name}
                                                </span>
                                                <span className="ml-2 text-xs text-gray-500">
                                                    ({tag.usage_count || 0} items)
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-200">
                    <button
                        onClick={onCancel}
                        disabled={processing}
                        className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleSubmit}
                        disabled={selectedTags.length === 0 || processing}
                        className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {processing ? 'Processing...' : `Apply to ${selectedItems.length} Items`}
                    </button>
                </div>
            </div>
        </div>
    );
};

const BulkProgressModal = ({ operation, progress, onClose, onCancel }) => {
    const { total, completed, failed, current_item, status, errors } = progress;
    const percentage = total > 0 ? (completed / total) * 100 : 0;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div className="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 className="text-lg font-medium text-gray-900">
                        {operation.title}
                    </h3>
                    {status === 'completed' && (
                        <button
                            onClick={onClose}
                            className="text-gray-400 hover:text-gray-600"
                        >
                            <XMarkIcon className="w-6 h-6" />
                        </button>
                    )}
                </div>

                <div className="p-6">
                    <div className="space-y-4">
                        {/* Progress Bar */}
                        <div>
                            <div className="flex justify-between items-center mb-2">
                                <span className="text-sm font-medium text-gray-700">
                                    Progress
                                </span>
                                <span className="text-sm text-gray-600">
                                    {completed}/{total} ({percentage.toFixed(1)}%)
                                </span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    className={`h-2 rounded-full transition-all duration-300 ${
                                        status === 'completed' ? 'bg-green-500' : 
                                        status === 'error' ? 'bg-red-500' : 'bg-blue-500'
                                    }`}
                                    style={{ width: `${Math.min(percentage, 100)}%` }}
                                />
                            </div>
                        </div>

                        {/* Current Status */}
                        <div className="text-sm text-gray-600">
                            {status === 'processing' && current_item && (
                                <p>Processing: {current_item}</p>
                            )}
                            {status === 'completed' && (
                                <p className="text-green-600">Operation completed successfully!</p>
                            )}
                            {status === 'error' && (
                                <p className="text-red-600">Operation failed with errors.</p>
                            )}
                        </div>

                        {/* Statistics */}
                        <div className="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div className="text-lg font-semibold text-green-600">{completed}</div>
                                <div className="text-xs text-gray-500">Completed</div>
                            </div>
                            <div>
                                <div className="text-lg font-semibold text-red-600">{failed}</div>
                                <div className="text-xs text-gray-500">Failed</div>
                            </div>
                            <div>
                                <div className="text-lg font-semibold text-gray-600">{total - completed}</div>
                                <div className="text-xs text-gray-500">Remaining</div>
                            </div>
                        </div>

                        {/* Errors */}
                        {errors && errors.length > 0 && (
                            <div className="bg-red-50 border border-red-200 rounded-md p-3">
                                <div className="flex">
                                    <ExclamationTriangleIcon className="h-5 w-5 text-red-400 mt-0.5 mr-2 flex-shrink-0" />
                                    <div className="text-sm text-red-700">
                                        <p className="font-medium mb-2">Errors encountered:</p>
                                        <ul className="list-disc list-inside space-y-1 max-h-32 overflow-y-auto">
                                            {errors.slice(0, 10).map((error, index) => (
                                                <li key={index}>{error}</li>
                                            ))}
                                            {errors.length > 10 && (
                                                <li>... and {errors.length - 10} more errors</li>
                                            )}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-200">
                    {status === 'processing' && (
                        <button
                            onClick={onCancel}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            Cancel Operation
                        </button>
                    )}
                    {status === 'completed' && (
                        <button
                            onClick={onClose}
                            className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
                        >
                            Close
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
};

export default function BulkOperations({ 
    selectedItems = [], 
    itemType = 'certificates', // certificates, employees, containers
    onSelectionChange, 
    onOperationComplete,
    availableTags = [],
    className = "" 
}) {
    const [currentModal, setCurrentModal] = useState(null);
    const [bulkProgress, setBulkProgress] = useState(null);
    const [availableActions, setAvailableActions] = useState([]);

    useEffect(() => {
        // Set available actions based on item type and selection
        const actions = getAvailableActions(itemType, selectedItems);
        setAvailableActions(actions);
    }, [itemType, selectedItems]);

    const getAvailableActions = (type, items) => {
        const baseActions = [
            {
                key: 'export_data',
                label: 'Export Data',
                description: 'CSV/Excel',
                disabled: items.length === 0
            }
        ];

        if (type === 'certificates') {
            return [
                ...baseActions,
                {
                    key: 'update_status',
                    label: 'Update Status',
                    description: 'Bulk status change',
                    disabled: items.length === 0
                },
                {
                    key: 'update_expiry_dates',
                    label: 'Update Expiry Dates',
                    description: 'Extend or set dates',
                    disabled: items.length === 0
                },
                {
                    key: 'assign_tags',
                    label: 'Manage Tags',
                    description: 'Add/remove tags',
                    disabled: items.length === 0
                },
                {
                    key: 'send_notifications',
                    label: 'Send Notifications',
                    description: 'Email reminders',
                    disabled: items.length === 0
                },
                {
                    key: 'delete_certificates',
                    label: 'Delete Certificates',
                    description: 'Permanent deletion',
                    disabled: items.length === 0
                }
            ];
        }

        if (type === 'employees') {
            return [
                ...baseActions,
                {
                    key: 'bulk_upload',
                    label: 'Bulk Upload Files',
                    description: 'Upload to selected',
                    disabled: items.length === 0
                },
                {
                    key: 'generate_report',
                    label: 'Generate Report',
                    description: 'Compliance report',
                    disabled: items.length === 0
                },
                {
                    key: 'assign_tags',
                    label: 'Manage Tags',
                    description: 'Add/remove tags',
                    disabled: items.length === 0
                }
            ];
        }

        return baseActions;
    };

    const handleActionSelect = (action) => {
        switch (action.key) {
            case 'update_status':
                setCurrentModal('status_update');
                break;
            case 'update_expiry_dates':
                setCurrentModal('expiry_update');
                break;
            case 'assign_tags':
                setCurrentModal('tags');
                break;
            case 'export_data':
                handleExportData();
                break;
            case 'send_notifications':
                handleSendNotifications();
                break;
            case 'delete_certificates':
                handleDeleteConfirmation();
                break;
            default:
                console.log('Unhandled action:', action.key);
        }
    };

    const handleBulkOperation = async (operation) => {
        setCurrentModal(null);
        
        try {
            // Initialize progress tracking
            setBulkProgress({
                total: selectedItems.length,
                completed: 0,
                failed: 0,
                current_item: null,
                status: 'processing',
                errors: []
            });

            const response = await fetch('/api/bulk-operations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    operation: operation.action,
                    item_type: itemType,
                    items: operation.items,
                    data: operation.data
                })
            });

            if (!response.ok) {
                throw new Error('Bulk operation failed');
            }

            const result = await response.json();
            
            // Update progress to completed
            setBulkProgress(prev => ({
                ...prev,
                completed: result.successful || prev.total,
                failed: result.failed || 0,
                status: 'completed',
                errors: result.errors || []
            }));

            if (onOperationComplete) {
                onOperationComplete(result);
            }

            // Clear selection after successful operation
            if (onSelectionChange) {
                onSelectionChange([]);
            }

        } catch (error) {
            console.error('Bulk operation error:', error);
            setBulkProgress(prev => ({
                ...prev,
                status: 'error',
                errors: [error.message]
            }));
        }
    };

    const handleExportData = async () => {
        try {
            const params = new URLSearchParams({
                item_type: itemType,
                items: selectedItems.map(item => item.id).join(','),
                format: 'xlsx'
            });

            const response = await fetch(`/api/export-data?${params}`);
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${itemType}_export_${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }
        } catch (error) {
            console.error('Export failed:', error);
        }
    };

    const handleSendNotifications = async () => {
        if (confirm(`Send expiry notifications for ${selectedItems.length} certificates?`)) {
            await handleBulkOperation({
                action: 'send_notifications',
                items: selectedItems,
                data: { notification_type: 'expiry_reminder' }
            });
        }
    };

    const handleDeleteConfirmation = () => {
        if (confirm(`Are you sure you want to delete ${selectedItems.length} certificates? This action cannot be undone.`)) {
            handleBulkOperation({
                action: 'delete_certificates',
                items: selectedItems,
                data: { permanent: false } // Move to trash instead of permanent delete
            });
        }
    };

    const handleCloseProgress = () => {
        setBulkProgress(null);
        // Refresh the page or update data
        if (onOperationComplete) {
            onOperationComplete({ refresh: true });
        }
    };

    return (
        <div className={className}>
            <BulkActionSelector
                selectedCount={selectedItems.length}
                onActionSelect={handleActionSelect}
                availableActions={availableActions}
            />

            {/* Modals */}
            {currentModal === 'status_update' && (
                <BulkStatusUpdateModal
                    selectedItems={selectedItems}
                    onConfirm={handleBulkOperation}
                    onCancel={() => setCurrentModal(null)}
                />
            )}

            {currentModal === 'expiry_update' && (
                <BulkExpiryUpdateModal
                    selectedItems={selectedItems}
                    onConfirm={handleBulkOperation}
                    onCancel={() => setCurrentModal(null)}
                />
            )}

            {currentModal === 'tags' && (
                <BulkTagsModal
                    selectedItems={selectedItems}
                    availableTags={availableTags}
                    onConfirm={handleBulkOperation}
                    onCancel={() => setCurrentModal(null)}
                />
            )}

            {bulkProgress && (
                <BulkProgressModal
                    operation={{ title: 'Bulk Operation in Progress' }}
                    progress={bulkProgress}
                    onClose={handleCloseProgress}
                    onCancel={() => setBulkProgress(null)}
                />
            )}
        </div>
    );
}

BulkActionSelector.propTypes = {
    selectedCount: PropTypes.number.isRequired,
    onActionSelect: PropTypes.func.isRequired,
    availableActions: PropTypes.array.isRequired
};

BulkStatusUpdateModal.propTypes = {
    selectedItems: PropTypes.array.isRequired,
    onConfirm: PropTypes.func.isRequired,
    onCancel: PropTypes.func.isRequired
};

BulkExpiryUpdateModal.propTypes = {
    selectedItems: PropTypes.array.isRequired,
    onConfirm: PropTypes.func.isRequired,
    onCancel: PropTypes.func.isRequired
};

BulkTagsModal.propTypes = {
    selectedItems: PropTypes.array.isRequired,
    availableTags: PropTypes.array.isRequired,
    onConfirm: PropTypes.func.isRequired,
    onCancel: PropTypes.func.isRequired
};

BulkProgressModal.propTypes = {
    operation: PropTypes.object.isRequired,
    progress: PropTypes.object.isRequired,
    onClose: PropTypes.func.isRequired,
    onCancel: PropTypes.func.isRequired
};

BulkOperations.propTypes = {
    selectedItems: PropTypes.array,
    itemType: PropTypes.oneOf(['certificates', 'employees', 'containers']),
    onSelectionChange: PropTypes.func,
    onOperationComplete: PropTypes.func,
    availableTags: PropTypes.array,
    className: PropTypes.string
};