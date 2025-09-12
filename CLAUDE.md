# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Backend (Laravel/PHP)
```bash
# Start development server with all services
composer run dev              # Runs server, queue, logs, and Vite concurrently

# Individual services
php artisan serve            # Laravel development server
php artisan queue:listen     # Background job processing
php artisan pail            # Real-time log monitoring

# Testing
composer run test           # Run PHPUnit tests
php artisan test           # Alternative test command

# Database
php artisan migrate        # Run migrations
php artisan db:seed        # Run database seeders

# Code quality
./vendor/bin/pint          # Laravel Pint code formatter (if available)
```

### Frontend (React/Vite)
```bash
# Development
npm run dev                # Start Vite development server

# Production
npm run build              # Build for production
```

## Architecture Overview

### Core System: Employee Container Management
This is a **training and certification management system** built around an "Employee Container" concept. Each employee has a digital container storing their background checks, certificates, and training records.

**Key Models:**
- `Employee`: Core employee data with background check files
- `EmployeeCertificate`: Individual certificates with expiry tracking
- `CertificateType`: Training/certification types with department requirements
- `Department`: Organizational departments

### Routing Structure
The application has a clear routing hierarchy in `routes/web.php`:

1. **Primary Route Group: Employee Containers** (`/employee-containers/`)
   - Main feature - digital employee folders
   - Certificate operations within containers
   - File upload/download for background checks and certificates
   - Bulk operations and search

2. **SDM Module** (`/sdm/`)
   - Employee master data management
   - Excel import/export functionality
   - Bulk operations for employee data

3. **Training Types Management** (`/training-types/`)
   - Certificate/training type administration
   - Department requirement mapping
   - Analytics and reporting

4. **Legacy Employee CRUD** (`/employees/admin/`)
   - Administrative employee management
   - Secondary to the container system

### Frontend Architecture (React + Inertia.js)

**Technology Stack:**
- React 18 with Inertia.js for server-side routing
- Tailwind CSS + Headless UI for styling
- Vite for build tooling
- Recharts for data visualization
- Lucide React for icons

**Key Page Structure:**
```
resources/js/Pages/
├── Auth/                    # Authentication pages
├── Dashboard/               # Dashboard variants
├── Employees/               # Employee management (legacy)
├── SDM/                     # Employee master data
├── TrainingTypes/           # Training/certificate type management
└── Departments/             # Department management
```

**Layout System:**
- `AuthenticatedLayout.jsx`: Main app layout with sidebar navigation
- `GuestLayout.jsx`: Authentication pages layout
- Components in `resources/js/Components/` follow Laravel Breeze patterns

### File Storage Architecture
- Uses Laravel's private disk for secure file storage
- Container-based file organization: `containers/employee-{id}/{type}/`
- Secure file serving through authenticated routes in `/files/`
- Background check files and certificates stored separately

### Key Features
1. **Employee Container System**: Digital folders for each employee
2. **Certificate Expiry Tracking**: Automatic status updates for expiring certificates
3. **Bulk Operations**: Mass updates and data imports via Excel
4. **Department-Based Requirements**: Training requirements per department
5. **File Management**: Secure upload/download with access control
6. **Analytics & Reporting**: Certificate compliance and statistics

### Database Structure
- Uses standard Laravel migrations in `database/migrations/`
- Key relationships: Employee → Certificates → Types → Departments
- Background check files stored as JSON array in employees table
- File storage metadata in separate `file_storage` table

### External Integrations
Based on `composer.json` dependencies:
- **Google Drive API**: Via `google/apiclient` 
- **PDF Generation**: Via `barryvdh/laravel-dompdf`
- **Excel Import/Export**: Via `maatwebsite/excel`
- **QR Code Generation**: Via `simplesoftwareio/simple-qrcode`

### Development Notes
- Laravel 12.x with PHP 8.2+
- Uses Laravel Breeze for authentication scaffolding
- Inertia.js bridges Laravel backend with React frontend
- Private file storage with secure access control
- Background job processing for certificate status updates