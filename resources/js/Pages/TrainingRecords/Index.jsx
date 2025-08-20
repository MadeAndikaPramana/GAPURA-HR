import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ auth, trainingRecords }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Training Records</h2>}
        >
            <Head title="Training Records" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <div className="flex items-center justify-between mb-6">
                                <Link
                                    className="px-6 py-2 text-white bg-green-500 rounded-md focus:outline-none"
                                    href={route("training-records.create")}
                                >
                                    Create Training Record
                                </Link>
                                <Link
                                    className="px-6 py-2 text-white bg-blue-500 rounded-md focus:outline-none"
                                    href={route("training-records.bulkImport")}
                                >
                                    Bulk Import Training Records
                                </Link>
                                <Link
                                    className="px-6 py-2 text-white bg-purple-500 rounded-md focus:outline-none"
                                    href={route("training-records.bulkExport")}
                                >
                                    Bulk Export Training Records
                                </Link>
                            </div>
                            <table className="table-fixed w-full">
                                <thead>
                                    <tr className="bg-gray-100">
                                        <th className="px-4 py-2 w-20">No.</th>
                                        <th className="px-4 py-2">Employee</th>
                                        <th className="px-4 py-2">Training Type</th>
                                        <th className="px-4 py-2">Certificate Number</th>
                                        <th className="px-4 py-2">Issuer</th>
                                        <th className="px-4 py-2">Issue Date</th>
                                        <th className="px-4 py-2">Expiry Date</th>
                                        <th className="px-4 py-2">Status</th>
                                        <th className="px-4 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {trainingRecords.map(({ id, employee, training_type, certificate_number, issuer, issue_date, expiry_date, status }) => (
                                        <tr key={id}>
                                            <td className="border px-4 py-2">{id}</td>
                                            <td className="border px-4 py-2">{employee.name}</td>
                                            <td className="border px-4 py-2">{training_type.name}</td>
                                            <td className="border px-4 py-2">{certificate_number}</td>
                                            <td className="border px-4 py-2">{issuer}</td>
                                            <td className="border px-4 py-2">{issue_date}</td>
                                            <td className="border px-4 py-2">{expiry_date}</td>
                                            <td className="border px-4 py-2">{status}</td>
                                            <td className="border px-4 py-2">
                                                <Link
                                                    tabIndex="1"
                                                    className="px-4 py-2 text-sm text-white bg-blue-500 rounded"
                                                    href={route("training-records.edit", id)}
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    onClick={() => {if (window.confirm("Are you sure you want to delete this training record?")) {Inertia.delete(route("training-records.destroy", id));}}}
                                                    className="px-4 py-2 text-sm text-white bg-red-500 rounded"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
