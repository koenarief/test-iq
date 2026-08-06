# Checklist Review Manusia Tahap 15 — FA dan WU

> INTERNAL REVIEW — NOT ACTIVE. Human review telah lulus dengan `overall_status=human_review_passed`; dokumen ini bukan approval atau freeze. Seluruh record tetap `active=false` dan belum diimpor.

## Rekap kontrak

| Subtes | Example | Scored | Opsi/record | Media | Easy | Medium | Hard | Durasi | Maks. skor |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| FA | 1 | 10 | 5 | 66 | 3 | 4 | 3 | 240 detik | 10 |
| WU | 1 | 12 | 5 | 78 | 4 | 5 | 3 | 360 detik | 12 |
| **Total** | **2** | **22** | — | **144** | **7** | **9** | **6** | — | **22** |

## Checklist umum per record

- [x] Logical ID, kind, dan display order tepat serta unik.
- [x] Prompt dan lima opsi dapat dibaca pada desktop serta mobile.
- [x] Tepat satu opsi correct dengan skor 1; empat opsi lain skor 0.
- [x] Alt text netral dan nama file tidak membocorkan key.
- [x] Aset monokrom, tajam, dan tidak memakai warna sebagai petunjuk.
- [x] Difficulty diperlakukan sebagai label editorial.
- [x] Konten terlihat orisinal dan tidak menyerupai instrumen lain.
- [x] Metadata lengkap; `review_status=human_review_passed`; `active=false`.

## Review FA

- [x] Seluruh potongan prompt dipakai tepat satu kali pada opsi benar.
- [x] Tidak ada overlap, peregangan, penambahan, atau penghilangan bagian.
- [x] Rotasi legal; refleksi tidak digunakan pada opsi benar.
- [x] Empat distraktor mustahil secara geometris dan mempunyai rationale berbeda.
- [x] Tidak ada opsi kedua yang lolos penyusunan.
- [x] Audit area, komposisi, topologi, rotasi, dan refleksi dapat ditelusuri.
- [x] Lekukan dan fragmen tetap jelas pada ukuran kecil.

### Sign-off FA

- [x] `fa-example-001`
- [x] `fa-001`
- [x] `fa-002`
- [x] `fa-003`
- [x] `fa-004`
- [x] `fa-005`
- [x] `fa-006`
- [x] `fa-007`
- [x] `fa-008`
- [x] `fa-009`
- [x] `fa-010`

## Review WU

- [x] Enam simbol pada setiap kubus unik dan mudah dibedakan.
- [x] Dua reference views konsisten dengan mapping enam sisi.
- [x] Tepat satu opsi merupakan rotasi proper.
- [x] Distraktor opposite-face dan mirror/chirality benar-benar invalid.
- [x] Adjacency, opposite-face, urutan siklik, dan orientasi simbol konsisten.
- [x] Rotation proof dapat ditelusuri tanpa membuka key kepada peserta.

### Sign-off WU

- [x] `wu-example-001`
- [x] `wu-001`
- [x] `wu-002`
- [x] `wu-003`
- [x] `wu-004`
- [x] `wu-005`
- [x] `wu-006`
- [x] `wu-007`
- [x] `wu-008`
- [x] `wu-009`
- [x] `wu-010`
- [x] `wu-011`
- [x] `wu-012`

## Review manusia

### Visual

- Status: passed
- Reviewer: ____________________
- Tanggal: ____________________
- Keputusan: `human_review_passed`
- Record revise/reject: tidak ada
- Catatan: Seluruh prompt dan opsi FA/WU terbaca jelas; tidak ditemukan gambar terpotong, petunjuk visual tidak sengaja, atau masalah skala.

### Geometri FA

- Status: passed
- Reviewer: ____________________
- Tanggal: ____________________
- Keputusan: `human_review_passed`
- Record revise/reject: tidak ada
- Catatan: Kejelasan potongan, opsi, rotasi legal, dan ketiadaan opsi ganda telah diperiksa manusia.

### Rotasi WU

- Status: passed
- Reviewer: ____________________
- Tanggal: ____________________
- Keputusan: `human_review_passed`
- Record revise/reject: tidak ada
- Catatan: Kubus, simbol, orientasi, adjacency, dan mirror/chirality telah diperiksa manusia.

### Bahasa dan logika

- Status: passed
- Reviewer: ____________________
- Tanggal: ____________________
- Keputusan: `human_review_passed`
- Record revise/reject: tidak ada
- Catatan: Bahasa petunjuk dan logika satu jawaban deterministik dinyatakan lulus review manusia.

## Gate

Draft tidak boleh dipindahkan ke dataset final produksi, diimpor, dibekukan, diaktifkan, atau dikirim kepada peserta sebelum review manusia dan approval tahap berikutnya selesai.

