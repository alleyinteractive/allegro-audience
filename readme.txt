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
