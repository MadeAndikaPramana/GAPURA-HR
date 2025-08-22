// resources/js/Pages/Employees/Create.jsx

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    UserPlusIcon,
    UserIcon,
    BuildingOfficeIcon,
    IdentificationIcon,
    CalendarDaysIcon,
    BriefcaseIcon,
    DocumentTextIcon,
    InformationCircleIcon
} from '@heroicons/react/24/outline';

export default function Create({ auth, departments }) {
    const { data, setData, post, processing, errors } = useForm({
        employee_id: '',
        name: '',
        nip: '',
        department_id: '',
        position: '',
        status: 'active',
        hire_date: '',
        background_check_date: '',
        background_check_status: 'cleared',
        background_check_notes: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('employees.store'));
    };

    const generateEmployeeId = () => {
        // Generate GAP ID format: GAP + 4 digit number
        const currentCount = Math.floor(Math.random() * 9999) + 1;
        const generated = `GAP${currentCount.toString().padStart(4, '0')}`;
        setData('employee_id', generated);
    };

    const generateNIP = () => {
        // Generate NIP format: 8 digit number (sesuai format MPGA)
        const year = new Date().getFullYear().toString().slice(-2);
        const month = (new Date().getMonth() + 1).toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 9999).toString().padStart(4, '0');
        const generated = `${year}${month}${random}`;
        setData('nip', generated);
    };

    // MPGA Department mapping
    const mpgaDepartments = [
        { code: 'DEDICATED', name: 'Dedicated Services', positions: ['AE Operator', 'Controller', 'Supervisor'] },
        { code: 'LOADING', name: 'Loading Operations', positions: ['Loading Agent', 'Loading Supervisor', 'Equipment Operator'] },
        { code: 'RAMP', name: 'Ramp Operations', positions: ['Ramp Agent', 'Senior Ramp Agent', 'Ramp Supervisor'] },
        { code: 'PORTER', name: 'Porter Services', positions: ['Porter', 'Senior Porter', 'Porter Supervisor'] },
        { code: 'GSE', name: 'GSE Operations', positions: ['GSE Operator', 'GSE Mechanic', 'GSE Supervisor'] },
        { code: 'AVSEC', name: 'Aviation Security', positions: ['Security Officer', 'Security Supervisor', 'AVSEC Inspector'] },
        { code: 'CARGO', name: 'Cargo Operations', positions: ['Cargo Handler', 'Cargo Supervisor', 'Cargo Officer'] },
        { code: 'ARRIVAL', name: 'Arrival Services', positions: ['Arrival Agent', 'Arrival Supervisor', 'Customer Service'] },
        { code: 'LOCO', name: 'Locomotive Operations', positions: ['Equipment Operator', 'Maintenance Staff', 'Supervisor'] },
        { code: 'ULD', name: 'ULD Operations', positions: ['ULD Handler', 'ULD Inspector', 'ULD Supervisor'] },
        { code: 'LNF', name: 'Lost & Found', positions: ['Lost & Found Officer', 'Customer Service', 'Supervisor'] },
        { code: 'FLOP', name: 'Flight Operations', positions: ['Flight Operations Officer', 'FOO Licensed', 'Operations Supervisor'] }
    ];

    const selectedDepartment = mpgaDepartments.find(dept =>
        departments.find(d => d.id == data.department_id)?.code === dept.code
    );

    const handleDepartmentChange = (departmentId) => {
        setData('department_id', departmentId);
        setData('position', ''); // Reset position when department changes
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('employees.index')}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-2" />
                            Back to Employees
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Tambah Karyawan Baru
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Tambahkan data karyawan baru ke sistem training GAPURA sesuai format MPGA
                            </p>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title="Tambah Karyawan" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {/* Info Banner */}
                    <div className="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div className="flex items-start">
                            <InformationCircleIcon className="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" />
                            <div>
                                <h3 className="text-sm font-medium text-blue-900">Format Data MPGA</h3>
                                <p className="text-sm text-blue-700 mt-1">
                                    Pastikan data yang diinput sesuai dengan format Excel MPGA: NAMA, NIPP (8 digit), Dept/Unit, dan Jabatan.
                                    Employee ID (GAP) akan digenerate otomatis untuk sistem internal.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        {/* Informasi Dasar */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <UserIcon className="w-5 h-5 mr-2" />
                                    Informasi Dasar
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Data identitas karyawan sesuai format MPGA
                                </p>
                            </div>
                            <div className="px-6 py-6 space-y-6">
                                {/* Nama Lengkap */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Nama Lengkap *
                                    </label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value.toUpperCase())}
                                        className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                            errors.name ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        placeholder="Contoh: PUTU EKA RESMAWAN"
                                        required
                                    />
                                    {errors.name && (
                                        <p className="mt-2 text-sm text-red-600">{errors.name}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        Masukkan nama lengkap dengan huruf kapital sesuai format MPGA
                                    </p>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Employee ID (GAP) */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Employee ID (GAP) *
                                        </label>
                                        <div className="flex space-x-2">
                                            <input
                                                type="text"
                                                value={data.employee_id}
                                                onChange={(e) => setData('employee_id', e.target.value)}
                                                className={`flex-1 border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                    errors.employee_id ? 'border-red-300' : 'border-gray-300'
                                                }`}
                                                placeholder="GAP0001"
                                                required
                                            />
                                            <button
                                                type="button"
                                                onClick={generateEmployeeId}
                                                className="px-3 py-2 text-sm bg-green-100 border border-green-300 rounded-md hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-green-500 text-green-700"
                                            >
                                                Generate
                                            </button>
                                        </div>
                                        {errors.employee_id && (
                                            <p className="mt-2 text-sm text-red-600">{errors.employee_id}</p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500">
                                            ID unik untuk sistem internal GAPURA
                                        </p>
                                    </div>

                                    {/* NIP/NIPP */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            NIP / NIPP *
                                        </label>
                                        <div className="flex space-x-2">
                                            <input
                                                type="text"
                                                value={data.nip}
                                                onChange={(e) => setData('nip', e.target.value)}
                                                className={`flex-1 border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                    errors.nip ? 'border-red-300' : 'border-gray-300'
                                                }`}
                                                placeholder="21608001"
                                                maxLength="8"
                                                required
                                            />
                                            <button
                                                type="button"
                                                onClick={generateNIP}
                                                className="px-3 py-2 text-sm bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-blue-700"
                                            >
                                                Generate
                                            </button>
                                        </div>
                                        {errors.nip && (
                                            <p className="mt-2 text-sm text-red-600">{errors.nip}</p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500">
                                            Nomor Induk Pegawai (8 digit) sesuai format MPGA
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Informasi Departemen & Jabatan */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <BuildingOfficeIcon className="w-5 h-5 mr-2" />
                                    Departemen & Jabatan
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Unit kerja dan posisi karyawan sesuai struktur MPGA
                                </p>
                            </div>
                            <div className="px-6 py-6 space-y-6">
                                {/* Department */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Departemen / Unit *
                                    </label>
                                    <select
                                        value={data.department_id}
                                        onChange={(e) => handleDepartmentChange(e.target.value)}
                                        className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                            errors.department_id ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        required
                                    >
                                        <option value="">Pilih Departemen</option>
                                        {departments.map((department) => (
                                            <option key={department.id} value={department.id}>
                                                {department.name} ({department.code})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.department_id && (
                                        <p className="mt-2 text-sm text-red-600">{errors.department_id}</p>
                                    )}
                                </div>

                                {/* Position */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Jabatan / Position *
                                    </label>
                                    {selectedDepartment ? (
                                        <select
                                            value={data.position}
                                            onChange={(e) => setData('position', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.position ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            required
                                        >
                                            <option value="">Pilih Jabatan</option>
                                            {selectedDepartment.positions.map((position) => (
                                                <option key={position} value={position}>
                                                    {position}
                                                </option>
                                            ))}
                                        </select>
                                    ) : (
                                        <input
                                            type="text"
                                            value={data.position}
                                            onChange={(e) => setData('position', e.target.value)}
                                            className={`w-full border rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                                errors.position ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            placeholder="Contoh: Porter, AE Operator, Controller"
                                            required
                                        />
                                    )}
                                    {errors.position && (
                                        <p className="mt-2 text-sm text-red-600">{errors.position}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        Pilih departemen terlebih dahulu untuk melihat jabatan yang tersedia
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Informasi Status & Tanggal */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <CalendarDaysIcon className="w-5 h-5 mr-2" />
                                    Status & Tanggal
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Status kepegawaian dan informasi tanggal penting
                                </p>
                            </div>
                            <div className="px-6 py-6 space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Status */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Status Karyawan *
                                        </label>
                                        <select
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                            required
                                        >
                                            <option value="active">Aktif</option>
                                            <option value="inactive">Tidak Aktif</option>
                                        </select>
                                    </div>

                                    {/* Hire Date */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Tanggal Bergabung
                                        </label>
                                        <input
                                            type="date"
                                            value={data.hire_date}
                                            onChange={(e) => setData('hire_date', e.target.value)}
                                            className="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Background Check (Optional) */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900 flex items-center">
                                    <DocumentTextIcon className="w-5 h-5 mr-2" />
                                    Background Check
                                    <span className="ml-2 text-xs text-gray-500">(Opsional)</span>
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Informasi pemeriksaan latar belakang karyawan
                                </p>
                            </div>
                            <div className="px-6 py-6 space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Tanggal Background Check
                                        </label>
                                        <input
                                            type="date"
                                            value={data.background_check_date}
                                            onChange={(e) => setData('background_check_date', e.target.value)}
                                            className="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Status Background Check
                                        </label>
                                        <select
                                            value={data.background_check_status}
                                            onChange={(e) => setData('background_check_status', e.target.value)}
                                            className="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                        >
                                            <option value="">Belum dilakukan</option>
                                            <option value="cleared">Cleared</option>
                                            <option value="pending">Pending</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Catatan Background Check
                                    </label>
                                    <textarea
                                        value={data.background_check_notes}
                                        onChange={(e) => setData('background_check_notes', e.target.value)}
                                        rows={3}
                                        className="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                        placeholder="Catatan tambahan mengenai background check..."
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Form Actions */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-6 py-4">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-gray-600">
                                        * Field wajib diisi
                                    </div>
                                    <div className="flex space-x-3">
                                        <Link
                                            href={route('employees.index')}
                                            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            Batal
                                        </Link>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                                        >
                                            {processing ? (
                                                <>
                                                    <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Menyimpan...
                                                </>
                                            ) : (
                                                <>
                                                    <UserPlusIcon className="w-4 h-4 mr-2" />
                                                    Simpan Karyawan
                                                </>
                                            )}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
