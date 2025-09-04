// resources/js/Layouts/TopNavigation.jsx - Fixed Page Titles

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

    // ✅ FINAL: Correct page title mapping after final restructure
    const getPageTitle = (currentUrl) => {
        // ✅ STRUKTUR AKHIR:
        if (currentUrl.startsWith('/employees')) return 'Container Data';
        if (currentUrl.startsWith('/sdm')) return 'SDM';
        if (currentUrl.includes('/employee-certificates')) return 'Training Records';
        if (currentUrl.includes('/certificate-types')) return 'Certificate Types';
        if (currentUrl.includes('/reports')) return 'Reports & Analytics';
        if (currentUrl.includes('/configuration')) return 'System Configuration';
        if (currentUrl.includes('/dashboard')) return 'Dashboard';
        return 'Dashboard';
    };

    // Get breadcrumb info
    const getBreadcrumb = (currentUrl) => {
        // ✅ STRUKTUR AKHIR:
        if (currentUrl.startsWith('/employees')) {
            return 'Digital employee folders with certificates and background check data';
        }
        if (currentUrl.startsWith('/sdm')) {
            return 'Traditional employee CRUD management and administration';
        }
        if (currentUrl.includes('/employee-certificates')) {
            return 'Training records and certificate management';
        }
        if (currentUrl.includes('/certificate-types')) {
            return 'Certificate type configuration';
        }
        if (currentUrl.includes('/reports')) {
            return 'Analytics and compliance reports';
        }
        if (currentUrl.includes('/configuration')) {
            return 'System settings and configuration';
        }
        return 'Overview sistem training';
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
                <div className="flex-1">
                    <div className="flex items-center space-x-4">
                        <div>
                            <h1 className="text-xl font-semibold text-slate-900">
                                {getPageTitle(url)}
                            </h1>
                            <p className="text-sm text-slate-500 mt-0.5">
                                {getBreadcrumb(url)}
                            </p>
                        </div>

                        {/* Current URL indicator for debugging (development only) */}
                        {process.env.NODE_ENV === 'development' && (
                            <div className="hidden lg:flex items-center px-3 py-1 bg-blue-50 border border-blue-200 rounded-full">
                                <div className="text-xs text-blue-600">
                                    URL: <span className="font-mono">{url}</span>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Right side - Search, notifications, user menu */}
                <div className="flex items-center space-x-4">
                    {/* Global search */}
                    <div className="hidden md:block">
                        <div className="relative">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon className="h-5 w-5 text-slate-400" />
                            </div>
                            <input
                                type="text"
                                placeholder="Search employees, certificates..."
                                className="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md leading-5 bg-white placeholder-slate-500 focus:outline-none focus:placeholder-slate-400 focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                            />
                        </div>
                    </div>

                    {/* Notifications */}
                    <button
                        type="button"
                        className="p-1 rounded-full text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        <span className="sr-only">View notifications</span>
                        <BellIcon className="h-6 w-6" />
                    </button>

                    {/* Profile dropdown */}
                    <div className="relative">
                        <div>
                            <button
                                type="button"
                                className="bg-white rounded-full flex items-center text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                onClick={() => setUserMenuOpen(!userMenuOpen)}
                            >
                                <span className="sr-only">Open user menu</span>
                                <div className="h-8 w-8 rounded-full bg-green-600 flex items-center justify-center">
                                    <span className="text-sm font-medium text-white">
                                        {user?.name?.charAt(0) || 'G'}
                                    </span>
                                </div>
                                <div className="hidden md:block ml-3">
                                    <div className="text-sm font-medium text-slate-700">
                                        {user?.name || 'GAPURA Super Admin'}
                                    </div>
                                    <div className="text-xs text-slate-500">
                                        {user?.email || 'admin@gapura.com'}
                                    </div>
                                </div>
                                <ChevronDownIcon className="hidden md:block ml-2 h-4 w-4 text-slate-400" />
                            </button>
                        </div>

                        {/* Dropdown menu */}
                        {userMenuOpen && (
                            <div className="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                                <Link
                                    href="/profile"
                                    className="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                    onClick={() => setUserMenuOpen(false)}
                                >
                                    <UserIcon className="mr-3 h-4 w-4" />
                                    Your Profile
                                </Link>
                                <Link
                                    href="/configuration"
                                    className="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                    onClick={() => setUserMenuOpen(false)}
                                >
                                    <Cog6ToothIcon className="mr-3 h-4 w-4" />
                                    Settings
                                </Link>
                                <button
                                    onClick={(e) => {
                                        logout(e);
                                        setUserMenuOpen(false);
                                    }}
                                    className="flex items-center w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                >
                                    <ArrowLeftOnRectangleIcon className="mr-3 h-4 w-4" />
                                    Sign out
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
