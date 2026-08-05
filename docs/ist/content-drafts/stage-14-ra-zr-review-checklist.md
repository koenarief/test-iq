# Checklist Review Manusia Tahap 14 — RA dan ZR

> Checklist internal. Review manusia Tahap 14 telah lulus, tetapi dokumen ini bukan approval atau freeze. Seluruh record tetap `in_review`, `active=false`, belum diimpor, dan belum frozen.

## Rekap kontrak

| Subtes | Example | Scored | Easy | Medium | Hard | Durasi | Maks. skor |
|---|---:|---:|---:|---:|---:|---:|---:|
| RA | 1 | 12 | 4 | 5 | 3 | 360 detik | 12 |
| ZR | 1 | 12 | 4 | 5 | 3 | 360 detik | 12 |
| **Total** | **2** | **24** | **8** | **10** | **6** | — | **24** |

## Checklist numeric per record

- [ ] Logical ID dan display order tepat serta unik.
- [ ] Prompt jelas dan meminta angka saja.
- [ ] Terdapat tepat satu `numeric_answer_key`.
- [ ] Key berupa string bilangan bulat nonnegatif canonical.
- [ ] Key tidak mempunyai tanda, desimal, pecahan, satuan, leading zero, atau pemisah ribuan.
- [ ] Tidak ada tolerance, rounding, multi-key, atau jawaban alternatif.
- [ ] Empty input diperlakukan blank; exact match correct; selain itu wrong.
- [ ] Solution internal menghasilkan key yang sama.
- [ ] Difficulty adalah label editorial, bukan klaim empiris.
- [ ] Metadata orisinalitas lengkap; `review_status=in_review`; `active=false`.

## Checklist khusus RA

- [ ] Seluruh informasi numerik mempunyai fungsi yang jelas.
- [ ] Urutan operasi tidak bergantung pada asumsi tersembunyi.
- [ ] Satuan dalam prompt konsisten dan tidak masuk field jawaban.
- [ ] Hasil akhir bilangan bulat nonnegatif tanpa pembulatan.
- [ ] Soal laju menyatakan laju tetap.
- [ ] Soal pembagian menyatakan pembagian sama rata bila diperlukan.
- [ ] Soal persamaan mempunyai tepat satu solusi yang memenuhi konteks.

## Checklist khusus ZR

- [ ] Aturan paling sederhana berlaku pada seluruh transisi yang ditampilkan.
- [ ] Jumlah suku cukup untuk mendukung aturan editorial.
- [ ] Deret berselang-seling menjelaskan posisi ganjil dan genap secara konsisten.
- [ ] Kelanjutan tidak membutuhkan pertukaran digit atau unordered-digit rule.
- [ ] Kelanjutan menghasilkan satu bilangan bulat nonnegatif.
- [ ] Tidak ada aturan alternatif sederhana yang menghasilkan jawaban berbeda.

## Sign-off logical ID

### RA

- [ ] `ra-example-001`
- [ ] `ra-001`  - [ ] `ra-002`  - [ ] `ra-003`  - [ ] `ra-004`
- [ ] `ra-005`  - [ ] `ra-006`  - [ ] `ra-007`  - [ ] `ra-008`
- [ ] `ra-009`  - [ ] `ra-010`  - [ ] `ra-011`  - [ ] `ra-012`

### ZR

- [ ] `zr-example-001`
- [ ] `zr-001`  - [ ] `zr-002`  - [ ] `zr-003`  - [ ] `zr-004`
- [ ] `zr-005`  - [ ] `zr-006`  - [ ] `zr-007`  - [ ] `zr-008`
- [ ] `zr-009`  - [ ] `zr-010`  - [ ] `zr-011`  - [ ] `zr-012`

## Review bahasa manusia

- Status: pending
- Reviewer: ____________________
- Tanggal: ____________________
- Keputusan: ____________________
- Catatan: _________________________________________________________________

## Review logika dan canonical answer manusia

- Status: passed
- Reviewer: ____________________
- Tanggal: ____________________
- Keputusan: `human_review_passed`
- Record yang direvisi: `ra-006`, `ra-011`, `zr-004`
- Catatan: Prompt, solusi, rationale, ambiguity review, dan rekap canonical key ketiga record telah diperbarui sesuai human review; difficulty tidak berubah.

## Gate setelah review

Draft tidak boleh dipindahkan ke dataset final produksi, diimpor, dibekukan, diaktifkan, atau dikirim kepada peserta sebelum review manusia dan approval tahap berikutnya selesai.
