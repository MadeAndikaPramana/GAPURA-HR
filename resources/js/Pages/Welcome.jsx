// resources/js/Pages/Welcome.jsx

import { Link, Head } from '@inertiajs/react';
import {
    ClipboardDocumentListIcon,
    UsersIcon,
    ChartBarIcon,
    ShieldCheckIcon,
    DocumentArrowDownIcon,
    BellIcon,
    ArrowRightIcon,
    CheckCircleIcon
} from '@heroicons/react/24/outline';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    return (
        <>
            <Head title="Welcome to Gapura Training System" />
            <div className="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-gradient-to-br from-green-50 to-blue-50 selection:bg-green-500 selection:text-white">
                <div className="max-w-7xl mx-auto p-6 lg:p-8">
                    {/* Header */}
                    <div className="flex justify-end mb-8">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="font-semibold text-gray-600 hover:text-gray-900 focus:outline focus:outline-2 focus:rounded-sm focus:outline-green-500"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <div className="space-x-4">
                                <Link
                                    href={route('login')}
                                    className="font-semibold text-gray-600 hover:text-gray-900 focus:outline focus:outline-2 focus:rounded-sm focus:outline-green-500"
                                >
                                    Log in
                                </Link>
                                {route().has('register') && (
                                    <Link
                                        href={route('register')}
                                        className="font-semibold text-gray-600 hover:text-gray-900 focus:outline focus:outline-2 focus:rounded-sm focus:outline-green-500"
                                    >
                                        Register
                                    </Link>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Hero Section */}
                    <div className="text-center mb-16">
                        <div className="flex justify-center mb-6">
                            <div className="bg-green-600 rounded-2xl p-4">
                                <div className="text-4xl font-bold text-white">G</div>
                            </div>
                        </div>
                        <h1 className="text-5xl font-bold text-gray-900 mb-4">
                            GAPURA
                            <span className="text-green-600"> Training System</span>
                        </h1>
                        <p className="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                            Comprehensive Training Management System untuk PT. Gapura Angkasa.
                            Kelola data training, sertifikasi, dan compliance karyawan dalam satu platform terintegrasi.
                        </p>

                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="inline-flex items-center px-8 py-4 bg-green-600 border border-transparent rounded-md font-semibold text-lg text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Go to Dashboard
                                <ArrowRightIcon className="w-5 h-5 ml-2" />
                            </Link>
                        ) : (
                            <Link
                                href={route('login')}
                                className="inline-flex items-center px-8 py-4 bg-green-600 border border-transparent rounded-md font-semibold text-lg text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Get Started
                                <ArrowRightIcon className="w-5 h-5 ml-2" />
                            </Link>
                        )}
                    </div>

                    {/* Features Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                        <div className="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition-shadow">
                            <div className="bg-blue-100 rounded-lg p-3 w-fit mb-4">
                                <UsersIcon className="w-8 h-8 text-blue-600" />
                            </div>
                            <h3 className="text-xl font-semibold text-gray-900 mb-3">Employee Management</h3>
                            <p className="text-gray-600 mb-4">
                                Centralized employee data management dengan support untuk 12 departemen MPGA.
                                Master data lengkap dengan background check tracking.
                            </p>
                            <ul className="space-y-2 text-sm text-gray-600">
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    CRUD operations
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Department integration
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Excel import/export
                                </li>
                            </ul>
                        </div>

                        <div className="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition-shadow">
                            <div className="bg-green-100 rounded-lg p-3 w-fit mb-4">
                                <ClipboardDocumentListIcon className="w-8 h-8 text-green-600" />
                            </div>
                            <h3 className="text-xl font-semibold text-gray-900 mb-3">Training Records</h3>
                            <p className="text-gray-600 mb-4">
                                Central repository untuk semua sertifikat karyawan dengan certificate lifecycle tracking.
                                One-to-many dan many-to-many relationships.
                            </p>
                            <ul className="space-y-2 text-sm text-gray-600">
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Certificate management
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Expiry date tracking
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Training provider data
                                </li>
                            </ul>
                        </div>

                        <div className="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition-shadow">
                            <div className="bg-yellow-100 rounded-lg p-3 w-fit mb-4">
                                <BellIcon className="w-8 h-8 text-yellow-600" />
                            </div>
                            <h3 className="text-xl font-semibold text-gray-900 mb-3">Compliance Monitoring</h3>
                            <p className="text-gray-600 mb-4">
                                Tracking sertifikat yang expired/akan expired dengan automated notifications.
                                Real-time compliance dashboard.
                            </p>
                            <ul className="space-y-2 text-sm text-gray-600">
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Auto expiry alerts
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Compliance dashboard
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Analytics & reports
                                </li>
                            </ul>
                        </div>

                        <div className="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition-shadow">
                            <div className="bg-purple-100 rounded-lg p-3 w-fit mb-4">
                                <ChartBarIcon className="w-8 h-8 text-purple-600" />
                            </div>
                            <h3 className="text-xl font-semibold text-gray-900 mb-3">Analytics & Reporting</h3>
                            <p className="text-gray-600 mb-4">
                                Comprehensive reporting dengan export data untuk audit dan compliance.
                                Training analytics per departemen dan jenis training.
                            </p>
                            <ul className="space-y-2 text-sm text-gray-600">
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Department analytics
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Training statistics
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Audit reports
                                </li>
                            </ul>
                        </div>

                        <div className="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition-shadow">
                            <div className="bg-indigo-100 rounded-lg p-3 w-fit mb-4">
                                <DocumentArrowDownIcon className="w-8 h-8 text-indigo-600" />
                            </div>
                            <h3 className="text-xl font-semibold text-gray-900 mb-3">Import/Export Automation</h3>
                            <p className="text-gray-600 mb-4">
                                CSV/Excel import dengan template matching dan export ke Excel dengan formatting.
                                Batch operations untuk mass updates.
                            </p>
                            <ul className="space-y-2 text-sm text-gray-600">
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Excel templates
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Batch operations
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Format validation
                                </li>
                            </ul>
                        </div>

                        <div className="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition-shadow">
                            <div className="bg-red-100 rounded-lg p-3 w-fit mb-4">
                                <ShieldCheckIcon className="w-8 h-8 text-red-600" />
                            </div>
                            <h3 className="text-xl font-semibold text-gray-900 mb-3">Security & Compliance</h3>
                            <p className="text-gray-600 mb-4">
                                Role-based access control dengan user authentication.
                                Audit trail untuk semua aktivitas sistem.
                            </p>
                            <ul className="space-y-2 text-sm text-gray-600">
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Role management
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Activity logging
                                </li>
                                <li className="flex items-center">
                                    <CheckCircleIcon className="w-4 h-4 text-green-500 mr-2" />
                                    Data protection
                                </li>
                            </ul>
                        </div>
                    </div>

                    {/* System Info */}
                    <div className="text-center border-t border-gray-200 pt-8">
                        <div className="bg-white rounded-lg shadow p-6 max-w-2xl mx-auto">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Development Status</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span className="font-medium text-gray-700">Phase 1:</span>
                                    <span className="text-green-600 ml-2">Employee Master Data ✅</span>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-700">Phase 2:</span>
                                    <span className="text-yellow-600 ml-2">Training Records 🚧</span>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-700">Laravel:</span>
                                    <span className="text-gray-600 ml-2">v{laravelVersion}</span>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-700">PHP:</span>
                                    <span className="text-gray-600 ml-2">v{phpVersion}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
