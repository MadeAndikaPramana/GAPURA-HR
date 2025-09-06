// resources/js/Layouts/TopNav.jsx - Top navigation bar

import { Link, router, usePage } from '@inertiajs/react';
import {
    Bars3Icon,
    BellIcon,
    MagnifyingGlassIcon,
    UserCircleIcon,
    Cog6ToothIcon,
    ArrowRightOnRectangleIcon
} from '@heroicons/react/24/outline';
import { Menu, Transition } from '@headlessui/react';
import { Fragment } from 'react';

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export default function TopNavigation({ sidebarOpen, setSidebarOpen, user }) {
    const { url } = usePage();

    // Generate page title based on current URL
    const getPageTitle = (currentUrl) => {
        if (currentUrl === '/dashboard') return 'Dashboard';
        if (currentUrl.includes('/sdm')) return 'SDM';
        if (currentUrl.includes('/employee-containers')) return 'Employee Containers';
        if (currentUrl.includes('/training-types')) return 'Training Types';
        if (currentUrl.includes('/employee-certificates')) return 'Certificate Records';
        if (currentUrl.includes('/departments')) return 'Departments';
        if (currentUrl.includes('/reports')) return 'Reports';
        if (currentUrl.includes('/configuration')) return 'System Settings';
        return 'GAPURA System';
    };

    // Generate breadcrumb based on current URL
    const getBreadcrumb = (currentUrl) => {
        if (currentUrl === '/dashboard') return 'System overview and statistics';
        if (currentUrl.includes('/sdm')) return 'Employee master data management';
        if (currentUrl.includes('/employee-containers')) return 'Digital employee file containers';
        if (currentUrl.includes('/training-types')) return 'Certificate types and employee distribution';
        if (currentUrl.includes('/employee-certificates')) return 'Individual certificate management';
        if (currentUrl.includes('/departments')) return 'Department management';
        if (currentUrl.includes('/reports')) return 'Analytics and compliance reports';
        if (currentUrl.includes('/configuration')) return 'System settings and configuration';
        return 'Employee container management system';
    };

    const logout = (e) => {
        e.preventDefault();
        router.post('/logout');
    };

    return (
        <div className="relative z-10 flex-shrink-0 flex h-16 bg-white border-b border-slate-200 shadow-sm">
            {/* Mobile menu button */}
            <button
                type="button"
                className="px-4 border-r border-slate-200 text-slate-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green-500 lg:hidden hover:bg-slate-50 transition-colors"
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
                            <div className="hidden xl:block">
                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                    {url}
                                </span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Right side - Actions and user menu */}
                <div className="ml-4 flex items-center md:ml-6 space-x-3">

                    {/* Search (hidden on mobile) */}
                    <div className="hidden md:block">
                        <div className="relative">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon className="h-5 w-5 text-slate-400" />
                            </div>
                            <input
                                type="search"
                                placeholder="Search employees..."
                                className="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-md leading-5 bg-white placeholder-slate-500 focus:outline-none focus:placeholder-slate-400 focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                            />
                        </div>
                    </div>

                    {/* Notifications */}
                    <button
                        type="button"
                        className="bg-white p-1 rounded-full text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                    >
                        <span className="sr-only">View notifications</span>
                        <BellIcon className="h-6 w-6" />
                    </button>

                    {/* User menu */}
                    <Menu as="div" className="ml-3 relative">
                        <div>
                            <Menu.Button className="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-150 hover:shadow-md">
                                <span className="sr-only">Open user menu</span>
                                <div className="flex items-center space-x-3 px-3 py-2">
                                    <div className="w-8 h-8 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center">
                                        <span className="text-white text-sm font-medium">
                                            {user?.name?.charAt(0) || 'G'}
                                        </span>
                                    </div>
                                    <div className="hidden md:block text-left">
                                        <div className="text-sm font-medium text-slate-900">{user?.name}</div>
                                        <div className="text-xs text-slate-500">{user?.email}</div>
                                    </div>
                                </div>
                            </Menu.Button>
                        </div>

                        <Transition
                            as={Fragment}
                            enter="transition ease-out duration-100"
                            enterFrom="transform opacity-0 scale-95"
                            enterTo="transform opacity-100 scale-100"
                            leave="transition ease-in duration-75"
                            leaveFrom="transform opacity-100 scale-100"
                            leaveTo="transform opacity-0 scale-95"
                        >
                            <Menu.Items className="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <Menu.Item>
                                    {({ active }) => (
                                        <Link
                                            href="/profile"
                                            className={classNames(
                                                active ? 'bg-slate-100' : '',
                                                'flex items-center px-4 py-2 text-sm text-slate-700 transition-colors'
                                            )}
                                        >
                                            <UserCircleIcon className="mr-3 h-4 w-4" />
                                            Your Profile
                                        </Link>
                                    )}
                                </Menu.Item>

                                <Menu.Item>
                                    {({ active }) => (
                                        <Link
                                            href="/configuration"
                                            className={classNames(
                                                active ? 'bg-slate-100' : '',
                                                'flex items-center px-4 py-2 text-sm text-slate-700 transition-colors'
                                            )}
                                        >
                                            <Cog6ToothIcon className="mr-3 h-4 w-4" />
                                            Settings
                                        </Link>
                                    )}
                                </Menu.Item>

                                <div className="border-t border-slate-100"></div>

                                <Menu.Item>
                                    {({ active }) => (
                                        <button
                                            onClick={logout}
                                            className={classNames(
                                                active ? 'bg-slate-100' : '',
                                                'flex items-center w-full px-4 py-2 text-sm text-slate-700 transition-colors'
                                            )}
                                        >
                                            <ArrowRightOnRectangleIcon className="mr-3 h-4 w-4" />
                                            Sign out
                                        </button>
                                    )}
                                </Menu.Item>
                            </Menu.Items>
                        </Transition>
                    </Menu>
                </div>
            </div>
        </div>
    );
}
