# Checklist Review Tahap 16 — ME

> **INTERNAL REVIEW ONLY.** Checklist ini tidak mengubah status menjadi approved/frozen dan tidak boleh dibagikan sebagai payload peserta.

## Status checkpoint

- `automated_language_review`: passed
- `automated_logic_review`: passed
- `automated_association_review`: passed
- `automated_memory_design_review`: passed
- `automated_leakage_review`: passed
- `human_language_review`: passed
- `human_logic_review`: passed
- `human_association_review`: passed
- `human_memory_design_review`: passed
- `human_leakage_review`: passed
- `overall_status`: human_review_passed
- `review_status`: human_review_passed
- `active`: false
- `approved`: false
- `frozen`: false
- `imported`: false

## Kontrak otomatis

- [x] Tepat 1 example dan 12 scored questions.
- [x] Tepat 15 main pairs: 12 tested dan 3 filler.
- [x] Seluruh 15 cue unik, 15 associate unik, dan seluruh 30 kata unik lintas sisi.
- [x] Display order pair tepat 1–15 dan scored tepat 1–12.
- [x] Setiap tested pair menjadi target tepat satu soal.
- [x] Filler `me-pair-004`, `me-pair-009`, dan `me-pair-013` tidak menjadi target.
- [x] Example memakai lima pair terpisah dan tidak memakai kata materi utama.
- [x] Setiap scored question mempunyai lima opsi A–E dan tepat satu correct.
- [x] Correct bernilai 1; wrong bernilai 0; tidak ada partial score.
- [x] Answer type seluruh scored record adalah `single_choice`.
- [x] Seluruh opsi scored berasal dari main pair pada sisi jawaban yang benar.
- [x] Distribusi arah 6 cue→associate dan 6 associate→cue.
- [x] Difficulty 4 easy, 5 medium, 3 hard.
- [x] Direction per difficulty: easy 2 forward/2 reverse; medium 3 forward/2 reverse; hard 1 forward/2 reverse.
- [x] Urutan key `C, A, D, B, E, C, D, A, C, E, B, D`.
- [x] Distribusi key A=2, B=2, C=3, D=3, E=2.
- [x] Frekuensi maksimum satu kata sebagai distractor adalah 2.
- [x] Memorization 120 detik, answering 240 detik, total 360 detik, single start.
- [x] Seluruh record `content_origin=original_internal`, `source_reference=null`, `copyright_status=internally_authored`, `normative_compatibility=none`, `review_status=human_review_passed`, `active=false`.
- [x] Participant-facing block tidak memuat key, `is_correct`, score, rationale, source pair, target pair, status tested/filler, atau marker development.
- [x] Tidak ada dataset produksi ME.

## Review manusia atas 15 pasangan

Untuk setiap pair, periksa kata mudah dipahami, asosiasi tidak dapat ditebak secara umum, tidak sinonim/antonim, tidak terkait fungsi/kategori langsung, tidak berima kuat, tidak berpola awalan, tidak menonjol, tidak sensitif, unik, dan layak untuk peserta umum.

| Pair ID | Pasangan | Automated | Human status | Catatan reviewer |
|---|---|---|---|---|
| `me-pair-001` | jendela — irama | pass | passed | |
| `me-pair-002` | sawah — kancing | pass | passed | |
| `me-pair-003` | bantal — lorong | pass | passed | |
| `me-pair-004` | ember — cerita | pass | passed | |
| `me-pair-005` | sepatu — kalender | pass | passed | |
| `me-pair-006` | sungai — pensil | pass | passed | |
| `me-pair-007` | cermin — ladang | pass | passed | |
| `me-pair-008` | tangga — rempah | pass | passed | |
| `me-pair-009` | piring — hutan | pass | passed | |
| `me-pair-010` | payung — kerikil | pass | passed | |
| `me-pair-011` | kertas — balkon | pass | passed | |
| `me-pair-012` | lemari — ombak | pass | passed | |
| `me-pair-013` | gelas — taman | pass | passed | |
| `me-pair-014` | kursi — petir | pass | passed | |
| `me-pair-015` | kapal — pita | pass | passed | |

### Konfirmasi pair reviewer

- [x] Natural association seluruh pair dapat diterima (`none`/`weak`).
- [x] Semantic relation seluruh pair dapat diterima (`none`/`weak`).
- [x] Tidak ada kesamaan fonologis yang menjadi petunjuk.
- [x] Tidak ada pasangan yang menjadi memorability outlier.
- [x] Ketiga filler setara kualitasnya dan tidak mudah dikenali.
- [x] Urutan 15 pair tidak menghasilkan pola alfabet atau mnemonic.

## Review manusia atas example

- [x] Lima pasangan example cukup arbitrer dan mudah dibaca.
- [x] Example terpisah sepenuhnya dari materi utama.
- [x] Prompt hanya mempunyai satu jawaban benar.
- [x] Penjelasan peserta mudah dipahami.
- [x] Dijelaskan bahwa materi akan ditutup dan tidak dapat dibuka kembali.
- [x] Dijelaskan bahwa example tidak masuk skor dan timer utama.
- [x] Seluruh lima opsi example berasal dari sisi associate materi contoh.

## Review manusia atas 12 soal

Untuk setiap soal, periksa prompt, target, key, sisi opsi, duplicate, semantic clue, panjang kata, posisi key, difficulty, direction, target tunggal, filler leakage, rationale, dan orisinalitas.

| Question | Direction | Difficulty | Key | Automated | Human status | Catatan reviewer |
|---|---|---|---|---|---|---|
| `me-001` | cue→associate | easy | C | pass | passed | |
| `me-002` | cue→associate | easy | A | pass | passed | |
| `me-003` | cue→associate | medium | D | pass | passed | |
| `me-004` | associate→cue | easy | B | pass | passed | |
| `me-005` | cue→associate | medium | E | pass | passed | |
| `me-006` | associate→cue | easy | C | pass | passed | |
| `me-007` | cue→associate | medium | D | pass | passed | |
| `me-008` | associate→cue | medium | A | pass | passed | |
| `me-009` | cue→associate | hard | C | pass | passed | |
| `me-010` | associate→cue | medium | E | pass | passed | |
| `me-011` | associate→cue | hard | B | pass | passed | |
| `me-012` | associate→cue | hard | D | pass | passed | |

### Konfirmasi question reviewer

- [x] Seluruh prompt jelas dan konsisten.
- [x] Seluruh correct option sesuai pair target.
- [x] Seluruh distractor berasal dari materi pada sisi yang sama.
- [x] Tidak ada opsi ganda atau key ganda.
- [x] Tidak ada soal yang dapat dijawab melalui hubungan semantik umum.
- [x] Panjang/familiaritas opsi tidak membocorkan jawaban.
- [x] Urutan key tidak mudah diprediksi.
- [x] Difficulty editorial layak.
- [x] Difficulty didasarkan pada kedekatan pair, kompetisi distractor, kesamaan kategori/familiaritas, dan posisi materi; bukan anggapan bahwa reverse recall otomatis lebih sulit.
- [x] Tidak ada petunjuk tested/filler.

## Review distribusi distractor

- Frekuensi 2: balkon, bantal, cerita, cermin, ember, gelas, hutan, jendela, kancing, payung, pensil, petir, piring, pita, rempah, sawah, sepatu, taman.
- Frekuensi 1: irama, kalender, kapal, kerikil, kertas, kursi, ladang, lemari, lorong, ombak, sungai, tangga.
- [x] Distribusi tidak menonjolkan pasangan target atau filler.
- [x] Tidak ada kata yang terlalu sering dan semua opsi tetap adil.

## Audit lifecycle read-only

- [x] Start kedua idempoten dan tidak menambah waktu.
- [x] Deadline memorization dan answering ditetapkan server pada first start.
- [x] Tepat pada akhir memorization, fase kanonis menjadi answering.
- [x] Timer fase tidak berjalan bersamaan.
- [x] Refresh/resume mengikuti timestamp server.
- [x] Back navigation diarahkan ke fase kanonis.
- [x] Payload answering tidak memuat materi hafalan.
- [x] Payload peserta tidak memuat key/correctness/rationale/source pair.
- [x] Autosave hanya menangani jawaban pada answering.
- [x] Finalization dan timeout menutup subtes sesuai kontrak yang ada.
- [ ] **Blocker integrasi:** belum ada state persisten “example selesai”.
- [ ] **Blocker integrasi:** panel memorization belum berupa grid pair responsif maksimal tiga kolom.

## Leakage review manusia

- [x] Nama field dan susunan tampilan tidak membocorkan key.
- [x] Logical ID tidak terlihat peserta.
- [x] `source_pair_id`, `target_pair_id`, `is_correct`, score, dan rationale tidak masuk payload.
- [x] Status tested/filler tidak terlihat pada material.
- [x] DOM answering tidak menyimpan material tersembunyi.
- [x] localStorage/sessionStorage tidak menyimpan material.
- [x] Network response answering tidak membawa daftar 15 pair.
- [x] Browser back tidak membuka materi setelah deadline.
- [x] Page source tidak memuat key.

## Keputusan human review

- [x] `human_language_review`: passed
- [x] `human_logic_review`: passed
- [x] `human_association_review`: passed
- [x] `human_memory_design_review`: passed
- [x] `human_leakage_review`: passed
- [x] Kedua gap runtime tetap dicatat sebagai blocker integrasi terbuka.
- [x] `overall_status` diubah menjadi `human_review_passed` pada checkpoint ini.

Human review Tahap 16 telah ditutup dengan status `human_review_passed`. Konten tetap `active=false`, belum approved, belum frozen, belum diimpor, card landing tetap inactive, dan kedua blocker runtime tetap terbuka.
