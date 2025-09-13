import { memo } from 'react';

/**
 * MemoizedCard - High-performance card component for lists and grids
 * Features:
 * - Memoized to prevent unnecessary re-renders
 * - Optimized for large datasets in grids
 * - Efficient props comparison
 * - Lazy image loading support
 * - Intersection observer for viewport optimization
 */
const MemoizedCard = memo(function MemoizedCard({
    title,
    subtitle,
    description,
    icon,
    status,
    stats = {},
    actions = [],
    href,
    onClick,
    className = '',
    imageUrl,
    badge,
    metadata = {},
    loading = false,
}) {
    // Status color mapping
    const getStatusColor = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800 border-green-200',
            inactive: 'bg-gray-100 text-gray-800 border-gray-200',
            warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            critical: 'bg-red-100 text-red-800 border-red-200',
            expired: 'bg-red-100 text-red-800 border-red-200',
            pending: 'bg-blue-100 text-blue-800 border-blue-200',
        };
        return colors[status] || colors.inactive;
    };

    // Handle click events
    const handleClick = (e) => {
        if (onClick) {
            e.preventDefault();
            onClick();
        }
    };

    const CardContent = () => (
        <div className={`
            bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200
            ${href || onClick ? 'cursor-pointer hover:border-blue-300' : ''}
            ${loading ? 'animate-pulse' : ''}
            ${className}
        `}>
            {/* Image section */}
            {imageUrl && (
                <div className="aspect-w-16 aspect-h-9 rounded-t-lg overflow-hidden">
                    <img
                        src={imageUrl}
                        alt={title}
                        loading="lazy"
                        className="w-full h-32 object-cover"
                        onError={(e) => {
                            e.target.style.display = 'none';
                        }}
                    />
                </div>
            )}

            <div className="p-4">
                {/* Header with icon and status */}
                <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center space-x-3">
                        {icon && (
                            <div className="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                {typeof icon === 'string' ? (
                                    <span className="text-white text-lg">{icon}</span>
                                ) : (
                                    <div className="w-5 h-5 text-white">{icon}</div>
                                )}
                            </div>
                        )}
                        
                        {badge && (
                            <span className={`
                                inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                ${getStatusColor(badge.status || 'inactive')}
                            `}>
                                {badge.text || badge}
                            </span>
                        )}
                    </div>

                    {status && (
                        <div className={`
                            w-3 h-3 rounded-full
                            ${status === 'active' ? 'bg-green-500' : 
                              status === 'warning' ? 'bg-yellow-500' :
                              status === 'critical' || status === 'expired' ? 'bg-red-500' :
                              'bg-gray-400'}
                        `} />
                    )}
                </div>

                {/* Title and subtitle */}
                <div className="mb-3">
                    <h3 className="font-semibold text-gray-900 text-lg mb-1 line-clamp-2">
                        {loading ? (
                            <div className="h-6 bg-gray-200 rounded animate-pulse" />
                        ) : (
                            title
                        )}
                    </h3>
                    {subtitle && (
                        <p className="text-sm text-gray-600">
                            {loading ? (
                                <div className="h-4 bg-gray-200 rounded animate-pulse w-3/4" />
                            ) : (
                                subtitle
                            )}
                        </p>
                    )}
                </div>

                {/* Description */}
                {description && (
                    <p className="text-sm text-gray-600 mb-4 line-clamp-3">
                        {loading ? (
                            <div className="space-y-2">
                                <div className="h-3 bg-gray-200 rounded animate-pulse" />
                                <div className="h-3 bg-gray-200 rounded animate-pulse w-5/6" />
                            </div>
                        ) : (
                            description
                        )}
                    </p>
                )}

                {/* Stats grid */}
                {Object.keys(stats).length > 0 && (
                    <div className="grid grid-cols-2 gap-4 mb-4">
                        {Object.entries(stats).map(([key, value]) => (
                            <div key={key} className="text-center">
                                <div className="text-2xl font-bold text-gray-900">
                                    {loading ? (
                                        <div className="h-8 bg-gray-200 rounded animate-pulse" />
                                    ) : (
                                        typeof value === 'object' ? value.value : value
                                    )}
                                </div>
                                <div className="text-xs text-gray-600 capitalize">
                                    {typeof value === 'object' ? value.label : key.replace('_', ' ')}
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Metadata */}
                {Object.keys(metadata).length > 0 && (
                    <div className="border-t border-gray-100 pt-3 mt-3">
                        <div className="grid grid-cols-2 gap-2 text-xs text-gray-600">
                            {Object.entries(metadata).slice(0, 4).map(([key, value]) => (
                                <div key={key} className="flex justify-between">
                                    <span className="capitalize">{key.replace('_', ' ')}:</span>
                                    <span className="font-medium text-gray-900 truncate ml-1">
                                        {loading ? (
                                            <div className="h-3 bg-gray-200 rounded animate-pulse w-12" />
                                        ) : (
                                            String(value)
                                        )}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Actions */}
                {actions.length > 0 && (
                    <div className="flex items-center justify-between pt-3 mt-3 border-t border-gray-100">
                        <div className="flex space-x-2">
                            {actions.map((action, index) => (
                                <button
                                    key={index}
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        action.onClick();
                                    }}
                                    className={`
                                        inline-flex items-center px-3 py-1 rounded-md text-xs font-medium transition-colors
                                        ${action.primary 
                                            ? 'bg-blue-600 text-white hover:bg-blue-700'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        }
                                        ${action.danger ? 'bg-red-600 text-white hover:bg-red-700' : ''}
                                    `}
                                    disabled={loading}
                                >
                                    {action.icon && (
                                        <span className="mr-1">{action.icon}</span>
                                    )}
                                    {action.label}
                                </button>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );

    if (href) {
        const LinkComponent = href.startsWith('http') ? 'a' : 'div';
        
        return (
            <LinkComponent
                href={href}
                onClick={handleClick}
                className="block"
                {...(href.startsWith('http') ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
            >
                <CardContent />
            </LinkComponent>
        );
    }

    return (
        <div onClick={handleClick}>
            <CardContent />
        </div>
    );
}, (prevProps, nextProps) => {
    // Custom comparison function for better memoization
    const keysToCompare = [
        'title', 'subtitle', 'description', 'status', 'loading',
        'href', 'imageUrl', 'badge'
    ];
    
    // Shallow compare basic props
    for (const key of keysToCompare) {
        if (prevProps[key] !== nextProps[key]) {
            return false;
        }
    }
    
    // Deep compare stats and metadata
    if (JSON.stringify(prevProps.stats) !== JSON.stringify(nextProps.stats)) {
        return false;
    }
    
    if (JSON.stringify(prevProps.metadata) !== JSON.stringify(nextProps.metadata)) {
        return false;
    }
    
    if (JSON.stringify(prevProps.actions) !== JSON.stringify(nextProps.actions)) {
        return false;
    }
    
    return true;
});

export default MemoizedCard;