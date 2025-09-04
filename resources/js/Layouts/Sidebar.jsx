// resources/js/Layouts/Sidebar.jsx - Fixed Navigation Active State

import { Link, usePage } from '@inertiajs/react';
import {
    HomeIcon,
    FolderIcon,
    UsersIcon,
    ClipboardDocumentListIcon,
    AcademicCapIcon,
    DocumentTextIcon,
    ArrowDownTrayIcon,
    Cog6ToothIcon,
    ChartBarIcon
} from '@heroicons/react/24/outline';

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export default function Sidebar({ user, mobile = false }) {
    const { url } = usePage();

    // ✅ FIXED: More specific URL matching to prevent overlapping
    const isCurrentPage = (path, exact = false) => {
        if (exact) {
            return url === path || url === `${path}/`;
        }

        // Special handling for specific routes to avoid overlap
        if (path === '/employees' && url.includes('/employee-containers')) {
            return false; // Don't activate "Data Karyawan" when in "Employee Containers"
        }

        return url.startsWith(path);
    };

    // ✅ NAVIGATION STRUCTURE - Clear separation
    const navigationSections = [
        {
            title: 'MAIN',
            items: [
                {
                    name: 'Dashboard',
                    href: '/dashboard',
                    icon: HomeIcon,
                    current: isCurrentPage('/dashboard', true)
                }
            ]
        },
        {
            title: 'DATA MANAGEMENT',
            items: [
                {
                    name: 'Container Data',
                    href: '/employees',
                    icon: FolderIcon,
                    description: 'Digital employee folders',
                    current: isCurrentPage('/employees')
                },
                {
                    name: 'SDM',
                    href: '/sdm',
                    icon: UsersIcon,
                    description: 'Traditional employee CRUD',
                    current: isCurrentPage('/sdm')
                }
            ]
        },
        {
            title: 'TRAINING & CERTIFICATES',
            items: [
                {
                    name: 'Training Records',
                    href: '/employee-certificates',
                    icon: ClipboardDocumentListIcon,
                    current: isCurrentPage('/employee-certificates')
                },
                {
                    name: 'Certificate Types',
                    href: '/certificate-types',
                    icon: AcademicCapIcon,
                    current: isCurrentPage('/certificate-types')
                }
            ]
        },
        {
            title: 'REPORTS & ANALYTICS',
            items: [
                {
                    name: 'Reports',
                    href: '/reports',
                    icon: ChartBarIcon,
                    current: isCurrentPage('/reports')
                },
                {
                    name: 'Import/Export',
                    href: '/import-export',
                    icon: ArrowDownTrayIcon,
                    current: isCurrentPage('/import-export')
                }
            ]
        },
        {
            title: 'SYSTEM',
            items: [
                {
                    name: 'Configuration',
                    href: '/configuration',
                    icon: Cog6ToothIcon,
                    current: isCurrentPage('/configuration')
                }
            ]
        }
    ];

    return (
        <div className={classNames(
            'flex flex-col flex-grow bg-slate-800 overflow-y-auto',
            mobile ? 'bg-slate-800' : ''
        )}>
            {/* Logo and branding */}
            <div className="flex items-center flex-shrink-0 px-4 py-6 bg-slate-900">
                <div className="flex items-center">
                    <div className="flex-shrink-0">
                        <div className="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center">
                            <span className="text-white font-bold text-lg">G</span>
                        </div>
                    </div>
                    <div className="ml-3 text-white">
                        <div className="text-sm font-bold">GAPURA</div>
                        <div className="text-xs opacity-90">TRAINING SYSTEM</div>
                    </div>
                </div>
            </div>

            {/* Navigation */}
            <nav className="flex-1 px-3 py-6 space-y-6 overflow-y-auto">
                {navigationSections.map((section) => (
                    <div key={section.title} className="space-y-2">
                        <h3 className="px-3 text-xs font-medium text-slate-400 uppercase tracking-wider">
                            {section.title}
                        </h3>
                        <div className="space-y-1">
                            {section.items.map((item) => {
                                return (
                                    <Link
                                        key={item.name}
                                        href={item.href}
                                        className={classNames(
                                            item.current
                                                ? 'bg-green-600 text-white shadow-lg ring-1 ring-green-500'
                                                : 'text-slate-300 hover:bg-slate-700 hover:text-white',
                                            'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out'
                                        )}
                                    >
                                        <item.icon
                                            className={classNames(
                                                item.current
                                                    ? 'text-green-200'
                                                    : 'text-slate-400 group-hover:text-slate-300',
                                                'mr-3 flex-shrink-0 h-5 w-5 transition-colors duration-200'
                                            )}
                                        />
                                        <div className="flex-1">
                                            <div className="text-sm font-medium">{item.name}</div>
                                            {item.description && (
                                                <div className={classNames(
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
                                            <div className="ml-2 h-2 w-2 bg-green-300 rounded-full"></div>
                                        )}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>

            {/* Current page indicator for debugging */}
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
                        <div className="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                            <span className="text-white text-sm font-medium">
                                {user?.name?.charAt(0) || 'G'}
                            </span>
                        </div>
                    </div>
                    <div className="ml-3 text-white">
                        <div className="text-sm font-medium">{user?.name || 'GAPURA Super Admin'}</div>
                        <div className="text-xs text-slate-400">{user?.email || 'admin@gapura.com'}</div>
                    </div>
                </div>
            </div>
        </div>
    );
}
