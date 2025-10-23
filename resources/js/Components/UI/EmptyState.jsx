import React from 'react';
import { Link } from '@inertiajs/react';
import { FolderOpenIcon } from '@heroicons/react/24/outline';

export default function EmptyState({
    icon: Icon = FolderOpenIcon,
    title = 'No data found',
    description = 'Get started by creating a new item.',
    actionLabel = null,
    actionHref = null,
    actionOnClick = null,
}) {
    return (
        <div className="text-center py-12 px-4">
            <Icon className="mx-auto h-12 w-12 text-gray-400" />
            <h3 className="mt-4 text-lg font-medium text-gray-900">{title}</h3>
            <p className="mt-2 text-sm text-gray-500 max-w-md mx-auto">{description}</p>

            {(actionLabel && (actionHref || actionOnClick)) && (
                <div className="mt-6">
                    {actionHref ? (
                        <Link
                            href={actionHref}
                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            {actionLabel}
                        </Link>
                    ) : (
                        <button
                            onClick={actionOnClick}
                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            {actionLabel}
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}
