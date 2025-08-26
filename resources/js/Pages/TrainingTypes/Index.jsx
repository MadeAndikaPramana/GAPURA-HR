import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer,
    PieChart, Pie, Cell
} from 'recharts';
import {
    Search, Plus, Filter, TrendingUp, AlertTriangle, CheckCircle,
    Clock, Users, Award, DollarSign, Target, BookOpen, Settings,
    FileText, Download, RefreshCw
} from 'lucide-react';

const TrainingTypesIndex = ({
    auth,
    trainingTypes = [],
    analytics = [],
    complianceOverview = {},
    monthlyTrends = [],
    costAnalytics = {},
    filters = {},
    filterOptions = {}
}) => {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category || '');
    const [selectedTab, setSelectedTab] = useState('overview');
    const [showFilters, setShowFilters] = useState(false);

    // Colors for charts
    const COLORS = ['#10B981', '#F59E0B', '#EF4444', '#6B7280'];
    const RISK_COLORS = {
        low: '#10B981',
        medium: '#F59E0B',
        high: '#EF4444',
        critical: '#DC2626'
    };

    // Mock data for demonstration - replace with real props
    const mockComplianceOverview = {
        total_employees: 24,
        mandatory_training_types: 15,
        overall_compliance_rate: 31.5,
        expiring_certificates: 0,
        expired_certificates: 52,
        total_risk_alerts: 52,
        compliance_grade: 'F'
    };

    const mockAnalytics = [
        {
            id: 1,
            name: 'Fire Safety Training',
            category: 'Safety',
            is_mandatory: true,
            total_certificates: 24,
            active_certificates: 8,
            expiring_certificates: 0,
            expired_certificates: 16,
            compliance_rate: 33.3,
            risk_level: 'critical',
            priority_score: 85
        },
        {
            id: 2,
            name: 'First Aid Training',
            category: 'Safety',
            is_mandatory: true,
            total_certificates: 18,
            active_certificates: 3,
            expiring_certificates: 0,
            expired_certificates: 15,
            compliance_rate: 16.7,
            risk_level: 'critical',
            priority_score: 95
        },
        // Add more mock data as needed
    ];

    const currentOverview = Object.keys(complianceOverview).length > 0 ? complianceOverview : mockComplianceOverview;
    const currentAnalytics = analytics.length > 0 ? analytics : mockAnalytics;

    const handleSearch = () => {
        console.log('Searching for:', searchTerm);
    };

    const handleAddTrainingType = () => {
        router.get(route('training-types.create'));
    };

    const handleExportReport = () => {
        // Implement export functionality
        console.log('Exporting report...');
    };

    const getRiskBadgeColor = (riskLevel) => {
        const colors = {
            low: 'bg-green-100 text-green-800',
            medium: 'bg-yellow-100 text-yellow-800',
            high: 'bg-red-100 text-red-800',
            critical: 'bg-red-200 text-red-900'
        };
        return colors[riskLevel] || colors.medium;
    };

    const getComplianceColor = (rate) => {
        if (rate >= 90) return 'text-green-600';
        if (rate >= 75) return 'text-yellow-600';
        if (rate >= 60) return 'text-orange-600';
        return 'text-red-600';
    };

    // Prepare chart data
    const complianceChartData = [
        { name: 'Fire Safety Training', compliance: 33.3 },
        { name: 'First Aid Training', compliance: 16.7 },
        { name: 'OHS Training', compliance: 33.3 },
        { name: 'DG Awareness', compliance: 12.5 },
        { name: 'Ground Handling', compliance: 41.7 },
        { name: 'Equipment Operation', compliance: 8.3 },
        { name: 'Aviation Security', compliance: 75.0 },
        { name: 'Maintenance Procedures', compliance: 50.0 }
    ];

    const riskPieData = [
        { name: 'Critical', value: 6, color: RISK_COLORS.critical },
        { name: 'High', value: 0, color: RISK_COLORS.high },
        { name: 'Medium', value: 1, color: RISK_COLORS.medium },
        { name: 'Low', value: 5, color: RISK_COLORS.low }
    ];

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Training Type Management" />

            <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <div className="bg-white shadow-sm border-b">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center py-6">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Training Type Management</h1>
                                <p className="mt-1 text-sm text-gray-500">
                                    Manage training types, monitor compliance, and analyze performance
                                </p>
                            </div>
                            <div className="flex space-x-3">
                                <button
                                    onClick={handleExportReport}
                                    className="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50 flex items-center space-x-2"
                                >
                                    <FileText className="h-4 w-4" />
                                    <span>Export Report</span>
                                </button>
                                <button
                                    onClick={handleAddTrainingType}
                                    className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center space-x-2"
                                >
                                    <Plus className="h-4 w-4" />
                                    <span>Add Training Type</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Key Metrics Dashboard */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Overview Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <Users className="h-8 w-8 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Total Employees</p>
                                    <p className="text-2xl font-semibold text-gray-900">
                                        {currentOverview.total_employees?.toLocaleString()}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <Target className="h-8 w-8 text-green-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Overall Compliance</p>
                                    <p className={`text-2xl font-semibold ${getComplianceColor(currentOverview.overall_compliance_rate)}`}>
                                        {currentOverview.overall_compliance_rate}%
                                    </p>
                                    <p className="text-sm text-gray-500">Grade: {currentOverview.compliance_grade}</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <Clock className="h-8 w-8 text-yellow-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Expiring Soon</p>
                                    <p className="text-2xl font-semibold text-yellow-600">
                                        {currentOverview.expiring_certificates}
                                    </p>
                                    <p className="text-sm text-gray-500">certificates</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <AlertTriangle className="h-8 w-8 text-red-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-500">Total Risk Alerts</p>
                                    <p className="text-2xl font-semibold text-red-600">
                                        {currentOverview.total_risk_alerts}
                                    </p>
                                    <p className="text-sm text-gray-500">
                                        {currentOverview.expired_certificates} expired
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Tab Navigation */}
                    <div className="mb-6">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8">
                                {[
                                    { id: 'overview', label: 'Overview', icon: TrendingUp },
                                    { id: 'compliance', label: 'Compliance Analysis', icon: Target },
                                    { id: 'management', label: 'Training Types', icon: BookOpen }
                                ].map((tab) => {
                                    const Icon = tab.icon;
                                    return (
                                        <button
                                            key={tab.id}
                                            onClick={() => setSelectedTab(tab.id)}
                                            className={`flex items-center space-x-2 py-3 px-1 border-b-2 font-medium text-sm ${
                                                selectedTab === tab.id
                                                    ? 'border-blue-500 text-blue-600'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            }`}
                                        >
                                            <Icon className="h-4 w-4" />
                                            <span>{tab.label}</span>
                                        </button>
                                    );
                                })}
                            </nav>
                        </div>
                    </div>

                    {/* Tab Content */}
                    {selectedTab === 'overview' && (
                        <div className="space-y-6">
                            {/* Charts Row */}
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* Compliance Chart */}
                                <div className="bg-white rounded-lg shadow p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                                        Training Compliance by Type
                                    </h3>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <BarChart data={complianceChartData}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis
                                                dataKey="name"
                                                angle={-45}
                                                textAnchor="end"
                                                height={80}
                                            />
                                            <YAxis />
                                            <Tooltip />
                                            <Bar dataKey="compliance" fill="#10B981" name="Compliance %" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>

                                {/* Risk Distribution */}
                                <div className="bg-white rounded-lg shadow p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                                        Risk Level Distribution
                                    </h3>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <PieChart>
                                            <Pie
                                                data={riskPieData}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                label={({ name, value }) => `${name}: ${value}`}
                                                outerRadius={80}
                                                fill="#8884d8"
                                                dataKey="value"
                                            >
                                                {riskPieData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                                ))}
                                            </Pie>
                                            <Tooltip />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>
                            </div>

                            {/* High Priority Training Types */}
                            <div className="bg-white rounded-lg shadow">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900">
                                        High Priority Training Types
                                    </h3>
                                    <p className="text-sm text-gray-500">
                                        Training types requiring immediate attention
                                    </p>
                                </div>
                                <div className="divide-y divide-gray-200">
                                    {currentAnalytics
                                        .filter(item => item.risk_level === 'high' || item.risk_level === 'critical')
                                        .slice(0, 5)
                                        .map((item) => (
                                        <div key={item.id} className="px-6 py-4 flex items-center justify-between">
                                            <div className="flex items-center space-x-4">
                                                <div className="flex-shrink-0">
                                                    <BookOpen className="h-8 w-8 text-gray-400" />
                                                </div>
                                                <div>
                                                    <h4 className="text-sm font-medium text-gray-900">{item.name}</h4>
                                                    <p className="text-sm text-gray-500">{item.category}</p>
                                                </div>
                                            </div>
                                            <div className="flex items-center space-x-4">
                                                <div className="text-right">
                                                    <p className={`text-sm font-medium ${getComplianceColor(item.compliance_rate)}`}>
                                                        {item.compliance_rate}% compliant
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        {item.expired_certificates} expired, {item.expiring_certificates} expiring
                                                    </p>
                                                </div>
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRiskBadgeColor(item.risk_level)}`}>
                                                    {item.risk_level}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {selectedTab === 'compliance' && (
                        <div className="bg-white rounded-lg shadow">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Detailed Compliance Analysis
                                </h3>
                            </div>
                            <div className="p-6">
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Training Type
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Category
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Compliance Rate
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Active
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Expiring
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Expired
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Risk Level
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {currentAnalytics.map((item) => (
                                                <tr key={item.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <BookOpen className="h-5 w-5 text-gray-400 mr-3" />
                                                            <div>
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {item.name}
                                                                </div>
                                                                {item.is_mandatory && (
                                                                    <div className="text-xs text-red-600">Mandatory</div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {item.category}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className={`text-sm font-medium ${getComplianceColor(item.compliance_rate)}`}>
                                                            {item.compliance_rate}%
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {item.active_certificates}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">
                                                        {item.expiring_certificates}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                                        {item.expired_certificates}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRiskBadgeColor(item.risk_level)}`}>
                                                            {item.risk_level}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    )}

                    {selectedTab === 'management' && (
                        <div className="space-y-6">
                            {/* Search and Filters */}
                            <div className="bg-white rounded-lg shadow p-6">
                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                                    <div className="flex-1 max-w-lg">
                                        <div className="relative">
                                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                            <input
                                                type="text"
                                                placeholder="Search training types..."
                                                value={searchTerm}
                                                onChange={(e) => setSearchTerm(e.target.value)}
                                                onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            />
                                        </div>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <button
                                            onClick={() => setShowFilters(!showFilters)}
                                            className="flex items-center space-x-2 px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                                        >
                                            <Filter className="h-4 w-4" />
                                            <span>Filters</span>
                                        </button>
                                        <select
                                            value={selectedCategory}
                                            onChange={(e) => setSelectedCategory(e.target.value)}
                                            className="border border-gray-300 rounded-md px-3 py-2 text-gray-700 focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option value="">All Categories</option>
                                            <option value="Safety">Safety</option>
                                            <option value="Security">Security</option>
                                            <option value="Compliance">Compliance</option>
                                            <option value="Operations">Operations</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {/* Training Types Management Table */}
                            <div className="bg-white rounded-lg shadow overflow-hidden">
                                <div className="px-6 py-4 border-b border-gray-200">
                                    <h3 className="text-lg font-medium text-gray-900">
                                        Training Types
                                    </h3>
                                </div>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Training Type
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Statistics
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Compliance
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {currentAnalytics.map((item) => (
                                                <tr key={item.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4">
                                                        <div className="flex items-center">
                                                            <BookOpen className="h-5 w-5 text-gray-400 mr-3" />
                                                            <div>
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {item.name}
                                                                </div>
                                                                <div className="text-sm text-gray-500">
                                                                    {item.category}
                                                                    {item.is_mandatory && (
                                                                        <span className="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                            Mandatory
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <div>Total: {item.total_certificates}</div>
                                                        <div className="text-xs">
                                                            Active: {item.active_certificates} |
                                                            Expiring: {item.expiring_certificates} |
                                                            Expired: {item.expired_certificates}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className={`text-sm font-medium ${getComplianceColor(item.compliance_rate)}`}>
                                                            {item.compliance_rate}%
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRiskBadgeColor(item.risk_level)}`}>
                                                            {item.risk_level}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex items-center justify-end space-x-2">
                                                            <button
                                                                onClick={() => router.get(route('training-types.show', item.id))}
                                                                className="text-blue-600 hover:text-blue-900"
                                                            >
                                                                View
                                                            </button>
                                                            <button
                                                                onClick={() => router.get(route('training-types.edit', item.id))}
                                                                className="text-indigo-600 hover:text-indigo-900"
                                                            >
                                                                Edit
                                                            </button>
                                                            <button className="text-gray-600 hover:text-gray-900">
                                                                <Settings className="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default TrainingTypesIndex;
