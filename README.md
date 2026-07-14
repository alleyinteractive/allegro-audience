<div align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://docs.allegrocdp.com/img/allegro-logo-horizontal-white.svg">
    <img alt="Allegro Audience" src="https://docs.allegrocdp.com/img/allegro-logo-horizontal-black.svg" height="40">
  </picture>
</div>

# Allegro Audience WordPress Plugin

Contributors: alleyinteractive

Tags: alleyinteractive, allegro, audience

Stable tag: 0.1.0

Requires at least: 6.5

Tested up to: 6.8

Requires PHP: 8.3

License: GPL v2 or later

[![Testing Suite](https://github.com/alleyinteractive/wp-allegro-audience/actions/workflows/all-pr-tests.yml/badge.svg?branch=develop)](https://github.com/alleyinteractive/wp-allegro-audience/actions/workflows/all-pr-tests.yml)

Connect your WordPress site to [Allegro Audience](https://allegrocdp.com/). Enter your organization URL, verify the connection, and Allegro Audience's `client.js` is automatically injected on every front-end page.

## Installation

Install via Composer:

```bash
composer require alleyinteractive/wp-allegro-audience
```

Or upload the plugin ZIP through **Plugins → Add New** in WordPress.

## Configuration

1. Activate the plugin.
2. Go to **Settings → Allegro Audience**.
3. Enter your Allegro Audience organization URL (e.g. `https://your-org.allegrocdp.com`).
4. Click **Save & Verify** — the plugin confirms the URL is a live Allegro Audience instance and checks that CORS is configured for your domain.
5. Once verified, `client.js` is injected automatically on every front-end page.

## How it works

- **Health check** — on save, the plugin makes a server-side request to `<org-url>/up` and verifies the `x-allegro-health: 1` response header.
- **CORS check** — the browser fetches `<org-url>/client.js` directly to confirm cross-origin access is configured. If it isn't, the settings page shows a warning with a link to the [Allegro Audience developer docs](https://docs.allegrocdp.com/developer/).
- **Script injection** — once verified, `<script src="<org-url>/client.js"></script>` is output in `wp_head` on every front-end page.

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

Run `npm run release` to bump the version and trigger the built-release workflow, which creates a versioned tag.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

Maintained by [Alley Interactive](https://alley.com/). Like what you see? [Come work with us](https://alley.com/careers/).

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.
