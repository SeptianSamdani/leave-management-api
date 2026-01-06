# Leave Management API

RESTful API untuk sistem manajemen cuti karyawan dengan Laravel dan PostgreSQL.

## Fitur Utama

- Authentication (Conventional & OAuth Google)
- Role-based Authorization (Employee & Admin)
- Manajemen Cuti dengan validasi kuota
- File upload untuk bukti pendukung
- Workflow approval (Pending → Approved/Rejected)

## Tech Stack

- Laravel 11
- MySQL
- Laravel Sanctum (API Authentication)
- Laravel Socialite (OAuth)

## Instalasi

### Prerequisites

- PHP 8.2+
- Composer
- MySQL
- Git

### Setup Project

```bash
# Clone repository
git clone <repository-url>
cd leave-management-api

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Konfigurasi .env

```env
APP_NAME="Leave Management API"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=leave_management
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

### Database Setup

```bash
# Create database
createdb leave_management

# Run migrations
php artisan migrate

# (Optional) Seed data untuk testing
php artisan db:seed --class=UserSeeder
```

### Storage Setup

```bash
# Create symbolic link untuk file storage
php artisan storage:link
```

### Menjalankan Aplikasi

```bash
php artisan serve
```

API akan berjalan di `http://localhost:8000`

## Struktur Database

### Table: users
- id, name, email, password
- role (enum: employee, admin)
- provider, provider_id, avatar (untuk OAuth)

### Table: leave_quotas
- user_id, year
- total (default: 12), used, remaining

### Table: leave_requests
- user_id, start_date, end_date, total_days
- reason, attachment, status (enum: pending, approved, rejected)
- approved_by, approved_at, admin_notes

## Arsitektur Sistem

### Clean Architecture Pattern

```
├── Controllers (HTTP Layer)
│   ├── AuthController
│   ├── OAuthController
│   ├── LeaveRequestController (Employee)
│   └── AdminLeaveController (Admin)
│
├── Services (Business Logic)
│   └── LeaveService
│       ├── createLeaveRequest()
│       ├── approveLeaveRequest()
│       ├── rejectLeaveRequest()
│       └── calculateWorkingDays()
│
├── Models (Data Layer)
│   ├── User
│   ├── LeaveQuota
│   └── LeaveRequest
│
└── Middleware
    └── RoleMiddleware (Authorization)
```

### Flow Sistem

**1. Authentication Flow**

```
User → Register/Login → Get Token → Access Protected Routes
User → OAuth Google → Callback → Auto Create Account → Get Token
```

**2. Leave Request Flow (Employee)**

```
Employee Submit Cuti
  ↓
Validasi Data & Upload File
  ↓
Hitung Working Days (exclude weekend)
  ↓
Check Kuota & Overlapping
  ↓
Status: Pending
```

**3. Approval Flow (Admin)**

```
Admin View All Requests
  ↓
Admin Approve/Reject
  ↓
If Approved: Deduct Quota
  ↓
Status: Approved/Rejected
```

### Business Logic

**1. Validasi Kuota**
- Setiap employee memiliki 12 hari cuti per tahun
- Saat approve, sistem otomatis mengurangi kuota
- Jika cancel approved leave, kuota dikembalikan

**2. Perhitungan Hari**
- Hanya menghitung hari kerja (Senin-Jumat)
- Weekend tidak dihitung sebagai cuti

**3. Validasi Overlap**
- Sistem mencegah pengajuan cuti yang overlap dengan cuti existing (pending/approved)

**4. File Upload**
- Attachment wajib diupload saat pengajuan
- Format: PDF, JPG, JPEG, PNG (max 2MB)
- Disimpan di `storage/app/public/leave-attachments`

## API Endpoints

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | /api/auth/register | Register user baru | No |
| POST | /api/auth/login | Login | No |
| GET | /api/auth/google | Redirect ke Google OAuth | No |
| GET | /api/auth/google/callback | Callback OAuth | No |
| GET | /api/auth/me | Get user profile | Yes |
| POST | /api/auth/logout | Logout | Yes |

### Employee Endpoints

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | /api/employee/leave-requests | List cuti saya | Employee |
| POST | /api/employee/leave-requests | Ajukan cuti | Employee |
| GET | /api/employee/leave-requests/{id} | Detail cuti | Employee |
| DELETE | /api/employee/leave-requests/{id} | Cancel cuti | Employee |
| GET | /api/employee/leave-statistics | Statistik kuota | Employee |

### Admin Endpoints

| Method | Endpoint | Description | Role |
|--------|----------|-------------|------|
| GET | /api/admin/leave-requests | List semua cuti | Admin |
| GET | /api/admin/leave-requests?status=pending | Filter by status | Admin |
| GET | /api/admin/leave-requests/{id} | Detail cuti | Admin |
| PATCH | /api/admin/leave-requests/{id}/approve | Approve cuti | Admin |
| PATCH | /api/admin/leave-requests/{id}/reject | Reject cuti | Admin |
| GET | /api/admin/dashboard | Dashboard statistics | Admin |

## Testing

### Credentials Default (Seeder)

**Admin:**
- Email: admin@example.com
- Password: password

**Employee:**
- Email: employee@example.com
- Password: password

### Postman Collection

Import file `Leave Management API.postman_collection.json` ke Postman.

Atau akses dokumentasi online:
[Link Postman Documentation]

### Testing Flow

1. Login sebagai employee
2. Copy token dari response
3. Set token di Postman environment variable
4. Test create leave request dengan file upload
5. Login sebagai admin
6. Approve/Reject leave request
7. Check kuota berkurang setelah approve

## Error Handling

API mengembalikan response JSON konsisten:

**Success Response:**
```json
{
    "success": true,
    "message": "Operation successful",
    "data": {}
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Error message",
    "errors": {}
}
```

## Security

- API menggunakan Laravel Sanctum untuk authentication
- Password di-hash menggunakan bcrypt
- File upload divalidasi (type & size)
- Role-based authorization menggunakan middleware
- CSRF protection untuk stateful requests

## Pengembangan Lebih Lanjut

Fitur yang bisa ditambahkan:
- Email notification saat status berubah
- Export data ke Excel/PDF
- Calendar view untuk cuti
- Multiple file attachments
- Leave history & audit log
- Integration dengan HR system

## Kontributor

Septian Samdani

## Lisensi

MIT License