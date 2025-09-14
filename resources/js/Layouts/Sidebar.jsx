// resources/js/Layouts/Sidebar.jsx - Cleaned Navigation

import { Link, usePage } from '@inertiajs/react';
import {
    HomeIcon,
    FolderIcon,
    UsersIcon,
    DocumentTextIcon,
    ArrowDownTrayIcon,
    Cog6ToothIcon,
    CubeIcon,
    BuildingOffice2Icon
} from '@heroicons/react/24/outline';

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export default function Sidebar({ user, mobile = false }) {
    const { url } = usePage();

    // Improved URL matching to prevent overlapping routes
    const isCurrentPage = (path, exact = false) => {
        if (exact) {
            return url === path || url === `${path}/`;
        }

        const currentPath = url.split('?')[0]; // Remove query parameters

        // Prevent employee-containers from activating employees menu
        if (path === '/employees' && currentPath.includes('/employee-containers')) {
            return false;
        }

        // Prevent sdm from activating employees menu
        if (path === '/employees' && currentPath.includes('/sdm')) {
            return false;
        }

        return currentPath.startsWith(path);
    };

    // CLEANED NAVIGATION STRUCTURE - Removed unnecessary items
    const navigationSections = [
        {
            title: 'EMPLOYEE MANAGEMENT',
            items: [
                {
                    name: 'SDM',
                    href: '/sdm',
                    icon: UsersIcon,
                    description: 'Employee master data with Excel sync',
                    current: isCurrentPage('/sdm')
                },
                {
                    name: 'Employee Containers',
                    href: '/employee-containers',
                    icon: FolderIcon,
                    description: 'Digital employee file containers',
                    current: isCurrentPage('/employee-containers')
                }
            ]
        },
        {
            title: 'TRAINING MANAGEMENT',
            items: [
                {
                    name: 'Training Types',
                    href: '/training-types',
                    icon: DocumentTextIcon,
                    description: 'Certificate types & who has them',
                    current: isCurrentPage('/training-types')
                }
            ]
        },
        {
            title: 'SYSTEM',
            items: [
                {
                    name: 'Departments',
                    href: '/departments',
                    icon: BuildingOffice2Icon,
                    description: 'Department management',
                    current: isCurrentPage('/departments')
                },
                {
                    name: 'Settings',
                    href: '/system/settings',
                    icon: Cog6ToothIcon,
                    description: 'System configuration',
                    current: isCurrentPage('/system')
                }
            ]
        }
    ];

    return (
        <div className={classNames(
            'flex flex-col h-full bg-slate-800',
            mobile ? 'w-full' : 'w-64'
        )}>
            {/* Logo and title */}
            <div className="flex items-center h-16 flex-shrink-0 px-4 bg-slate-900">
                <div className="flex items-center space-x-3">
                    <div className="w-8 h-8 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center">
                        <CubeIcon className="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h1 className="text-white text-lg font-bold">GAPURA</h1>
                        <p className="text-green-400 text-xs font-medium">Container System</p>
                    </div>
                </div>
            </div>

            {/* Navigation */}
            <nav className="flex-1 px-2 py-4 space-y-6 overflow-y-auto">
                {navigationSections.map((section) => (
                    <div key={section.title}>
                        {/* Section Title */}
                        <div className="px-3 mb-3">
                            <h3 className="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                                {section.title}
                            </h3>
                        </div>

                        {/* Section Items */}
                        <div className="space-y-1">
                            {section.items.map((item) => {
                                const IconComponent = item.icon;

                                return (
                                    <Link
                                        key={item.name}
                                        href={item.href}
                                        className={classNames(
                                            'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-150 ease-in-out',
                                            item.current
                                                ? 'bg-green-600 text-white shadow-lg'
                                                : 'text-slate-300 hover:bg-slate-700 hover:text-white'
                                        )}
                                    >
                                        {/* Icon */}
                                        <IconComponent
                                            className={classNames(
                                                'mr-3 h-5 w-5 flex-shrink-0 transition-colors duration-150 ease-in-out',
                                                item.current
                                                    ? 'text-green-200'
                                                    : 'text-slate-400 group-hover:text-slate-300'
                                            )}
                                        />

                                        {/* Content */}
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between">
                                                <span className="truncate">{item.name}</span>
                                            </div>

                                            {/* Description */}
                                            {item.description && (
                                                <div className={classNames(
                                                    'truncate transition-colors duration-150 ease-in-out',
                                                    item.current
                                                        ? 'text-green-200'
                                                        : 'text-slate-400 group-hover:text-slate-300',
                                                    'text-xs mt-0.5'
                                                )}>
                                                    {item.description}
                                                </div>
                                            )}
                                        </div>

                                        {/* Active indicator */}
                                        {item.current && (
                                            <div className="ml-2 h-2 w-2 bg-green-300 rounded-full animate-pulse"></div>
                                        )}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>

            {/* Quick Actions Section */}
            <div className="px-2 py-4 border-t border-slate-700">
                <div className="space-y-1">
                    <Link
                        href="/sdm/import"
                        className="group flex items-center px-3 py-2 text-sm font-medium rounded-md text-slate-300 hover:bg-slate-700 hover:text-white transition-colors duration-150 ease-in-out"
                    >
                        <ArrowDownTrayIcon className="mr-3 h-4 w-4 text-slate-400 group-hover:text-slate-300" />
                        <span className="text-xs">Import Excel</span>
                    </Link>
                </div>
            </div>

            {/* Development Mode Debug Info */}
            {process.env.NODE_ENV === 'development' && (
                <div className="px-3 py-2 border-t border-slate-700 bg-slate-900">
                    <div className="text-xs text-slate-400">
                        Current URL: <span className="text-slate-300">{url}</span>
                    </div>
                </div>
            )}

            {/* User info at bottom */}
            <div className="flex-shrink-0 border-t border-slate-700 p-4">
                <div className="flex items-center">
                    <div className="flex-shrink-0">
                        <div className="w-8 h-8 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-lg">
                            <span className="text-white text-sm font-medium">
                                {user?.name?.charAt(0) || 'G'}
                            </span>
                        </div>
                    </div>
                    <div className="ml-3 text-white">
                        <div className="text-sm font-medium truncate">{user?.name || 'GAPURA Admin'}</div>
                        <div className="text-xs text-slate-400 truncate">{user?.email || 'admin@gapura.com'}</div>
                    </div>
                </div>

                {/* User actions */}
                <div className="mt-3 flex space-x-2">
                    <Link
                        href="/profile"
                        className="text-xs text-slate-400 hover:text-slate-300 transition-colors"
                    >
                        Profile
                    </Link>
                    <span className="text-slate-600">•</span>
                    <Link
                        method="post"
                        href="/logout"
                        as="button"
                        className="text-xs text-slate-400 hover:text-red-400 transition-colors"
                    >
                        Logout
                    </Link>
                </div>
            </div>
        </div>
    );
}
