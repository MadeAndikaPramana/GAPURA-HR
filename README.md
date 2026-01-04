# CertManager

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/React-18-61DAFB?style=for-the-badge&logo=react&logoColor=black" alt="React">
  <img src="https://img.shields.io/badge/Inertia.js-1.0-9553E9?style=for-the-badge&logo=inertia&logoColor=white" alt="Inertia">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind">
</p>

Sistem Manajemen Pelatihan & Sertifikasi Karyawan berbasis web yang modern dan user-friendly. CertManager dirancang untuk mengelola data karyawan, sertifikasi, dan pelatihan dengan pendekatan "Employee Container" - folder digital untuk setiap karyawan.

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Tech Stack](#-tech-stack)
- [Requirements](#-requirements)
- [Instalasi](#-instalasi)
- [Konfigurasi](#️-konfigurasi)
- [Database Setup](#-database-setup)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [Development Commands](#-development-commands)
- [Struktur Project](#-struktur-project)
- [Panduan Penggunaan](#-panduan-penggunaan)
- [Troubleshooting](#-troubleshooting)
- [Credits](#-credits)

## ✨ Fitur Utama

### 1. **Employee Container System**
Sistem folder digital untuk setiap karyawan yang menyimpan:
- Background check files
- Sertifikat & dokumen pelatihan
- Status sertifikasi real-time
- Riwayat pelatihan lengkap

### 2. **Dashboard Analytics**
Dashboard komprehensif dengan:
- 📊 8 kartu statistik real-time
- 📈 Pie Chart status sertifikat
- 📉 Bar Chart per departemen
- 📅 Line Chart trend 6 bulan
- 🔔 Recent activities & alerts
- ⚡ Quick actions panel

### 3. **Certificate Expiry Tracking**
- Auto-update status sertifikat (Active, Expiring Soon, Expired)
- Warning notification H-30 sebelum expired
- Bulk compliance reporting
- Export Excel untuk audit

### 4. **Training Types Management**
- Master data jenis pelatihan/sertifikasi
- Department requirement mapping
- Validity period & recurrent training
- Learning objectives & cost estimation
- Analytics per training type

### 5. **Employee Management (SDM Module)**
- CRUD karyawan lengkap
- Import/export Excel
- Department assignment
- Background check file upload
- Bulk operations

### 6. **Department Management**
- Struktur departemen organisasi
- Training requirements per department
- Department-based analytics
- Active/inactive status

### 7. **File Management**
- Secure private file storage
- Container-based organization
- Background check & certificate files
- Access control & authentication
- Download/upload dengan validation

### 8. **Reporting & Export**
- Employee containers export (Excel)
- Compliance reports
- Certificate analytics
- Department statistics
- Custom filters

## 🛠 Tech Stack

### Backend
- **Laravel 12.x** - PHP Framework
- **PHP 8.2+** - Programming Language
- **SQLite/MySQL** - Database
- **Laravel Breeze** - Authentication
- **Maatwebsite/Excel** - Excel Import/Export
- **DomPDF** - PDF Generation
- **Google Drive API** - Cloud Storage Integration

### Frontend
- **React 18** - UI Framework
- **Inertia.js** - SPA Routing
- **Tailwind CSS** - Styling Framework
- **Headless UI** - Accessible Components
- **Recharts** - Data Visualization
- **Lucide React** - Icon Library
- **Vite** - Build Tool

## 📦 Requirements

- **PHP** >= 8.2
- **Composer** >= 2.x
- **Node.js** >= 18.x
- **NPM** >= 9.x
- **SQLite** atau **MySQL** 8.x
- **Git**

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
cd certmanager
```

### 2. Install Dependencies

**Backend (PHP):**
```bash
composer install
```

**Frontend (Node):**
```bash
npm install
```

### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

## ⚙️ Konfigurasi

Edit file `.env` sesuai environment Anda:

```env
APP_NAME="CertManager"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database - SQLite (Default)
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# Atau MySQL
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=certmanager
# DB_USERNAME=root
# DB_PASSWORD=

# Mail Configuration (Optional)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@certmanager.local
MAIL_FROM_NAME="${APP_NAME}"

# Queue Configuration
QUEUE_CONNECTION=database
```

## 🗄 Database Setup

### Opsi 1: SQLite (Recommended untuk Development)

```bash
# Buat file database
touch database/database.sqlite

# Jalankan migrations
php artisan migrate:fresh

# Seed data demo (opsional)
php artisan db:seed --class=DemoDataSeeder

# Seed admin user
php artisan db:seed --class=AdminUserSeeder
```

### Opsi 2: MySQL

```bash
# Buat database MySQL
mysql -u root -p
CREATE DATABASE certmanager;
EXIT;

# Update .env dengan MySQL credentials
# Kemudian jalankan migrations
php artisan migrate:fresh --seed
```

### Data Seeding

**Admin User Credentials** (setelah seeding):
```
Email    : admin@certmanager.local
Password : password
```

**Demo Data** includes:
- 5 Departments (HR, Engineering, Operations, Sales, IT)
- 10 Employees dengan berbagai department
- 5 Certificate Types (Fire Safety, First Aid, etc.)
- 20-40 Certificates dengan status bervariasi

## 🏃 Menjalankan Aplikasi

### Development Mode

**Opsi 1: Composer Script (Recommended)**
```bash
# Jalankan semua services sekaligus (server + queue + logs + vite)
composer run dev
```

Ini akan menjalankan:
- ✅ Laravel development server (`http://127.0.0.1:8000`)
- ✅ Queue worker untuk background jobs
- ✅ Real-time log monitoring (Pail)
- ✅ Vite dev server dengan HMR

**Opsi 2: Manual (Terminal Terpisah)**

Terminal 1 - Laravel Server:
```bash
php artisan serve
```

Terminal 2 - Frontend (Vite):
```bash
npm run dev
```

Terminal 3 - Queue Worker (Optional):
```bash
php artisan queue:listen
```

Terminal 4 - Logs (Optional):
```bash
php artisan pail
```

### Production Build

```bash
# Build frontend assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start server (gunakan web server seperti Nginx/Apache)
php artisan serve --host=0.0.0.0 --port=8000
```

## 💻 Development Commands

### Testing

```bash
# Run PHPUnit tests
composer run test
# atau
php artisan test

# Run with coverage
php artisan test --coverage
```

### Database

```bash
# Fresh migration
php artisan migrate:fresh

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Refresh autoloader
composer dump-autoload

# Seed specific seeder
php artisan db:seed --class=DemoDataSeeder
php artisan db:seed --class=AdminUserSeeder
```

### Code Quality

```bash
# Format code dengan Laravel Pint
./vendor/bin/pint

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Container Management

```bash
# Health check all containers
php artisan containers:health-check

# Check specific employee
php artisan containers:health-check --employee=EMP001

# Auto-repair issues
php artisan containers:health-check --repair

# Generate report
php artisan containers:health-check --report=report.txt

# Bulk operations
php artisan containers:bulk-operations create   # Create missing
php artisan containers:bulk-operations repair   # Repair all
php artisan containers:bulk-operations cleanup  # Cleanup orphaned
php artisan containers:bulk-operations migrate  # Migrate structure
```

### MPGA Import

```bash
# Import MPGA training data
php artisan mpga:import file.xlsx

# Update existing certificates
php artisan mpga:import file.xlsx --update-existing

# Create new certificate types
php artisan mpga:import file.xlsx --create-types
```

## 📁 Struktur Project

```
certmanager/
├── app/
│   ├── Console/Commands/          # Artisan commands
│   ├── Exports/                   # Excel export classes
│   │   └── ContainersExport.php
│   ├── Http/Controllers/          # Controllers
│   │   ├── DashboardController.php
│   │   ├── EmployeeContainerController.php
│   │   ├── TrainingTypeController.php
│   │   └── ...
│   ├── Models/                    # Eloquent models
│   │   ├── Employee.php
│   │   ├── EmployeeCertificate.php
│   │   ├── CertificateType.php
│   │   └── ...
│   └── ...
│
├── database/
│   ├── migrations/                # Database migrations
│   └── seeders/                   # Database seeders
│       ├── DemoDataSeeder.php
│       ├── AdminUserSeeder.php
│       └── DatabaseSeeder.php
│
├── resources/
│   └── js/
│       ├── Components/            # React components
│       │   ├── UI/               # Reusable UI components
│       │   │   ├── LoadingButton.jsx
│       │   │   ├── EmptyState.jsx
│       │   │   ├── ConfirmModal.jsx
│       │   │   └── FormInput.jsx
│       │   └── ...
│       ├── Layouts/              # Layout components
│       │   ├── AuthenticatedLayout.jsx
│       │   ├── GuestLayout.jsx
│       │   └── Sidebar.jsx
│       └── Pages/                # Page components
│           ├── Dashboard/
│           │   └── Index.jsx     # Main dashboard
│           ├── EmployeeContainers/
│           │   ├── Index.jsx
│           │   ├── Show.jsx
│           │   └── ...
│           ├── TrainingTypes/
│           │   ├── Index.jsx
│           │   ├── Create.jsx
│           │   ├── Edit.jsx
│           │   └── Analytics.jsx
│           ├── SDM/              # Employee master data
│           └── ...
│
├── routes/
│   └── web.php                   # Web routes
│
├── storage/
│   └── app/
│       └── private/              # Secure file storage
│           └── containers/       # Employee containers
│
├── tests/                        # PHPUnit tests
│
├── .env.example                  # Environment template
├── CLAUDE.md                     # Claude Code instructions
├── composer.json                 # PHP dependencies
├── package.json                  # Node dependencies
└── README.md                     # This file
```

## 📖 Panduan Penggunaan

### 1. Login

Akses `http://127.0.0.1:8000` dan login dengan:
- **Email**: `admin@certmanager.local`
- **Password**: `password`

### 2. Dashboard

Setelah login, Anda akan melihat:
- **Statistics Cards**: Total employees, certificates, status overview
- **Charts**: Visual analytics (Pie, Bar, Line)
- **Recent Activities**: Latest certificate updates
- **Quick Actions**: Shortcuts ke fitur utama

### 3. Employee Containers

**Sidebar → Employee Containers**

- **View All**: Lihat semua employee containers
- **Search**: Filter by name, NIK, department
- **Create**: Add new employee container
- **Details**: Klik container untuk melihat detail
  - Background check files
  - Certificates list dengan status
  - Upload/download files
  - Add new certificate

### 4. Training Types

**Sidebar → Training Types**

- **View All**: List semua jenis training
- **Create**: Tambah training type baru
- **Edit**: Update training details
- **Analytics**: Lihat analytics per training type
  - Certificate statistics
  - Department distribution
  - Compliance rate

### 5. SDM Module

**Sidebar → SDM (Employee Master)**

- **CRUD Operations**: Create, Read, Update, Delete employees
- **Import Excel**: Bulk import employee data
- **Export Excel**: Download employee list
- **Bulk Operations**: Mass updates

### 6. Departments

**Sidebar → Departments**

- **Manage Departments**: Create/edit departments
- **Training Requirements**: Set required trainings per dept
- **Analytics**: View department statistics

### 7. Export & Reports

**Features:**
- **Container Export**: Export all containers to Excel
- **Compliance Report**: Generate compliance report
- **Certificate Analytics**: Export analytics data
- **Custom Filters**: Filter by department, status, date range

## 🔧 Troubleshooting

### Error: "vendor/autoload.php not found"

```bash
composer install
```

### Error: "Target class [Seeder] does not exist"

```bash
composer dump-autoload
```

### Error: "Column not found: issued_by"

Update repository dan jalankan fresh migration:
```bash
git pull
php artisan migrate:fresh --seed
```

### Error: "SQLSTATE[HY000]: General error: 1 no such table"

```bash
php artisan migrate:fresh
php artisan db:seed
```

### Frontend tidak ter-update

```bash
npm run build
# atau untuk dev mode
npm run dev
```

### Permission denied untuk storage

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Queue jobs tidak berjalan

```bash
php artisan queue:restart
php artisan queue:listen
```

## 🏗 Roadmap Development (Completed)

- ✅ **DAY 1**: Critical bug fixes
  - Fixed TrainingTypes/Edit.jsx
  - Created Analytics page
  - Implemented export & reporting

- ✅ **DAY 2**: Dashboard & Analytics
  - Complete dashboard with charts
  - Real-time statistics
  - Recent activities feed

- ✅ **DAY 3**: UI Polish
  - Reusable component library
  - LoadingButton, EmptyState, ConfirmModal, FormInput
  - Consistent design system

- ✅ **DAY 4**: Testing & Demo Data
  - EmptyState integration
  - DemoDataSeeder with realistic data
  - AdminUserSeeder

- ✅ **DAY 5**: Documentation
  - Comprehensive README
  - Setup instructions
  - Troubleshooting guide

## 🎯 Key Features Detail

### Employee Container Concept

Setiap karyawan memiliki "container" digital yang berisi:

```
Container/
├── Background Checks/
│   ├── KTP.pdf
│   ├── SKCK.pdf
│   └── Medical_Certificate.pdf
│
└── Certificates/
    ├── Fire_Safety_2024.pdf
    ├── First_Aid_2023.pdf
    └── IT_Security_2024.pdf
```

**Status Tracking:**
- 🟢 **Active**: Certificate masih valid
- 🟡 **Expiring Soon**: < 30 hari sebelum expired
- 🔴 **Expired**: Sudah melewati expiry date

### Certificate Lifecycle

```
Issue → Active → Expiring Soon → Expired → Renewal
```

Sistem automatically:
1. Update status based on expiry date
2. Send notification H-30 (expiring soon)
3. Mark as expired setelah expiry date
4. Generate compliance reports

### File Storage Security

- ✅ Private storage (tidak accessible via URL)
- ✅ Authenticated access only
- ✅ Container-based organization
- ✅ File validation (type, size)
- ✅ Secure download with tokens

## 📝 API Endpoints

### Dashboard
```
GET  /dashboard              - Main dashboard
GET  /api/dashboard/stats    - Statistics API
```

### Employee Containers
```
GET    /employee-containers              - List all
POST   /employee-containers              - Create new
GET    /employee-containers/{id}         - Show details
PUT    /employee-containers/{id}         - Update
DELETE /employee-containers/{id}         - Delete
GET    /employee-containers/export       - Export Excel
GET    /employee-containers/compliance   - Compliance report
```

### Training Types
```
GET    /training-types                   - List all
POST   /training-types                   - Create new
GET    /training-types/{id}/edit         - Edit form
PUT    /training-types/{id}              - Update
DELETE /training-types/{id}              - Delete
GET    /training-types/{id}/analytics    - Analytics
```

### Certificates
```
POST   /employee-containers/{id}/certificates        - Add certificate
DELETE /employee-containers/{id}/certificates/{cert} - Delete certificate
```

### Files
```
GET    /files/background-check/{id}/{index}  - Download background check
GET    /files/certificate/{id}               - Download certificate
POST   /employee-containers/{id}/upload      - Upload file
```

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License.

## 👥 Credits

**Built with:**
- [Laravel](https://laravel.com) - The PHP Framework for Web Artisans
- [React](https://react.dev) - The library for web and native user interfaces
- [Inertia.js](https://inertiajs.com) - The Modern Monolith
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [Recharts](https://recharts.org) - Redefined chart library built with React

**Developed by:** CertManager Development Team

---

<p align="center">Made with ❤️ for better HR management</p>
