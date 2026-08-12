# Stage 18 — DISC Regression Checklist

Suite clone-only:

~~~text
tests/Feature/Disc/DiscCloneRegressionTest.php
~~~

Guard suite:

- APP_ENV harus local;
- connection harus MySQL;
- configured dan active database harus tes_iq_migration_test;
- database aktif tidak boleh tes_iq;
- seluruh mutation berada dalam outer transaction dan di-rollback;
- tidak memakai RefreshDatabase, DatabaseMigrations, truncate, wipe,
  fresh, reset, atau global seeding.

## Automated checks

- [ ] Landing dan seluruh route DISC tersedia.
- [ ] Active question DISC tetap 24.
- [ ] Test DISC baru dapat dibuat.
- [ ] Submit 23 jawaban ditolak.
- [ ] Submit tepat 24 jawaban berhasil.
- [ ] Jawaban hanya tersimpan pada test baru.
- [ ] Most, least, change, dan graph deterministik.
- [ ] Profile dan disc_type terhitung.
- [ ] Result tersedia.
- [ ] Delete user hanya men-null-kan disc_tests.user_id.
- [ ] Dry-run cleanup IST tidak mengubah DISC.
- [ ] Tidak ada foreign key IST menuju DISC.
- [ ] Outer transaction kembali rollback.

## External invariants

Bandingkan count, deterministic SHA-256, dan schema dump sebelum migration,
sesudah migration, sesudah seed, sesudah import, sesudah UAT, dan sesudah
rollback.

Baseline immutable:

~~~text
disc_questions=24
disc_tests=48
disc_answers=528
disc_results=0
disc_graph_conversions=49
disc_profiles=16
disc_statement_interpretations=96
~~~

Setiap mismatch adalah hard NO-GO.

