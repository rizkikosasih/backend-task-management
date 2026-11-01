# Kontrak API: Manajemen Proyek (Projects)

Menangani siklus hidup entitas proyek, isolasi kepemilikan data, dan pencarian terpaginasi.  

---

## 1. Daftar Proyek

Mengambil daftar proyek dengan filter pencarian dan pembagian halaman.

- **Metode:** `GET`
- **Rute:** `/api/projects`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `view project`
- **Parameter Kueri:**
  - `page` (integer, opsional, default: 1)
  - `per_page` (integer, opsional, default: 10)
  - `search` (string, opsional)

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Platform Integrasi API",
      "description": "Proyek pembangunan gateway komunikasi terpadu",
      "created_by": 2,
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

## 2. Detail Proyek

Mengambil informasi rinci proyek spesifik berdasarkan identitas pengenal.

- **Metode:** `GET`
- **Rute:** `/api/projects/{project}`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Otorisasi ABAC:** Admin bebas akses; Member wajib terdaftar pada tugas proyek tersebut.

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Project retrieved successfully",
  "data": {
    "id": 1,
    "name": "Platform Integrasi API",
    "description": "Proyek pembangunan gateway komunikasi terpadu",
    "created_by": 2,
    "created_at": "2026-11-01T08:00:00.000000Z"
  },
  "meta": null
}
```

---

## 3. Pembuatan Proyek Baru

Membuat entitas proyek baru di bawah akun Admin inisiator.

- **Metode:** `POST`
- **Rute:** `/api/projects`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `create project`
- **Header Khusus:** `X-Idempotency-Key` (UUID, direkomendasikan)

### Payload Permintaan
```json
{
  "name": "Platform Integrasi API",
  "description": "Proyek pembangunan gateway komunikasi terpadu"
}
```

### Respon Sukses (201 Created)
```json
{
  "success": true,
  "message": "Project created successfully",
  "data": {
    "id": 1,
    "name": "Platform Integrasi API",
    "description": "Proyek pembangunan gateway komunikasi terpadu",
    "created_by": 2,
    "created_at": "2026-11-01T08:00:00.000000Z"
  },
  "meta": null
}
```

---

## 4. Pengubahan Informasi Proyek

Mengubah atribut nama atau deskripsi proyek yang ada.

- **Metode:** `PUT`
- **Rute:** `/api/projects/{project}`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `update project`
- **Otorisasi ABAC:** Hanya Admin pemilik inisiator (`created_by === user.id`)

### Payload Permintaan
```json
{
  "name": "Platform Integrasi API V2",
  "description": "Pembaruan arsitektur gateway komunikasi"
}
```

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Project updated successfully",
  "data": {
    "id": 1,
    "name": "Platform Integrasi API V2",
    "description": "Pembaruan arsitektur gateway komunikasi",
    "created_by": 2,
    "created_at": "2026-11-01T08:00:00.000000Z"
  },
  "meta": null
}
```

---

## 5. Penghapusan Proyek

Menghapus entitas proyek secara soft delete beserta tugas di dalamnya.

- **Metode:** `DELETE`
- **Rute:** `/api/projects/{project}`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `delete project`
- **Otorisasi ABAC:** Hanya Admin pemilik inisiator (`created_by === user.id`)

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Project deleted successfully",
  "data": null,
  "meta": null
}
```
