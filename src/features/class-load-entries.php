<?php
/**
 * Load_Entries class file
 *
 * @package wp-allegro-audience
 */

declare(strict_types=1);

namespace Alley\WP\Allegro_Audience\Features;

use Alley\WP\Types\Feature;

/**
 * Load the built entries from the build directory.
 *
 * Entries that include an `index.php` file will be loaded.
 */
class Load_Entries implements Feature {

	/**
	 * Constructor.
	 *
	 * @param bool $cache Whether to use APCu caching for entry files. Default false.
	 */
	public function __construct( public readonly bool $cache = false ) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		foreach ( $this->get_entry_files() as $path ) {
			if ( $this->is_valid_path( $path ) ) {
				require_once $path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.IncludingFile, WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			}
		}
	}

	/**
	 * Verify the path is a real file within the plugin build directory.
	 *
	 * @param string $path Absolute path to check.
	 */
	private function is_valid_path( string $path ): bool {
		$real = realpath( $path );
		return false !== $real
			&& str_starts_with( $real, WP_ALLEGRO_AUDIENCE_DIR . DIRECTORY_SEPARATOR . 'build' );
	}

	/**
	 * Get the list of entry files.
	 *
	 * Uses APCu caching when enabled and available.
	 *
	 * @return string[] List of entry file paths.
	 */
	protected function get_entry_files(): array {
		if ( $this->cache && function_exists( 'apcu_fetch' ) ) {
			/** @var string[]|false */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
			$cache = apcu_fetch( 'wp_allegro_audience_entries' );

			if ( is_array( $cache ) ) {
				return $cache;
			}
		}

		$files = glob( WP_ALLEGRO_AUDIENCE_DIR . '/build/**/index.php' ) ?: [];

		if ( $this->cache && function_exists( 'apcu_store' ) ) {
			apcu_store( 'wp_allegro_audience_entries', $files, HOUR_IN_SECONDS );
		}

		return $files;
	}
}
