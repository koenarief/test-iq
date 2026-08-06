# Draft Konten Tahap 16 — ME

> **INTERNAL REVIEW ONLY — MEMUAT PASANGAN TARGET, KUNCI, DAN RATIONALE. JANGAN DIKIRIM SEBAGAI PAYLOAD PESERTA.**

Konten ME ini dibuat secara internal sebagai asesmen ingatan pasangan informasi dan tidak ditujukan sebagai reproduksi atau pengganti instrumen psikologi normatif.

## Status dan kontrak

- Produk: **Tes Kemampuan Kognitif Adaptasi**.
- `author_draft`: complete.
- `automated_language_review`: passed.
- `automated_logic_review`: passed.
- `automated_association_review`: passed.
- `automated_memory_design_review`: passed.
- `automated_leakage_review`: passed.
- `human_language_review`: passed.
- `human_logic_review`: passed.
- `human_association_review`: passed.
- `human_memory_design_review`: passed.
- `human_leakage_review`: passed.
- `overall_status`: human_review_passed.
- `review_status`: human_review_passed.
- `active`: false.
- `approved`: false.
- `frozen`: false.
- `imported`: false.
- Answer type: `single_choice`; skor binary 1/0; maksimum skor 12.
- Fase memorization: 120 detik; answering: 240 detik; total: 360 detik; single start.
- Record: 1 example + 12 scored questions.
- Materi utama: 15 pasangan; 12 diuji tepat satu kali dan 3 menjadi filler internal.
- Draft terstruktur kanonis untuk review berada di `stage-16-me-data-draft.json`; file tersebut tidak dibaca runtime dan bukan dataset produksi.

Tidak ada klaim IQ, norma, skor standar, atau kesetaraan dengan instrumen psikologi normatif.

## Desain peserta

### Petunjuk

Anda akan melihat 15 pasangan kata selama 2 menit. Ingat hubungan setiap pasangan. Setelah waktu menghafal berakhir, materi akan ditutup otomatis dan tidak dapat dibuka kembali. Anda kemudian memiliki 4 menit untuk menjawab 12 soal pilihan ganda. Pilih satu jawaban untuk setiap soal. Contoh tidak dihitung dalam skor.

### Example `me-example-001`

Materi contoh terpisah:

| Cue | Associate |
|---|---|
| tas | senja |
| roti | pagar |
| lampu | pasir |
| meja | kabut |
| bukit | daun |

Prompt: **Dalam materi contoh, kata “roti” dipasangkan dengan kata apa?**

| Opsi | Teks | Skor |
|---|---|---:|
| A | kabut | 0 |
| B | pagar | 1 |
| C | senja | 0 |
| D | pasir | 0 |
| E | daun | 0 |

Penjelasan peserta: Jawaban yang benar adalah pagar karena pada materi contoh terdapat pasangan roti — pagar. Saat subtes utama dimulai, materi hafalan akan ditutup setelah 2 menit dan tidak dapat dibuka kembali. Contoh ini tidak dihitung dalam skor.

Catatan internal: seluruh opsi example berasal dari sisi associate pada lima pasangan materi contoh. Prosedur pilihan dengan demikian sama dengan scored questions. Seluruh kata example terpisah dari materi utama dan example tidak masuk skor maupun timer utama.

## Materi utama dan status internal

Urutan berikut deterministik. Kolom `tested` dan `question` hanya untuk reviewer internal dan dilarang masuk tampilan peserta.

| Urutan | Pair ID | Cue | Associate | Tested | Question |
|---:|---|---|---|---|---|
| 1 | `me-pair-001` | jendela | irama | ya | `me-001` |
| 2 | `me-pair-002` | sawah | kancing | ya | `me-002` |
| 3 | `me-pair-003` | bantal | lorong | ya | `me-003` |
| 4 | `me-pair-004` | ember | cerita | tidak | — |
| 5 | `me-pair-005` | sepatu | kalender | ya | `me-005` |
| 6 | `me-pair-006` | sungai | pensil | ya | `me-004` |
| 7 | `me-pair-007` | cermin | ladang | ya | `me-007` |
| 8 | `me-pair-008` | tangga | rempah | ya | `me-006` |
| 9 | `me-pair-009` | piring | hutan | tidak | — |
| 10 | `me-pair-010` | payung | kerikil | ya | `me-009` |
| 11 | `me-pair-011` | kertas | balkon | ya | `me-008` |
| 12 | `me-pair-012` | lemari | ombak | ya | `me-010` |
| 13 | `me-pair-013` | gelas | taman | tidak | — |
| 14 | `me-pair-014` | kursi | petir | ya | `me-011` |
| 15 | `me-pair-015` | kapal | pita | ya | `me-012` |

Tested pair: `me-pair-001`, `me-pair-002`, `me-pair-003`, `me-pair-005`, `me-pair-006`, `me-pair-007`, `me-pair-008`, `me-pair-010`, `me-pair-011`, `me-pair-012`, `me-pair-014`, `me-pair-015`.

Filler pair: `me-pair-004`, `me-pair-009`, `me-pair-013`. Ketiganya tersebar pada posisi 4, 9, dan 13, tampil identik dengan pair lain, digunakan sewajarnya sebagai sumber distraktor, dan tidak menjadi target soal.

### Audit asosiasi 15 pasangan

| Pair ID | Natural | Semantic | Phonological | Outlier | Status | Catatan ringkas |
|---|---|---|---|---|---|---|
| `me-pair-001` | none | none | none | no | pass | Benda bangunan dan pola bunyi tidak berhubungan umum. |
| `me-pair-002` | none | none | none | no | pass | Lahan pertanian dan pengait pakaian berbeda kategori. |
| `me-pair-003` | none | none | none | no | pass | Perlengkapan tidur dan jalur penghubung tidak terkait fungsi. |
| `me-pair-004` | none | none | none | no | pass | Wadah dan narasi tidak memiliki relasi langsung. |
| `me-pair-005` | none | none | none | no | pass | Alas kaki dan penanda tanggal tidak berasosiasi umum. |
| `me-pair-006` | none | none | none | no | pass | Aliran air dan alat tulis tidak berhubungan langsung. |
| `me-pair-007` | none | none | none | no | pass | Permukaan pemantul dan lahan budidaya berbeda fungsi. |
| `me-pair-008` | none | none | none | no | pass | Sarana berpindah tingkat dan bahan masakan tidak berhubungan. |
| `me-pair-009` | none | none | none | no | pass | Peralatan makan dan kawasan pepohonan berbeda kategori. |
| `me-pair-010` | none | none | none | no | pass | Pelindung portabel dan batu kecil tidak berhubungan. |
| `me-pair-011` | none | none | none | no | pass | Bahan lembaran dan bagian bangunan tidak memiliki relasi utama. |
| `me-pair-012` | none | none | none | no | pass | Perabot penyimpanan dan gerak air tidak berkaitan langsung. |
| `me-pair-013` | weak | none | none | no | pass | Dapat hadir di ruang sama, tetapi tanpa relasi khas atau fungsi bersama. |
| `me-pair-014` | none | none | none | no | pass | Tempat duduk dan gejala cuaca tidak berhubungan umum. |
| `me-pair-015` | none | none | none | no | pass | Kendaraan air dan bahan berbentuk jalur tidak memiliki relasi umum. |

Semua 30 kata materi utama unik secara global, mudah dibaca, non-sensitif, bukan merek atau nama tokoh, tidak berupa angka, tidak berima kuat, dan tidak membentuk urutan alfabet atau pola awalan yang membantu hafalan. Audit otomatis/editorial dan review manusia telah lulus.

## Dua belas scored questions

Format prompt konsisten:

- Forward: `Dalam materi hafalan, kata “X” dipasangkan dengan kata apa?`
- Reverse: `Dalam materi hafalan, kata apa yang dipasangkan dengan “Y”?`

Semua opsi berasal dari materi utama pada sisi jawaban yang sama. Tanda `*` di bawah hanya kunci internal.

| ID | Difficulty | Arah | Target | A | B | C | D | E | Key |
|---|---|---|---|---|---|---|---|---|---|
| `me-001` | easy | cue→associate | `me-pair-001` | kancing | cerita | irama* | hutan | petir | C |
| `me-002` | easy | cue→associate | `me-pair-002` | kancing* | kalender | pensil | taman | pita | A |
| `me-003` | medium | cue→associate | `me-pair-003` | irama | rempah | balkon | lorong* | ombak | D |
| `me-004` | easy | associate→cue | `me-pair-006` | jendela | sungai* | ember | piring | gelas | B |
| `me-005` | medium | cue→associate | `me-pair-005` | cerita | ladang | kerikil | petir | kalender* | E |
| `me-006` | easy | associate→cue | `me-pair-008` | sawah | sepatu | tangga* | payung | bantal | C |
| `me-007` | medium | cue→associate | `me-pair-007` | pensil | hutan | pita | ladang* | kancing | D |
| `me-008` | medium | associate→cue | `me-pair-011` | kertas* | cermin | lemari | kursi | kapal | A |
| `me-009` | hard | cue→associate | `me-pair-010` | balkon | taman | kerikil* | lorong | rempah | C |
| `me-010` | medium | associate→cue | `me-pair-012` | piring | gelas | jendela | ember | lemari* | E |
| `me-011` | hard | associate→cue | `me-pair-014` | payung | kursi* | bantal | sawah | sepatu | B |
| `me-012` | hard | associate→cue | `me-pair-015` | sungai | tangga | kertas | kapal* | cermin | D |

Prompt lengkap, `source_pair_id`, scoring, option rationale, ambiguity review, leakage review, serta metadata tiap record tersedia di JSON draft internal.

Difficulty bersifat editorial dan tidak ditentukan hanya oleh arah recall. Dasarnya adalah kedekatan posisi pair, kompetisi distractor, kesamaan kategori/familiaritas kata, dan posisi target dalam materi.

| ID | Difficulty rationale internal |
|---|---|
| `me-001` | Target berada pada awal materi; distractor tersebar dan berbeda kategori sehingga kompetisi relatif rendah. |
| `me-002` | Target dekat awal materi dan distractor berasal dari posisi tersebar dengan kompetisi kategori rendah. |
| `me-003` | Distractor mencakup pair awal berdekatan dan beberapa associate dengan panjang serupa. |
| `me-004` | Cue distractor berasal dari posisi berjauhan dan kategori yang mudah dibedakan. |
| `me-005` | Ada distractor dari pair yang berdekatan dengan target dan beberapa associate mempunyai familiaritas serupa. |
| `me-006` | Cue pilihan berbeda kategori dan sumbernya cukup tersebar dalam materi. |
| `me-007` | Distractor dari posisi 6 dan 9 mengapit target pada posisi 7. |
| `me-008` | Beberapa cue distractor berasal dari posisi 12, 14, dan 15 yang dekat dengan target 11 serta memiliki familiaritas serupa. |
| `me-009` | Tiga distractor berasal dari posisi 8, 11, dan 13 yang dekat dengan target 10 serta bersaing dalam panjang/familiaritas. |
| `me-010` | Seluruh opsi berupa benda konkret sehari-hari, tetapi posisi distractor cukup tersebar sehingga kompetisi dinilai medium. |
| `me-011` | Semua opsi berupa benda konkret sangat familiar dengan panjang relatif serupa. |
| `me-012` | Distractor terkonsentrasi pada posisi 6–11 dan bersaing sebagai kelompok tengah terhadap target akhir. |

### Rekap distribusi

- Logical ID: `me-example-001`, `me-001`–`me-012`, `me-pair-001`–`me-pair-015`.
- Difficulty: easy 4 (`me-001`, `me-002`, `me-004`, `me-006`); medium 5 (`me-003`, `me-005`, `me-007`, `me-008`, `me-010`); hard 3 (`me-009`, `me-011`, `me-012`).
- Direction per difficulty: easy 2 forward/2 reverse; medium 3 forward/2 reverse; hard 1 forward/2 reverse.
- Recall direction: cue→associate 6 (`me-001`, `me-002`, `me-003`, `me-005`, `me-007`, `me-009`); associate→cue 6 (`me-004`, `me-006`, `me-008`, `me-010`, `me-011`, `me-012`).
- Urutan key: `C, A, D, B, E, C, D, A, C, E, B, D`.
- Distribusi key: A=2, B=2, C=3, D=3, E=2.
- Setiap target pair diuji tepat satu kali; filler tidak menjadi target.
- Tepat satu opsi benar skor 1 per soal; empat opsi lain skor 0.

### Frekuensi distraktor

| Frekuensi | Kata |
|---:|---|
| 2 | balkon, bantal, cerita, cermin, ember, gelas, hutan, jendela, kancing, payung, pensil, petir, piring, pita, rempah, sawah, sepatu, taman |
| 1 | irama, kalender, kapal, kerikil, kertas, kursi, ladang, lemari, lorong, ombak, sungai, tangga |

Frekuensi maksimum adalah 2, di bawah batas 4. Filler tidak selalu dipakai dan tidak memiliki pola posisi khusus.

## Kontrak tampilan materi

Urutan 15 pair harus tetap. Tampilan target adalah grid responsif maksimal tiga kolom pada desktop dan tetap terbaca pada mobile, tanpa nomor, warna pengelompokan, ikon, gambar, atau penekanan berbeda. Payload memorization hanya memerlukan `cue` dan `associate`; status tested/filler, logical ID, key, source pair, dan rationale dilarang masuk payload.

Runtime tidak diubah pada Tahap 16. Panel hafalan saat ini merender satu string pada satu elemen paragraf dengan `whitespace-pre-line`, sehingga kontrak tiga kolom belum dapat dipenuhi secara terstruktur. Ini adalah blocker integrasi sebelum aktivasi, bukan alasan mengubah konten draft.

## Audit lifecycle ME secara read-only

Audit dilakukan terhadap service start/timer/access, controller/presenter peserta, halaman instruction/work, countdown, autosave, finalization, dan feature test yang sudah ada. Tidak ada runtime yang diubah.

| # | Kontrak | Hasil | Bukti/temuan |
|---:|---|---|---|
| 1 | Example selesai sebelum start utama | partial | Halaman instruction selalu menampilkan example sebelum tombol start, tetapi tidak ada state persisten atau konfirmasi bahwa peserta telah menyelesaikan example. |
| 2 | Start utama hanya sekali | pass | Start kedua mengembalikan timestamp lama; tidak menambah waktu. |
| 3 | Memorization 120 detik | pass | Deadline memorization dihitung server dari `memorization_seconds`; katalog ME menetapkan 120. |
| 4 | Materi hilang setelah 120 detik | pass | Tepat pada deadline, fase kanonis menjadi answering dan presenter tidak lagi mengirim materi. |
| 5 | Transisi satu arah | pass | Access decision berasal dari timestamp server dan tidak menyediakan transisi balik. |
| 6 | Answering 240 detik | pass | Deadline answering ditetapkan bersamaan saat first start, 240 detik setelah akhir memorization. |
| 7 | Timer tidak bersamaan | pass | Phase timer memilih satu deadline aktif: memorization lalu answering. |
| 8 | Refresh tidak mengembalikan materi | pass | Refresh meminta ulang state server; answering menghasilkan `memorizationContent=null`. |
| 9 | Back navigation tidak membuka materi | pass | Navigator mengarahkan request ke destination kanonis sesuai waktu server. |
| 10 | Autosave tidak menyimpan materi/key | pass | Autosave hanya menerima jawaban saat phase answering. |
| 11 | Participant payload tidak memuat key | pass | Presenter membentuk payload aman eksplisit; field model sensitif tidak diserialisasi. |
| 12 | Payload answering tidak memuat semua pair | pass | Presenter hanya mengirim questions/options/saved answer; `memorizationContent` bernilai null. |
| 13 | Selesai answering menutup subtes | pass | Finalization mengunci dan memajukan state sesuai kontrak yang ada. |
| 14 | Timeout menghasilkan submission | pass | Expiry frontend meminta state server; finalization timeout menangani deadline yang lewat. |
| 15 | Resume menjaga phase | pass | Phase dihitung ulang dari server timestamps, bukan state lokal browser. |
| 16 | Server sumber kebenaran waktu | pass | Service menerima `CarbonInterface`; countdown frontend diselaraskan dengan `serverTime`. |
| 17 | Tidak ada endpoint peserta yang mengembalikan materi setelah phase selesai | pass | Access kanonis dan presenter mode-gated hanya mengirim materi pada memorization. |

Blocker integrasi yang harus diputuskan pada tahap runtime terpisah:

1. Apakah example perlu gerbang “telah dibaca/selesai” yang eksplisit dan persisten, atau keberadaannya pada instruction sebelum tombol start sudah dianggap cukup.
2. Panel memorization perlu renderer struktur pair responsif agar dapat memenuhi layout maksimal tiga kolom tanpa trik spasi pada string.

## Leakage review

| Risiko | Hasil | Catatan |
|---|---|---|
| Nama field participant membocorkan jawaban | pass | Participant-facing draft hanya memakai cue, associate, prompt, code, text, dan display order aman. |
| Logical ID berpola sama dengan key | pass | Urutan key tidak mengikuti ID atau display order. |
| `source_pair_id` dikirim ke peserta | pass | Hanya ada pada blok internal; presenter runtime tidak mengirimnya. |
| `is_correct` dikirim ke peserta | pass | Tidak ada pada participant-facing block atau presenter option payload. |
| Rationale dikirim ke peserta | pass | Rationale hanya internal. |
| Status filler dikirim ke peserta | pass | Memorization participant block memuat 15 pair tanpa penanda. |
| Materi tetap ada pada DOM setelah transisi | pass | Work mengganti mode dan tidak merender panel memorization pada answering. |
| Materi disimpan sebagai hidden element | pass | Tidak ditemukan hidden copy dalam flow ME yang diaudit. |
| Materi tersimpan di local/session storage | pass | Tidak ditemukan pemakaian storage browser dalam flow ME yang diaudit. |
| Response answering membawa daftar lengkap | pass | `memorizationContent=null` dan tidak ada `pairs` pada payload answering. |
| Browser back memunculkan materi | pass | Server mengembalikan destination kanonis berdasarkan deadline. |
| Page source memuat key | pass | Props peserta dibentuk eksplisit tanpa snapshot key, score, atau correctness. |

JSON draft memang memuat data internal sensitif untuk review/validator dan tidak boleh dijadikan payload peserta atau dibaca runtime.

## Quality control editorial

- Pair review: 15 pass; 0 revise; 0 reject.
- Question review: 12 pass; 0 revise; 0 reject.
- Example review: 1 pass; 0 revise; 0 reject.
- Ambiguity review: seluruh target, opsi, dan mapping deterministik; tidak ada multiple key.
- Language self-review: prompt konsisten, kata umum, ejaan jelas, tidak sensitif.
- Logic self-review: seluruh correct option cocok dengan target pair dan seluruh distraktor berasal dari sisi jawaban yang benar.
- Memory design self-review: 12 tested + 3 filler, enam forward + enam reverse, target tidak berulang, filler tersebar.
- Leakage self-review: participant-facing block lolos larangan key, correctness, rationale, status filler, source pair, dan marker development.

Review manusia untuk language, logic, association, memory-design, dan leakage telah `passed`. Status konten adalah `human_review_passed`, tetapi kedua blocker integrasi runtime tetap terbuka dan harus diselesaikan sebelum aktivasi.

## Batas tahap

- Tidak ada import atau perubahan database.
- Tidak ada dataset produksi `database/data/ist-final/me.json`.
- Tidak ada perubahan runtime, timer, lifecycle, autosave, frontend, final importer, card landing, atau subtes lain.
- Card landing tetap inactive.
- Tidak ada IQ, norma, kategori normatif, approval, freeze, atau commit.
