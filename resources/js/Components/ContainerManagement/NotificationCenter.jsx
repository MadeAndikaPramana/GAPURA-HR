// components/ContainerManagement/NotificationCenter.jsx
import { useState, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import PropTypes from 'prop-types';
import {
    BellIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    InformationCircleIcon,
    CheckCircleIcon,
    XMarkIcon,
    EyeIcon,
    CalendarIcon,
    UserIcon,
    AcademicCapIcon,
    ClockIcon,
    ArrowTopRightOnSquareIcon,
    Cog6ToothIcon,
    BellSlashIcon,
    FunnelIcon
} from '@heroicons/react/24/outline';
import { BellIcon as BellSolidIcon } from '@heroicons/react/24/solid';

const NotificationItem = ({ notification, onMarkRead, onAction, onDismiss }) => {
    const getNotificationIcon = (type, priority) => {
        const iconClasses = {
            'certificate_expiring': 'text-yellow-600',
            'certificate_expired': 'text-red-600',
            'background_check_expiring': 'text-yellow-600', 
            'background_check_expired': 'text-red-600',
            'compliance_issue': 'text-red-600',
            'document_uploaded': 'text-green-600',
            'system_alert': 'text-blue-600',
            'reminder': 'text-purple-600'
        };

        const icons = {
            'certificate_expiring': ExclamationTriangleIcon,
            'certificate_expired': XCircleIcon,
            'background_check_expiring': ExclamationTriangleIcon,
            'background_check_expired': XCircleIcon,
            'compliance_issue': ExclamationTriangleIcon,
            'document_uploaded': CheckCircleIcon,
            'system_alert': InformationCircleIcon,
            'reminder': ClockIcon
        };

        const IconComponent = icons[type] || InformationCircleIcon;
        const iconClass = iconClasses[type] || 'text-gray-600';

        return <IconComponent className={`w-5 h-5 ${iconClass}`} />;
    };

    const getPriorityBadge = (priority) => {
        const badges = {
            high: 'bg-red-100 text-red-800',
            medium: 'bg-yellow-100 text-yellow-800',
            low: 'bg-blue-100 text-blue-800'
        };

        return (
            <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${
                badges[priority] || badges.medium
            }`}>
                {priority.charAt(0).toUpperCase() + priority.slice(1)}
            </span>
        );
    };

    const formatRelativeTime = (dateString) => {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes} min ago`;
        if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        if (days < 7) return `${days} day${days > 1 ? 's' : ''} ago`;
        
        return date.toLocaleDateString();
    };

    const handleAction = (actionType) => {
        if (onAction) {
            onAction(notification, actionType);
        }
    };

    return (
        <div className={`p-4 border-b border-gray-200 hover:bg-gray-50 transition-colors ${
            !notification.is_read ? 'bg-blue-50 border-l-4 border-l-blue-500' : ''
        }`}>
            <div className="flex items-start space-x-3">
                {/* Icon */}
                <div className="flex-shrink-0 mt-1">
                    {getNotificationIcon(notification.type, notification.priority)}
                </div>

                {/* Content */}
                <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between">
                        <div className="flex-1">
                            <p className={`text-sm ${!notification.is_read ? 'font-semibold text-gray-900' : 'text-gray-800'}`}>
                                {notification.title}
                            </p>
                            <p className="text-sm text-gray-600 mt-1">
                                {notification.message}
                            </p>
                            
                            {/* Metadata */}
                            <div className="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                <span className="flex items-center">
                                    <ClockIcon className="w-3 h-3 mr-1" />
                                    {formatRelativeTime(notification.created_at)}
                                </span>
                                
                                {notification.employee && (
                                    <span className="flex items-center">
                                        <UserIcon className="w-3 h-3 mr-1" />
                                        {notification.employee.name}
                                    </span>
                                )}
                                
                                {notification.certificate_type && (
                                    <span className="flex items-center">
                                        <AcademicCapIcon className="w-3 h-3 mr-1" />
                                        {notification.certificate_type.name}
                                    </span>
                                )}
                                
                                {notification.expiry_date && (
                                    <span className="flex items-center">
                                        <CalendarIcon className="w-3 h-3 mr-1" />
                                        Expires: {new Date(notification.expiry_date).toLocaleDateString()}
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center space-x-2 ml-4">
                            {getPriorityBadge(notification.priority)}
                            
                            {!notification.is_read && (
                                <div className="w-2 h-2 bg-blue-600 rounded-full" />
                            )}
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center space-x-3 mt-3">
                        {!notification.is_read && (
                            <button
                                onClick={() => onMarkRead(notification.id)}
                                className="text-xs text-blue-600 hover:text-blue-800 flex items-center"
                            >
                                <EyeIcon className="w-3 h-3 mr-1" />
                                Mark as read
                            </button>
                        )}

                        {notification.action_url && (
                            <button
                                onClick={() => handleAction('view')}
                                className="text-xs text-green-600 hover:text-green-800 flex items-center"
                            >
                                <ArrowTopRightOnSquareIcon className="w-3 h-3 mr-1" />
                                View Details
                            </button>
                        )}

                        {(notification.type.includes('expir') || notification.type.includes('compliance')) && (
                            <button
                                onClick={() => handleAction('renew')}
                                className="text-xs text-purple-600 hover:text-purple-800 flex items-center"
                            >
                                <CalendarIcon className="w-3 h-3 mr-1" />
                                Schedule Renewal
                            </button>
                        )}

                        <button
                            onClick={() => onDismiss(notification.id)}
                            className="text-xs text-gray-500 hover:text-gray-700 flex items-center"
                        >
                            <XMarkIcon className="w-3 h-3 mr-1" />
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

const NotificationFilters = ({ filters, onFilterChange }) => {
    const [showFilters, setShowFilters] = useState(false);

    const notificationTypes = [
        { value: 'certificate_expiring', label: 'Certificate Expiring' },
        { value: 'certificate_expired', label: 'Certificate Expired' },
        { value: 'background_check_expiring', label: 'Background Check Expiring' },
        { value: 'background_check_expired', label: 'Background Check Expired' },
        { value: 'compliance_issue', label: 'Compliance Issues' },
        { value: 'document_uploaded', label: 'Document Uploaded' },
        { value: 'system_alert', label: 'System Alerts' },
        { value: 'reminder', label: 'Reminders' }
    ];

    const priorities = [
        { value: 'high', label: 'High Priority' },
        { value: 'medium', label: 'Medium Priority' },
        { value: 'low', label: 'Low Priority' }
    ];

    return (
        <div className="border-b border-gray-200">
            <div className="p-4">
                <button
                    onClick={() => setShowFilters(!showFilters)}
                    className="flex items-center text-sm text-gray-600 hover:text-gray-800"
                >
                    <FunnelIcon className="w-4 h-4 mr-2" />
                    Filters
                    {(filters.types.length > 0 || filters.priorities.length > 0 || filters.unread_only) && (
                        <span className="ml-2 px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full">
                            {filters.types.length + filters.priorities.length + (filters.unread_only ? 1 : 0)}
                        </span>
                    )}
                </button>

                {showFilters && (
                    <div className="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Notification Types
                            </label>
                            <div className="space-y-2 max-h-32 overflow-y-auto">
                                {notificationTypes.map((type) => (
                                    <label key={type.value} className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={filters.types.includes(type.value)}
                                            onChange={(e) => {
                                                const newTypes = e.target.checked
                                                    ? [...filters.types, type.value]
                                                    : filters.types.filter(t => t !== type.value);
                                                onFilterChange({ ...filters, types: newTypes });
                                            }}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">{type.label}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Priority
                            </label>
                            <div className="space-y-2">
                                {priorities.map((priority) => (
                                    <label key={priority.value} className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={filters.priorities.includes(priority.value)}
                                            onChange={(e) => {
                                                const newPriorities = e.target.checked
                                                    ? [...filters.priorities, priority.value]
                                                    : filters.priorities.filter(p => p !== priority.value);
                                                onFilterChange({ ...filters, priorities: newPriorities });
                                            }}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">{priority.label}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Status
                            </label>
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={filters.unread_only}
                                    onChange={(e) => onFilterChange({ ...filters, unread_only: e.target.checked })}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <span className="ml-2 text-sm text-gray-700">Unread only</span>
                            </label>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

const NotificationSettings = ({ settings, onSettingsChange, onClose }) => {
    const [localSettings, setLocalSettings] = useState(settings);

    const handleSave = () => {
        onSettingsChange(localSettings);
        onClose();
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[80vh] overflow-y-auto">
                <div className="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 className="text-lg font-medium text-gray-900">Notification Settings</h3>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <XMarkIcon className="w-6 h-6" />
                    </button>
                </div>

                <div className="p-6 space-y-6">
                    <div>
                        <h4 className="text-sm font-medium text-gray-900 mb-3">Email Notifications</h4>
                        <div className="space-y-3">
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={localSettings.email_certificate_expiring}
                                    onChange={(e) => setLocalSettings({
                                        ...localSettings,
                                        email_certificate_expiring: e.target.checked
                                    })}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <span className="ml-2 text-sm text-gray-700">Certificate expiring reminders</span>
                            </label>

                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={localSettings.email_compliance_issues}
                                    onChange={(e) => setLocalSettings({
                                        ...localSettings,
                                        email_compliance_issues: e.target.checked
                                    })}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <span className="ml-2 text-sm text-gray-700">Compliance issues</span>
                            </label>

                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={localSettings.email_weekly_summary}
                                    onChange={(e) => setLocalSettings({
                                        ...localSettings,
                                        email_weekly_summary: e.target.checked
                                    })}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <span className="ml-2 text-sm text-gray-700">Weekly summary reports</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <h4 className="text-sm font-medium text-gray-900 mb-3">Reminder Timing</h4>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs text-gray-600 mb-1">
                                    First reminder (days before expiry)
                                </label>
                                <input
                                    type="number"
                                    value={localSettings.first_reminder_days}
                                    onChange={(e) => setLocalSettings({
                                        ...localSettings,
                                        first_reminder_days: parseInt(e.target.value)
                                    })}
                                    min="1"
                                    max="365"
                                    className="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs text-gray-600 mb-1">
                                    Final reminder (days before expiry)
                                </label>
                                <input
                                    type="number"
                                    value={localSettings.final_reminder_days}
                                    onChange={(e) => setLocalSettings({
                                        ...localSettings,
                                        final_reminder_days: parseInt(e.target.value)
                                    })}
                                    min="1"
                                    max="365"
                                    className="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 className="text-sm font-medium text-gray-900 mb-3">Browser Notifications</h4>
                        <label className="flex items-center">
                            <input
                                type="checkbox"
                                checked={localSettings.browser_notifications}
                                onChange={(e) => setLocalSettings({
                                    ...localSettings,
                                    browser_notifications: e.target.checked
                                })}
                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <span className="ml-2 text-sm text-gray-700">Enable browser push notifications</span>
                        </label>
                    </div>
                </div>

                <div className="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-200">
                    <button
                        onClick={onClose}
                        className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleSave}
                        className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
                    >
                        Save Settings
                    </button>
                </div>
            </div>
        </div>
    );
};

export default function NotificationCenter({ 
    userId, 
    initialUnreadCount = 0,
    onNotificationCountChange,
    className = "" 
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(initialUnreadCount);
    const [loading, setLoading] = useState(false);
    const [filters, setFilters] = useState({
        types: [],
        priorities: [],
        unread_only: false
    });
    const [settings, setSettings] = useState({
        email_certificate_expiring: true,
        email_compliance_issues: true,
        email_weekly_summary: false,
        first_reminder_days: 30,
        final_reminder_days: 7,
        browser_notifications: false
    });
    const [showSettings, setShowSettings] = useState(false);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);

    const dropdownRef = useRef(null);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        if (isOpen) {
            fetchNotifications();
            loadUserSettings();
        }
    }, [isOpen, filters]);

    useEffect(() => {
        // Set up periodic refresh for notifications
        const interval = setInterval(fetchUnreadCount, 60000); // Every minute
        return () => clearInterval(interval);
    }, []);

    useEffect(() => {
        if (onNotificationCountChange) {
            onNotificationCountChange(unreadCount);
        }
    }, [unreadCount, onNotificationCountChange]);

    const fetchNotifications = async (pageNum = 1) => {
        setLoading(true);
        
        try {
            const params = new URLSearchParams({
                page: pageNum,
                per_page: 20,
                ...filters.types.length > 0 && { types: filters.types.join(',') },
                ...filters.priorities.length > 0 && { priorities: filters.priorities.join(',') },
                ...filters.unread_only && { unread_only: 'true' }
            });

            const response = await fetch(`/api/notifications?${params}`);
            const data = await response.json();
            
            if (pageNum === 1) {
                setNotifications(data.data);
            } else {
                setNotifications(prev => [...prev, ...data.data]);
            }
            
            setHasMore(data.current_page < data.last_page);
            setPage(pageNum);
            
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchUnreadCount = async () => {
        try {
            const response = await fetch('/api/notifications/unread-count');
            const data = await response.json();
            setUnreadCount(data.count);
        } catch (error) {
            console.error('Failed to fetch unread count:', error);
        }
    };

    const loadUserSettings = async () => {
        try {
            const response = await fetch('/api/notifications/settings');
            const data = await response.json();
            setSettings(data);
        } catch (error) {
            console.error('Failed to load user settings:', error);
        }
    };

    const handleMarkAsRead = async (notificationId) => {
        try {
            await router.post(`/api/notifications/${notificationId}/read`);
            
            setNotifications(prev => prev.map(n => 
                n.id === notificationId ? { ...n, is_read: true } : n
            ));
            setUnreadCount(prev => Math.max(0, prev - 1));
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };

    const handleMarkAllAsRead = async () => {
        try {
            await router.post('/api/notifications/mark-all-read');
            
            setNotifications(prev => prev.map(n => ({ ...n, is_read: true })));
            setUnreadCount(0);
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    };

    const handleDismiss = async (notificationId) => {
        try {
            await router.delete(`/api/notifications/${notificationId}`);
            
            setNotifications(prev => prev.filter(n => n.id !== notificationId));
            
            const notification = notifications.find(n => n.id === notificationId);
            if (notification && !notification.is_read) {
                setUnreadCount(prev => Math.max(0, prev - 1));
            }
        } catch (error) {
            console.error('Failed to dismiss notification:', error);
        }
    };

    const handleAction = (notification, actionType) => {
        switch (actionType) {
            case 'view':
                if (notification.action_url) {
                    router.visit(notification.action_url);
                    setIsOpen(false);
                }
                break;
            case 'renew':
                // Navigate to certificate renewal or scheduling
                router.visit(`/employee-containers/${notification.employee_id}`);
                setIsOpen(false);
                break;
            default:
                break;
        }
    };

    const handleSettingsChange = async (newSettings) => {
        try {
            await router.post('/api/notifications/settings', newSettings);
            setSettings(newSettings);
        } catch (error) {
            console.error('Failed to save notification settings:', error);
        }
    };

    const handleLoadMore = () => {
        if (!loading && hasMore) {
            fetchNotifications(page + 1);
        }
    };

    return (
        <div className={`relative ${className}`} ref={dropdownRef}>
            {/* Notification Bell */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-lg"
            >
                {unreadCount > 0 ? (
                    <BellSolidIcon className="w-6 h-6 text-blue-600" />
                ) : (
                    <BellIcon className="w-6 h-6" />
                )}
                
                {unreadCount > 0 && (
                    <span className="absolute top-0 right-0 block h-4 w-4 rounded-full bg-red-500 text-xs font-medium text-white text-center leading-4">
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </span>
                )}
            </button>

            {/* Notification Dropdown */}
            {isOpen && (
                <div className="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-[80vh] overflow-hidden">
                    {/* Header */}
                    <div className="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 className="text-lg font-semibold text-gray-900">
                            Notifications
                            {unreadCount > 0 && (
                                <span className="ml-2 px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">
                                    {unreadCount} new
                                </span>
                            )}
                        </h3>
                        
                        <div className="flex items-center space-x-2">
                            {unreadCount > 0 && (
                                <button
                                    onClick={handleMarkAllAsRead}
                                    className="text-xs text-blue-600 hover:text-blue-800"
                                >
                                    Mark all read
                                </button>
                            )}
                            
                            <button
                                onClick={() => setShowSettings(true)}
                                className="p-1 text-gray-400 hover:text-gray-600"
                                title="Notification settings"
                            >
                                <Cog6ToothIcon className="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    {/* Filters */}
                    <NotificationFilters
                        filters={filters}
                        onFilterChange={setFilters}
                    />

                    {/* Notifications List */}
                    <div className="max-h-96 overflow-y-auto">
                        {loading && notifications.length === 0 ? (
                            <div className="flex items-center justify-center py-8">
                                <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                                <span className="ml-2 text-sm text-gray-600">Loading notifications...</span>
                            </div>
                        ) : notifications.length === 0 ? (
                            <div className="text-center py-8">
                                <BellSlashIcon className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                                <p className="text-sm text-gray-600">No notifications found</p>
                            </div>
                        ) : (
                            <>
                                {notifications.map((notification) => (
                                    <NotificationItem
                                        key={notification.id}
                                        notification={notification}
                                        onMarkRead={handleMarkAsRead}
                                        onAction={handleAction}
                                        onDismiss={handleDismiss}
                                    />
                                ))}
                                
                                {hasMore && (
                                    <div className="p-4 text-center border-t border-gray-200">
                                        <button
                                            onClick={handleLoadMore}
                                            disabled={loading}
                                            className="text-sm text-blue-600 hover:text-blue-800 disabled:opacity-50"
                                        >
                                            {loading ? 'Loading...' : 'Load more notifications'}
                                        </button>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>
            )}

            {/* Settings Modal */}
            {showSettings && (
                <NotificationSettings
                    settings={settings}
                    onSettingsChange={handleSettingsChange}
                    onClose={() => setShowSettings(false)}
                />
            )}
        </div>
    );
}

NotificationItem.propTypes = {
    notification: PropTypes.object.isRequired,
    onMarkRead: PropTypes.func.isRequired,
    onAction: PropTypes.func.isRequired,
    onDismiss: PropTypes.func.isRequired
};

NotificationFilters.propTypes = {
    filters: PropTypes.object.isRequired,
    onFilterChange: PropTypes.func.isRequired
};

NotificationSettings.propTypes = {
    settings: PropTypes.object.isRequired,
    onSettingsChange: PropTypes.func.isRequired,
    onClose: PropTypes.func.isRequired
};

NotificationCenter.propTypes = {
    userId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    initialUnreadCount: PropTypes.number,
    onNotificationCountChange: PropTypes.func,
    className: PropTypes.string
};