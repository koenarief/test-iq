# Draft Konten Tahap 15 — FA dan WU Visual

**INTERNAL REVIEW ONLY — MEMUAT KUNCI DAN PROOF, JANGAN DIPUBLIKASIKAN KE PESERTA**

Konten visual ini dibuat secara internal untuk asesmen adaptasi dan tidak ditujukan sebagai reproduksi, substitusi normatif, atau salinan instrumen psikologi lain.

## Status dan kontrak

- `author_draft`: complete
- `automated_visual_review`: complete
- `automated_geometry_review`: complete
- `automated_rotation_review`: complete
- `automated_language_review`: complete
- `automated_logic_review`: complete
- `human_visual_review`: passed
- `human_geometry_review`: passed
- `human_rotation_review`: passed
- `human_language_review`: passed
- `human_logic_review`: passed
- `overall_status`: human_review_passed
- `active`: false
- `approved`: false
- `frozen`: false
- `imported`: false
- `answer_type`: image_choice
- Skor setiap butir: benar `1`; salah/kosong `0`.
- Aset berada hanya di `docs/ist/content-drafts/stage-15-assets/` dan tidak dibaca runtime.
- Seluruh metadata record: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

---

# FA — Penyusunan Bentuk

## Spesifikasi dan petunjuk

- 1 example + 10 scored; `duration_seconds=240`; `max_subtest_score=10`.
- Rotasi potongan diizinkan; refleksi, peregangan, overlap, penambahan, dan penghilangan bagian dilarang.
- Model audit internal memakai exact-cover sel satuan; gambar peserta tidak menampilkan koordinat atau key.

**Petunjuk peserta:** Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.

### fa-example-001

- Contract: `subtest_code=FA`; `kind=example`; `display_order=0`; `difficulty_target=null`; `answer_type=image_choice`.
- Prompt text: Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.
- Prompt media: `fa-example-001-prompt` → `fa/examples/fa-example-001-prompt.svg`.
- Potongan internal: D2={0,0 1,0}; L3={0,0 0,1 1,1}; I3={0,0 1,0 2,0}.
- Options:
  - A. `media_id=fa-example-001-option-a`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan A`; `failure_mode=concavity_mismatch`. Rationale: Susunan cekung mengunci satu sel sehingga exact-cover rotasi-only tidak dapat menempatkan seluruh potongan.
  - B. `media_id=fa-example-001-option-b`; `score_value=1`; `is_correct=true`; `alt_text=Diagram pilihan B`; `failure_mode=exact_cover_valid`. Rationale: Seluruh potongan menutup siluet tepat satu kali tanpa tumpang tindih; exact-cover rotasi-only berhasil.
  - C. `media_id=fa-example-001-option-c`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan C`; `failure_mode=terminal_residue`. Rationale: Penempatan potongan pada ujung kontur selalu menyisakan residu yang tidak cocok dengan potongan tersisa.
  - D. `media_id=fa-example-001-option-d`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan D`; `failure_mode=composition_mismatch`. Rationale: Area siluet sama, tetapi enumerasi exact-cover dengan seluruh rotasi legal tidak menemukan pembagian yang cocok untuk ketiga potongan.
  - E. `media_id=fa-example-001-option-e`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan E`; `failure_mode=boundary_partition_mismatch`. Rationale: Pemisahan kontur memaksa pembagian sel yang tidak sesuai dengan bentuk ketiga potongan.
- Key internal: **B**.
- Explanation peserta: Bandingkan jumlah dan bentuk seluruh potongan dengan setiap pilihan. Pilihan yang tepat memakai semua potongan sekali, tanpa tumpang tindih atau pencerminan..
- Rationale internal: Tiga potongan D2, L3, I3 mempunyai total 8 sel satuan. Opsi B adalah satu-satunya siluet yang lolos exact-cover dengan rotasi tanpa refleksi.
- Visual logic: model `unit_cell_exact_cover`; area 8; target cells `0,0 0,1 0,2 1,1 1,2 2,0 2,1 2,2`.
- Geometry audit: area/composition=pass; topology=pass; rotation=pass; reflection=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### fa-001

- Contract: `subtest_code=FA`; `kind=scored`; `display_order=1`; `difficulty_target=easy`; `answer_type=image_choice`.
- Prompt text: Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.
- Prompt media: `fa-001-prompt` → `fa/questions/fa-q001-prompt.svg`.
- Potongan internal: D2={0,0 1,0}; L3={0,0 0,1 1,1}; T4={0,0 1,0 1,1 2,0}.
- Options:
  - A. `media_id=fa-001-option-a`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan A`; `failure_mode=concavity_mismatch`. Rationale: Susunan cekung mengunci satu sel sehingga exact-cover rotasi-only tidak dapat menempatkan seluruh potongan.
  - B. `media_id=fa-001-option-b`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan B`; `failure_mode=terminal_residue`. Rationale: Penempatan potongan pada ujung kontur selalu menyisakan residu yang tidak cocok dengan potongan tersisa.
  - C. `media_id=fa-001-option-c`; `score_value=1`; `is_correct=true`; `alt_text=Diagram pilihan C`; `failure_mode=exact_cover_valid`. Rationale: Seluruh potongan menutup siluet tepat satu kali tanpa tumpang tindih; exact-cover rotasi-only berhasil.
  - D. `media_id=fa-001-option-d`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan D`; `failure_mode=composition_mismatch`. Rationale: Area siluet sama, tetapi enumerasi exact-cover dengan seluruh rotasi legal tidak menemukan pembagian yang cocok untuk ketiga potongan.
  - E. `media_id=fa-001-option-e`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan E`; `failure_mode=boundary_partition_mismatch`. Rationale: Pemisahan kontur memaksa pembagian sel yang tidak sesuai dengan bentuk ketiga potongan.
- Key internal: **C**.
- Explanation peserta: `null`.
- Rationale internal: Tiga potongan D2, L3, T4 mempunyai total 9 sel satuan. Opsi C adalah satu-satunya siluet yang lolos exact-cover dengan rotasi tanpa refleksi.
- Visual logic: model `unit_cell_exact_cover`; area 9; target cells `0,0 0,1 0,2 1,0 1,1 1,2 2,0 2,1 2,2`.
- Geometry audit: area/composition=pass; topology=pass; rotation=pass; reflection=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### fa-002

- Contract: `subtest_code=FA`; `kind=scored`; `display_order=2`; `difficulty_target=easy`; `answer_type=image_choice`.
- Prompt text: Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.
- Prompt media: `fa-002-prompt` → `fa/questions/fa-q002-prompt.svg`.
- Potongan internal: I3={0,0 1,0 2,0}; L3={0,0 0,1 1,1}; O4={0,0 0,1 1,0 1,1}.
- Options:
  - A. `media_id=fa-002-option-a`; `score_value=1`; `is_correct=true`; `alt_text=Diagram pilihan A`; `failure_mode=exact_cover_valid`. Rationale: Seluruh potongan menutup siluet tepat satu kali tanpa tumpang tindih; exact-cover rotasi-only berhasil.
  - B. `media_id=fa-002-option-b`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan B`; `failure_mode=concavity_mismatch`. Rationale: Susunan cekung mengunci satu sel sehingga exact-cover rotasi-only tidak dapat menempatkan seluruh potongan.
  - C. `media_id=fa-002-option-c`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan C`; `failure_mode=terminal_residue`. Rationale: Penempatan potongan pada ujung kontur selalu menyisakan residu yang tidak cocok dengan potongan tersisa.
  - D. `media_id=fa-002-option-d`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan D`; `failure_mode=composition_mismatch`. Rationale: Area siluet sama, tetapi enumerasi exact-cover dengan seluruh rotasi legal tidak menemukan pembagian yang cocok untuk ketiga potongan.
  - E. `media_id=fa-002-option-e`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan E`; `failure_mode=boundary_partition_mismatch`. Rationale: Pemisahan kontur memaksa pembagian sel yang tidak sesuai dengan bentuk ketiga potongan.
- Key internal: **A**.
- Explanation peserta: `null`.
- Rationale internal: Tiga potongan I3, L3, O4 mempunyai total 10 sel satuan. Opsi A adalah satu-satunya siluet yang lolos exact-cover dengan rotasi tanpa refleksi.
- Visual logic: model `unit_cell_exact_cover`; area 10; target cells `0,0 0,1 0,2 0,3 1,0 1,1 1,2 1,3 2,2 2,3`.
- Geometry audit: area/composition=pass; topology=pass; rotation=pass; reflection=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### fa-003

- Contract: `subtest_code=FA`; `kind=scored`; `display_order=3`; `difficulty_target=easy`; `answer_type=image_choice`.
- Prompt text: Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.
- Prompt media: `fa-003-prompt` → `fa/questions/fa-q003-prompt.svg`.
- Potongan internal: D2={0,0 1,0}; S4={0,1 1,0 1,1 2,0}; L4={0,0 0,1 0,2 1,2}.
- Options:
  - A. `media_id=fa-003-option-a`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan A`; `failure_mode=concavity_mismatch`. Rationale: Susunan cekung mengunci satu sel sehingga exact-cover rotasi-only tidak dapat menempatkan seluruh potongan.
  - B. `media_id=fa-003-option-b`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan B`; `failure_mode=terminal_residue`. Rationale: Penempatan potongan pada ujung kontur selalu menyisakan residu yang tidak cocok dengan potongan tersisa.
  - C. `media_id=fa-003-option-c`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan C`; `failure_mode=composition_mismatch`. Rationale: Area siluet sama, tetapi enumerasi exact-cover dengan seluruh rotasi legal tidak menemukan pembagian yang cocok untuk ketiga potongan.
  - D. `media_id=fa-003-option-d`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan D`; `failure_mode=boundary_partition_mismatch`. Rationale: Pemisahan kontur memaksa pembagian sel yang tidak sesuai dengan bentuk ketiga potongan.
  - E. `media_id=fa-003-option-e`; `score_value=1`; `is_correct=true`; `alt_text=Diagram pilihan E`; `failure_mode=exact_cover_valid`. Rationale: Seluruh potongan menutup siluet tepat satu kali tanpa tumpang tindih; exact-cover rotasi-only berhasil.
- Key internal: **E**.
- Explanation peserta: `null`.
- Rationale internal: Tiga potongan D2, S4, L4 mempunyai total 10 sel satuan. Opsi E adalah satu-satunya siluet yang lolos exact-cover dengan rotasi tanpa refleksi.
- Visual logic: model `unit_cell_exact_cover`; area 10; target cells `0,1 0,2 0,3 1,0 1,1 1,2 1,3 2,0 2,2 2,3`.
- Geometry audit: area/composition=pass; topology=pass; rotation=pass; reflection=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### fa-004

- Contract: `subtest_code=FA`; `kind=scored`; `display_order=4`; `difficulty_target=medium`; `answer_type=image_choice`.
- Prompt text: Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.
- Prompt media: `fa-004-prompt` → `fa/questions/fa-q004-prompt.svg`.
- Potongan internal: L3={0,0 0,1 1,1}; T4={0,0 1,0 1,1 2,0}; Z4={0,0 1,0 1,1 2,1}.
- Options:
  - A. `media_id=fa-004-option-a`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan A`; `failure_mode=concavity_mismatch`. Rationale: Susunan cekung mengunci satu sel sehingga exact-cover rotasi-only tidak dapat menempatkan seluruh potongan.
  - B. `media_id=fa-004-option-b`; `score_value=1`; `is_correct=true`; `alt_text=Diagram pilihan B`; `failure_mode=exact_cover_valid`. Rationale: Seluruh potongan menutup siluet tepat satu kali tanpa tumpang tindih; exact-cover rotasi-only berhasil.
  - C. `media_id=fa-004-option-c`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan C`; `failure_mode=terminal_residue`. Rationale: Penempatan potongan pada ujung kontur selalu menyisakan residu yang tidak cocok dengan potongan tersisa.
  - D. `media_id=fa-004-option-d`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan D`; `failure_mode=reflection_required`. Rationale: Siluet baru dapat ditutup bila salah satu potongan dicerminkan; refleksi tidak diizinkan.
  - E. `media_id=fa-004-option-e`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan E`; `failure_mode=boundary_partition_mismatch`. Rationale: Pemisahan kontur memaksa pembagian sel yang tidak sesuai dengan bentuk ketiga potongan.
- Key internal: **B**.
- Explanation peserta: `null`.
- Rationale internal: Tiga potongan L3, T4, Z4 mempunyai total 11 sel satuan. Opsi B adalah satu-satunya siluet yang lolos exact-cover dengan rotasi tanpa refleksi.
- Visual logic: model `unit_cell_exact_cover`; area 11; target cells `0,1 1,0 1,1 2,0 2,1 3,0 3,1 4,0 5,0 5,1 6,1`.
- Geometry audit: area/composition=pass; topology=pass; rotation=pass; reflection=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### fa-005

- Contract: `subtest_code=FA`; `kind=scored`; `display_order=5`; `difficulty_target=medium`; `answer_type=image_choice`.
- Prompt text: Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.
- Prompt media: `fa-005-prompt` → `fa/questions/fa-q005-prompt.svg`.
- Potongan internal: I3={0,0 1,0 2,0}; O4={0,0 0,1 1,0 1,1}; L4={0,0 0,1 0,2 1,2}.
- Options:
  - A. `media_id=fa-005-option-a`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan A`; `failure_mode=concavity_mismatch`. Rationale: Susunan cekung mengunci satu sel sehingga exact-cover rotasi-only tidak dapat menempatkan seluruh potongan.
  - B. `media_id=fa-005-option-b`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan B`; `failure_mode=terminal_residue`. Rationale: Penempatan potongan pada ujung kontur selalu menyisakan residu yang tidak cocok dengan potongan tersisa.
  - C. `media_id=fa-005-option-c`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan C`; `failure_mode=composition_mismatch`. Rationale: Area siluet sama, tetapi enumerasi exact-cover dengan seluruh rotasi legal tidak menemukan pembagian yang cocok untuk ketiga potongan.
  - D. `media_id=fa-005-option-d`; `score_value=1`; `is_correct=true`; `alt_text=Diagram pilihan D`; `failure_mode=exact_cover_valid`. Rationale: Seluruh potongan menutup siluet tepat satu kali tanpa tumpang tindih; exact-cover rotasi-only berhasil.
  - E. `media_id=fa-005-option-e`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan E`; `failure_mode=boundary_partition_mismatch`. Rationale: Pemisahan kontur memaksa pembagian sel yang tidak sesuai dengan bentuk ketiga potongan.
- Key internal: **D**.
- Explanation peserta: `null`.
- Rationale internal: Tiga potongan I3, O4, L4 mempunyai total 11 sel satuan. Opsi D adalah satu-satunya siluet yang lolos exact-cover dengan rotasi tanpa refleksi.
- Visual logic: model `unit_cell_exact_cover`; area 11; target cells `0,1 0,2 0,3 1,0 1,1 1,2 1,3 2,0 2,1 2,2 2,3`.
- Geometry audit: area/composition=pass; topology=pass; rotation=pass; reflection=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### fa-006

- Contract: `subtest_code=FA`; `kind=scored`; `display_order=6`; `difficulty_target=medium`; `answer_type=image_choice`.
- Prompt text: Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.
- Prompt media: `fa-006-prompt` → `fa/questions/fa-q006-prompt.svg`.
- Potongan internal: D2={0,0 1,0}; T4={0,0 1,0 1,1 2,0}; U5={0,0 0,1 1,1 2,0 2,1}.
- Options:
  - A. `media_id=fa-006-option-a`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan A`; `failure_mode=concavity_mismatch`. Rationale: Susunan cekung mengunci satu sel sehingga exact-cover rotasi-only tidak dapat menempatkan seluruh potongan.
  - B. `media_id=fa-006-option-b`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan B`; `failure_mode=terminal_residue`. Rationale: Penempatan potongan pada ujung kontur selalu menyisakan residu yang tidak cocok dengan potongan tersisa.
  - C. `media_id=fa-006-option-c`; `score_value=1`; `is_correct=true`; `alt_text=Diagram pilihan C`; `failure_mode=exact_cover_valid`. Rationale: Seluruh potongan menutup siluet tepat satu kali tanpa tumpang tindih; exact-cover rotasi-only berhasil.
  - D. `media_id=fa-006-option-d`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan D`; `failure_mode=composition_mismatch`. Rationale: Area siluet sama, tetapi enumerasi exact-cover dengan seluruh rotasi legal tidak menemukan pembagian yang cocok untuk ketiga potongan.
  - E. `media_id=fa-006-option-e`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan E`; `failure_mode=boundary_partition_mismatch`. Rationale: Pemisahan kontur memaksa pembagian sel yang tidak sesuai dengan bentuk ketiga potongan.
- Key internal: **C**.
- Explanation peserta: `null`.
- Rationale internal: Tiga potongan D2, T4, U5 mempunyai total 11 sel satuan. Opsi C adalah satu-satunya siluet yang lolos exact-cover dengan rotasi tanpa refleksi.
- Visual logic: model `unit_cell_exact_cover`; area 11; target cells `0,1 1,0 1,1 2,0 2,1 3,0 4,0 4,1 5,0 6,0 6,1`.
- Geometry audit: area/composition=pass; topology=pass; rotation=pass; reflection=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### fa-007

- Contract: `subtest_code=FA`; `kind=scored`; `display_order=7`; `difficulty_target=medium`; `answer_type=image_choice`.
- Prompt text: Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.
- Prompt media: `fa-007-prompt` → `fa/questions/fa-q007-prompt.svg`.
- Potongan internal: L3={0,0 0,1 1,1}; S4={0,1 1,0 1,1 2,0}; P5={0,0 0,1 0,2 1,0 1,1}.
- Options:
  - A. `media_id=fa-007-option-a`; `score_value=1`; `is_correct=true`; `alt_text=Diagram pilihan A`; `failure_mode=exact_cover_valid`. Rationale: Seluruh potongan menutup siluet tepat satu kali tanpa tumpang tindih; exact-cover rotasi-only berhasil.
  - B. `media_id=fa-007-option-b`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan B`; `failure_mode=concavity_mismatch`. Rationale: Susunan cekung mengunci satu sel sehingga exact-cover rotasi-only tidak dapat menempatkan seluruh potongan.
  - C. `media_id=fa-007-option-c`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan C`; `failure_mode=terminal_residue`. Rationale: Penempatan potongan pada ujung kontur selalu menyisakan residu yang tidak cocok dengan potongan tersisa.
  - D. `media_id=fa-007-option-d`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan D`; `failure_mode=reflection_required`. Rationale: Siluet baru dapat ditutup bila salah satu potongan dicerminkan; refleksi tidak diizinkan.
  - E. `media_id=fa-007-option-e`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan E`; `failure_mode=boundary_partition_mismatch`. Rationale: Pemisahan kontur memaksa pembagian sel yang tidak sesuai dengan bentuk ketiga potongan.
- Key internal: **A**.
- Explanation peserta: `null`.
- Rationale internal: Tiga potongan L3, S4, P5 mempunyai total 12 sel satuan. Opsi A adalah satu-satunya siluet yang lolos exact-cover dengan rotasi tanpa refleksi.
- Visual logic: model `unit_cell_exact_cover`; area 12; target cells `0,1 1,0 1,1 2,0 2,1 3,0 3,1 4,0 5,0 5,1 6,0 6,1`.
- Geometry audit: area/composition=pass; topology=pass; rotation=pass; reflection=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### fa-008

- Contract: `subtest_code=FA`; `kind=scored`; `display_order=8`; `difficulty_target=hard`; `answer_type=image_choice`.
- Prompt text: Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.
- Prompt media: `fa-008-prompt` → `fa/questions/fa-q008-prompt.svg`.
- Potongan internal: I3={0,0 1,0 2,0}; T4={0,0 1,0 1,1 2,0}; V5={0,0 0,1 0,2 1,2 2,2}.
- Options:
  - A. `media_id=fa-008-option-a`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan A`; `failure_mode=concavity_mismatch`. Rationale: Susunan cekung mengunci satu sel sehingga exact-cover rotasi-only tidak dapat menempatkan seluruh potongan.
  - B. `media_id=fa-008-option-b`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan B`; `failure_mode=terminal_residue`. Rationale: Penempatan potongan pada ujung kontur selalu menyisakan residu yang tidak cocok dengan potongan tersisa.
  - C. `media_id=fa-008-option-c`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan C`; `failure_mode=composition_mismatch`. Rationale: Area siluet sama, tetapi enumerasi exact-cover dengan seluruh rotasi legal tidak menemukan pembagian yang cocok untuk ketiga potongan.
  - D. `media_id=fa-008-option-d`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan D`; `failure_mode=boundary_partition_mismatch`. Rationale: Pemisahan kontur memaksa pembagian sel yang tidak sesuai dengan bentuk ketiga potongan.
  - E. `media_id=fa-008-option-e`; `score_value=1`; `is_correct=true`; `alt_text=Diagram pilihan E`; `failure_mode=exact_cover_valid`. Rationale: Seluruh potongan menutup siluet tepat satu kali tanpa tumpang tindih; exact-cover rotasi-only berhasil.
- Key internal: **E**.
- Explanation peserta: `null`.
- Rationale internal: Tiga potongan I3, T4, V5 mempunyai total 12 sel satuan. Opsi E adalah satu-satunya siluet yang lolos exact-cover dengan rotasi tanpa refleksi.
- Visual logic: model `unit_cell_exact_cover`; area 12; target cells `0,1 0,2 0,3 1,0 1,2 1,3 2,0 2,1 2,2 2,3 3,0 3,2`.
- Geometry audit: area/composition=pass; topology=pass; rotation=pass; reflection=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### fa-009

- Contract: `subtest_code=FA`; `kind=scored`; `display_order=9`; `difficulty_target=hard`; `answer_type=image_choice`.
- Prompt text: Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.
- Prompt media: `fa-009-prompt` → `fa/questions/fa-q009-prompt.svg`.
- Potongan internal: L4={0,0 0,1 0,2 1,2}; O4={0,0 0,1 1,0 1,1}; W5={0,0 0,1 1,1 1,2 2,2}.
- Options:
  - A. `media_id=fa-009-option-a`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan A`; `failure_mode=concavity_mismatch`. Rationale: Susunan cekung mengunci satu sel sehingga exact-cover rotasi-only tidak dapat menempatkan seluruh potongan.
  - B. `media_id=fa-009-option-b`; `score_value=1`; `is_correct=true`; `alt_text=Diagram pilihan B`; `failure_mode=exact_cover_valid`. Rationale: Seluruh potongan menutup siluet tepat satu kali tanpa tumpang tindih; exact-cover rotasi-only berhasil.
  - C. `media_id=fa-009-option-c`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan C`; `failure_mode=terminal_residue`. Rationale: Penempatan potongan pada ujung kontur selalu menyisakan residu yang tidak cocok dengan potongan tersisa.
  - D. `media_id=fa-009-option-d`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan D`; `failure_mode=composition_mismatch`. Rationale: Area siluet sama, tetapi enumerasi exact-cover dengan seluruh rotasi legal tidak menemukan pembagian yang cocok untuk ketiga potongan.
  - E. `media_id=fa-009-option-e`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan E`; `failure_mode=boundary_partition_mismatch`. Rationale: Pemisahan kontur memaksa pembagian sel yang tidak sesuai dengan bentuk ketiga potongan.
- Key internal: **B**.
- Explanation peserta: `null`.
- Rationale internal: Tiga potongan L4, O4, W5 mempunyai total 13 sel satuan. Opsi B adalah satu-satunya siluet yang lolos exact-cover dengan rotasi tanpa refleksi.
- Visual logic: model `unit_cell_exact_cover`; area 13; target cells `0,0 0,1 0,2 1,0 1,1 1,2 2,0 2,1 2,2 3,1 3,2 4,0 4,1`.
- Geometry audit: area/composition=pass; topology=pass; rotation=pass; reflection=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### fa-010

- Contract: `subtest_code=FA`; `kind=scored`; `display_order=10`; `difficulty_target=hard`; `answer_type=image_choice`.
- Prompt text: Perhatikan seluruh potongan pada gambar. Pilih bentuk utuh yang dapat disusun menggunakan setiap potongan tepat satu kali. Potongan boleh diputar, tetapi tidak boleh dicerminkan.
- Prompt media: `fa-010-prompt` → `fa/questions/fa-q010-prompt.svg`.
- Potongan internal: T4={0,0 1,0 1,1 2,0}; Z4={0,0 1,0 1,1 2,1}; Y5={0,0 0,1 0,2 0,3 1,1}.
- Options:
  - A. `media_id=fa-010-option-a`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan A`; `failure_mode=concavity_mismatch`. Rationale: Susunan cekung mengunci satu sel sehingga exact-cover rotasi-only tidak dapat menempatkan seluruh potongan.
  - B. `media_id=fa-010-option-b`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan B`; `failure_mode=terminal_residue`. Rationale: Penempatan potongan pada ujung kontur selalu menyisakan residu yang tidak cocok dengan potongan tersisa.
  - C. `media_id=fa-010-option-c`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan C`; `failure_mode=reflection_required`. Rationale: Siluet baru dapat ditutup bila salah satu potongan dicerminkan; refleksi tidak diizinkan.
  - D. `media_id=fa-010-option-d`; `score_value=1`; `is_correct=true`; `alt_text=Diagram pilihan D`; `failure_mode=exact_cover_valid`. Rationale: Seluruh potongan menutup siluet tepat satu kali tanpa tumpang tindih; exact-cover rotasi-only berhasil.
  - E. `media_id=fa-010-option-e`; `score_value=0`; `is_correct=false`; `alt_text=Diagram pilihan E`; `failure_mode=boundary_partition_mismatch`. Rationale: Pemisahan kontur memaksa pembagian sel yang tidak sesuai dengan bentuk ketiga potongan.
- Key internal: **D**.
- Explanation peserta: `null`.
- Rationale internal: Tiga potongan T4, Z4, Y5 mempunyai total 13 sel satuan. Opsi D adalah satu-satunya siluet yang lolos exact-cover dengan rotasi tanpa refleksi.
- Visual logic: model `unit_cell_exact_cover`; area 13; target cells `0,1 0,2 0,3 0,4 0,5 0,6 0,7 1,0 1,1 1,2 1,3 1,4 1,5`.
- Geometry audit: area/composition=pass; topology=pass; rotation=pass; reflection=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

## Rekap FA

- Record: 11 (1 example + 10 scored); media: 66.
- Difficulty scored: easy 3; medium 4; hard 3.
- Distribusi key scored: A=2, B=2, C=2, D=2, E=2.
- Seluruh opsi benar lolos exact-cover rotasi-only; seluruh distraktor gagal. Distraktor refleksi eksplisit hanya lolos bila refleksi ilegal diaktifkan.

---

# WU — Rotasi Kubus

## Spesifikasi dan petunjuk

- 1 example + 12 scored; `duration_seconds=360`; `max_subtest_score=12`.
- Setiap record menggunakan enam simbol unik pada enam sisi.
- Reference menampilkan dua orientasi dari kubus yang sama; proof internal mengenumerasi 24 rotasi proper.

**Petunjuk peserta:** Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.

### wu-example-001

- Contract: `subtest_code=WU`; `kind=example`; `display_order=0`; `difficulty_target=null`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-example-001-reference` → `wu/examples/wu-example-001-reference.svg`.
- Face mapping internal: top=circle; bottom=plus; front=cross; back=bullseye; right=fourdots; left=diamond.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-example-001-option-a`; `visible=top/right/front`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan A`; `failure_mode=mirror_chirality`. Rationale: Tuple top/right/front membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - B. `media_id=wu-example-001-option-b`; `visible=bottom/front/left`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan B`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right bottom/front/left termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
  - C. `media_id=wu-example-001-option-c`; `visible=top/bottom/front`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan C`; `failure_mode=opposite_faces_adjacent`. Rationale: Tuple top/bottom/front menempatkan pasangan sisi berlawanan pada satu sudut, sehingga tidak mungkin.
  - D. `media_id=wu-example-001-option-d`; `visible=top/front/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan D`; `failure_mode=mirror_chirality`. Rationale: Tuple top/front/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - E. `media_id=wu-example-001-option-e`; `visible=top/back/front`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan E`; `failure_mode=opposite_faces_adjacent`. Rationale: Tuple top/back/front menempatkan pasangan sisi berlawanan pada satu sudut, sehingga tidak mungkin.
- Key internal: **B**.
- Explanation peserta: Rotasi kubus mempertahankan sisi yang bersebelahan, sisi yang berlawanan, dan urutan tiga sisi pada satu sudut..
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi B yang mempunyai tuple orientasi valid.
- Rotation proof: putar searah pandang 90° → putar searah pandang 90° menghasilkan Top=bottom, Front=front, Right=left.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-001

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=1`; `difficulty_target=easy`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-001-reference` → `wu/questions/wu-q001-reference.svg`.
- Face mapping internal: top=bullseye; bottom=fourdots; front=diamond; back=square; right=hexagon; left=star.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-001-option-a`; `visible=bottom/left/front`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan A`; `failure_mode=mirror_chirality`. Rationale: Tuple bottom/left/front membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - B. `media_id=wu-001-option-b`; `visible=top/front/back`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan B`; `failure_mode=opposite_faces_adjacent`. Rationale: Tuple top/front/back menempatkan pasangan sisi berlawanan pada satu sudut, sehingga tidak mungkin.
  - C. `media_id=wu-001-option-c`; `visible=bottom/left/back`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan C`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right bottom/left/back termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
  - D. `media_id=wu-001-option-d`; `visible=bottom/back/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan D`; `failure_mode=mirror_chirality`. Rationale: Tuple bottom/back/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - E. `media_id=wu-001-option-e`; `visible=bottom/top/front`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan E`; `failure_mode=opposite_faces_adjacent`. Rationale: Tuple bottom/top/front menempatkan pasangan sisi berlawanan pada satu sudut, sehingga tidak mungkin.
- Key internal: **C**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi C yang mempunyai tuple orientasi valid.
- Rotation proof: putar vertikal 90° → putar maju 90° → putar maju 90° menghasilkan Top=bottom, Front=left, Right=back.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-002

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=2`; `difficulty_target=easy`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-002-reference` → `wu/questions/wu-q002-reference.svg`.
- Face mapping internal: top=square; bottom=hexagon; front=star; back=doublecircle; right=circle; left=plus.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-002-option-a`; `visible=front/right/top`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan A`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right front/right/top termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
  - B. `media_id=wu-002-option-b`; `visible=front/left/top`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan B`; `failure_mode=mirror_chirality`. Rationale: Tuple front/left/top membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - C. `media_id=wu-002-option-c`; `visible=top/left/bottom`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan C`; `failure_mode=opposite_faces_adjacent`. Rationale: Tuple top/left/bottom menempatkan pasangan sisi berlawanan pada satu sudut, sehingga tidak mungkin.
  - D. `media_id=wu-002-option-d`; `visible=front/bottom/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan D`; `failure_mode=mirror_chirality`. Rationale: Tuple front/bottom/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - E. `media_id=wu-002-option-e`; `visible=bottom/front/back`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan E`; `failure_mode=opposite_faces_adjacent`. Rationale: Tuple bottom/front/back menempatkan pasangan sisi berlawanan pada satu sudut, sehingga tidak mungkin.
- Key internal: **A**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi A yang mempunyai tuple orientasi valid.
- Rotation proof: putar vertikal 90° → putar searah pandang 90° menghasilkan Top=front, Front=right, Right=top.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-003

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=3`; `difficulty_target=easy`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-003-reference` → `wu/questions/wu-q003-reference.svg`.
- Face mapping internal: top=doublecircle; bottom=circle; front=plus; back=cross; right=bullseye; left=fourdots.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-003-option-a`; `visible=back/right/top`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan A`; `failure_mode=mirror_chirality`. Rationale: Tuple back/right/top membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - B. `media_id=wu-003-option-b`; `visible=bottom/top/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan B`; `failure_mode=opposite_faces_adjacent`. Rationale: Tuple bottom/top/left menempatkan pasangan sisi berlawanan pada satu sudut, sehingga tidak mungkin.
  - C. `media_id=wu-003-option-c`; `visible=back/top/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan C`; `failure_mode=mirror_chirality`. Rationale: Tuple back/top/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - D. `media_id=wu-003-option-d`; `visible=back/bottom/left`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan D`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right back/bottom/left termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
  - E. `media_id=wu-003-option-e`; `visible=bottom/left/top`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan E`; `failure_mode=opposite_faces_adjacent`. Rationale: Tuple bottom/left/top menempatkan pasangan sisi berlawanan pada satu sudut, sehingga tidak mungkin.
- Key internal: **D**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi D yang mempunyai tuple orientasi valid.
- Rotation proof: putar maju 90° → putar vertikal 90° → putar vertikal 90° menghasilkan Top=back, Front=bottom, Right=left.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-004

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=4`; `difficulty_target=easy`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-004-reference` → `wu/questions/wu-q004-reference.svg`.
- Face mapping internal: top=cross; bottom=bullseye; front=fourdots; back=diamond; right=square; left=hexagon.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-004-option-a`; `visible=right/front/top`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan A`; `failure_mode=mirror_chirality`. Rationale: Tuple right/front/top membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - B. `media_id=wu-004-option-b`; `visible=right/top/front`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan B`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right right/top/front termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
  - C. `media_id=wu-004-option-c`; `visible=bottom/right/top`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan C`; `failure_mode=opposite_faces_adjacent`. Rationale: Tuple bottom/right/top menempatkan pasangan sisi berlawanan pada satu sudut, sehingga tidak mungkin.
  - D. `media_id=wu-004-option-d`; `visible=right/top/back`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan D`; `failure_mode=mirror_chirality`. Rationale: Tuple right/top/back membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - E. `media_id=wu-004-option-e`; `visible=front/bottom/back`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan E`; `failure_mode=opposite_faces_adjacent`. Rationale: Tuple front/bottom/back menempatkan pasangan sisi berlawanan pada satu sudut, sehingga tidak mungkin.
- Key internal: **B**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi B yang mempunyai tuple orientasi valid.
- Rotation proof: putar vertikal 90° → putar vertikal 90° → putar vertikal 90° → putar maju 90° menghasilkan Top=right, Front=top, Right=front.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-005

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=5`; `difficulty_target=medium`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-005-reference` → `wu/questions/wu-q005-reference.svg`.
- Face mapping internal: top=diamond; bottom=square; front=hexagon; back=star; right=doublecircle; left=circle.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-005-option-a`; `visible=left/back/top`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan A`; `failure_mode=mirror_chirality`. Rationale: Tuple left/back/top membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - B. `media_id=wu-005-option-b`; `visible=left/bottom/back`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan B`; `failure_mode=mirror_chirality`. Rationale: Tuple left/bottom/back membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - C. `media_id=wu-005-option-c`; `visible=top/front/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan C`; `failure_mode=mirror_chirality`. Rationale: Tuple top/front/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - D. `media_id=wu-005-option-d`; `visible=bottom/back/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan D`; `failure_mode=mirror_chirality`. Rationale: Tuple bottom/back/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - E. `media_id=wu-005-option-e`; `visible=right/back/top`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan E`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right right/back/top termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
- Key internal: **E**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi E yang mempunyai tuple orientasi valid.
- Rotation proof: putar vertikal 90° → putar vertikal 90° → putar searah pandang 90° menghasilkan Top=right, Front=back, Right=top.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-006

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=6`; `difficulty_target=medium`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-006-reference` → `wu/questions/wu-q006-reference.svg`.
- Face mapping internal: top=star; bottom=doublecircle; front=circle; back=plus; right=cross; left=bullseye.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-006-option-a`; `visible=top/right/front`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan A`; `failure_mode=mirror_chirality`. Rationale: Tuple top/right/front membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - B. `media_id=wu-006-option-b`; `visible=top/front/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan B`; `failure_mode=mirror_chirality`. Rationale: Tuple top/front/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - C. `media_id=wu-006-option-c`; `visible=left/front/top`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan C`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right left/front/top termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
  - D. `media_id=wu-006-option-d`; `visible=bottom/back/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan D`; `failure_mode=mirror_chirality`. Rationale: Tuple bottom/back/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - E. `media_id=wu-006-option-e`; `visible=front/bottom/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan E`; `failure_mode=mirror_chirality`. Rationale: Tuple front/bottom/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
- Key internal: **C**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi C yang mempunyai tuple orientasi valid.
- Rotation proof: putar searah pandang 90° menghasilkan Top=left, Front=front, Right=top.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-007

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=7`; `difficulty_target=medium`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-007-reference` → `wu/questions/wu-q007-reference.svg`.
- Face mapping internal: top=plus; bottom=cross; front=bullseye; back=fourdots; right=diamond; left=square.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-007-option-a`; `visible=bottom/left/front`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan A`; `failure_mode=mirror_chirality`. Rationale: Tuple bottom/left/front membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - B. `media_id=wu-007-option-b`; `visible=bottom/back/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan B`; `failure_mode=mirror_chirality`. Rationale: Tuple bottom/back/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - C. `media_id=wu-007-option-c`; `visible=front/bottom/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan C`; `failure_mode=mirror_chirality`. Rationale: Tuple front/bottom/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - D. `media_id=wu-007-option-d`; `visible=bottom/back/right`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan D`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right bottom/back/right termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
  - E. `media_id=wu-007-option-e`; `visible=back/top/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan E`; `failure_mode=mirror_chirality`. Rationale: Tuple back/top/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
- Key internal: **D**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi D yang mempunyai tuple orientasi valid.
- Rotation proof: putar maju 90° → putar maju 90° menghasilkan Top=bottom, Front=back, Right=right.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-008

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=8`; `difficulty_target=medium`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-008-reference` → `wu/questions/wu-q008-reference.svg`.
- Face mapping internal: top=fourdots; bottom=diamond; front=square; back=hexagon; right=star; left=doublecircle.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-008-option-a`; `visible=front/top/left`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan A`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right front/top/left termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
  - B. `media_id=wu-008-option-b`; `visible=front/left/top`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan B`; `failure_mode=mirror_chirality`. Rationale: Tuple front/left/top membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - C. `media_id=wu-008-option-c`; `visible=front/bottom/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan C`; `failure_mode=mirror_chirality`. Rationale: Tuple front/bottom/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - D. `media_id=wu-008-option-d`; `visible=back/top/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan D`; `failure_mode=mirror_chirality`. Rationale: Tuple back/top/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - E. `media_id=wu-008-option-e`; `visible=right/top/back`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan E`; `failure_mode=mirror_chirality`. Rationale: Tuple right/top/back membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
- Key internal: **A**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi A yang mempunyai tuple orientasi valid.
- Rotation proof: putar vertikal 90° → putar vertikal 90° → putar maju 90° menghasilkan Top=front, Front=top, Right=left.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-009

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=9`; `difficulty_target=medium`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-009-reference` → `wu/questions/wu-q009-reference.svg`.
- Face mapping internal: top=hexagon; bottom=star; front=doublecircle; back=circle; right=plus; left=cross.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-009-option-a`; `visible=back/right/top`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan A`; `failure_mode=mirror_chirality`. Rationale: Tuple back/right/top membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - B. `media_id=wu-009-option-b`; `visible=back/top/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan B`; `failure_mode=mirror_chirality`. Rationale: Tuple back/top/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - C. `media_id=wu-009-option-c`; `visible=front/left/bottom`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan C`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right front/left/bottom termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
  - D. `media_id=wu-009-option-d`; `visible=right/top/back`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan D`; `failure_mode=mirror_chirality`. Rationale: Tuple right/top/back membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - E. `media_id=wu-009-option-e`; `visible=left/bottom/back`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan E`; `failure_mode=mirror_chirality`. Rationale: Tuple left/bottom/back membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
- Key internal: **C**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi C yang mempunyai tuple orientasi valid.
- Rotation proof: putar vertikal 90° → putar vertikal 90° → putar maju 90° → putar vertikal 90° menghasilkan Top=front, Front=left, Right=bottom.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-010

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=10`; `difficulty_target=hard`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-010-reference` → `wu/questions/wu-q010-reference.svg`.
- Face mapping internal: top=circle; bottom=plus; front=cross; back=bullseye; right=fourdots; left=diamond.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-010-option-a`; `visible=right/front/top`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan A`; `failure_mode=mirror_chirality`. Rationale: Tuple right/front/top membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - B. `media_id=wu-010-option-b`; `visible=right/top/back`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan B`; `failure_mode=mirror_chirality`. Rationale: Tuple right/top/back membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - C. `media_id=wu-010-option-c`; `visible=left/bottom/back`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan C`; `failure_mode=mirror_chirality`. Rationale: Tuple left/bottom/back membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - D. `media_id=wu-010-option-d`; `visible=top/front/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan D`; `failure_mode=mirror_chirality`. Rationale: Tuple top/front/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - E. `media_id=wu-010-option-e`; `visible=back/right/bottom`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan E`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right back/right/bottom termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
- Key internal: **E**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi E yang mempunyai tuple orientasi valid.
- Rotation proof: putar maju 90° → putar vertikal 90° menghasilkan Top=back, Front=right, Right=bottom.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-011

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=11`; `difficulty_target=hard`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-011-reference` → `wu/questions/wu-q011-reference.svg`.
- Face mapping internal: top=bullseye; bottom=fourdots; front=diamond; back=square; right=hexagon; left=star.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-011-option-a`; `visible=left/back/top`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan A`; `failure_mode=mirror_chirality`. Rationale: Tuple left/back/top membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - B. `media_id=wu-011-option-b`; `visible=right/bottom/back`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan B`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right right/bottom/back termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
  - C. `media_id=wu-011-option-c`; `visible=left/bottom/back`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan C`; `failure_mode=mirror_chirality`. Rationale: Tuple left/bottom/back membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - D. `media_id=wu-011-option-d`; `visible=top/front/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan D`; `failure_mode=mirror_chirality`. Rationale: Tuple top/front/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - E. `media_id=wu-011-option-e`; `visible=bottom/back/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan E`; `failure_mode=mirror_chirality`. Rationale: Tuple bottom/back/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
- Key internal: **B**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi B yang mempunyai tuple orientasi valid.
- Rotation proof: putar vertikal 90° → putar maju 90° → putar maju 90° → putar maju 90° menghasilkan Top=right, Front=bottom, Right=back.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

### wu-012

- Contract: `subtest_code=WU`; `kind=scored`; `display_order=12`; `difficulty_target=hard`; `answer_type=image_choice`.
- Prompt text: Perhatikan dua tampilan kubus referensi. Pilih kubus yang menunjukkan susunan sisi yang sama setelah rotasi.
- Reference media: `wu-012-reference` → `wu/questions/wu-q012-reference.svg`.
- Face mapping internal: top=square; bottom=hexagon; front=star; back=doublecircle; right=circle; left=plus.
- Reference views internal: `top/front/right | bottom/left/back`.
- Options:
  - A. `media_id=wu-012-option-a`; `visible=top/right/front`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan A`; `failure_mode=mirror_chirality`. Rationale: Tuple top/right/front membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - B. `media_id=wu-012-option-b`; `visible=top/front/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan B`; `failure_mode=mirror_chirality`. Rationale: Tuple top/front/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - C. `media_id=wu-012-option-c`; `visible=bottom/back/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan C`; `failure_mode=mirror_chirality`. Rationale: Tuple bottom/back/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
  - D. `media_id=wu-012-option-d`; `visible=left/top/back`; `score_value=1`; `is_correct=true`; `alt_text=Kubus pilihan D`; `failure_mode=proper_rotation`. Rationale: Tuple Top/Front/Right left/top/back termasuk salah satu dari 24 rotasi proper dan mempertahankan adjacency, opposite faces, serta chirality.
  - E. `media_id=wu-012-option-e`; `visible=front/bottom/left`; `score_value=0`; `is_correct=false`; `alt_text=Kubus pilihan E`; `failure_mode=mirror_chirality`. Rationale: Tuple front/bottom/left membalik urutan siklik tiga muka; ini merupakan mirror, bukan rotasi proper.
- Key internal: **D**.
- Explanation peserta: `null`.
- Rationale internal: Mapping enam sisi unik diverifikasi melalui 24 rotasi proper. Hanya opsi D yang mempunyai tuple orientasi valid.
- Rotation proof: putar vertikal 90° → putar maju 90° menghasilkan Top=left, Front=top, Right=back.
- Cube audit: adjacency=pass; opposite-face=pass; chirality=pass; symbol-orientation=pass; small_screen=pass.
- Reviews: `ambiguity_review=pass`; `automated_visual_review=pass`; `automated_geometry_review=pass`; `automated_rotation_review=pass`; `automated_language_review=pass`; `automated_logic_review=pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=human_review_passed`; `active=false`.

## Rekap WU

- Record: 13 (1 example + 12 scored); media: 78.
- Difficulty scored: easy 4; medium 5; hard 3.
- Distribusi key scored: A=2, B=2, C=3, D=3, E=2.
- Setiap key berada dalam 24 rotasi proper. Distraktor ditolak karena pasangan sisi berlawanan atau urutan siklik mirror.

---

# Audit keseluruhan

- Total 24 record: 2 example + 22 scored.
- Total 120 opsi visual dan 144 media pertanyaan.
- Seluruh record `review_status=human_review_passed`, `active=false`, belum approved, belum frozen, dan belum diimpor.
- Tidak ada klaim kesetaraan dengan instrumen normatif, skor standar, norma, atau IQ.
- Human visual, geometry, rotation, language, dan logic review telah passed.
