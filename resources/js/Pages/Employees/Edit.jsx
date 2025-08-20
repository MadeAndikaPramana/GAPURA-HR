import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Edit({ auth, employee }) {
    const { data, setData, put, processing, errors } = useForm({
        employee_id: employee.employee_id,
        name: employee.name,
        position: employee.position,
        status: employee.status,
    });

    function submit(e) {
        e.preventDefault();
        put(route("employees.update", employee.id));
    }

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Edit Employee</h2>}
        >
            <Head title="Edit Employee" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <form onSubmit={submit}>
                                <div className="mb-4">
                                    <label className="text-gray-700">Employee ID</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.employee_id}
                                        onChange={(e) => setData("employee_id", e.target.value)}
                                    />
                                    <span className="text-red-600 mt-2">{errors.employee_id}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Name</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.name}
                                        onChange={(e) => setData("name", e.target.value)}
                                    />
                                    <span className="text-red-600 mt-2">{errors.name}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Position</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.position}
                                        onChange={(e) => setData("position", e.target.value)}
                                    />
                                    <span className="text-red-600 mt-2">{errors.position}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Status</label>
                                    <select
                                        className="mt-1 block w-full"
                                        value={data.status}
                                        onChange={(e) => setData("status", e.target.value)}
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    <span className="text-red-600 mt-2">{errors.status}</span>
                                </div>
                                <div className="mt-4">
                                    <button
                                        type="submit"
                                        className="px-6 py-2 font-bold text-white bg-green-500 rounded"
                                        disabled={processing}
                                    >
                                        Update
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
