# Glossary & Kamus Domain Bisnis

Kamus istilah domain bisnis dan teknis untuk mencegah ambiguitas istilah pada basis kode dan komunikasi tim.  

---

## 1. Domain Pengguna & Keamanan

- **User:** Entitas akun pengguna yang terdaftar di sistem melalui kredensial lokal (email/kata sandi) atau penyedia pihak ketiga.
- **Admin:** Peran dengan privilese penuh untuk menginisiasi proyek baru dan mengelola entitas yang ia miliki.
- **Member:** Peran operasional yang bertugas mengeksekusi tugas yang didelegasikan kepadanya.
- **RBAC (Role-Based Access Control):** Model pembatasan akses berdasarkan peran global (`admin`, `member`) yang dikelola via pustaka Spatie.
- **ABAC (Attribute-Based Access Control):** Model pembatasan akses berbasis atribut kepemilikan dan relasi data (`created_by`, `assigned_to`) yang dievaluasi melalui Laravel Policy.
- **Account Linking:** Penautan akun otomatis antara kredensial lokal dan penyedia OAuth2 (Google/GitHub) jika alamat email yang terverifikasi bernilai identik.

---

## 2. Domain Manajemen Kerja

- **Project:** Kontainer kerja tingkat atas yang menampung sekumpulan tugas. Dibuat oleh Admin dan memiliki atribut kepemilikan inisiator (`created_by`).
- **Owner (Pemilik Proyek):** Pengguna spesifik berstatus Admin yang memprakarsai pembuatan proyek tersebut. Memiliki hak eksklusif menghapus atau mengubah detail proyek.
- **Task:** Unit pekerjaan spesifik di bawah suatu proyek yang memiliki batas waktu pengerjaan dan penerima tugas.
- **Assignee (Penerima Tugas):** Pengguna berstatus Member atau Admin yang ditugaskan untuk menyelesaikan pekerjaan pada entitas tugas (`assigned_to`).
- **TaskStatus:** Kondisi siklus hidup pengerjaan tugas dengan tiga nilai status kanonikal: `todo` (belum dimulai), `in_progress` (sedang dikerjakan), dan `done` (selesai).
- **Comment:** Entitas percakapan kontekstual yang terikat pada tugas tertentu untuk mendukung diskusi antar anggota tim yang terlibat.

---

## 3. Terminologi Teknis & Arsitektur

- **Idempotency Key:** Nilai unik (UUID) yang dikirimkan melalui header `X-Idempotency-Key` untuk menjamin bahwa permintaan mutasi identik tidak akan mengeksekusi proses duplikat pada database.
- **Pessimistic Locking:** Mekanisme penguncian baris data pada tingkat transaksi database (`lockForUpdate`) untuk mencegah beberapa proses memodifikasi baris tugas yang sama secara paralel.
- **Domain Event:** Sinyal yang diterbitkan oleh domain saat terjadi perubahan status penting di dalam sistem (misalnya `TaskAssigned` ketika tugas dialokasikan ke pengguna baru).
- **Envelope API:** Format standar struktur pembungkus JSON (`ApiResponse`) yang menjamin konsistensi keluaran respon pada seluruh endpoint aplikasi.
