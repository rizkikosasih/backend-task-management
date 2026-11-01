# Master Architecture Decision Records (ADR)

Repositori: backend-task-management  
Dokumen Terkonsolidasi untuk Keputusan Arsitektur Tingkat Makro  

---

## ADR-001: Injeksi Service Konkret dan Eliminasi Abstraksi Repositori 1:1

### Konteks
Pola repositori sebelumnya dibuat dengan antarmuka 1:1 untuk setiap model Eloquent (`ProjectRepositoryInterface`, `TaskRepositoryInterface`). Implementasi kelas hanya meneruskan pemanggilan metode bawaan Eloquent tanpa variasi driver atau kebutuhan polimorfisme runtime. Pendekatan ini meningkatkan kompleksitas kode tanpa memberikan nilai tambah pada modularitas.

### Alternatif
1. **Mempertahankan Repositori Interface 1:1:** Menjaga antarmuka formal untuk setiap entitas, namun menyebabkan duplikasi penulisan kode dan indirection yang tidak memiliki kegunaan langsung.
2. **Injeksi Langsung Model Eloquent ke Controller:** Menghapus seluruh layer abstraksi, namun mencampuradukkan penanganan HTTP dengan logika bisnis aplikasi.
3. **Domain Service Konkret dengan Pemanggilan Eloquent:** Memusatkan logika bisnis pada kelas service konkret yang berinteraksi langsung dengan model Eloquent tanpa lapisan interface perantara.

### Keputusan
Mengadopsi pendekatan nomor 3: Mengeliminasi antarmuka repositori redundan dan memusatkan operasi manipulasi data pada Domain Service konkret (`ProjectService`, `TaskService`, `CommentService`, `AuthService`).

### Konsekuensi
- Jalur eksekusi logika lebih ringkas dan mudah ditelusuri.
- Mengurangi jumlah berkas boilerplate di dalam basis kode.
- Jika di masa mendatang dibutuhkan driver database non-relasional, antarmuka baru dapat diperkenalkan secara selektif pada batas modul terkait.

### Status Implementasi
Tuntas diimplementasikan pada seluruh modul aplikasi di `app/Services/`.

---

## ADR-002: Persistensi Lingkungan Lokal dan Pengujian Berbasis SQLite

### Konteks
Pengujian otomatis dan pengembangan lokal sebelumnya mengharuskan kontainer basis data MySQL selalu aktif. Hal ini memperlambat eksekusi rangkaian tes di lingkungan pengembang lokal dan menyulitkan evaluasi cepat tanpa ketergantungan daemon eksternal.

### Alternatif
1. **Wajib Menggunakan MySQL untuk Semua Lingkungan:** Menjamin keselarasan dialek SQL penuh, namun menambah beban konsumsi sumber daya mesin pengembang.
2. **Dual Driver (SQLite untuk Pengujian & Lokal, MySQL untuk Produksi):** Menjalankan pengujian otomatis menggunakan SQLite in-memory dan menyediakan opsi SQLite file untuk pengembangan lokal, sementara lingkungan produksi tetap menggunakan MySQL.

### Keputusan
Mengadopsi pendekatan dual driver. Berkas `phpunit.xml` dan konfigurasi koneksi aplikasi diatur agar mengutamakan driver SQLite saat pengujian berjalan, sementara berkas konfigurasi Docker dan Sail tetap dipertahankan untuk kebutuhan integrasi berlanjut.

### Konsekuensi
- Eksekusi pengujian PHPUnit menjadi sangat cepat karena berjalan di memori.
- Diperlukan kedisiplinan dalam penulisan kueri agar tidak memanfaatkan sintaksis khusus MySQL yang tidak kompatibel dengan SQLite.

### Status Implementasi
Tuntas dikonfigurasi pada `phpunit.xml` dan berkas lingkungan `.env.example`.

---

## ADR-003: Eliminasi Runtime Node.js dan Penegakan Pengetikan Ketat Native PHP

### Konteks
Repositori murni berfungsi sebagai penyedia antarmuka backend API, namun sebelumnya masih membawa dependensi `package.json`, konfigurasi Prettier, dan pustaka Node.js yang tidak memiliki keterkaitan dengan logika server.

### Alternatif
1. **Mempertahankan Prettier melalui Node.js:** Membutuhkan dua runtime berbeda (PHP dan Node.js) di dalam alur kerja pengembangan dan CI runner.
2. **Transisi Penuh ke Linter dan Toolchain Native PHP:** Menghapus ekosistem Node.js dan mengalihkan seluruh tugas pemformatan serta analisis kode ke Laravel Pint dan Larastan.

### Keputusan
Menghapus seluruh berkas dan ketergantungan Node.js dari repositori. Penegakan standar kode didelegasikan kepada Laravel Pint dengan konfigurasi `declare_strict_types` aktif dan Larastan pada tingkat analisis level 5.

### Konsekuensi
- Menghilangkan friksi dependensi lintas runtime pada alur integrasi GitHub Actions.
- Seluruh 89 berkas PHP di dalam proyek memiliki deklarasi tipe yang ketat dan konsisten.

### Status Implementasi
Tuntas diverifikasi melalui `vendor/bin/pint --test` dan `vendor/bin/phpstan analyse` di lokal maupun runner CI.

---

## ADR-004: Mekanisme Proteksi Transaksi Jaringan Menggunakan Idempotency Key

### Konteks
Pada kondisi jaringan dengan latensi tinggi atau percobaan pengiriman ulang otomatis dari aplikasi klien, pemanggilan endpoint mutasi data (`POST /api/projects`, `POST /api/tasks`) berisiko menciptakan entitas ganda yang tidak diinginkan.

### Alternatif
1. **Mengandalkan Pengecekan Duplikasi Manual di Controller:** Rentan terhadap celah waktu di antara pengecekan dan penyimpanan.
2. **Penerapan Idempotency Key Middleware:** Memanfaatkan header HTTP standar `X-Idempotency-Key` yang dipadukan dengan layer cache untuk mendeteksi permintaan berulang dan mengembalikan respon tersimpan secara langsung.

### Keputusan
Menerapkan `IdempotencyMiddleware` pada endpoint pembuatan sumber daya. Kunci disimpan pada cache selama 24 jam bersama payload respon hasil eksekusi pertama.

### Konsekuensi
- Mencegah timbulnya data duplikat pada tingkat jaringan.
- Memerlukan klien API untuk mengirimkan nilai UUID unik pada header `X-Idempotency-Key`.

### Status Implementasi
Tuntas diimplementasikan pada `app/Http/Middleware/IdempotencyMiddleware.php` dan teruji pada suite `tests/Feature/IdempotencyTest.php`.

---

## ADR-005: Kontrol Konkurensi Transisi Status Tugas Menggunakan Penguncian Pesimistis

### Konteks
Ketika beberapa pengguna atau proses latar belakang memodifikasi status tugas yang sama dalam waktu bersamaan, kondisi perlombaan (*race condition*) dapat menyebabkan status akhir tidak valid atau menimpa status yang telah didahului oleh transaksi lain.

### Alternatif
1. **Pembaruan Kolom Langsung Tanpa Transaksi:** Rawan menghasilkan data inkonsisten saat beban mutasi tinggi.
2. **Optimistic Locking Menggunakan Versi Kolom:** Membutuhkan penambahan kolom versi di tabel dan penanganan pengecualian di sisi klien jika terjadi konflik.
3. **Pessimistic Locking di dalam Transaksi Database:** Mengunci baris data tugas menggunakan `lockForUpdate()` selama blok transaksi berlangsung.

### Keputusan
Mengadopsi pendekatan penguncian pesimistis pada metode `TaskService::updateStatus`. Baris data tugas dikunci hingga transaksi selesai sebelum status baru dicatat dan event penugasan dipicu.

### Konsekuensi
- Integritas urutan perubahan status terjamin secara mutlak pada tingkat database.
- Terdapat latensi minimal bagi transaksi konkuren berikutnya yang menunggu pelepasan kunci baris data.

### Status Implementasi
Tuntas diimplementasikan pada `app/Services/TaskService.php` dan terverifikasi pada suite `tests/Feature/TaskStatusTransitionTest.php`.

---

## ADR-006: Otomasi Dokumentasi Antarmuka Menggunakan Scramble OpenAPI

### Konteks
Dokumentasi API sering kali mengalami desinkronisasi dengan kode sumber jika ditulis secara manual (misalnya menggunakan anotasi Swagger/OpenAPI yang panjang di controller atau berkas YAML eksternal).

### Alternatif
1. **Menulis Anotasi Manual Swagger (L5-Swagger):** Membebani controller dengan baris komentar panjang yang mengaburkan logika bisnis.
2. **Generasi Otomatis via Scramble:** Memanfaatkan analisis statis tipe data request, resource, dan aturan validasi untuk menghasilkan spesifikasi OpenAPI 3.0 secara otomatis tanpa anotasi tambahan.

### Keputusan
Mengintegrasikan pustaka `dedoc/scramble` untuk menyediakan antarmuka dokumentasi interaktif pada endpoint `/docs/api` dan spesifikasi skema pada `/docs/api.json`.

### Konsekuensi
- Menghilangkan beban penulisan anotasi manual pada controller.
- Mengharuskan penulisan Form Request dan API Resource yang terstruktur agar tipe data terpetakan dengan akurat.

### Status Implementasi
Tuntas dipasang via Composer dan dapat diakses langsung pada rute `/docs/api`.
