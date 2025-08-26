import React, { useState, useEffect } from 'react';
import {
    Save, X, AlertCircle, CheckCircle, DollarSign, Clock,
    Users, BookOpen, Shield, Target, Settings, Info
} from 'lucide-react';

const TrainingTypeForm = ({
    trainingType = null,
    providers = [],
    categoryOptions = {},
    onSave,
    onCancel,
    errors = {}
}) => {
    const [formData, setFormData] = useState({
        name: '',
        code: '',
        category: '',
        description: '',
        is_mandatory: false,
        is_active: true,
        validity_period_months: 12,
        warning_period_days: 30,
        default_provider_id: '',
        estimated_cost: '',
        estimated_duration_hours: '',
        requirements: '',
        learning_objectives: '',
        requires_certification: true,
        auto_renewal_available: false,
        max_participants_per_batch: '',
        compliance_target_percentage: 95.00,
        applicable_departments: [],
        applicable_job_levels: []
    });

    const [activeTab, setActiveTab] = useState('basic');
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Mock departments - in real app, this would come from props
    const mockDepartments = [
        { id: 1, name: 'Operations' },
        { id: 2, name: 'Security' },
        { id: 3, name: 'Engineering' },
        { id: 4, name: 'Management' },
        { id: 5, name: 'HR' },
        { id: 6, name: 'Finance' }
    ];

    const jobLevels = [
        'Entry Level',
        'Junior Staff',
        'Senior Staff',
        'Supervisor',
        'Manager',
        'Senior Manager',
        'Director'
    ];

    useEffect(() => {
        if (trainingType) {
            setFormData({
                ...trainingType,
                estimated_cost: trainingType.estimated_cost || '',
                estimated_duration_hours: trainingType.estimated_duration_hours || '',
                max_participants_per_batch: trainingType.max_participants_per_batch || '',
                applicable_departments: trainingType.applicable_departments || [],
                applicable_job_levels: trainingType.applicable_job_levels || []
            });
        }
    }, [trainingType]);

    const handleInputChange = (field, value) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const handleMultiSelectChange = (field, value, checked) => {
        setFormData(prev => ({
            ...prev,
            [field]: checked
                ? [...prev[field], value]
                : prev[field].filter(item => item !== value)
        }));
    };

    const handleSubmit = async () => {
        setIsSubmitting(true);

        try {
            await onSave(formData);
        } catch (error) {
            console.error('Form submission error:', error);
        } finally {
            setIsSubmitting(false);
        }
    };

    const generateCode = () => {
        const categoryCode = formData.category
            ? formData.category.split(' ').map(word => word.substring(0, 1)).join('').toUpperCase()
            : 'TRN';
        const randomNum = Math.floor(Math.random() * 999) + 1;
        const code = `${categoryCode}-${randomNum.toString().padStart(3, '0')}`;
        handleInputChange('code', code);
    };

    const tabs = [
        { id: 'basic', label: 'Basic Information', icon: BookOpen },
        { id: 'details', label: 'Training Details', icon: Info },
        { id: 'compliance', label: 'Compliance & Target', icon: Target },
        { id: 'advanced', label: 'Advanced Settings', icon: Settings }
    ];

    return (
        <div className="max-w-4xl mx-auto bg-white rounded-lg shadow-lg">
            {/* Header */}
            <div className="px-6 py-4 border-b border-gray-200">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">
                            {trainingType ? 'Edit Training Type' : 'Create New Training Type'}
                        </h2>
                        <p className="text-sm text-gray-500 mt-1">
                            {trainingType
                                ? `Modify training type configuration`
                                : 'Set up a new training type with compliance tracking'
                            }
                        </p>
                    <button
                        onClick={onCancel}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <X className="h-6 w-6" />
                    </button>
                </div>
            </div>

            {/* Tab Navigation */}
            <div className="border-b border-gray-200">
                <nav className="flex space-x-8 px-6">
                    {tabs.map((tab) => {
                        const Icon = tab.icon;
                        return (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={`flex items-center space-x-2 py-4 px-1 border-b-2 font-medium text-sm ${
                                    activeTab === tab.id
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

            <div className="px-6 py-6">
                    {/* Basic Information Tab */}
                    {activeTab === 'basic' && (
                        <div className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Training Type Name *
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.name}
                                        onChange={(e) => handleInputChange('name', e.target.value)}
                                        className={`w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 ${
                                            errors.name ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                        placeholder="e.g., Aviation Safety Awareness"
                                    />
                                    {errors.name && (
                                        <p className="mt-1 text-sm text-red-600 flex items-center">
                                            <AlertCircle className="h-4 w-4 mr-1" />
                                            {errors.name}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Training Code *
                                    </label>
                                    <div className="flex">
                                        <input
                                            type="text"
                                            value={formData.code}
                                            onChange={(e) => handleInputChange('code', e.target.value)}
                                            className={`flex-1 px-3 py-2 border rounded-l-md focus:ring-blue-500 focus:border-blue-500 ${
                                                errors.code ? 'border-red-300' : 'border-gray-300'
                                            }`}
                                            placeholder="e.g., ASA-001"
                                        />
                                        <button
                                            type="button"
                                            onClick={generateCode}
                                            className="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md text-sm hover:bg-gray-200"
                                        >
                                            Generate
                                        </button>
                                    </div>
                                    {errors.code && (
                                        <p className="mt-1 text-sm text-red-600 flex items-center">
                                            <AlertCircle className="h-4 w-4 mr-1" />
                                            {errors.code}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Category *
                                    </label>
                                    <select
                                        value={formData.category}
                                        onChange={(e) => handleInputChange('category', e.target.value)}
                                        className={`w-full px-3 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500 ${
                                            errors.category ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                    >
                                        <option value="">Select Category</option>
                                        <option value="Aviation Safety">Aviation Safety</option>
                                        <option value="Security">Security</option>
                                        <option value="Operations">Operations</option>
                                        <option value="Technical">Technical</option>
                                        <option value="Management">Management</option>
                                        <option value="Compliance">Compliance</option>
                                        <option value="Health & Safety">Health & Safety</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Default Training Provider
                                    </label>
                                    <select
                                        value={formData.default_provider_id}
                                        onChange={(e) => handleInputChange('default_provider_id', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">No default provider</option>
                                        {providers.map(provider => (
                                            <option key={provider.id} value={provider.id}>
                                                {provider.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Description
                                </label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => handleInputChange('description', e.target.value)}
                                    rows={3}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Brief description of the training..."
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="flex items-center space-x-3">
                                    <input
                                        type="checkbox"
                                        checked={formData.is_mandatory}
                                        onChange={(e) => handleInputChange('is_mandatory', e.target.checked)}
                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">
                                            Mandatory Training
                                        </label>
                                        <p className="text-xs text-gray-500">
                                            Required for all applicable employees
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-center space-x-3">
                                    <input
                                        type="checkbox"
                                        checked={formData.is_active}
                                        onChange={(e) => handleInputChange('is_active', e.target.checked)}
                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">
                                            Active Status
                                        </label>
                                        <p className="text-xs text-gray-500">
                                            Currently available for assignment
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Training Details Tab */}
                    {activeTab === 'details' && (
                        <div className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        <Clock className="inline h-4 w-4 mr-1" />
                                        Validity Period (Months)
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.validity_period_months}
                                        onChange={(e) => handleInputChange('validity_period_months', parseInt(e.target.value))}
                                        min="1"
                                        max="120"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Warning Period (Days)
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.warning_period_days}
                                        onChange={(e) => handleInputChange('warning_period_days', parseInt(e.target.value))}
                                        min="1"
                                        max="365"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        <Users className="inline h-4 w-4 mr-1" />
                                        Max Participants per Batch
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.max_participants_per_batch}
                                        onChange={(e) => handleInputChange('max_participants_per_batch', parseInt(e.target.value) || '')}
                                        min="1"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="No limit"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        <DollarSign className="inline h-4 w-4 mr-1" />
                                        Estimated Cost (IDR)
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.estimated_cost}
                                        onChange={(e) => handleInputChange('estimated_cost', parseFloat(e.target.value) || '')}
                                        min="0"
                                        step="1000"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="0"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Duration (Hours)
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.estimated_duration_hours}
                                        onChange={(e) => handleInputChange('estimated_duration_hours', parseFloat(e.target.value) || '')}
                                        min="0.5"
                                        step="0.5"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="0"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Training Requirements
                                </label>
                                <textarea
                                    value={formData.requirements}
                                    onChange={(e) => handleInputChange('requirements', e.target.value)}
                                    rows={3}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Prerequisites, eligibility criteria, etc..."
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Learning Objectives
                                </label>
                                <textarea
                                    value={formData.learning_objectives}
                                    onChange={(e) => handleInputChange('learning_objectives', e.target.value)}
                                    rows={3}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="What participants will learn and achieve..."
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="flex items-center space-x-3">
                                    <input
                                        type="checkbox"
                                        checked={formData.requires_certification}
                                        onChange={(e) => handleInputChange('requires_certification', e.target.checked)}
                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">
                                            Requires Certification
                                        </label>
                                        <p className="text-xs text-gray-500">
                                            Issues certificate upon completion
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-center space-x-3">
                                    <input
                                        type="checkbox"
                                        checked={formData.auto_renewal_available}
                                        onChange={(e) => handleInputChange('auto_renewal_available', e.target.checked)}
                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <div>
                                        <label className="text-sm font-medium text-gray-700">
                                            Auto Renewal Available
                                        </label>
                                        <p className="text-xs text-gray-500">
                                            Can be automatically renewed
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Compliance & Target Tab */}
                    {activeTab === 'compliance' && (
                        <div className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    <Target className="inline h-4 w-4 mr-1" />
                                    Target Compliance Percentage
                                </label>
                                <div className="relative">
                                    <input
                                        type="number"
                                        value={formData.compliance_target_percentage}
                                        onChange={(e) => handleInputChange('compliance_target_percentage', parseFloat(e.target.value))}
                                        min="0"
                                        max="100"
                                        step="0.1"
                                        className="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    />
                                    <span className="ml-2 text-sm text-gray-500">%</span>
                                </div>
                                <p className="mt-1 text-xs text-gray-500">
                                    Target percentage of applicable employees who should have valid certificates
                                </p>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-3">
                                    Applicable Departments
                                </label>
                                <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    {mockDepartments.map(dept => (
                                        <label key={dept.id} className="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                checked={formData.applicable_departments.includes(dept.id)}
                                                onChange={(e) => handleMultiSelectChange('applicable_departments', dept.id, e.target.checked)}
                                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                            />
                                            <span className="text-sm text-gray-700">{dept.name}</span>
                                        </label>
                                    ))}
                                </div>
                                <p className="mt-1 text-xs text-gray-500">
                                    Leave empty to apply to all departments
                                </p>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-3">
                                    Applicable Job Levels
                                </label>
                                <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    {jobLevels.map(level => (
                                        <label key={level} className="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                checked={formData.applicable_job_levels.includes(level)}
                                                onChange={(e) => handleMultiSelectChange('applicable_job_levels', level, e.target.checked)}
                                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                            />
                                            <span className="text-sm text-gray-700">{level}</span>
                                        </label>
                                    ))}
                                </div>
                                <p className="mt-1 text-xs text-gray-500">
                                    Leave empty to apply to all job levels
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Advanced Settings Tab */}
                    {activeTab === 'advanced' && (
                        <div className="space-y-6">
                            <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                <div className="flex">
                                    <AlertCircle className="h-5 w-5 text-yellow-400" />
                                    <div className="ml-3">
                                        <h3 className="text-sm font-medium text-yellow-800">
                                            Advanced Settings
                                        </h3>
                                        <p className="mt-1 text-sm text-yellow-700">
                                            These settings are for advanced configuration. Modify only if you understand their impact.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Priority Score (0-100)
                                </label>
                                <input
                                    type="number"
                                    value={formData.priority_score || 0}
                                    onChange={(e) => handleInputChange('priority_score', parseInt(e.target.value))}
                                    min="0"
                                    max="100"
                                    className="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Higher scores indicate higher priority for scheduling and compliance tracking
                                </p>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Certificate Template Path
                                </label>
                                <input
                                    type="text"
                                    value={formData.certificate_template || ''}
                                    onChange={(e) => handleInputChange('certificate_template', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="templates/certificates/default.pdf"
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Path to the certificate template file for this training type
                                </p>
                            </div>
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                    <div className="text-sm text-gray-500">
                        * Required fields
                    </div>
                    <div className="flex items-center space-x-3">
                        <button
                            type="button"
                            onClick={onCancel}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleSubmit}
                            disabled={isSubmitting}
                            className="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Save className="h-4 w-4" />
                            <span>
                                {isSubmitting
                                    ? 'Saving...'
                                    : trainingType
                                        ? 'Update Training Type'
                                        : 'Create Training Type'
                                }
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default TrainingTypeForm;
