# Draft Konten Tahap 14 — RA dan ZR Numeric

> **INTERNAL REVIEW ONLY — MEMUAT KUNCI DAN SOLUSI, JANGAN DIPUBLIKASIKAN KE PESERTA**

Dokumen ini memuat draft orisinal RA dan ZR untuk **Tes Kemampuan Kognitif Adaptasi**. Konten ini dibuat untuk asesmen adaptasi internal dan tidak ditujukan sebagai reproduksi atau pengganti instrumen psikologi normatif.

## Status dan kontrak umum

- `author_draft`: complete
- `automated_language_review`: complete (self-review awal, bukan approval manusia)
- `automated_logic_review`: complete (self-review awal, bukan approval manusia)
- `human_language_review`: pending
- `human_logic_review`: passed
- `overall_status`: human_review_passed
- `active`: false
- `answer_type`: numeric
- Skor: exact canonical match `1`; tidak cocok `0`; kosong `0`/blank.
- Tidak ada partial score, tolerance, rounding, multi-key, atau jawaban alternatif.
- Heading level tiga setiap butir adalah field `logical_id` record.
- Example memakai `display_order=0`; scored memakai display order lokal 1–12.
- `numeric_answer_key` selalu berupa string bilangan bulat nonnegatif canonical tanpa satuan, tanda, desimal, leading zero, atau pemisah ribuan.
- Metadata setiap record: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

---

# RA — Perhitungan Kontekstual

**Petunjuk peserta:** Persoalan berikut adalah soal-soal hitungan. Bacalah setiap soal dengan cermat, hitung hasil akhirnya, lalu masukkan angka saja tanpa satuan atau pemisah ribuan.

**Sumber transkripsi:** Screenshot yang diberikan pemilik proyek; example dan soal sumber 77–88 ditranskripsikan tanpa mengambil soal 89–96. Nomor sumber hanya menjadi referensi internal; aplikasi menampilkan soal 1–12. Difficulty merupakan overlay aplikasi dan bukan bagian dari sumber.

**Kontrak subtes:** 1 example + 12 scored; `duration_seconds=360`; `max_item_score=1`; `max_subtest_score=12`.

## RA Example

### ra-example-001

- Contract: `subtest_code=RA`; `kind=example`; `display_order=0`; `difficulty_target=null`; `answer_type=numeric`.
- Prompt: Sebatang pensil harganya 25 rupiah. Berapakah harga 3 batang?
- `numeric_answer_key`: `"75"`.
- Explanation peserta: Harga tiga batang pensil adalah `25 × 3 = 75` rupiah. Masukkan `75` pada input jawaban.
- Solution internal: `25 × 3 = 75`.
- Rationale internal: Perkalian harga satu batang dengan tiga batang menghasilkan satu jawaban numerik.
- Difficulty basis: Example; perkalian langsung, tidak masuk distribusi difficulty.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

## RA Scored Questions

### ra-001

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=1`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Jika seorang anak memiliki 50 rupiah dan memberikan 15 rupiah kepada orang lain, berapa rupiahkah yang masih tinggal padanya?
- `numeric_answer_key`: `"35"`.
- Explanation: `null`.
- Solution internal: `50 − 15 = 35`.
- Rationale internal: Uang yang diberikan dikurangkan dari jumlah awal.
- Difficulty basis: Easy sesuai overlay aplikasi untuk soal sumber 77.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-002

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=2`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Berapa km-kah yang dapat ditempuh oleh kereta api dalam waktu 7 jam, jika kecepatannya 40 km/jam?
- `numeric_answer_key`: `"280"`.
- Explanation: `null`.
- Solution internal: `7 × 40 = 280`.
- Rationale internal: Jarak diperoleh dari waktu dikalikan kecepatan.
- Difficulty basis: Easy sesuai overlay aplikasi untuk soal sumber 78.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-003

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=3`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: 15 peti buah-buahan beratnya 250 kg dan setiap peti kosong beratnya 3 kg, berapakah berat buah-buahan itu?
- `numeric_answer_key`: `"205"`.
- Explanation: `null`.
- Solution internal: `250 − (15 × 3) = 250 − 45 = 205`.
- Rationale internal: Berat seluruh peti kosong dikurangkan dari berat total peti beserta buah.
- Difficulty basis: Easy sesuai overlay aplikasi untuk soal sumber 79.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-004

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=4`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Seseorang mempunyai persediaan rumput yang cukup untuk 7 ekor kuda selama 78 hari. Berapa harikah persediaan itu cukup untuk 21 ekor kuda?
- `numeric_answer_key`: `"26"`.
- Explanation: `null`.
- Solution internal: `(7 × 78) ÷ 21 = 26`.
- Rationale internal: Persediaan tetap setara dengan 546 kuda-hari; untuk 21 kuda cukup selama 26 hari.
- Difficulty basis: Easy sesuai overlay aplikasi untuk soal sumber 80.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-005

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=5`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: 3 batang coklat harganya Rp 5,-. Berapa batangkah yang dapat kita beli dengan Rp 50,-?
- `numeric_answer_key`: `"30"`.
- Explanation: `null`.
- Solution internal: `(50 ÷ 5) × 3 = 30`.
- Rationale internal: Lima puluh rupiah membeli sepuluh kelompok yang masing-masing berisi tiga batang.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 81.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-006

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=6`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Seseorang dapat berjalan 1,75 m dalam waktu ¼ detik. Berapakah meterkah yang dapat ia tempuh dalam waktu 10 detik?
- `numeric_answer_key`: `"70"`.
- Explanation: `null`.
- Solution internal: `1,75 ÷ ¼ × 10 = 7 × 10 = 70`.
- Rationale internal: Kecepatan berjalan adalah tujuh meter per detik, sehingga dalam sepuluh detik ditempuh 70 meter.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 82.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=source_transcription: pass`; `logic_review_notes=manual_arithmetic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-007

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=7`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Jika sebuah batu terletak 15 m di sebelah selatan dari sebatang pohon dan pohon itu berada 30 m di sebelah selatan dari sebuah rumah, berapa meterkah jarak antara batu dan rumah itu?
- `numeric_answer_key`: `"45"`.
- Explanation: `null`.
- Solution internal: `15 + 30 = 45`.
- Rationale internal: Batu berada 15 meter lebih jauh ke selatan dari pohon yang sudah 30 meter di selatan rumah.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 83.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-008

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=8`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Jika 4 ½ m bahan sandang harganya Rp 90,-, berapakah rupiahkah harganya 2 ½ m?
- `numeric_answer_key`: `"50"`.
- Explanation: `null`.
- Solution internal: `90 ÷ 4,5 × 2,5 = 20 × 2,5 = 50`.
- Rationale internal: Harga per meter adalah 20 rupiah, sehingga dua setengah meter berharga 50 rupiah.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 84.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-009

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=9`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: 7 orang dapat menyelesaikan sesuatu pekerjaan dalam 6 hari. Berapa orangkah yang diperlukan untuk menyelesaikan pekerjaan itu dalam setengah hari?
- `numeric_answer_key`: `"84"`.
- Explanation: `null`.
- Solution internal: `(7 × 6) ÷ 0,5 = 84`.
- Rationale internal: Pekerjaan memerlukan 42 orang-hari; agar selesai dalam setengah hari diperlukan 84 orang.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 85.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-010

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=10`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Karena dipanaskan, kawat yang panjangnya 48 cm akan mengembang menjadi 52 cm setelah pemanasan, berapakah panjangnya kawat yang berukuran 72 cm?
- `numeric_answer_key`: `"78"`.
- Explanation: `null`.
- Solution internal: `72 × (52 ÷ 48) = 78`.
- Rationale internal: Faktor pemuaian yang sama adalah 52/48; diterapkan pada kawat 72 cm menghasilkan 78 cm.
- Difficulty basis: Hard sesuai overlay aplikasi untuk soal sumber 86.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-011

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=11`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Suatu pabrik dapat menghasilkan 304 batang pensil dalam waktu 8 jam. Berapa batangkah dihasilkan dalam waktu setengah jam?
- `numeric_answer_key`: `"19"`.
- Explanation: `null`.
- Solution internal: `304 ÷ 8 × 0,5 = 38 × 0,5 = 19`.
- Rationale internal: Laju produksi adalah 38 batang per jam; dalam setengah jam dihasilkan 19 batang.
- Difficulty basis: Hard sesuai overlay aplikasi untuk soal sumber 87.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=source_transcription: pass`; `logic_review_notes=manual_arithmetic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-012

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=12`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Untuk suatu campuran diperlukan 2 bagian perak dan 3 bagian timah. Berapa gramkah perak yang diperlukan untuk mendapatkan campuran itu yang beratnya 15 gram?
- `numeric_answer_key`: `"6"`.
- Explanation: `null`.
- Solution internal: `2 ÷ (2 + 3) × 15 = 6`.
- Rationale internal: Perak merupakan dua dari lima bagian campuran, sehingga massanya dua perlima dari 15 gram.
- Difficulty basis: Hard sesuai overlay aplikasi untuk soal sumber 88.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### Rekap RA

- Record: 13 (1 example + 12 scored); maksimum skor 12.
- Difficulty scored: easy 4 (`ra-001, ra-002, ra-003, ra-004`); medium 5 (`ra-005, ra-006, ra-007, ra-008, ra-009`); hard 3 (`ra-010, ra-011, ra-012`).
- Canonical scored keys: `35, 280, 205, 26, 30, 70, 45, 50, 84, 78, 19, 6`.

---

# ZR — Deret Angka

**Petunjuk peserta:** Setiap deret tersusun menurut suatu aturan tertentu. Temukan aturannya, lalu masukkan angka berikutnya saja tanpa satuan atau pemisah.

**Sumber transkripsi:** Screenshot yang diberikan pemilik proyek; example dan deret sumber 97–108 ditranskripsikan tanpa mengambil soal 109–116. Nomor sumber hanya menjadi referensi internal; aplikasi menampilkan soal 1–12. Difficulty merupakan overlay aplikasi dan bukan bagian dari sumber.

**Kontrak subtes:** 1 example + 12 scored; `duration_seconds=360`; `max_item_score=1`; `max_subtest_score=12`.

## ZR Example

### zr-example-001

- Contract: `subtest_code=ZR`; `kind=example`; `display_order=0`; `difficulty_target=null`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 2, 4, 6, 8, 10, 12, 14, ?
- `numeric_answer_key`: `"16"`.
- Explanation peserta: Setiap angka bertambah 2, sehingga angka berikutnya adalah `14 + 2 = 16`. Masukkan `16` pada input jawaban.
- Solution internal: Selisih tetap `+2`; berikutnya `16`.
- Rationale internal: Tujuh suku menunjukkan aturan penambahan dua dengan satu kelanjutan.
- Difficulty basis: Example; penambahan tetap, tidak masuk distribusi difficulty.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

## ZR Scored Questions

### zr-001

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=1`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 6, 9, 12, 15, 18, 21, 24, ?
- `numeric_answer_key`: `"27"`.
- Explanation: `null`.
- Solution internal: Selisih tetap `+3`; `24 + 3 = 27`.
- Rationale internal: Seluruh transisi bertambah tiga.
- Difficulty basis: Easy sesuai overlay aplikasi untuk soal sumber 97.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-002

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=2`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 15, 16, 18, 19, 21, 22, 24, ?
- `numeric_answer_key`: `"25"`.
- Explanation: `null`.
- Solution internal: Penambahan bergantian `+1, +2`; setelah 24 berlaku `+1`, sehingga hasilnya 25.
- Rationale internal: Dua penambahan berselang-seling berulang secara konsisten.
- Difficulty basis: Easy sesuai overlay aplikasi untuk soal sumber 98.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-003

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=3`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 19, 18, 22, 21, 25, 24, 28, ?
- `numeric_answer_key`: `"27"`.
- Explanation: `null`.
- Solution internal: Operasi bergantian `−1, +4`; setelah 28 berlaku `−1`, sehingga hasilnya 27.
- Rationale internal: Pengurangan satu dan penambahan empat berulang secara konsisten.
- Difficulty basis: Easy sesuai overlay aplikasi untuk soal sumber 99.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-004

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=4`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 16, 12, 17, 13, 18, 14, 19, ?
- `numeric_answer_key`: `"15"`.
- Explanation: `null`.
- Solution internal: Operasi bergantian `−4, +5`; setelah 19 berlaku `−4`, sehingga hasilnya 15.
- Rationale internal: Pengurangan empat dan penambahan lima berulang secara konsisten.
- Difficulty basis: Easy sesuai overlay aplikasi untuk soal sumber 100.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=source_transcription: pass`; `logic_review_notes=manual_sequence_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-005

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=5`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 2, 4, 8, 10, 20, 22, 44, ?
- `numeric_answer_key`: `"46"`.
- Explanation: `null`.
- Solution internal: Operasi bergantian `×2, +2`; setelah 44 berlaku `+2`, sehingga hasilnya 46.
- Rationale internal: Perkalian dua dan penambahan dua berulang secara konsisten.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 101.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-006

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=6`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 15, 13, 16, 12, 17, 11, 18, ?
- `numeric_answer_key`: `"10"`.
- Explanation: `null`.
- Solution internal: Selisih bergantian `−2, +3, −4, +5, −6, +7`; berikutnya `−8`, sehingga `18 − 8 = 10`.
- Rationale internal: Besar pengurangan dan penambahan meningkat satu secara bergantian.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 102.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-007

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=7`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 25, 22, 11, 33, 30, 15, 45, ?
- `numeric_answer_key`: `"42"`.
- Explanation: `null`.
- Solution internal: Siklus operasi `−3, ÷2, ×3`; setelah 45 berlaku `−3`, sehingga hasilnya 42.
- Rationale internal: Tiga operasi berulang dua kali dan menentukan kelanjutan berikutnya.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 103.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-008

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=8`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 49, 51, 54, 27, 9, 11, 14, ?
- `numeric_answer_key`: `"7"`.
- Explanation: `null`.
- Solution internal: Siklus operasi `+2, +3, ÷2, ÷3`; setelah 14 berlaku `÷2`, sehingga hasilnya 7.
- Rationale internal: Empat operasi berulang dan bagian kedua siklus mengonfirmasi kelanjutannya.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 104.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-009

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=9`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 2, 3, 1, 3, 4, 2, 4, ?
- `numeric_answer_key`: `"5"`.
- Explanation: `null`.
- Solution internal: Siklus operasi `+1, −2, +2`; setelah 4 berlaku `+1`, sehingga hasilnya 5.
- Rationale internal: Tiga perubahan berulang dua kali dan menentukan kelanjutan berikutnya.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 105.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-010

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=10`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 19, 17, 20, 16, 21, 15, 22, ?
- `numeric_answer_key`: `"14"`.
- Explanation: `null`.
- Solution internal: Selisih bergantian `−2, +3, −4, +5, −6, +7`; berikutnya `−8`, sehingga `22 − 8 = 14`.
- Rationale internal: Besar pengurangan dan penambahan meningkat satu secara bergantian.
- Difficulty basis: Hard sesuai overlay aplikasi untuk soal sumber 106.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-011

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=11`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 94, 92, 46, 44, 22, 20, 10, ?
- `numeric_answer_key`: `"8"`.
- Explanation: `null`.
- Solution internal: Operasi bergantian `−2, ÷2`; setelah 10 berlaku `−2`, sehingga hasilnya 8.
- Rationale internal: Pengurangan dua dan pembagian dua berulang secara konsisten.
- Difficulty basis: Hard sesuai overlay aplikasi untuk soal sumber 107.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-012

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=12`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 5, 8, 9, 8, 11, 12, 11, ?
- `numeric_answer_key`: `"14"`.
- Explanation: `null`.
- Solution internal: Siklus operasi `+3, +1, −1`; setelah 11 berlaku `+3`, sehingga hasilnya 14.
- Rationale internal: Tiga perubahan berulang dua kali dan menentukan kelanjutan berikutnya.
- Difficulty basis: Hard sesuai overlay aplikasi untuk soal sumber 108.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### Rekap ZR

- Record: 13 (1 example + 12 scored); maksimum skor 12.
- Difficulty scored: easy 4 (`zr-001`–`zr-004`); medium 5 (`zr-005`–`zr-009`); hard 3 (`zr-010`–`zr-012`).
- Canonical scored keys: `27, 25, 27, 15, 46, 10, 42, 7, 5, 14, 8, 14`.

---

# Audit lintas subtes

- Total: 26 record (2 example + 24 scored).
- Seluruh record mempunyai tepat satu `numeric_answer_key` canonical.
- Seluruh key adalah string bilangan bulat nonnegatif tanpa format alternatif.
- Setiap subtes mempunyai difficulty scored 4 easy, 5 medium, dan 3 hard.
- Seluruh `qc_status=pass`; tidak ada record `revise` atau `reject` pada self-review awal.
- Status draft keseluruhan `human_review_passed`; seluruh record tetap `review_status=in_review` dan `active=false`, belum approved, belum frozen, dan belum diimpor.
