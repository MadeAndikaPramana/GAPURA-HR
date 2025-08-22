// Fix untuk frontend - TrainingRecords/Index.jsx

export default function Index({ auth, trainingRecords, employees, trainingTypes, departments, filters, stats }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [selectedEmployee, setSelectedEmployee] = useState(filters.employee || ''); // FIX: employee bukan employee_id
    const [selectedTrainingType, setSelectedTrainingType] = useState(filters.training_type || ''); // FIX: training_type bukan training_type_id
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || ''); // FIX: department bukan department_id

    const handleSearch = () => {
        router.get(route('training-records.index'), {
            search: searchTerm,
            status: selectedStatus,
            employee: selectedEmployee,        // FIX: gunakan employee
            training_type: selectedTrainingType, // FIX: gunakan training_type
            department: selectedDepartment,    // FIX: gunakan department
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedStatus('');
        setSelectedEmployee('');
        setSelectedTrainingType('');
        setSelectedDepartment('');
        router.get(route('training-records.index'));
    };

    const exportRecords = () => {
        const params = new URLSearchParams({
            search: searchTerm,
            status: selectedStatus,
            employee: selectedEmployee,        // FIX: gunakan employee
            training_type: selectedTrainingType, // FIX: gunakan training_type
            department: selectedDepartment,    // FIX: gunakan department
        });

        window.location.href = route('import-export.training-records.export') + '?' + params.toString();
    };

    // Handle auto-search on filter change
    const handleFilterChange = (filterType, value) => {
        const newFilters = {
            search: searchTerm,
            status: selectedStatus,
            employee: selectedEmployee,
            training_type: selectedTrainingType,
            department: selectedDepartment,
        };

        newFilters[filterType] = value;

        router.get(route('training-records.index'), newFilters, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Training Records" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div className="bg-blue-50 rounded-lg p-6">
                            <div className="flex items-center">
                                <UserIcon className="w-8 h-8 text-blue-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-blue-600">Total Employees</p>
                                    <p className="text-2xl font-bold text-blue-900">{stats.total_employees}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-green-50 rounded-lg p-6">
                            <div className="flex items-center">
                                <CheckCircleIcon className="w-8 h-8 text-green-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-green-600">Active Certificates</p>
                                    <p className="text-2xl font-bold text-green-900">{stats.active_certificates}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-yellow-50 rounded-lg p-6">
                            <div className="flex items-center">
                                <ExclamationTriangleIcon className="w-8 h-8 text-yellow-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-yellow-600">Expiring Soon</p>
                                    <p className="text-2xl font-bold text-yellow-900">{stats.expiring_certificates}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-red-50 rounded-lg p-6">
                            <div className="flex items-center">
                                <XCircleIcon className="w-8 h-8 text-red-600" />
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-red-600">Expired</p>
                                    <p className="text-2xl font-bold text-red-900">{stats.expired_certificates}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow p-6 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
                            {/* Search */}
                            <div className="md:col-span-2">
                                <div className="relative">
                                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <input
                                        type="text"
                                        placeholder="Search records..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="pl-10 w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                        onKeyPress={(e) => {
                                            if (e.key === 'Enter') {
                                                handleSearch();
                                            }
                                        }}
                                    />
                                </div>
                            </div>

                            {/* Status Filter */}
                            <div>
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => {
                                        setSelectedStatus(e.target.value);
                                        handleFilterChange('status', e.target.value);
                                    }}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="expiring_soon">Expiring Soon</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>

                            {/* Employee Filter */}
                            <div>
                                <select
                                    value={selectedEmployee}
                                    onChange={(e) => {
                                        setSelectedEmployee(e.target.value);
                                        handleFilterChange('employee', e.target.value);
                                    }}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Employees</option>
                                    {employees.map((employee) => (
                                        <option key={employee.id} value={employee.id}>
                                            {employee.employee_id} - {employee.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Training Type Filter */}
                            <div>
                                <select
                                    value={selectedTrainingType}
                                    onChange={(e) => {
                                        setSelectedTrainingType(e.target.value);
                                        handleFilterChange('training_type', e.target.value);
                                    }}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Training Types</option>
                                    {trainingTypes.map((type) => (
                                        <option key={type.id} value={type.id}>
                                            {type.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Department Filter */}
                            <div>
                                <select
                                    value={selectedDepartment}
                                    onChange={(e) => {
                                        setSelectedDepartment(e.target.value);
                                        handleFilterChange('department', e.target.value);
                                    }}
                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="">All Departments</option>
                                    {departments && departments.map((dept) => (
                                        <option key={dept.id} value={dept.id}>
                                            {dept.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex justify-between">
                            <div className="flex space-x-3">
                                <button
                                    onClick={handleSearch}
                                    className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    <MagnifyingGlassIcon className="w-4 h-4 mr-2" />
                                    Search
                                </button>

                                <button
                                    onClick={clearFilters}
                                    className="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    <FunnelIcon className="w-4 h-4 mr-2" />
                                    Clear Filters
                                </button>
                            </div>

                            <div className="flex space-x-3">
                                <button
                                    onClick={exportRecords}
                                    className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    <ArrowDownTrayIcon className="w-4 h-4 mr-2" />
                                    Export
                                </button>

                                <Link
                                    href={route('training-records.create')}
                                    className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    <PlusIcon className="w-4 h-4 mr-2" />
                                    Add Training
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Training Records Table */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employee
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Training Type
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Certificate
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Valid Until
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {trainingRecords.data && trainingRecords.data.length > 0 ? (
                                    trainingRecords.data.map((record) => (
                                        <tr key={record.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div className="flex-shrink-0 h-10 w-10">
                                                        <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                            <span className="text-sm font-medium text-green-800">
                                                                {record.employee?.name?.charAt(0)}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="ml-4">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {record.employee?.name}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {record.employee?.employee_id}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {record.training_type?.name}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {record.certificate_number}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {new Date(record.expiry_date).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                    record.status === 'active'
                                                        ? 'bg-green-100 text-green-800'
                                                        : record.status === 'expiring_soon'
                                                        ? 'bg-yellow-100 text-yellow-800'
                                                        : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {record.status.replace('_', ' ')}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div className="flex space-x-3">
                                                    <Link
                                                        href={route('training-records.show', record.id)}
                                                        className="text-green-600 hover:text-green-900"
                                                    >
                                                        <EyeIcon className="w-4 h-4" />
                                                    </Link>
                                                    <Link
                                                        href={route('training-records.edit', record.id)}
                                                        className="text-blue-600 hover:text-blue-900"
                                                    >
                                                        <PencilIcon className="w-4 h-4" />
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-4 text-center text-gray-500">
                                            No training records found
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {trainingRecords.links && (
                            <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Showing <span className="font-medium">{trainingRecords.from}</span> to{' '}
                                            <span className="font-medium">{trainingRecords.to}</span> of{' '}
                                            <span className="font-medium">{trainingRecords.total}</span> results
                                        </p>
                                    </div>
                                    <div>
                                        <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            {trainingRecords.links.map((link, index) => (
                                                <Link
                                                    key={index}
                                                    href={link.url || '#'}
                                                    className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                        link.active
                                                            ? 'z-10 bg-green-50 border-green-500 text-green-600'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    } ${index === 0 ? 'rounded-l-md' : ''} ${
                                                        index === trainingRecords.links.length - 1 ? 'rounded-r-md' : ''
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ))}
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
