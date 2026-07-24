<?php
/**
 * Uninstall handler for Allegro Audience.
 *
 * Removes plugin options when the plugin is deleted via the WordPress admin.
 *
 * @package allegro-audience
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'allegro_audience_tenant_url' );
