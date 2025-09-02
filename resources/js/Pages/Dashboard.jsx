import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({ auth }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h1 className="text-2xl font-bold mb-4">🗂️ GAPURA Employee Data Container System</h1>
                            <p className="text-gray-600 mb-6">Welcome to the digital employee container system!</p>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="bg-blue-50 p-6 rounded-lg">
                                    <h3 className="font-semibold text-blue-900 mb-2">👥 Employees</h3>
                                    <p className="text-blue-700 text-sm">Manage employee profiles and containers</p>
                                    <a href="/employees" className="inline-block mt-3 text-blue-600 hover:text-blue-800 font-medium">
                                        View Employees →
                                    </a>
                                </div>

                                <div className="bg-green-50 p-6 rounded-lg">
                                    <h3 className="font-semibold text-green-900 mb-2">🏆 Certificates</h3>
                                    <p className="text-green-700 text-sm">Track training certificates and compliance</p>
                                    <a href="/employee-certificates" className="inline-block mt-3 text-green-600 hover:text-green-800 font-medium">
                                        View Certificates →
                                    </a>
                                </div>

                                <div className="bg-purple-50 p-6 rounded-lg">
                                    <h3 className="font-semibold text-purple-900 mb-2">📋 Certificate Types</h3>
                                    <p className="text-purple-700 text-sm">Manage certificate types and categories</p>
                                    <a href="/certificate-types" className="inline-block mt-3 text-purple-600 hover:text-purple-800 font-medium">
                                        View Types →
                                    </a>
                                </div>
                            </div>

                            <div className="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <h4 className="font-semibold text-yellow-900 mb-2">🚀 Phase 1 Features</h4>
                                <ul className="text-sm text-yellow-800 space-y-1">
                                    <li>✅ Employee digital containers</li>
                                    <li>✅ Certificate management with recurrent tracking</li>
                                    <li>✅ Background check document storage</li>
                                    <li>✅ MPGA Excel import functionality</li>
                                    <li>✅ Compliance monitoring & reporting</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
