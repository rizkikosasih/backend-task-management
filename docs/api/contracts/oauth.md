# Kontrak API: Autentikasi OAuth2 (Socialite)

Menangani alur pengalihan, panggilan balik (*callback*), dan penautan akun pihak ketiga.  

---

## 1. Pengalihan Penyedia OAuth

Mengarahkan klien ke halaman otorisasi penyedia pihak ketiga (`google` atau `github`).

- **Metode:** `GET`
- **Rute:** `/api/oauth/{provider}/redirect`
- **Parameter Rute:** `provider` (`google` atau `github`)
- **Autentikasi:** Tidak
- **Pembatasan Frekuensi:** 10 permintaan per menit

### Respon Sukses (302 Redirect / JSON URL)
Mengarahkan peramban secara otomatis ke URL konsen penyedia terkait.

---

## 2. Callback Otorisasi OAuth

Menangani respon pengalihan dari penyedia identitas dengan kode otorisasi, melakukan validasi profil, dan menautkan atau membuat entitas pengguna lokal.

- **Metode:** `GET`
- **Rute:** `/api/oauth/{provider}/callback`
- **Parameter Rute:** `provider` (`google` atau `github`)
- **Autentikasi:** Tidak

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "OAuth login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Budi Santoso",
      "email": "budi@gmail.com",
      "is_oauth_user": true
    },
    "token": "3|tokenSanctumOAuth..."
  },
  "meta": null
}
```

---

## 3. Penautan Penyedia Tambahan (Account Linking)

Menautkan akun pihak ketiga tambahan ke akun pengguna lokal yang sedang aktif login.

- **Metode:** `POST`
- **Rute:** `/api/oauth/{provider}/link`
- **Parameter Rute:** `provider` (`google` atau `github`)
- **Autentikasi:** Ya (`auth:sanctum`)

### Payload Permintaan
```json
{
  "provider_id": "gh_987654321",
  "email": "budi@gmail.com"
}
```

### Respon Sukses (200 OK)
```json
{
  "success": true,
  "message": "Provider linked successfully",
  "data": {
    "provider": "github",
    "connected_at": "2026-11-01T08:30:00.000000Z"
  },
  "meta": null
}
```
