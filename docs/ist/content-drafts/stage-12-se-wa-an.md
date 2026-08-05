# Draft Konten Tahap 12 — SE, WA, dan AN

> **INTERNAL REVIEW ONLY — JANGAN DIPUBLIKASIKAN KE PESERTA**

Dokumen ini memuat kunci dan rationale internal. Seluruh butir dibuat secara independen untuk **Tes Kemampuan Kognitif Adaptasi**, bukan sebagai reproduksi instrumen lain. Konten ini dibuat untuk asesmen adaptasi internal dan tidak ditujukan sebagai reproduksi instrumen psikologi normatif.

## Status dokumen

- `author_draft`: complete
- `automated_language_review`: complete (self-review awal, bukan approval manusia)
- `automated_logic_review`: complete (self-review awal, bukan approval manusia)
- `human_language_review`: pending
- `human_logic_review`: pending
- `overall_status`: in_review
- `active`: false
- Answer type seluruh butir: `single_choice`
- Scoring seluruh opsi: benar `1`, salah `0`, kosong `0`
- Heading level tiga setiap butir adalah nilai field `logical_id` record tersebut.
- Notasi opsi: `A. teks (0)` berarti `code=A`, `text=teks`, `is_correct=false`, `score_value=0`; notasi `(1, key)` berarti `is_correct=true`, `score_value=1`.
- Metadata wajib setiap record: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### Catatan matematis distribusi key

Lima opsi yang masing-masing menjadi key minimal dua kali memakai 10 dari 12 posisi. Dua posisi tersisa membuat distribusi paling seimbang menjadi `3,3,2,2,2`. Karena itu, ketentuan “maksimum satu opsi menjadi key tiga kali” tidak dapat dipenuhi bersamaan dengan minimum dua kali untuk setiap opsi tanpa membuat satu opsi muncul empat kali. Draft memilih `3,3,2,2,2` untuk menghindari konsentrasi empat key pada satu huruf. Keputusan ini wajib dikonfirmasi pada review manusia sebelum approval.

---

# SE — Melengkapi Kalimat

**Petunjuk singkat:** Pilih satu kata atau frasa yang paling tepat untuk melengkapi kalimat. Gunakan makna seluruh kalimat, bukan hanya kecocokan tata bahasa.

## SE Example

### se-example-001

- Contract: `subtest_code=SE`; `kind=example`; `display_order=0`; `difficulty_target=null`; `answer_type=single_choice`.
- Prompt: Petugas menutup jendela agar air hujan tidak ___ ke dalam ruangan.
- Options: A. mengering (`0`); B. masuk (`1`, key); C. berhenti (`0`); D. menyusut (`0`); E. mengeras (`0`).
- Key: **B**.
- Explanation peserta: Kata “masuk” paling tepat karena jendela ditutup untuk mencegah air hujan bergerak ke dalam ruangan.
- Rationale internal: Hubungan tujuan tindakan dinyatakan oleh “agar tidak”; hanya “masuk” menghasilkan tujuan yang logis.
- Difficulty basis: Example; hubungan langsung dan kosakata umum, tidak masuk distribusi difficulty.
- Reviews: `ambiguity_review=pass` (opsi lain tidak menghasilkan tujuan yang wajar); `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

## SE Scored Questions

### se-001

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=1`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Lampu lalu lintas berubah merah, sehingga pengendara harus ___.
- Options: A. berbelok (`0`); B. melaju (`0`); C. berhenti (`1`, key); D. mendahului (`0`); E. berputar (`0`).
- Key: **C**. Explanation: `null`.
- Rationale internal: “Sehingga” menandai akibat; tindakan yang sesuai saat lampu merah adalah berhenti.
- Difficulty basis: Hubungan sebab-akibat langsung dan distraktor berbeda jelas.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### se-002

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=2`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Rapat evaluasi baru dapat dimulai setelah seluruh data berhasil ___.
- Options: A. terkumpul (`1`, key); B. terpisah (`0`); C. tertutup (`0`); D. terbagi (`0`); E. terlipat (`0`).
- Key: **A**. Explanation: `null`.
- Rationale internal: Evaluasi memerlukan data yang telah terkumpul; semua opsi cocok secara bentuk, tetapi hanya A sesuai tujuan rapat.
- Difficulty basis: Memerlukan pemahaman urutan prasyarat, dengan distraktor bentuk kata serupa.
- Reviews: `ambiguity_review=pass` (tidak ada kondisi tambahan yang mendukung opsi lain); `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### se-003

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=3`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Menjelang perjalanan, awan gelap membuat langit tampak ___.
- Options: A. cerah (`0`); B. bening (`0`); C. luas (`0`); D. tinggi (`0`); E. mendung (`1`, key).
- Key: **E**. Explanation: `null`.
- Rationale internal: Awan gelap membuat langit tampak mendung dan menjadi alasan langsung membawa payung.
- Difficulty basis: Konteks sehari-hari dan hubungan sebab-akibat langsung.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### se-004

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=4`; `difficulty_target=hard`; `answer_type=single_choice`.
- Prompt: Usulan itu terdengar menarik, tetapi belum dapat diterapkan karena uraian langkah kerjanya masih ___.
- Options: A. ringkas (`0`); B. samar (`1`, key); C. lugas (`0`); D. stabil (`0`); E. tegas (`0`).
- Key: **B**. Explanation: `null`.
- Rationale internal: Hambatan penerapan muncul karena uraian tidak cukup jelas; “samar” paling tepat, sedangkan “ringkas” tidak selalu berarti tidak jelas.
- Difficulty basis: Distraktor semantik dekat mengharuskan peserta membedakan singkat dari tidak jelas.
- Reviews: `ambiguity_review=pass` (kontras “menarik, tetapi belum dapat diterapkan” mengunci kebutuhan kejelasan); `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### se-005

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=5`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Jalan utama ditutup sementara, maka pengemudi perlu mencari rute ___.
- Options: A. tetap (`0`); B. sempit (`0`); C. terdekat (`0`); D. alternatif (`1`, key); E. terpanjang (`0`).
- Key: **D**. Explanation: `null`.
- Rationale internal: Penutupan rute utama menuntut rute pengganti; “alternatif” menyatakan fungsi itu secara tepat.
- Difficulty basis: Memerlukan pemahaman konsekuensi praktis; beberapa opsi dapat menjadi sifat rute tetapi bukan fungsi yang diperlukan.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### se-006

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=6`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Agar dokumen mudah ditemukan kembali, berkas disusun secara ___.
- Options: A. terpisah (`0`); B. terburu-buru (`0`); C. teratur (`1`, key); D. tertutup (`0`); E. sementara (`0`).
- Key: **C**. Explanation: `null`.
- Rationale internal: Susunan teratur secara langsung mendukung kemudahan menemukan kembali dokumen.
- Difficulty basis: Hubungan tujuan langsung dan kosakata umum.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### se-007

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=7`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Dengan memprioritaskan langkah penting, tim dapat bekerja lebih ___ meskipun waktu persiapan singkat.
- Options: A. perlahan (`0`); B. efisien (`1`, key); C. terpisah (`0`); D. longgar (`0`); E. spontan (`0`).
- Key: **B**. Explanation: `null`.
- Rationale internal: Prioritas membantu menggunakan waktu dan usaha secara efisien.
- Difficulty basis: Peserta perlu menghubungkan strategi prioritas dengan cara kerja, bukan sekadar kecepatan.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### se-008

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=8`; `difficulty_target=hard`; `answer_type=single_choice`.
- Prompt: Pernyataan itu tampak meyakinkan, namun bukti yang diberikan belum cukup ___ kesimpulannya.
- Options: A. mengulang (`0`); B. menyertai (`0`); C. membatasi (`0`); D. menggantikan (`0`); E. mendukung (`1`, key).
- Key: **E**. Explanation: `null`.
- Rationale internal: Fungsi bukti adalah mendukung kesimpulan; kedekatan konteks opsi lain tidak membentuk relasi pembuktian.
- Difficulty basis: Konteks abstrak dan distraktor verba yang sama-sama dapat berkaitan dengan pernyataan.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### se-009

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=9`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Setelah hujan berhenti, udara terasa lebih ___.
- Options: A. sejuk (`1`, key); B. bising (`0`); C. padat (`0`); D. tajam (`0`); E. kasar (`0`).
- Key: **A**. Explanation: `null`.
- Rationale internal: “Sejuk” merupakan sifat udara yang wajar setelah hujan; opsi lain tidak sesuai konteks sensasi udara.
- Difficulty basis: Makna kontekstual langsung dengan distraktor berbeda jelas.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### se-010

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=10`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Petunjuk diringkas supaya pembaca dapat memahami urutan kerja secara ___.
- Options: A. utuh (`0`); B. acak (`0`); C. sempit (`0`); D. jelas (`1`, key); E. kasar (`0`).
- Key: **D**. Explanation: `null`.
- Rationale internal: Perangkuman petunjuk bertujuan memperjelas urutan kerja; “utuh” tidak menjelaskan cara pemahaman.
- Difficulty basis: Memerlukan pemilihan adverbia yang paling sesuai dengan tujuan komunikasi.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### se-011

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=11`; `difficulty_target=hard`; `answer_type=single_choice`.
- Prompt: Kedua rencana menawarkan manfaat serupa, sehingga keputusan perlu dibuat berdasarkan kriteria yang paling ___ dengan tujuan utama.
- Options: A. konsisten (`0`); B. seimbang (`0`); C. relevan (`1`, key); D. serasi (`0`); E. tetap (`0`).
- Key: **C**. Explanation: `null`.
- Rationale internal: Kriteria harus memiliki hubungan langsung dengan tujuan; “relevan” menyatakan hubungan itu paling presisi.
- Difficulty basis: Distraktor semantik dekat dan konteks pengambilan keputusan abstrak.
- Reviews: `ambiguity_review=pass` (konsisten dapat menjadi sifat kriteria, tetapi tidak menjamin keterkaitan dengan tujuan); `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### se-012

- Contract: `subtest_code=SE`; `kind=scored`; `display_order=12`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Sebelum mengirim laporan, Nara memeriksa ulang angka-angkanya untuk ___ kesalahan terbawa ke versi akhir.
- Options: A. menyusun (`0`); B. menunda (`0`); C. merangkum (`0`); D. mencegah (`1`, key); E. menyalin (`0`).
- Key: **D**. Explanation: `null`.
- Rationale internal: Pemeriksaan ulang dilakukan untuk mencegah kesalahan terbawa ke laporan versi akhir.
- Difficulty basis: Hubungan tujuan cukup langsung, tetapi semua opsi merupakan verba tindakan yang gramatikal.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### Rekap SE

- Scored: 12; example: 1; seluruh record: 13.
- Difficulty scored: easy 4 (`001,003,006,009`); medium 5 (`002,005,007,010,012`); hard 3 (`004,008,011`).
- Distribusi key scored: A=2, B=2, C=3, D=3, E=2.
- Urutan key: C–A–E–B–D–C–B–E–A–D–C–D (tidak membentuk siklus A–E).

---

# WA — Kata yang Berbeda

**Petunjuk singkat:** Empat pilihan mempunyai kategori atau fungsi bersama. Pilih satu pilihan yang tidak termasuk kelompok tersebut.

## WA Example

### wa-example-001

- Contract: `subtest_code=WA`; `kind=example`; `display_order=0`; `difficulty_target=null`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. apel (`0`); B. mangga (`0`); C. wortel (`1`, key); D. jeruk (`0`); E. pisang (`0`).
- Key: **C**.
- Explanation peserta: Apel, mangga, jeruk, dan pisang adalah buah. Wortel adalah sayuran, sehingga menjadi pilihan yang berbeda.
- Rationale internal: Kategori bersama empat opsi adalah buah; wortel berbeda sebagai sayuran. Alternatif warna, bentuk, dan cara konsumsi tidak membentuk kelompok empat yang lebih kuat.
- Difficulty basis: Example; kategori konkret dan umum, tidak masuk distribusi difficulty.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

## WA Scored Questions

### wa-001

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=1`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. meja (`0`); B. sepeda (`1`, key); C. kursi (`0`); D. lemari (`0`); E. rak (`0`).
- Key: **B**. Explanation: `null`.
- Rationale internal: Meja, kursi, lemari, dan rak adalah perabot; sepeda adalah alat transportasi. Dasar bahan atau lokasi penggunaan tidak menghasilkan kelompok empat yang lebih kuat.
- Difficulty basis: Kategori fungsi konkret dengan pemisah jelas.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### wa-002

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=2`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. mendengar (`0`); B. melihat (`0`); C. mencium (`0`); D. menulis (`1`, key); E. mengecap (`0`).
- Key: **D**. Explanation: `null`.
- Rationale internal: Empat opsi adalah aktivitas menerima rangsangan indra; menulis adalah aktivitas menghasilkan simbol. Alternatif organ tubuh tidak mengubah pemisahan fungsional ini.
- Difficulty basis: Fungsi indra dikenal umum dan odd-one-out langsung.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### wa-003

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=3`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. puncak (`1`, key); B. akar (`0`); C. fondasi (`0`); D. landasan (`0`); E. dasar (`0`).
- Key: **A**. Explanation: `null`.
- Rationale internal: Akar, fondasi, landasan, dan dasar sama-sama menyatakan penopang atau bagian bawah; puncak menyatakan bagian teratas. Penggunaan literal dan kiasan telah diperiksa dan tetap mempertahankan arah bawah versus atas.
- Difficulty basis: Memerlukan abstraksi makna bersama lintas konteks literal dan kiasan.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### wa-004

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=4`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. merah (`0`); B. biru (`0`); C. manis (`1`, key); D. hijau (`0`); E. kuning (`0`).
- Key: **C**. Explanation: `null`.
- Rationale internal: Empat opsi adalah warna; manis adalah rasa. Tidak ada pengelompokan alternatif utama yang menyatukan empat opsi selain kategori warna.
- Difficulty basis: Kategori sifat inderawi konkret dengan pemisah jelas.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### wa-005

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=5`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. dokter (`0`); B. perawat (`0`); C. apoteker (`0`); D. fisioterapis (`0`); E. arsitek (`1`, key).
- Key: **E**. Explanation: `null`.
- Rationale internal: Empat opsi merupakan profesi layanan kesehatan; arsitek bekerja dalam perancangan bangunan. Dasar pendidikan atau tempat kerja tidak membentuk alternatif kelompok empat yang lebih tepat.
- Difficulty basis: Memerlukan pengenalan bidang fungsi profesi, tetapi bukan pengetahuan khusus.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### wa-006

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=6`; `difficulty_target=hard`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. menyimpulkan (`0`); B. menyalin (`1`, key); C. menafsirkan (`0`); D. menganalisis (`0`); E. membandingkan (`0`).
- Key: **B**. Explanation: `null`.
- Rationale internal: Empat opsi mengolah informasi untuk menghasilkan pemahaman atau penilaian; menyalin hanya mereproduksi informasi. Alternatif “semua aktivitas kognitif” terlalu luas dan tidak sekuat perbedaan transformasi versus reproduksi.
- Difficulty basis: Kategori proses mental abstrak dengan kata-kata yang semuanya tampak sebagai kegiatan informasi.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### wa-007

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=7`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. benih (`0`); B. bibit (`0`); C. tunas (`0`); D. ranting (`1`, key); E. kecambah (`0`).
- Key: **D**. Explanation: `null`.
- Rationale internal: Benih, bibit, tunas, dan kecambah berkaitan dengan tahap awal pertumbuhan tanaman; ranting merupakan bagian tanaman yang telah berkembang. Alternatif “bagian tanaman” tidak mencakup empat opsi secara setara.
- Difficulty basis: Memerlukan pengelompokan berdasarkan tahap proses, bukan sekadar asosiasi dengan tanaman.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### wa-008

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=8`; `difficulty_target=hard`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. janji (`0`); B. kontrak (`0`); C. perkiraan (`1`, key); D. kesepakatan (`0`); E. komitmen (`0`).
- Key: **C**. Explanation: `null`.
- Rationale internal: Janji, kontrak, kesepakatan, dan komitmen menyatakan keterikatan untuk melakukan sesuatu; perkiraan menyatakan dugaan. Tingkat formalitas berbeda, tetapi dasar keterikatan tetap mengelompokkan empat opsi.
- Difficulty basis: Konsep abstrak dengan distraktor yang sama-sama berkaitan dengan pernyataan tentang masa depan.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### wa-009

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=9`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. kubus (`1`, key); B. persegi (`0`); C. lingkaran (`0`); D. segitiga (`0`); E. trapesium (`0`).
- Key: **A**. Explanation: `null`.
- Rationale internal: Persegi, lingkaran, segitiga, dan trapesium adalah bentuk dua dimensi; kubus adalah bentuk tiga dimensi. Dasar geometri ini tunggal dan jelas.
- Difficulty basis: Perbedaan dimensi bentuk dikenal umum.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### wa-010

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=10`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. mengiris (`0`); B. mencincang (`0`); C. memarut (`0`); D. mengupas (`0`); E. merebus (`1`, key).
- Key: **E**. Explanation: `null`.
- Rationale internal: Empat opsi mengubah bahan melalui tindakan mekanis dengan alat; merebus menggunakan panas. Semua dapat menjadi proses persiapan makanan, tetapi mekanisme membentuk pemisahan yang jelas.
- Difficulty basis: Memerlukan klasifikasi berdasarkan proses, bukan bidang penggunaan umum.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### wa-011

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=11`; `difficulty_target=hard`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. kompas (`0`); B. peta (`0`); C. jadwal (`1`, key); D. alamat (`0`); E. koordinat (`0`).
- Key: **C**. Explanation: `null`.
- Rationale internal: Kompas, peta, alamat, dan koordinat membantu menentukan arah atau lokasi; jadwal mengatur waktu. Alternatif “alat bantu perjalanan” terlalu kontekstual dan kurang mendasar daripada informasi spasial versus temporal.
- Difficulty basis: Pengelompokan fungsi abstrak spasial dengan distraktor temporal yang masih relevan dalam perjalanan.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### wa-012

- Contract: `subtest_code=WA`; `kind=scored`; `display_order=12`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Pilih kata yang berbeda dari empat kata lainnya.
- Options: A. mengawali (`0`); B. memulai (`0`); C. membuka (`0`); D. mengakhiri (`1`, key); E. merintis (`0`).
- Key: **D**. Explanation: `null`.
- Rationale internal: Empat opsi menyatakan tindakan memulai; mengakhiri menyatakan tindakan menutup atau menyelesaikan. Makna “membuka” yang lain tidak mengalahkan penggunaan umum sebagai awal suatu kegiatan.
- Difficulty basis: Memerlukan abstraksi sinonimi fungsional dengan satu lawan arah.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### Rekap WA

- Scored: 12; example: 1; seluruh record: 13.
- Difficulty scored: easy 4 (`001,002,004,009`); medium 5 (`003,005,007,010,012`); hard 3 (`006,008,011`).
- Distribusi key scored: A=2, B=2, C=3, D=3, E=2.
- Urutan key: B–D–A–C–E–B–D–C–A–E–C–D (tidak membentuk siklus A–E).

---

# AN — Analogi

**Petunjuk singkat:** Tentukan hubungan pada pasangan pertama, lalu pilih kata yang membentuk hubungan paling setara pada pasangan kedua: `A : B = C : ?`.

## AN Example

### an-example-001

- Contract: `subtest_code=AN`; `kind=example`; `display_order=0`; `difficulty_target=null`; `answer_type=single_choice`.
- Prompt: Kunci : Membuka = Pensil : ?
- Options: A. menghapus (`0`); B. menulis (`1`, key); C. mengukur (`0`); D. melipat (`0`); E. memotong (`0`).
- Key: **B**.
- Explanation peserta: Kunci digunakan untuk membuka; dengan hubungan yang sama, pensil digunakan untuk menulis.
- Rationale internal: Relasi alat terhadap fungsi utama identik pada kedua pasangan.
- Difficulty basis: Example; relasi alat-fungsi langsung, tidak masuk distribusi difficulty.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

## AN Scored Questions

### an-001

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=1`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Sapu : Membersihkan = Gunting : ?
- Options: A. menempel (`0`); B. menimbang (`0`); C. mengikat (`0`); D. menyimpan (`0`); E. memotong (`1`, key).
- Key: **E**. Explanation: `null`.
- Rationale internal: Sapu digunakan untuk membersihkan; gunting digunakan untuk memotong.
- Difficulty basis: Relasi alat-fungsi langsung dengan distraktor berbeda.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### an-002

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=2`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Bab : Buku = Adegan : ?
- Options: A. kamera (`0`); B. dialog (`0`); C. film (`1`, key); D. panggung (`0`); E. pemeran (`0`).
- Key: **C**. Explanation: `null`.
- Rationale internal: Bab merupakan bagian dari buku; adegan merupakan bagian dari film.
- Difficulty basis: Relasi bagian-keseluruhan lintas media dengan distraktor yang masih terkait film.
- Reviews: `ambiguity_review=pass` (adegan juga dapat ada dalam drama, tetapi “film” satu-satunya keseluruhan karya pada opsi); `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### an-003

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=3`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Dokter : Pasien = Guru : ?
- Options: A. kelas (`0`); B. murid (`1`, key); C. buku (`0`); D. papan (`0`); E. sekolah (`0`).
- Key: **B**. Explanation: `null`.
- Rationale internal: Dokter memberikan layanan profesional kepada pasien; guru mengajar murid.
- Difficulty basis: Relasi profesi terhadap penerima layanan sangat umum.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### an-004

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=4`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Pembekuan : Es = Penguapan : ?
- Options: A. embun (`0`); B. hujan (`0`); C. cairan (`0`); D. uap (`1`, key); E. kristal (`0`).
- Key: **D**. Explanation: `null`.
- Rationale internal: Pembekuan menghasilkan es; penguapan menghasilkan uap.
- Difficulty basis: Relasi proses-hasil memerlukan pemetaan perubahan wujud, tetapi tetap pengetahuan umum.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### an-005

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=5`; `difficulty_target=hard`; `answer_type=single_choice`.
- Prompt: Peta : Wilayah = Diagram : ?
- Options: A. hubungan (`1`, key); B. pensil (`0`); C. kertas (`0`); D. warna (`0`); E. ukuran (`0`).
- Key: **A**. Explanation: `null`.
- Rationale internal: Peta merepresentasikan wilayah; diagram merepresentasikan hubungan antarkomponen.
- Difficulty basis: Relasi representasi abstrak dua domain; distraktor merupakan atribut atau media, bukan objek yang direpresentasikan.
- Reviews: `ambiguity_review=pass` (diagram dapat merepresentasikan proses, tetapi “hubungan” mencakup fungsi representasional yang paling umum dan satu-satunya objek abstrak relevan pada opsi); `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### an-006

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=6`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Tepung : Roti = Tanah liat : ?
- Options: A. pasir (`0`); B. batu (`0`); C. kayu (`0`); D. kaca (`0`); E. gerabah (`1`, key).
- Key: **E**. Explanation: `null`.
- Rationale internal: Tepung merupakan bahan untuk membuat roti; tanah liat merupakan bahan untuk membuat gerabah.
- Difficulty basis: Relasi bahan-produk konkret dan umum.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### an-007

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=7`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Perpustakaan : Buku = Galeri : ?
- Options: A. tiket (`0`); B. dinding (`0`); C. pengunjung (`0`); D. lukisan (`1`, key); E. penjaga (`0`).
- Key: **D**. Explanation: `null`.
- Rationale internal: Perpustakaan merupakan tempat koleksi buku; galeri merupakan tempat koleksi atau pajangan lukisan.
- Difficulty basis: Relasi tempat terhadap isi dengan distraktor yang sama-sama dapat berada di lokasi.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### an-008

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=8`; `difficulty_target=hard`; `answer_type=single_choice`.
- Prompt: Gerimis : Hujan = Senyum : ?
- Options: A. sapaan (`0`); B. tawa (`1`, key); C. tangis (`0`); D. diam (`0`); E. tatapan (`0`).
- Key: **B**. Explanation: `null`.
- Rationale internal: Gerimis merupakan bentuk presipitasi berintensitas lebih rendah daripada hujan; senyum merupakan ekspresi kegembiraan berintensitas lebih rendah daripada tawa.
- Difficulty basis: Relasi tingkat/intensitas dipetakan antar dua domain berbeda.
- Reviews: `ambiguity_review=pass` (relasi bukan sebab atau urutan waktu; hanya B mempertahankan peningkatan intensitas ekspresi positif); `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### an-009

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=9`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Termometer : Suhu = Timbangan : ?
- Options: A. panjang (`0`); B. waktu (`0`); C. berat (`1`, key); D. arah (`0`); E. bunyi (`0`).
- Key: **C**. Explanation: `null`.
- Rationale internal: Termometer mengukur suhu; timbangan mengukur berat.
- Difficulty basis: Relasi alat-objek ukur langsung, dengan opsi berupa besaran berbeda.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### an-010

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=10`; `difficulty_target=easy`; `answer_type=single_choice`.
- Prompt: Kandang : Burung = Akuarium : ?
- Options: A. ikan (`1`, key); B. air (`0`); C. kaca (`0`); D. pasir (`0`); E. tanaman (`0`).
- Key: **A**. Explanation: `null`.
- Rationale internal: Kandang menjadi tempat pemeliharaan burung; akuarium menjadi tempat pemeliharaan ikan.
- Difficulty basis: Relasi tempat terhadap penghuni konkret dan familiar.
- Reviews: `ambiguity_review=pass` (air adalah isi akuarium, tetapi bukan penghuni yang setara dengan burung); `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### an-011

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=11`; `difficulty_target=hard`; `answer_type=single_choice`.
- Prompt: Draf awal : Naskah final = Sketsa awal : ?
- Options: A. kanvas kosong (`0`); B. pensil warna (`0`); C. bingkai kayu (`0`); D. lukisan final (`1`, key); E. garis bantu (`0`).
- Key: **D**. Explanation: `null`.
- Rationale internal: Draf awal dikembangkan menjadi naskah final; sketsa awal dikembangkan menjadi lukisan final.
- Difficulty basis: Relasi tahap awal-hasil final melibatkan proses tersirat dan distraktor yang terkait media gambar.
- Reviews: `ambiguity_review=pass`; `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### an-012

- Contract: `subtest_code=AN`; `kind=scored`; `display_order=12`; `difficulty_target=medium`; `answer_type=single_choice`.
- Prompt: Tanda tanya : Pertanyaan = Tanda seru : ?
- Options: A. jawaban (`0`); B. jeda (`0`); C. seruan (`1`, key); D. kutipan (`0`); E. perintah (`0`).
- Key: **C**. Explanation: `null`.
- Rationale internal: Tanda tanya menandai pertanyaan; tanda seru menandai seruan.
- Difficulty basis: Relasi simbol-fungsi dengan “perintah” sebagai distraktor dekat karena tanda seru dapat menyertai kalimat perintah, tetapi tidak terbatas padanya.
- Reviews: `ambiguity_review=pass` (seruan adalah fungsi tanda yang paling umum dan setara dengan kategori pertanyaan); `language_review_notes=automated_language_review: pass`; `logic_review_notes=automated_logic_review: pass`; `qc_status=pass`.
- Metadata: `content_origin=original_internal`; `source_reference=null`; `copyright_status=internally_authored`; `normative_compatibility=none`; `review_status=in_review`; `active=false`.

### Rekap AN

- Scored: 12; example: 1; seluruh record: 13.
- Difficulty scored: easy 4 (`001,003,006,010`); medium 5 (`002,004,007,009,012`); hard 3 (`005,008,011`).
- Distribusi key scored: A=2, B=2, C=3, D=3, E=2.
- Urutan key: E–C–B–D–A–E–D–B–C–A–D–C (tidak membentuk siklus A–E).

---

# Audit lintas subtes

- Total: 36 scored + 3 example = 39 record.
- Total opsi: 39 × 5 = 195 opsi.
- Setiap record mempunyai tepat satu key; binary score hanya `1/0`.
- Setiap subtes mempunyai difficulty scored 4 easy, 5 medium, 3 hard.
- Setiap subtes mempunyai distribusi key A=2, B=2, C=3, D=3, E=2; example tidak dihitung.
- Pengecualian matematis distribusi key telah dicatat dan masih menunggu konfirmasi manusia.
- Semua record `in_review` dan `active=false`; tidak ada status approved/frozen.
- Seluruh `qc_status=pass`; tidak ada record berstatus revise atau reject pada self-review awal.
- Tidak ada data pribadi, nama merek/tokoh aktual, fakta cepat berubah, materi sensitif, atau klaim normatif.
- Human language review dan human logic review masih wajib sebelum approval berikutnya.
