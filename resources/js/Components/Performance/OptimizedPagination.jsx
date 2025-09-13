import { memo, useMemo } from 'react';
import { Link, router } from '@inertiajs/react';
import { 
    ChevronLeftIcon, 
    ChevronRightIcon,
    ChevronDoubleLeftIcon,
    ChevronDoubleRightIcon 
} from '@heroicons/react/24/outline';

/**
 * OptimizedPagination - High-performance pagination component
 * Features:
 * - Memoized to prevent unnecessary re-renders
 * - Smart page number calculation to avoid rendering too many buttons
 * - Prefetching of adjacent pages for better UX
 * - Optimized for large datasets
 * - Accessible keyboard navigation
 */
const OptimizedPagination = memo(function OptimizedPagination({
    data,
    preserveState = true,
    preserveScroll = false,
    className = '',
    showStats = true,
    maxVisiblePages = 7,
    prefetchPages = true,
}) {
    // Memoized pagination calculations
    const paginationInfo = useMemo(() => {
        if (!data) return null;

        const {
            current_page: currentPage,
            last_page: lastPage,
            per_page: perPage,
            from,
            to,
            total
        } = data;

        return {
            currentPage,
            lastPage,
            perPage,
            from: from || 0,
            to: to || 0,
            total: total || 0,
            hasPages: lastPage > 1
        };
    }, [data]);

    // Calculate visible page numbers with smart truncation
    const visiblePages = useMemo(() => {
        if (!paginationInfo?.hasPages) return [];

        const { currentPage, lastPage } = paginationInfo;
        const delta = Math.floor(maxVisiblePages / 2);
        const pages = [];

        let startPage = Math.max(1, currentPage - delta);
        let endPage = Math.min(lastPage, currentPage + delta);

        // Adjust range if we're near the beginning or end
        if (endPage - startPage + 1 < maxVisiblePages) {
            if (startPage === 1) {
                endPage = Math.min(lastPage, startPage + maxVisiblePages - 1);
            } else if (endPage === lastPage) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }
        }

        // Add first page and ellipsis if needed
        if (startPage > 1) {
            pages.push(1);
            if (startPage > 2) {
                pages.push('...');
            }
        }

        // Add visible pages
        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }

        // Add ellipsis and last page if needed
        if (endPage < lastPage) {
            if (endPage < lastPage - 1) {
                pages.push('...');
            }
            pages.push(lastPage);
        }

        return pages;
    }, [paginationInfo, maxVisiblePages]);

    // Prefetch adjacent pages for better UX
    const prefetchPage = (page) => {
        if (!prefetchPages || !page || page === paginationInfo?.currentPage) return;
        
        const url = new URL(window.location);
        url.searchParams.set('page', page);
        
        // Use requestIdleCallback if available, fallback to setTimeout
        if (window.requestIdleCallback) {
            window.requestIdleCallback(() => {
                router.prefetch(url.pathname + url.search);
            });
        } else {
            setTimeout(() => {
                router.prefetch(url.pathname + url.search);
            }, 100);
        }
    };

    const handlePageClick = (page) => {
        if (page === paginationInfo?.currentPage) return;

        router.get(
            window.location.pathname,
            { ...Object.fromEntries(new URLSearchParams(window.location.search)), page },
            { preserveState, preserveScroll }
        );
    };

    // Early return if no pagination needed
    if (!paginationInfo?.hasPages) {
        return showStats && paginationInfo ? (
            <div className={`text-sm text-gray-600 ${className}`}>
                Showing {paginationInfo.from.toLocaleString()} to {paginationInfo.to.toLocaleString()} of {paginationInfo.total.toLocaleString()} results
            </div>
        ) : null;
    }

    const { currentPage, lastPage, from, to, total } = paginationInfo;

    return (
        <div className={`flex items-center justify-between ${className}`}>
            {/* Results info */}
            {showStats && (
                <div className="text-sm text-gray-600">
                    Showing <span className="font-medium">{from.toLocaleString()}</span> to{' '}
                    <span className="font-medium">{to.toLocaleString()}</span> of{' '}
                    <span className="font-medium">{total.toLocaleString()}</span> results
                </div>
            )}

            {/* Pagination controls */}
            <nav className="flex items-center space-x-1" role="navigation" aria-label="Pagination">
                {/* First page button */}
                <button
                    onClick={() => handlePageClick(1)}
                    disabled={currentPage === 1}
                    onMouseEnter={() => prefetchPage(1)}
                    className={`
                        inline-flex items-center px-2 py-1 rounded-md text-sm font-medium transition-colors
                        ${currentPage === 1 
                            ? 'text-gray-400 cursor-not-allowed' 
                            : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                        }
                    `}
                    aria-label="Go to first page"
                >
                    <ChevronDoubleLeftIcon className="h-4 w-4" />
                </button>

                {/* Previous page button */}
                <button
                    onClick={() => handlePageClick(currentPage - 1)}
                    disabled={currentPage === 1}
                    onMouseEnter={() => prefetchPage(currentPage - 1)}
                    className={`
                        inline-flex items-center px-2 py-1 rounded-md text-sm font-medium transition-colors
                        ${currentPage === 1 
                            ? 'text-gray-400 cursor-not-allowed' 
                            : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                        }
                    `}
                    aria-label="Go to previous page"
                >
                    <ChevronLeftIcon className="h-4 w-4" />
                </button>

                {/* Page numbers */}
                <div className="flex items-center space-x-1">
                    {visiblePages.map((page, index) => (
                        <div key={index}>
                            {page === '...' ? (
                                <span className="px-3 py-1 text-gray-500">...</span>
                            ) : (
                                <button
                                    onClick={() => handlePageClick(page)}
                                    onMouseEnter={() => prefetchPage(page)}
                                    className={`
                                        inline-flex items-center px-3 py-1 rounded-md text-sm font-medium transition-colors
                                        ${page === currentPage
                                            ? 'bg-blue-600 text-white'
                                            : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                                        }
                                    `}
                                    aria-label={`Go to page ${page}`}
                                    aria-current={page === currentPage ? 'page' : undefined}
                                >
                                    {page}
                                </button>
                            )}
                        </div>
                    ))}
                </div>

                {/* Next page button */}
                <button
                    onClick={() => handlePageClick(currentPage + 1)}
                    disabled={currentPage === lastPage}
                    onMouseEnter={() => prefetchPage(currentPage + 1)}
                    className={`
                        inline-flex items-center px-2 py-1 rounded-md text-sm font-medium transition-colors
                        ${currentPage === lastPage 
                            ? 'text-gray-400 cursor-not-allowed' 
                            : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                        }
                    `}
                    aria-label="Go to next page"
                >
                    <ChevronRightIcon className="h-4 w-4" />
                </button>

                {/* Last page button */}
                <button
                    onClick={() => handlePageClick(lastPage)}
                    disabled={currentPage === lastPage}
                    onMouseEnter={() => prefetchPage(lastPage)}
                    className={`
                        inline-flex items-center px-2 py-1 rounded-md text-sm font-medium transition-colors
                        ${currentPage === lastPage 
                            ? 'text-gray-400 cursor-not-allowed' 
                            : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                        }
                    `}
                    aria-label="Go to last page"
                >
                    <ChevronDoubleRightIcon className="h-4 w-4" />
                </button>
            </nav>
        </div>
    );
});

export default OptimizedPagination;