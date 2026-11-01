# Kontrak API: Manajemen Tugas (Tasks)

Menangani pencatatan tugas, alokasi anggota, transisi status dengan kontrol konkurensi, dan delegasi pekerjaan.  

---

## 1. Daftar Tugas

Mengambil daftar tugas berdasarkan filter status, proyek, atau penerima pekerjaan.

- **Metode:** `GET`
- **Rute:** `/api/tasks`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `view task`
- **Parameter Kueri:**
  - `page` (integer, opsional, default: 1)
  - `per_page` (integer, opsional, default: 10)
  - `search` (string, opsional)
  - `status` (string, opsional: `todo`, `in_progress`, `done`)
  - `project_id` (integer, opsional)
  - `assigned_to` (integer, opsional)

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": [
    {
      "id": 1,
      "project_id": 1,
      "title": "Konfigurasi SSL Endpoint",
      "description": "Pemasangan sertifikat TLS pada gateway",
      "status": "todo",
      "assigned_to": 3,
      "due_date": "2026-11-15",
      "created_at": "2026-11-01T08:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1,
    "has_next_page": false
  }
}
```

---

## 2. Detail Tugas

Mengambil data lengkap satu entitas tugas.

- **Metode:** `GET`
- **Rute:** `/api/tasks/{task}`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Otorisasi ABAC:** Admin bebas akses; Member hanya berhak melihat tugas yang ditugaskan ke dirinya (`task.assigned_to === user.id`).

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Task retrieved successfully",
  "data": {
    "id": 1,
    "project_id": 1,
    "title": "Konfigurasi SSL Endpoint",
    "description": "Pemasangan sertifikat TLS pada gateway",
    "status": "todo",
    "assigned_to": 3,
    "due_date": "2026-11-15",
    "created_at": "2026-11-01T08:00:00.000000Z"
  },
  "meta": null
}
```

---

## 3. Pembuatan Tugas Baru

Menambahkan tugas di bawah proyek yang ditentukan.

- **Metode:** `POST`
- **Rute:** `/api/tasks`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `create task`
- **Otorisasi ABAC:** Hanya pemilik inisiator proyek (`project.created_by === user.id`)
- **Header Khusus:** `X-Idempotency-Key` (UUID, direkomendasikan)

### Payload Permintaan
```json
{
  "project_id": 1,
  "title": "Konfigurasi SSL Endpoint",
  "description": "Pemasangan sertifikat TLS pada gateway",
  "assigned_to": 3,
  "due_date": "2026-11-15"
}
```

### Respon Sukses (201 Created)
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {
    "id": 1,
    "project_id": 1,
    "title": "Konfigurasi SSL Endpoint",
    "description": "Pemasangan sertifikat TLS pada gateway",
    "status": "todo",
    "assigned_to": 3,
    "due_date": "2026-11-15",
    "created_at": "2026-11-01T08:00:00.000000Z"
  },
  "meta": null
}
```

---

## 4. Pengubahan Rincian Tugas

Mengubah informasi judul, deskripsi, penerima tugas, atau tenggat waktu.

- **Metode:** `PUT`
- **Rute:** `/api/tasks/{task}`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `update task detail`
- **Otorisasi ABAC:** Hanya pemilik inisiator proyek induk (`task.project.created_by === user.id`)

### Payload Permintaan
```json
{
  "title": "Konfigurasi SSL & TLS 1.3",
  "description": "Penegakan protokol TLS minimal versi 1.3",
  "assigned_to": 3,
  "due_date": "2026-11-20"
}
```

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Task updated successfully",
  "data": {
    "id": 1,
    "title": "Konfigurasi SSL & TLS 1.3",
    "status": "todo"
  },
  "meta": null
}
```

---

## 5. Perubahan Status Tugas (Pessimistic Locking)

Mengubah status alur kerja tugas (`todo`, `in_progress`, `done`) dengan penguncian baris data database.

- **Metode:** `PATCH`
- **Rute:** `/api/tasks/{task}/status`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `update task status`
- **Otorisasi ABAC:** Pemilik proyek induk ATAU pengguna penerima tugas terkait (`user.id === task.assigned_to`)

### Payload Permintaan
```json
{
  "status": "in_progress"
}
```

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Task status updated successfully",
  "data": {
    "id": 1,
    "title": "Konfigurasi SSL & TLS 1.3",
    "status": "in_progress"
  },
  "meta": null
}
```

---

## 6. Penghapusan Tugas

Menghapus entitas tugas secara soft delete.

- **Metode:** `DELETE`
- **Rute:** `/api/tasks/{task}`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `delete task`
- **Otorisasi ABAC:** Hanya pemilik inisiator proyek induk (`task.project.created_by === user.id`)

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Task deleted successfully",
  "data": null,
  "meta": null
}
```
