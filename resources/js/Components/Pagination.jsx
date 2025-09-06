// resources/js/Components/Pagination.jsx - Complete pagination component

import { Link } from '@inertiajs/react';
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronDoubleLeftIcon,
    ChevronDoubleRightIcon
} from '@heroicons/react/24/outline';

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export default function Pagination({ links }) {
    if (!links || links.length <= 3) {
        return null; // Don't show pagination if there are no pages
    }

    return (
        <nav className="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-3 sm:px-6">
            <div className="flex flex-1 justify-between sm:hidden">
                {/* Mobile pagination */}
                {links[0].url ? (
                    <Link
                        href={links[0].url}
                        className="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Previous
                    </Link>
                ) : (
                    <span className="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-400">
                        Previous
                    </span>
                )}

                {links[links.length - 1].url ? (
                    <Link
                        href={links[links.length - 1].url}
                        className="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Next
                    </Link>
                ) : (
                    <span className="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-400">
                        Next
                    </span>
                )}
            </div>

            <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                {/* Desktop pagination info */}
                <div>
                    <p className="text-sm text-slate-700">
                        Showing page <span className="font-medium">{getCurrentPage(links)}</span> of{' '}
                        <span className="font-medium">{getTotalPages(links)}</span>
                    </p>
                </div>

                {/* Desktop pagination controls */}
                <div>
                    <nav className="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                        {/* First page */}
                        {shouldShowFirstPage(links) && (
                            <>
                                <PaginationLink
                                    href={getFirstPageUrl(links)}
                                    className="rounded-l-md"
                                    disabled={!getFirstPageUrl(links)}
                                >
                                    <ChevronDoubleLeftIcon className="h-5 w-5" />
                                    <span className="sr-only">First page</span>
                                </PaginationLink>
                            </>
                        )}

                        {/* Previous page */}
                        <PaginationLink
                            href={links[0].url}
                            className={!shouldShowFirstPage(links) ? "rounded-l-md" : ""}
                            disabled={!links[0].url}
                        >
                            <ChevronLeftIcon className="h-5 w-5" />
                            <span className="sr-only">Previous</span>
                        </PaginationLink>

                        {/* Page numbers */}
                        {links.slice(1, -1).map((link, index) => {
                            if (link.label === '...') {
                                return (
                                    <span
                                        key={index}
                                        className="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-300"
                                    >
                                        ...
                                    </span>
                                );
                            }

                            return (
                                <PaginationLink
                                    key={index}
                                    href={link.url}
                                    active={link.active}
                                    disabled={!link.url}
                                >
                                    {link.label}
                                </PaginationLink>
                            );
                        })}

                        {/* Next page */}
                        <PaginationLink
                            href={links[links.length - 1].url}
                            className={!shouldShowLastPage(links) ? "rounded-r-md" : ""}
                            disabled={!links[links.length - 1].url}
                        >
                            <ChevronRightIcon className="h-5 w-5" />
                            <span className="sr-only">Next</span>
                        </PaginationLink>

                        {/* Last page */}
                        {shouldShowLastPage(links) && (
                            <PaginationLink
                                href={getLastPageUrl(links)}
                                className="rounded-r-md"
                                disabled={!getLastPageUrl(links)}
                            >
                                <ChevronDoubleRightIcon className="h-5 w-5" />
                                <span className="sr-only">Last page</span>
                            </PaginationLink>
                        )}
                    </nav>
                </div>
            </div>
        </nav>
    );
}

// Individual pagination link component
function PaginationLink({ href, active = false, disabled = false, className = '', children }) {
    const baseClasses = "relative inline-flex items-center px-4 py-2 text-sm font-medium ring-1 ring-inset ring-slate-300 focus:z-20 focus:outline-offset-0";

    const classes = classNames(
        baseClasses,
        active
            ? "z-10 bg-green-600 text-white focus:ring-2 focus:ring-green-600"
            : disabled
            ? "text-slate-400 cursor-not-allowed"
            : "text-slate-900 hover:bg-slate-50 focus:ring-2 focus:ring-green-600",
        className
    );

    if (disabled) {
        return (
            <span className={classes}>
                {children}
            </span>
        );
    }

    if (active) {
        return (
            <span className={classes} aria-current="page">
                {children}
            </span>
        );
    }

    return (
        <Link
            href={href}
            className={classes}
            preserveScroll
            preserveState
        >
            {children}
        </Link>
    );
}

// Helper functions
function getCurrentPage(links) {
    const activePage = links.find(link => link.active);
    return activePage ? activePage.label : '1';
}

function getTotalPages(links) {
    // Find the highest page number in the links
    const pageNumbers = links
        .filter(link => link.label && !isNaN(link.label) && link.label !== '...')
        .map(link => parseInt(link.label))
        .filter(num => !isNaN(num));

    return pageNumbers.length > 0 ? Math.max(...pageNumbers) : 1;
}

function shouldShowFirstPage(links) {
    // Show first page button if we're not on page 1 and there are many pages
    const currentPage = parseInt(getCurrentPage(links));
    const totalPages = getTotalPages(links);
    return currentPage > 2 && totalPages > 5;
}

function shouldShowLastPage(links) {
    // Show last page button if we're not near the end and there are many pages
    const currentPage = parseInt(getCurrentPage(links));
    const totalPages = getTotalPages(links);
    return currentPage < totalPages - 1 && totalPages > 5;
}

function getFirstPageUrl(links) {
    // Find the URL pattern and create first page URL
    const secondPage = links.find(link => link.label === '2');
    if (secondPage && secondPage.url) {
        return secondPage.url.replace(/page=2/, 'page=1');
    }
    return null;
}

function getLastPageUrl(links) {
    // Find the last numbered page
    const totalPages = getTotalPages(links);
    const anyPageLink = links.find(link => link.url && link.label && !isNaN(link.label));

    if (anyPageLink && anyPageLink.url) {
        return anyPageLink.url.replace(/page=\d+/, `page=${totalPages}`);
    }
    return null;
}
