import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Create({ auth, employees, trainingTypes }) {
    const { data, setData, post, processing, errors } = useForm({
        employee_id: "",
        training_type_id: "",
        certificate_number: "",
        issuer: "GLC",
        issue_date: "",
        expiry_date: "",
        notes: "",
    });

    useEffect(() => {
        if (data.issue_date && data.training_type_id) {
            const selectedTrainingType = trainingTypes.find(type => type.id == data.training_type_id);
            if (selectedTrainingType) {
                const issueDate = new Date(data.issue_date);
                const expiryDate = new Date(issueDate);
                expiryDate.setMonth(issueDate.getMonth() + selectedTrainingType.validity_months);
                setData("expiry_date", expiryDate.toISOString().split('T')[0]);
            }
        }
    }, [data.issue_date, data.training_type_id]);

    function submit(e) {
        e.preventDefault();
        post(route("training-records.store"));
    }

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Create Training Record</h2>}
        >
            <Head title="Create Training Record" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <form onSubmit={submit}>
                                <div className="mb-4">
                                    <label className="text-gray-700">Employee</label>
                                    <select
                                        className="mt-1 block w-full"
                                        value={data.employee_id}
                                        onChange={(e) => setData("employee_id", e.target.value)}
                                    >
                                        <option value="">Select Employee</option>
                                        {employees.map((employee) => (
                                            <option key={employee.id} value={employee.id}>
                                                {employee.name}
                                            </option>
                                        ))}
                                    </select>
                                    <span className="text-red-600 mt-2">{errors.employee_id}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Training Type</label>
                                    <select
                                        className="mt-1 block w-full"
                                        value={data.training_type_id}
                                        onChange={(e) => setData("training_type_id", e.target.value)}
                                    >
                                        <option value="">Select Training Type</option>
                                        {trainingTypes.map((type) => (
                                            <option key={type.id} value={type.id}>
                                                {type.name}
                                            </option>
                                        ))}
                                    </select>
                                    <span className="text-red-600 mt-2">{errors.training_type_id}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Certificate Number</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.certificate_number}
                                        onChange={(e) => setData("certificate_number", e.target.value)}
                                    />
                                    <span className="text-red-600 mt-2">{errors.certificate_number}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Issuer</label>
                                    <select
                                        className="mt-1 block w-full"
                                        value={data.issuer}
                                        onChange={(e) => setData("issuer", e.target.value)}
                                    >
                                        <option value="GLC">GLC</option>
                                        <option value="DGCA">DGCA</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <span className="text-red-600 mt-2">{errors.issuer}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Issue Date</label>
                                    <input
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.issue_date}
                                        onChange={(e) => setData("issue_date", e.target.value)}
                                    />
                                    <span className="text-red-600 mt-2">{errors.issue_date}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Expiry Date</label>
                                    <input
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.expiry_date}
                                        readOnly
                                    />
                                    <span className="text-red-600 mt-2">{errors.expiry_date}</span>
                                </div>
                                <div className="mb-4">
                                    <label className="text-gray-700">Notes</label>
                                    <textarea
                                        className="mt-1 block w-full"
                                        value={data.notes}
                                        onChange={(e) => setData("notes", e.target.value)}
                                    ></textarea>
                                    <span className="text-red-600 mt-2">{errors.notes}</span>
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
