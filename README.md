<img src="/favicon.png">

# Draft CMS

Custom CMS Sandbox for Private Project

## Requirements

- PHP 8.4
- Composer
- Node.js + npm
- MySQL (or SQLite, used automatically by the test suite)

## Installation

```bash
git clone <repo-url>
cd draftcms

composer install
cp .env.example .env
php artisan key:generate

# configure DB_* in .env, then:
npm install
npm run build   # or `npm run dev` while working on frontend assets

php artisan draft:setup
```

### `draft:setup`

Bootstraps a brand-new install end to end — this is the **one command** a fresh clone needs, after `composer install`/`npm install` and a configured `.env`. It runs, in order:

1. `migrate --force`
2. `storage:link`
3. `make:media` — creates the public `assets` media container
4. `users:create-super-admin` — your first login (prompts interactively unless options are passed)
5. `sites:create-default` — the default `Site`
6. `collections:create-pages` — the default `pages` collection
7. `blueprints:create-homepage` — a `Homepage` blueprint
8. `collections:create-homepage-entry` — a dummy homepage entry using that blueprint
9. `admin:sidebar` — seeds the default admin sidebar menu

Non-interactively:

```bash
php artisan draft:setup \
  --name="Admin" --email=admin@example.com --password=secret1234 \
  --site-name="My Site" --site-handle=default
```

Pass `--force` to create another Super Admin even if users already exist. Every step is also its own standalone Artisan command (`php artisan list` to see them) if you only need to re-run one.

## Admin panel

Lives under the `CMS_ROUTE` prefix from `config/cms.php` (`admin-panel` by default, override via `CMS_ROUTE` in `.env`): `http://localhost:8000/admin-panel`. Log in with the Super Admin account created by `draft:setup`.

From there you manage:

- **Collections & Entries** — content types and their content (`/admin-panel/collections`)
- **Taxonomies & Terms** — categorization (`/admin-panel/taxonomies`)
- **Blueprints & Fieldsets** — the field schema entries/terms/globals are built from (`/admin-panel/blueprints`)
- **Globals** — sitewide key/value data not tied to any one entry (`/admin-panel/globals`)
- **Navigation, Sites, Media, Forms, Redirects, Users/Roles** — the rest of the admin sidebar

### Scheduled entries (requires a server cron)

Picking **Published** on an entry whose **Date** is in the future saves it as **Scheduled** instead (mirroring [Statamic's scheduling](https://statamic.dev/advanced-topics/scheduling)) — it stays off the frontend and out of `@collection()` until that date arrives. The same rule applies to the bulk "Set status…" action in the entry list.

#### How it goes live

Nothing flips a Scheduled entry back to Published on its own — a scheduled command does that:

- **Command**: `php artisan entries:publish-scheduled` (`App\Modules\Collections\Console\Commands\PublishScheduledEntriesCommand`) — finds every entry with `status = scheduled` whose `date` has passed and flips it to `published`.
- **Registered on Laravel's scheduler**: `routes/console.php` runs it `->everyMinute()`.
- **The scheduler itself is inert without a cron entry** — Laravel's scheduler only checks "is anything due?" when something actually calls `php artisan schedule:run`. Registering a command in `routes/console.php` is not enough by itself.

#### Production / staging setup

Add **one** real crontab entry per server (this is the only entry needed — every scheduled task in the app, present or future, runs through it):

```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

```bash
crontab -e   # paste the line above, adjusting the path, then save
```

Without this cron entry, scheduled entries will simply never go live — nothing errors, they just sit as "Scheduled" forever. After adding it:

- `php artisan schedule:list` — confirms `entries:publish-scheduled` is registered and shows when it's next due.
- Wait a minute, then check `storage/logs/laravel.log` and the entry's status if something looks stuck.

#### Local development

Cron isn't running on a dev machine, so use one of:

```bash
php artisan schedule:work      # keeps running in a terminal, ticks every minute like real cron would
php artisan entries:publish-scheduled   # or just run the command directly, once, whenever you want to test it
```

#### Notes

- **Timezone**: "is the date in the future?" is evaluated with `Carbon::parse($entry->date)->isFuture()`, which compares against the app's configured timezone (`config('app.timezone')` / `APP_TIMEZONE` in `.env`) — make sure that's set correctly for your audience, since an entry dated for "9am" means 9am in that timezone, not UTC or the server's local time.
- **Frequency**: `everyMinute()` means an entry can go live up to ~60 seconds after its scheduled time, not instantly at the exact second.
- **Manual override**: an editor can always bypass the wait by opening the entry and re-saving with a past/present date — see `EntryForm::save()`, which resolves Published-vs-Scheduled the same way on every save.

## Activity log

Activity logging is **optional** and is **not** part of `draft:setup`. If you need it (e.g. to keep track of who changed what in the admin panel), run this command once after the initial setup:

```bash
php artisan draft:activity_log
```

Like every other bootstrap step, it is a standalone Artisan command — run `php artisan list` to find it, or `php artisan draft:activity_log --help` to see the options it supports.

### Running it in each environment

**Local development** — run it directly from the project root:

```bash
php artisan draft:activity_log
```

**Docker (staging)** — run it inside the running `app` container (remember the `--env-file` flag, see [Docker (staging)](#docker-staging)):

```bash
docker compose --env-file .env.docker exec app php artisan draft:activity_log
```

**Production / existing install** — run it after pulling the latest code and running migrations, since the command depends on the application being fully set up (database migrated, `.env` configured):

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan draft:activity_log
```

### Notes

- Run `php artisan draft:setup` **first** — the activity log needs a working install (migrated database and at least one user).
- If something doesn't look right after running it, check `storage/logs/laravel.log`.

## reCAPTCHA (bot protection)

[Google reCAPTCHA v3](https://developers.google.com/recaptcha/docs/v3) — score-based, no checkbox/puzzle widget. Set these in `.env` to turn it on:

```
RECAPTCHA_ENABLED=true
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=
RECAPTCHA_SCORE_THRESHOLD=0.5   # 0.0 (likely a bot) – 1.0 (likely human)
```

**`RECAPTCHA_ENABLED`** is a dedicated on/off switch, separate from the keys — flip it to `false` to disable reCAPTCHA locally during development without having to blank out (and later re-type) real keys. It defaults to `true`.

**Currently protects**: the admin login form only. It's disabled (skipped everywhere it's wired up, nothing breaks) whenever `RECAPTCHA_ENABLED=false` *or* either key is blank — so it's safe to leave off in local dev either way.

- `App\Services\RecaptchaService` — the whole implementation. `enabled()` is true only when the toggle is on and both keys are set; `verify(?string $token, string $expectedAction, ?float $threshold = null): bool` calls Google's `siteverify` API and checks success + matching `action` + score, failing closed (`false`) on anything wrong, including a network error — never silently "pass" on a broken check. `siteKey()` exposes the public key for the frontend script.
- `<x-recaptcha-script />` (`resources/views/components/recaptcha-script.blade.php`) — loads Google's JS (`@once`, so including it more than once on a page is harmless).
- `App\Modules\Auth\resources\views\livewire\login.blade.php` and `AuthServiceProvider::configureActions()` are the reference implementation for wiring a new form up: render `<x-recaptcha-script />` + a hidden `recaptcha_token` input only when `$recaptcha->enabled()`, call `grecaptcha.execute(siteKey, {action: '...'})` on submit to fill that hidden input before the form actually posts, then check `$recaptcha->verify($request->input('recaptcha_token'), '...')` server-side before doing anything else with the request. Pick a distinct `action` name per form/flow (e.g. `'login'`) — it's part of what's verified, and helps you tell forms apart in the [reCAPTCHA admin console](https://www.google.com/recaptcha/admin).
- Add it to a Livewire component the same way: call `app(RecaptchaService::class)->verify(...)` wherever you'd otherwise start processing a submission (e.g. the top of a `save()`/`submit()` method), same as `AuthServiceProvider` does — `enabled()` first, `verify()` second, and treat a `false` from either exactly like a validation failure.

## Frontend templating

A public request hits `App\Modules\Collections\Http\Controllers\PageController`, which resolves the `Site` from the request host/path, finds the matching published `Entry` by its URL, and renders the Blade view configured on that entry's blueprint (`Blueprint::data.template`) with:

- `$entry` — the raw `Entry` model
- `$data` — `$entry->fluentData()`, the entry's field data recursively wrapped in `Illuminate\Support\Fluent`, so any field reads as `$data->field` (as well as `$data['field']`), at any nesting depth — including replicator rows. It also resolves `taxonomy` fields to their `TaxonomyTerm`(s), `author` fields to a user's name/email, `link` fields to a resolved URL, `assets` fields to `AssetMeta`, drops replicator rows switched off in the editor, and — for a non-origin-locale translation — always reflects the origin entry's _current_ value for any field whose "Locale" toggle is off.
- `$site` — the resolved `Site`

See `resources/views/pages/static/homepage.blade.php` for a full worked example, including a live demo block of every directive below.

### Blade directives

Registered in `App\Providers\TemplatingServiceProvider`, backed by `App\Support\Templating\TemplateTags`. Available in any Blade view, not just entry templates.

#### `@global('handle')` … `@endglobal`

Resolves a global set's saved values into `$global` (a `Fluent`, same shape as `$data`). Renders nothing if that global set has no saved data.

```blade
@global('site_settings')
    <p>{{ $global->site_name }}</p>
@endglobal
```

#### `@collection('handle', $options = [])` … `@endcollection`

Loops a collection's entries, exposing `$entry` (the model) and `$data` (`$entry->fluentData()`) each iteration.

```blade
@collection('blog')
    <a href="{{ $entry->uri }}">{{ $data->title }}</a>
@endcollection
```

#### `@taxonomy('handle', $options = [])` … `@endtaxonomy`

Loops a taxonomy's terms, exposing `$term` (the model) and `$data` (`$term->fluentData()`).

```blade
@taxonomy('categories')
    <li>{{ $data->title }}</li>
@endtaxonomy
```

#### `@sites()` … `@endsites`

Loops every configured site, in their configured order, exposing `$site`.

```blade
@sites()
    <a href="{{ $site->url }}">{{ $site->name }}</a>
@endsites
```

### Query options

`@collection()` and `@taxonomy()` both take an optional second argument — an options array — as plain PHP inside the directive's parentheses:

| Option    | Applies to    | Example                                                               | Behavior                                                                                                                                                                                                                         |
| --------- | ------------- | --------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `site`    | both          | `['site' => 'indonesia']`                                             | Only that site's rows.                                                                                                                                                                                                           |
| `status`  | `@collection` | `['status' => 'draft']`                                               | Overrides the default `published` filter.                                                                                                                                                                                        |
| `where`   | both          | `['where' => ['featured' => true]]`                                   | Equality filter. Matches a real column (`site`, `status`, `date`, `order`, `slug`, `uri`, `id`, `created_at`, `updated_at`) by that name; anything else is looked up inside the field data (`data->featured`).                   |
| `sort`    | both          | `['sort' => 'date:desc']` or `['sort' => ['date:desc', 'title:asc']]` | `field:asc` / `field:desc`, same column-or-data-field rule as `where`. Multiple entries sort by each in turn.                                                                                                                    |
| `random`  | both          | `['random' => true]`                                                  | Shuffles instead of sorting; overrides `sort` when both are given.                                                                                                                                                               |
| `limit`   | both          | `['limit' => 5]`                                                      | Caps how many rows come back.                                                                                                                                                                                                    |
| `related` | `@collection` | `['related' => $entry]`                                               | Pass an `Entry` (or its id). Returns other entries in the same collection that share at least one taxonomy term with it, excluding itself. Returns nothing (not everything) if the source entry has no taxonomy field filled in. |

Options combine freely:

```blade
@collection('blog', ['site' => $site->handle, 'where' => ['featured' => true], 'sort' => 'date:desc', 'limit' => 3])
    <a href="{{ $entry->uri }}">{{ $data->title }}</a>
@endcollection

@collection('blog', ['related' => $entry, 'limit' => 3])
    <a href="{{ $entry->uri }}">{{ $data->title }}</a>
@endcollection
```

> A directive's `@` must be preceded by whitespace or punctuation, not a letter — this is standard Blade behavior (it's how Blade tells `@if` apart from an email address like `name@if.com`). Writing `foo@collection(...)` inline won't compile; `foo @collection(...)` will.

## Docker (staging)

A self-contained Docker setup for spinning up a staging build — e.g. for a project manager to click around a real deploy — without installing PHP/Node/MySQL on the server at all, just Docker. One `app` container runs [FrankenPHP](https://frankenphp.dev/) (PHP and the Caddy web server bundled into a single binary — no separate Nginx/PHP-FPM processes or fastcgi config to keep in sync, see `docker/Caddyfile`), the Laravel scheduler, and a queue worker, all under Supervisor (`docker/supervisord.conf`); a `db` container runs MySQL. Deliberately simple: one app instance, meant for a staging box, not scaled production traffic.

### Prerequisites on the server

- [Docker Engine + the Compose plugin](https://docs.docker.com/engine/install/) installed (`docker --version` and `docker compose version` both work).
- A user in the `docker` group (or root) — no PHP, Composer, Node, or MySQL need to be installed on the host; everything runs inside the containers.
- Port `8080` open on the server's firewall if it needs to be reached from outside — see [Reverse proxy / domain](#reverse-proxy--domain-optional) below if you want it behind a host reverse proxy on port 80/443 with a real domain instead.

### First deploy — genuinely just two commands

```bash
git clone <repo-url> draftcms
cd draftcms
docker compose --env-file .env.docker build
docker compose --env-file .env.docker up -d
```

That's it — no `.env` to copy, no `APP_KEY` to generate and paste anywhere, no separate bootstrap command to run afterward. Here's what makes that possible, and why:

- **`.env.docker`** is committed to the repo (unlike a normal `.env`) with working staging defaults already filled in — including a real, pre-generated `APP_KEY` — and is wired directly into `docker-compose.yml`'s `env_file:` for both services. There's nothing to copy or fill in for a first deploy to work.
- **`docker/entrypoint.sh`** waits for MySQL, migrates, and — only the very first time (it checks the `users` table directly before doing this, so it's a safe no-op on every later boot) — runs `draft:setup` itself, using the `DRAFT_ADMIN_*`/`DRAFT_SITE_*` values from `.env.docker`. So the admin account, default site, and default "Pages" collection already exist by the time `docker compose up -d` returns.

> **Every `docker compose` command in this section needs `--env-file .env.docker`** — that's what makes `APP_PORT` (and everything else in that file) actually take effect; Compose otherwise only auto-reads a file literally named `.env`, which this project doesn't use for Docker. Forgetting the flag doesn't error, it just silently falls back to defaults (port `8080`, etc.) — if a value you set in `.env.docker` doesn't seem to apply, check you included it. Tip: `alias dc='docker compose --env-file .env.docker'` in your shell profile avoids retyping it.

Visit `http://<server-address>:8080` (or whatever `APP_PORT` you set — see below) — `/admin-panel` to log in with the default admin account (`admin@example.com` / `change-me-1234`, from `.env.docker`'s `DRAFT_ADMIN_*` values — **change this password after your first login**, from Settings → Security in the admin panel).

### Customizing before the first boot

`.env.docker` is committed but still a real env file — edit it before your *first* `docker compose --env-file .env.docker up -d` if you want something other than the defaults:

- `APP_PORT` — the host port the app is published on, if `8080` is already taken on this server (or you just want a different one).
- `DRAFT_ADMIN_EMAIL`/`DRAFT_ADMIN_PASSWORD` — the first admin account's login.
- `APP_URL` — the real domain/address this deploy will actually be reached at.
- `DB_PASSWORD` — also update `MYSQL_PASSWORD`/`MYSQL_ROOT_PASSWORD` in the same file to match if you change this; see the comment above them in `.env.docker` for why there are two names for the same value.

Editing any of these *after* the first boot needs a restart to take effect (`docker compose --env-file .env.docker down && docker compose --env-file .env.docker up -d`) — and for `DRAFT_ADMIN_*`/`DB_PASSWORD`/`MYSQL_*` specifically, only actually changes anything on a database that's still empty (a fresh volume) or has been wiped with `down -v` — see Notes below.

### Verifying it came up

```bash
docker compose ps                 # both "app" and "db" should show "healthy"
docker compose logs -f app        # tail FrankenPHP/scheduler/setup output
curl -I http://localhost:8080     # expect a 200/302, not a connection error — adjust the port if you changed APP_PORT
```

If `docker compose ps` shows `app` stuck on "health: starting" for more than ~30 seconds on a brand-new deploy, check `docker compose logs app` — it's most likely still waiting on MySQL to finish its own first-time initialization (`Waiting for the database...` repeating is normal here and resolves on its own).

### Redeploying after a code change

```bash
git pull
docker compose --env-file .env.docker build app
docker compose --env-file .env.docker up -d
```

Migrations and the config/route/view caches re-run automatically on every container start — the one-time admin/site bootstrap correctly does not re-run (see above), so this is safe to repeat on every deploy.

### Reverse proxy / domain (optional)

For a staging URL on port 80/443 with a real domain (rather than `http://ip:8080`), put a reverse proxy (Nginx, Caddy, or Traefik) on the host in front of the `app` container, proxying to `127.0.0.1:${APP_PORT}` (`8080` unless you changed it). That's a host-level concern outside this repo's `docker-compose.yml` — e.g. a minimal host Nginx `server` block with `proxy_pass http://127.0.0.1:8080;`, or a host Caddyfile pointed at the same address for automatic HTTPS.

### What's running

- **`app`** — three processes: FrankenPHP (mapped to `APP_PORT` on the host, default `8080`), `php artisan schedule:work` (so [scheduled entries](#scheduled-entries-requires-a-server-cron) actually go live in staging, without needing a real crontab on the host machine), and `php artisan queue:work` (so emails — password resets, currently — send in the background instead of making the request that triggered them wait on SMTP; see `App\Models\User::sendPasswordResetNotification()`).
- **`db`** — MySQL 8.4, with a named volume (`db-data`) so data survives `docker compose down` (use `docker compose down -v` to wipe it too — this also wipes the admin account, so the *next* `docker compose --env-file .env.docker up -d` will re-bootstrap a fresh one from `.env.docker`'s `DRAFT_ADMIN_*` values, and will pick up any `DB_PASSWORD`/`MYSQL_*` changes made since the last time the volume was created).
- Uploaded media persists in the `storage-app` named volume across image rebuilds.

### Notes

- **This is a staging/demo setup, not a production-hardening guide.** `.env.docker` deliberately commits a working `APP_KEY` and DB password so a fresh deploy needs zero manual steps — fine for an internal PM-facing staging box, but replace both (and rotate the admin password) before this is ever exposed anywhere that matters. `APP_DEBUG=false` is still set correctly, so Laravel's debug page won't leak values to visitors regardless.
- `DB_USERNAME`/`MYSQL_USER` must not be `root` — the MySQL image rejects that value (root is already managed separately via `MYSQL_ROOT_PASSWORD`).
- `db` is not published to the host by default — only `app`'s web port is. To connect a DB client (TablePlus, DBeaver, etc.) from outside Docker, add a `ports: ["3306:3306"]` entry under the `db` service in `docker-compose.yml`.
- Logs from all three `app` processes (FrankenPHP, the scheduler, the queue worker) go to the container's stdout — view them with `docker compose logs -f app`.
- A one-off command that doesn't need the `db` service (e.g. inspecting the built image) can skip starting it: `docker compose run --rm --no-deps app <command>`. `docker/entrypoint.sh` only waits on the database and runs the migrate/bootstrap/cache steps when the container's actual command is `supervisord` (i.e., a real `docker compose up`), so one-off commands like this run immediately.

## Testing

```bash
vendor/bin/pint --dirty --format agent   # fix code style on changed files
php artisan test --compact               # full suite (SQLite)
php artisan test --compact --filter=Name # a single test
```

Every change should be covered by a Pest test — see `tests/Feature/**` for existing patterns per module (`tests/Feature/Support/Templating/TemplateTagsTest.php` covers the directives above).