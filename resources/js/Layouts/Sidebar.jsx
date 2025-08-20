import ApplicationLogo from '@/Components/ApplicationLogo';
import NavLink from '@/Components/NavLink';
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
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon
} from '@heroicons/react/24/outline';

export default function Sidebar({ user }) {
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
                {
                    name: 'Training Types',
                    href: route('training-types.index'),
                    icon: TagIcon,
                    current: route().current('training-types.*'),
                    description: 'Master jenis pelatihan',
                    roles: ['super_admin']
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
                    href: route('system.templates'),
                    icon: DocumentArrowDownIcon,
                    current: route().current('system.templates*'),
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

    // Quick Status Indicators (if data is available)
    const quickStats = {
        expiring_soon: 15, // This would come from props in real implementation
        expired: 5,
        compliance_rate: 92
    };

    const hasRole = (roles) => {
        if (!roles || roles.length === 0) return true;
        return roles.includes(user?.role);
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
        <aside className="w-64 flex-shrink-0 bg-sidebar-bg text-white flex flex-col h-screen">
            {/* Logo Header */}
            <div className="flex h-16 items-center justify-center bg-primary-green">
                <Link href="/" className="flex items-center space-x-2">
                    <ApplicationLogo className="block h-9 w-auto" />
                    <div className="text-white">
                        <div className="font-bold text-lg">GAPURA</div>
                        <div className="text-xs opacity-90">Training System</div>
                    </div>
                </Link>
            </div>

            {/* Quick Status */}
            {(quickStats.expiring_soon > 0 || quickStats.expired > 0) && (
                <div className="px-4 py-3 bg-gray-800 border-b border-gray-700">
                    <div className="text-xs font-medium text-gray-300 mb-2">Quick Status</div>
                    <div className="space-y-1">
                        {quickStats.expiring_soon > 0 && (
                            <div className="flex items-center justify-between text-xs">
                                <div className="flex items-center">
                                    <ExclamationTriangleIcon className="w-3 h-3 text-yellow-400 mr-1" />
                                    <span className="text-yellow-300">Expiring Soon</span>
                                </div>
                                <span className="bg-yellow-600 text-white px-1.5 py-0.5 rounded text-xs">
                                    {quickStats.expiring_soon}
                                </span>
                            </div>
                        )}
                        {quickStats.expired > 0 && (
                            <div className="flex items-center justify-between text-xs">
                                <div className="flex items-center">
                                    <ExclamationTriangleIcon className="w-3 h-3 text-red-400 mr-1" />
                                    <span className="text-red-300">Expired</span>
                                </div>
                                <span className="bg-red-600 text-white px-1.5 py-0.5 rounded text-xs">
                                    {quickStats.expired}
                                </span>
                            </div>
                        )}
                        <div className="flex items-center justify-between text-xs pt-1 border-t border-gray-700">
                            <div className="flex items-center">
                                <CheckCircleIcon className="w-3 h-3 text-green-400 mr-1" />
                                <span className="text-green-300">Compliance</span>
                            </div>
                            <span className={`px-1.5 py-0.5 rounded text-xs ${
                                quickStats.compliance_rate >= 90 ? 'bg-green-600 text-white' :
                                quickStats.compliance_rate >= 80 ? 'bg-yellow-600 text-white' :
                                'bg-red-600 text-white'
                            }`}>
                                {quickStats.compliance_rate}%
                            </span>
                        </div>
                    </div>
                </div>
            )}

            {/* Navigation */}
            <nav className="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                {navigationItems.map((section) => (
                    <div key={section.section}>
                        {section.title && (
                            <div className="px-3 py-2">
                                <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    {section.title}
                                </h3>
                            </div>
                        )}
                        <div className={section.title ? 'space-y-1' : 'space-y-1 mb-6'}>
                            {section.items.map((item) => {
                                const Icon = item.icon;

                                // Check if user has required role
                                if (!hasRole(item.roles)) {
                                    return null;
                                }

                                return (
                                    <Link
                                        key={item.name}
                                        href={item.href}
                                        className={`group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-150 ${
                                            item.current
                                                ? 'bg-primary-green text-white'
                                                : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                                        }`}
                                        title={item.description}
                                    >
                                        <Icon
                                            className={`mr-3 h-5 w-5 ${
                                                item.current
                                                    ? 'text-white'
                                                    : 'text-gray-400 group-hover:text-white'
                                            }`}
                                        />
                                        <span className="flex-1">{item.name}</span>
                                        {item.badge && (
                                            <span className={`ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getBadgeClasses(item.badgeColor)}`}>
                                                {item.badge}
                                            </span>
                                        )}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </nav>

            {/* User Info */}
            <div className="flex-shrink-0 p-4 border-t border-gray-700">
                <div className="flex items-center">
                    <div className="flex-shrink-0">
                        <div className="w-8 h-8 bg-primary-green rounded-full flex items-center justify-center">
                            <span className="text-sm font-medium text-white">
                                {user?.name?.charAt(0)?.toUpperCase() || 'U'}
                            </span>
                        </div>
                    </div>
                    <div className="ml-3 overflow-hidden">
                        <p className="text-sm font-medium text-white truncate">
                            {user?.name || 'User'}
                        </p>
                        <p className="text-xs text-gray-400 truncate">
                            {user?.role === 'super_admin' && (
                                <span className="flex items-center">
                                    <ShieldCheckIcon className="w-3 h-3 mr-1" />
                                    Super Admin
                                </span>
                            )}
                            {user?.role === 'admin' && 'Admin'}
                            {user?.role === 'user' && 'User'}
                        </p>
                    </div>
                </div>
            </div>
        </aside>
    );
}
