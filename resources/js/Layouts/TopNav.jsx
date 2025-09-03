// resources/js/Layouts/TopNavigation.jsx

import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Bars3Icon,
    BellIcon,
    ChevronDownIcon,
    MagnifyingGlassIcon,
    Cog6ToothIcon,
    ArrowLeftOnRectangleIcon,
    UserIcon
} from '@heroicons/react/24/outline';

export default function TopNavigation({ sidebarOpen, setSidebarOpen, user }) {
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const { url } = usePage();

    // Get page title based on current URL
    const getPageTitle = (currentUrl) => {
        if (currentUrl.includes('/employees')) return 'Employee Containers';
        if (currentUrl.includes('/employee-certificates')) return 'Training Records';
        if (currentUrl.includes('/certificate-types')) return 'Certificate Types';
        if (currentUrl.includes('/reports')) return 'Reports & Analytics';
        if (currentUrl.includes('/settings')) return 'System Configuration';
        return 'Dashboard';
    };

    const logout = (e) => {
        e.preventDefault();
        router.post('/logout');
    };

    return (
        <div className="relative z-10 flex-shrink-0 flex h-16 bg-slate-50 border-b border-slate-200 shadow-sm">
            {/* Mobile menu button */}
            <button
                type="button"
                className="px-4 border-r border-slate-200 text-slate-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green-500 lg:hidden hover:bg-slate-100"
                onClick={() => setSidebarOpen(true)}
            >
                <span className="sr-only">Open sidebar</span>
                <Bars3Icon className="h-6 w-6" />
            </button>

            {/* Main navigation content */}
            <div className="flex-1 px-4 flex justify-between items-center">
                {/* Left side - Page title and breadcrumb */}
                <div className="flex-1 flex items-center">
                    <h1 className="text-lg font-semibold text-slate-800">
                        {getPageTitle(url)}
                    </h1>
                    <div className="ml-4 text-sm text-slate-500">
                        {url !== '/dashboard' && (
                            <span>Overview sistem training</span>
                        )}
                    </div>
                </div>

                {/* Right side - Search, notifications, and user menu */}
                <div className="ml-4 flex items-center space-x-4">
                    {/* Search */}
                    <div className="relative hidden md:block">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon className="h-4 w-4 text-slate-400" />
                        </div>
                        <input
                            type="text"
                            className="block w-64 pl-10 pr-3 py-2 border border-slate-300 rounded-lg text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white"
                            placeholder="Search employees, certificates..."
                        />
                    </div>

                    {/* Notifications */}
                    <button
                        type="button"
                        className="p-2 text-slate-400 hover:text-slate-500 hover:bg-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors"
                    >
                        <span className="sr-only">View notifications</span>
                        <BellIcon className="h-5 w-5" />
                    </button>

                    {/* Profile dropdown */}
                    <div className="relative">
                        <button
                            type="button"
                            className="flex items-center text-sm rounded-lg bg-white px-3 py-2 text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-green-500 border border-slate-200 transition-colors"
                            onClick={() => setUserMenuOpen(!userMenuOpen)}
                        >
                            <div className="w-7 h-7 bg-green-600 rounded-full flex items-center justify-center mr-3">
                                <span className="text-white font-medium text-sm">
                                    {user?.name?.charAt(0).toUpperCase() || 'G'}
                                </span>
                            </div>
                            <div className="text-left">
                                <div className="text-sm font-medium text-slate-900">
                                    GAPURA Super Admin
                                </div>
                                <div className="text-xs text-slate-500">
                                    {user?.email || 'admin@gapura.com'}
                                </div>
                            </div>
                            <ChevronDownIcon className="ml-2 h-4 w-4 text-slate-400" />
                        </button>

                        {/* Dropdown menu */}
                        {userMenuOpen && (
                            <div className="origin-top-right absolute right-0 mt-2 w-56 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                                <div className="py-1">
                                    <Link
                                        href="/profile"
                                        className="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                        onClick={() => setUserMenuOpen(false)}
                                    >
                                        <UserIcon className="mr-3 h-4 w-4 text-slate-400" />
                                        Your Profile
                                    </Link>
                                    <Link
                                        href="/settings"
                                        className="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                        onClick={() => setUserMenuOpen(false)}
                                    >
                                        <Cog6ToothIcon className="mr-3 h-4 w-4 text-slate-400" />
                                        Settings
                                    </Link>
                                    <hr className="my-1 border-slate-200" />
                                    <button
                                        onClick={(e) => {
                                            logout(e);
                                            setUserMenuOpen(false);
                                        }}
                                        className="flex items-center w-full px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                    >
                                        <ArrowLeftOnRectangleIcon className="mr-3 h-4 w-4 text-slate-400" />
                                        Sign out
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Close user menu when clicking outside */}
            {userMenuOpen && (
                <div
                    className="fixed inset-0 z-40"
                    onClick={() => setUserMenuOpen(false)}
                />
            )}
        </div>
    );
}
