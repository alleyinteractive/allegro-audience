# Design: wordpress.org Release-Readiness for Allegro Audience

Date: 2026-07-24
Status: Approved

## Goal

Make the Allegro Audience plugin submittable to the wordpress.org plugin
directory: no composer dependency required at runtime, the
`alleyinteractive/wp-type-extensions` abstraction removed from the project, and
every issue that would flag a wordpress.org review resolved.

## Current State

- Bootstrap `wp-allegro-audience.php` loads `vendor/wordpress-autoload.php`
  (from `alleyinteractive/composer-wordpress-autoloader`) then requires
  `src/main.php` and calls `main()`.
- `src/main.php` builds an `Alley\WP\Features\Group` (from
  `alleyinteractive/wp-type-extensions`) of two features and boots it.
- Two features, each implementing `Alley\WP\Types\Feature`:
  - `Allegro_Settings` — settings page under Settings → Allegro Audience, a REST
    route (`wp-allegro-audience/v1/settings`) that health-checks + saves the
    tenant URL, and a plugin action link. Renders an inline script/style.
  - `Load_Client_Script` — injects `<script src="{tenant_url}/client.js">` in
    `wp_head` on the front end.
- Runtime composer deps: `composer-wordpress-autoloader`, `wp-type-extensions`.
- Front-end JS build tooling is already gone; `build/` on disk is untracked
  cruft not loaded by the plugin.
- No `readme.txt`. Plugin header has `Tested up to: 7.0` (invalid) and no
  `License`/`License URI` lines.

## Decisions

1. **Eliminate the Feature/Group abstraction** — features become plain classes
   with a `boot()` method; `main()` news them up and boots them directly.
2. **Composer stays dev-only** — no composer autoloader or runtime dep; the
   shipped plugin needs no `vendor/`. Dev tooling (phpcs/phpstan/rector/tests)
   still uses composer.
3. **Fix everything** to be release-ready, not just report.

## Changes

### 1. Remove `wp-type-extensions`

- `src/features/class-allegro-settings.php`, `class-load-client-script.php`:
  remove `use Alley\WP\Types\Feature;` and `implements Feature`. Keep `boot()`.
- `src/main.php`:
  ```php
  function main(): void {
      ( new Allegro_Settings() )->boot();
      ( new Load_Client_Script() )->boot();
  }
  ```
- `src/features/README.md`: rewrite to describe the plain-class pattern (a class
  with a `boot()` method, registered in `main()`), dropping references to the
  `Feature` interface and `Group`.

### 2. Drop composer at runtime

- `wp-allegro-audience.php`: replace the `vendor/wordpress-autoload.php` block
  with direct `require_once` of `src/main.php` and the two feature class files
  (order: feature classes then `main.php`, or `main.php` last). Keep the
  `ABSPATH` guard and `WP_ALLEGRO_AUDIENCE_DIR` define. No admin notice about
  composer needed anymore.
- `composer.json`:
  - `require`: `{ "php": "^8.3" }` only.
  - Remove `alleyinteractive/wp-type-extensions`.
  - Move `alleyinteractive/composer-wordpress-autoloader` to `require-dev` so
    dev/test autoloading of `Alley\WP\Allegro_Audience\` keeps working.
  - Keep `extra.wordpress-autoloader`, `autoload-dev`, and scripts.
- Tests remain green: `tests/bootstrap.php` loads the plugin via the bootstrap
  file (which now requires the classes directly); testkit + the dev autoloader
  cover the `Tests\` namespace.

### 3. `.deployignore`

Add `CLAUDE.md`, `vendor/`, `composer.json`, and `docs/` so the release archive
is self-contained with no composer/AI footprint. (`composer.lock` is already
listed.)

### 4. wordpress.org flag fixes

- **`readme.txt`** (new, required). WP.org format:
  - Header: Contributors, Tags, `Requires at least: 6.5`, `Tested up to: 6.7`,
    `Stable tag: 0.1.0`, `Requires PHP: 8.3`, `License: GPLv2 or later`,
    `License URI`.
  - Short + long Description, Installation, FAQ, Changelog (mirror
    `CHANGELOG.md`).
  - **External services** section (required by WP.org): disclose that the plugin
    loads `client.js` from the admin-configured Allegro instance on every
    front-end page, and that the admin settings screen contacts the Allegro
    instance URL (`/up`, `/client.js`) and `https://docs.allegrocdp.com`. Link
    to Allegro's terms and privacy policy.
- **Plugin header** (`wp-allegro-audience.php`): `Tested up to: 7.0` → `6.7`;
  add `License: GPLv2 or later` and
  `License URI: https://www.gnu.org/licenses/gpl-2.0.html`.
- **Direct-access guards**: add `defined( 'ABSPATH' ) || exit;` near the top of
  `src/main.php` and both feature class files.
- **`uninstall.php`** (new): delete the `allegro_audience_tenant_url` option on
  uninstall.

### Out of scope / noted only

- The untracked `build/` directory is not shipped and not loaded — left as-is.
- The `.github/built-release.yml` workflow builds a `*-built` branch (Alley
  convention). WP.org deploys use SVN + `.distignore`; not changed here, noted
  for follow-up if desired.
- `Requires PHP: 8.3` is genuinely required (typed class constants in the code);
  left unchanged.

## Verification

- `composer phpcs`, `composer phpstan` (level max), `composer rector` pass.
- `composer phpunit` passes (both feature tests).
- Grep confirms no remaining references to `Alley\WP\Types`, `Alley\WP\Features`,
  or `wp-type-extensions` in `src/`, tests, or the bootstrap.
- Manual check: with `vendor/` absent, the plugin's runtime classes still load
  (bootstrap requires them directly).
