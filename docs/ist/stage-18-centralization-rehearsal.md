# Stage 18 — Centralization Rehearsal

Status: in progress. Dokumen ini tidak memberikan izin menulis ke
database utama.

## Safety boundary

- Primary: tes_iq — read-only selama Tahap 18.
- Clone: tes_iq_migration_test.
- Automated test: tes_iq_testing.
- Landing IST tetap inactive.
- IQ dan norma tidak dibuat.
- Dataset staging asli tidak diubah.

## Git baseline

~~~text
1d73d2a Add reviewed ME assessment content
e8deece Mark FA and WU content as human reviewed
261fe4e Add draft FA and WU visual assessment content
~~~

Working tree awal berisi pekerjaan Tahap 17 yang belum di-commit.

## Backup

Lokasi di luar Git:

~~~text
/tmp/ist-stage18-backups/20260806_114745
~~~

| Artifact | Bytes | SHA-256 |
|---|---:|---|
| tes_iq-full.sql | 193199 | a4970a59d26963e4c831d26c6f8f3542b1d44d8aa33cfb73fb27449c90b9939c |
| tes_iq-schema.sql | 15396 | 9bd27c3c933f3ac381ca656420f6ec474f50ed543846ef158aa05f7c673ce39c |
| tes_iq-disc.sql | 210748 | 9475a3ac67a5251c5c6e85b2236316e8c1111466cae9c78a3378a43845952060 |
| tes_iq-users.sql | 2607 | b067b3c23825d49bc6575a06c12d30dfdc2bf336b23f67712889d0b6b2b69b5b |

Seluruh file non-empty. Full dump dipulihkan hanya ke clone.

## Clone baseline

Restore ke tes_iq_migration_test berhasil. Sebelum migration IST:

| Table | Rows |
|---|---:|
| users | 1 |
| sessions | 2 |
| cache | 2 |
| migrations | 15 |
| disc_questions | 24 |
| disc_tests | 48 |
| disc_answers | 528 |
| disc_results | 0 |
| disc_graph_conversions | 49 |
| disc_profiles | 16 |
| disc_statement_interpretations | 96 |

Tidak ada tabel IST pada baseline clone. Deterministic DISC checksums clone
cocok byte-for-byte dengan primary:

| Table | SHA-256 |
|---|---|
| disc_answers | 859c8ce2ad4f94b3d70e490b4264b0b2a8fdb80ecadd285ce4146a24ba7dfb31 |
| disc_graph_conversions | 31bc4e803f6b7e450d722b847f8d45a204d0f46da1fcf77fa19bb3b6a9a5790a |
| disc_profiles | 9e8bf682dd3073f097613b0fdb90410680b77f229dcd63a7b012009d53f31487 |
| disc_questions | 8fc70240a5d38013e1f2316653c41e8efeb4065f8c3c92dc884dfc0333a842fb |
| disc_results | 79463f38b09439dd8c28e28be19a5bfc39bcd265ecefc6c36047848674d3bfb0 |
| disc_statement_interpretations | fb9ac25a4984800d50c25ff55aadab24c4c0ecbd75767397aded7d5271733866 |
| disc_tests | fa3d0b62772b5ca208c9ef1bc2a382e9de09a101767d6ebc8602a0836ce7eff9 |

## Code hardening

- Final import tetap validate-only tanpa flag confirm-write.
- Write membutuhkan allow-database yang sama persis.
- Configured dan active database harus sama dan allowlisted.
- tes_iq_testing hanya menerima automated test fixture yang ditandai.
- Primary write memerlukan production gate terpisah yang default-nya off.
- Output command memuat environment, connection, host, database, version,
  fingerprint, approval/freeze, dan mode.
- Importer hanya mengubah field master IST milik pipeline.
- Nilai master lama dipulihkan jika publish media gagal.
- ME wajib memiliki memorization; subtest lain wajib null.

## Rehearsal artifact

Lokasi sementara:

~~~text
/tmp/ist-final-rehearsal-stage18-v2
~~~

Artifact hanya untuk clone, active=false, dan fingerprint substantif tetap:

~~~text
d231ac58274c0f2095751b57ca50b986c74b8a18c0e0fd5b46601b332d31f5e0
~~~

Strict validator: 104 scored, 9 examples, 435 options, 144 media.

## Verifikasi non-database

- Pure IST unit: 120 passed, 314 assertions.
- Stage 15 validator: passed.
- Stage 16 validator: passed.
- Strict rehearsal validator: passed.
- JSON: 13 valid.
- SVG: 144 valid.
- Frontend production build: passed.
- git diff --check: passed.

## Status

| Gate | Status |
|---|---|
| primary_database_audit | passed |
| backup_verified | passed |
| clone_restore | passed |
| clone_migration | pending |
| disc_regression | pending |
| clone_import | pending |
| clone_uat | pending |
| clone_rollback | pending |
| production_migration | not_started |
| tes_iq_modified | false |

Pending tidak boleh diubah menjadi passed sebelum eksekusi clone dan
verifikasi checksum selesai.
