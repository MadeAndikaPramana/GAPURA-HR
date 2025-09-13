import { useState, useEffect, useMemo, useCallback } from 'react';
import { debounce } from 'lodash';

/**
 * LazyDataGrid - High-performance data grid with virtualization and lazy loading
 * Optimized for large datasets with features like:
 * - Virtual scrolling for thousands of rows
 * - Debounced search and filtering
 * - Lazy loading of data
 * - Memoized row rendering
 * - Efficient state management
 */
export default function LazyDataGrid({
    data = [],
    columns = [],
    loading = false,
    onSearch,
    onSort,
    onFilter,
    searchTerm = '',
    sortConfig = null,
    filters = {},
    pageSize = 50,
    virtualScrolling = true,
    className = '',
    emptyMessage = 'No data available',
}) {
    const [localSearch, setLocalSearch] = useState(searchTerm);
    const [visibleRange, setVisibleRange] = useState({ start: 0, end: pageSize });
    const [containerHeight, setContainerHeight] = useState(600);

    // Debounced search to avoid excessive API calls
    const debouncedSearch = useMemo(
        () => debounce((term) => {
            if (onSearch) {
                onSearch(term);
            }
        }, 300),
        [onSearch]
    );

    useEffect(() => {
        debouncedSearch(localSearch);
        return () => debouncedSearch.cancel();
    }, [localSearch, debouncedSearch]);

    // Memoized filtered and sorted data
    const processedData = useMemo(() => {
        let result = [...data];

        // Apply local filtering if no server-side filtering
        if (!onFilter && localSearch) {
            result = result.filter(item =>
                columns.some(col => {
                    const value = getNestedValue(item, col.accessor);
                    return String(value).toLowerCase().includes(localSearch.toLowerCase());
                })
            );
        }

        // Apply sorting if no server-side sorting
        if (!onSort && sortConfig) {
            result.sort((a, b) => {
                const aVal = getNestedValue(a, sortConfig.accessor);
                const bVal = getNestedValue(b, sortConfig.accessor);
                
                if (aVal < bVal) return sortConfig.direction === 'asc' ? -1 : 1;
                if (aVal > bVal) return sortConfig.direction === 'asc' ? 1 : -1;
                return 0;
            });
        }

        return result;
    }, [data, localSearch, sortConfig, columns, onFilter, onSort]);

    // Virtual scrolling calculations
    const rowHeight = 60; // Fixed row height for virtual scrolling
    const visibleData = useMemo(() => {
        if (!virtualScrolling) return processedData;
        
        return processedData.slice(visibleRange.start, visibleRange.end);
    }, [processedData, visibleRange, virtualScrolling]);

    const handleScroll = useCallback((e) => {
        if (!virtualScrolling) return;

        const scrollTop = e.target.scrollTop;
        const containerHeight = e.target.clientHeight;
        
        const startIndex = Math.floor(scrollTop / rowHeight);
        const endIndex = Math.min(
            startIndex + Math.ceil(containerHeight / rowHeight) + 5, // Buffer
            processedData.length
        );

        setVisibleRange({ start: startIndex, end: endIndex });
    }, [virtualScrolling, rowHeight, processedData.length]);

    const handleSort = useCallback((accessor) => {
        if (onSort) {
            onSort(accessor);
        } else {
            // Local sorting fallback
            const newDirection = 
                sortConfig?.accessor === accessor && sortConfig?.direction === 'asc' 
                    ? 'desc' : 'asc';
            // This would need to be handled by parent component
        }
    }, [onSort, sortConfig]);

    const handleSearchChange = useCallback((e) => {
        setLocalSearch(e.target.value);
    }, []);

    // Memoized row component to prevent unnecessary re-renders
    const TableRow = useMemo(() => {
        return ({ item, index, style }) => (
            <div 
                key={item.id || index}
                className="grid gap-4 py-3 px-4 border-b border-gray-200 hover:bg-gray-50 transition-colors"
                style={{
                    ...style,
                    gridTemplateColumns: columns.map(col => col.width || '1fr').join(' '),
                }}
            >
                {columns.map((column, colIndex) => {
                    const value = getNestedValue(item, column.accessor);
                    return (
                        <div key={colIndex} className="flex items-center min-w-0">
                            {column.render ? column.render(value, item, index) : (
                                <span className="truncate" title={String(value)}>
                                    {String(value)}
                                </span>
                            )}
                        </div>
                    );
                })}
            </div>
        );
    }, [columns]);

    return (
        <div className={`bg-white rounded-lg border border-gray-200 ${className}`}>
            {/* Search and Filters Header */}
            <div className="p-4 border-b border-gray-200">
                <div className="flex items-center space-x-4">
                    <div className="flex-1">
                        <input
                            type="text"
                            placeholder="Search..."
                            value={localSearch}
                            onChange={handleSearchChange}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                    <div className="text-sm text-gray-600">
                        {loading ? 'Loading...' : `${processedData.length} items`}
                    </div>
                </div>
            </div>

            {/* Column Headers */}
            <div 
                className="grid gap-4 py-3 px-4 bg-gray-50 border-b border-gray-200 font-medium text-gray-900"
                style={{
                    gridTemplateColumns: columns.map(col => col.width || '1fr').join(' '),
                }}
            >
                {columns.map((column, index) => (
                    <div 
                        key={index}
                        className={`flex items-center ${column.sortable ? 'cursor-pointer hover:text-blue-600' : ''}`}
                        onClick={column.sortable ? () => handleSort(column.accessor) : undefined}
                    >
                        <span className="truncate">{column.header}</span>
                        {column.sortable && sortConfig?.accessor === column.accessor && (
                            <span className="ml-1">
                                {sortConfig.direction === 'asc' ? '↑' : '↓'}
                            </span>
                        )}
                    </div>
                ))}
            </div>

            {/* Data Rows */}
            <div 
                className="relative overflow-auto"
                style={{ height: `${containerHeight}px` }}
                onScroll={handleScroll}
            >
                {loading ? (
                    <div className="flex items-center justify-center py-12">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span className="ml-2 text-gray-600">Loading...</span>
                    </div>
                ) : processedData.length === 0 ? (
                    <div className="flex items-center justify-center py-12 text-gray-500">
                        {emptyMessage}
                    </div>
                ) : virtualScrolling ? (
                    <div style={{ height: `${processedData.length * rowHeight}px`, position: 'relative' }}>
                        {visibleData.map((item, index) => (
                            <TableRow
                                key={item.id || (visibleRange.start + index)}
                                item={item}
                                index={visibleRange.start + index}
                                style={{
                                    position: 'absolute',
                                    top: `${(visibleRange.start + index) * rowHeight}px`,
                                    width: '100%',
                                    height: `${rowHeight}px`,
                                }}
                            />
                        ))}
                    </div>
                ) : (
                    processedData.map((item, index) => (
                        <TableRow
                            key={item.id || index}
                            item={item}
                            index={index}
                        />
                    ))
                )}
            </div>

            {/* Performance Stats (Debug Mode) */}
            {process.env.NODE_ENV === 'development' && (
                <div className="px-4 py-2 bg-gray-50 border-t border-gray-200 text-xs text-gray-500">
                    Total: {processedData.length} | 
                    Visible: {visibleData.length} | 
                    Range: {visibleRange.start}-{visibleRange.end} |
                    Virtual: {virtualScrolling ? 'On' : 'Off'}
                </div>
            )}
        </div>
    );
}

// Helper function to get nested object values
function getNestedValue(obj, path) {
    return path.split('.').reduce((current, key) => current?.[key], obj) ?? '';
}