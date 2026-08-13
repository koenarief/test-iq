# Draft Konten Tahap 13 — GE Berbobot 0–3

> **INTERNAL REVIEW ONLY — MEMUAT BOBOT DAN KUNCI, JANGAN DIPUBLIKASIKAN KE PESERTA**

Dokumen ini memuat 1 example dan 10 scored questions GE untuk **Tes Kemampuan Kognitif Adaptasi**. Pasangan kata ditranskripsikan dari screenshot yang diberikan pemilik proyek: example pertama serta soal sumber 61–70. Nomor sumber hanya menjadi referensi transkripsi; urutan aplikasi tetap 1–10 dan soal 71–76 tidak digunakan.

## Status dan kontrak

- `overall_status`: in_review
- `active`: false
- `answer_type`: single_choice_weighted
- `duration_seconds`: 300
- `max_item_score`: 3
- `raw_maximum`: 30
- `weighted_maximum`: 60
- Example memakai `display_order=0`; scored memakai display order lokal 1–10.
- Setiap record mempunyai tepat satu opsi skor 3, satu opsi skor 2, satu opsi skor 1, dan dua opsi skor 0.
- Hanya opsi skor 3 memakai `is_correct=true`; skor 0–2 memakai `is_correct=false`.
- Metadata setiap record: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.
- Bobot 0–3 adalah hierarki editorial internal dan tidak mempunyai makna normatif.

## Petunjuk peserta

Pilih kata yang paling tepat mencakup pengertian kedua kata berikut. Beberapa pilihan mungkin masih berhubungan, tetapi pilih konsep yang paling tepat dan paling spesifik untuk keduanya.

## Example

### ge-example-001

- Contract: `subtest_code=GE`; `kind=example`; `display_order=0`; `difficulty_target=null`; `answer_type=single_choice_weighted`.
- Prompt: Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: ayam — itik.
- Options:
  - A. `text=Hewan`; `score_value=1`; `is_correct=false`.
  - B. `text=Burung`; `score_value=3`; `is_correct=true`.
  - C. `text=Hewan ternak`; `score_value=2`; `is_correct=false`.
  - D. `text=Petelur`; `score_value=0`; `is_correct=false`.
  - E. `text=Kandang`; `score_value=0`; `is_correct=false`.
- Best answer: **B** (`3`).
- Explanation peserta: Ayam dan itik sama-sama termasuk burung.
- Rationale internal: Burung merupakan konsep yang langsung mencakup keduanya. Hewan ternak dekat tetapi bergantung konteks pemeliharaan; hewan terlalu umum; petelur hanya sifat sebagian individu dan kandang adalah tempat.
- Difficulty basis: Example; tidak masuk distribusi difficulty.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=source_transcription_and_internal_options: pass`; `logic_review_notes=manual_semantic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

## Scored Questions

### ge-001

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=1`; `difficulty_target=easy`; `answer_type=single_choice_weighted`.
- Prompt: Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: mawar — melati.
- Options:
  - A. `text=Tanaman`; `score_value=1`; `is_correct=false`.
  - B. `text=Bunga`; `score_value=3`; `is_correct=true`.
  - C. `text=Tumbuhan hias`; `score_value=2`; `is_correct=false`.
  - D. `text=Kebun`; `score_value=0`; `is_correct=false`.
  - E. `text=Harum`; `score_value=0`; `is_correct=false`.
- Best answer: **B** (`3`). Explanation: `null`.
- Rationale internal: Bunga adalah kategori langsung keduanya. Tumbuhan hias dekat tetapi berbasis penggunaan; tanaman lebih umum; kebun adalah tempat dan harum adalah sifat yang tidak mencakup semua individu.
- Difficulty basis: Easy sesuai overlay aplikasi untuk soal sumber 61.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=source_transcription_and_internal_options: pass`; `logic_review_notes=manual_semantic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-002

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=2`; `difficulty_target=easy`; `answer_type=single_choice_weighted`.
- Prompt: Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: mata — telinga.
- Options:
  - A. `text=Indra`; `score_value=2`; `is_correct=false`.
  - B. `text=Organ tubuh`; `score_value=1`; `is_correct=false`.
  - C. `text=Kepala`; `score_value=0`; `is_correct=false`.
  - D. `text=Pancaindra`; `score_value=3`; `is_correct=true`.
  - E. `text=Wajah`; `score_value=0`; `is_correct=false`.
- Best answer: **D** (`3`). Explanation: `null`.
- Rationale internal: Pancaindra adalah kategori paling spesifik. Indra sangat dekat tetapi lebih umum; organ tubuh benar namun luas; kepala dan wajah hanya lokasi yang tidak menjadi kategori keduanya.
- Difficulty basis: Easy sesuai overlay aplikasi untuk soal sumber 62.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=source_transcription_and_internal_options: pass`; `logic_review_notes=manual_semantic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-003

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=3`; `difficulty_target=easy`; `answer_type=single_choice_weighted`.
- Prompt: Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: gula — intan.
- Options:
  - A. `text=Benda padat`; `score_value=1`; `is_correct=false`.
  - B. `text=Benda bening`; `score_value=2`; `is_correct=false`.
  - C. `text=Kristal`; `score_value=3`; `is_correct=true`.
  - D. `text=Mineral`; `score_value=0`; `is_correct=false`.
  - E. `text=Perhiasan`; `score_value=0`; `is_correct=false`.
- Best answer: **C** (`3`). Explanation: `null`.
- Rationale internal: Gula dan intan dapat berbentuk kristal. Bening merupakan kemiripan tampak yang cukup dekat tetapi tidak selalu berlaku; benda padat terlalu umum; mineral dan perhiasan hanya tepat untuk intan.
- Difficulty basis: Easy sesuai overlay aplikasi untuk soal sumber 63.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=source_transcription_and_internal_options: pass`; `logic_review_notes=manual_semantic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-004

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=4`; `difficulty_target=medium`; `answer_type=single_choice_weighted`.
- Prompt: Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: hujan — salju.
- Options:
  - A. `text=Presipitasi`; `score_value=3`; `is_correct=true`.
  - B. `text=Fenomena cuaca`; `score_value=2`; `is_correct=false`.
  - C. `text=Air`; `score_value=1`; `is_correct=false`.
  - D. `text=Awan`; `score_value=0`; `is_correct=false`.
  - E. `text=Dingin`; `score_value=0`; `is_correct=false`.
- Best answer: **A** (`3`). Explanation: `null`.
- Rationale internal: Keduanya merupakan bentuk presipitasi. Fenomena cuaca benar tetapi lebih luas; air menyebut bahan dasarnya secara lemah; awan berkaitan sebagai asal dan dingin tidak wajib bagi hujan.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 64.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=source_transcription_and_internal_options: pass`; `logic_review_notes=manual_semantic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-005

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=5`; `difficulty_target=medium`; `answer_type=single_choice_weighted`.
- Prompt: Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: pengantar surat — telepon.
- Options:
  - A. `text=Percakapan`; `score_value=0`; `is_correct=false`.
  - B. `text=Penghubung`; `score_value=2`; `is_correct=false`.
  - C. `text=Sarana komunikasi`; `score_value=1`; `is_correct=false`.
  - D. `text=Penyampai pesan`; `score_value=3`; `is_correct=true`.
  - E. `text=Kantor`; `score_value=0`; `is_correct=false`.
- Best answer: **D** (`3`). Explanation: `null`.
- Rationale internal: Keduanya menjalankan fungsi menyampaikan pesan. Penghubung sangat dekat tetapi kurang menyebut objek yang disampaikan; sarana komunikasi lebih umum dan kurang tepat untuk orang; percakapan adalah bentuk komunikasi dan kantor hanya tempat terkait.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 65.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=source_transcription_and_internal_options: pass`; `logic_review_notes=manual_semantic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-006

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=6`; `difficulty_target=medium`; `answer_type=single_choice_weighted`.
- Prompt: Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: kamera — kacamata.
- Options:
  - A. `text=Penglihatan`; `score_value=0`; `is_correct=false`.
  - B. `text=Alat optik`; `score_value=3`; `is_correct=true`.
  - C. `text=Peralatan berlensa`; `score_value=2`; `is_correct=false`.
  - D. `text=Benda buatan`; `score_value=1`; `is_correct=false`.
  - E. `text=Fotografi`; `score_value=0`; `is_correct=false`.
- Best answer: **B** (`3`). Explanation: `null`.
- Rationale internal: Alat optik adalah kategori fungsi paling tepat. Peralatan berlensa sangat dekat tetapi menekankan komponen; benda buatan terlalu luas; penglihatan dan fotografi hanya berkaitan kuat dengan salah satu fungsi.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 66.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=source_transcription_and_internal_options: pass`; `logic_review_notes=manual_semantic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-007

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=7`; `difficulty_target=medium`; `answer_type=single_choice_weighted`.
- Prompt: Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: lambung — usus.
- Options:
  - A. `text=Bagian tubuh`; `score_value=1`; `is_correct=false`.
  - B. `text=Rongga perut`; `score_value=0`; `is_correct=false`.
  - C. `text=Organ dalam`; `score_value=2`; `is_correct=false`.
  - D. `text=Organ pencernaan`; `score_value=3`; `is_correct=true`.
  - E. `text=Penyerap makanan`; `score_value=0`; `is_correct=false`.
- Best answer: **D** (`3`). Explanation: `null`.
- Rationale internal: Organ pencernaan adalah kategori fungsi langsung. Organ dalam dekat tetapi lebih luas; bagian tubuh sangat umum; rongga perut adalah lokasi dan penyerap makanan tidak menggambarkan fungsi keduanya secara setara.
- Difficulty basis: Medium sesuai overlay aplikasi untuk soal sumber 67.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=source_transcription_and_internal_options: pass`; `logic_review_notes=manual_semantic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-008

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=8`; `difficulty_target=hard`; `answer_type=single_choice_weighted`.
- Prompt: Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: banyak — sedikit.
- Options:
  - A. `text=Keterangan kuantitas`; `score_value=3`; `is_correct=true`.
  - B. `text=Ukuran jumlah`; `score_value=2`; `is_correct=false`.
  - C. `text=Besaran`; `score_value=1`; `is_correct=false`.
  - D. `text=Bilangan`; `score_value=0`; `is_correct=false`.
  - E. `text=Urutan`; `score_value=0`; `is_correct=false`.
- Best answer: **A** (`3`). Explanation: `null`.
- Rationale internal: Banyak dan sedikit merupakan keterangan kuantitas. Ukuran jumlah sangat dekat tetapi kurang tepat sebagai kelas kata/konsep; besaran lebih umum; keduanya bukan bilangan tertentu dan bukan urutan.
- Difficulty basis: Hard sesuai overlay aplikasi untuk soal sumber 68; pilihan berdekatan pada domain kuantitas.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=source_transcription_and_internal_options: pass`; `logic_review_notes=manual_semantic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-009

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=9`; `difficulty_target=hard`; `answer_type=single_choice_weighted`.
- Prompt: Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: telur — benih.
- Options:
  - A. `text=Hasil perkembangbiakan`; `score_value=1`; `is_correct=false`.
  - B. `text=Awal kehidupan`; `score_value=2`; `is_correct=false`.
  - C. `text=Calon individu baru`; `score_value=3`; `is_correct=true`.
  - D. `text=Bahan pangan`; `score_value=0`; `is_correct=false`.
  - E. `text=Tumbuhan`; `score_value=0`; `is_correct=false`.
- Best answer: **C** (`3`). Explanation: `null`.
- Rationale internal: Telur dan benih memuat calon individu baru. Awal kehidupan sangat dekat tetapi lebih abstrak; hasil perkembangbiakan lebih umum dan tidak selalu menunjuk potensi individu; bahan pangan hanya penggunaan tertentu dan tumbuhan tidak mencakup telur.
- Difficulty basis: Hard sesuai overlay aplikasi untuk soal sumber 69; opsi 3, 2, dan 1 membedakan potensi individu dari tahap dan asal.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=source_transcription_and_internal_options: pass`; `logic_review_notes=manual_semantic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### ge-010

- Contract: `subtest_code=GE`; `kind=scored`; `display_order=10`; `difficulty_target=hard`; `answer_type=single_choice_weighted`.
- Prompt: Pilih kata yang paling tepat mencakup pengertian kedua kata berikut: bendera — lencana.
- Options:
  - A. `text=Penanda`; `score_value=1`; `is_correct=false`.
  - B. `text=Hiasan`; `score_value=0`; `is_correct=false`.
  - C. `text=Tanda identitas`; `score_value=2`; `is_correct=false`.
  - D. `text=Kain`; `score_value=0`; `is_correct=false`.
  - E. `text=Lambang`; `score_value=3`; `is_correct=true`.
- Best answer: **E** (`3`). Explanation: `null`.
- Rationale internal: Bendera dan lencana berfungsi sebagai lambang. Tanda identitas sangat dekat tetapi tidak mencakup seluruh fungsi keduanya; penanda lebih umum; hiasan hanya penggunaan tambahan dan kain tidak mencakup lencana.
- Difficulty basis: Hard sesuai overlay aplikasi untuk soal sumber 70; pilihan dekat membedakan lambang, identitas, dan penanda.
- Reviews: `ambiguity_review=pass`; `weight_hierarchy_review=pass`; `language_review_notes=source_transcription_and_internal_options: pass`; `logic_review_notes=manual_semantic_validation: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

## Rekap audit draft

- Record: 11 (1 example + 10 scored).
- Opsi: 55; setiap record tepat lima opsi A–E.
- Setiap record memiliki tepat satu skor 3, satu skor 2, satu skor 1, dan dua skor 0.
- Setiap record memiliki tepat satu `is_correct=true`, selalu pada skor 3.
- Difficulty scored: easy 3 (`ge-001`–`ge-003`); medium 4 (`ge-004`–`ge-007`); hard 3 (`ge-008`–`ge-010`).
- Urutan posisi best answer scored: B–D–C–A–D–B–D–A–C–E.
- Raw maximum: `10 × 3 = 30`.
- Weighted maximum: `3×(3×1) + 4×(3×2) + 3×(3×3) = 60`.
- Seluruh record tetap `active=false`, belum approved, belum frozen, dan belum diimpor.
