# Product Requirement Document (PRD) - Backend Task Management API

Versi: 2.0
Pemilik Produk: Rizki Kosasih

---

## 1. Ringkasan Eksekutif

Backend Task Management API adalah sistem antarmuka pemrograman aplikasi berbasis RESTful yang dirancang untuk mengelola proyek kolaboratif, penugasan pekerjaan, dan diskusi internal. Sistem ini menitikberatkan pada isolasi data kepemilikan, pemisahan wewenang peran terperinci, jaminan idempotensi transaksi, dan pencegahan inkonsistensi data pada modifikasi status tugas secara konkuren.

---

## 2. Sasaran dan Indikator Keberhasilan

- Memastikan isolasi data: Pengguna dengan peran anggota hanya dapat mengakses tugas yang didelegasikan kepadanya atau proyek tempat ia berkontribusi aktif.
- Menjamin integritas transaksi status: Mencegah kondisi perlombaan (*race condition*) ketika status tugas diubah secara paralel oleh banyak pihak.
- Mencegah duplikasi data: Transaksi pembuatan proyek dan tugas wajib memiliki resistensi terhadap pengiriman ganda akibat latensi jaringan melalui kunci idempotensi.
- Autentikasi hibrida tanpa akun ganda: Integrasi OAuth2 menautkan kredensial penyedia eksternal dengan akun lokal jika alamat email terverifikasi identik.

---

## 3. Persona dan Hak Akses

Sistem membedakan dua aktor utama dengan perpaduan kontrol akses berbasis peran (RBAC) dan atribut kepemilikan (ABAC):

1. **Admin:**
   - Memiliki kendali pembuatan proyek baru.
   - Bertindak sebagai pemilik (*Owner*) atas proyek yang dibuatnya.
   - Berhak mendelegasikan tugas kepada anggota lain pada proyek miliknya.
   - Berhak memodifikasi dan menghapus seluruh tugas dan proyek yang ia inisiasi.

2. **Member:**
   - Menjalankan penugasan yang diberikan oleh Admin.
   - Hanya dapat melihat rincian proyek dan tugas yang terhubung langsung dengan dirinya.
   - Diberi hak mengubah status tugas yang ditugaskan kepadanya.
   - Dapat berpartisipasi memberikan komentar dan mengelola komentar miliknya sendiri.

---

## 4. Ruang Lingkup Fungsional

### 4.1 Autentikasi dan Manajemen Sesi
- Registrasi dan login lokal berbasis token (*Laravel Sanctum*).
- Autentikasi pihak ketiga (*OAuth2*) melalui Google dan GitHub dengan mekanisme penautan akun otomatis berbasis kecocokan email.
- Penautan multi-penyedia identitas ke dalam satu entitas akun pengguna.
- Pembatalan token sesi saat logout.

### 4.2 Manajemen Proyek
- Pembuatan proyek baru oleh Admin dengan pencatatan pemilik inisiator (`created_by`).
- Menampilkan daftar proyek dengan filter pencarian dan paginasi data.
- Modifikasi dan penghapusan lunak (*soft delete*) proyek hanya oleh Admin inisiator.
- Akses detail proyek dibatasi untuk Admin inisiator dan Member yang terdaftar pada tugas proyek terkait.

### 4.3 Manajemen Tugas
- Pembuatan tugas di bawah proyek spesifik oleh Admin inisiator.
- Penugasan tugas ke satu pengguna Member.
- Alur status tugas terdefinisi: `todo`, `in_progress`, `done`.
- Pengendalian konkurensi: Penguncian pesimistis (*pessimistic locking*) diterapkan saat transisi status berlangsung guna menjamin konsistensi antrean status.
- Penerbitan domain event asynchronous saat tugas didelegasikan ke pengguna baru (`TaskAssigned`).

### 4.4 Sistem Komentar
- Percakapan terstruktur di bawah entitas tugas.
- Seluruh pengguna yang memiliki akses membaca tugas dapat menuliskan komentar.
- Penghapusan komentar hanya diizinkan untuk pemilik komentar bersangkutan.

### 4.5 Keandalan Transaksi dan Idempotensi
- Pengiriman header `X-Idempotency-Key` pada endpoint pembuatan proyek dan tugas.
- Hasil respon disimpan dalam cache sementara untuk mendeteksi permintaan berulang dengan payload identik.

---

## 5. Kebutuhan Non-Fungsional

- **Pengetikan Ketat:** Seluruh berkas PHP menegakkan `declare(strict_types=1);` dan lulus analisis statis Larastan level 5 tanpa galat.
- **Standarisasi Respon:** Seluruh keluaran API mengikuti amplop respon terpadu (`ApiResponse`) dengan struktur status, pesan, data, dan metadata.
- **Dokumentasi Otomatis:** Spesifikasi OpenAPI 3.0 digenerasikan secara otomatis melalui Scramble tanpa anotasi manual yang membebani kode sumber.
- **Isolasi Database:** Implementasi basis data SQLite lokal dan MySQL pada produksi dengan pemeliharaan skema melalui migrasi berkala.
