# Checklist Review Manusia Tahap 13 — GE Berbobot

> Checklist internal. Human weight review telah lulus, tetapi dokumen ini bukan approval atau freeze. Seluruh record tetap `active=false` dan belum diimpor.

## Rekap kontrak

| Komponen | Nilai yang diharapkan |
|---|---:|
| Example | 1 |
| Scored | 10 |
| Total record | 11 |
| Opsi per record | 5 |
| Total opsi | 55 |
| Skor per record | tepat satu masing-masing 0, 1, 2, 3, 4 |
| Best answer | tepat satu skor 4 dan `is_correct=true` |
| Difficulty scored | easy 3, medium 4, hard 3 |
| Maksimum skor subtes | 40 |

## Checklist per record

- [ ] Logical ID tepat: `ge-example-001` atau `ge-001`–`ge-010`.
- [ ] Prompt menampilkan tepat dua konsep dan tidak terlalu panjang.
- [ ] Terdapat tepat lima opsi A–E.
- [ ] Score values adalah integer dan tepat membentuk himpunan `{0,1,2,3,4}`.
- [ ] Tepat satu opsi skor 4 dengan `is_correct=true`.
- [ ] Seluruh opsi skor 0–3 mempunyai `is_correct=false`.
- [ ] Opsi skor 4 menyatakan persamaan utama paling spesifik dan bermakna.
- [ ] Opsi skor 3 berkaitan kuat tetapi lebih umum atau kurang lengkap.
- [ ] Opsi skor 2 mempunyai hubungan yang benar tetapi jauh lebih luas.
- [ ] Opsi skor 1 hanya mempunyai ciri permukaan, kemungkinan, atau hubungan sangat lemah.
- [ ] Opsi skor 0 tidak sesuai atau berlawanan dengan persamaan utama.
- [ ] Tidak ada dua opsi yang layak memperoleh bobot sama.
- [ ] Panjang opsi tidak menjadi petunjuk tunggal bagi bobot 4.
- [ ] Bahasa baku, tidak sensitif, dan tidak membutuhkan pengetahuan khusus.
- [ ] Difficulty adalah label editorial, bukan klaim empiris.
- [ ] Rationale menjelaskan seluruh urutan bobot 4→0.
- [ ] Metadata orisinalitas lengkap; `review_status=in_review`; `active=false`.

## Sign-off per logical ID

- [ ] `ge-example-001`
- [ ] `ge-001`
- [ ] `ge-002`
- [ ] `ge-003`
- [ ] `ge-004`
- [ ] `ge-005`
- [ ] `ge-006`
- [ ] `ge-007`
- [ ] `ge-008`
- [ ] `ge-009`
- [ ] `ge-010`

## Review bahasa manusia

- Status: pending
- Reviewer: ____________________
- Tanggal: ____________________
- Keputusan: ____________________
- Catatan: _________________________________________________________________

## Review logika manusia

- Status: pending
- Reviewer: ____________________
- Tanggal: ____________________
- Keputusan: ____________________
- Catatan: _________________________________________________________________

## Review hierarki bobot manusia

- Status: passed
- Reviewer: ____________________
- Tanggal: ____________________
- Keputusan: `human_review_passed`
- Record yang direvisi: `ge-007` opsi B; `ge-010` opsi E
- Catatan: Bobot tetap 1; redaksi dan rationale diperbaiki agar benar untuk kedua konsep.

## Gate setelah review

Draft tidak boleh dipindahkan ke dataset final produksi, diimpor, dibekukan, diaktifkan, atau dikirim kepada peserta sebelum review manusia dan approval tahap berikutnya selesai.
