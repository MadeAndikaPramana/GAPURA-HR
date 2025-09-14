import { Link, Head } from '@inertiajs/react';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    return (
        <>
            <Head title="Welcome - GAPURA Employee Container System" />
            <div className="bg-gray-50 text-black/50">
                <div className="relative min-h-screen flex flex-col items-center justify-center selection:bg-blue-500 selection:text-white">
                    <div className="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                        <header className="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
                            <div className="flex lg:justify-center lg:col-start-2">
                                <div className="bg-white p-6 rounded-lg shadow-lg">
                                    <h1 className="text-3xl font-bold text-blue-600 mb-2">🗂️ GAPURA</h1>
                                    <p className="text-gray-600 text-sm">Employee Data Container System</p>
                                </div>
                            </div>
                            <nav className="-mx-3 flex flex-1 justify-end">
                                {auth.user ? (
                                    <Link
                                        href={route('sdm.index')}
                                        className="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-blue-500"
                                    >
                                        SDM
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={route('login')}
                                            className="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-blue-500"
                                        >
                                            Log in
                                        </Link>
                                        {route().has('register') && (
                                            <Link
                                                href={route('register')}
                                                className="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-blue-500 ml-4"
                                            >
                                                Register
                                            </Link>
                                        )}
                                    </>
                                )}
                            </nav>
                        </header>

                        <main className="mt-6">
                            <div className="grid gap-6 lg:grid-cols-2 lg:gap-8">
                                <div className="bg-white p-6 rounded-lg shadow-lg">
                                    <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                        🎯 Phase 1: Foundation Ready
                                    </h2>
                                    <p className="text-gray-600 text-sm mb-4">
                                        Digital employee containers untuk organize certificate & background check data.
                                    </p>
                                    <ul className="text-sm text-gray-600 space-y-2">
                                        <li>✅ Employee digital containers</li>
                                        <li>✅ Certificate management</li>
                                        <li>✅ Background check storage</li>
                                        <li>✅ MPGA data import ready</li>
                                    </ul>
                                </div>

                                <div className="bg-white p-6 rounded-lg shadow-lg">
                                    <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                        🚀 Quick Access
                                    </h2>
                                    <div className="space-y-3">
                                        {auth.user ? (
                                            <>
                                                <Link href="/employees" className="block w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-center transition-colors">
                                                    👥 Employee Containers
                                                </Link>
                                                <Link href="/certificate-types" className="block w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-center transition-colors">
                                                    📋 Certificate Types
                                                </Link>
                                            </>
                                        ) : (
                                            <Link href={route('login')} className="block w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-center transition-colors">
                                                🔐 Login to Access System
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div className="mt-8 bg-white p-6 rounded-lg shadow-lg">
                                <h3 className="text-lg font-semibold text-gray-900 mb-3">System Information</h3>
                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span className="text-gray-600">Laravel Version:</span>
                                        <span className="ml-2 font-mono">{laravelVersion}</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">PHP Version:</span>
                                        <span className="ml-2 font-mono">{phpVersion}</span>
                                    </div>
                                </div>
                            </div>
                        </main>

                        <footer className="py-16 text-center text-sm text-black/70">
                            GAPURA Employee Data Container System - Phase 1 Foundation
                        </footer>
                    </div>
                </div>
            </div>
        </>
    );
}
