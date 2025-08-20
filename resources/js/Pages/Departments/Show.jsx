import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    PencilIcon,
    UsersIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    ChartBarIcon,
    ClipboardDocumentListIcon,
    CalendarIcon,
    UserPlusIcon,
    DocumentArrowDownIcon,
    EyeIcon,
    BuildingOfficeIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, department, trainingStats, trainingByCategory, employeesWithoutTraining, recentActivities, complianceRate }) {
    const [activeTab, setActiveTab] = useState('overview');

    const getStatusColor = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            expiring_soon: 'bg-yellow-100 text-yellow-800',
            expired: 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getStatusIcon = (status) => {
        const icons = {
            active: <CheckCircleIcon className="w-4 h-4" />,
            expiring_soon: <ExclamationTriangleIcon className="w-4 h-4" />,
            expired: <XCircleIcon className="w-4 h-4" />
        };
        return icons[status] || <XCircleIcon className="w-4 h-4" />;
    };

    const getCategoryColor = (category) => {
        const colors = {
            safety: 'bg-red-100 text-red-800',
            operational: 'bg-blue-100 text-blue-800',
            security: 'bg-purple-100 text-purple-800',
            technical: 'bg-green-100 text-green-800'
        };
        return colors[category] || 'bg-gray-100 text-gray-800';
    };

    const getComplianceColor = (rate) => {
        if (rate >= 90) return 'text-green-600';
        if (rate >= 80) return 'text-yellow-600';
        if (rate >= 70) return 'text-orange-600';
        return 'text-red-600';
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const addEmployee = () => {
        router.get(route('employees.create'), {
            department_id: department.id
        });
    };

    const exportDepartmentData = () => {
        router.get(route('departments.export'), {
            department_id: department.id
        });
    };

    const tabs = [
        { id: 'overview', name: 'Overview', icon: ChartBarIcon },
        { id: 'employees', name: 'Employees', icon: UsersIcon },
        { id: 'training', name: 'Training Analysis', icon: ClipboardDocumentListIcon },
        { id: 'activities', name: 'Recent Activities', icon: CalendarIcon }
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('departments.index')}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-2" />
                            Back to Departments
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {department.name}
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Department Code: {department.code} • {department.employees?.length || 0} employees
                            </p>
                        </div>
                    </div>
                    <div className="flex space-x-2">
                        <button
                            onClick={addEmployee}
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <UserPlusIcon className="w-4 h-4 mr-2" />
                            Add Employee
                        </button>
                        <button
                            onClick={exportDepartmentData}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <DocumentArrowDownIcon className="w-4 h-4 mr-2" />
                            Export Data
                        </button>
                        <Link
                            href={route('departments.edit', department.id)}
                            className="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Edit Department
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Department - ${department.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">

                        {/* Department Information Card */}
                        <div className="lg:col-span-1">
                            <div className="bg-white shadow rounded-lg p-6">
                                <div className="flex items-center space-x-4 mb-6">
                                    <div className="flex-shrink-0">
                                        <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                                            <BuildingOfficeIcon className="w-8 h-8 text-blue-600" />
                                        </div>
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900">
                                            {department.name}
                                        </h3>
                                        <p className="text-sm text-gray-500">
                                            Code: {department.code}
                                        </p>
                                    </div>
                                </div>

                                {department.description && (
                                    <div className="mb-6">
                                        <h4 className="text-sm font-medium text-gray-900 mb-2">Description</h4>
                                        <p className="text-sm text-gray-600">
                                            {department.description}
                                        </p>
                                    </div>
                                )}

                                {/* Department Statistics */}
                                <div className="space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="text-center">
                                            <div className="text-2xl font-bold text-blue-600">{department.employees?.length || 0}</div>
                                            <div className="text-xs text-gray-500">Employees</div>
                                        </div>
                                        <div className="text-center">
                                            <div className="text-2xl font-bold text-green-600">{trainingStats?.total_certificates || 0}</div>
                                            <div className="text-xs text-gray-500">Certificates</div>
                                        </div>
                                        <div className="text-center">
                                            <div className="text-2xl font-bold text-yellow-600">{trainingStats?.expiring_certificates || 0}</div>
                                            <div className="text-xs text-gray-500">Expiring</div>
                                        </div>
                                        <div className="text-center">
                                            <div className="text-2xl font-bold text-red-600">{trainingStats?.expired_certificates || 0}</div>
                                            <div className="text-xs text-gray-500">Expired</div>
                                        </div>
                                    </div>

                                    {/* Compliance Rate */}
                                    <div className="mt-4 pt-4 border-t border-gray-200">
                                        <div className="flex items-center justify-between mb-2">
                                            <span className="text-sm font-medium text-gray-900">Compliance Rate</span>
                                            <span className={`text-sm font-bold ${getComplianceColor(complianceRate)}`}>
                                                {complianceRate}%
                                            </span>
                                        </div>
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div
                                                className={`h-2 rounded-full transition-all duration-300 ${
                                                    complianceRate >= 90 ? 'bg-green-600' :
                                                    complianceRate >= 80 ? 'bg-yellow-600' :
                                                    complianceRate >= 70 ? 'bg-orange-600' : 'bg-red-600'
                                                }`}
                                                style={{ width: `${complianceRate}%` }}
                                            ></div>
                                        </div>
                                        <p className="text-xs text-gray-500 mt-1">
                                            {complianceRate >= 90 ? 'Excellent' :
                                             complianceRate >= 80 ? 'Good' :
                                             complianceRate >= 70 ? 'Fair' : 'Needs Attention'}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Actions */}
                            <div className="bg-white shadow rounded-lg p-6 mt-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                                <div className="space-y-2">
                                    <button
                                        onClick={addEmployee}
                                        className="w-full flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
                                    >
                                        <UserPlusIcon className="w-4 h-4 mr-2 text-green-600" />
                                        Add Employee to Dept
                                    </button>
                                    <Link
                                        href={route('training-records.create')}
                                        className="w-full flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
                                    >
                                        <ClipboardDocumentListIcon className="w-4 h-4 mr-2 text-blue-600" />
                                        Add Training Record
                                    </Link>
                                    <button
                                        onClick={exportDepartmentData}
                                        className="w-full flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md"
                                    >
                                        <DocumentArrowDownIcon className="w-4 h-4 mr-2 text-purple-600" />
                                        Export Department Data
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Main Content */}
                        <div className="lg:col-span-3">
                            <div className="bg-white shadow rounded-lg">
                                {/* Tabs */}
                                <div className="border-b border-gray-200">
                                    <nav className="-mb-px flex">
                                        {tabs.map((tab) => {
                                            const Icon = tab.icon;
                                            return (
                                                <button
                                                    key={tab.id}
                                                    onClick={() => setActiveTab(tab.id)}
                                                    className={`${
                                                        activeTab === tab.id
                                                            ? 'border-green-500 text-green-600'
                                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                                    } w-1/4 py-4 px-1 text-center border-b-2 font-medium text-sm flex items-center justify-center space-x-2`}
                                                >
                                                    <Icon className="w-5 h-5" />
                                                    <span>{tab.name}</span>
                                                </button>
                                            );
                                        })}
                                    </nav>
                                </div>

                                {/* Tab Content */}
                                <div className="p-6">
                                    {activeTab === 'overview' && (
                                        <div className="space-y-6">
                                            {/* Overall Statistics */}
                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                                <div className="bg-blue-50 rounded-lg p-4">
                                                    <div className="flex items-center">
                                                        <UsersIcon className="w-8 h-8 text-blue-600" />
                                                        <div className="ml-3">
                                                            <p className="text-sm font-medium text-blue-600">Total Employees</p>
                                                            <p className="text-2xl font-bold text-blue-900">{department.employees?.length || 0}</p>
                                                            <p className="text-xs text-blue-700">
                                                                {department.employees?.filter(e => e.status === 'active').length || 0} active
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="bg-green-50 rounded-lg p-4">
                                                    <div className="flex items-center">
                                                        <CheckCircleIcon className="w-8 h-8 text-green-600" />
                                                        <div className="ml-3">
                                                            <p className="text-sm font-medium text-green-600">Active Certificates</p>
                                                            <p className="text-2xl font-bold text-green-900">{trainingStats?.active_certificates || 0}</p>
                                                            <p className="text-xs text-green-700">
                                                                {trainingStats?.total_certificates || 0} total
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="bg-yellow-50 rounded-lg p-4">
                                                    <div className="flex items-center">
                                                        <ExclamationTriangleIcon className="w-8 h-8 text-yellow-600" />
                                                        <div className="ml-3">
                                                            <p className="text-sm font-medium text-yellow-600">Needs Attention</p>
                                                            <p className="text-2xl font-bold text-yellow-900">
                                                                {(trainingStats?.expiring_certificates || 0) + (trainingStats?.expired_certificates || 0)}
                                                            </p>
                                                            <p className="text-xs text-yellow-700">
                                                                {trainingStats?.expiring_certificates || 0} expiring, {trainingStats?.expired_certificates || 0} expired
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Training by Category */}
                                            {trainingByCategory && trainingByCategory.length > 0 && (
                                                <div>
                                                    <h4 className="text-lg font-medium text-gray-900 mb-4">Training by Category</h4>
                                                    <div className="space-y-3">
                                                        {trainingByCategory.map((category, index) => (
                                                            <div key={index} className="border border-gray-200 rounded-lg p-4">
                                                                <div className="flex items-center justify-between mb-2">
                                                                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getCategoryColor(category.category)}`}>
                                                                        {category.category.charAt(0).toUpperCase() + category.category.slice(1)}
                                                                    </span>
                                                                    <span className="text-sm text-gray-600">
                                                                        {category.total} certificates
                                                                    </span>
                                                                </div>
                                                                <div className="grid grid-cols-3 gap-4 text-sm">
                                                                    <div className="text-center">
                                                                        <div className="text-green-600 font-medium">{category.active}</div>
                                                                        <div className="text-xs text-gray-500">Active</div>
                                                                    </div>
                                                                    <div className="text-center">
                                                                        <div className="text-yellow-600 font-medium">{category.expiring}</div>
                                                                        <div className="text-xs text-gray-500">Expiring</div>
                                                                    </div>
                                                                    <div className="text-center">
                                                                        <div className="text-red-600 font-medium">{category.expired}</div>
                                                                        <div className="text-xs text-gray-500">Expired</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {activeTab === 'employees' && (
                                        <div className="space-y-4">
                                            <div className="flex items-center justify-between">
                                                <h3 className="text-lg font-medium text-gray-900">Department Employees</h3>
                                                <span className="text-sm text-gray-500">
                                                    {department.employees?.length || 0} total employees
                                                </span>
                                            </div>

                                            {department.employees && department.employees.length > 0 ? (
                                                <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                                    <table className="min-w-full divide-y divide-gray-300">
                                                        <thead className="bg-gray-50">
                                                            <tr>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Employee
                                                                </th>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Position
                                                                </th>
                                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    Training Records
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
                                                            {department.employees.map((employee) => (
                                                                <tr key={employee.id}>
                                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                                        <div>
                                                                            <div className="text-sm font-medium text-gray-900">
                                                                                {employee.name}
                                                                            </div>
                                                                            <div className="text-sm text-gray-500">
                                                                                ID: {employee.employee_id}
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                        {employee.position || 'No position'}
                                                                    </td>
                                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                        <div>
                                                                            <div>{employee.training_records?.length || 0} total</div>
                                                                            <div className="text-xs text-gray-500">
                                                                                {employee.training_records?.filter(r => r.status === 'active').length || 0} active
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                                            employee.status === 'active'
                                                                                ? 'bg-green-100 text-green-800'
                                                                                : 'bg-red-100 text-red-800'
                                                                        }`}>
                                                                            {employee.status.charAt(0).toUpperCase() + employee.status.slice(1)}
                                                                        </span>
                                                                    </td>
                                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                                        <Link
                                                                            href={route('employees.show', employee.id)}
                                                                            className="text-green-600 hover:text-green-900"
                                                                        >
                                                                            <EyeIcon className="w-4 h-4" />
                                                                        </Link>
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            ) : (
                                                <div className="text-center py-6">
                                                    <UsersIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No employees</h3>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        This department doesn't have any employees yet.
                                                    </p>
                                                    <div className="mt-6">
                                                        <button
                                                            onClick={addEmployee}
                                                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                                        >
                                                            <UserPlusIcon className="-ml-1 mr-2 h-5 w-5" />
                                                            Add First Employee
                                                        </button>
                                                    </div>
                                                </div>
                                            )}

                                            {/* Employees without training */}
                                            {employeesWithoutTraining && employeesWithoutTraining.length > 0 && (
                                                <div className="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                                    <h4 className="text-sm font-medium text-yellow-800 mb-2">
                                                        Employees Without Training ({employeesWithoutTraining.length})
                                                    </h4>
                                                    <div className="space-y-1">
                                                        {employeesWithoutTraining.slice(0, 5).map((employee, index) => (
                                                            <div key={index} className="flex items-center justify-between text-sm">
                                                                <span className="text-yellow-700">
                                                                    {employee.name} ({employee.employee_id})
                                                                </span>
                                                                <Link
                                                                    href={route('training-records.create', { employee_id: employee.id })}
                                                                    className="text-yellow-600 hover:text-yellow-900 text-xs"
                                                                >
                                                                    Add Training
                                                                </Link>
                                                            </div>
                                                        ))}
                                                        {employeesWithoutTraining.length > 5 && (
                                                            <p className="text-xs text-yellow-600">
                                                                +{employeesWithoutTraining.length - 5} more employees need training
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {activeTab === 'training' && (
                                        <div className="space-y-6">
                                            <h3 className="text-lg font-medium text-gray-900">Training Analysis</h3>

                                            {/* Training Category Breakdown */}
                                            {trainingByCategory && trainingByCategory.length > 0 ? (
                                                <div className="space-y-4">
                                                    {trainingByCategory.map((category, index) => (
                                                        <div key={index} className="border border-gray-200 rounded-lg p-6">
                                                            <div className="flex items-center justify-between mb-4">
                                                                <div className="flex items-center">
                                                                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getCategoryColor(category.category)}`}>
                                                                        {category.category.charAt(0).toUpperCase() + category.category.slice(1)}
                                                                    </span>
                                                                    <span className="ml-3 text-lg font-semibold text-gray-900">
                                                                        {category.total} certificates
                                                                    </span>
                                                                </div>
                                                                <div className="text-sm text-gray-500">
                                                                    {Math.round((category.active / category.total) * 100)}% compliance
                                                                </div>
                                                            </div>

                                                            <div className="grid grid-cols-4 gap-4">
                                                                <div className="text-center">
                                                                    <div className="text-2xl font-bold text-green-600">{category.active}</div>
                                                                    <div className="text-xs text-gray-500">Active</div>
                                                                </div>
                                                                <div className="text-center">
                                                                    <div className="text-2xl font-bold text-yellow-600">{category.expiring}</div>
                                                                    <div className="text-xs text-gray-500">Expiring Soon</div>
                                                                </div>
                                                                <div className="text-center">
                                                                    <div className="text-2xl font-bold text-red-600">{category.expired}</div>
                                                                    <div className="text-xs text-gray-500">Expired</div>
                                                                </div>
                                                                <div className="text-center">
                                                                    <div className="text-2xl font-bold text-gray-600">{category.total}</div>
                                                                    <div className="text-xs text-gray-500">Total</div>
                                                                </div>
                                                            </div>

                                                            {/* Progress Bar */}
                                                            <div className="mt-4">
                                                                <div className="w-full bg-gray-200 rounded-full h-2">
                                                                    <div
                                                                        className="bg-green-600 h-2 rounded-full"
                                                                        style={{ width: `${Math.round((category.active / category.total) * 100)}%` }}
                                                                    ></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <div className="text-center py-6">
                                                    <ClipboardDocumentListIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No training data</h3>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        No training records found for this department.
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {activeTab === 'activities' && (
                                        <div className="space-y-4">
                                            <h3 className="text-lg font-medium text-gray-900">Recent Training Activities</h3>

                                            {recentActivities && recentActivities.length > 0 ? (
                                                <div className="space-y-3">
                                                    {recentActivities.map((activity, index) => (
                                                        <div key={index} className="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg">
                                                            <div className={`w-2 h-2 rounded-full ${
                                                                activity.status === 'active' ? 'bg-green-500' :
                                                                activity.status === 'expiring_soon' ? 'bg-yellow-500' :
                                                                'bg-red-500'
                                                            }`}></div>
                                                            <div className="flex-1">
                                                                <p className="text-sm font-medium text-gray-900">{activity.employee_name}</p>
                                                                <p className="text-xs text-gray-500">{activity.training_name}</p>
                                                                <p className="text-xs text-gray-400">
                                                                    {formatDate(activity.created_at)} • {activity.certificate_number}
                                                                </p>
                                                            </div>
                                                            <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(activity.status)}`}>
                                                                {getStatusIcon(activity.status)}
                                                                <span className="ml-1">{activity.status.replace('_', ' ')}</span>
                                                            </span>
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <div className="text-center py-6">
                                                    <CalendarIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                    <h3 className="mt-2 text-sm font-medium text-gray-900">No recent activities</h3>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        No training activities recorded for this department.
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
