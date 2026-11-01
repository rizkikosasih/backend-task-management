# Kontrak API: Komentar Tugas (Comments)

Menangani thread diskusi terikat pada suatu tugas, serta aturan penghapusan khusus oleh pemilik komentar.  

---

## 1. Daftar Komentar Tugas

Mengambil daftar seluruh komentar di bawah tugas spesifik secara terurut.

- **Metode:** `GET`
- **Rute:** `/api/tasks/{task}/comments`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `view comment`

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Comments retrieved successfully",
  "data": [
    {
      "id": 1,
      "task_id": 1,
      "user_id": 3,
      "message": "Sertifikat TLS telah diterbitkan oleh Let's Encrypt.",
      "created_at": "2026-11-01T09:00:00.000000Z"
    }
  ],
  "meta": null
}
```

---

## 2. Penambahan Komentar

Menuliskan komentar baru pada tugas.

- **Metode:** `POST`
- **Rute:** `/api/tasks/{task}/comments`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `create comment`

### Payload Permintaan
```json
{
  "message": "Konfigurasi reverse proxy selesai diuji coba."
}
```

### Respon Sukses (201 Created)
```json
{
  "success": true,
  "message": "Comment created successfully",
  "data": {
    "id": 2,
    "task_id": 1,
    "user_id": 3,
    "message": "Konfigurasi reverse proxy selesai diuji coba.",
    "created_at": "2026-11-01T09:15:00.000000Z"
  },
  "meta": null
}
```

---

## 3. Penghapusan Komentar

Menghapus komentar secara soft delete.

- **Metode:** `DELETE`
- **Rute:** `/api/comments/{comment}`
- **Autentikasi:** Ya (`auth:sanctum`)
- **Izin Diperlukan:** `delete comment`
- **Otorisasi ABAC:** Hanya pemilik sah pembuat komentar tersebut (`comment.user_id === user.id`)

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Comment deleted successfully",
  "data": null,
  "meta": null
}
```
