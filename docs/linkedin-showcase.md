# LinkedIn Showcase - Task Management API

Dokumen ini berisi materi siap pakai untuk publikasi portofolio atau postingan LinkedIn Showcase untuk proyek Task Management API.

---

## Metadata Portofolio LinkedIn

- **Judul Proyek**: Task Management API — RESTful API dengan RBAC & ABAC (Laravel 12)
- **Kategori Proyek**: Backend Development / API Design
- **URL Repositori**: `https://github.com/rizkikosasih/backend-task-management`
- **Keahlian Terkait (Skills)**:
  - Laravel Framework (v12)
  - RESTful API Design
  - Laravel Sanctum (Token-Based Authentication)
  - Role-Based Access Control (Spatie Laravel Permission)
  - Attribute-Based Access Control (Laravel Policy)
  - Service-Repository Pattern
  - MySQL Database Design
  - Automated Testing (Feature & Unit Test)

---

## LinkedIn Post Copy (Siap Bagikan)

```markdown
[PROJECT SHOWCASE] Task Management API: RESTful API dengan RBAC & ABAC

Menyelesaikan proyek Task Management API menggunakan Laravel 12, dibangun dengan fokus pada Data Isolation, kontrol akses granular, dan Clean Architecture melalui Service-Repository Pattern.

Fitur utama:
1. Authentication (Sanctum): Register, login menghasilkan API token, logout dengan token revocation, endpoint profil user.
2. Kombinasi RBAC & ABAC: Role Admin/Member menentukan izin fitur secara global (RBAC), sementara validasi kepemilikan data (ABAC) menentukan akses granular per resource — contoh: Member hanya bisa update status task yang di-assign ke dirinya, hanya Admin pembuat project (Owner) yang bisa create/update/delete task pada project tersebut.
3. Project & Task Management: CRUD lengkap untuk Project dan Task, dengan status task (todo, in_progress, done) dan assignment ke user.
4. Comment System: Setiap task punya thread komentar, hanya pemilik komentar yang bisa menghapus miliknya sendiri.
5. Pagination & Filtering: Query parameter untuk search, filter berdasarkan status/project/assigned_to, dan pagination pada seluruh endpoint list.
6. Service-Repository Pattern: Business logic dipisah dari akses database (Service layer) dan query database dipisah dari domain logic (Repository layer dengan interface contract), mempermudah unit testing.
7. Policy-Driven Authorization: Seluruh otorisasi granular ditangani melalui Laravel Policy, bukan pengecekan role manual di controller.

Stack Teknologi: Laravel 12, MySQL, Laravel Sanctum, Spatie Laravel Permission, Prettier (PHP & Blade formatting), Laravel IDE Helper.

Lihat Repositori & Dokumentasi Lengkap:
[LINK GITHUB TASK MANAGEMENT API]

#Laravel #API #BackendDevelopment #RBAC #SoftwareArchitecture #PHP
```

---

## Gambar/Media Pendukung yang Disarankan

1. Diagram matrix otorisasi RBAC & ABAC (tabel Resource/Action/Admin/Member dari README).
2. Screenshot response API (Postman/Insomnia) untuk endpoint task dengan filter dan pagination aktif.
3. Cuplikan struktur folder `app/Services/` dan `app/Repositories/` sebagai bukti penerapan Service-Repository Pattern.

---

## Tips Pengunggahan

1. Proyek ini murni backend/API tanpa antarmuka visual — gunakan dokumentasi endpoint (Postman collection atau file `.http` pada folder `tests/api/`) sebagai pengganti demo visual, bukan URL live deployment.
2. Cantumkan matrix otorisasi RBAC & ABAC pada media pendukung post — ini pembeda teknis utama proyek ini dibanding showcase Laravel lain (CRM WhatsApp Integration).
3. Posisikan di bagian Accomplishments > Projects pada profil LinkedIn, kaitkan dengan pengalaman kerja backend yang relevan.
