# Draft Konten Tahap 13 — GE Berbobot 0–4

> **INTERNAL REVIEW ONLY — MEMUAT BOBOT DAN KUNCI, JANGAN DIPUBLIKASIKAN KE PESERTA**

Dokumen ini memuat 1 example dan 10 scored questions GE untuk **Tes Kemampuan Kognitif Adaptasi**. Konten disusun independen untuk menemukan persamaan utama atau konsep bersama dari dua objek/gagasan. Konten ini dibuat untuk asesmen adaptasi internal dan tidak ditujukan sebagai reproduksi atau pengganti instrumen psikologi normatif.

## Status dan kontrak

- `author_draft`: complete
- `automated_language_review`: complete (self-review awal, bukan approval manusia)
- `automated_logic_review`: complete (self-review awal, bukan approval manusia)
- `human_language_review`: pending
- `human_logic_review`: pending
- `human_weight_review`: passed
- `overall_status`: human_review_passed
- `active`: false
- `answer_type`: single_choice_weighted
- `duration_seconds`: 300
- `max_item_score`: 4
- `max_subtest_score`: 40
- Heading level tiga setiap butir adalah field `logical_id` record.
- Example memakai `display_order=0`; scored memakai display order lokal 1–10.
- Setiap record mempunyai tepat satu opsi untuk masing-masing `score_value` 4, 3, 2, 1, dan 0.
- Hanya opsi skor 4 memakai `is_correct=true`; skor 0–3 memakai `is_correct=false`.
- Metadata setiap record: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.
- Bobot 0–4 adalah hierarki editorial internal dan tidak mempunyai makna normatif.

## Petunjuk peserta

Setiap soal menampilkan dua konsep. Pilih opsi yang paling tepat menjelaskan persamaan utama keduanya. Beberapa opsi mungkin berkaitan, tetapi pilih persamaan yang paling spesifik dan paling bermakna bagi kedua konsep.

## Example

### ge-example-001

- Contract: `subtest_code=GE`; `kind=example`; `display_order=0`; `difficulty_target=null`; `answer_type=single_choice_weighted`.
- Prompt: Apakah persamaan utama antara jam dinding dan kalender?
- Options:
  - A. `text=Benda yang dapat dipasang pada dinding`; `score_value=1`; `is_correct=false`.
  - B. `text=Alat bantu untuk mengatur dan memahami waktu`; `score_value=4`; `is_correct=true`.
  - C. `text=Penyaji informasi yang dapat dilihat`; `score_value=3`; `is_correct=false`.
  - D. `text=Benda yang memuat angka atau tanda`; `score_value=2`; `is_correct=false`.
  - E. `text=Alat untuk mengukur suhu ruangan`; `score_value=0`; `is_correct=false`.
- Best answer: **B** (`4`).
- Explanation peserta: Keduanya membantu memahami dan mengatur waktu. Kalender menunjukkan hari atau tanggal, sedangkan jam menunjukkan waktu dalam sehari.
- Rationale internal: Skor 4 menangkap fungsi temporal bersama. Skor 3 benar tetapi terlalu umum; skor 2 hanya ciri permukaan; skor 1 bersifat kemungkinan penempatan; skor 0 tidak sesuai fungsi.
- Difficulty basis: Example; persamaan fungsi umum dan tidak masuk distribusi difficulty.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

## Scored Questions

### ge-001

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=1`; `difficulty_target=easy`; `answer_type=single_choice_weighted`.
- Prompt: Apakah persamaan utama antara payung dan jas hujan?
- Options:
  - A. `text=Perlengkapan untuk menghadapi kondisi cuaca`; `score_value=3`; `is_correct=false`.
  - B. `text=Alat untuk mengeringkan pakaian basah`; `score_value=0`; `is_correct=false`.
  - C. `text=Perlengkapan yang melindungi tubuh dari air hujan`; `score_value=4`; `is_correct=true`.
  - D. `text=Barang yang biasa digunakan di luar ruang`; `score_value=2`; `is_correct=false`.
  - E. `text=Benda yang dapat disimpan setelah digunakan`; `score_value=1`; `is_correct=false`.
- Best answer: **C** (`4`). Explanation: `null`.
- Rationale internal: Skor 4 menyebut fungsi dan ancaman yang sama secara tepat. Skor 3 adalah kategori fungsi lebih luas; skor 2 adalah konteks penggunaan; skor 1 ciri generik; skor 0 berlawanan dengan fungsi perlindungan.
- Difficulty basis: Persamaan konkret sehari-hari dengan opsi terbaik yang langsung.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-002

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=2`; `difficulty_target=easy`; `answer_type=single_choice_weighted`.
- Prompt: Apakah persamaan utama antara sepeda dan perahu dayung?
- Options:
  - A. `text=Alat transportasi yang digerakkan dengan tenaga manusia`; `score_value=4`; `is_correct=true`.
  - B. `text=Benda yang sering dipakai untuk kegiatan luar ruang`; `score_value=2`; `is_correct=false`.
  - C. `text=Barang yang dapat dipinjam atau disewakan`; `score_value=1`; `is_correct=false`.
  - D. `text=Kendaraan yang mengandalkan mesin pembakaran`; `score_value=0`; `is_correct=false`.
  - E. `text=Alat yang memindahkan orang dari satu tempat ke tempat lain`; `score_value=3`; `is_correct=false`.
- Best answer: **A** (`4`). Explanation: `null`.
- Rationale internal: Skor 4 menambahkan mekanisme penggerak yang sama pada kategori transportasi. Skor 3 benar tetapi lebih umum; skor 2 hanya konteks; skor 1 kemungkinan kepemilikan; skor 0 salah.
- Difficulty basis: Objek konkret dan fungsi transportasi yang mudah dikenali.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-003

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=3`; `difficulty_target=easy`; `answer_type=single_choice_weighted`.
- Prompt: Apakah persamaan utama antara pensil dan kapur tulis?
- Options:
  - A. `text=Benda yang umumnya berbentuk memanjang`; `score_value=1`; `is_correct=false`.
  - B. `text=Alat yang dapat digunakan untuk menulis atau menggambar`; `score_value=3`; `is_correct=false`.
  - C. `text=Alat tulis yang menggunakan tinta cair`; `score_value=0`; `is_correct=false`.
  - D. `text=Perlengkapan yang dapat digunakan dalam kegiatan belajar`; `score_value=2`; `is_correct=false`.
  - E. `text=Alat untuk menulis atau menggambar tanpa menggunakan tinta`; `score_value=4`; `is_correct=true`.
- Best answer: **E** (`4`). Explanation: `null`.
- Rationale internal: Skor 4 menyatakan fungsi bersama sekaligus pembeda mekanisme tanpa tinta. Skor 3 benar tetapi kurang spesifik; skor 2 kategori penggunaan; skor 1 bentuk permukaan; skor 0 bertentangan.
- Difficulty basis: Fungsi konkret dan perbedaan tinta yang familiar.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-004

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=4`; `difficulty_target=medium`; `answer_type=single_choice_weighted`.
- Prompt: Apakah persamaan utama antara peta dan denah?
- Options:
  - A. `text=Dokumen visual yang dapat dicetak pada kertas`; `score_value=2`; `is_correct=false`.
  - B. `text=Representasi visual tentang lokasi dan hubungan ruang`; `score_value=4`; `is_correct=true`.
  - C. `text=Sumber informasi yang membantu menemukan tempat`; `score_value=3`; `is_correct=false`.
  - D. `text=Benda yang mungkin dilipat saat disimpan`; `score_value=1`; `is_correct=false`.
  - E. `text=Rekaman yang menyimpan suara suatu tempat`; `score_value=0`; `is_correct=false`.
- Best answer: **B** (`4`). Explanation: `null`.
- Rationale internal: Skor 4 menangkap sifat representasi spasial. Skor 3 menyatakan salah satu fungsi; skor 2 media penyajian; skor 1 sifat fisik yang tidak wajib; skor 0 tidak sesuai.
- Difficulty basis: Memerlukan pembedaan konsep representasi, fungsi navigasi, dan media fisik.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-005

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=5`; `difficulty_target=medium`; `answer_type=single_choice_weighted`.
- Prompt: Apakah persamaan utama antara termometer dan speedometer?
- Options:
  - A. `text=Perangkat utama untuk mengirim pesan jarak jauh`; `score_value=0`; `is_correct=false`.
  - B. `text=Benda buatan yang digunakan dalam aktivitas manusia`; `score_value=1`; `is_correct=false`.
  - C. `text=Alat yang memberikan informasi melalui skala atau indikator`; `score_value=3`; `is_correct=false`.
  - D. `text=Alat ukur yang menyatakan hasil pengukuran sebagai nilai`; `score_value=4`; `is_correct=true`.
  - E. `text=Perangkat yang membantu pengguna memperoleh informasi`; `score_value=2`; `is_correct=false`.
- Best answer: **D** (`4`). Explanation: `null`.
- Rationale internal: Skor 4 mengidentifikasi keduanya sebagai alat ukur dengan keluaran nilai. Skor 3 hampir tepat tetapi tidak menyatakan pengukuran; skor 2 lebih umum; skor 1 generik; skor 0 salah fungsi.
- Difficulty basis: Persamaan fungsi perlu dibedakan dari cara tampilan dan kategori perangkat umum.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-006

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=6`; `difficulty_target=medium`; `answer_type=single_choice_weighted`.
- Prompt: Apakah persamaan utama antara akar dan fondasi?
- Options:
  - A. `text=Bagian dasar yang menopang dan membantu menjaga kestabilan keseluruhan`; `score_value=3`; `is_correct=false`.
  - B. `text=Hiasan yang ditempatkan pada bagian paling atas`; `score_value=0`; `is_correct=false`.
  - C. `text=Struktur dasar yang mengikat keseluruhan pada tempatnya sekaligus menopangnya`; `score_value=4`; `is_correct=true`.
  - D. `text=Bagian yang biasanya berada dekat permukaan bawah`; `score_value=1`; `is_correct=false`.
  - E. `text=Unsur struktur yang menjadi bagian dari sesuatu yang lebih besar`; `score_value=2`; `is_correct=false`.
- Best answer: **C** (`4`). Explanation: `null`.
- Rationale internal: Skor 4 memuat dua fungsi bersama: menambatkan dan menopang. Skor 3 benar tetapi kurang lengkap; skor 2 kategori bagian; skor 1 hanya posisi; skor 0 berlawanan.
- Difficulty basis: Persamaan lintas domain biologis dan konstruksi memerlukan abstraksi fungsi.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-007

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=7`; `difficulty_target=medium`; `answer_type=single_choice_weighted`.
- Prompt: Apakah persamaan utama antara resep masakan dan petunjuk perakitan?
- Options:
  - A. `text=Informasi tertulis yang dapat dibaca berulang kali`; `score_value=2`; `is_correct=false`.
  - B. `text=Bahan informasi yang dapat disimpan untuk digunakan kembali`; `score_value=1`; `is_correct=false`.
  - C. `text=Cerita rekaan yang dibuat untuk menghibur pembaca`; `score_value=0`; `is_correct=false`.
  - D. `text=Panduan praktis untuk melakukan suatu kegiatan`; `score_value=3`; `is_correct=false`.
  - E. `text=Urutan langkah yang diikuti untuk menghasilkan hasil tertentu`; `score_value=4`; `is_correct=true`.
- Best answer: **E** (`4`). Explanation: `null`.
- Rationale internal: Skor 4 menangkap struktur berurutan dan orientasi hasil. Skor 3 benar tetapi lebih luas; skor 2 media informasi; skor 1 menunjukkan bahwa keduanya dapat disimpan dan digunakan kembali, tetapi hal tersebut hanya merupakan ciri praktis yang lemah dan tidak menjelaskan struktur langkah ataupun orientasi hasil; skor 0 salah jenis teks.
- Difficulty basis: Memerlukan pemilihan ciri definisional dibanding kategori panduan yang lebih umum.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-008

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=8`; `difficulty_target=hard`; `answer_type=single_choice_weighted`.
- Prompt: Apakah persamaan utama antara kompas dan prinsip?
- Options:
  - A. `text=Acuan yang membantu menjaga arah ketika menentukan langkah`; `score_value=4`; `is_correct=true`.
  - B. `text=Sumber informasi yang dapat dipertimbangkan`; `score_value=2`; `is_correct=false`.
  - C. `text=Sesuatu yang dapat dimiliki atau digunakan seseorang`; `score_value=1`; `is_correct=false`.
  - D. `text=Panduan yang membantu seseorang memilih tindakan`; `score_value=3`; `is_correct=false`.
  - E. `text=Alat yang digunakan untuk menghitung jumlah benda`; `score_value=0`; `is_correct=false`.
- Best answer: **A** (`4`). Explanation: `null`.
- Rationale internal: Kompas menjaga arah perjalanan secara literal, sedangkan prinsip menjaga arah tindakan secara figuratif. Skor 3 menangkap fungsi panduan tetapi tidak mempertahankan konsep arah; skor 2 dan 1 makin umum; skor 0 tidak sesuai.
- Difficulty basis: Memerlukan abstraksi literal–figuratif dengan dua opsi fungsi yang semantik dekat.
- Reviews: `ambiguity_review=pass` (skor 4 mempertahankan unsur “arah” yang menjadi jembatan konsep); `weight_hierarchy_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-009

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=9`; `difficulty_target=hard`; `answer_type=single_choice_weighted`.
- Prompt: Apakah persamaan utama antara saringan dan editor?
- Options:
  - A. `text=Bagian yang terlibat dalam suatu proses kerja`; `score_value=1`; `is_correct=false`.
  - B. `text=Alat untuk memasukkan semua bahan tanpa pemilihan`; `score_value=0`; `is_correct=false`.
  - C. `text=Sarana yang dapat membantu memperbaiki hasil`; `score_value=2`; `is_correct=false`.
  - D. `text=Penyeleksi yang mempertahankan bagian sesuai kriteria dan menyisihkan bagian lain`; `score_value=4`; `is_correct=true`.
  - E. `text=Pihak atau alat yang menyingkirkan bagian yang tidak diinginkan`; `score_value=3`; `is_correct=false`.
- Best answer: **D** (`4`). Explanation: `null`.
- Rationale internal: Keduanya melakukan seleksi berdasar kriteria, bukan sekadar membuang. Skor 3 hanya menangkap penghilangan; skor 2 hasil umum; skor 1 sangat luas; skor 0 berlawanan.
- Difficulty basis: Persamaan mekanisme lintas objek fisik dan peran abstrak, dengan dua tingkat jawaban seleksi yang dekat.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-010

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=10`; `difficulty_target=hard`; `answer_type=single_choice_weighted`.
- Prompt: Apakah persamaan utama antara benih dan gagasan?
- Options:
  - A. `text=Hasil akhir yang tidak lagi mengalami perubahan`; `score_value=0`; `is_correct=false`.
  - B. `text=Awal yang menyimpan potensi untuk berkembang menjadi sesuatu yang lebih besar`; `score_value=4`; `is_correct=true`.
  - C. `text=Tahap awal dari suatu proses perkembangan`; `score_value=3`; `is_correct=false`.
  - D. `text=Sesuatu yang dapat menjadi sumber perubahan`; `score_value=2`; `is_correct=false`.
  - E. `text=Sesuatu yang dapat disimpan sebelum dikembangkan lebih lanjut`; `score_value=1`; `is_correct=false`.
- Best answer: **B** (`4`). Explanation: `null`.
- Rationale internal: Benih berkembang menjadi tumbuhan dan gagasan berkembang menjadi karya atau tindakan; skor 4 menangkap awal sekaligus potensi. Skor 3 hanya tahap; skor 2 menggambarkan dampak umum. Pada skor 1, benih dapat disimpan sebelum ditanam, sedangkan gagasan dapat dicatat atau disimpan sebelum dikembangkan; kesamaan ini bersifat praktis dan bukan inti hubungan keduanya. Skor 0 berlawanan.
- Difficulty basis: Persamaan abstrak lintas wujud fisik dan mental dengan hierarki kedekatan makna.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

## Rekap audit draft

- Record: 11 (1 example + 10 scored).
- Opsi: 55; setiap record tepat lima opsi A–E.
- Setiap record memiliki tepat satu skor 4, satu skor 3, satu skor 2, satu skor 1, dan satu skor 0.
- Setiap record memiliki tepat satu `is_correct=true`, selalu pada skor 4.
- Difficulty scored: easy 3 (`ge-001`–`ge-003`); medium 4 (`ge-004`–`ge-007`); hard 3 (`ge-008`–`ge-010`).
- Distribusi posisi best answer scored: A=2, B=2, C=2, D=2, E=2.
- Urutan posisi best answer: C–A–E–B–D–C–E–A–D–B.
- Seluruh `qc_status=pass`; tidak ada record `revise` atau `reject` pada self-review awal.
- Seluruh record `review_status=in_review` dan `active=false`; tidak ada approval atau freeze.
- Status review draft: `human_review_passed`; `active=false`; belum approved dan belum frozen.
