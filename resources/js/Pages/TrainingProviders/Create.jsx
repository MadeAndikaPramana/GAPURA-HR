import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    BuildingOfficeIcon,
    UserIcon,
    EnvelopeIcon,
    PhoneIcon,
    GlobeAltIcon,
    ShieldCheckIcon,
    DocumentTextIcon,
    CalendarDaysIcon,
    StarIcon
} from '@heroicons/react/24/outline';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        code: '',
        contact_person: '',
        email: '',
        phone: '',
        address: '',
        website: '',
        accreditation_number: '',
        accreditation_expiry: '',
        contract_start_date: '',
        contract_end_date: '',
        rating: '',
        notes: '',
        is_active: true
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('training-providers.store'), {
            onSuccess: () => {
                reset();
            },
        });
    };

    const getRatingStars = (rating) => {
        const stars = [];
        const ratingValue = parseFloat(rating) || 0;
        const fullStars = Math.floor(ratingValue);
        const hasHalfStar = ratingValue % 1 !== 0;

        for (let i = 0; i < 5; i++) {
            if (i < fullStars) {
                stars.push(
                    <StarIcon key={i} className="w-5 h-5 text-yellow-400 fill-current" />
                );
            } else if (i === fullStars && hasHalfStar) {
                stars.push(
                    <StarIcon key={i} className="w-5 h-5 text-yellow-400 fill-current opacity-50" />
                );
            } else {
                stars.push(
                    <StarIcon key={i} className="w-5 h-5 text-gray-300" />
                );
            }
        }

        return stars;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link
                            href={route('training-providers.index')}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <ArrowLeftIcon className="w-4 h-4 mr-2" />
                            Back to Training Providers
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                Add New Training Provider
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Register a new training service provider
                            </p>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title="Add Training Provider" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <div className="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <div className="flex items-center">
                                <BuildingOfficeIcon className="w-8 h-8 text-green-600 mr-3" />
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">Provider Information</h3>
                                    <p className="text-sm text-gray-500">Fill in the details for the new training provider</p>
                                </div>
                            </div>
                        </div>

                        <div className="p-6">
                            <div className="p-6">
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    {/* Left Column - Basic Information */}
                                    <div className="space-y-6">
                                        <div>
                                            <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                                <BuildingOfficeIcon className="w-5 h-5 mr-2 text-green-600" />
                                                Basic Information
                                            </h4>

                                            {/* Provider Name */}
                                            <div className="mb-4">
                                                <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                                                    Provider Name *
                                                </label>
                                                <input
                                                    type="text"
                                                    id="name"
                                                    value={data.name}
                                                    onChange={(e) => setData('name', e.target.value)}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                    placeholder="Enter provider company name"
                                                />
                                                {errors.name && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                                )}
                                            </div>

                                            {/* Provider Code */}
                                            <div className="mb-4">
                                                <label htmlFor="code" className="block text-sm font-medium text-gray-700 mb-1">
                                                    Provider Code
                                                </label>
                                                <input
                                                    type="text"
                                                    id="code"
                                                    value={data.code}
                                                    onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                    placeholder="e.g., PROVIDER001"
                                                    maxLength="20"
                                                />
                                                {errors.code && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.code}</p>
                                                )}
                                                <p className="mt-1 text-xs text-gray-500">Optional unique identifier for the provider</p>
                                            </div>

                                            {/* Rating */}
                                            <div className="mb-4">
                                                <label htmlFor="rating" className="block text-sm font-medium text-gray-700 mb-1">
                                                    Initial Rating
                                                </label>
                                                <div className="flex items-center space-x-3">
                                                    <input
                                                        type="number"
                                                        id="rating"
                                                        min="0"
                                                        max="5"
                                                        step="0.1"
                                                        value={data.rating}
                                                        onChange={(e) => setData('rating', e.target.value)}
                                                        className="block w-24 border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                        placeholder="0.0"
                                                    />
                                                    <div className="flex">
                                                        {getRatingStars(data.rating)}
                                                    </div>
                                                    <span className="text-sm text-gray-500">/ 5.0</span>
                                                </div>
                                                {errors.rating && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.rating}</p>
                                                )}
                                                <p className="mt-1 text-xs text-gray-500">Rating from 0.0 to 5.0 (optional)</p>
                                            </div>

                                            {/* Status */}
                                            <div className="mb-4">
                                                <div className="flex items-center">
                                                    <input
                                                        id="is_active"
                                                        type="checkbox"
                                                        checked={data.is_active}
                                                        onChange={(e) => setData('is_active', e.target.checked)}
                                                        className="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                                                    />
                                                    <label htmlFor="is_active" className="ml-2 block text-sm text-gray-900">
                                                        Active Provider
                                                    </label>
                                                </div>
                                                <p className="mt-1 text-xs text-gray-500">Active providers appear in training assignment options</p>
                                            </div>
                                        </div>

                                        {/* Contact Information */}
                                        <div>
                                            <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                                <UserIcon className="w-5 h-5 mr-2 text-green-600" />
                                                Contact Information
                                            </h4>

                                            {/* Contact Person */}
                                            <div className="mb-4">
                                                <label htmlFor="contact_person" className="block text-sm font-medium text-gray-700 mb-1">
                                                    Contact Person
                                                </label>
                                                <input
                                                    type="text"
                                                    id="contact_person"
                                                    value={data.contact_person}
                                                    onChange={(e) => setData('contact_person', e.target.value)}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                    placeholder="Primary contact name"
                                                />
                                                {errors.contact_person && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.contact_person}</p>
                                                )}
                                            </div>

                                            {/* Email */}
                                            <div className="mb-4">
                                                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                                                    <EnvelopeIcon className="w-4 h-4 inline mr-1" />
                                                    Email Address
                                                </label>
                                                <input
                                                    type="email"
                                                    id="email"
                                                    value={data.email}
                                                    onChange={(e) => setData('email', e.target.value)}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                    placeholder="provider@example.com"
                                                />
                                                {errors.email && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                                )}
                                            </div>

                                            {/* Phone */}
                                            <div className="mb-4">
                                                <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1">
                                                    <PhoneIcon className="w-4 h-4 inline mr-1" />
                                                    Phone Number
                                                </label>
                                                <input
                                                    type="tel"
                                                    id="phone"
                                                    value={data.phone}
                                                    onChange={(e) => setData('phone', e.target.value)}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                    placeholder="+62 21 1234 5678"
                                                />
                                                {errors.phone && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
                                                )}
                                            </div>

                                            {/* Website */}
                                            <div className="mb-4">
                                                <label htmlFor="website" className="block text-sm font-medium text-gray-700 mb-1">
                                                    <GlobeAltIcon className="w-4 h-4 inline mr-1" />
                                                    Website
                                                </label>
                                                <input
                                                    type="url"
                                                    id="website"
                                                    value={data.website}
                                                    onChange={(e) => setData('website', e.target.value)}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                    placeholder="https://www.provider.com"
                                                />
                                                {errors.website && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.website}</p>
                                                )}
                                            </div>

                                            {/* Address */}
                                            <div className="mb-4">
                                                <label htmlFor="address" className="block text-sm font-medium text-gray-700 mb-1">
                                                    Address
                                                </label>
                                                <textarea
                                                    id="address"
                                                    rows="3"
                                                    value={data.address}
                                                    onChange={(e) => setData('address', e.target.value)}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                    placeholder="Complete business address"
                                                />
                                                {errors.address && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.address}</p>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    {/* Right Column - Accreditation & Contract */}
                                    <div className="space-y-6">
                                        {/* Accreditation Information */}
                                        <div>
                                            <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                                <ShieldCheckIcon className="w-5 h-5 mr-2 text-green-600" />
                                                Accreditation & Certification
                                            </h4>

                                            {/* Accreditation Number */}
                                            <div className="mb-4">
                                                <label htmlFor="accreditation_number" className="block text-sm font-medium text-gray-700 mb-1">
                                                    Accreditation Number
                                                </label>
                                                <input
                                                    type="text"
                                                    id="accreditation_number"
                                                    value={data.accreditation_number}
                                                    onChange={(e) => setData('accreditation_number', e.target.value)}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                    placeholder="e.g., LSP-001-2024"
                                                />
                                                {errors.accreditation_number && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.accreditation_number}</p>
                                                )}
                                                <p className="mt-1 text-xs text-gray-500">Official accreditation/certification number</p>
                                            </div>

                                            {/* Accreditation Expiry */}
                                            <div className="mb-4">
                                                <label htmlFor="accreditation_expiry" className="block text-sm font-medium text-gray-700 mb-1">
                                                    <CalendarDaysIcon className="w-4 h-4 inline mr-1" />
                                                    Accreditation Expiry Date
                                                </label>
                                                <input
                                                    type="date"
                                                    id="accreditation_expiry"
                                                    value={data.accreditation_expiry}
                                                    onChange={(e) => setData('accreditation_expiry', e.target.value)}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                />
                                                {errors.accreditation_expiry && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.accreditation_expiry}</p>
                                                )}
                                                <p className="mt-1 text-xs text-gray-500">When the accreditation expires</p>
                                            </div>
                                        </div>

                                        {/* Contract Information */}
                                        <div>
                                            <h4 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                                <DocumentTextIcon className="w-5 h-5 mr-2 text-green-600" />
                                                Contract Information
                                            </h4>

                                            {/* Contract Start Date */}
                                            <div className="mb-4">
                                                <label htmlFor="contract_start_date" className="block text-sm font-medium text-gray-700 mb-1">
                                                    Contract Start Date
                                                </label>
                                                <input
                                                    type="date"
                                                    id="contract_start_date"
                                                    value={data.contract_start_date}
                                                    onChange={(e) => setData('contract_start_date', e.target.value)}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                />
                                                {errors.contract_start_date && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.contract_start_date}</p>
                                                )}
                                            </div>

                                            {/* Contract End Date */}
                                            <div className="mb-4">
                                                <label htmlFor="contract_end_date" className="block text-sm font-medium text-gray-700 mb-1">
                                                    Contract End Date
                                                </label>
                                                <input
                                                    type="date"
                                                    id="contract_end_date"
                                                    value={data.contract_end_date}
                                                    onChange={(e) => setData('contract_end_date', e.target.value)}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                />
                                                {errors.contract_end_date && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.contract_end_date}</p>
                                                )}
                                            </div>
                                        </div>

                                        {/* Notes */}
                                        <div>
                                            <h4 className="text-lg font-medium text-gray-900 mb-4">
                                                Additional Notes
                                            </h4>

                                            <div className="mb-4">
                                                <label htmlFor="notes" className="block text-sm font-medium text-gray-700 mb-1">
                                                    Notes
                                                </label>
                                                <textarea
                                                    id="notes"
                                                    rows="4"
                                                    value={data.notes}
                                                    onChange={(e) => setData('notes', e.target.value)}
                                                    className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                                    placeholder="Additional information about this provider..."
                                                />
                                                {errors.notes && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
                                                )}
                                                <p className="mt-1 text-xs text-gray-500">Internal notes about the provider</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Form Actions */}
                                <div className="mt-8 pt-6 border-t border-gray-200 flex items-center justify-end space-x-4">
                                    <Link
                                        href={route('training-providers.index')}
                                        className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        Cancel
                                    </Link>
                                    <button
                                        onClick={handleSubmit}
                                        disabled={processing}
                                        className="inline-flex items-center px-6 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'Creating...' : 'Create Provider'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Help Text */}
                    <div className="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div className="flex">
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-blue-800">
                                    💡 Tips for Adding Training Providers
                                </h3>
                                <div className="mt-2 text-sm text-blue-700">
                                    <ul className="list-disc list-inside space-y-1">
                                        <li><strong>Provider Name:</strong> Use the complete legal business name</li>
                                        <li><strong>Provider Code:</strong> Optional unique identifier for easy reference</li>
                                        <li><strong>Accreditation:</strong> Add certification details to ensure compliance</li>
                                        <li><strong>Contract Dates:</strong> Track active contract periods</li>
                                        <li><strong>Rating:</strong> Initial rating can be updated based on performance</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
