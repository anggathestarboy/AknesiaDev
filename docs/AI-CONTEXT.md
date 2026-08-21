# AI Context — DraftCMS

Ringkasan kondisi kode saat ini untuk AI agent, supaya tidak perlu
menebak-nebak arsitektur. Baca ini + `docs/PRD.md` sebelum mengerjakan task
apa pun di luar bugfix kecil.

## Struktur direktori nyata (bukan asumsi Statamic)

```
app/
  Actions/Fortify/     — action registrasi/update profil (CreateNewUser, dll)
  Concerns/
  Console/Commands/
  Http/Controllers/
  Livewire/            — komponen Livewire (baru untuk Settings/Auth)
  Livewire/Settings/
  Models/              — HANYA ADA User.php saat ini
  Providers/
database/migrations/    — skema penuh CMS sudah ada, lihat docs/PRD.md §3
routes/
  web.php               — cuma route `/` dan `/dashboard` (auth+verified)
  settings.php
resources/views/        — Blade + Livewire views (Flux UI)
```

Tidak ada folder `app/Models/Cms/`, `app/Statamic/`, `app/Cp/` atau
sejenisnya. Kalau butuh model CMS baru, ikuti struktur `app/Models/` yang
sudah ada (flat, bukan sub-namespace) kecuali user minta lain.

## Model & auth yang sudah ada

- `App\Models\User` — primary key **UUID** via trait `HasUuids`
  (`newUniqueId()` → `Str::uuid7()`). `$incrementing`/`$keyType` diatur
  otomatis oleh trait, jangan override manual.
- Auth stack: Laravel Fortify mengatur route & controller
  (register/login/2FA/passkey/password-confirm). Custom action ada di
  `app/Actions/Fortify/`. UI login/register pakai Livewire component,
  bukan Blade form biasa — cek `app/Livewire/` sebelum bikin form auth baru.
- `sessions.user_id` sudah bertipe `char(36)` (UUID), sudah disamakan dengan
  `users.id` — **jangan** balikin ke `bigint`/`foreignId`.

## Pola skema CMS yang harus diikuti kalau menambah tabel/model baru

1. Tabel referensi (`collections`, `taxonomies`, `blueprints`, `forms`,
   `sites`, dst.) pakai `id()` auto-increment biasa + `handle` (string,
   unique) sebagai kunci yang dipakai untuk foreign key lintas tabel — bukan
   `id` numerik. Contoh: `entries.collection` adalah string yang mereferensi
   `collections.handle`, bukan `collections.id`.
2. Tabel "isi konten" (`entries`) pakai **UUID** sebagai primary key.
3. Data fleksibel/schema-less (isi field blueprint, settings) disimpan di
   kolom `json` bernama `data` atau `settings` — jangan bikin kolom terpisah
   per field custom.
4. `created_at`/`updated_at` di migration CMS ini didefinisikan manual
   sebagai `timestamp(...)->nullable()`, **bukan** `$table->timestamps()`
   bawaan Laravel. Ikuti pola yang sama di migration baru pada domain yang
   sama supaya konsisten (kecuali user minta ubah).
5. Default site handle yang dipakai di skema adalah `"indonesia"` (lihat
   default `sitemaps.site`). Jangan hardcode locale/site lain sebagai
   default tanpa konfirmasi.

## Yang jangan diasumsikan tersedia

Lihat `docs/PRD.md` §5. Yang paling sering bikin AI ngarang: jangan
berasumsi ada admin panel, template engine, API layer, role/permission
system, atau search index — semua itu **belum dibangun**.

## Sumber kebenaran kalau ragu

1. `database/migrations/` — schema aktual (paling akurat, cek sebelum
   membuat model/migration baru).
2. `docs/PRD.md` — scope & niat produk.
3. `docs/AI-CONSTRAINTS.md` — batasan perilaku AI.
4. `CLAUDE.md` (root) — konvensi coding Laravel/Boost project ini.
5. Kalau masih ragu soal keputusan produk (nama fitur, prioritas, desain
   UI) → **tanya user**, jangan menebak.
