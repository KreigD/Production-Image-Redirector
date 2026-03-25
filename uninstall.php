<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Production_Image_Redirector
 * @since 1.1.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/constants.php';

/**
 * Delete plugin options for the current site.
 *
 * @since 1.1.0
 */
function production_image_redirector_delete_site_options() {
	delete_option( PRODUCTION_IMAGE_REDIRECTOR_OPTION_NAME );
}

if ( ! is_multisite() ) {
	production_image_redirector_delete_site_options();
	return;
}

$site_ids = get_sites(
	array(
		'fields' => 'ids',
		'number' => 0,
	)
);

foreach ( $site_ids as $site_id ) {
	switch_to_blog( $site_id );
	production_image_redirector_delete_site_options();
	restore_current_blog();
}
