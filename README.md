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

A self-contained Docker setup for spinning up a staging build — e.g. for a project manager to click around a real deploy — without installing PHP/Node/MySQL on the server at all, just Docker. One `app` container runs [FrankenPHP](https://frankenphp.dev/) (PHP and the Caddy web server bundled into a single binary — no separate Nginx/PHP-FPM processes or fastcgi config to keep in sync, see `docker/Caddyfile`) plus the Laravel scheduler, both under Supervisor (`docker/supervisord.conf`); a `db` container runs MySQL. Deliberately simple: one app instance, no queue worker (the app doesn't dispatch any queued jobs today — add a `queue:work` program to `docker/supervisord.conf` if that changes), meant for a staging box, not scaled production traffic.

### Prerequisites on the server

- [Docker Engine + the Compose plugin](https://docs.docker.com/engine/install/) installed (`docker --version` and `docker compose version` both work).
- A user in the `docker` group (or root) — no PHP, Composer, Node, or MySQL need to be installed on the host; everything runs inside the containers.
- The port you'll expose (`8080` by default) open on the server's firewall if it needs to be reached from outside — see [Reverse proxy / domain](#reverse-proxy--domain-optional) below if you want it behind a host reverse proxy on port 80/443 with a real domain instead.

### Step-by-step: first deploy

**1. Clone the repository onto the server**

```bash
git clone <repo-url> draftcms
cd draftcms
```

**2. Copy the staging env file**

```bash
cp .env.staging.example .env
```

`.env.staging.example` differs from `.env.example` in exactly the ways Docker needs: `DB_HOST=db` (the compose service name, not `127.0.0.1`), `APP_ENV=staging`, `APP_DEBUG=false`. Don't substitute a plain `.env.example` copy here — the DB connection won't resolve.

**3. Edit `.env` for this deployment**

At minimum, change:

- `APP_URL` — the URL this staging site will actually be reached at, e.g. `http://staging.example.com` or `http://203.0.113.10:8080`.
- `DB_PASSWORD` — replace the placeholder `change-me` with a real password (also used as the MySQL root password — see Notes below).
- `APP_PORT` — the host port FrankenPHP is published on, if `8080` is already taken on this server.

**4. Build the image**

```bash
docker compose build
```

This installs Composer dependencies, builds the frontend assets (`npm run build`), and assembles the FrankenPHP image — see the multi-stage `Dockerfile`. Expect this step to take a few minutes the first time.

**5. Generate an `APP_KEY`**

No PHP needs to be installed on the host for this — it runs inside a throwaway container built from the image you just built:

```bash
docker compose run --rm --no-deps app php artisan key:generate --show
```

Copy the printed value (`base64:...`) into `.env` as `APP_KEY=`.

**6. Start the stack**

```bash
docker compose up -d
```

`docker/entrypoint.sh` waits for MySQL to accept connections, then runs `migrate --force`, `storage:link`, and caches config/routes/views — automatically, every time the container starts. So the app is fully ready as soon as this command returns; no separate "run migrations" step.

**7. Bootstrap the CMS content (one time only)**

The database schema exists at this point, but there's no admin user, site, or default collection yet — that's what `draft:setup` does everywhere, Docker included:

```bash
docker compose exec app php artisan draft:setup \
  --name="Admin" --email=admin@example.com --password=change-me-1234 \
  --site-name="Staging" --site-handle=default
```

**8. Verify it's up**

```bash
docker compose ps                 # both "app" and "db" should show "healthy"/"running"
docker compose logs -f app        # tail FrankenPHP/scheduler output
curl -I http://localhost:8080     # expect a 200/302, not a connection error
```

Then hand the project manager the URL — `http://<server-address>:8080`, and `/admin-panel` for the CMS login screen with the credentials from step 7.

### Redeploying after a code change

```bash
git pull
docker compose build app
docker compose up -d
```

Migrations and the config/route/view caches re-run automatically on every container start (step 6's entrypoint logic) — steps 5 and 7 are one-time only and shouldn't be repeated on every deploy.

### Reverse proxy / domain (optional)

For a staging URL on port 80/443 with a real domain (rather than `http://ip:8080`), put a reverse proxy (Nginx, Caddy, or Traefik) on the host in front of the `app` container, proxying to `127.0.0.1:${APP_PORT}`. That's a host-level concern outside this repo's `docker-compose.yml` — e.g. a minimal host Nginx `server` block with `proxy_pass http://127.0.0.1:8080;`, or a host Caddyfile pointed at the same address for automatic HTTPS.

### What's running

- **`app`** — FrankenPHP (mapped to `APP_PORT` on the host, default `8080`) and `php artisan schedule:work` (so [scheduled entries](#scheduled-entries-requires-a-server-cron) actually go live in staging, without needing a real crontab on the host machine).
- **`db`** — MySQL 8.4, with a named volume (`db-data`) so data survives `docker compose down` (use `docker compose down -v` to wipe it too — this deletes all staging data, so double-check before running it).
- Uploaded media persists in the `storage-app` named volume across image rebuilds.

### Notes

- Never run with `APP_DEBUG=true` on anything a client/PM can reach: Laravel's debug page leaks `.env` values and stack traces.
- `DB_USERNAME` must not be `root` — the MySQL image's `MYSQL_USER` rejects that value (root is already managed separately via `MYSQL_ROOT_PASSWORD`, which `docker-compose.yml` also derives from `DB_PASSWORD`).
- `db` is not published to the host by default — only `app`'s web port is. To connect a DB client (TablePlus, DBeaver, etc.) from outside Docker, add a `ports: ["3306:3306"]` entry under the `db` service in `docker-compose.yml`.
- Logs from both `app` processes (FrankenPHP, the scheduler) go to the container's stdout — view them with `docker compose logs -f app`.
- If `docker compose up -d` seems to hang on first boot, it's very likely step 6's entrypoint still waiting on MySQL to finish initializing (`docker compose logs -f app` will show "Waiting for the database..." repeating) — that's normal on the very first start and can take up to ~30 seconds while MySQL's own image bootstraps.

## Testing

```bash
vendor/bin/pint --dirty --format agent   # fix code style on changed files
php artisan test --compact               # full suite (SQLite)
php artisan test --compact --filter=Name # a single test
```

Every change should be covered by a Pest test — see `tests/Feature/**` for existing patterns per module (`tests/Feature/Support/Templating/TemplateTagsTest.php` covers the directives above).
