import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ auth, trainingTypes }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Training Types</h2>}
        >
            <Head title="Training Types" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <div className="flex items-center justify-between mb-6">
                                <Link
                                    className="px-6 py-2 text-white bg-green-500 rounded-md focus:outline-none"
                                    href={route("training-types.create")}
                                >
                                    Create Training Type
                                </Link>
                            </div>
                            <table className="table-fixed w-full">
                                <thead>
                                    <tr className="bg-gray-100">
                                        <th className="px-4 py-2 w-20">No.</th>
                                        <th className="px-4 py-2">Name</th>
                                        <th className="px-4 py-2">Code</th>
                                        <th className="px-4 py-2">Validity (Months)</th>
                                        <th className="px-4 py-2">Category</th>
                                        <th className="px-4 py-2">Status</th>
                                        <th className="px-4 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {trainingTypes.map(({ id, name, code, validity_months, category, is_active }) => (
                                        <tr key={id}>
                                            <td className="border px-4 py-2">{id}</td>
                                            <td className="border px-4 py-2">{name}</td>
                                            <td className="border px-4 py-2">{code}</td>
                                            <td className="border px-4 py-2">{validity_months}</td>
                                            <td className="border px-4 py-2">{category}</td>
                                            <td className="border px-4 py-2">{is_active ? 'Active' : 'Inactive'}</td>
                                            <td className="border px-4 py-2">
                                                <Link
                                                    tabIndex="1"
                                                    className="px-4 py-2 text-sm text-white bg-blue-500 rounded"
                                                    href={route("training-types.edit", id)}
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    onClick={() => {if (window.confirm("Are you sure you want to delete this training type?")) {Inertia.delete(route("training-types.destroy", id));}}}
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
