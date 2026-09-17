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

## Testing

```bash
vendor/bin/pint --dirty --format agent   # fix code style on changed files
php artisan test --compact               # full suite (SQLite)
php artisan test --compact --filter=Name # a single test
```

Every change should be covered by a Pest test — see `tests/Feature/**` for existing patterns per module (`tests/Feature/Support/Templating/TemplateTagsTest.php` covers the directives above).
