# Pipeline Dataset Final Tes Kemampuan Kognitif Adaptasi

## Tujuan dan batas produk

Pipeline ini memvalidasi dan mengimpor question bank untuk **Tes Kemampuan Kognitif Adaptasi**: sembilan subtes, 104 scored questions, sembilan example, dan 2.700 detik waktu inti. Hasil produk hanya berupa skor internal nonnormatif.

Pipeline tidak membaca workbook norma, tidak menghitung SW/Gesamt/IQ, tidak membuat kategori psikologis, dan tidak mengaktifkan landing card.

## Pemisahan development dan final

- `database/data/ist-development/` tetap merupakan fixture development dengan version range dan media path tersendiri.
- `database/data/ist-final-staging/` menampung hasil konsolidasi yang telah lulus review manusia tetapi belum approved/frozen. Paket ini hanya dapat memakai validator staging read-only dan tidak dapat diimpor.
- Dataset final menggunakan identifier `tes-kemampuan-kognitif-adaptasi-104`, final version range, validator, guard, installer, dan command terpisah.
- Importer final tidak mengubah, menghapus, atau mengambil kepemilikan natural key milik dataset development.
- Fixture otomatis Tahap 10 dibangkitkan di direktori sementara saat test. Fixture memakai marker `[TEST-FIXTURE]`, selalu inactive, dan ditolak activation gate.

## Struktur paket final

```text
dataset/
├── manifest.json
├── approvals.json
├── checksums.json
├── se.json
├── wa.json
├── an.json
├── ge.json
├── ra.json
├── zr.json
├── fa.json
├── wu.json
├── me.json
└── media/
    ├── metadata.json
    └── ... aset yang dideklarasikan ...
```

Nomor soal bersifat lokal per subtes dan per `kind`: scored dimulai dari 1 sampai jumlah subtes, sedangkan example memakai nomor 1 sendiri. `logical_id` bersifat unik global dengan pola `SE-S-001` atau `SE-E-001`.

## Manifest dan versioning

Manifest mengunci identitas produk, status `frozen`, `active=false`, urutan subtes, count, durasi, daftar file, media, provenance, approval, freeze, dan algoritma checksum.

Versi dipisahkan menjadi:

- `instrument_version`
- `question_bank_version`
- `scoring_rule_version`
- `media_version`
- `report_version`
- numeric `record_version` untuk kolom source version runtime

`norm_version`, bila dicantumkan, wajib `null`. Version development `900000001` dan range development tidak diterima.

Reimport record version yang sama ditolak eksplisit. Session peserta yang sudah ada tetap memakai snapshotnya sendiri.

## Approval dan freeze

`approvals.json` wajib berisi content author, language reviewer, logic reviewer, owner approver, visual reviewer FA/WU, timestamp, keputusan `approved`, approval version, dan notes. Nilai kosong atau placeholder ditolak.

Freeze metadata wajib mencatat `frozen_at`, `frozen_by`, dan `freeze_version`. Urutan waktu wajib:

```text
approval/visual approval <= freeze <= checksum generation
```

## Checksum

Algoritma yang dipakai adalah SHA-256. `checksums.json` mencantumkan, secara canonical dan terurut:

- `manifest.json`
- `approvals.json`
- sembilan file subtes
- `media/metadata.json`
- seluruh aset media

`checksums.json` tidak mencantumkan checksum dirinya sendiri. Import tidak pernah memperbaiki checksum otomatis. File hilang, mismatch, symlink, duplicate path, atau file tambahan menyebabkan penolakan.

## Scoring record

Tipe yang didukung adalah `single_choice`, `single_choice_weighted`, `numeric`, dan `image_choice`.

- Binary/image choice: lima opsi, satu correct, skor 1/0, max item 1.
- GE: lima opsi, tepat satu skor 3 sebagai correct, partial hanya 1–2, skor 0 sebagai wrong, seluruh skor 0–3, max item 3, dan rationale internal.
- Numeric: satu canonical answer, tanpa multi-key, max item 1.
- Example menggunakan tipe subtes, berada di luar scored count, dan tidak masuk runtime snapshot.

Difficulty scored harus tepat 4/5/3 untuk subtes 12 soal dan 3/4/3 untuk subtes 10 soal. Label ini bersifat editorial, bukan ukuran psikometrik empiris.

## Media

Media final dipasang di `ist/final/{question_bank_version}/...`, bukan pada path development. Validator menolak path traversal, absolute path, symlink, mismatch extension/MIME, ukuran atau dimensi invalid, checksum mismatch, alt text pembocor jawaban, dan SVG aktif/berbahaya.

SVG tidak boleh memuat script, event handler, external reference, `foreignObject`, JavaScript URL, entity, atau doctype. Media di-stage terlebih dahulu dan baru dipublish setelah transaksi database berhasil. Kegagalan publish mengompensasi row yang baru dibuat dan membersihkan staging.

## Database guard

Guard dataset nyata hanya menerima `APP_ENV=local` dan connection MySQL. Environment testing/`tes_iq_testing` terbatas pada fixture otomatis yang ditandai. Configured dan active database harus sama serta berada dalam allowlist eksplisit `IST_FINAL_DATABASE_ALLOWLIST`. Database utama memerlukan production gate terpisah yang default-nya nonaktif.

Operasi tulis juga memerlukan `--allow-database` yang tepat sama dengan configured dan active database serta `--confirm-write`. Nama environment, connection, host, configured database, active database, instrument, version, fingerprint, approval/freeze, dan mode ditampilkan sebelum import.

## Dry-run dan import

Validasi paket staging yang belum approved/frozen:

```bash
php artisan ist:import-final-dataset database/data/ist-final-staging \
  --dry-run \
  --staging
```

Mode `--staging` tidak menerima `--allow-database`, tidak membuka koneksi database, dan tidak menulis database atau media. Validator final ketat tetap menolak paket staging; perubahan status ke approved/frozen harus dilakukan dalam tahap terpisah beserta checksum baru.

Command:

```bash
php artisan ist:import-final-dataset /path/to/dataset --dry-run
```

Tanpa `--confirm-write`, command selalu menjadi dry-run. Dry-run melakukan guard read-only dan seluruh validasi file tanpa menulis database atau media. Memberikan `--allow-database` saja tidak mengizinkan write.

Operasi tulis terkontrol:

```bash
php artisan ist:import-final-dataset /path/to/dataset \
  --allow-database=database_allowlisted \
  --confirm-write \
  --question-bank-version=question_bank_version
```

Tidak ada flag untuk melewati approval, checksum, atau production guard. Import berhasil tetap membuat seluruh question dan option dalam keadaan inactive.

## Activation gate

Import bukan aktivasi. Aktivasi di masa mendatang wajib merupakan tindakan eksplisit terpisah dan memerlukan:

- import sukses;
- frozen dataset;
- approval lengkap;
- checksum cocok;
- golden tests lulus;
- UAT sign-off;
- explicit activation approval.

Test fixture tidak pernah dapat diaktifkan. Tahap 10 tidak mempunyai mutation aktivasi dan tidak mengubah landing card atau route peserta.

## Keamanan answer key

Answer key dan GE weights tersimpan server-side pada source/scoring data dan row option. Presenter peserta tetap membentuk payload eksplisit yang hanya berisi prompt, opsi aman, media aman, saved answer peserta, dan metadata runtime. Approval, provenance, score, `is_correct`, dan answer-key snapshot tidak dikirim ke peserta atau dicetak penuh ke log/command output.

Provenance lengkap tetap berada dalam frozen dataset yang diverifikasi checksum. Schema runtime saat ini menyimpan numeric source version; activation/release harus mempertahankan frozen dataset sebagai artefak audit.
