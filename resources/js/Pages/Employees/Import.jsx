import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Import({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        file: null,
    });

    function submit(e) {
        e.preventDefault();
        post(route("employees.handleImport"));
    }

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Import Employees</h2>}
        >
            <Head title="Import Employees" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <form onSubmit={submit}>
                                <div className="mb-4">
                                    <label className="text-gray-700">CSV File</label>
                                    <input
                                        type="file"
                                        className="mt-1 block w-full"
                                        onChange={(e) => setData("file", e.target.files[0])}
                                    />
                                    <span className="text-red-600 mt-2">{errors.file}</span>
                                </div>
                                <div className="mt-4">
                                    <button
                                        type="submit"
                                        className="px-6 py-2 font-bold text-white bg-green-500 rounded"
                                        disabled={processing}
                                    >
                                        Import
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
