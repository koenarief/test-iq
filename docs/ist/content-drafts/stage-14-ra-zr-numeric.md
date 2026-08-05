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

**Petunjuk peserta:** Bacalah situasi hitung dengan cermat. Masukkan satu bilangan bulat sebagai jawaban. Jangan menuliskan satuan atau pemisah ribuan.

**Kontrak subtes:** 1 example + 12 scored; `duration_seconds=360`; `max_item_score=1`; `max_subtest_score=12`.

## RA Example

### ra-example-001

- Contract: `subtest_code=RA`; `kind=example`; `display_order=0`; `difficulty_target=null`; `answer_type=numeric`.
- Prompt: Sebuah rak mempunyai 4 tingkat. Setiap tingkat berisi 6 kotak. Berapa jumlah seluruh kotak? Masukkan angka saja.
- `numeric_answer_key`: `"24"`.
- Explanation peserta: Empat tingkat masing-masing berisi enam kotak, sehingga jumlahnya `4 × 6 = 24`.
- Solution internal: `4 × 6 = 24`.
- Rationale internal: Seluruh informasi diperlukan dan hanya menghasilkan satu bilangan bulat.
- Difficulty basis: Example; perkalian langsung, tidak masuk distribusi difficulty.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

## RA Scored Questions

### ra-001

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=1`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Terdapat 7 paket pensil. Setiap paket berisi 5 pensil. Berapa jumlah seluruh pensil? Masukkan angka saja.
- `numeric_answer_key`: `"35"`.
- Explanation: `null`.
- Solution internal: `7 × 5 = 35`.
- Rationale internal: Perkalian jumlah kelompok dan isi per kelompok menghasilkan satu jawaban.
- Difficulty basis: Satu operasi perkalian dengan bilangan kecil.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-002

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=2`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Sebanyak 48 apel dibagikan sama rata kepada 6 kelompok. Berapa apel yang diterima setiap kelompok? Masukkan angka saja.
- `numeric_answer_key`: `"8"`.
- Explanation: `null`.
- Solution internal: `48 ÷ 6 = 8`.
- Rationale internal: Frasa “sama rata” menentukan operasi pembagian tunggal.
- Difficulty basis: Satu operasi pembagian yang habis dibagi.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-003

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=3`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Sebuah buku memiliki 125 halaman. Raka membaca 38 halaman pada hari pertama dan 27 halaman pada hari kedua. Berapa halaman yang belum dibaca? Masukkan angka saja.
- `numeric_answer_key`: `"60"`.
- Explanation: `null`.
- Solution internal: `125 − 38 − 27 = 60`.
- Rationale internal: Dua jumlah yang telah dibaca harus dikurangkan dari total; tidak ada halaman yang dibaca ulang.
- Difficulty basis: Dua langkah pengurangan dengan informasi kontekstual.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-004

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=4`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Tiga bus masing-masing membawa 26 penumpang. Berapa jumlah seluruh penumpang? Masukkan angka saja.
- `numeric_answer_key`: `"78"`.
- Explanation: `null`.
- Solution internal: `3 × 26 = 78`.
- Rationale internal: Kapasitas aktual setiap bus dinyatakan sama dan seluruh penumpang dijumlahkan.
- Difficulty basis: Perkalian langsung satu langkah.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-005

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=5`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Empat buku catatan masing-masing berharga 7 token. Pembeli juga membayar biaya kemasan 5 token. Berapa total token yang dibayar? Masukkan angka saja.
- `numeric_answer_key`: `"33"`.
- Explanation: `null`.
- Solution internal: `(4 × 7) + 5 = 33`.
- Rationale internal: Harga barang dihitung terlebih dahulu, lalu biaya tetap ditambahkan satu kali.
- Difficulty basis: Menggabungkan perkalian dan penjumlahan dengan biaya tetap.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-006

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=6`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Enam kotak masing-masing berisi 18 botol. Sebanyak 36 botol dipindahkan. Sisa botol dibagikan sama rata ke 3 rak. Berapa botol pada setiap rak? Masukkan angka saja.
- `numeric_answer_key`: `"24"`.
- Explanation: `null`.
- Solution internal: `(6 × 18 − 36) ÷ 3 = 24`.
- Rationale internal: Jumlah awal adalah `6 × 18 = 108`; setelah 36 botol dipindahkan tersisa 72, lalu pembagian sama rata ke 3 rak menghasilkan 24 botol per rak.
- Difficulty basis: Tiga tahap operasi berupa perkalian, pengurangan, dan pembagian sama rata.
- Reviews: `ambiguity_review=pass (kata “masing-masing”, jumlah yang dipindahkan, dan pembagian sama rata menentukan satu urutan operasi)`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=human_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-007

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=7`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Sebuah mesin menghasilkan 15 komponen dalam 5 menit dengan laju tetap. Berapa komponen yang dihasilkan dalam 22 menit? Masukkan angka saja.
- `numeric_answer_key`: `"66"`.
- Explanation: `null`.
- Solution internal: `15 ÷ 5 = 3` komponen per menit; `3 × 22 = 66`.
- Rationale internal: Laju dinyatakan tetap sehingga perbandingan langsung mempunyai satu hasil.
- Difficulty basis: Dua langkah perhitungan laju dan proyeksi waktu.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-008

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=8`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Rata-rata lima nilai adalah 18. Empat nilai pertama adalah 12, 17, 19, dan 21. Berapa nilai kelima? Masukkan angka saja.
- `numeric_answer_key`: `"21"`.
- Explanation: `null`.
- Solution internal: Total lima nilai `5 × 18 = 90`; jumlah empat nilai `12 + 17 + 19 + 21 = 69`; nilai kelima `90 − 69 = 21`.
- Rationale internal: Definisi rata-rata menentukan total, lalu nilai yang hilang diperoleh dengan pengurangan.
- Difficulty basis: Memerlukan membalik konsep rata-rata dan melakukan beberapa operasi.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-009

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=9`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Usia Lina 9 tahun. Kakaknya 4 tahun lebih tua. Berapa usia kakak Lina? Masukkan angka saja.
- `numeric_answer_key`: `"13"`.
- Explanation: `null`.
- Solution internal: `9 + 4 = 13`.
- Rationale internal: “Lebih tua” menentukan penambahan selisih usia.
- Difficulty basis: Penjumlahan satu langkah dengan bilangan kecil.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-010

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=10`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Gudang mula-mula menyimpan 240 paket. Tiga kelompok yang masing-masing berisi 28 paket dikirim, lalu 35 paket baru diterima. Berapa paket yang tersimpan sekarang? Masukkan angka saja.
- `numeric_answer_key`: `"191"`.
- Explanation: `null`.
- Solution internal: `240 − (3 × 28) + 35 = 240 − 84 + 35 = 191`.
- Rationale internal: Jumlah terkirim dikurangkan dan penerimaan baru ditambahkan dalam urutan yang jelas.
- Difficulty basis: Tiga operasi dengan perubahan stok dua arah.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-011

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=11`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Sebuah gudang memiliki 72 paket. Setelah menerima 18 paket, sebanyak 20 paket dikirim. Sisa paket dibagi sama rata ke 5 rak. Berapa paket pada setiap rak? Masukkan angka saja.
- `numeric_answer_key`: `"14"`.
- Explanation: `null`.
- Solution internal: `(72 + 18 − 20) ÷ 5 = 14`.
- Rationale internal: Stok menjadi `72 + 18 = 90`, kemudian berkurang menjadi 70 setelah 20 paket dikirim; pembagian sama rata ke 5 rak menghasilkan 14 paket per rak.
- Difficulty basis: Tiga perubahan stok harus diterapkan berurutan sebelum pembagian sama rata.
- Reviews: `ambiguity_review=pass (urutan menerima, mengirim, lalu membagi dinyatakan eksplisit dan menghasilkan satu jawaban bulat)`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=human_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ra-012

- Contract: `subtest_code=RA`; `kind=scored`; `display_order=12`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Sebuah perjalanan terdiri dari tiga tahap selama 45 menit, 35 menit, dan 50 menit. Di antara tahap terdapat dua kali jeda selama 10 menit dan 15 menit. Berapa total menit perjalanan beserta jeda? Masukkan angka saja.
- `numeric_answer_key`: `"155"`.
- Explanation: `null`.
- Solution internal: `45 + 35 + 50 + 10 + 15 = 155`.
- Rationale internal: Semua durasi tahap dan kedua jeda dimasukkan tepat satu kali.
- Difficulty basis: Menyeleksi dan menjumlahkan lima durasi dari narasi.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### Rekap RA

- Record: 13 (1 example + 12 scored); maksimum skor 12.
- Difficulty scored: easy 4 (`ra-001, ra-002, ra-004, ra-009`); medium 5 (`ra-003, ra-005, ra-007, ra-010, ra-012`); hard 3 (`ra-006, ra-008, ra-011`).
- Canonical scored keys: `35, 8, 60, 78, 33, 24, 66, 21, 13, 191, 14, 155`.

---

# ZR — Deret Angka

**Petunjuk peserta:** Temukan aturan paling sederhana yang konsisten pada deret, lalu masukkan satu bilangan berikutnya. Masukkan angka saja.

**Kontrak subtes:** 1 example + 12 scored; `duration_seconds=360`; `max_item_score=1`; `max_subtest_score=12`.

## ZR Example

### zr-example-001

- Contract: `subtest_code=ZR`; `kind=example`; `display_order=0`; `difficulty_target=null`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 4, 7, 10, 13, ___. Masukkan angka saja.
- `numeric_answer_key`: `"16"`.
- Explanation peserta: Setiap angka bertambah 3, sehingga angka berikutnya adalah `13 + 3 = 16`.
- Solution internal: Selisih tetap `+3`; berikutnya `16`.
- Rationale internal: Lima posisi menunjukkan aturan aritmetika paling sederhana dengan satu kelanjutan.
- Difficulty basis: Example; selisih tetap langsung, tidak masuk distribusi difficulty.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

## ZR Scored Questions

### zr-001

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=1`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 6, 11, 16, 21, ___. Masukkan angka saja.
- `numeric_answer_key`: `"26"`.
- Explanation: `null`.
- Solution internal: Selisih tetap `+5`; `21 + 5 = 26`.
- Rationale internal: Empat transisi mendukung satu aturan selisih tetap yang sederhana.
- Difficulty basis: Penambahan tetap satu digit.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-002

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=2`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 3, 6, 12, 24, ___. Masukkan angka saja.
- `numeric_answer_key`: `"48"`.
- Explanation: `null`.
- Solution internal: Setiap angka dikalikan `2`; `24 × 2 = 48`.
- Rationale internal: Rasio tetap ditunjukkan pada seluruh transisi.
- Difficulty basis: Perkalian tetap yang mudah dikenali.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-003

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=3`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 45, 40, 35, 30, ___. Masukkan angka saja.
- `numeric_answer_key`: `"25"`.
- Explanation: `null`.
- Solution internal: Selisih tetap `−5`; `30 − 5 = 25`.
- Rationale internal: Seluruh transisi menggunakan pengurangan yang sama dan tetap nonnegatif.
- Difficulty basis: Pengurangan tetap langsung.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-004

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=4`; `difficulty_target=easy`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 1, 4, 9, 16, 25, ___. Masukkan angka saja.
- `numeric_answer_key`: `"36"`.
- Explanation: `null`.
- Solution internal: Suku adalah kuadrat berurutan `1², 2², 3², 4², 5²`; berikutnya `6² = 36`.
- Rationale internal: Lima suku tepat mengikuti kuadrat bilangan bulat berurutan, sehingga kelanjutan paling sederhana adalah kuadrat berikutnya.
- Difficulty basis: Pola kuadrat berurutan yang umum dengan lima suku pendukung.
- Reviews: `ambiguity_review=pass (lima kuadrat berurutan mendukung satu kelanjutan editorial yang sederhana)`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=human_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-005

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=5`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 5, 8, 14, 23, 35, ___. Masukkan angka saja.
- `numeric_answer_key`: `"50"`.
- Explanation: `null`.
- Solution internal: Selisih berturut-turut `+3, +6, +9, +12`; selisih berikutnya `+15`; `35 + 15 = 50`.
- Rationale internal: Selisih meningkat teratur sebesar 3 dan menggunakan cukup transisi untuk membedakan dari selisih tetap.
- Difficulty basis: Memerlukan analisis selisih tingkat pertama yang berubah teratur.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-006

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=6`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 2, 6, 12, 20, 30, ___. Masukkan angka saja.
- `numeric_answer_key`: `"42"`.
- Explanation: `null`.
- Solution internal: Selisih `+4, +6, +8, +10`; berikutnya `+12`; `30 + 12 = 42`.
- Rationale internal: Selisih berupa bilangan genap berurutan; setara dengan pola `n × (n+1)`.
- Difficulty basis: Selisih bertingkat dengan kenaikan dua.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-007

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=7`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 81, 27, 9, 3, ___. Masukkan angka saja.
- `numeric_answer_key`: `"1"`.
- Explanation: `null`.
- Solution internal: Setiap angka dibagi `3`; `3 ÷ 3 = 1`.
- Rationale internal: Rasio pembagian tetap berlaku pada semua transisi dan menghasilkan bilangan bulat.
- Difficulty basis: Deret menurun dengan pembagian tetap.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-008

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=8`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 4, 9, 19, 39, 79, ___. Masukkan angka saja.
- `numeric_answer_key`: `"159"`.
- Explanation: `null`.
- Solution internal: Setiap angka dikalikan `2` lalu ditambah `1`; `79 × 2 + 1 = 159`.
- Rationale internal: Aturan gabungan yang sama menghasilkan seluruh suku setelah suku pertama.
- Difficulty basis: Memerlukan identifikasi operasi dua tahap berulang.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-009

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=9`; `difficulty_target=medium`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 100, 96, 88, 76, 60, ___. Masukkan angka saja.
- `numeric_answer_key`: `"40"`.
- Explanation: `null`.
- Solution internal: Pengurangan berturut-turut `4, 8, 12, 16`; berikutnya `20`; `60 − 20 = 40`.
- Rationale internal: Besar pengurangan bertambah tetap sebesar 4 dan kelanjutan tetap nonnegatif.
- Difficulty basis: Memerlukan analisis selisih negatif yang berubah teratur.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-010

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=10`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 3, 4, 7, 11, 18, 29, ___. Masukkan angka saja.
- `numeric_answer_key`: `"47"`.
- Explanation: `null`.
- Solution internal: Mulai suku ketiga, setiap suku adalah jumlah dua suku sebelumnya; `18 + 29 = 47`.
- Rationale internal: Empat suku berturut-turut memverifikasi aturan rekursif yang sama.
- Difficulty basis: Memerlukan pengenalan relasi antar dua suku sebelumnya, bukan selisih tunggal.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-011

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=11`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 2, 5, 4, 10, 6, 15, 8, ___. Masukkan angka saja.
- `numeric_answer_key`: `"20"`.
- Explanation: `null`.
- Solution internal: Posisi ganjil `2, 4, 6, 8` bertambah 2; posisi genap `5, 10, 15, 20` bertambah 5.
- Rationale internal: Dua deret berselang-seling masing-masing mempunyai aturan aritmetika sederhana dan menentukan posisi berikutnya secara tunggal.
- Difficulty basis: Memerlukan pemisahan dua pola interleaved.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### zr-012

- Contract: `subtest_code=ZR`; `kind=scored`; `display_order=12`; `difficulty_target=hard`; `answer_type=numeric`.
- Prompt: Tentukan angka berikutnya: 1, 2, 6, 15, 31, 56, ___. Masukkan angka saja.
- `numeric_answer_key`: `"92"`.
- Explanation: `null`.
- Solution internal: Selisih berturut-turut `1, 4, 9, 16, 25` atau `1², 2², 3², 4², 5²`; selisih berikutnya `6² = 36`; `56 + 36 = 92`.
- Rationale internal: Lima selisih membentuk kuadrat berurutan dan memberikan satu kelanjutan paling sederhana.
- Difficulty basis: Memerlukan analisis selisih dan pengenalan pola kuadrat tingkat kedua.
- Reviews: `ambiguity_review=pass`; `canonical_answer_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### Rekap ZR

- Record: 13 (1 example + 12 scored); maksimum skor 12.
- Difficulty scored: easy 4 (`zr-001`–`zr-004`); medium 5 (`zr-005`–`zr-009`); hard 3 (`zr-010`–`zr-012`).
- Canonical scored keys: `26, 48, 25, 36, 50, 42, 1, 159, 40, 47, 20, 92`.

---

# Audit lintas subtes

- Total: 26 record (2 example + 24 scored).
- Seluruh record mempunyai tepat satu `numeric_answer_key` canonical.
- Seluruh key adalah string bilangan bulat nonnegatif tanpa format alternatif.
- Setiap subtes mempunyai difficulty scored 4 easy, 5 medium, dan 3 hard.
- Seluruh `qc_status=pass`; tidak ada record `revise` atau `reject` pada self-review awal.
- Status draft keseluruhan `human_review_passed`; seluruh record tetap `review_status=in_review` dan `active=false`, belum approved, belum frozen, dan belum diimpor.
