# Beamtic MarkPress

MarkPress displays Markdown notes from HedgeDoc as HTML. It makes notes easier
for search engines to index, cleaner to print, and easy to save as PDF using
the browser's print menu.

This repository contains the Laravel application only.
[Sandboxer](https://github.com/Turbulentarius/sandboxer) provides the Docker
Compose environment used to develop it, including PHP, Apache, and HedgeDoc.
Sandboxer's containers and configuration live in that separate repository.

## Requirements

- PHP 8.4.1 or newer, with DOM and the extensions required by Composer.
- Composer 2.
- A reachable HedgeDoc instance with notes that can be downloaded without signing in.

MarkPress does not need a database, migrations, a queue worker, Node.js, or an
asset build. CSS and fonts are served directly from `public/`.

## Develop with Sandboxer

For a fresh checkout, clone MarkPress into Sandboxer's `www/laravel` directory
**before** starting Sandboxer. Its setup service leaves existing applications alone.
If that directory already contains the generated example or your own work, move
it aside first; do not overwrite it.

```sh
git clone https://github.com/Turbulentarius/sandboxer.git
cd sandboxer
git clone https://github.com/Turbulentarius/markpress.git www/laravel
docker compose up -d --build
docker compose exec -w /srv/sandboxer/laravel php composer setup
```

Run `composer setup` once for a new checkout: it installs dependencies, copies
`.env.example` if `.env` is missing, and generates the application key.
For subsequent dependency installs, use `composer install` instead.

Open **http://laravel.localhost/notes** for the introduction. Create a note in
**http://hedgedoc.localhost**, make it publicly readable, then open
`http://laravel.localhost/notes/<note-id>` using its HedgeDoc ID or alias.
Sandboxer's other example applications and database services are independent of
MarkPress; MarkPress itself does not use them.

## Configure the note source

Edit the application's `.env` file (not Sandboxer's root `.env`):

```dotenv
APP_NAME="Beamtic MarkPress"
APP_URL=http://laravel.localhost
HEDGEDOC_URL=http://hedgedoc:3000
```

`HEDGEDOC_URL` is the HedgeDoc base URL reachable from the PHP process. The
`hedgedoc:3000` hostname works on Sandboxer's internal Docker network. When
running outside that network, set a reachable URL, for example
`https://notes.example.com`. MarkPress fetches `<base-url>/<note-id>/download`.

After changing settings, clear any cached configuration:

```sh
docker compose exec -w /srv/sandboxer/laravel php php artisan config:clear
```

Keep `.env` private. The repository includes only `.env.example`, without an
application key or personal credentials.

## Run without Sandboxer

From the application directory, with PHP and Composer installed:

```sh
composer setup
# Set HEDGEDOC_URL and APP_URL in .env for your environment.
php artisan serve
```

Visit `http://127.0.0.1:8000/notes`. For another web server, its document root must
be `public/`; `storage/` and `bootstrap/cache/` must be writable by PHP.
Use `APP_DEBUG=false` when making an instance publicly accessible.

## Routes and notes

- `/notes` renders the bundled introduction in `resources/notes/default.md`.
- `/notes/<note-id>` renders a note from the configured HedgeDoc instance.
- `/up` is Laravel's application health endpoint; it does not check HedgeDoc.

The first H1 supplies the page title. Links in the form `/s/<note-id>` and
matching absolute HedgeDoc share links are rewritten to `/notes/<note-id>`.
The optional `robots` field in a note's leading front matter becomes the
`X-Robots-Tag` response header.

MarkPress forwards no login credentials to HedgeDoc. Its renderer preserves raw
HTML, including note `<style>` blocks, so configure a source whose note content
you trust; it is not a general-purpose HTML sanitizer.

To print or save a note as PDF, use your browser's print menu.

## Tests

Inside Sandboxer, run from its repository root:

```sh
docker compose exec -w /srv/sandboxer/laravel php composer test
```

With local PHP, run `composer test` from this repository. Tests fake HedgeDoc HTTP
responses and require neither a running HedgeDoc instance nor a database.

## Repository contents

Application code, static assets, configuration templates, tests, and
`composer.lock` are versioned. Installed dependencies, local environment files,
databases, logs, caches, and Sandboxer's development environment are excluded.

See [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) for the adapted HedgeDoc CSS,
Roboto fonts, and dependency licenses. The Laravel skeleton's generic project
license declaration has been removed; those third-party notices do not assign a
license to original MarkPress code.
