import { Link } from '@inertiajs/react';
import {
    HomeIcon,
    UsersIcon,
    ClipboardDocumentListIcon,
    TagIcon,
    BuildingOfficeIcon,
    DocumentArrowDownIcon,
    ChartBarIcon,
    CogIcon,
    ShieldCheckIcon
} from '@heroicons/react/24/outline';

export default function Sidebar({ user, mobile = false }) {
    const navigationItems = [
        // Core Navigation (All Users)
        {
            section: 'main',
            items: [
                {
                    name: 'Dashboard',
                    href: route('dashboard'),
                    icon: HomeIcon,
                    current: route().current('dashboard'),
                    description: 'Overview sistem training'
                }
            ]
        },

        // Data Management (Admin & Super Admin)
        {
            section: 'data',
            title: 'Data Management',
            items: [
                {
                    name: 'Data Karyawan',
                    href: route('employees.index'),
                    icon: UsersIcon,
                    current: route().current('employees.*'),
                    description: 'Kelola data karyawan lengkap',
                    roles: ['admin', 'super_admin']
                },
                {
                    name: 'Training Records',
                    href: route('training-records.index'),
                    icon: ClipboardDocumentListIcon,
                    current: route().current('training-records.*'),
                    description: 'Data pelatihan dan sertifikasi',
                    roles: ['admin', 'super_admin']
                }
            ]
        },

        // Configuration (Super Admin Only)
        {
            section: 'config',
            title: 'Configuration',
            items: [
                // 🚀 ADD THIS: Training Types menu item
                {
                    name: 'Training Types',
                    href: route('training-types.index'),
                    icon: TagIcon,
                    current: route().current('training-types.*'),
                    description: 'Master jenis pelatihan',
                    roles: ['super_admin', 'admin'] // Allow both admin and super_admin
                },
                {
                    name: 'Training Providers',
                    href: route('training-providers.index'),
                    icon: BuildingOfficeIcon,
                    current: route().current('training-providers.*'),
                    description: 'Penyedia layanan training',
                    roles: ['super_admin', 'admin']
                },
                {
                    name: 'Departments',
                    href: route('departments.index'),
                    icon: BuildingOfficeIcon,
                    current: route().current('departments.*'),
                    description: 'Manajemen departemen',
                    roles: ['super_admin']
                }
            ]
        },

        // Reports & Analytics
        {
            section: 'reports',
            title: 'Reports & Analytics',
            items: [
                {
                    name: 'Reports',
                    href: route('dashboard'), // Will be updated with actual reports route
                    icon: ChartBarIcon,
                    current: route().current('reports.*'),
                    description: 'Laporan dan analisis',
                    roles: ['admin', 'super_admin'],
                    badge: 'Coming Soon',
                    badgeColor: 'blue'
                },
                {
                    name: 'Import/Export',
                    href: route('import-export.index'),
                    icon: DocumentArrowDownIcon,
                    current: route().current('import-export.*'),
                    description: 'Template dan import data',
                    roles: ['admin', 'super_admin']
                }
            ]
        },

        // System (Super Admin Only)
        {
            section: 'system',
            title: 'System',
            items: [
                {
                    name: 'System Stats',
                    href: route('system.stats'),
                    icon: CogIcon,
                    current: route().current('system.stats'),
                    description: 'Statistik sistem',
                    roles: ['super_admin']
                }
            ]
        }
    ];

    const hasRole = (roles) => {
        if (!roles || roles.length === 0) return true;
        // For now, let's assume user has access (since role system might not be fully implemented)
        return true;
        // return roles.includes(user?.role);
    };

    const getBadgeClasses = (color) => {
        const colors = {
            red: 'bg-red-100 text-red-800',
            yellow: 'bg-yellow-100 text-yellow-800',
            green: 'bg-green-100 text-green-800',
            blue: 'bg-blue-100 text-blue-800',
            purple: 'bg-purple-100 text-purple-800'
        };
        return colors[color] || colors.blue;
    };

    return (
        <aside className="w-64 flex-shrink-0 bg-gray-900 text-white flex flex-col h-screen">
            {/* Enhanced Header */}
            <div className="flex h-20 items-center justify-center bg-gradient-to-r from-green-600 to-green-700 shadow-lg">
                <Link href="/" className="text-center">
                    <div className="text-white">
                        <div className="font-bold text-xl tracking-wider">GAPURA</div>
                        <div className="text-sm opacity-90 tracking-wide">TRAINING SYSTEM</div>
                    </div>
                </Link>
            </div>

            {/* Navigation */}
            <nav className="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                {navigationItems.map((section) => (
                    <div key={section.section}>
                        {section.title && (
                            <div className="px-3 py-3">
                                <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    {section.title}
                                </h3>
                            </div>
                        )}
                        <div className={section.title ? 'space-y-1' : ''}>
                            {section.items.map((item) => {
                                // Check if user has required role
                                if (!hasRole(item.roles)) {
                                    return null;
                                }

                                return (
                                    <Link
                                        key={item.name}
                                        href={item.href}
                                        className={`group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-150 ease-in-out ${
                                            item.current
                                                ? 'bg-green-600 text-white shadow-sm'
                                                : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                                        }`}
                                    >
                                        <item.icon
                                            className={`flex-shrink-0 h-5 w-5 mr-3 transition-colors duration-150 ease-in-out ${
                                                item.current
                                                    ? 'text-green-200'
                                                    : 'text-gray-400 group-hover:text-gray-300'
                                            }`}
                                        />
                                        <div className="flex-1">
                                            <div className="flex items-center justify-between">
                                                <span className="truncate">{item.name}</span>
                                                {item.badge && (
                                                    <span className={`ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getBadgeClasses(item.badgeColor)}`}>
                                                        {item.badge}
                                                    </span>
                                                )}
                                            </div>
                                            {item.description && (
                                                <div className={`text-xs mt-0.5 ${
                                                    item.current ? 'text-green-100' : 'text-gray-500'
                                                }`}>
                                                    {item.description}
                                                </div>
                                            )}
                                        </div>
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>

            {/* User Info Footer */}
            <div className="flex-shrink-0 p-4 border-t border-gray-700">
                <div className="flex items-center">
                    <div className="flex-shrink-0">
                        <div className="h-10 w-10 bg-green-600 rounded-full flex items-center justify-center">
                            <span className="text-sm font-medium text-white">
                                {user?.name?.charAt(0)?.toUpperCase() || 'G'}
                            </span>
                        </div>
                    </div>
                    <div className="ml-3 flex-1 min-w-0">
                        <p className="text-sm font-medium text-white truncate">
                            GAPURA Super Admin
                        </p>
                        <p className="text-xs text-gray-400 truncate">
                            {user?.email || 'admin@gapura.com'}
                        </p>
                    </div>
                </div>
            </div>
        </aside>
    );
}
