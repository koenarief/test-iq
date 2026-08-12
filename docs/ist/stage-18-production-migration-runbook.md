# Stage 18 — Production Migration Runbook

Runbook masa depan ini belum pernah dijalankan pada tes_iq.

## Hard stop

Berhenti bila target database tidak tepat, backup tidak dapat dipulihkan,
checksum DISC berubah, dataset belum benar-benar approved/frozen, primary
gate belum aktif, landing berubah aktif, ada sesi IST aktif, atau konten
instruction/ME tidak lengkap.

Jangan pernah menjalankan migrate:fresh, migrate:refresh, migrate:reset,
db:wipe, DatabaseSeeder, atau seeder DISC.

## Urutan

1. Freeze perubahan aplikasi dan database.
2. Guard environment, connection, configured database, dan active database.
3. Buat full/schema/DISC/users dump dan SHA-256.
4. Restore ke clone baru dan cocokkan DISC.
5. Jalankan tujuh migration IST dalam satu batch khusus.
6. Jalankan hanya IstSubtestSeeder.
7. Import artifact final inactive dengan allowlist dan confirm-write.
8. Cocokkan counts/checksum DISC.
9. Jalankan test DISC dan IST.
10. UAT melalui protected clone preview.
11. Nonaktifkan preview.
12. Uji rollback batch khusus pada clone.
13. Cocokkan kembali DISC/users/session/cache.
14. Primary execution membutuhkan approval manusia baru.

## Clone import

~~~bash
APP_ENV=local \
DB_DATABASE=tes_iq_migration_test \
IST_FINAL_DATABASE_ALLOWLIST=tes_iq_migration_test \
IST_FINAL_MEDIA_DISK=local \
php artisan ist:import-final-dataset \
  /tmp/ist-final-rehearsal-stage18-v2 \
  --allow-database=tes_iq_migration_test \
  --question-bank-version=2026.08.06-stage17 \
  --confirm-write
~~~

Media clone memakai disk local, bukan public runtime utama.

## Protected preview

~~~bash
APP_ENV=local \
DB_DATABASE=tes_iq_migration_test \
IST_REHEARSAL_PREVIEW_ENABLED=true \
IST_REHEARSAL_PREVIEW_DATABASE_ALLOWLIST=tes_iq_migration_test \
IST_REHEARSAL_PREVIEW_RECORD_VERSION=100000017 \
php artisan ist:rehearsal-preview 100000017 \
  --allow-database=tes_iq_migration_test \
  --enable \
  --confirm
~~~

Preview membutuhkan user terautentikasi, exact clone, dan exact version.
Landing umum tetap inactive. Setelah UAT gunakan command yang sama dengan
disable menggantikan enable.

## Rollback clone

Gunakan hanya nomor batch IST yang dicatat dan tujuh path migration
eksplisit. Jangan rollback seluruh aplikasi. Setelah rollback:

- tujuh tabel IST harus hilang;
- seluruh tabel DISC tetap ada;
- DISC schema/count/checksum sama dengan baseline;
- users/session/cache sama;
- media rehearsal version dihapus hanya setelah exact path diverifikasi.

## Primary execution

Primary membutuhkan artifact production, backup baru, maintenance window,
allowlist tepat, confirm-write, dan IST_FINAL_PRIMARY_WRITE_ENABLED=true.
Rehearsal metadata tidak boleh digunakan. Aktivasi landing adalah keputusan
rilis terpisah.
