# Known Issues & Mitigasi Teknis

Katalog pencatatan kendala teknis dan mitigasi solusinya guna mencegah investigasi berulang.  

---

## 1. Timeout Proses Composer Dump-Autoload pada Lingkungan Windows

- **Deskripsi:** Pemanggilan perintah `composer dump-autoload -o` terkadang mengalami waktu henti lama (*hang*) atau memicu galat batas waktu proses (*timeout*) saat dijalankan di sistem operasi Windows.
- **Akar Masalah:** Pemindaian berkas secara mendalam pada sistem berkas NTFS dan keterlibatan eksekusi skrip otomatisasi pasca-instalasi.
- **Mitigasi:** Jalankan perintah autoloader tanpa opsi optimasi penuh saat pengembangan lokal (`composer dump-autoload`), atau tingkatkan nilai `COMPOSER_PROCESS_TIMEOUT=0` pada terminal PowerShell sebelum eksekusi.
- **Status:** Terdokumentasi dan termitigasi.

---

## 2. Pelanggaran Aturan Single Blank Line pada Berkas Kelas Baru

- **Deskripsi:** Berkas kelas baru yang dibuat melalui template generator dapat memicu kegagalan linter `vendor/bin/pint --test` pada tahapan integrasi CI akibat baris kosong ekstra di akhir berkas (`single_blank_line_at_eof`).
- **Akar Masalah:** Perbedaan penanganan karakter akhir baris antara generator teks dan konfigurasi linter Laravel Pint.
- **Mitigasi:** Pastikan setiap penambahan berkas PHP diakhiri tepat satu baris baru (*single newline*) tanpa baris kosong berlebih sebelum proses pengajuan komit.
- **Status:** Terselesaikan pada konfigurasi linter.
