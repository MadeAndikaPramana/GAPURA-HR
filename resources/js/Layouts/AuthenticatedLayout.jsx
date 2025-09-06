// resources/js/Layouts/AuthenticatedLayout.jsx - Updated with complete sidebar integration

import { useState } from 'react';
import { Link } from '@inertiajs/react';
import Sidebar from './Sidebar';
import TopNavigation from './TopNav';
import {
    XMarkIcon,
} from '@heroicons/react/24/outline';

export default function AuthenticatedLayout({ user, header, children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="min-h-screen bg-slate-100">
            {/* Mobile sidebar overlay */}
            <div className={`fixed inset-0 flex z-40 lg:hidden ${sidebarOpen ? '' : 'pointer-events-none'}`}>
                {/* Overlay */}
                <div
                    className={`fixed inset-0 bg-slate-600 bg-opacity-75 transition-opacity ease-linear duration-300 ${
                        sidebarOpen ? 'opacity-100' : 'opacity-0'
                    }`}
                    onClick={() => setSidebarOpen(false)}
                />

                {/* Mobile sidebar */}
                <div className={`relative flex-1 flex flex-col max-w-xs w-full transform ease-in-out duration-300 ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                }`}>
                    {/* Close button */}
                    <div className="absolute top-0 right-0 -mr-12 pt-2">
                        <button
                            type="button"
                            className="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                            onClick={() => setSidebarOpen(false)}
                        >
                            <span className="sr-only">Close sidebar</span>
                            <XMarkIcon className="h-6 w-6 text-white" />
                        </button>
                    </div>

                    {/* Mobile sidebar content */}
                    <Sidebar user={user} mobile />
                </div>
            </div>

            {/* Desktop sidebar */}
            <div className="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0">
                <Sidebar user={user} />
            </div>

            {/* Main content area */}
            <div className="lg:pl-64 flex flex-col flex-1">
                {/* Top navigation */}
                <TopNavigation
                    sidebarOpen={sidebarOpen}
                    setSidebarOpen={setSidebarOpen}
                    user={user}
                />

                {/* Page header */}
                {header && (
                    <header className="bg-white shadow-sm border-b border-slate-200">
                        <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {header}
                        </div>
                    </header>
                )}

                {/* Main content */}
                <main className="flex-1">
                    {children}
                </main>
            </div>
        </div>
    );
}
