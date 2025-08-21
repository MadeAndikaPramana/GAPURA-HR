
import React from 'react';

export default function NotFound() {
    return (
        <div className="flex items-center justify-center min-h-screen bg-gray-100">
            <div className="text-center">
                <h1 className="text-6xl font-bold text-gray-800">404</h1>
                <p className="text-xl font-medium text-gray-600">Page Not Found</p>
                <p className="mt-4 text-gray-500">
                    The page you are looking for does not exist.
                </p>
                <a href="/dashboard" className="mt-6 inline-block px-6 py-3 text-sm font-semibold text-white bg-blue-500 rounded-md hover:bg-blue-600">
                    Go to Dashboard
                </a>
            </div>
        </div>
    );
}
