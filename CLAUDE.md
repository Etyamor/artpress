# CLAUDE.md

Guidance for Claude Code when working in this repository. Human-oriented docs live in `README.md`.

## Project Overview

ArtPress is a classic (non-block) WordPress theme for blogs. Vite bundles assets, Tailwind CSS v4 styles them, and front-end code is TypeScript. Minimum targets: **WordPress 6.1, PHP 8.0** — don't use PHP 8.1+ syntax (enums, readonly, `never`, first-class callables, `new` in initializers) in theme code.

Release version lives **only** in the `Version:` header of `style.css` (zip name, asset `?ver=`, release tag all read it). `package.json` has no version.

## Commands

| Task | Command |
|---|---|
| Dev server (HMR) | `npm run dev` (deletes `dist/.vite/manifest.json` → theme switches to dev mode) |
| Production build | `npm run build` |
| TS type-check | `npm run typecheck` |
| PHP lint (WPCS security) | `composer lint` / `vendor/bin/phpcs` (`composer lint:fix` for phpcbf) |
| PHP tests | `composer test` / `vendor/bin/phpunit` (single: `vendor/bin/phpunit --filter SearchHookTest`) |
| All checks | `make check` |
| Theme zip | `make zip` → `artpress-<version>.zip` |
| Translations | `npm run i18n` (pot → sync po → compile mo) |

**This machine has no host PHP/Composer/make/zip.** Run PHP tooling via Docker:

```bash
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app -e COMPOSER_HOME=/tmp/c composer:2 install
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app php:8.3-cli vendor/bin/phpcs
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app php:8.3-cli vendor/bin/phpunit
```

Use `php:8.0-cli` to verify the minimum PHP version. For `make zip`, use a `node:22` container and `apt-get install zip make`.

**Before finishing any change, run:** phpcs + phpunit (if PHP touched), `npm run typecheck` + `npm run build` (if TS/CSS touched). CI runs all of them.

## Architecture

### Bootstrapping
`functions.php` only `require_once`s modules from `inc/`. Root templates (`header.php`, `footer.php`, `index.php`, `singular.php`) only call `get_template_part()` into `template-parts/`. Template parts execute inside `load_template()`, so their top-level variables are **function-local** (this is why PHPCS's PrefixAllGlobals variable rule is excluded for `template-parts/`).

### inc/ modules
- `setup.php` — theme supports, 4 nav menus (`primary`, `footer`, `footer_categories`, `footer_legal`), `sidebar-1`, `artpress_reading_time()` (200 wpm, min 1).
- `assets.php` — dual-mode Vite loading (see below). Localizes `artpressInfinite` onto the main script on single posts when infinite article is on.
- `cleanup.php` — removes emoji, generator, block-library CSS, jQuery, etc.
- `queries.php` — `ARTPRESS_HERO_COUNT = 9`; `artpress_setup_home_query()` **intentionally replaces `$GLOBALS['wp_query']`** with an offset query (so `the_posts_pagination()` works), `artpress_restore_query()` puts it back. Offset = `9 + (paged-1) * posts_per_page`; `found_posts` is reduced by 9.
- `template-helpers.php` — `artpress_post_meta()`, `artpress_post_thumbnail()` (echo markup).
- `category-layout.php` — per-category archive layout stored in term meta `artpress_layout` (`list` default, `grid`, `list-text`, `grid-text`; `-text` = no thumbnails). Select on category add/edit screens (nonce `artpress_category_layout`, cap `edit_term`; default is saved as *no meta*), "Layout" column on the categories list. `artpress_current_archive_layout()` (filter `artpress_archive_layout`) is used by `template-parts/index.php`; the home page always uses `grid`. `template-parts/post-card.php` takes `$args['layout']`; wrapper classes come from `artpress_layout_wrapper_class()`. Add a layout by extending `artpress_category_layouts()`, the REST enum, the wrapper class switch and post-card.
- `subscribe.php` — `wp_ajax(_nopriv)_artpress_subscribe` → nonce `artpress_subscribe` (field `nonce`), emails admin.
- `infinite-article.php` — next post = next **older** published post (`artpress_infinite_next_post_id`, filterable via `artpress_infinite_next_post_id`, statically cached per post). The partial is served by requesting the permalink with `?artpress_infinite=1`; a `template_redirect` hook at priority 20 renders `template-parts/single/infinite-article.php` in a real loop and `exit`s, sets `DONOTCACHEPAGE` and `noindex`. This keeps singular context so ad plugins (Advanced Ads) inject into content as on normal page views — don't move this to admin-ajax/REST.
- `ads-compat.php` — workarounds for Advanced Ads 2.0.29 bugs (`<insclass=` missing space; ads.txt reading the wrong store key). Both are idempotent so they stay harmless once the plugin is fixed.
- `admin/` — "ArtPress" admin menu. Each settings group has `options.php` (register_setting + `*_sanitize`), `fields.php` (render) and sometimes `hooks.php` (frontend effect):
  - `artpress_general`: `subscribe_section`, `share_buttons`, `share_facebook|x|linkedin`, `infinite_article`, `infinite_article_limit` (1–20, default 5). Defaults when unset are **true** for subscribe/share, **false** for infinite.
  - `artpress_smtp`: hooks `phpmailer_init`. Password stored as plain text in options (known limitation).
  - `artpress_widgets`: `render_block` overrides for `core/categories` / `core/tag-cloud` (limit, orderby name|count), and `posts_search` override restricting search columns (`post_title|post_content|post_name`, whitelist only).

### Asset loading (`inc/assets.php`)
- **Production**: `dist/.vite/manifest.json` exists → every `.css`/`.js` entry in the manifest is enqueued with handle `vite-` + `sanitize_title(<manifest key>)` (main script handle is `vite-resources-scripts-scripts-ts`), versioned by `style.css` Version.
- **Development**: no manifest → loads `@vite/client`, `resources/scripts/scripts.ts`, `resources/styles/styles.css` from `http://localhost:5173/wp-content/themes/<theme>/…`.
- All Vite JS handles get `type="module"`.
- If you rename/move an entry point, update **both** `vite.config.mjs` `rollupOptions.input` and the two paths in `assets.php`.

### Front-end (TypeScript, `resources/scripts/`)
- `scripts.ts` imports all modules; each module self-guards on the presence of its DOM elements.
- `types/globals.d.ts` declares `window.artpressInfinite`, `window.adsbygoogle`, `window.advads`, and the `artpress:article-loaded` CustomEvent (dispatched on `document` with `{ article, postId }` after each infinite article is activated — use it to init per-article behaviour).
- `tsconfig.json` is strict with `noUnusedLocals/Parameters`; Vite transpiles, `tsc` only type-checks (`noEmit`).
- `infinite-article.ts` re-executes scripts from fetched articles in order (marker comments), skips already-loaded `src` scripts and non-executable types, guards `adsbygoogle.push` against TagErrors, and decodes Advanced Ads consent placeholders. Read its comments before changing ad-related logic.

## Security & Lint Rules (`phpcs.xml.dist`)

PHPCS enforces WPCS `WordPress.Security`, `WordPress.DB`, i18n, discouraged/deprecated APIs, prefixing and PHPCompatibilityWP — **not** whitespace style. When writing PHP:
- Escape at output: `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`. Build full URLs first, then `esc_url()` the whole thing.
- Input: `sanitize_*( wp_unslash( $_POST['x'] ?? '' ) )`; verify nonces on state-changing requests; check capabilities in admin pages.
- SQL: `$wpdb->prepare()`; any non-placeholder fragment (e.g. column names) must come from a hard-coded whitelist.
- i18n: text domain `artpress`, `/* translators: */` comment above strings with placeholders, ordered placeholders (`%1$s`) when more than one. Changing a msgid means updating `languages/*.po` (+ `npm run i18n:mo`).
- Use `wp_date()`/`gmdate()` rather than `date()`.
- Prefix all globals/functions/constants/hooks with `artpress` (`ARTPRESS_` for constants).
- Suppress a sniff only for genuinely intentional code: `// phpcs:ignore Sniff.Name -- reason`.

Note that admin settings screen labels are currently hard-coded English (not translated).

## Tests (`tests/`)

- PHPUnit 9.6 + Brain Monkey + Yoast polyfills; **no WordPress install needed**. Bootstrap: `tests/bootstrap.php`; fakes for `WP_Query` (records args in `WP_Query::$created`, returns results queued in `WP_Query::$queue`), `WP_Post`, `WP_Term`, `$wpdb`, PHPMailer and a `wp_send_json_*` exception in `tests/stubs.php`.
- `tests/TestCase.php`: use `set_up()`/`tear_down()` (snake_case, polyfills). `requireTheme('inc/x.php')` loads function files once per process; `captureHook('inc/.../hooks.php', 'hook_name', 'filter'|'action')` re-includes a hook-only file and returns the closure it registered.
- Because function files are `require_once`d, hooks they add are only seen by the first test. Test hook *registration* with `@runInSeparateProcess` + `@preserveGlobalState disabled`. Same for anything depending on a function **not** existing (Brain Monkey-defined functions persist).
- Mock WP functions with `Brain\Monkey\Functions\when()/expect()/stubs()`; `stubEscapeFunctions()` / `stubTranslationFunctions()` are available.
- New PHP logic should get a test in `tests/Unit/*Test.php`. Prefer extracting logic into named `artpress_*` functions over anonymous closures so it can be unit-tested.

## Styling Rules

**Always prefer Tailwind utility classes directly in PHP templates.** Only use CSS component files (`resources/styles/components/`) with `@apply` when styling markup that WordPress generates and you cannot control — such as `wp_nav_menu()` output, `the_category()` links, widget internals, or JS-driven state toggles (e.g. `.open`, `.menu-open`). If you can add classes to the HTML element in a template, use Tailwind classes there instead of creating a CSS rule.

**Always use semantic color tokens, never raw palette colors.** In templates and CSS, use the semantic aliases defined in `@theme inline` in `resources/styles/colors.css` (e.g. `bg-accent`, `text-foreground`, `border-surface-dark-border`) — never raw palette classes like `bg-primary-600` or `text-neutral-900`. If no existing semantic token fits, add a new one to the `@theme inline` block in `colors.css` first, then use it.

Tailwind v4 auto-detects classes in non-gitignored project files (including `resources/scripts/**/*.ts`), plus the explicit `@source` globs for `template-parts/` and `inc/` in `styles.css`. Write class names as complete literal strings — never build them by concatenation.

## Fonts and Images

Vite only bundles assets referenced from CSS/TS entry points — never from PHP.
- Fonts: `npm install --save-dev @fontsource/<family>` then `@import "@fontsource/<family>";` in `resources/styles/fonts.css`.
- Images: reference from CSS via `url('../images/file.webp')`; optimized by vite-plugin-image-optimizer.

Build output: images → `dist/images/[hash]`, fonts → `dist/fonts/[hash]`, CSS/JS → `dist/[hash].*`.

## CI/CD

- `.github/workflows/ci.yml` — push to `main`, PRs, and `workflow_call`: PHPCS (`cs2pr` annotations), PHPUnit matrix PHP 8.0–8.4, `npm ci` + typecheck + build. `package-lock.json` must stay in sync with `package.json` (`npm ci` fails otherwise).
- `.github/workflows/release.yml` — `workflow_dispatch` ("Build theme zip" button): calls CI, then `make zip`, verifies the archive (contains `style.css` + manifest, no `node_modules/vendor/tests/resources`), uploads it as an artifact; optional input `create_release` publishes GitHub Release `v<Version>`.
- Zip contents are defined by `DIST_FILES`/`DIST_DIRS` in the `Makefile` — add new root-level PHP templates there.

## Release Checklist

1. Bump `Version:` in `style.css` (and `Stable tag` + changelog in `readme.txt`).
2. `npm run i18n` if strings changed.
3. Merge to `main`, then Actions → Build theme zip → Run workflow (tick release if publishing).
