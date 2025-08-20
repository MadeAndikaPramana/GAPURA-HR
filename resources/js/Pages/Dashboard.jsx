import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({ auth, total_employees, active_certificates, expiring_soon, expired }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-700">Total Employees</h3>
                            <p className="text-3xl font-bold text-gray-900">{total_employees}</p>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-700">Active Certificates</h3>
                            <p className="text-3xl font-bold text-green-500">{active_certificates}</p>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-700">Expiring Soon</h3>
                            <p className="text-3xl font-bold text-yellow-500">{expiring_soon}</p>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-700">Expired Certificates</h3>
                            <p className="text-3xl font-bold text-red-500">{expired}</p>
                        </div>
                    </div>

                    {/* Compliance Charts and Recent Activities Table will go here */}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}