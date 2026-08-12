# Tahap 17 — Checklist UAT Internal

Status dokumen: `prepared`. Semua item di bawah belum dianggap sign-off sampai diuji dan diberi bukti oleh reviewer manusia.

## Guard sebelum UAT

- [ ] Gunakan environment/database UAT yang diizinkan; jangan gunakan `tes_iq`.
- [ ] Pastikan dataset final staging tetap `human_review_passed`, inactive, unapproved, unfrozen, dan unimported.
- [ ] Pastikan card Tes Kemampuan Kognitif Adaptasi tetap inactive bagi pengguna umum.
- [ ] Pastikan tidak ada PDF/workbook sumber atau kunci internal pada network/log/browser storage.

## General

- [ ] Card tetap inactive.
- [ ] Dataset tidak dapat dimulai pengguna umum.
- [ ] Admin/internal preview, bila tersedia, tetap terproteksi.
- [ ] Semua subtes tampil berurutan SE, WA, AN, GE, RA, ZR, FA, WU, ME.
- [ ] Sembilan example tidak masuk scoring.
- [ ] Timer tiap subtes sesuai 240/240/240/300/360/360/240/360/360 detik.
- [ ] Refresh tidak mereset timer.
- [ ] Blank dapat disubmit atau diselesaikan melalui timeout.
- [ ] Subtes yang selesai terkunci dan tidak dapat dibuka kembali.
- [ ] Hasil menampilkan tepat: “Hasil ini merupakan skor internal berdasarkan sembilan subtes. Nilai ini belum merupakan skor IQ atau interpretasi normatif.”
- [ ] Hasil tidak menampilkan IQ, norma, SW, Gesamt, kategori normatif, atau dominasi.

## ME — Example dan lifecycle

- [ ] Example wajib dipilih dan dikonfirmasi sebelum tombol start aktif.
- [ ] Start sebelum example selesai ditolak server.
- [ ] Refresh mempertahankan state example complete.
- [ ] Feedback example tidak terlihat di payload sebelum completion.
- [ ] Example tidak membuat scored answer atau mengubah skor.
- [ ] Example tidak memulai atau mengurangi waktu.
- [ ] Start sesudah example berhasil dan menetapkan deadline server 120/240 detik.
- [ ] Start kedua tidak memberi tambahan waktu.

## ME — Memorization dan leakage

- [ ] Memorization menampilkan tepat 15 pair dalam urutan deterministik.
- [ ] Desktop menggunakan maksimal tiga kolom.
- [ ] Tablet turun menjadi dua kolom.
- [ ] Mobile menggunakan satu kolom tanpa clipping atau horizontal scroll.
- [ ] Semua kartu pair setara, tanpa nomor, ikon, grouping color, tested/filler marker.
- [ ] Materi hilang tepat saat masuk answering.
- [ ] Back/refresh setelah answering tidak mengembalikan materi.
- [ ] Answering menampilkan tepat 12 soal.
- [ ] Timeout memorization dan answering berpindah/finalize dengan benar.
- [ ] Autosave saat memorization ditolak.
- [ ] Network payload memorization hanya memuat cue, associate, dan displayOrder.
- [ ] Network/DOM answering tidak memuat pair, key, tested/filler, logical mapping, rationale, atau hidden memorization content.

## FA/WU

- [ ] Seluruh 144 media yang direferensikan tampil tanpa broken asset.
- [ ] Alt text netral dan tidak membocorkan key.
- [ ] Zoom/responsiveness aman pada desktop, tablet, dan mobile.
- [ ] Tidak ada nama/path file yang membocorkan jawaban.
- [ ] Geometri, simbol, orientasi, mirror/chirality, dan skala tetap sesuai hasil human review Tahap 15.

## Scoring smoke test

- [ ] Binary/image/ME: correct=1, wrong/blank=0.
- [ ] Numeric: exact canonical match=1; wrong/blank=0.
- [ ] GE: score 3 correct, 1–2 partial, 0 wrong, kosong blank.
- [ ] Persentase memakai awarded/max × 100.
- [ ] Total internal adalah rata-rata sembilan persentase, bukan jumlah raw score.

## Sign-off

- [ ] Bukti browser/network capture aman telah dilampirkan.
- [ ] Security review leakage ME lulus.
- [ ] Reviewer konten/visual mengonfirmasi staging identik dengan draft reviewed.
- [ ] Product owner memberi approval eksplisit pada tahap terpisah.
- [ ] Freeze dan checksum ulang dilakukan hanya setelah approval.

Catatan/sign-off reviewer:

```text
Nama:
Tanggal:
Environment:
Hasil:
Temuan:
```
