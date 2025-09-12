// components/ContainerManagement/GlobalSearch.jsx
import { useState, useEffect, useCallback, useRef } from 'react';
import { router } from '@inertiajs/react';
import PropTypes from 'prop-types';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    XMarkIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    UserIcon,
    BuildingOfficeIcon,
    AcademicCapIcon,
    CalendarIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ClockIcon,
    AdjustmentsHorizontalIcon
} from '@heroicons/react/24/outline';

// Debounced search hook
const useDebounce = (value, delay) => {
    const [debouncedValue, setDebouncedValue] = useState(value);

    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);

        return () => {
            clearTimeout(handler);
        };
    }, [value, delay]);

    return debouncedValue;
};

const SearchInput = ({ value, onChange, onSearch, loading }) => {
    return (
        <div className="relative flex-1">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
            </div>
            <input
                type="text"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                onKeyPress={(e) => e.key === 'Enter' && onSearch()}
                className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Search employees, departments, certificates..."
            />
            {loading && (
                <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                </div>
            )}
        </div>
    );
};

const FilterDropdown = ({ title, options, selected, onSelectionChange, icon: Icon, multiple = false }) => {
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleToggleOption = (value) => {
        if (multiple) {
            const newSelected = selected.includes(value)
                ? selected.filter(item => item !== value)
                : [...selected, value];
            onSelectionChange(newSelected);
        } else {
            onSelectionChange(value === selected ? null : value);
            setIsOpen(false);
        }
    };

    const selectedCount = multiple ? selected.length : (selected ? 1 : 0);

    return (
        <div className="relative" ref={dropdownRef}>
            <button
                onClick={() => setIsOpen(!isOpen)}
                className={`inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors ${
                    selectedCount > 0 
                        ? 'bg-blue-50 text-blue-700 border-blue-300' 
                        : 'bg-white text-gray-700 hover:bg-gray-50'
                }`}
            >
                {Icon && <Icon className="w-4 h-4 mr-2" />}
                {title}
                {selectedCount > 0 && (
                    <span className="ml-1 px-1.5 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">
                        {selectedCount}
                    </span>
                )}
                <ChevronDownIcon className={`ml-2 w-4 h-4 transition-transform ${
                    isOpen ? 'transform rotate-180' : ''
                }`} />
            </button>

            {isOpen && (
                <div className="absolute z-50 mt-1 w-64 bg-white rounded-lg shadow-lg border border-gray-200 max-h-60 overflow-y-auto">
                    <div className="py-1">
                        {options.map((option) => (
                            <label
                                key={option.value}
                                className="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer"
                            >
                                <input
                                    type={multiple ? 'checkbox' : 'radio'}
                                    name={title}
                                    checked={multiple ? selected.includes(option.value) : selected === option.value}
                                    onChange={() => handleToggleOption(option.value)}
                                    className="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <div className="flex-1">
                                    <div className="text-sm font-medium text-gray-900">
                                        {option.label}
                                    </div>
                                    {option.count !== undefined && (
                                        <div className="text-xs text-gray-500">
                                            {option.count} items
                                        </div>
                                    )}
                                </div>
                            </label>
                        ))}
                    </div>
                    
                    {multiple && selected.length > 0 && (
                        <div className="border-t border-gray-200 px-3 py-2">
                            <button
                                onClick={() => onSelectionChange([])}
                                className="text-xs text-blue-600 hover:text-blue-800"
                            >
                                Clear all
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

const QuickFilters = ({ filters, onFilterChange, quickStats }) => {
    const quickFilterOptions = [
        {
            key: 'expiring_soon',
            label: 'Expiring Soon',
            count: quickStats?.expiring_soon || 0,
            color: 'yellow',
            icon: ExclamationTriangleIcon
        },
        {
            key: 'expired',
            label: 'Expired',
            count: quickStats?.expired || 0,
            color: 'red',
            icon: XMarkIcon
        },
        {
            key: 'compliant',
            label: 'Fully Compliant',
            count: quickStats?.compliant || 0,
            color: 'green',
            icon: CheckCircleIcon
        },
        {
            key: 'pending',
            label: 'Pending Review',
            count: quickStats?.pending || 0,
            color: 'blue',
            icon: ClockIcon
        }
    ];

    return (
        <div className="flex flex-wrap gap-2">
            {quickFilterOptions.map((option) => {
                const Icon = option.icon;
                const isActive = filters.quick_filter === option.key;
                const colorClasses = {
                    yellow: isActive 
                        ? 'bg-yellow-100 text-yellow-800 border-yellow-300' 
                        : 'bg-white text-yellow-700 border-gray-300 hover:bg-yellow-50',
                    red: isActive 
                        ? 'bg-red-100 text-red-800 border-red-300' 
                        : 'bg-white text-red-700 border-gray-300 hover:bg-red-50',
                    green: isActive 
                        ? 'bg-green-100 text-green-800 border-green-300' 
                        : 'bg-white text-green-700 border-gray-300 hover:bg-green-50',
                    blue: isActive 
                        ? 'bg-blue-100 text-blue-800 border-blue-300' 
                        : 'bg-white text-blue-700 border-gray-300 hover:bg-blue-50'
                };

                return (
                    <button
                        key={option.key}
                        onClick={() => onFilterChange('quick_filter', isActive ? null : option.key)}
                        className={`inline-flex items-center px-3 py-2 border rounded-lg text-sm font-medium transition-colors ${
                            colorClasses[option.color]
                        }`}
                    >
                        <Icon className="w-4 h-4 mr-1.5" />
                        {option.label}
                        <span className="ml-1.5 px-1.5 py-0.5 text-xs bg-white bg-opacity-50 rounded-full">
                            {option.count}
                        </span>
                    </button>
                );
            })}
        </div>
    );
};

const ActiveFilters = ({ filters, onFilterChange, onClearAll, filterOptions }) => {
    const activeFilters = [];

    // Build active filters list
    if (filters.departments?.length > 0) {
        activeFilters.push({
            key: 'departments',
            label: 'Departments',
            value: filters.departments.map(d => 
                filterOptions.departments.find(dept => dept.value === d)?.label || d
            ).join(', '),
            count: filters.departments.length
        });
    }

    if (filters.certificate_types?.length > 0) {
        activeFilters.push({
            key: 'certificate_types',
            label: 'Certificate Types',
            value: filters.certificate_types.map(t => 
                filterOptions.certificateTypes.find(type => type.value === t)?.label || t
            ).join(', '),
            count: filters.certificate_types.length
        });
    }

    if (filters.certificate_status?.length > 0) {
        activeFilters.push({
            key: 'certificate_status',
            label: 'Status',
            value: filters.certificate_status.map(s => 
                filterOptions.certificateStatuses.find(status => status.value === s)?.label || s
            ).join(', '),
            count: filters.certificate_status.length
        });
    }

    if (filters.expiry_date_start || filters.expiry_date_end) {
        const startDate = filters.expiry_date_start ? new Date(filters.expiry_date_start).toLocaleDateString() : 'Any';
        const endDate = filters.expiry_date_end ? new Date(filters.expiry_date_end).toLocaleDateString() : 'Any';
        activeFilters.push({
            key: 'date_range',
            label: 'Expiry Date Range',
            value: `${startDate} - ${endDate}`
        });
    }

    if (filters.quick_filter) {
        activeFilters.push({
            key: 'quick_filter',
            label: 'Quick Filter',
            value: filters.quick_filter.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
        });
    }

    if (activeFilters.length === 0) return null;

    return (
        <div className="flex flex-wrap items-center gap-2">
            <span className="text-sm text-gray-500">Active filters:</span>
            {activeFilters.map((filter) => (
                <span
                    key={filter.key}
                    className="inline-flex items-center px-2.5 py-1 bg-blue-100 text-blue-800 text-sm rounded-full"
                >
                    <span className="font-medium">{filter.label}:</span>
                    <span className="ml-1 truncate max-w-32">{filter.value}</span>
                    {filter.count && filter.count > 1 && (
                        <span className="ml-1 text-xs">({filter.count})</span>
                    )}
                    <button
                        onClick={() => onFilterChange(filter.key, filter.key.endsWith('s') ? [] : null)}
                        className="ml-1.5 flex-shrink-0 text-blue-600 hover:text-blue-800"
                    >
                        <XMarkIcon className="w-3 h-3" />
                    </button>
                </span>
            ))}
            <button
                onClick={onClearAll}
                className="text-sm text-gray-500 hover:text-gray-700 underline ml-2"
            >
                Clear all
            </button>
        </div>
    );
};

export default function GlobalSearch({ 
    initialFilters = {}, 
    onSearchResults, 
    onFilterChange: onExternalFilterChange,
    className = "" 
}) {
    const [searchTerm, setSearchTerm] = useState(initialFilters.search || '');
    const [filters, setFilters] = useState({
        search: '',
        departments: [],
        certificate_types: [],
        certificate_status: [],
        expiry_date_start: '',
        expiry_date_end: '',
        quick_filter: null,
        ...initialFilters
    });
    const [loading, setLoading] = useState(false);
    const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);
    const [searchResults, setSearchResults] = useState({});
    const [filterOptions, setFilterOptions] = useState({
        departments: [],
        certificateTypes: [],
        certificateStatuses: [
            { value: 'active', label: 'Active', count: 0 },
            { value: 'expired', label: 'Expired', count: 0 },
            { value: 'expiring_soon', label: 'Expiring Soon', count: 0 },
            { value: 'pending', label: 'Pending', count: 0 }
        ]
    });
    const [quickStats, setQuickStats] = useState({});

    const debouncedSearchTerm = useDebounce(searchTerm, 500);

    // Load filter options and quick stats
    useEffect(() => {
        loadFilterOptions();
        loadQuickStats();
    }, []);

    // Perform search when debounced term or filters change
    useEffect(() => {
        performSearch();
    }, [debouncedSearchTerm, filters]);

    const loadFilterOptions = async () => {
        try {
            const response = await fetch('/api/container-search/filter-options');
            const data = await response.json();
            
            setFilterOptions({
                departments: data.departments || [],
                certificateTypes: data.certificate_types || [],
                certificateStatuses: data.certificate_statuses || [
                    { value: 'active', label: 'Active', count: 0 },
                    { value: 'expired', label: 'Expired', count: 0 },
                    { value: 'expiring_soon', label: 'Expiring Soon', count: 0 },
                    { value: 'pending', label: 'Pending', count: 0 }
                ]
            });
        } catch (error) {
            console.error('Failed to load filter options:', error);
        }
    };

    const loadQuickStats = async () => {
        try {
            const response = await fetch('/api/container-search/quick-stats');
            const data = await response.json();
            setQuickStats(data);
        } catch (error) {
            console.error('Failed to load quick stats:', error);
        }
    };

    const performSearch = async () => {
        setLoading(true);
        
        try {
            const searchParams = new URLSearchParams();
            
            // Add search term
            if (debouncedSearchTerm) {
                searchParams.append('search', debouncedSearchTerm);
            }
            
            // Add filters
            Object.entries(filters).forEach(([key, value]) => {
                if (value && value !== '' && !(Array.isArray(value) && value.length === 0)) {
                    if (Array.isArray(value)) {
                        value.forEach(v => searchParams.append(`${key}[]`, v));
                    } else {
                        searchParams.append(key, value);
                    }
                }
            });

            const response = await fetch(`/api/container-search?${searchParams.toString()}`);
            const data = await response.json();
            
            setSearchResults(data);
            
            if (onSearchResults) {
                onSearchResults(data);
            }
        } catch (error) {
            console.error('Search failed:', error);
            setSearchResults({ error: 'Search failed. Please try again.' });
        } finally {
            setLoading(false);
        }
    };

    const handleFilterChange = useCallback((key, value) => {
        const newFilters = { ...filters, [key]: value };
        setFilters(newFilters);
        
        if (onExternalFilterChange) {
            onExternalFilterChange(newFilters);
        }
    }, [filters, onExternalFilterChange]);

    const handleClearAllFilters = () => {
        const clearedFilters = {
            search: '',
            departments: [],
            certificate_types: [],
            certificate_status: [],
            expiry_date_start: '',
            expiry_date_end: '',
            quick_filter: null
        };
        setSearchTerm('');
        setFilters(clearedFilters);
        
        if (onExternalFilterChange) {
            onExternalFilterChange(clearedFilters);
        }
    };

    const handleSearchTermChange = (term) => {
        setSearchTerm(term);
        handleFilterChange('search', term);
    };

    return (
        <div className={`bg-white rounded-lg shadow-sm border border-gray-200 ${className}`}>
            {/* Main Search Bar */}
            <div className="p-4 border-b border-gray-200">
                <div className="flex items-center space-x-3">
                    <SearchInput
                        value={searchTerm}
                        onChange={handleSearchTermChange}
                        onSearch={performSearch}
                        loading={loading}
                    />
                    
                    <button
                        onClick={() => setShowAdvancedFilters(!showAdvancedFilters)}
                        className={`inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors ${
                            showAdvancedFilters ? 'bg-blue-50 text-blue-700' : 'bg-white text-gray-700 hover:bg-gray-50'
                        }`}
                    >
                        <AdjustmentsHorizontalIcon className="w-4 h-4 mr-2" />
                        Filters
                        <ChevronDownIcon className={`ml-2 w-4 h-4 transition-transform ${
                            showAdvancedFilters ? 'transform rotate-180' : ''
                        }`} />
                    </button>
                </div>
                
                {/* Search Results Summary */}
                {searchResults.summary && (
                    <div className="mt-3 text-sm text-gray-600">
                        Found {searchResults.summary.total} results
                        {searchResults.summary.total > 0 && (
                            <>
                                {' '}({searchResults.summary.employees} employees, {searchResults.summary.certificates} certificates)
                            </>
                        )}
                        {debouncedSearchTerm && (
                            <span> for "{debouncedSearchTerm}"</span>
                        )}
                    </div>
                )}
            </div>

            {/* Quick Filters */}
            <div className="px-4 py-3 border-b border-gray-200">
                <QuickFilters
                    filters={filters}
                    onFilterChange={handleFilterChange}
                    quickStats={quickStats}
                />
            </div>

            {/* Advanced Filters */}
            {showAdvancedFilters && (
                <div className="p-4 bg-gray-50 border-b border-gray-200">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <FilterDropdown
                            title="Departments"
                            options={filterOptions.departments}
                            selected={filters.departments}
                            onSelectionChange={(value) => handleFilterChange('departments', value)}
                            icon={BuildingOfficeIcon}
                            multiple
                        />
                        
                        <FilterDropdown
                            title="Certificate Types"
                            options={filterOptions.certificateTypes}
                            selected={filters.certificate_types}
                            onSelectionChange={(value) => handleFilterChange('certificate_types', value)}
                            icon={AcademicCapIcon}
                            multiple
                        />
                        
                        <FilterDropdown
                            title="Certificate Status"
                            options={filterOptions.certificateStatuses}
                            selected={filters.certificate_status}
                            onSelectionChange={(value) => handleFilterChange('certificate_status', value)}
                            icon={CheckCircleIcon}
                            multiple
                        />
                        
                        <div className="space-y-2">
                            <label className="block text-sm font-medium text-gray-700">
                                Expiry Date Range
                            </label>
                            <div className="flex space-x-2">
                                <input
                                    type="date"
                                    value={filters.expiry_date_start}
                                    onChange={(e) => handleFilterChange('expiry_date_start', e.target.value)}
                                    className="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Start date"
                                />
                                <input
                                    type="date"
                                    value={filters.expiry_date_end}
                                    onChange={(e) => handleFilterChange('expiry_date_end', e.target.value)}
                                    className="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="End date"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Active Filters */}
            <div className="px-4 py-3">
                <ActiveFilters
                    filters={filters}
                    onFilterChange={handleFilterChange}
                    onClearAll={handleClearAllFilters}
                    filterOptions={filterOptions}
                />
            </div>
        </div>
    );
}

SearchInput.propTypes = {
    value: PropTypes.string.isRequired,
    onChange: PropTypes.func.isRequired,
    onSearch: PropTypes.func.isRequired,
    loading: PropTypes.bool
};

FilterDropdown.propTypes = {
    title: PropTypes.string.isRequired,
    options: PropTypes.array.isRequired,
    selected: PropTypes.oneOfType([PropTypes.array, PropTypes.string]),
    onSelectionChange: PropTypes.func.isRequired,
    icon: PropTypes.elementType,
    multiple: PropTypes.bool
};

QuickFilters.propTypes = {
    filters: PropTypes.object.isRequired,
    onFilterChange: PropTypes.func.isRequired,
    quickStats: PropTypes.object
};

ActiveFilters.propTypes = {
    filters: PropTypes.object.isRequired,
    onFilterChange: PropTypes.func.isRequired,
    onClearAll: PropTypes.func.isRequired,
    filterOptions: PropTypes.object.isRequired
};

GlobalSearch.propTypes = {
    initialFilters: PropTypes.object,
    onSearchResults: PropTypes.func,
    onFilterChange: PropTypes.func,
    className: PropTypes.string
};