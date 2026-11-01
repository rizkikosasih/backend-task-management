# Kontrak API: Autentikasi Lokal (Auth)

Menangani registrasi, sesi login, penarikan token, dan profil pengguna lokal.  

---

## 1. Registrasi Akun

Mendaftarkan akun pengguna baru ke dalam sistem dan memberikan peran bawaan `member`.

- **Metode:** `POST`
- **Rute:** `/api/auth/register`
- **Autentikasi:** Tidak

### Header Permintaan
```http
Accept: application/json
Content-Type: application/json
```

### Payload Permintaan
```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

### Respon Sukses (201 Created)
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Budi Santoso",
      "email": "budi@example.com"
    },
    "token": "1|rahasiaTokenSanctum..."
  },
  "meta": null
}
```

---

## 2. Login Pengguna

Memvalidasi kredensial pengguna dan menerbitkan token otentikasi Sanctum baru.

- **Metode:** `POST`
- **Rute:** `/api/auth/login`
- **Autentikasi:** Tidak

### Payload Permintaan
```json
{
  "email": "budi@example.com",
  "password": "Password123!"
}
```

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Budi Santoso",
      "email": "budi@example.com"
    },
    "token": "2|tokenBaruSanctum..."
  },
  "meta": null
}
```

---

## 3. Profil Pengguna Aktif

Mengambil informasi akun pengguna yang sedang terotentikasi beserta perannya.

- **Metode:** `GET`
- **Rute:** `/api/auth/me`
- **Autentikasi:** Ya (`auth:sanctum`)

### Header Permintaan
```http
Accept: application/json
Authorization: Bearer 2|tokenBaruSanctum...
```

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "User profile retrieved successfully",
  "data": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "roles": [
      "member"
    ]
  },
  "meta": null
}
```

---

## 4. Logout Pengguna

Mencabut dan menghapus token akses aktif saat ini.

- **Metode:** `POST`
- **Rute:** `/api/auth/logout`
- **Autentikasi:** Ya (`auth:sanctum`)

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null,
  "meta": null
}
```
