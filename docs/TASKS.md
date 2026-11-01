# Project Task Backlog (TASKS)

Hierarki Standar Pelacakan Tugas Proyek: Priority -> Sub-Poin -> Item Pekerjaan.  

---

## Priority 1

### 1. Keamanan & Pengendalian Akses Jaringan

- [ ] **Rate Limiting Dinamis per Pengguna:**
  Menerapkan pembatasan frekuensi permintaan berbasis ID pengguna terotentikasi pada seluruh rute mutasi API guna mencegah serangan brute-force dan penyalahgunaan sumber daya server.
- [ ] **Validasi Token Penyedia OAuth Terpusat:**
  Menambahkan verifikasi integritas token akses dari penyedia pihak ketiga sebelum menuntaskan proses penautan akun lokal.

### 2. Notifikasi & Komunikasi Domain

- [ ] **Penyedia Notifikasi Multi-Kanal:**
  Mengintegrasikan pengiriman notifikasi penugasan tugas melalui email dan kanal komunikasi eksternal memanfaatkan listener `SendTaskAssignedNotification`.

---

## Priority 2

### 1. Audit Trail & Log Transaksi

- [ ] **Pencatatan Riwayat Perubahan Entitas:**
  Mencatat riwayat modifikasi pada tabel tugas dan proyek ke dalam tabel log audit khusus untuk kebutuhan visibilitas kepatuhan data.

### 2. Pelaporan & Ekspor Data

- [ ] **Ekspor Ringkasan Proyek:**
  Menyediakan endpoint pembuatan berkas laporan berformat CSV yang merangkum metrik penyelesaian tugas di bawah proyek spesifik.
