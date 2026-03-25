<?php
/**
 * Plugin activation and deactivation functionality.
 *
 * @package Production_Image_Redirector
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation / deactivation lifecycle.
 *
 * @since 1.0.0
 */
class Production_Image_Redirector_Activator {

	/**
	 * Plugin activation hook.
	 * Handles both single site and multisite activation.
	 *
	 * @param bool $network_wide Whether the plugin is being network activated.
	 * @since 1.0.0
	 */
	public static function activate( $network_wide = false ) {
		if ( $network_wide && is_multisite() ) {
			self::activate_network_wide();
		} else {
			self::activate_single_site();
		}
	}

	/**
	 * Activate plugin for a single site.
	 * Can be called directly or via wpmu_new_blog hook.
	 *
	 * @param int|null $blog_id Blog ID when invoked for a new site, or null.
	 * @since 1.0.0
	 */
	public static function activate_single_site( $blog_id = null ) {
		if ( null !== $blog_id ) {
			switch_to_blog( $blog_id );
		}

		$default_options = array(
			'production_url'  => '',
			'enable_redirect' => 0,
		);

		if ( false === get_option( PRODUCTION_IMAGE_REDIRECTOR_OPTION_NAME ) ) {
			add_option( PRODUCTION_IMAGE_REDIRECTOR_OPTION_NAME, $default_options );
		}

		if ( null !== $blog_id ) {
			restore_current_blog();
		}
	}

	/**
	 * Activate plugin network-wide for multisite.
	 *
	 * @since 1.0.0
	 */
	private static function activate_network_wide() {
		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			self::activate_single_site();
			restore_current_blog();
		}
	}

	/**
	 * Plugin deactivation hook.
	 * Handles both single site and multisite deactivation.
	 *
	 * @param bool $_network_wide Unused; required by WordPress hook signature.
	 * @since 1.0.0
	 */
	public static function deactivate( $_network_wide = false ) {
		// Options are retained so configuration survives re-activation.
	}
}
