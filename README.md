# ArtPress

A blog-focused classic WordPress theme. Assets are built with **Vite**, styled with **Tailwind CSS v4**, and scripted in **TypeScript**. PHP is linted with **WordPress Coding Standards (security rules)** and unit-tested with **PHPUnit + Brain Monkey**.

- Requires WordPress 6.1+, PHP 8.0+
- Text domain: `artpress` (translations: `es_ES`, `uk`)

## Features

| Feature | Where | Settings |
|---|---|---|
| Home hero grid (9 latest posts) + offset-paginated post grid | `template-parts/index.php`, `hero.php`, `inc/queries.php` | — |
| Infinite article (auto-loads older posts on single posts) | `inc/infinite-article.php`, `resources/scripts/modules/infinite-article.ts` | ArtPress → General |
| Reading progress bar | `resources/scripts/modules/reading-progress.ts` | — |
| Share buttons (Facebook / X / LinkedIn) | `template-parts/single/share-buttons.php` | ArtPress → General |
| Newsletter subscribe (AJAX → email to admin) | `inc/subscribe.php`, `resources/scripts/modules/subscribe.ts` | ArtPress → General |
| SMTP for `wp_mail()` | `inc/admin/smtp/*` | ArtPress → SMTP |
| Categories / Tag Cloud block limits & sort, search-column selection | `inc/admin/widgets/*` | ArtPress → Widgets |
| Advanced Ads / AdSense compatibility patches | `inc/ads-compat.php` | — |
| Per-category archive layout: list / grid, with or without images | `inc/category-layout.php`, `template-parts/post-card.php` | Posts → Categories → edit → *Posts layout* |
| Related posts | `template-parts/single/related-posts.php` | — |

## Requirements for development

- Node.js 22+ and npm
- PHP 8.0+ and Composer (or Docker, see below)
- The theme folder must live in `wp-content/themes/` of a WordPress install for `npm run dev` (Vite base path is derived from the folder location).

## Getting started

```bash
npm install
composer install

npm run dev        # Vite dev server with HMR (removes dist manifest → theme loads from localhost:5173)
npm run build      # production build into dist/ (theme loads hashed files via manifest)
```

## Quality checks

```bash
composer lint      # PHPCS: WordPress security, DB, i18n, PHP-compat rules
composer lint:fix  # auto-fix what PHPCBF can
composer test      # PHPUnit unit tests
npm run typecheck  # tsc --noEmit (strict)
make check         # all of the above (lint + test + typecheck)
```

No local PHP? Run the same commands in Docker:

```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app -e COMPOSER_HOME=/tmp/c composer:2 install
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app php:8.3-cli vendor/bin/phpcs
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app php:8.3-cli vendor/bin/phpunit
```

### What the linter enforces

`phpcs.xml.dist` pulls in, from WPCS 3:

- `WordPress.Security.*` — output escaping, nonce verification, input sanitization/unslashing, safe redirects
- `WordPress.DB.*` — prepared SQL, no direct queries / interpolation
- `WordPress.WP.*` / `WordPress.PHP.*` — discouraged & deprecated APIs, global overrides, enqueue params, `date()`, `eval`, etc.
- `WordPress.WP.I18n` — text domain `artpress`, translator comments, ordered placeholders
- `WordPress.NamingConventions.PrefixAllGlobals` — prefix `artpress` (template-part locals exempted)
- `PHPCompatibilityWP` — PHP 8.0+

Whitespace/formatting rules are deliberately not enforced. Intentional exceptions are marked inline with `// phpcs:ignore <Sniff> -- reason`.

## CI/CD (GitHub Actions)

- **CI** (`.github/workflows/ci.yml`) — on every push to `main` and every PR: PHPCS, PHPUnit on PHP 8.0–8.4, TypeScript type-check + Vite build.
- **Build theme zip** (`.github/workflows/release.yml`) — manual. Go to **Actions → Build theme zip → Run workflow**. It runs CI, then `make zip`, and uploads `artpress-<version>.zip` as a workflow artifact. Tick **"Also publish a GitHub Release"** to create release `v<version>` with the zip attached (fails if that tag already exists).

The version comes from `Version:` in `style.css`.

## Packaging locally

```bash
make zip      # npm ci + build, then artpress-<version>.zip with only the shippable files
make clean
```

## Translations

```bash
npm run i18n   # regenerate languages/artpress.pot, merge into .po files, compile .mo
```

## License

GPL-2.0-or-later
