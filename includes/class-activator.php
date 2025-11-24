<?php

/**
 * Plugin activation and deactivation functionality
 *
 * @package Production_Image_Redirector
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

class Production_Image_Redirector_Activator
{

	/**
	 * Plugin activation hook
	 * Handles both single site and multisite activation
	 */
	public static function activate($network_wide = false)
	{
		if ($network_wide && is_multisite()) {
			// Network activation: set defaults for all sites
			self::activate_network_wide();
		} else {
			// Single site activation
			self::activate_single_site();
		}

		// Flush rewrite rules if needed
		flush_rewrite_rules();
	}

	/**
	 * Activate plugin for a single site
	 * Can be called directly or via wpmu_new_blog hook
	 */
	public static function activate_single_site($blog_id = null)
	{
		// If blog_id is provided, switch to that blog
		if ($blog_id !== null) {
			switch_to_blog($blog_id);
		}

		// Set default options
		$default_options = array(
			'production_url' => '',
			'enable_redirect' => 0
		);

		// Only add option if it doesn't exist
		if (get_option('production_image_redirector_settings') === false) {
			add_option('production_image_redirector_settings', $default_options);
		}

		// Restore if we switched
		if ($blog_id !== null) {
			restore_current_blog();
		}
	}

	/**
	 * Activate plugin network-wide for multisite
	 */
	private static function activate_network_wide()
	{
		global $wpdb;

		// Get all site IDs
		$site_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

		// Set default options for each site
		$default_options = array(
			'production_url' => '',
			'enable_redirect' => 0
		);

		foreach ($site_ids as $site_id) {
			switch_to_blog($site_id);
			self::activate_single_site();
			restore_current_blog();
		}
	}

	/**
	 * Plugin deactivation hook
	 * Handles both single site and multisite deactivation
	 */
	public static function deactivate($network_wide = false)
	{
		if ($network_wide && is_multisite()) {
			// Network deactivation: flush rewrite rules for all sites
			global $wpdb;
			$site_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

			foreach ($site_ids as $site_id) {
				switch_to_blog($site_id);
				flush_rewrite_rules();
				restore_current_blog();
			}
		} else {
			// Single site deactivation
			flush_rewrite_rules();
		}

		// Note: We keep the options in case user wants to reactivate
		// If you want to clean up options, uncomment the line below:
		// delete_option('production_image_redirector_settings');
	}

	/**
	 * Plugin uninstall hook (called when plugin is deleted)
	 * Handles both single site and multisite uninstall
	 */
	public static function uninstall()
	{
		// Check if this is a multisite uninstall
		if (is_multisite() && is_network_admin()) {
			// Network uninstall: clean up options for all sites
			global $wpdb;
			$site_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

			foreach ($site_ids as $site_id) {
				switch_to_blog($site_id);
				delete_option('production_image_redirector_settings');
				restore_current_blog();
			}
		} else {
			// Single site uninstall: clean up options
			delete_option('production_image_redirector_settings');
		}
	}
}
