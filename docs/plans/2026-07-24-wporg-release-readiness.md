# wordpress.org Release-Readiness Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the Allegro Audience plugin submittable to wordpress.org — no composer required at runtime, the `wp-type-extensions` abstraction removed, and every wordpress.org review flag resolved.

**Architecture:** Features become plain classes with a `boot()` method; the bootstrap requires them directly (no composer autoloader). Composer remains for dev tooling only. Metadata/security fixes (readme.txt, header, ABSPATH guards, uninstall.php) bring it to directory standards.

**Tech Stack:** PHP 8.3, WordPress 6.5+, composer (dev-only: phpcs/phpstan/rector/phpunit via Mantle Testkit).

## Global Constraints

- Namespace: `Alley\WP\Allegro_Audience\...`, features under `...\Features\`.
- PHP 8.3+; `declare(strict_types=1);` in every PHP file.
- Class files use WordPress `class-{slug}.php` naming.
- Must pass PHPStan level max, phpcs (alley-coding-standards), rector.
- The shipped plugin must run with **no `vendor/` directory** present.
- No runtime composer dependencies; `require` in composer.json is `{ "php": "^8.3" }` only.
- Do NOT touch the untracked `build/` directory or the `.github` workflows.

---

### Task 1: Eliminate the Feature/Group abstraction

**Files:**
- Modify: `src/features/class-allegro-settings.php`
- Modify: `src/features/class-load-client-script.php`
- Modify: `src/main.php`

**Interfaces:**
- Produces: `Allegro_Settings` and `Load_Client_Script` remain plain classes each with a public `boot(): void` method. `main(): void` in namespace `Alley\WP\Allegro_Audience` instantiates and boots both.

- [ ] **Step 1: Remove the interface from `Allegro_Settings`**

In `src/features/class-allegro-settings.php`, delete the line:
```php
use Alley\WP\Types\Feature;
```
and change:
```php
class Allegro_Settings implements Feature {
```
to:
```php
class Allegro_Settings {
```

- [ ] **Step 2: Remove the interface from `Load_Client_Script`**

In `src/features/class-load-client-script.php`, delete the line:
```php
use Alley\WP\Types\Feature;
```
and change:
```php
class Load_Client_Script implements Feature {
```
to:
```php
class Load_Client_Script {
```

- [ ] **Step 3: Rewrite `src/main.php` to boot features directly**

Replace the full contents of `src/main.php` with:
```php
<?php
/**
 * The main plugin function
 *
 * @package wp-allegro-audience
 */

declare(strict_types=1);

namespace Alley\WP\Allegro_Audience;

use Alley\WP\Allegro_Audience\Features\Allegro_Settings;
use Alley\WP\Allegro_Audience\Features\Load_Client_Script;

defined( 'ABSPATH' ) || exit;

/**
 * Instantiate the plugin.
 */
function main(): void {
	( new Allegro_Settings() )->boot();
	( new Load_Client_Script() )->boot();
}
```

- [ ] **Step 4: Verify no references to the removed packages remain in src**

Run: `grep -rn "Alley\\\\WP\\\\Types\|Alley\\\\WP\\\\Features\|wp-type-extensions" src/`
Expected: no output (exit code 1).

- [ ] **Step 5: Commit**

```bash
git add src/main.php src/features/class-allegro-settings.php src/features/class-load-client-script.php
git commit -m "refactor: remove wp-type-extensions Feature/Group abstraction"
```

---

### Task 2: Drop composer at runtime

**Files:**
- Modify: `wp-allegro-audience.php`
- Modify: `composer.json`

**Interfaces:**
- Consumes: `main()`, `Allegro_Settings`, `Load_Client_Script` from Task 1.
- Produces: bootstrap loads all runtime classes via `require_once` (no autoloader); `WP_ALLEGRO_AUDIENCE_DIR` still defined.

- [ ] **Step 1: Rewrite the bootstrap loader**

In `wp-allegro-audience.php`, replace the entire composer block (the `if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) { ... } else { ... }` block AND the subsequent `require_once __DIR__ . '/src/main.php';`) — i.e. everything from the `// Check if Composer is installed` comment through `require_once __DIR__ . '/src/main.php';` — with:
```php
// Load the plugin's runtime classes (no Composer autoloader required).
require_once __DIR__ . '/src/features/class-allegro-settings.php';
require_once __DIR__ . '/src/features/class-load-client-script.php';
require_once __DIR__ . '/src/main.php';
```
Leave the header docblock, the `namespace` line, the `if ( ! defined( 'ABSPATH' ) ) { exit; }` guard, the `WP_ALLEGRO_AUDIENCE_DIR` define, and the final `main();` call intact.

- [ ] **Step 2: Update composer.json require/require-dev**

In `composer.json`, change the `require` block to:
```json
    "require": {
        "php": "^8.3"
    },
```
and add `alleyinteractive/composer-wordpress-autoloader` to `require-dev` (keep the existing dev deps, sorted):
```json
    "require-dev": {
        "alleyinteractive/alley-coding-standards": "^2.0",
        "alleyinteractive/composer-wordpress-autoloader": "^1.0",
        "mantle-framework/testkit": "^1.15",
        "rector/rector": "^2.0",
        "szepeviktor/phpstan-wordpress": "^2.0"
    },
```
Do not change `extra.wordpress-autoloader`, `autoload-dev`, `config`, or `scripts`.

- [ ] **Step 3: Regenerate the autoloader / lockfile for dev**

Run: `composer update alleyinteractive/composer-wordpress-autoloader --no-interaction 2>&1 | tail -5`
Expected: composer resolves; `composer-wordpress-autoloader` moves to dev, `wp-type-extensions` is removed from the lockfile.

- [ ] **Step 4: Verify the runtime classes load without the composer autoloader**

Run:
```bash
php -r 'define("ABSPATH", __DIR__."/"); require "wp-allegro-audience.php";' 2>&1 | grep -i "fatal\|error" || echo "LOADS CLEAN"
```
Expected: `LOADS CLEAN` (the plugin file requires its classes directly; WordPress function calls inside `main()` hooks are only registered, not executed, so no undefined-function fatal at require time). If undefined-function errors appear from `add_action` etc., that is expected only if `main()` runs — confirm the output shows no *class not found* / *require failed* fatals.

- [ ] **Step 5: Confirm no runtime composer references remain**

Run: `grep -n "wordpress-autoload\|InstalledVersions\|Composer is not installed" wp-allegro-audience.php || echo "CLEAN"`
Expected: `CLEAN`.

- [ ] **Step 6: Commit**

```bash
git add wp-allegro-audience.php composer.json composer.lock
git commit -m "refactor: load runtime classes without Composer autoloader"
```

---

### Task 3: Add direct-access guards and uninstall cleanup

**Files:**
- Modify: `src/features/class-allegro-settings.php`
- Modify: `src/features/class-load-client-script.php`
- Create: `uninstall.php`

**Interfaces:**
- Consumes: `Allegro_Settings::OPTION_TENANT_URL` constant value `allegro_audience_tenant_url`.

- [ ] **Step 1: Add ABSPATH guard to `Allegro_Settings`**

In `src/features/class-allegro-settings.php`, immediately after the `use ...;` import lines and before the class docblock, add:
```php
defined( 'ABSPATH' ) || exit;
```

- [ ] **Step 2: Add ABSPATH guard to `Load_Client_Script`**

In `src/features/class-load-client-script.php`, immediately after the `namespace ...;` line (there are no `use` imports after Task 1), add:
```php

defined( 'ABSPATH' ) || exit;
```

- [ ] **Step 3: Create `uninstall.php`**

Create `uninstall.php` with:
```php
<?php
/**
 * Uninstall handler for Allegro Audience.
 *
 * Removes plugin options when the plugin is deleted via the WordPress admin.
 *
 * @package wp-allegro-audience
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'allegro_audience_tenant_url' );
```

- [ ] **Step 4: Verify phpcs passes on the changed PHP**

Run: `composer phpcs -- src/ uninstall.php 2>&1 | tail -20`
Expected: no errors (warnings that pre-existed are acceptable; no new violations).

- [ ] **Step 5: Commit**

```bash
git add src/features/class-allegro-settings.php src/features/class-load-client-script.php uninstall.php
git commit -m "feat: add direct-access guards and uninstall cleanup"
```

---

### Task 4: Fix plugin header and add readme.txt

**Files:**
- Modify: `wp-allegro-audience.php`
- Create: `readme.txt`

- [ ] **Step 1: Fix the plugin header**

In `wp-allegro-audience.php`, in the header docblock, change:
```php
 * Tested up to: 7.0
```
to:
```php
 * Tested up to: 6.7
```
and add these two lines immediately after the `Tested up to:` line:
```php
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
```

- [ ] **Step 2: Create `readme.txt`**

Create `readme.txt` with:
```
=== Allegro Audience ===
Contributors: alleyinteractive
Tags: analytics, audience, cdp, allegro
Requires at least: 6.5
Tested up to: 6.7
Stable tag: 0.1.0
Requires PHP: 8.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Load Allegro Audience on your WordPress site and connect it to your Allegro CDP instance.

== Description ==

Allegro Audience connects your WordPress site to your Allegro CDP instance. After
you enter and verify your Allegro organization URL on the settings screen, the
plugin loads the Allegro `client.js` script on every front-end page so Allegro can
collect audience data for your site.

Features:

* A **Settings → Allegro Audience** screen to enter and verify your Allegro
  organization URL.
* A server-side health check and a browser CORS check so you can confirm the
  connection before going live.
* Automatic injection of the Allegro `client.js` script on the front end once a
  URL is configured.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-allegro-audience`
   directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings → Allegro Audience** and enter your Allegro organization URL,
   then click **Save & Verify**.

== Frequently Asked Questions ==

= Where do I find my Allegro organization URL? =

Your Allegro organization URL is the base URL of your Allegro CDP instance, for
example `https://your-org.allegrocdp.com`. See the Allegro developer
documentation at https://docs.allegrocdp.com/developer/ for details.

= The settings screen shows a CORS warning. What does that mean? =

Your Allegro instance must allow cross-origin requests from your WordPress
site's domain. Update the CORS configuration on your Allegro instance, then
re-verify.

== External services ==

This plugin connects to your Allegro CDP instance and to the Allegro
documentation site. It is required for the plugin to function.

* **Allegro CDP instance** (the organization URL you configure): On every
  front-end page the plugin loads `client.js` from `{your-organization-url}/client.js`
  so Allegro can collect audience data from visitors' browsers. On the admin
  settings screen the plugin also requests `{your-organization-url}/up` (server
  health check) and `{your-organization-url}/client.js` (CORS check). What data
  is collected is governed by Allegro's terms and privacy policy. See
  https://allegroaudience.com/ for terms of service and privacy policy.
* **Allegro documentation** (`https://docs.allegrocdp.com`): linked from the
  settings screen for developer documentation. No site or visitor data is sent.

== Changelog ==

= 0.1.0 =
* Initial release.
```

- [ ] **Step 3: Verify the header parses and versions match**

Run: `grep -n "Tested up to:\|License:\|License URI:\|Version:" wp-allegro-audience.php && grep -n "Stable tag:" readme.txt`
Expected: header shows `Tested up to: 6.7`, both License lines, `Version: 0.1.0`; readme shows `Stable tag: 0.1.0` (matches Version).

- [ ] **Step 4: Commit**

```bash
git add wp-allegro-audience.php readme.txt
git commit -m "docs: add readme.txt and fix plugin header for wordpress.org"
```

---

### Task 5: Update .deployignore and features README

**Files:**
- Modify: `.deployignore`
- Modify: `src/features/README.md`

- [ ] **Step 1: Add entries to `.deployignore`**

Add these lines to `.deployignore` (keep the file alphabetically-ish grouped; append is fine):
```
CLAUDE.md
composer.json
docs
vendor
```

- [ ] **Step 2: Rewrite `src/features/README.md` for the plain-class pattern**

Replace the full contents of `src/features/README.md` with:
```markdown
# Features

Features are plain PHP classes with a public `boot(): void` method. Each feature
lives in `src/features/` in the namespace `Alley\WP\Allegro_Audience\Features` and
uses the WordPress `class-{slug}.php` file-naming convention.

A feature registers its own hooks inside `boot()`:

```php
<?php

declare(strict_types=1);

namespace Alley\WP\Allegro_Audience\Features;

defined( 'ABSPATH' ) || exit;

class Hello {
	public function boot(): void {
		add_action( 'init', $this->say_hello( ... ) );
	}

	public function say_hello(): void {
		// ...
	}
}
```

Register the feature in `src/main.php` and require its file in the plugin
bootstrap (`wp-allegro-audience.php`):

```php
function main(): void {
	( new Allegro_Settings() )->boot();
	( new Load_Client_Script() )->boot();
	( new Hello() )->boot();
}
```
```

- [ ] **Step 3: Verify deployignore contains the new entries**

Run: `grep -E "^(CLAUDE.md|composer.json|docs|vendor)$" .deployignore`
Expected: all four lines present.

- [ ] **Step 4: Commit**

```bash
git add .deployignore src/features/README.md
git commit -m "chore: exclude dev files from release and document feature pattern"
```

---

### Task 6: Full verification pass

**Files:** none (verification only).

- [ ] **Step 1: Static analysis — PHPStan level max**

Run: `composer phpstan 2>&1 | tail -20`
Expected: `[OK] No errors`.

- [ ] **Step 2: Coding standards**

Run: `composer phpcs 2>&1 | tail -20`
Expected: no errors.

- [ ] **Step 3: Rector dry-run**

Run: `composer rector 2>&1 | tail -20`
Expected: no changes suggested (or only pre-existing/unrelated).

- [ ] **Step 4: Unit/feature tests (requires wp-env test DB)**

Run: `composer phpunit 2>&1 | tail -20`
Expected: all tests pass. If the environment has no MySQL/wp test install, note that this step must be run where the Mantle test DB is available (`composer serve` / wp-env), and confirm the two feature tests are unchanged and still only instantiate the feature classes + call `boot()`.

- [ ] **Step 5: Confirm the release archive would be self-contained**

Run: `grep -rn "wp-type-extensions\|Alley\\\\WP\\\\Types\|Alley\\\\WP\\\\Features" src/ tests/ wp-allegro-audience.php uninstall.php || echo "NO STALE REFS"`
Expected: `NO STALE REFS`.

- [ ] **Step 6: Final commit if any fixes were applied during verification**

```bash
git add -A
git commit -m "chore: verification fixes for wordpress.org readiness" || echo "nothing to commit"
```

---

## Self-Review Notes

- **Spec coverage:** Task 1 = remove abstraction; Task 2 = drop runtime composer + composer.json; Task 3 = ABSPATH guards + uninstall.php; Task 4 = header fixes + readme.txt (incl. External services disclosure); Task 5 = .deployignore (CLAUDE.md + vendor/composer/docs) + features README; Task 6 = verification. All spec sections mapped.
- **Type consistency:** `boot(): void`, `main(): void`, `OPTION_TENANT_URL` = `allegro_audience_tenant_url` used consistently across tasks.
- **Note on Task 2 Step 4:** the smoke test only proves classes/requires resolve; full runtime behavior is covered by phpunit (Task 6 Step 4).
