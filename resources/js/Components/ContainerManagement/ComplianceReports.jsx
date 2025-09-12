// components/ContainerManagement/ComplianceReports.jsx
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import PropTypes from 'prop-types';
import {
    DocumentArrowDownIcon,
    DocumentChartBarIcon,
    CalendarIcon,
    BuildingOfficeIcon,
    UsersIcon,
    AcademicCapIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    ClockIcon,
    FunnelIcon,
    ArrowPathIcon,
    EyeIcon,
    Cog6ToothIcon
} from '@heroicons/react/24/outline';

const ReportTemplateCard = ({ template, onGenerate, onPreview, onSchedule }) => {
    const iconMap = {
        'compliance_summary': DocumentChartBarIcon,
        'employee_certificates': UsersIcon,
        'department_compliance': BuildingOfficeIcon,
        'expiry_forecast': CalendarIcon,
        'certificate_inventory': AcademicCapIcon,
        'audit_trail': ClockIcon
    };

    const Icon = iconMap[template.key] || DocumentChartBarIcon;

    const getStatusColor = (lastRun) => {
        if (!lastRun) return 'text-gray-400';
        
        const daysSince = (new Date() - new Date(lastRun)) / (1000 * 60 * 60 * 24);
        if (daysSince <= 1) return 'text-green-600';
        if (daysSince <= 7) return 'text-yellow-600';
        return 'text-red-600';
    };

    return (
        <div className="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-sm transition-shadow">
            <div className="flex items-start justify-between mb-4">
                <div className="flex items-center">
                    <div className="p-2 bg-blue-50 rounded-lg mr-3">
                        <Icon className="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <h3 className="text-lg font-medium text-gray-900">{template.name}</h3>
                        <p className="text-sm text-gray-600 mt-1">{template.description}</p>
                    </div>
                </div>
                
                {template.frequency && (
                    <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        {template.frequency}
                    </span>
                )}
            </div>

            <div className="space-y-3">
                <div className="flex items-center justify-between text-sm">
                    <span className="text-gray-600">Last generated:</span>
                    <span className={getStatusColor(template.last_run)}>
                        {template.last_run 
                            ? new Date(template.last_run).toLocaleDateString()
                            : 'Never'
                        }
                    </span>
                </div>

                <div className="flex items-center justify-between text-sm">
                    <span className="text-gray-600">Format:</span>
                    <span className="text-gray-900">{template.formats.join(', ')}</span>
                </div>

                <div className="flex items-center justify-between text-sm">
                    <span className="text-gray-600">Estimated time:</span>
                    <span className="text-gray-900">{template.estimated_time}</span>
                </div>
            </div>

            <div className="flex items-center space-x-2 mt-4 pt-4 border-t border-gray-200">
                <button
                    onClick={() => onPreview(template)}
                    className="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <EyeIcon className="w-4 h-4 mr-1" />
                    Preview
                </button>
                
                <button
                    onClick={() => onGenerate(template)}
                    className="flex-1 inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <DocumentArrowDownIcon className="w-4 h-4 mr-1" />
                    Generate
                </button>
                
                {template.schedulable && (
                    <button
                        onClick={() => onSchedule(template)}
                        className="p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-md"
                        title="Schedule report"
                    >
                        <Cog6ToothIcon className="w-4 h-4" />
                    </button>
                )}
            </div>
        </div>
    );
};

const ReportGenerationModal = ({ template, onGenerate, onCancel }) => {
    const [filters, setFilters] = useState({
        date_range: 'last_30_days',
        start_date: '',
        end_date: '',
        departments: [],
        certificate_types: [],
        status_filter: 'all',
        include_historical: false,
        format: 'xlsx'
    });
    const [availableFilters, setAvailableFilters] = useState({
        departments: [],
        certificate_types: []
    });
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        loadFilterOptions();
    }, []);

    const loadFilterOptions = async () => {
        try {
            const response = await fetch('/api/reports/filter-options');
            const data = await response.json();
            setAvailableFilters(data);
        } catch (error) {
            console.error('Failed to load filter options:', error);
        }
    };

    const handleFilterChange = (key, value) => {
        setFilters(prev => ({ ...prev, [key]: value }));
    };

    const handleArrayFilterChange = (key, value, checked) => {
        setFilters(prev => ({
            ...prev,
            [key]: checked 
                ? [...prev[key], value]
                : prev[key].filter(item => item !== value)
        }));
    };

    const handleGenerate = async () => {
        setLoading(true);
        try {
            await onGenerate(template, filters);
        } finally {
            setLoading(false);
        }
    };

    const dateRangeOptions = [
        { value: 'last_7_days', label: 'Last 7 days' },
        { value: 'last_30_days', label: 'Last 30 days' },
        { value: 'last_90_days', label: 'Last 90 days' },
        { value: 'current_month', label: 'Current month' },
        { value: 'current_quarter', label: 'Current quarter' },
        { value: 'current_year', label: 'Current year' },
        { value: 'custom', label: 'Custom range' }
    ];

    const statusOptions = [
        { value: 'all', label: 'All certificates' },
        { value: 'active', label: 'Active only' },
        { value: 'expired', label: 'Expired only' },
        { value: 'expiring_soon', label: 'Expiring soon' },
        { value: 'compliant', label: 'Compliant employees' },
        { value: 'non_compliant', label: 'Non-compliant employees' }
    ];

    const formatOptions = [
        { value: 'xlsx', label: 'Excel (.xlsx)' },
        { value: 'csv', label: 'CSV (.csv)' },
        { value: 'pdf', label: 'PDF (.pdf)' }
    ];

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div className="flex items-center justify-between p-6 border-b border-gray-200">
                    <div>
                        <h3 className="text-lg font-medium text-gray-900">Generate Report</h3>
                        <p className="text-sm text-gray-600 mt-1">{template.name}</p>
                    </div>
                    <button
                        onClick={onCancel}
                        className="text-gray-400 hover:text-gray-600"
                        disabled={loading}
                    >
                        <XCircleIcon className="w-6 h-6" />
                    </button>
                </div>

                <div className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Date Range */}
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Date Range
                            </label>
                            <select
                                value={filters.date_range}
                                onChange={(e) => handleFilterChange('date_range', e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                {dateRangeOptions.map(option => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            
                            {filters.date_range === 'custom' && (
                                <div className="grid grid-cols-2 gap-3 mt-3">
                                    <div>
                                        <label className="block text-xs text-gray-600 mb-1">Start Date</label>
                                        <input
                                            type="date"
                                            value={filters.start_date}
                                            onChange={(e) => handleFilterChange('start_date', e.target.value)}
                                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs text-gray-600 mb-1">End Date</label>
                                        <input
                                            type="date"
                                            value={filters.end_date}
                                            onChange={(e) => handleFilterChange('end_date', e.target.value)}
                                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        />
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Departments */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Departments
                            </label>
                            <div className="border border-gray-300 rounded-md p-3 max-h-32 overflow-y-auto">
                                <label className="flex items-center mb-2">
                                    <input
                                        type="checkbox"
                                        checked={filters.departments.length === 0}
                                        onChange={(e) => setFilters(prev => ({ ...prev, departments: e.target.checked ? [] : prev.departments }))}
                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <span className="ml-2 text-sm text-gray-700 font-medium">All Departments</span>
                                </label>
                                {availableFilters.departments.map(dept => (
                                    <label key={dept.id} className="flex items-center mb-1">
                                        <input
                                            type="checkbox"
                                            checked={filters.departments.includes(dept.id)}
                                            onChange={(e) => handleArrayFilterChange('departments', dept.id, e.target.checked)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">{dept.name}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Certificate Types */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Certificate Types
                            </label>
                            <div className="border border-gray-300 rounded-md p-3 max-h-32 overflow-y-auto">
                                <label className="flex items-center mb-2">
                                    <input
                                        type="checkbox"
                                        checked={filters.certificate_types.length === 0}
                                        onChange={(e) => setFilters(prev => ({ ...prev, certificate_types: e.target.checked ? [] : prev.certificate_types }))}
                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <span className="ml-2 text-sm text-gray-700 font-medium">All Types</span>
                                </label>
                                {availableFilters.certificate_types.map(type => (
                                    <label key={type.id} className="flex items-center mb-1">
                                        <input
                                            type="checkbox"
                                            checked={filters.certificate_types.includes(type.id)}
                                            onChange={(e) => handleArrayFilterChange('certificate_types', type.id, e.target.checked)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">{type.name}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Status Filter */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Status Filter
                            </label>
                            <select
                                value={filters.status_filter}
                                onChange={(e) => handleFilterChange('status_filter', e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                {statusOptions.map(option => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Format */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Export Format
                            </label>
                            <select
                                value={filters.format}
                                onChange={(e) => handleFilterChange('format', e.target.value)}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                {formatOptions.map(option => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Additional Options */}
                        <div className="md:col-span-2">
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={filters.include_historical}
                                    onChange={(e) => handleFilterChange('include_historical', e.target.checked)}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <span className="ml-2 text-sm text-gray-700">Include historical data (previous versions)</span>
                            </label>
                        </div>
                    </div>

                    {/* Preview Summary */}
                    <div className="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 className="text-sm font-medium text-gray-900 mb-2">Report Summary</h4>
                        <div className="grid grid-cols-2 gap-4 text-sm text-gray-600">
                            <div>
                                <span className="font-medium">Date Range:</span>{' '}
                                {filters.date_range === 'custom' 
                                    ? `${filters.start_date || 'Not set'} to ${filters.end_date || 'Not set'}`
                                    : dateRangeOptions.find(opt => opt.value === filters.date_range)?.label
                                }
                            </div>
                            <div>
                                <span className="font-medium">Departments:</span>{' '}
                                {filters.departments.length === 0 ? 'All' : filters.departments.length}
                            </div>
                            <div>
                                <span className="font-medium">Certificate Types:</span>{' '}
                                {filters.certificate_types.length === 0 ? 'All' : filters.certificate_types.length}
                            </div>
                            <div>
                                <span className="font-medium">Format:</span>{' '}
                                {formatOptions.find(opt => opt.value === filters.format)?.label}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-200">
                    <button
                        onClick={onCancel}
                        disabled={loading}
                        className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleGenerate}
                        disabled={loading || (filters.date_range === 'custom' && (!filters.start_date || !filters.end_date))}
                        className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {loading ? (
                            <>
                                <ArrowPathIcon className="w-4 h-4 mr-2 animate-spin" />
                                Generating...
                            </>
                        ) : (
                            <>
                                <DocumentArrowDownIcon className="w-4 h-4 mr-2" />
                                Generate Report
                            </>
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
};

const ReportScheduleModal = ({ template, onSchedule, onCancel }) => {
    const [schedule, setSchedule] = useState({
        frequency: 'weekly',
        day_of_week: '1',
        day_of_month: '1',
        hour: '09',
        minute: '00',
        recipients: '',
        enabled: true,
        filters: {}
    });

    const frequencyOptions = [
        { value: 'daily', label: 'Daily' },
        { value: 'weekly', label: 'Weekly' },
        { value: 'monthly', label: 'Monthly' },
        { value: 'quarterly', label: 'Quarterly' }
    ];

    const dayOptions = [
        { value: '1', label: 'Monday' },
        { value: '2', label: 'Tuesday' },
        { value: '3', label: 'Wednesday' },
        { value: '4', label: 'Thursday' },
        { value: '5', label: 'Friday' },
        { value: '6', label: 'Saturday' },
        { value: '0', label: 'Sunday' }
    ];

    const handleSubmit = () => {
        onSchedule(template, schedule);
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div className="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 className="text-lg font-medium text-gray-900">Schedule Report</h3>
                    <button
                        onClick={onCancel}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <XCircleIcon className="w-6 h-6" />
                    </button>
                </div>

                <div className="p-6 space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Frequency
                        </label>
                        <select
                            value={schedule.frequency}
                            onChange={(e) => setSchedule(prev => ({ ...prev, frequency: e.target.value }))}
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            {frequencyOptions.map(option => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    {schedule.frequency === 'weekly' && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Day of Week
                            </label>
                            <select
                                value={schedule.day_of_week}
                                onChange={(e) => setSchedule(prev => ({ ...prev, day_of_week: e.target.value }))}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                {dayOptions.map(option => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    {schedule.frequency === 'monthly' && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Day of Month
                            </label>
                            <input
                                type="number"
                                min="1"
                                max="31"
                                value={schedule.day_of_month}
                                onChange={(e) => setSchedule(prev => ({ ...prev, day_of_month: e.target.value }))}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Hour
                            </label>
                            <select
                                value={schedule.hour}
                                onChange={(e) => setSchedule(prev => ({ ...prev, hour: e.target.value }))}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                {Array.from({ length: 24 }, (_, i) => (
                                    <option key={i} value={i.toString().padStart(2, '0')}>
                                        {i.toString().padStart(2, '0')}:00
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Minute
                            </label>
                            <select
                                value={schedule.minute}
                                onChange={(e) => setSchedule(prev => ({ ...prev, minute: e.target.value }))}
                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                {['00', '15', '30', '45'].map(min => (
                                    <option key={min} value={min}>
                                        :{min}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Email Recipients
                        </label>
                        <textarea
                            value={schedule.recipients}
                            onChange={(e) => setSchedule(prev => ({ ...prev, recipients: e.target.value }))}
                            rows={3}
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter email addresses, separated by commas"
                        />
                    </div>

                    <label className="flex items-center">
                        <input
                            type="checkbox"
                            checked={schedule.enabled}
                            onChange={(e) => setSchedule(prev => ({ ...prev, enabled: e.target.checked }))}
                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <span className="ml-2 text-sm text-gray-700">Enable scheduled reports</span>
                    </label>
                </div>

                <div className="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-200">
                    <button
                        onClick={onCancel}
                        className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleSubmit}
                        className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
                    >
                        Schedule Report
                    </button>
                </div>
            </div>
        </div>
    );
};

export default function ComplianceReports({ className = "" }) {
    const [reportTemplates, setReportTemplates] = useState([]);
    const [loading, setLoading] = useState(true);
    const [currentModal, setCurrentModal] = useState(null);
    const [selectedTemplate, setSelectedTemplate] = useState(null);
    const [recentReports, setRecentReports] = useState([]);

    useEffect(() => {
        loadReportTemplates();
        loadRecentReports();
    }, []);

    const loadReportTemplates = async () => {
        try {
            const response = await fetch('/api/reports/templates');
            const data = await response.json();
            setReportTemplates(data);
        } catch (error) {
            console.error('Failed to load report templates:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadRecentReports = async () => {
        try {
            const response = await fetch('/api/reports/recent');
            const data = await response.json();
            setRecentReports(data);
        } catch (error) {
            console.error('Failed to load recent reports:', error);
        }
    };

    const handleGenerateReport = async (template, filters = {}) => {
        try {
            setCurrentModal(null);
            
            const response = await fetch('/api/reports/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    template_key: template.key,
                    filters
                })
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${template.name.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.${filters.format || 'xlsx'}`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                loadRecentReports(); // Refresh recent reports list
            } else {
                throw new Error('Report generation failed');
            }
        } catch (error) {
            console.error('Report generation error:', error);
            alert('Failed to generate report. Please try again.');
        }
    };

    const handlePreviewReport = (template) => {
        // Open preview in new window/tab
        window.open(`/api/reports/preview/${template.key}`, '_blank');
    };

    const handleScheduleReport = async (template, schedule) => {
        try {
            const response = await fetch('/api/reports/schedule', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    template_key: template.key,
                    schedule
                })
            });

            if (response.ok) {
                setCurrentModal(null);
                alert('Report scheduled successfully!');
                loadReportTemplates(); // Refresh templates to show updated schedule info
            } else {
                throw new Error('Failed to schedule report');
            }
        } catch (error) {
            console.error('Report scheduling error:', error);
            alert('Failed to schedule report. Please try again.');
        }
    };

    const defaultTemplates = [
        {
            key: 'compliance_summary',
            name: 'Compliance Summary',
            description: 'Overall compliance status across all departments and certificate types',
            formats: ['xlsx', 'pdf'],
            estimated_time: '2-3 minutes',
            frequency: 'Weekly',
            schedulable: true,
            last_run: null
        },
        {
            key: 'employee_certificates',
            name: 'Employee Certificate Report',
            description: 'Detailed certificate status for each employee',
            formats: ['xlsx', 'csv'],
            estimated_time: '3-5 minutes',
            frequency: 'Monthly',
            schedulable: true,
            last_run: null
        },
        {
            key: 'department_compliance',
            name: 'Department Compliance Analysis',
            description: 'Compliance rates and statistics by department',
            formats: ['xlsx', 'pdf'],
            estimated_time: '1-2 minutes',
            frequency: 'Monthly',
            schedulable: true,
            last_run: null
        },
        {
            key: 'expiry_forecast',
            name: 'Certificate Expiry Forecast',
            description: 'Upcoming certificate expirations and renewal schedule',
            formats: ['xlsx', 'csv', 'pdf'],
            estimated_time: '1-2 minutes',
            frequency: 'Weekly',
            schedulable: true,
            last_run: null
        },
        {
            key: 'certificate_inventory',
            name: 'Certificate Inventory',
            description: 'Complete inventory of all certificates and their current status',
            formats: ['xlsx', 'csv'],
            estimated_time: '5-10 minutes',
            frequency: 'Monthly',
            schedulable: false,
            last_run: null
        },
        {
            key: 'audit_trail',
            name: 'Audit Trail Report',
            description: 'Change history and audit log for compliance tracking',
            formats: ['xlsx', 'pdf'],
            estimated_time: '3-5 minutes',
            frequency: 'Quarterly',
            schedulable: true,
            last_run: null
        }
    ];

    const templates = reportTemplates.length > 0 ? reportTemplates : defaultTemplates;

    if (loading) {
        return (
            <div className={`flex items-center justify-center py-12 ${className}`}>
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p className="text-gray-600">Loading report templates...</p>
                </div>
            </div>
        );
    }

    return (
        <div className={`space-y-6 ${className}`}>
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900">Compliance Reports</h2>
                    <p className="text-gray-600">Generate and schedule compliance reports</p>
                </div>
                
                <button
                    onClick={loadRecentReports}
                    className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                    <ArrowPathIcon className="w-4 h-4 mr-2" />
                    Refresh
                </button>
            </div>

            {/* Report Templates */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {templates.map((template) => (
                    <ReportTemplateCard
                        key={template.key}
                        template={template}
                        onGenerate={(template) => {
                            setSelectedTemplate(template);
                            setCurrentModal('generate');
                        }}
                        onPreview={handlePreviewReport}
                        onSchedule={(template) => {
                            setSelectedTemplate(template);
                            setCurrentModal('schedule');
                        }}
                    />
                ))}
            </div>

            {/* Recent Reports */}
            {recentReports.length > 0 && (
                <div className="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 className="text-lg font-medium text-gray-900 mb-4">Recent Reports</h3>
                    <div className="space-y-3">
                        {recentReports.map((report) => (
                            <div key={report.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p className="font-medium text-gray-900">{report.name}</p>
                                    <p className="text-sm text-gray-600">
                                        Generated {new Date(report.created_at).toLocaleString()}
                                    </p>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <span className="text-xs text-gray-500">{report.format.toUpperCase()}</span>
                                    <a
                                        href={report.download_url}
                                        className="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200"
                                    >
                                        <DocumentArrowDownIcon className="w-3 h-3 mr-1" />
                                        Download
                                    </a>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Modals */}
            {currentModal === 'generate' && selectedTemplate && (
                <ReportGenerationModal
                    template={selectedTemplate}
                    onGenerate={handleGenerateReport}
                    onCancel={() => {
                        setCurrentModal(null);
                        setSelectedTemplate(null);
                    }}
                />
            )}

            {currentModal === 'schedule' && selectedTemplate && (
                <ReportScheduleModal
                    template={selectedTemplate}
                    onSchedule={handleScheduleReport}
                    onCancel={() => {
                        setCurrentModal(null);
                        setSelectedTemplate(null);
                    }}
                />
            )}
        </div>
    );
}

ReportTemplateCard.propTypes = {
    template: PropTypes.object.isRequired,
    onGenerate: PropTypes.func.isRequired,
    onPreview: PropTypes.func.isRequired,
    onSchedule: PropTypes.func.isRequired
};

ReportGenerationModal.propTypes = {
    template: PropTypes.object.isRequired,
    onGenerate: PropTypes.func.isRequired,
    onCancel: PropTypes.func.isRequired
};

ReportScheduleModal.propTypes = {
    template: PropTypes.object.isRequired,
    onSchedule: PropTypes.func.isRequired,
    onCancel: PropTypes.func.isRequired
};

ComplianceReports.propTypes = {
    className: PropTypes.string
};