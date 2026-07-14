# Allegro Audience

Contributors: alleyinteractive

Tags: alleyinteractive, allegro, cdp, audience

Stable tag: 0.1.0

Requires at least: 6.5

Tested up to: 6.8

Requires PHP: 8.3

License: GPL v2 or later

[![Testing Suite](https://github.com/alleyinteractive/wp-allegro-audience/actions/workflows/all-pr-tests.yml/badge.svg?branch=develop)](https://github.com/alleyinteractive/wp-allegro-audience/actions/workflows/all-pr-tests.yml)

WordPress plugin to connect your site to an [Allegro CDP](https://allegrocdp.com/) instance. Provides a simple settings page where you enter your Allegro organization URL. The plugin validates the connection and then injects the Allegro `client.js` script on every page of your site.

## Installation

Install via Composer:

```bash
composer require alleyinteractive/wp-allegro-audience
```

Or upload the plugin ZIP through **Plugins → Add New** in WordPress.

## Configuration

1. Activate the plugin.
2. Go to **Settings → Allegro Audience**.
3. Enter your Allegro organization URL (e.g. `https://your-org.allegrocdp.com`).
4. Click **Save & Verify** — the plugin will confirm the URL is a live Allegro instance.
5. Once verified, `client.js` will be injected automatically on every front-end page.

## How it works

- **Health check** — on save, the plugin sends a server-side request to `<org-url>/up` and looks for the `x-allegro-health: 1` response header to confirm the URL points to an Allegro instance.
- **Script injection** — after a successful health check, the plugin outputs `<script src="<org-url>/client.js"></script>` in the front-end `<head>`.

## Development

```sh
composer install
composer serve   # starts wp-env
```

Run tests and linting:

```sh
composer test       # lint + PHPUnit
composer phpunit    # PHPUnit only
composer phpstan    # static analysis (level max)
composer phpcs      # coding standards
```

## Releasing

Run `npm run release` to bump the version and trigger the built-release workflow, which compiles the plugin and creates a versioned tag containing all required assets.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

Maintained by [Alley Interactive](https://alley.com/). Like what you see? [Come work with us](https://alley.com/careers/).

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.
