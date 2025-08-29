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
	 */
	public static function activate()
	{
		// Set default options
		$default_options = array(
			'production_url' => '',
			'enable_redirect' => 0
		);

		add_option('production_image_redirector_settings', $default_options);

		// Flush rewrite rules if needed
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook
	 */
	public static function deactivate()
	{
		// Flush rewrite rules
		flush_rewrite_rules();

		// Note: We keep the options in case user wants to reactivate
		// If you want to clean up options, uncomment the line below:
		// delete_option('production_image_redirector_settings');
	}

	/**
	 * Plugin uninstall hook (called when plugin is deleted)
	 */
	public static function uninstall()
	{
		// Clean up options when plugin is completely removed
		delete_option('production_image_redirector_settings');
	}
}
