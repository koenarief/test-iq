# Tahap 17 — Konsolidasi Dataset Final Staging

## Status checkpoint

- Dataset consolidated: `true`
- Dataset validation: `passed`
- Runtime integration ME: `passed` pada automated test
- UAT checklist: `prepared`, belum sign-off
- Review status: `human_review_passed`
- Approved/frozen/active/imported: `false/false/false/false`
- Landing card: tetap inactive

Checkpoint ini bukan approval, freeze, import, aktivasi, atau pernyataan siap produksi.

## Baseline dan sumber

Baseline bersih pada commit `1d73d2a Add reviewed ME assessment content`, didahului `e8deece` dan `261fe4e`. Konsolidasi hanya membaca artefak internal reviewed berikut:

- `stage-12-se-wa-an.md`
- `stage-13-ge-weighted.md`
- `stage-14-ra-zr-numeric.md`
- `stage-15-assets/geometry-spec-draft.json`
- `stage-15-assets/media-manifest-draft.json`
- `stage-16-me-data-draft.json`

PDF IST, workbook norma, kunci sumber, database aktif, dan dataset dummy development tidak dipakai sebagai sumber konten.

## Keputusan pipeline

Pipeline Tahap 10 memerlukan paket final berstatus frozen dengan approval lengkap. Konten Tahap 17 wajib tetap belum approved dan belum frozen, sehingga hasil konsolidasi ditempatkan di `database/data/ist-final-staging/`, bukan dipaksa ke kontrak final produksi.

Validator final ketat tetap berlaku. Jalur `validateStaging()` ditambahkan untuk status `human_review_passed`, inactive, unapproved, unfrozen, dan unimported. Command `--staging` bersifat validate-only, menolak `--allow-database`, serta tidak membuka koneksi atau melakukan mutation database/media.

## Struktur dan mapping

| Subtes | File | Scored | Example | Tipe | Maksimum |
|---|---|---:|---:|---|---:|
| SE | `se.json` | 12 | 1 | single_choice | 12 |
| WA | `wa.json` | 12 | 1 | single_choice | 12 |
| AN | `an.json` | 12 | 1 | single_choice | 12 |
| GE | `ge.json` | 10 | 1 | single_choice_weighted | 30 |
| RA | `ra.json` | 12 | 1 | numeric | 12 |
| ZR | `zr.json` | 12 | 1 | numeric | 12 |
| FA | `fa.json` | 10 | 1 | image_choice | 10 |
| WU | `wu.json` | 12 | 1 | image_choice | 12 |
| ME | `me.json` | 12 | 1 | single_choice | 12 |

Total paket: 9 subtes, 9 example, 104 scored, 113 record pertanyaan, dan 435 opsi. Count tipe untuk scored record adalah single choice 48, weighted 10, numeric 24, dan image choice 22.

File kontrol terdiri atas `manifest.json`, `approvals.json`, `checksums.json`, dan `media/metadata.json`. Dataset version adalah `2026.08.06-stage17`.

## Media

Sebanyak 144 SVG FA/WU disalin byte-identik ke staging. Metadata mencakup logical ID, path relatif netral, role, subtes, relasi pertanyaan/opsi, MIME, byte size, width, height, viewBox, SHA-256, alt text, review status, dan inactive state. Nama file tidak mengandung key jawaban.

- Content fingerprint: `d231ac58274c0f2095751b57ca50b986c74b8a18c0e0fd5b46601b332d31f5e0`
- Media checksum aggregate: `ee0a8d82e49016422108b47869dfb0461990896df4d76a16a9c0e2d848275296`
- Disclaimer fingerprint: `dc0f64497e259fa67ea486112c1a7065e48853b848557b841f740c15cd9e9e87`

## Scoring dan batas produk

Kontrak scoring tidak berubah. Binary/numeric memberi 1 atau 0; GE memakai 0–3 dengan skor 3 sebagai correct dan 1–2 sebagai partial. Persentase subtes tetap `awarded_score / max_subtest_score * 100`; total internal adalah rata-rata aritmetika sembilan persentase, bukan jumlah raw score.

Nama, subtitle, dan disclaimer produk divalidasi persis. Dataset menyatakan `normative=false` dan `iq_output=false`. Tidak ada SW, Gesamt, IQ, norma, kategori normatif, atau interpretasi psikologis yang ditambahkan.

## Integrasi ME

State example selesai menggunakan `ist_test_subtests.instruction_viewed_at`, field existing yang sebelumnya belum digunakan. Endpoint ME menerima satu pilihan example aktif, lalu menyimpan timestamp konfirmasi secara idempoten. State bertahan setelah refresh, tidak membuat scored answer, tidak mengubah skor, dan tidak memulai atau menambah timer. Start ME pertama ditolak sebelum completion; start sesudah completion tetap single-start dan idempoten.

Feedback example ME tidak berada di payload sebelum completion. Setelah completion, instruction menampilkan feedback dan mengaktifkan tombol start bila snapshot lengkap.

Materi hafalan dikirim sebagai array aman berisi hanya `cue`, `associate`, dan `displayOrder`. Renderer memakai satu kolom di mobile, dua di tablet, dan maksimal tiga di desktop, tanpa nomor, ikon, warna pengelompokan, atau indikator tested/filler. Saat batas memorization tercapai, payload answering membawa array pair kosong dan komponen hafalan tidak dirender.

## Leakage audit

Automated test membuktikan payload hafalan tidak membawa tested/filler, logical mapping, key, rationale, source reference, `is_correct`, atau `score_value`. Payload answering dan refresh answering tidak membawa pair; request instruction lama diarahkan ke state work kanonis. Autosave saat memorization ditolak. Example completion tidak membuat jawaban dan tidak mengurangi waktu.

## Validasi dan test

- Validator Tahap 15: passed; 24 record dan 144 SVG.
- Validator Tahap 16: passed; 1/12 example/scored, pair 15/12/3, arah 6/6.
- Validator final staging dan command validate-only: passed; 104/9/435/144.
- JSON parse: seluruh 13 file JSON passed.
- SVG structure/security/checksum melalui validator: seluruh 144 passed.
- Pure unit IST: 107 test, 301 assertion, 0 failure/error/warning.
- Feature lifecycle/HTTP/ME terpilih: 70 test, 502 assertion, 0 failure/error/warning.
- Suite feature IST penuh final: 113 test, 682 assertion, 0 failure/error/warning.
- Pengujian ulang perubahan leakage ME: 14 test, 140 assertion, 0 failure/error/warning.
- PHP lint: passed untuk seluruh file PHP terkait.
- Frontend build: passed, Vite 8.2.0, 3.756 module transformed.
- Frontend contract test: termasuk dalam pure unit IST; project tidak menyediakan JS test/lint runner terpisah.
- Fixture audit: tabel `ist_tests`, `ist_test_subtests`, `ist_test_questions`, dan `ist_answers` kembali 0 pada `tes_iq_testing`.
- Full application suite tidak dijalankan karena test auth/profile memakai `RefreshDatabase`; Tahap 17 tidak memberi izin refresh database. Seluruh suite IST yang memakai transaksi aman telah dijalankan.

## Risiko dan langkah menuju approval

Risiko yang masih perlu UAT/security review:

- `instruction_viewed_at` kini mempunyai semantik khusus ME dan harus didokumentasikan bila kelak dipakai secara umum.
- Material ME tetap sensitif selama jendela memorization; cache/proxy produksi harus menghormati kebijakan session dan no-store aplikasi.
- Layout visual perlu diperiksa manual pada device nyata, terutama viewport sempit dan perpindahan tepat pada deadline.
- Paket belum approved/frozen, sehingga checksum harus dibangkitkan ulang oleh tahap approval/freeze bila metadata berubah.

Langkah berikutnya adalah menjalankan UAT manual, security review, sign-off approval eksplisit, freeze terpisah, validasi checksum ulang, lalu import inactive pada database yang diizinkan. Aktivasi card dan dataset tetap merupakan keputusan terpisah.
