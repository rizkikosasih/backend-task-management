# Project Task Backlog (TASKS)

Hierarki Standar Pelacakan Tugas Proyek: Priority -> Sub-Poin -> Item Pekerjaan.

---

## Priority 1

### 1. Keamanan & Pengendalian Akses Jaringan

- [ ] **Rate Limiting Dinamis Berbasis Redis per Identitas Pengguna:**
  Menerapkan pembatasan frekuensi mutasi data API memanfaatkan algoritma token bucket Redis berbasis User ID terotentikasi, dengan mekanisme fallback ke driver database cache.
- [ ] **Validasi Integritas Token OAuth Terpusat:**
  Menambahkan verifikasi payload dan status aktif token otentikasi dari penyedia eksternal (Google/GitHub) sebelum finalisasi penautan akun lokal.

### 2. Notifikasi Domain & Infrastruktur Asinkron

- [ ] **Worker Antrean Multi-Kanal Berkinerja Tinggi:**
  Mengarahkan listener SendTaskAssignedNotification ke antrean worker asinkron berbasis Redis dengan integrasi kelas Mailable dan logging kegagalan pengiriman.

---

## Priority 2

### 1. Audit Trail & Log Transaksi

- [ ] **Skema Riwayat Perubahan Entitas Asinkron:**
  Membangun tabel log audit terisolasi yang dipicu melalui domain event asinkron guna merekam riwayat perubahan tugas tanpa menambah durasi penguncian baris (lockForUpdate).

### 2. Pelaporan & Transformasi Data

- [ ] **Generator Ekspor CSV Streaming:**
  Menyediakan endpoint streaming pembuatan berkas CSV ringkasan metrik proyek untuk mencegah lonjakan alokasi memori saat memproses dataset besar.

---

## Priority 3

### 1. Manajemen Logging & Observabilitas

- [ ] **Middleware Correlation ID & Monolog JSON Formatter:**
  Menginjeksi header X-Request-ID ke konteks log terstruktur JSON Monolog dan seluruh amplop respon HTTP untuk penelusuran transaksi terdistribusi.
- [ ] **Filter Sanitisasi Log Kontekstual:**
  Mengonfigurasi penanganan eksepsi global agar menyaring kredensial sensitif dari payload request sebelum dicatat pada berkas rotasi harian.

### 2. Strategi Caching & Concurrency Control

- [ ] **Tagged Cache Proyek & Tugas dengan Fallback Guard:**
  Mengimplementasikan mekanisme cache berlabel pada kueri baca proyek dan tugas yang terinvaliasi otomatis saat terjadi mutasi data, disertai penanganan isolasi driver non-Redis.
- [ ] **Distributed Lock Metrik Tugas:**
  Menerapkan Redis atomic locks dengan batasan TTL ketat pada kalkulasi agregasi metrik tugas guna mengeliminasi kondisi cache stampede.

### 3. Skema Basis Data & Optimasi Kueri

- [ ] **Indeks B-Tree Komposit Filter Tugas:**
  Menambahkan migrasi indeks komposit pada pasangan kolom (project_id, status) dan (assigned_to, status) untuk mengeliminasi full table scan pada kueri filter task list.
- [ ] **Penegakan Integritas Tipe Kolom Status:**
  Mengubah tipe kolom status dari VARCHAR(255) bebas menjadi tipe data terbatas yang tervalidasi serta memperketat batasan Foreign Key onDelete restrict/cascade.

### 4. Kesiapan Produksi & Standarisasi Respon

- [ ] **Endpoint Health Check Probe Terpadu:**
  Menyediakan rute pemantauan /api/health/live dan /api/health/ready yang memverifikasi kesiapan koneksi basis data utama dan kluster Redis.
- [ ] **Header Metadata Paginasi:**
  Menyematkan informasi paginasi terpadu pada header HTTP respon (X-Total-Count, X-Page, X-Per-Page) untuk melengkapi amplop respon JSON.
