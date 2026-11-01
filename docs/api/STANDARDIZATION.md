# API Standardization & Kontrak Komunikasi

Dokumen standar format komunikasi JSON, penanganan galat HTTP, konvensi header, dan paginasi data.  

---

## 1. Struktur Amplop Respon (Response Envelope)

Seluruh endpoint antarmuka wajib mengembalikan struktur JSON konsisten menggunakan pembungkus `ApiResponse`.

### 1.1 Respon Sukses Standar (200 OK / 201 Created)

```json
{
  "success": true,
  "message": "Project created successfully",
  "data": {
    "id": 1,
    "name": "Sistem Pembayaran",
    "description": "Modul integrasi gerbang pembayaran",
    "created_by": 2,
    "created_at": "2026-11-01T08:00:00.000000Z"
  },
  "meta": null
}
```

### 1.2 Respon Sukses Terpaginasi (Paginated)

```json
{
  "success": true,
  "message": "Data retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Setup OAuth2 Client",
      "status": "todo",
      "assigned_to": 3
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 48,
    "has_next_page": true
  }
}
```

### 1.3 Respon Galat Validasi (422 Unprocessable Entity)

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "title": [
      "The title field is required."
    ],
    "project_id": [
      "The selected project id is invalid."
    ]
  }
}
```

### 1.4 Respon Galat Otorisasi dan Penolakan Akses (403 Forbidden)

```json
{
  "success": false,
  "message": "You do not have the required permissions.",
  "errors": null
}
```

---

## 2. Pemetaan Kode Status HTTP

Penanganan galat terpusat dikelola oleh `ApiExceptionTransformer` dengan ketentuan kode status berikut:

| Kode Status | Pengecualian Internal | Keterangan |
| :--- | :--- | :--- |
| `200 OK` | - | Permintaan berhasil diproses |
| `201 Created` | - | Entitas baru berhasil dibuat |
| `401 Unauthorized` | `AuthenticationException` | Token tidak disertakan atau kedaluwarsa |
| `403 Forbidden` | `AuthorizationException`, `SpatieUnauthorizedException` | Pengguna tidak memiliki izin peran atau bukan pemilik data |
| `404 Not Found` | `ModelNotFoundException`, `NotFoundHttpException` | ID entitas tidak ditemukan di database atau rute tidak valid |
| `405 Method Not Allowed` | `MethodNotAllowedHttpException` | Metode HTTP tidak diizinkan pada rute terkait |
| `422 Unprocessable Entity` | `ValidationException`, `BusinessException` | Kegagalan validasi masukan atau pelanggaran aturan domain |
| `500 Server Error` | `QueryException`, `Throwable` | Kegagalan basis data atau pengecualian internal server |

---

## 3. Konvensi Header HTTP

Setiap klien yang mengonsumsi antarmuka API wajib mematuhi ketentuan header berikut:

| Header | Wajib | Nilai Contoh | Penjelasan |
| :--- | :--- | :--- | :--- |
| `Accept` | Ya | `application/json` | Menjamin respon selalu diformat sebagai JSON |
| `Content-Type` | Ya | `application/json` | Format payload permintaan |
| `Authorization` | Kondisional | `Bearer 1\|abc...` | Wajib untuk seluruh endpoint yang memerlukan sesi login |
| `X-Idempotency-Key` | Direkomendasikan | `b1e27a69-8d74-4b53-bc2a-bf351ddf8762` | Digunakan pada `POST /api/projects` dan `POST /api/tasks` untuk mencegah transaksi ganda |

---

## 4. Parameter Paginasi dan Penyaringan

Endpoint daftar data mendukung parameter kueri terstandarisasi:

- `page`: Nomor halaman aktif (default: `1`).
- `per_page`: Jumlah entitas per halaman (default: `10`, batas atas: `100`).
- `search`: Kueri pencarian teks bebas pada nama proyek atau judul tugas.
- `status`: Penyaringan status tugas (`todo`, `in_progress`, `done`).
- `project_id`: Penyaringan tugas berdasarkan kontainer proyek tertentu.
- `assigned_to`: Penyaringan tugas berdasarkan pengguna penerima pekerjaan.
