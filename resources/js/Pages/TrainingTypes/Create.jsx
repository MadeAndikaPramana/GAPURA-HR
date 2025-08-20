import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        code: "",
        validity_months: "",
        category: "safety",
        description: "",
        is_active: true,
    });

    function submit(e) {
        e.preventDefault();
        post(route("training-types.store"));
    }

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Create Training Type</h2>}
        >
            <Head title="Create Training Type" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <form onSubmit={submit}>
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
                                    <label className="text-gray-700">Code</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.code}
                                        onChange={(e) => setData("code", e.target.value)}
                                    />
                                    <span className="text-red-600 mt-2">{errors.code}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Validity (Months)</label>
                                    <input
                                        type="number"
                                        className="mt-1 block w-full"
                                        value={data.validity_months}
                                        onChange={(e) => setData("validity_months", e.target.value)}
                                    />
                                    <span className="text-red-600 mt-2">{errors.validity_months}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Category</label>
                                    <select
                                        className="mt-1 block w-full"
                                        value={data.category}
                                        onChange={(e) => setData("category", e.target.value)}
                                    >
                                        <option value="safety">Safety</option>
                                        <option value="operational">Operational</option>
                                        <option value="security">Security</option>
                                        <option value="technical">Technical</option>
                                    </select>
                                    <span className="text-red-600 mt-2">{errors.category}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Description</label>
                                    <textarea
                                        className="mt-1 block w-full"
                                        value={data.description}
                                        onChange={(e) => setData("description", e.target.value)}
                                    ></textarea>
                                    <span className="text-red-600 mt-2">{errors.description}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Is Active</label>
                                    <input
                                        type="checkbox"
                                        className="mt-1"
                                        checked={data.is_active}
                                        onChange={(e) => setData("is_active", e.target.checked)}
                                    />
                                    <span className="text-red-600 mt-2">{errors.is_active}</span>
                                </div>
                                <div className="mt-4">
                                    <button
                                        type="submit"
                                        className="px-6 py-2 font-bold text-white bg-green-500 rounded"
                                        disabled={processing}
                                    >
                                        Save
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
