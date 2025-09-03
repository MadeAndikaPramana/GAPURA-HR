// resources/js/Layouts/Sidebar.jsx

import { Link, usePage, router } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import {
    HomeIcon,
    UsersIcon,
    DocumentTextIcon,
    AcademicCapIcon,
    ClipboardDocumentListIcon,
    ChartBarIcon,
    CogIcon,
    ArrowLeftOnRectangleIcon,
    FolderIcon,
    DocumentIcon,
} from '@heroicons/react/24/outline';

const navigationSections = [
    {
        title: 'MAIN',
        items: [
            { name: 'Dashboard', href: '/dashboard', icon: HomeIcon },
        ]
    },
    {
        title: 'DATA MANAGEMENT',
        items: [
            { name: 'Employee Containers', href: '/employees', icon: UsersIcon },
            { name: 'Data Karyawan', href: '/employees', icon: FolderIcon },
        ]
    },
    {
        title: 'TRAINING & CERTIFICATES',
        items: [
            { name: 'Training Records', href: '/employee-certificates', icon: AcademicCapIcon },
            { name: 'Certificate Types', href: '/certificate-types', icon: ClipboardDocumentListIcon },
        ]
    },
    {
        title: 'REPORTS & ANALYTICS',
        items: [
            { name: 'Reports', href: '/reports', icon: ChartBarIcon },
            { name: 'Import/Export', href: '/import-export', icon: DocumentIcon },
        ]
    },
    {
        title: 'SYSTEM',
        items: [
            { name: 'Configuration', href: '/settings', icon: CogIcon },
        ]
    }
];

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export default function Sidebar({ user, mobile = false }) {
    const { url } = usePage();

    const logout = (e) => {
        e.preventDefault();
        router.post('/logout');
    };

    return (
        <div className="flex flex-col h-full bg-slate-800 border-r border-slate-700">
            {/* Logo */}
            <div className="flex items-center justify-center h-16 px-4 border-b border-slate-700 bg-green-600">
                <div className="flex items-center text-white">
                    <div className="w-8 h-8 bg-white rounded-lg flex items-center justify-center mr-3">
                        <span className="text-green-600 font-bold text-lg">G</span>
                    </div>
                    <div>
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
                                const current = url.startsWith(item.href);
                                return (
                                    <Link
                                        key={item.name}
                                        href={item.href}
                                        className={classNames(
                                            current
                                                ? 'bg-green-600 text-white shadow-lg'
                                                : 'text-slate-300 hover:bg-slate-700 hover:text-white',
                                            'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out'
                                        )}
                                    >
                                        <item.icon
                                            className={classNames(
                                                current
                                                    ? 'text-green-100'
                                                    : 'text-slate-400 group-hover:text-slate-200',
                                                'mr-3 h-5 w-5 flex-shrink-0'
                                            )}
                                            aria-hidden="true"
                                        />
                                        {item.name}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>

            {/* User info */}
            <div className="flex-shrink-0 border-t border-slate-700 p-4 bg-slate-750">
                <div className="flex items-center mb-3">
                    <div className="flex-shrink-0">
                        <div className="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center">
                            <span className="text-white font-medium text-sm">
                                {user?.name?.charAt(0).toUpperCase() || 'A'}
                            </span>
                        </div>
                    </div>
                    <div className="ml-3">
                        <div className="text-sm font-medium text-slate-200">{user?.name || 'Admin'}</div>
                        <div className="text-xs text-slate-400">{user?.email || 'admin@gapura.com'}</div>
                    </div>
                </div>

                {/* Logout button */}
                <button
                    onClick={logout}
                    className="w-full flex items-center px-3 py-2 text-sm font-medium text-slate-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200"
                >
                    <ArrowLeftOnRectangleIcon className="mr-3 h-5 w-5 text-slate-400" aria-hidden="true" />
                    Sign out
                </button>
            </div>
        </div>
    );
}
