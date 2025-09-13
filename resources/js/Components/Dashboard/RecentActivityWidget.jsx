import { useState, useEffect } from 'react';
import { 
    ClockIcon,
    UserIcon,
    DocumentCheckIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowRightIcon
} from '@heroicons/react/24/outline';

/**
 * RecentActivityWidget - Shows recent system activity and changes
 * Features real-time updates, activity filtering, and action shortcuts
 */
export default function RecentActivityWidget({
    className = '',
    autoRefresh = true,
    refreshInterval = 30000,
    maxItems = 10,
    showFilters = false,
    onActivityClick
}) {
    const [activityData, setActivityData] = useState({
        activities: [],
        total_count: 0,
        last_updated: null
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filter, setFilter] = useState('all');

    const activityTypes = {
        all: 'All Activities',
        certificate_added: 'Certificates Added',
        certificate_expired: 'Certificates Expired',
        employee_added: 'Employees Added',
        background_check: 'Background Checks'
    };

    useEffect(() => {
        fetchActivityData();
        
        if (autoRefresh) {
            const interval = setInterval(fetchActivityData, refreshInterval);
            return () => clearInterval(interval);
        }
    }, [autoRefresh, refreshInterval, filter]);

    const fetchActivityData = async () => {
        try {
            const params = new URLSearchParams({
                limit: maxItems,
                type: filter !== 'all' ? filter : ''
            });

            const response = await fetch(`/api/dashboard/recent-activity?${params}`);
            if (!response.ok) throw new Error('Failed to fetch activity data');
            
            const data = await response.json();
            setActivityData(data);
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const getActivityIcon = (type) => {
        const icons = {
            certificate_added: CheckCircleIcon,
            certificate_expired: XCircleIcon,
            certificate_expiring: ExclamationTriangleIcon,
            employee_added: UserIcon,
            employee_updated: UserIcon,
            background_check_cleared: CheckCircleIcon,
            background_check_pending: ClockIcon,
            training_completed: DocumentCheckIcon,
            system_backup: ClockIcon
        };
        
        const Icon = icons[type] || ClockIcon;
        return <Icon className="w-4 h-4" />;
    };

    const getActivityColor = (type) => {
        const colors = {
            certificate_added: 'text-green-600 bg-green-100',
            certificate_expired: 'text-red-600 bg-red-100',
            certificate_expiring: 'text-yellow-600 bg-yellow-100',
            employee_added: 'text-blue-600 bg-blue-100',
            employee_updated: 'text-blue-600 bg-blue-100',
            background_check_cleared: 'text-green-600 bg-green-100',
            background_check_pending: 'text-yellow-600 bg-yellow-100',
            training_completed: 'text-green-600 bg-green-100',
            system_backup: 'text-gray-600 bg-gray-100'
        };
        
        return colors[type] || 'text-gray-600 bg-gray-100';
    };

    const formatTimeAgo = (dateString) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
        
        return date.toLocaleDateString();
    };

    const getActivityTitle = (activity) => {
        const titles = {
            certificate_added: 'Certificate Added',
            certificate_expired: 'Certificate Expired',
            certificate_expiring: 'Certificate Expiring Soon',
            employee_added: 'New Employee Added',
            employee_updated: 'Employee Updated',
            background_check_cleared: 'Background Check Cleared',
            background_check_pending: 'Background Check Pending',
            training_completed: 'Training Completed',
            system_backup: 'System Backup'
        };
        
        return titles[activity.type] || 'System Activity';
    };

    const ActivityItem = ({ activity, index }) => (
        <div 
            key={activity.id || index}
            className={`
                flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors
                ${onActivityClick ? 'cursor-pointer' : ''}
            `}
            onClick={() => onActivityClick?.(activity)}
        >
            {/* Activity Icon */}
            <div className={`
                flex-shrink-0 p-1.5 rounded-lg
                ${getActivityColor(activity.type)}
            `}>
                {getActivityIcon(activity.type)}
            </div>

            {/* Activity Content */}
            <div className="flex-1 min-w-0">
                <div className="flex items-start justify-between">
                    <div className="flex-1">
                        <p className="text-sm font-medium text-gray-900">
                            {getActivityTitle(activity)}
                        </p>
                        <p className="text-sm text-gray-600 mt-1">
                            {activity.description}
                        </p>
                        
                        {/* Additional details */}
                        {activity.details && (
                            <div className="mt-2 text-xs text-gray-500 bg-gray-50 p-2 rounded">
                                {typeof activity.details === 'string' 
                                    ? activity.details 
                                    : JSON.stringify(activity.details)
                                }
                            </div>
                        )}
                    </div>
                    
                    <div className="flex-shrink-0 ml-2">
                        <span className="text-xs text-gray-500">
                            {formatTimeAgo(activity.created_at)}
                        </span>
                    </div>
                </div>

                {/* User and entity info */}
                {(activity.user_name || activity.entity_name) && (
                    <div className="flex items-center space-x-2 mt-2 text-xs text-gray-500">
                        {activity.user_name && (
                            <span>by {activity.user_name}</span>
                        )}
                        {activity.entity_name && (
                            <span>• {activity.entity_name}</span>
                        )}
                    </div>
                )}
            </div>

            {/* Action indicator */}
            {onActivityClick && (
                <div className="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                    <ArrowRightIcon className="w-4 h-4 text-gray-400" />
                </div>
            )}
        </div>
    );

    return (
        <div className={`bg-white rounded-lg border border-gray-200 ${className}`}>
            <div className="p-4 border-b border-gray-200">
                <div className="flex items-center justify-between">
                    <h3 className="text-lg font-semibold text-gray-900">Recent Activity</h3>
                    
                    <div className="flex items-center space-x-2">
                        {activityData.total_count > 0 && (
                            <span className="text-sm text-gray-500">
                                {activityData.total_count} total
                            </span>
                        )}
                        
                        {showFilters && (
                            <select
                                value={filter}
                                onChange={(e) => setFilter(e.target.value)}
                                className="text-sm border border-gray-300 rounded px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                {Object.entries(activityTypes).map(([key, label]) => (
                                    <option key={key} value={key}>{label}</option>
                                ))}
                            </select>
                        )}
                    </div>
                </div>
            </div>

            <div className="max-h-96 overflow-y-auto">
                {error ? (
                    <div className="p-4 text-center">
                        <ExclamationTriangleIcon className="w-8 h-8 text-red-500 mx-auto mb-2" />
                        <p className="text-sm text-red-600">{error}</p>
                        <button
                            onClick={fetchActivityData}
                            className="mt-2 text-sm text-blue-600 hover:text-blue-800"
                        >
                            Try again
                        </button>
                    </div>
                ) : loading ? (
                    <div className="p-4">
                        {/* Loading skeleton */}
                        {Array.from({ length: 3 }).map((_, index) => (
                            <div key={index} className="flex items-start space-x-3 p-3 animate-pulse">
                                <div className="w-8 h-8 bg-gray-200 rounded-lg"></div>
                                <div className="flex-1 space-y-2">
                                    <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                                    <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                                <div className="h-3 bg-gray-200 rounded w-16"></div>
                            </div>
                        ))}
                    </div>
                ) : activityData.activities.length === 0 ? (
                    <div className="p-8 text-center">
                        <ClockIcon className="w-8 h-8 text-gray-400 mx-auto mb-2" />
                        <p className="text-sm text-gray-500">No recent activity</p>
                    </div>
                ) : (
                    <div className="p-2 space-y-1">
                        {activityData.activities.map((activity, index) => (
                            <ActivityItem key={activity.id || index} activity={activity} index={index} />
                        ))}
                    </div>
                )}
            </div>

            {/* Footer */}
            {activityData.activities.length > 0 && (
                <div className="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <div className="flex items-center justify-between">
                        {activityData.last_updated && (
                            <span className="text-xs text-gray-500">
                                Last updated: {new Date(activityData.last_updated).toLocaleTimeString()}
                            </span>
                        )}
                        
                        {activityData.total_count > maxItems && (
                            <button
                                onClick={() => onActivityClick?.({ type: 'view_all' })}
                                className="text-xs text-blue-600 hover:text-blue-800 font-medium"
                            >
                                View all activity
                            </button>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}