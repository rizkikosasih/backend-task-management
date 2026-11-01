# Task Management API

![Task Management API](.github/assets/og-image.png)

Sistem manajemen tugas (Task Management) berbasis RESTful API yang dibangun dengan fokus pada isolasi data (**Data Isolation**), kontrol akses granular (**RBAC & ABAC**), idempotensi transaksi jaringan, dan konkurensi data menggunakan **Domain Service Layer**.

---

## Tech Stack

- **Framework**: Laravel 12
- **Database**: SQLite (Lokal & Testing), MySQL (Produksi & Docker)
- **Authentication**: Laravel Sanctum & Laravel Socialite (OAuth2)
- **Authorization**: Spatie Laravel Permission & Laravel Policy
- **Architecture**: Domain Service Layer & Eloquent ORM
- Dev Tools:
  - Laravel Pint: Standar formatting kode dan strict typing PHP.
  - Larastan (PHPStan): Static analysis level 5.
  - Scramble: OpenAPI 3.0 documentation generator (/docs/api).

---

## Installation & Setup

- Clone & Dependencies

```bash
git clone https://github.com/rizkikosasih/backend-task-management.git nama-folder

cd nama-folder

composer run setup:local
```

- Environment & Keys

```bash
cp .env.example .env

php artisan key:generate
```

- Database & Security Seeder

```bash
php artisan migrate --seed
```

- IDE Optimization (Optional)

```bash
php artisan ide-helper:generate php artisan ide-helper:meta
```

- Run Application

```bash
php artisan serve
```

- Run Automated Tests

```bash
php artisan test
```

---

## Fitur Utama

### 1. Authentication

Endpoint:

```
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET /api/auth/me
```

Fungsi:

- registrasi user
- login menghasilkan **API token**
- logout revoke token
- melihat profil user

---

### 2. RBAC System

Role:

```
admin
member
```

Daftar Permission:

```
view project
create project
update project
delete project
view task
create task
update task detail
update task status
delete task
view comment
create comment
delete comment
```

---

#### **Matrix Otorisasi & Kontrol Akses (RBAC & ABAC)**

Sistem ini menerapkan kombinasi **Role-Based Access Control (RBAC)** untuk izin fitur secara global dan **Attribute-Based Access Control (ABAC)** untuk validasi kepemilikan data (Ownership).

| **Resource** | **Action**        | **Admin** | **Member** | **Penjelasan Logic**                                                       |
| ------------ | ----------------- | --------- | ---------- | -------------------------------------------------------------------------- |
| **Project**  | **Create**        | ✅        | ❌         | Semua Admin bisa buat Project.                                             |
|              | **View Detail**   | ✅        | ✅         | Admin bebas lihat. Member harus terlibat (punya task) di Project tersebut. |
|              | **Update/Delete** | ✅\*      | ❌         | \*Hanya Admin pembuat Project (**Owner**).                                 |
| **Task**     | **View List**     | ✅        | ✅         | Admin bebas lihat semua. Member hanya lihat task yang di-assign ke dia.    |
|              | **View Detail**   | ✅        | ✅         | Admin bebas lihat semua. Member hanya bisa lihat task miliknya.            |
|              | **Create**        | ✅\*      | ❌         | \*Hanya Admin pembuat Project (**Owner**).                                 |
|              | **Update Detail** | ✅\*      | ❌         | \*Hanya Admin pembuat Project (**Owner**).                                 |
|              | **Update Status** | ✅\*      | ✅         | \*Admin Owner ATAU Member yang di-assign ke task tersebut.                 |
|              | **Delete**        | ✅\*      | ❌         | \*Hanya Admin pembuat Project (**Owner**).                                 |
| **Comment**  | **View List**     | ✅        | ✅         | Terbuka bagi siapa saja yang punya akses ke Task tersebut.                 |
|              | **Create**        | ✅        | ✅         | Admin/Member yang terlibat di Task boleh kasih komentar.                   |
|              | **Delete**        | ✅\*      | ✅\*       | \*Hanya pemilik komentar (**Owner**) yang boleh hapus komennya.            |

---

### 3. Project Management

Endpoint:

```
GET /api/projects
POST /api/projects
GET /api/projects/{project}
PUT /api/projects/{project}
DELETE /api/projects/{project}
```

Field project:

```
id
name
description
created_by
created_at
updated_at
deleted_at
```

---

### 4. Task Management

Endpoint:

```
GET /api/tasks
POST /api/tasks
GET /api/tasks/{task}
PUT /api/tasks/{task}
DELETE /api/tasks/{task}
PATCH /api/tasks/{task}/status
```

Field task:

```
id
project_id
title
description
status
assigned_to
due_date
created_at
updated_at
deleted_at
```

Status task:

```
todo
in_progress
done
```

---

### 5. Comment System

Endpoint:

```
GET /api/tasks/{task}/comments
POST /api/tasks/{task}/comments
DELETE /api/comments/{comment}
```

Field:

```
id
task_id
user_id
message
created_at
updated_at
deleted_at
```

---

### 6. Pagination & Filtering

Contoh:

```
GET /api/projects?page=1&per_page=10&search=qwerty
GET /api/tasks?status=todo&project_id=1&assigned_to=1&search=test&page=1&per_page=10
```

---

### 7. Technical Excellence

- **Domain Service Pattern**: Memusatkan logika bisnis pada kelas service konkret yang berinteraksi langsung dengan model Eloquent tanpa lapisan abstraksi berlebih.
- **Policy-Driven Authorization**: Menggunakan Laravel Policy secara menyeluruh untuk menangani otorisasi granular (RBAC dan ABAC).
- **Idempotency & Concurrency**: Proteksi duplikasi mutasi melalui Idempotency Key Middleware dan pencegahan race condition status tugas melalui Pessimistic Locking.
- **Conventional Commits**: Menggunakan standar pesan commit terstruktur untuk riwayat pengembangan profesional.

---

## Dokumentasi Arsitektur & Spesifikasi

- [Product Requirement Document (PRD)](PRD.md)
- [Master Architecture Decision Records (ADR)](docs/DECISION_LOG.md)
- [Entity Relationship Diagram & Skema Database](docs/database/ERD.md)
- [Standarisasi API & Penanganan Galat](docs/api/STANDARDIZATION.md)
- [Kontrak API Endpoint](docs/api/contracts/)
- [Kamus Domain Bisnis (Glossary)](docs/GLOSSARY.md)
- [Pelacakan Masalah Teknis (Known Issues)](docs/KNOWN_ISSUES.md)
- [Pelacakan Tugas (Tasks Backlog)](docs/TASKS.md)
- [Aktivitas Pengembang Harian (DEV_LOG)](DEV_LOG.md)

---

## Struktur Database

Lihat detail lengkap spesifikasi skema, relasi kardinalitas, dan indeks B-Tree pada [docs/database/ERD.md](docs/database/ERD.md).

---

## Struktur Folder

```
app/
 ├── Http/
 │    ├── Controllers/
 │    ├── Middleware/
 │    │     └── IdempotencyMiddleware.php
 │    ├── Requests/
 │    └── Resources/
 │
 ├── Services/
 │    ├── AuthService.php
 │    ├── OAuthService.php
 │    ├── ProjectService.php
 │    ├── TaskService.php
 │    └── CommentService.php
 │
 ├── Models/
 │     ├── User.php
 │     ├── Project.php
 │     ├── Task.php
 │     ├── Comment.php
 │     └── OAuthProvider.php
 │
 ├── Policies/
 │     ├── ProjectPolicy.php
 │     ├── TaskPolicy.php
 │     └── CommentPolicy.php
 │
 ├── Events/
 │     └── TaskAssigned.php
 ├── Listeners/
 │     └── SendTaskAssignedNotification.php
 ├── Enums/
 │     └── TaskStatus.php
 ├── Exceptions/
 │     ├── ApiExceptionTransformer.php
 │     └── BusinessException.php
 ├── Helpers/
 │     └── ApiResponse.php
 └── Providers/

tests/
 ├── Feature/
 ├── Unit/
 └── api/
       ├── auth.http
       ├── comment.http
       ├── project.http
       └── task.http
```
