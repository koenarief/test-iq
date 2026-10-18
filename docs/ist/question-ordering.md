# Pengurutan bank soal IST

Nomor soal pada admin adalah posisi dalam pasangan subtes dan jenis (`scored` atau `example`). Posisi juga menentukan `display_order`. Soal nonaktif tetap memiliki posisi; soal soft-deleted tidak memesan nomor.

- Menambahkan atau memindahkan soal ke posisi yang sudah terisi menggeser soal lain.
- Posisi melebihi jumlah soal ditempatkan di akhir.
- Menghapus soal merapatkan urutan grup yang sama.
- Memindahkan soal ke subtes/jenis lain merapikan grup asal dan tujuan.
- Mengedit konten tanpa mengganti nomor mempertahankan urutan relatif, termasuk pada bank lama yang nomornya berlubang.
- ID soal, arsip soft-delete, dan snapshot peserta tidak diganti oleh pengurutan.

## Penerapan

Deploy perubahan kode dan migrasi bersama, saat admin tidak sedang mengedit bank soal. Jalankan pada environment/database yang memang dituju:

```bash
php artisan migrate --force
php artisan ist:renumber-questions
php artisan ist:renumber-questions --apply
npm run build
```

Command pertama renumber hanya menampilkan grup/jumlah soal yang perlu dirapikan. `--apply` merapikan semua grup dalam satu transaksi; aman dijalankan ulang. Migrasi sendiri hanya memperbaiki constraint, tidak menomori ulang data. Mutasi admin berikutnya juga merapikan grup yang disentuh.

Constraint baru menjaga keunikan nomor untuk soal yang belum dihapus melalui generated column `live_question_number`. Kolom ini NULL pada arsip, sehingga beberapa arsip boleh memiliki nomor sama. Mengembalikan constraint lama lewat rollback ditolak jika nomor sudah dipakai ulang; arsip tidak dihapus otomatis demi rollback.

Pengurutan mengunci katalog subtes dalam urutan ID yang tetap selama transaksi admin, lalu menukar nomor memakai posisi sementara 0 yang tidak pernah disimpan sebagai hasil akhir. Penulisan langsung oleh SQL/import tidak melalui layanan pengurutan admin. Dataset import masih memiliki pemeriksaan natural key sendiri; renumber bukan operasi sinkronisasi ulang dataset asal.

## Verifikasi

Tes database menggunakan MySQL `tes_iq_testing` dengan transaksi yang di-rollback. Terapkan migrasi baru ke database testing sebelum menjalankan:

```bash
php artisan migrate --env=testing --path=database/migrations/2026_10_19_000001_allow_reusing_deleted_ist_question_numbers.php --force
php artisan test tests/Feature/Admin/IstQuestionOrderingTest.php tests/Feature/Admin/IstQuestionControllerTest.php
```

Skenario regresi mencakup hapus/sisip/pindah, nomor arsip berulang, batas grup subtes/jenis, soal nonaktif, perapian celah lama, edit konten, posisi tidak valid, unique constraint, pratinjau command, dan keutuhan snapshot peserta. Selesaikan build frontend sebelum menjalankan tes Inertia agar hash manifest tidak berubah di tengah tes.
