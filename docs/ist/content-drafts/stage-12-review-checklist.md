# Checklist Review Manusia Tahap 12 — SE, WA, AN

> Dokumen kerja internal. Checklist ini tidak merupakan approval. Status seluruh konten tetap `in_review` dan `active=false` sampai reviewer manusia menandatangani tahap terpisah.

## Rekap yang harus diverifikasi

| Subtes | Example | Scored | Opsi/butir | Easy | Medium | Hard | Distribusi key scored |
|---|---:|---:|---:|---:|---:|---:|---|
| SE | 1 | 12 | 5 | 4 | 5 | 3 | A2, B2, C3, D3, E2 |
| WA | 1 | 12 | 5 | 4 | 5 | 3 | A2, B2, C3, D3, E2 |
| AN | 1 | 12 | 5 | 4 | 5 | 3 | A2, B2, C3, D3, E2 |
| **Total** | **3** | **36** | **195 opsi** | **12** | **15** | **9** | — |

Catatan: distribusi `3,3,2,2,2` adalah distribusi paling seimbang untuk 12 key ketika kelima opsi wajib muncul minimal dua kali. Reviewer harus mengonfirmasi keputusan ini karena ketentuan “maksimum satu opsi tiga kali” tidak dapat dipenuhi secara bersamaan tanpa menaikkan satu opsi menjadi empat kali.

## Checklist per butir

Gunakan satu baris review untuk setiap logical ID. Jangan ubah status menjadi approved/frozen pada Tahap 12.

- [ ] Logical ID dan display order tepat serta unik.
- [ ] Prompt jelas, singkat, dan menggunakan bahasa Indonesia baku.
- [ ] Terdapat tepat lima opsi A–E.
- [ ] Tepat satu opsi mempunyai `is_correct=true` dan `score_value=1`.
- [ ] Empat opsi lain mempunyai `is_correct=false` dan `score_value=0`.
- [ ] Seluruh opsi cocok secara tata bahasa dan panjangnya tidak membocorkan key.
- [ ] Tidak terdapat negasi ganda atau kata absolut yang tidak diperlukan.
- [ ] Tidak membutuhkan pengetahuan khusus, fakta cepat berubah, atau konteks budaya sempit.
- [ ] Tidak mengandung data pribadi, materi sensitif, merek, atau tokoh aktual.
- [ ] Explanation example dan rationale internal konsisten dengan key.
- [ ] Difficulty merupakan label editorial yang masuk akal, bukan klaim empiris.
- [ ] Metadata orisinalitas lengkap; status `in_review`; `active=false`.
- [ ] Keputusan distribusi key `3,3,2,2,2` telah dikonfirmasi atau diberi arahan revisi.

## Checklist khusus SE

- [ ] Hanya ada satu pelengkap yang paling tepat secara makna.
- [ ] Distraktor tidak gugur hanya karena tata bahasa.
- [ ] Hubungan sebab, tujuan, kondisi, atau urutan logis teridentifikasi jelas.
- [ ] Distraktor semantik dekat pada hard item tetap bukan jawaban setara.

## Checklist khusus WA

- [ ] Empat opsi mempunyai satu kategori bersama yang dapat dinyatakan singkat.
- [ ] Satu opsi berbeda pada dasar klasifikasi yang sama.
- [ ] Pengelompokan alternatif utama telah dicatat dan tidak lebih kuat.
- [ ] Opsi relatif seimbang dalam bentuk dan panjang.

## Checklist khusus AN

- [ ] Relasi A→B dapat dinyatakan dalam satu kalimat.
- [ ] Relasi C→key identik atau paling setara.
- [ ] Distraktor memakai relasi berbeda, bukan relasi alternatif yang sama kuat.
- [ ] Arah relasi tidak dapat dibalik secara ambigu.

## Daftar logical ID untuk sign-off

### SE

- [ ] `se-example-001`
- [ ] `se-001`  - [ ] `se-002`  - [ ] `se-003`  - [ ] `se-004`
- [ ] `se-005`  - [ ] `se-006`  - [ ] `se-007`  - [ ] `se-008`
- [ ] `se-009`  - [ ] `se-010`  - [ ] `se-011`  - [ ] `se-012`

### WA

- [ ] `wa-example-001`
- [ ] `wa-001`  - [ ] `wa-002`  - [ ] `wa-003`  - [ ] `wa-004`
- [ ] `wa-005`  - [ ] `wa-006`  - [ ] `wa-007`  - [ ] `wa-008`
- [ ] `wa-009`  - [ ] `wa-010`  - [ ] `wa-011`  - [ ] `wa-012`

### AN

- [ ] `an-example-001`
- [ ] `an-001`  - [ ] `an-002`  - [ ] `an-003`  - [ ] `an-004`
- [ ] `an-005`  - [ ] `an-006`  - [ ] `an-007`  - [ ] `an-008`
- [ ] `an-009`  - [ ] `an-010`  - [ ] `an-011`  - [ ] `an-012`

## Status review

### Author draft

- Status: complete
- Catatan: Seluruh butir disusun independen berdasarkan spesifikasi konsep produk adaptasi internal.

### Language review manusia

- Status: pending
- Reviewer: ____________________
- Tanggal: ____________________
- Keputusan: ____________________
- Catatan/revisi: ____________________________________________________________

### Logic review manusia

- Status: pending
- Reviewer: ____________________
- Tanggal: ____________________
- Keputusan: ____________________
- Catatan/revisi: ____________________________________________________________

## Gate setelah review

Konten tidak boleh dipindahkan ke direktori dataset final produksi, diimpor, dibekukan, diaktifkan, atau dikirim ke frontend sebelum seluruh revisi manusia selesai dan approval berikutnya diberikan secara eksplisit.
