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

---

## Priority 3

### 1. Manajemen Logging & Observabilitas

- [ ] **Strukturisasi Log JSON Monolog & Correlation ID:**
  Mengonfigurasi Monolog agar mencetak log berformat JSON terstruktur dengan injeksi Correlation ID (X-Request-ID) unik pada setiap daur hidup request untuk penelusuran terdistribusi.
- [ ] **Konfigurasi Kanal Log Harian & Kontekstual Eksepsi:**
  Menetapkan rotasi berkas log harian dengan retensi otomatis dan penangkapan payload eksepsi tanpa membocorkan data sensitif (sanitisasi kredensial).

### 2. Strategi Caching & Kinerja Layanan

- [ ] **Penerapan Caching Tagged Redis pada Kueri Proyek dan Tugas:**
  Mengimplementasikan mekanisme cache berlabel (tagged cache) untuk endpoint pembacaan proyek dan tugas yang terinvaliasi otomatis saat terjadi mutasi data.
- [ ] **Atomic Locks & Perlindungan Cache Stampede:**
  Menerapkan Redis atomic locks pada kalkulasi agregasi metrik tugas guna mencegah fenomena penyerbuan basis data saat cache kedaluwarsa secara serentak.

### 3. Standarisasi Basis Data & Indeks Performa

- [ ] **Penyesuaian Indeks Komposit & Evaluasi Execution Plan:**
  Menambahkan indeks B-Tree komposit pada kolom frekuensi tinggi (seperti project_id + status pada tabel tasks) serta menjalankan analisis EXPLAIN guna mengeliminasi full table scan.
- [ ] **Penegakan Standar Tipe Kolom & Integritas Referensial:**
  Menstandarkan tipe data kolom status dan pengenal entitas serta memperketat batasan Foreign Key onDelete cascade/restrict sesuai batasan domain.

### 4. Peningkatan Kapabilitas Teknis & Kesiapan Produksi

- [ ] **Endpoint Health Probes Standar:**
  Menyediakan endpoint pemantauan status liveness dan readiness (/api/health/live dan /api/health/ready) yang memeriksa koneksi basis data dan ketersediaan penyimpanan.
- [ ] **Standarisasi Header Metadata Paginasi:**
  Menyematkan informasi paginasi terpadu pada header HTTP respon (X-Total-Count, X-Page, X-Per-Page) untuk melengkapi amplop respon JSON.
